<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../admin.php");
    exit();
}

// Year selector
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$min_year_stmt = $pdo->query("SELECT MIN(YEAR(created_at)) FROM bookings");
$min_year = (int)($min_year_stmt->fetchColumn() ?: date('Y'));
$max_year = (int)date('Y');

// Property filter (admin can view all or one property)
$selected_pid = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
$all_properties_stmt = $pdo->query("SELECT id, property_name, owner_id FROM properties ORDER BY property_name");
$all_properties = $all_properties_stmt->fetchAll();

// Build monthly data
function build_monthly_data($pdo, $year, $pid = 0) {
    $monthly = [];
    for ($m = 1; $m <= 12; $m++) {
        $month_start = sprintf('%04d-%02d-01', $year, $m);
        $month_end   = date('Y-m-t', strtotime($month_start));

        $where_pid = $pid > 0 ? "AND b.property_id = $pid" : "";
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) AS total_bookings,
                COUNT(DISTINCT b.property_id) AS active_properties,
                COALESCE(SUM(b.total_price),0) AS total_revenue,
                COALESCE(SUM(b.amount_paid),0) AS total_paid,
                COALESCE(SUM(CASE WHEN b.booking_type='online' THEN b.amount_paid ELSE 0 END),0) AS online_paid,
                COALESCE(SUM(CASE WHEN b.booking_type='inplace' THEN b.amount_paid ELSE 0 END),0) AS inplace_paid,
                COALESCE(SUM(CASE WHEN b.status='confirmed' THEN 1 ELSE 0 END),0) AS confirmed,
                COALESCE(SUM(CASE WHEN b.status='cancelled' THEN 1 ELSE 0 END),0) AS cancelled,
                COALESCE(SUM(CASE WHEN b.status='checked_in' THEN 1 ELSE 0 END),0) AS checked_in,
                COALESCE(SUM(CASE WHEN b.status='checked_out' THEN 1 ELSE 0 END),0) AS checked_out
            FROM bookings b
            WHERE DATE(b.created_at) BETWEEN ? AND ? $where_pid
        ");
        $stmt->execute([$month_start, $month_end]);
        $row = $stmt->fetch();
        $row['commission'] = $row['online_paid'] * 0.2;

        // New properties registered
        $np_stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE DATE(created_at) BETWEEN ? AND ?");
        $np_stmt->execute([$month_start, $month_end]);
        $row['new_properties'] = (int)$np_stmt->fetchColumn();

        // New users registered
        $nu_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ? AND role != 'admin'");
        $nu_stmt->execute([$month_start, $month_end]);
        $row['new_users'] = (int)$nu_stmt->fetchColumn();

        // Commission paid/received
        $cpd_where = $pid > 0 ? "AND property_id = $pid" : "";
        $cpd_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM hotel_service_payments WHERE status='approved' AND DATE(created_at) BETWEEN ? AND ? $cpd_where");
        $cpd_stmt->execute([$month_start, $month_end]);
        $row['commission_paid'] = (float)$cpd_stmt->fetchColumn();

        // Top property
        if ($pid === 0) {
            $tp_stmt = $pdo->prepare("
                SELECT p.property_name, COUNT(*) AS cnt
                FROM bookings b JOIN properties p ON b.property_id = p.id
                WHERE DATE(b.created_at) BETWEEN ? AND ?
                GROUP BY b.property_id ORDER BY cnt DESC LIMIT 1
            ");
            $tp_stmt->execute([$month_start, $month_end]);
            $row['top_property'] = $tp_stmt->fetchColumn() ?: '—';
        }

        $monthly[$m] = array_merge($row, [
            'month_label' => date('F', mktime(0,0,0,$m,1)),
            'month_num'   => $m,
            'month_start' => $month_start,
            'month_end'   => $month_end,
        ]);
    }
    return $monthly;
}

$monthly_data = build_monthly_data($pdo, $selected_year, $selected_pid);

// Handle print request
$print_month = isset($_GET['print']) ? (int)$_GET['print'] : 0;
$is_print    = $print_month > 0;

if ($is_print && isset($monthly_data[$print_month])) {
    $pd = $monthly_data[$print_month];
    $filter_label = $selected_pid > 0
        ? (array_values(array_filter($all_properties, fn($p) => $p['id'] == $selected_pid))[0]['property_name'] ?? 'All Properties')
        : 'All Properties';

    // Booking list for the month
    $pid_where = $selected_pid > 0 ? "AND b.property_id = $selected_pid" : "";
    $bl_stmt = $pdo->prepare("
        SELECT b.*, pr.room_name, p.property_name
        FROM bookings b
        JOIN property_rooms pr ON b.room_id = pr.id
        JOIN properties p ON b.property_id = p.id
        WHERE DATE(b.created_at) BETWEEN ? AND ? $pid_where
        ORDER BY b.created_at DESC
        LIMIT 80
    ");
    $bl_stmt->execute([$pd['month_start'], $pd['month_end']]);
    $booking_rows = $bl_stmt->fetchAll();

    // Top 5 properties by revenue
    $top_props_stmt = $pdo->prepare("
        SELECT p.property_name, COUNT(*) AS bk_count, COALESCE(SUM(b.amount_paid),0) AS revenue
        FROM bookings b JOIN properties p ON b.property_id = p.id
        WHERE DATE(b.created_at) BETWEEN ? AND ?
        GROUP BY b.property_id ORDER BY revenue DESC LIMIT 5
    ");
    $top_props_stmt->execute([$pd['month_start'], $pd['month_end']]);
    $top_props = $top_props_stmt->fetchAll();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Monthly Report — <?php echo $pd['month_label']; ?> <?php echo $selected_year; ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Arial, sans-serif; background:#fff; color:#1a2340; font-size:13px; }

        /* HEADER */
        .report-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:28px 40px 22px; border-bottom:3px solid #003580;
        }
        .header-left { display:flex; align-items:center; gap:18px; }
        .header-left img { height:64px; }
        .brand-name { font-size:22px; font-weight:900; color:#003580; }
        .brand-tagline { font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:0.12em; margin-top:2px; }
        .header-right { text-align:right; }
        .report-title { font-size:20px; font-weight:800; color:#003580; }
        .report-subtitle { font-size:11px; color:#64748b; margin-top:4px; }
        .admin-badge { display:inline-block; margin-top:6px; padding:4px 14px; background:#003580; color:#febb02; border-radius:999px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; }

        /* SUMMARY BAND */
        .summary-band { display:grid; grid-template-columns:repeat(5,1fr); border-bottom:2px solid #e2e8f0; }
        .summary-cell { padding:16px 18px; border-right:1px solid #e2e8f0; text-align:center; }
        .summary-cell:last-child { border-right:none; }
        .summary-cell .s-value { font-size:20px; font-weight:900; color:#003580; }
        .summary-cell .s-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-top:3px; }

        /* SECTION */
        .section { padding:22px 40px; }
        .section-title { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#003580; border-left:4px solid #febb02; padding-left:12px; margin-bottom:16px; }

        /* GRID */
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
        .stat-box { border:1.5px solid #e2e8f0; border-radius:12px; padding:14px 16px; }
        .stat-box .stat-val { font-size:17px; font-weight:900; color:#003580; }
        .stat-box .stat-lab { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-top:2px; }
        .stat-box .stat-sub { font-size:10px; color:#64748b; margin-top:4px; }

        /* TABLE */
        .report-table { width:100%; border-collapse:collapse; font-size:11px; }
        .report-table th { background:#003580; color:#fff; padding:9px 12px; text-align:left; font-weight:700; font-size:9px; text-transform:uppercase; letter-spacing:0.1em; }
        .report-table td { padding:8px 12px; border-bottom:1px solid #f1f5f9; }
        .report-table tr:nth-child(even) td { background:#f8fafc; }
        .report-table tr:last-child td { border-bottom:none; }

        .status-pill { display:inline-block; padding:2px 9px; border-radius:999px; font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; border:1.5px solid; }
        .s-confirmed  { color:#15803d; border-color:#16a34a; }
        .s-pending    { color:#b45309; border-color:#d97706; }
        .s-checked_in { color:#1d4ed8; border-color:#2563eb; }
        .s-checked_out{ color:#475569; border-color:#94a3b8; }
        .s-cancelled  { color:#dc2626; border-color:#ef4444; }

        /* TOP PROPERTIES */
        .top-props-table { width:100%; border-collapse:collapse; font-size:11.5px; }
        .top-props-table th { background:#f8fafc; color:#003580; padding:8px 14px; text-align:left; font-weight:800; font-size:9px; text-transform:uppercase; letter-spacing:0.1em; border-bottom:2px solid #e2e8f0; }
        .top-props-table td { padding:9px 14px; border-bottom:1px solid #f1f5f9; }
        .rank-badge { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; background:#003580; color:#febb02; font-size:9px; font-weight:900; }

        /* DIVIDER */
        .divider { height:1px; background:#e2e8f0; margin:0 40px; }

        /* FOOTER */
        .report-footer { background:#003580; color:#fff; padding:22px 40px; display:flex; align-items:center; justify-content:space-between; }
        .footer-left .f-company { font-size:15px; font-weight:900; }
        .footer-left .f-company span { color:#febb02; }
        .footer-left .f-tagline { font-size:9px; color:rgba(255,255,255,0.55); text-transform:uppercase; letter-spacing:0.15em; margin-top:2px; }
        .footer-mid { text-align:center; font-size:10px; color:rgba(255,255,255,0.6); line-height:1.6; }
        .footer-right { text-align:right; font-size:10px; color:rgba(255,255,255,0.55); line-height:1.7; }

        /* PRINT */
        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .no-print { display:none !important; }
            .report-footer { position:fixed; bottom:0; left:0; right:0; }
            body { padding-bottom:100px; }
        }
        @page { margin:0; size:A4; }

        /* BAR */
        .rev-bar-wrap { background:#f1f5f9; border-radius:999px; height:7px; overflow:hidden; }
        .rev-bar-fill { background:linear-gradient(90deg,#003580,#006ce4); height:100%; border-radius:999px; }
    </style>
</head>
<body>

    <div class="no-print" style="background:#f1f5f9; padding:12px 40px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e2e8f0;">
        <a href="monthly_report.php?year=<?php echo $selected_year; ?>&property_id=<?php echo $selected_pid; ?>" style="text-decoration:none; color:#003580; font-weight:700; font-size:12px;">← Back to Monthly Reports</a>
        <button onclick="window.print()" style="background:#003580; color:#fff; border:none; padding:10px 28px; border-radius:8px; font-weight:700; font-size:12px; cursor:pointer;">🖨 Print / Download PDF</button>
    </div>

    <!-- HEADER -->
    <div class="report-header">
        <div class="header-left">
            <img src="../../assets/logo.png" alt="BookingJaunt">
            <div>
                <div class="brand-name">BookingJaunt</div>
                <div class="brand-tagline">Family Travel. Securely Enjoyed.</div>
            </div>
        </div>
        <div class="header-right">
            <div class="report-title">Admin Monthly Report — <?php echo $pd['month_label']; ?> <?php echo $selected_year; ?></div>
            <div class="report-subtitle">Generated on <?php echo date('F d, Y \a\t h:i A'); ?></div>
            <span class="admin-badge">🛡 Admin View — <?php echo htmlspecialchars($filter_label); ?></span>
        </div>
    </div>

    <!-- SUMMARY BAND -->
    <div class="summary-band">
        <div class="summary-cell">
            <div class="s-value"><?php echo number_format($pd['total_bookings']); ?></div>
            <div class="s-label">Total Bookings</div>
        </div>
        <div class="summary-cell">
            <div class="s-value">LKR <?php echo number_format($pd['total_revenue']); ?></div>
            <div class="s-label">Platform Revenue</div>
        </div>
        <div class="summary-cell">
            <div class="s-value">LKR <?php echo number_format($pd['commission']); ?></div>
            <div class="s-label">Commission Earned</div>
        </div>
        <div class="summary-cell">
            <div class="s-value"><?php echo number_format($pd['new_properties']); ?></div>
            <div class="s-label">New Properties</div>
        </div>
        <div class="summary-cell">
            <div class="s-value"><?php echo number_format($pd['new_users']); ?></div>
            <div class="s-label">New Users</div>
        </div>
    </div>

    <!-- PERFORMANCE SECTION -->
    <div class="section">
        <div class="section-title">Platform Performance Breakdown</div>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-val">LKR <?php echo number_format($pd['online_paid']); ?></div>
                <div class="stat-lab">Online Revenue</div>
                <div class="stat-sub">Bookings via platform</div>
            </div>
            <div class="stat-box">
                <div class="stat-val">LKR <?php echo number_format($pd['inplace_paid']); ?></div>
                <div class="stat-lab">Physical Revenue</div>
                <div class="stat-sub">Walk-in hotel bookings</div>
            </div>
            <div class="stat-box">
                <div class="stat-val">LKR <?php echo number_format($pd['commission_paid']); ?></div>
                <div class="stat-lab">Commission Received</div>
                <div class="stat-sub">Approved hotel payments</div>
            </div>
            <div class="stat-box">
                <div class="stat-val">LKR <?php echo number_format(max(0, $pd['commission'] - $pd['commission_paid'])); ?></div>
                <div class="stat-lab">Commission Due</div>
                <div class="stat-sub">Pending from hotels</div>
            </div>
            <div class="stat-box">
                <div class="stat-val"><?php echo number_format($pd['confirmed']); ?></div>
                <div class="stat-lab">Confirmed</div>
                <div class="stat-sub">Successful reservations</div>
            </div>
            <div class="stat-box">
                <div class="stat-val"><?php echo number_format($pd['checked_in']); ?></div>
                <div class="stat-lab">Checked In</div>
                <div class="stat-sub">Active guests</div>
            </div>
            <div class="stat-box">
                <div class="stat-val"><?php echo number_format($pd['checked_out']); ?></div>
                <div class="stat-lab">Checked Out</div>
                <div class="stat-sub">Completed stays</div>
            </div>
            <div class="stat-box">
                <div class="stat-val"><?php echo number_format($pd['cancelled']); ?></div>
                <div class="stat-lab">Cancelled</div>
                <div class="stat-sub">Cancellations this month</div>
            </div>
        </div>
    </div>

    <?php if (!empty($top_props)): ?>
    <div class="divider"></div>
    <div class="section">
        <div class="section-title">Top Performing Properties — <?php echo $pd['month_label']; ?> <?php echo $selected_year; ?></div>
        <?php $max_rev = max(array_column($top_props, 'revenue')); ?>
        <table class="top-props-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Property</th>
                    <th>Bookings</th>
                    <th>Revenue (LKR)</th>
                    <th style="width:200px;">Revenue Share</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_props as $i => $tp): ?>
                <tr>
                    <td><span class="rank-badge"><?php echo $i+1; ?></span></td>
                    <td style="font-weight:700;"><?php echo htmlspecialchars($tp['property_name']); ?></td>
                    <td><?php echo number_format($tp['bk_count']); ?></td>
                    <td style="font-weight:700; color:#003580;">LKR <?php echo number_format($tp['revenue']); ?></td>
                    <td>
                        <div class="rev-bar-wrap">
                            <div class="rev-bar-fill" style="width:<?php echo $max_rev > 0 ? round(($tp['revenue']/$max_rev)*100) : 0; ?>%;"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="divider"></div>

    <!-- BOOKING LIST -->
    <div class="section">
        <div class="section-title">
            Booking Records — <?php echo $pd['month_label']; ?> <?php echo $selected_year; ?>
            (<?php echo count($booking_rows); ?> shown<?php echo count($booking_rows) >= 80 ? ', limited to 80' : ''; ?>)
        </div>
        <?php if (empty($booking_rows)): ?>
            <p style="color:#94a3b8; text-align:center; padding:24px 0; font-size:12px;">No bookings recorded for this month.</p>
        <?php else: ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Guest</th>
                    <th>Property</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Type</th>
                    <th>Total (LKR)</th>
                    <th>Paid (LKR)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($booking_rows as $i => $bk): ?>
                <tr>
                    <td style="color:#94a3b8; font-weight:700;"><?php echo $i+1; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($bk['guest_name']); ?></strong>
                        <div style="color:#94a3b8; font-size:9px;"><?php echo htmlspecialchars($bk['guest_phone']); ?></div>
                    </td>
                    <td style="font-weight:600; font-size:11px;"><?php echo htmlspecialchars($bk['property_name']); ?></td>
                    <td><?php echo htmlspecialchars($bk['room_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($bk['check_in_date'])); ?></td>
                    <td><?php echo date('M d, Y', strtotime($bk['check_out_date'])); ?></td>
                    <td style="text-transform:uppercase; font-size:9px; font-weight:700;"><?php echo $bk['booking_type']; ?></td>
                    <td style="font-weight:700;"><?php echo number_format($bk['total_price']); ?></td>
                    <td style="font-weight:700;"><?php echo number_format($bk['amount_paid']); ?></td>
                    <td><span class="status-pill s-<?php echo $bk['status']; ?>"><?php echo str_replace('_',' ',$bk['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <div class="report-footer">
        <div class="footer-left">
            <div class="f-company">Booking<span>Jaunt</span></div>
            <div class="f-tagline">Family Travel. Securely Enjoyed.</div>
        </div>
        <div class="footer-mid">
            This is a confidential admin report generated by BookingJaunt.<br>
            For queries contact: admin@bookingjaunt.com | support@bookingjaunt.com
        </div>
        <div class="footer-right">
            View: <?php echo htmlspecialchars($filter_label); ?><br>
            Period: <?php echo $pd['month_label']; ?> <?php echo $selected_year; ?><br>
            Printed: <?php echo date('d M Y, h:i A'); ?>
        </div>
    </div>

</body>
</html>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Reports — Admin | Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .sidebar-link.active { background-color: rgba(255,255,255,0.1); border-left:4px solid #febb02; color:white; border-radius:0 12px 12px 0; margin-left:-12px; padding-left:24px; }
        .custom-scrollbar::-webkit-scrollbar { width:4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background:transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:10px; }
        #sidebar.show { transform:translateX(0); }
        .month-card { background:#fff; border:1.5px solid #e2e8f0; border-radius:1.5rem; padding:1.5rem; display:flex; flex-direction:column; gap:1rem; transition:all 0.25s; }
        .month-card:hover { box-shadow:0 8px 32px rgba(0,53,128,0.10); transform:translateY(-2px); border-color:#c7d8f7; }
        .month-card.current { border-color:#003580; background:linear-gradient(135deg,#f0f7ff,#fff); }
        .stat-chip { display:flex; flex-direction:column; padding:12px 14px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0; }
        .stat-chip .val { font-size:1.1rem; font-weight:900; color:#003580; }
        .stat-chip .lab { font-size:0.55rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-top:2px; }
        .bar-wrap { background:#f1f5f9; border-radius:999px; height:6px; overflow:hidden; }
        .bar-fill { background:linear-gradient(90deg,#006ce4,#003580); height:100%; border-radius:999px; transition:width 0.8s ease; }
        .download-btn { display:flex; align-items:center; justify-content:center; gap:8px; padding:10px; border-radius:12px; border:1.5px solid #003580; color:#003580; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; text-decoration:none; transition:all 0.2s; }
        .download-btn:hover { background:#003580; color:#febb02; }
        .year-select { padding:8px 16px; border:1.5px solid #e2e8f0; border-radius:10px; font-weight:700; font-size:0.75rem; color:#003580; background:#fff; cursor:pointer; }
        .prop-select { padding:8px 16px; border:1.5px solid #e2e8f0; border-radius:10px; font-weight:700; font-size:0.75rem; color:#003580; background:#fff; cursor:pointer; }
        .summary-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:2rem; }
        .summary-box { background:#fff; border:1.5px solid #e2e8f0; border-radius:1.25rem; padding:1.25rem 1.5rem; }
        .summary-box .s-val { font-size:1.5rem; font-weight:900; color:#003580; }
        .summary-box .s-lab { font-size:0.6rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-top:3px; }
        .pill { display:inline-block; padding:2px 10px; border-radius:999px; font-size:0.6rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; border:1.5px solid; }
        .p-empty { color:#94a3b8; border-color:#e2e8f0; }
        .no-data { color:#94a3b8; text-align:center; padding:2rem 0; font-size:0.8rem; }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden" style="background:#f0f4f8;">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen">

        <!-- Top Nav -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-40 px-4 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-[#003580] hover:bg-gray-100 transition-all">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#003580,#006ce4);">
                        <i class="fas fa-calendar-alt text-white text-sm"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-[#003580]">Monthly Reports</h1>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Platform Overview — <?php echo $selected_year; ?></p>
                    </div>
                </div>
            </div>
            <form method="GET" class="flex items-center gap-3">
                <select name="property_id" onchange="this.form.submit()" class="prop-select">
                    <option value="0">All Properties</option>
                    <?php foreach ($all_properties as $prop): ?>
                        <option value="<?php echo $prop['id']; ?>" <?php echo $prop['id'] == $selected_pid ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prop['property_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="year" onchange="this.form.submit()" class="year-select">
                    <?php for ($y = $max_year; $y >= $min_year; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $selected_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </header>

        <div class="p-4 lg:p-8">

            <!-- Year Summary Strip -->
            <?php
            $y_bookings    = array_sum(array_column($monthly_data, 'total_bookings'));
            $y_revenue     = array_sum(array_column($monthly_data, 'total_paid'));
            $y_commission  = array_sum(array_column($monthly_data, 'commission'));
            $y_com_paid    = array_sum(array_column($monthly_data, 'commission_paid'));
            $y_new_users   = array_sum(array_column($monthly_data, 'new_users'));
            $y_new_props   = array_sum(array_column($monthly_data, 'new_properties'));
            ?>
            <div class="summary-strip">
                <div class="summary-box">
                    <div class="s-val"><?php echo number_format($y_bookings); ?></div>
                    <div class="s-lab">Total Bookings <?php echo $selected_year; ?></div>
                </div>
                <div class="summary-box">
                    <div class="s-val">LKR <?php echo number_format($y_revenue); ?></div>
                    <div class="s-lab">Platform Revenue</div>
                </div>
                <div class="summary-box">
                    <div class="s-val">LKR <?php echo number_format($y_commission); ?></div>
                    <div class="s-lab">Commission Earned (20%)</div>
                </div>
                <div class="summary-box">
                    <div class="s-val"><?php echo number_format($y_new_users); ?></div>
                    <div class="s-lab">New Users Registered</div>
                </div>
            </div>

            <!-- Monthly Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php
                $max_m_rev = max(array_column($monthly_data, 'total_paid')) ?: 1;
                foreach ($monthly_data as $m => $md):
                    $is_current = ($m == date('n') && $selected_year == date('Y'));
                    $has_data   = $md['total_bookings'] > 0;
                    $bar_pct    = $max_m_rev > 0 ? round(($md['total_paid']/$max_m_rev)*100) : 0;
                ?>
                <div class="month-card <?php echo $is_current ? 'current' : ''; ?>">

                    <!-- Card Header -->
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-xl font-black text-gray-900"><?php echo $md['month_label']; ?></span>
                                <?php if ($is_current): ?>
                                    <span style="display:inline-block; padding:2px 8px; border-radius:999px; font-size:8px; font-weight:800; text-transform:uppercase; background:#003580; color:#febb02;">Current</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400"><?php echo $selected_year; ?></p>
                        </div>
                        <span class="text-2xl font-black text-gray-100" style="letter-spacing:-2px;"><?php echo sprintf('%02d', $m); ?></span>
                    </div>

                    <?php if (!$has_data): ?>
                    <div class="flex flex-col items-center justify-center py-5 rounded-2xl bg-gray-50 border border-dashed border-gray-200">
                        <i class="fas fa-calendar-xmark text-2xl text-gray-200 mb-2"></i>
                        <p class="text-xs font-bold text-gray-300 uppercase tracking-widest">No bookings</p>
                    </div>
                    <?php else: ?>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-2">
                        <div class="stat-chip">
                            <span class="val"><?php echo number_format($md['total_bookings']); ?></span>
                            <span class="lab">Bookings</span>
                        </div>
                        <div class="stat-chip">
                            <span class="val" style="font-size:0.9rem;">LKR <?php echo number_format($md['total_paid']); ?></span>
                            <span class="lab">Revenue</span>
                        </div>
                        <div class="stat-chip">
                            <span class="val" style="font-size:0.85rem; color:#dc2626;">LKR <?php echo number_format($md['commission']); ?></span>
                            <span class="lab">Commission</span>
                        </div>
                        <div class="stat-chip">
                            <span class="val" style="font-size:0.85rem; color:#16a34a;">LKR <?php echo number_format($md['commission_paid']); ?></span>
                            <span class="lab">Com. Received</span>
                        </div>
                    </div>

                    <!-- Revenue Bar -->
                    <div>
                        <div class="flex justify-between text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">
                            <span>Revenue vs Peak Month</span>
                            <span><?php echo $bar_pct; ?>%</span>
                        </div>
                        <div class="bar-wrap">
                            <div class="bar-fill" style="width:<?php echo $bar_pct; ?>%;"></div>
                        </div>
                    </div>

                    <!-- Quick Badges -->
                    <div class="flex flex-wrap gap-2">
                        <span class="pill" style="color:#15803d; border-color:#16a34a;"><?php echo $md['confirmed']; ?> Confirmed</span>
                        <span class="pill" style="color:#1d4ed8; border-color:#2563eb;"><?php echo $md['checked_in']; ?> Checked In</span>
                        <span class="pill" style="color:#dc2626; border-color:#ef4444;"><?php echo $md['cancelled']; ?> Cancelled</span>
                        <?php if ($selected_pid === 0): ?>
                            <span class="pill" style="color:#7c3aed; border-color:#8b5cf6;"><?php echo $md['new_properties']; ?> New Props</span>
                        <?php endif; ?>
                        <span class="pill" style="color:#0369a1; border-color:#0ea5e9;"><?php echo $md['new_users']; ?> New Users</span>
                    </div>

                    <?php if ($selected_pid === 0 && !empty($md['top_property'])): ?>
                    <div class="flex items-center gap-2 text-[10px] text-gray-500 font-semibold px-3 py-2 bg-blue-50 rounded-xl">
                        <i class="fas fa-trophy text-yellow-400"></i>
                        Top: <?php echo htmlspecialchars($md['top_property']); ?>
                    </div>
                    <?php endif; ?>

                    <?php endif; ?>

                    <!-- Download Button -->
                    <a href="monthly_report.php?year=<?php echo $selected_year; ?>&property_id=<?php echo $selected_pid; ?>&print=<?php echo $m; ?>"
                       target="_blank" class="download-btn">
                        <i class="fas fa-file-download"></i>
                        Download <?php echo $md['month_label']; ?> Report
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>
