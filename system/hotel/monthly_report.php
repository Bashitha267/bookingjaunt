<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

// Fetch user properties
$properties_stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ? ORDER BY property_name");
$properties_stmt->execute([$user_id]);
$properties = $properties_stmt->fetchAll();

// Resolve selected property
$property = null;
if ($selected_property_id) {
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
    $stmt->execute([$selected_property_id, $user_id]);
    $property = $stmt->fetch();
}
if (!$property && !empty($properties)) {
    $property = $properties[0];
}
if (!$property) {
    header("Location: ../../index.php");
    exit();
}
$property_id = $property['id'];

// Year selector
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$min_year_stmt = $pdo->prepare("SELECT MIN(YEAR(created_at)) FROM bookings WHERE property_id = ?");
$min_year_stmt->execute([$property_id]);
$min_year = (int)($min_year_stmt->fetchColumn() ?: date('Y'));
$max_year = (int)date('Y');

// Build monthly data for selected year
$monthly_data = [];
for ($m = 1; $m <= 12; $m++) {
    $month_start = sprintf('%04d-%02d-01', $selected_year, $m);
    $month_end   = date('Y-m-t', strtotime($month_start));
    $month_label = date('F', mktime(0,0,0,$m,1));

    $stats = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_bookings,
            COALESCE(SUM(total_price),0) AS total_revenue,
            COALESCE(SUM(amount_paid),0) AS total_paid,
            COALESCE(SUM(CASE WHEN booking_type='online' THEN amount_paid ELSE 0 END),0) AS online_paid,
            COALESCE(SUM(CASE WHEN booking_type='inplace' THEN amount_paid ELSE 0 END),0) AS inplace_paid,
            COALESCE(SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END),0) AS confirmed,
            COALESCE(SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END),0) AS cancelled,
            COALESCE(SUM(CASE WHEN status='checked_in' THEN 1 ELSE 0 END),0) AS checked_in
        FROM bookings 
        WHERE property_id = ? 
          AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stats->execute([$property_id, $month_start, $month_end]);
    $row = $stats->fetch();

    // Commission
    $row['commission'] = $row['online_paid'] * 0.2;

    // Top room
    $room_stmt = $pdo->prepare("
        SELECT pr.room_name, COUNT(*) AS cnt 
        FROM bookings b 
        JOIN property_rooms pr ON b.room_id = pr.id 
        WHERE b.property_id = ? AND DATE(b.created_at) BETWEEN ? AND ?
        GROUP BY pr.room_name ORDER BY cnt DESC LIMIT 1
    ");
    $room_stmt->execute([$property_id, $month_start, $month_end]);
    $row['top_room'] = $room_stmt->fetchColumn() ?: '—';

    // Avg rating that month
    $rating_stmt = $pdo->prepare("
        SELECT ROUND(AVG(rating),1) FROM reviews 
        WHERE property_id = ? AND DATE(created_at) BETWEEN ? AND ? AND status='published'
    ");
    $rating_stmt->execute([$property_id, $month_start, $month_end]);
    $row['avg_rating'] = $rating_stmt->fetchColumn() ?: 0;

    $monthly_data[$m] = array_merge($row, [
        'month_label' => $month_label,
        'month_num'   => $m,
        'month_start' => $month_start,
        'month_end'   => $month_end,
    ]);
}

// Handle print view request
$print_month = isset($_GET['print']) ? (int)$_GET['print'] : 0;
$is_print    = $print_month > 0;

if ($is_print && isset($monthly_data[$print_month])) {
    $pd = $monthly_data[$print_month];

    // Fetch booking list for that month
    $booking_list = $pdo->prepare("
        SELECT b.*, pr.room_name 
        FROM bookings b 
        JOIN property_rooms pr ON b.room_id = pr.id 
        WHERE b.property_id = ? AND DATE(b.created_at) BETWEEN ? AND ?
        ORDER BY b.created_at DESC
    ");
    $booking_list->execute([$property_id, $pd['month_start'], $pd['month_end']]);
    $booking_rows = $booking_list->fetchAll();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['property_name']); ?> — <?php echo $pd['month_label']; ?> <?php echo $selected_year; ?> Report</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background:#fff; color:#1a2340; font-size:13px; }

        /* ===== HEADER ===== */
        .report-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:28px 40px 22px;
            border-bottom:3px solid #003580;
        }
        .header-left { display:flex; align-items:center; gap:18px; }
        .header-left img { height:64px; width:auto; }
        .brand-name { font-size:22px; font-weight:900; color:#003580; letter-spacing:-0.5px; }
        .brand-tagline { font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:0.12em; margin-top:2px; }
        .header-right { text-align:right; }
        .report-title { font-size:20px; font-weight:800; color:#003580; }
        .report-subtitle { font-size:11px; color:#64748b; margin-top:4px; }
        .property-badge {
            display:inline-block; margin-top:6px; padding:4px 14px;
            background:#003580; color:#febb02; border-radius:999px;
            font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em;
        }

        /* ===== SUMMARY BAND ===== */
        .summary-band {
            display:grid; grid-template-columns:repeat(4,1fr);
            gap:0; border-bottom:2px solid #e2e8f0;
        }
        .summary-cell {
            padding:18px 24px; border-right:1px solid #e2e8f0;
            text-align:center;
        }
        .summary-cell:last-child { border-right:none; }
        .summary-cell .s-value { font-size:22px; font-weight:900; color:#003580; }
        .summary-cell .s-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-top:3px; }

        /* ===== SECTION HEADERS ===== */
        .section { padding:24px 40px; }
        .section-title {
            font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.12em;
            color:#003580; border-left:4px solid #febb02; padding-left:12px;
            margin-bottom:16px;
        }

        /* ===== STATS GRID ===== */
        .stats-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:10px; }
        .stat-box {
            border:1.5px solid #e2e8f0; border-radius:12px; padding:14px 18px;
        }
        .stat-box .stat-val { font-size:18px; font-weight:900; color:#003580; }
        .stat-box .stat-lab { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-top:2px; }
        .stat-box .stat-sub { font-size:10px; color:#64748b; margin-top:4px; }

        /* ===== TABLE ===== */
        .report-table { width:100%; border-collapse:collapse; font-size:11.5px; }
        .report-table th {
            background:#003580; color:#fff; padding:9px 12px;
            text-align:left; font-weight:700; font-size:9px;
            text-transform:uppercase; letter-spacing:0.1em;
        }
        .report-table td { padding:9px 12px; border-bottom:1px solid #f1f5f9; }
        .report-table tr:nth-child(even) td { background:#f8fafc; }
        .report-table tr:last-child td { border-bottom:none; }

        .status-pill {
            display:inline-block; padding:2px 10px; border-radius:999px;
            font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em;
            border:1.5px solid;
        }
        .s-confirmed  { color:#15803d; border-color:#16a34a; }
        .s-pending    { color:#b45309; border-color:#d97706; }
        .s-checked_in { color:#1d4ed8; border-color:#2563eb; }
        .s-checked_out{ color:#475569; border-color:#94a3b8; }
        .s-cancelled  { color:#dc2626; border-color:#ef4444; }

        /* ===== DIVIDER ===== */
        .divider { height:1px; background:#e2e8f0; margin:0 40px; }

        /* ===== INSIGHT ROW ===== */
        .insight-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .insight-box { border:1.5px solid #e2e8f0; border-radius:12px; padding:14px 18px; }
        .insight-box .i-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-bottom:6px; }
        .insight-box .i-value { font-size:16px; font-weight:900; color:#003580; }
        .insight-box .i-sub   { font-size:10px; color:#64748b; margin-top:3px; }

        /* ===== FOOTER ===== */
        .report-footer {
            background:#003580; color:#fff;
            padding:22px 40px;
            display:flex; align-items:center; justify-content:space-between;
            margin-top:auto;
        }
        .footer-left .f-company { font-size:15px; font-weight:900; letter-spacing:-0.3px; }
        .footer-left .f-company span { color:#febb02; }
        .footer-left .f-tagline { font-size:9px; color:rgba(255,255,255,0.55); text-transform:uppercase; letter-spacing:0.15em; margin-top:2px; }
        .footer-mid { text-align:center; font-size:10px; color:rgba(255,255,255,0.6); line-height:1.6; }
        .footer-right { text-align:right; font-size:10px; color:rgba(255,255,255,0.55); line-height:1.7; }

        /* ===== PRINT ===== */
        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .no-print { display:none !important; }
            .report-footer { position:fixed; bottom:0; left:0; right:0; }
            body { padding-bottom:100px; }
        }
        @page { margin:0; size:A4; }
    </style>
</head>
<body>

    <!-- Print Button (no-print) -->
    <div class="no-print" style="background:#f1f5f9; padding:12px 40px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e2e8f0;">
        <a href="monthly_report.php?property_id=<?php echo $property_id; ?>&year=<?php echo $selected_year; ?>" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; color:#003580; font-weight:700; font-size:12px;">
            ← Back to Monthly Reports
        </a>
        <button onclick="window.print()" style="background:#003580; color:#fff; border:none; padding:10px 28px; border-radius:8px; font-weight:700; font-size:12px; cursor:pointer; display:flex; align-items:center; gap:8px;">
            🖨 Print / Download PDF
        </button>
    </div>

    <!-- REPORT HEADER -->
    <div class="report-header">
        <div class="header-left">
            <img src="../../assets/logo.png" alt="BookingJaunt Logo">
            <div>
                <div class="brand-name">BookingJaunt</div>
                <div class="brand-tagline">Family Travel. Securely Enjoyed.</div>
            </div>
        </div>
        <div class="header-right">
            <div class="report-title"><?php echo $pd['month_label']; ?> <?php echo $selected_year; ?> — Monthly Report</div>
            <div class="report-subtitle">Generated on <?php echo date('F d, Y \a\t h:i A'); ?></div>
            <span class="property-badge"><?php echo htmlspecialchars($property['property_name']); ?></span>
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
            <div class="s-label">Total Revenue</div>
        </div>
        <div class="summary-cell">
            <div class="s-value">LKR <?php echo number_format($pd['total_paid']); ?></div>
            <div class="s-label">Amount Collected</div>
        </div>
        <div class="summary-cell">
            <div class="s-value">LKR <?php echo number_format($pd['commission']); ?></div>
            <div class="s-label">Commission (20%)</div>
        </div>
    </div>

    <!-- PERFORMANCE SECTION -->
    <div class="section">
        <div class="section-title">Performance Breakdown</div>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-val">LKR <?php echo number_format($pd['online_paid']); ?></div>
                <div class="stat-lab">Online Revenue</div>
                <div class="stat-sub">Paid via platform</div>
            </div>
            <div class="stat-box">
                <div class="stat-val">LKR <?php echo number_format($pd['inplace_paid']); ?></div>
                <div class="stat-lab">Physical Revenue</div>
                <div class="stat-sub">Walk-in bookings</div>
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
                <div class="stat-val"><?php echo number_format($pd['cancelled']); ?></div>
                <div class="stat-lab">Cancelled</div>
                <div class="stat-sub">Cancellations this month</div>
            </div>
            <div class="stat-box">
                <div class="stat-val"><?php echo $pd['top_room']; ?></div>
                <div class="stat-lab">Top Room Type</div>
                <div class="stat-sub">Most booked</div>
            </div>
        </div>

        <div class="insight-row" style="margin-top:14px;">
            <div class="insight-box">
                <div class="i-label">Average Guest Rating</div>
                <div class="i-value"><?php echo $pd['avg_rating'] ? number_format($pd['avg_rating'],1).' / 5.0' : 'No ratings'; ?></div>
                <div class="i-sub">Published reviews for <?php echo $pd['month_label']; ?></div>
            </div>
            <div class="insight-box">
                <div class="i-label">Property Location</div>
                <div class="i-value"><?php echo htmlspecialchars($property['city'] ?? '—'); ?></div>
                <div class="i-sub"><?php echo htmlspecialchars($property['address'] ?? $property['district'] ?? ''); ?></div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- BOOKING LIST -->
    <div class="section">
        <div class="section-title">Booking Records — <?php echo $pd['month_label']; ?> <?php echo $selected_year; ?> (<?php echo count($booking_rows); ?> total)</div>
        <?php if (empty($booking_rows)): ?>
            <p style="color:#94a3b8; text-align:center; padding:24px 0; font-size:12px;">No bookings recorded for this month.</p>
        <?php else: ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Guest Name</th>
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
                        <div style="color:#94a3b8; font-size:10px;"><?php echo htmlspecialchars($bk['guest_phone']); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($bk['room_name']); ?>
                        <?php if($bk['room_number']): ?><div style="color:#94a3b8; font-size:10px;">#<?php echo $bk['room_number']; ?></div><?php endif; ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($bk['check_in_date'])); ?></td>
                    <td><?php echo date('M d, Y', strtotime($bk['check_out_date'])); ?></td>
                    <td style="text-transform:uppercase; font-size:10px; font-weight:700;"><?php echo $bk['booking_type']; ?></td>
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
            This report was automatically generated by the BookingJaunt platform.<br>
            For queries, contact support@bookingjaunt.com
        </div>
        <div class="footer-right">
            Property: <?php echo htmlspecialchars($property['property_name']); ?><br>
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
    <title>Monthly Reports - <?php echo htmlspecialchars($property['property_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex min-h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="main-content overflow-y-auto" style="position:relative; z-index:1;">

        <!-- Header -->
        <header class="glass-header sticky top-0 z-40 px-2 lg:px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 rounded-xl btn-glass flex items-center justify-center">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <!-- Property Selector -->
                <form method="GET" class="hidden sm:flex items-center gap-3">
                    <select name="property_id" onchange="this.form.submit()" class="glass-input px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest">
                        <?php foreach ($properties as $prop): ?>
                            <option value="<?php echo (int)$prop['id']; ?>" <?php echo (int)$prop['id'] === (int)$property_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prop['property_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <!-- Year Selector -->
            <form method="GET" class="flex items-center gap-3">
                <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                <label class="text-xs font-bold uppercase tracking-widest" style="color:var(--text-muted);">Year</label>
                <select name="year" onchange="this.form.submit()" class="glass-input px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest">
                    <?php for ($y = $max_year; $y >= $min_year; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $selected_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </header>

        <div class="py-4 px-2 lg:py-8 lg:px-4">

            <!-- Page Title -->
            <div class="mb-8 anim-up">
                <div class="flex items-center gap-4 mb-2">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background:rgba(254,187,2,0.15); border:1px solid rgba(254,187,2,0.3);">
                        <i class="fas fa-calendar-alt text-xl" style="color:#febb02;"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black" style="color:white;">Monthly Reports</h1>
                        <p class="text-sm mt-0.5" style="color:var(--text-secondary);">
                            <?php echo htmlspecialchars($property['property_name']); ?> — <?php echo $selected_year; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Year summary strip -->
            <?php
            $year_total_bk  = array_sum(array_column($monthly_data, 'total_bookings'));
            $year_total_rev = array_sum(array_column($monthly_data, 'total_paid'));
            $year_commission= array_sum(array_column($monthly_data, 'commission'));
            ?>
            <div class="grid grid-cols-3 gap-4 mb-8 anim-up-2">
                <div class="glass-card p-5 text-center">
                    <div class="text-xl font-black mb-1" style="color:white;"><?php echo number_format($year_total_bk); ?></div>
                    <div class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">Total Bookings <?php echo $selected_year; ?></div>
                </div>
                <div class="glass-card p-5 text-center">
                    <div class="text-xl font-black mb-1" style="color:white;">LKR <?php echo number_format($year_total_rev); ?></div>
                    <div class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">Total Revenue <?php echo $selected_year; ?></div>
                </div>
                <div class="glass-card p-5 text-center">
                    <div class="text-xl font-black mb-1" style="color:#fdba74;">LKR <?php echo number_format($year_commission); ?></div>
                    <div class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">Commission Due <?php echo $selected_year; ?></div>
                </div>
            </div>

            <!-- Monthly Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($monthly_data as $m => $md): 
                    $is_current = ($m == date('n') && $selected_year == date('Y'));
                    $has_data   = $md['total_bookings'] > 0;
                    $fill_pct   = $year_total_rev > 0 ? round(($md['total_paid'] / $year_total_rev) * 100) : 0;
                ?>
                <div class="glass-card p-6 flex flex-col gap-4 anim-up <?php echo $is_current ? 'current-month-card' : ''; ?>"
                     style="<?php echo $is_current ? 'border-color:rgba(254,187,2,0.45); background:rgba(254,187,2,0.07);' : ''; ?>">

                    <!-- Month Header -->
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xl font-black" style="color:white;"><?php echo $md['month_label']; ?></span>
                                <?php if ($is_current): ?>
                                    <span class="text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full" style="background:rgba(254,187,2,0.2); color:#febb02; border:1px solid rgba(254,187,2,0.4);">Current</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);"><?php echo $selected_year; ?></div>
                        </div>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-black" 
                             style="background:rgba(255,255,255,0.08); color:white; border:1px solid var(--glass-border);">
                            <?php echo sprintf('%02d', $m); ?>
                        </div>
                    </div>

                    <?php if (!$has_data): ?>
                    <!-- No data state -->
                    <div class="flex-1 flex flex-col items-center justify-center py-4 rounded-xl" style="background:rgba(0,0,0,0.15);">
                        <i class="fas fa-calendar-xmark text-2xl mb-2 opacity-20" style="color:white;"></i>
                        <p class="text-xs font-bold" style="color:var(--text-muted);">No bookings this month</p>
                    </div>
                    <?php else: ?>

                    <!-- Key Metrics Row -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl p-3" style="background:rgba(0,0,0,0.2); border:1px solid var(--glass-border);">
                            <div class="text-lg font-black" style="color:white;"><?php echo number_format($md['total_bookings']); ?></div>
                            <div class="text-[9px] font-bold uppercase tracking-wider mt-0.5" style="color:var(--text-muted);">Bookings</div>
                        </div>
                        <div class="rounded-xl p-3" style="background:rgba(0,0,0,0.2); border:1px solid var(--glass-border);">
                            <div class="text-lg font-black" style="color:white;"><?php echo number_format($md['total_paid']); ?></div>
                            <div class="text-[9px] font-bold uppercase tracking-wider mt-0.5" style="color:var(--text-muted);">Revenue (LKR)</div>
                        </div>
                    </div>

                    <!-- Revenue Bar -->
                    <div>
                        <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest mb-1.5" style="color:var(--text-muted);">
                            <span>Online</span>
                            <span>Physical</span>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden flex" style="background:rgba(255,255,255,0.08);">
                            <?php 
                            $total_p = $md['online_paid'] + $md['inplace_paid'];
                            $online_pct = $total_p > 0 ? ($md['online_paid']/$total_p)*100 : 50;
                            ?>
                            <div style="width:<?php echo $online_pct; ?>%; background:linear-gradient(90deg,#60a5fa,#3b82f6);"></div>
                            <div style="width:<?php echo 100-$online_pct; ?>%; background:linear-gradient(90deg,#a78bfa,#8b5cf6);"></div>
                        </div>
                        <div class="flex justify-between text-[9px] mt-1" style="color:var(--text-muted);">
                            <span>LKR <?php echo number_format($md['online_paid']); ?></span>
                            <span>LKR <?php echo number_format($md['inplace_paid']); ?></span>
                        </div>
                    </div>

                    <!-- Quick Stats Row -->
                    <div class="flex items-center gap-3 text-[10px]" style="color:var(--text-secondary);">
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-check-circle" style="color:#86efac;"></i>
                            <?php echo $md['confirmed']; ?> Confirmed
                        </span>
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-times-circle" style="color:#fca5a5;"></i>
                            <?php echo $md['cancelled']; ?> Cancelled
                        </span>
                        <?php if ($md['avg_rating']): ?>
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-star" style="color:#fbbf24;"></i>
                            <?php echo number_format($md['avg_rating'],1); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Commission -->
                    <div class="flex items-center justify-between rounded-xl px-4 py-2.5" style="background:rgba(248,113,113,0.08); border:1px solid rgba(248,113,113,0.2);">
                        <span class="text-[10px] font-bold uppercase tracking-widest" style="color:rgba(252,165,165,0.7);">Commission Due</span>
                        <span class="font-black text-sm" style="color:#fca5a5;">LKR <?php echo number_format($md['commission']); ?></span>
                    </div>

                    <?php endif; ?>

                    <!-- Download Button -->
                    <a href="monthly_report.php?property_id=<?php echo $property_id; ?>&year=<?php echo $selected_year; ?>&print=<?php echo $m; ?>"
                       target="_blank"
                       class="flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all mt-auto"
                       style="background:rgba(0,108,228,0.18); border:1.5px solid rgba(96,165,250,0.4); color:#93c5fd;"
                       onmouseover="this.style.background='rgba(0,108,228,0.32)'"
                       onmouseout="this.style.background='rgba(0,108,228,0.18)'">
                        <i class="fas fa-file-download"></i>
                        Download <?php echo $md['month_label']; ?> Report
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>

    <style>
        .current-month-card { animation: glow 3s ease-in-out infinite alternate; }
        @keyframes glow {
            from { box-shadow: 0 0 12px rgba(254,187,2,0.15); }
            to   { box-shadow: 0 0 28px rgba(254,187,2,0.30); }
        }
    </style>
</body>
</html>
