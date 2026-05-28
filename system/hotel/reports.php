<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

if ($_SESSION['role'] === 'admin') {
    $properties_stmt = $pdo->query("SELECT id, property_name FROM properties ORDER BY property_name");
    $properties = $properties_stmt->fetchAll();
} else {
    $properties_stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE owner_id = ? ORDER BY property_name");
    $properties_stmt->execute([$user_id]);
    $properties = $properties_stmt->fetchAll();
}

$property = null;
$property_id = 0;
if ($selected_property_id) {
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$selected_property_id]);
        $property = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt->execute([$selected_property_id, $user_id]);
        $property = $stmt->fetch();
    }
}

if (!$property && !empty($properties)) {
    $first_property_id = (int)$properties[0]['id'];
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$first_property_id]);
        $property = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt->execute([$first_property_id, $user_id]);
        $property = $stmt->fetch();
    }
}

$property_id = $property['id'] ?? 0;
if (!$property_id && $_SESSION['role'] !== 'admin') die("Property not found.");

$from   = $_GET['from']   ?? '';
$to     = $_GET['to']     ?? '';
$type   = $_GET['type']   ?? '';
$status = $_GET['status'] ?? '';

$where  = ["b.property_id = ?"];
$params = [$property_id];
if ($from !== '') { $where[] = "DATE(b.created_at) >= ?"; $params[] = $from; }
if ($to   !== '') { $where[] = "DATE(b.created_at) <= ?"; $params[] = $to;   }
if ($type !== '') { $where[] = "b.booking_type = ?";      $params[] = $type; }
if ($status !== '') { $where[] = "b.status = ?";          $params[] = $status; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$summary_stmt = $pdo->prepare("SELECT COUNT(*) AS total_bookings, COALESCE(SUM(b.total_price),0) AS total_value, COALESCE(SUM(b.amount_paid),0) AS total_paid, COALESCE(SUM(CASE WHEN b.booking_type='online' THEN b.amount_paid ELSE 0 END),0) AS online_paid, COALESCE(SUM(CASE WHEN b.booking_type='inplace' THEN b.amount_paid ELSE 0 END),0) AS inplace_paid FROM bookings b $where_sql");
$summary_stmt->execute($params);
$summary = $summary_stmt->fetch();

$boost_where = ["property_id = ?", "payment_status = 'success'"];
$boost_params = [$property_id];
if ($from !== '') { $boost_where[] = "DATE(created_at) >= ?"; $boost_params[] = $from; }
if ($to   !== '') { $boost_where[] = "DATE(created_at) <= ?"; $boost_params[] = $to;   }
$boost_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS boost_spend FROM property_boosts WHERE " . implode(' AND ', $boost_where));
$boost_stmt->execute($boost_params);
$boost_spend = $boost_stmt->fetch()['boost_spend'] ?? 0;

$list_stmt = $pdo->prepare("SELECT b.*, pr.room_name FROM bookings b JOIN property_rooms pr ON b.room_id = pr.id $where_sql ORDER BY b.created_at DESC");
$list_stmt->execute($params);
$bookings = $list_stmt->fetchAll();

$chart_month = isset($_GET['chart_month']) ? (int)$_GET['chart_month'] : (int)date('n');
$chart_year  = isset($_GET['chart_year'])  ? (int)$_GET['chart_year']  : (int)date('Y');
if ($chart_month < 1 || $chart_month > 12) $chart_month = (int)date('n');
if ($chart_year < 2000 || $chart_year > 2100) $chart_year = (int)date('Y');

$chart_start = sprintf('%04d-%02d-01', $chart_year, $chart_month);
$chart_end   = (new DateTime($chart_start))->modify('last day of this month')->format('Y-m-d');

$trend_stmt = $pdo->prepare("SELECT DATE(b.created_at) AS day, COUNT(*) AS total FROM bookings b WHERE b.property_id = ? AND DATE(b.created_at) BETWEEN ? AND ? GROUP BY DATE(b.created_at) ORDER BY day");
$trend_stmt->execute([$property_id, $chart_start, $chart_end]);
$trend_rows = $trend_stmt->fetchAll();
$trend_map = [];
foreach ($trend_rows as $row) $trend_map[$row['day']] = (int)$row['total'];

$days_in_month = (int)date('t', strtotime($chart_start));
$trend_labels = []; $trend_counts = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $dv = sprintf('%04d-%02d-%02d', $chart_year, $chart_month, $d);
    $trend_labels[] = date('M d', strtotime($dv));
    $trend_counts[] = $trend_map[$dv] ?? 0;
}

$room_stmt = $pdo->prepare("SELECT pr.room_name, COUNT(*) AS total FROM bookings b JOIN property_rooms pr ON b.room_id = pr.id WHERE b.property_id = ? AND DATE(b.created_at) BETWEEN ? AND ? GROUP BY pr.room_name ORDER BY total DESC");
$room_stmt->execute([$property_id, $chart_start, $chart_end]);
$room_rows = $room_stmt->fetchAll();
$room_labels = []; $room_counts = [];
foreach ($room_rows as $row) { $room_labels[] = $row['room_name']; $room_counts[] = (int)$row['total']; }
if (empty($room_labels)) { $room_labels = ['No data']; $room_counts = [0]; }

$type_stmt = $pdo->prepare("SELECT b.booking_type, COUNT(*) AS total FROM bookings b WHERE b.property_id = ? AND DATE(b.created_at) BETWEEN ? AND ? GROUP BY b.booking_type");
$type_stmt->execute([$property_id, $chart_start, $chart_end]);
$type_map = ['online' => 0, 'inplace' => 0];
foreach ($type_stmt->fetchAll() as $row) { if (isset($type_map[$row['booking_type']])) $type_map[$row['booking_type']] = (int)$row['total']; }
$booking_type_counts = [$type_map['online'], $type_map['inplace']];

$revenue_stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN b.booking_type='online' THEN b.amount_paid ELSE 0 END),0) AS online_paid, COALESCE(SUM(CASE WHEN b.booking_type='inplace' THEN b.amount_paid ELSE 0 END),0) AS inplace_paid FROM bookings b WHERE b.property_id = ? AND DATE(b.created_at) BETWEEN ? AND ?");
$revenue_stmt->execute([$property_id, $chart_start, $chart_end]);
$revenue = $revenue_stmt->fetch();
$revenue_online   = (float)($revenue['online_paid'] ?? 0);
$revenue_inplace  = (float)($revenue['inplace_paid'] ?? 0);
$commission_total = $revenue_online * 0.2;
$summary_commission = ($summary['online_paid'] ?? 0) * 0.2;

$status_styles = ['pending' => 'badge-pending', 'confirmed' => 'badge-confirmed', 'checked_in' => 'badge-checked_in', 'checked_out' => 'badge-checked_out', 'cancelled' => 'badge-cancelled'];

$download_params = [
    'property_id' => $property_id,
    'from' => $from,
    'to' => $to,
    'type' => $type,
    'status' => $status
];
$download_params = array_filter($download_params, static function ($value) {
    return $value !== '' && $value !== null;
});
$download_query = http_build_query($download_params);
$download_url = 'hotel_report_download.php' . ($download_query ? '?' . $download_query : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Override Chart.js default colors for dark mode */
        .chart-wrapper canvas { filter: none; }
    </style>
</head>
<body class="flex min-h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="main-content overflow-y-auto" style="position:relative; z-index:1;">

        <header class="glass-header sticky top-0 z-40 px-2 lg:px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 rounded-xl btn-glass flex items-center justify-center"><i class="fas fa-bars-staggered"></i></button>
                <form method="GET" class="hidden sm:block">
                    <label class="sr-only" for="propertySelect">Property</label>
                    <select id="propertySelect" name="property_id" onchange="this.form.submit()" class="glass-input px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest">
                        <?php foreach ($properties as $prop): ?>
                            <option value="<?php echo (int)$prop['id']; ?>" <?php echo (int)$prop['id'] === (int)$property_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prop['property_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="hidden xl:flex items-center gap-3">
                <a href="<?php echo $download_url . ($download_query ? '&' : '?') . 'view=1'; ?>" target="_blank" class="btn-glass"><i class="fas fa-eye"></i> View Report</a>
                <a href="<?php echo $download_url; ?>" class="btn-primary"><i class="fas fa-download"></i> Download Report</a>
                <a href="../../index.php" target="_blank" class="btn-glass"><i class="fas fa-external-link-alt"></i> Visit Site</a>
            </div>
        </header>

        <div class="py-4 px-2 lg:py-8 lg:px-4 space-y-8">
            <div class="anim-up">
                <h1 class="text-2xl font-black" style="color:white;">Reports</h1>
                <p class="text-sm mt-1" style="color:var(--text-secondary);">Performance summary for <?php echo htmlspecialchars($property['property_name'] ?? 'your property'); ?></p>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 anim-up">
                <?php
                $stat_items = [
                    ['label' => 'Total Bookings',   'value' => number_format($summary['total_bookings'] ?? 0), 'icon' => 'fa-calendar-check',   'color' => '#93c5fd'],
                    ['label' => 'Total Value',       'value' => 'LKR '.number_format($summary['total_value'] ?? 0), 'icon' => 'fa-coins',          'color' => '#fcd34d'],
                    ['label' => 'Amount Paid',       'value' => 'LKR '.number_format($summary['total_paid'] ?? 0), 'icon' => 'fa-check-circle',   'color' => '#86efac'],
                    ['label' => 'Online Paid',       'value' => 'LKR '.number_format($summary['online_paid'] ?? 0), 'icon' => 'fa-globe',         'color' => '#7dd3fc'],
                    ['label' => 'Commission (20%)',  'value' => 'LKR '.number_format($summary_commission), 'icon' => 'fa-percentage',             'color' => '#fca5a5'],
                    ['label' => 'Boost Spend',       'value' => 'LKR '.number_format($boost_spend), 'icon' => 'fa-bolt',                          'color' => '#fdba74'],
                    ['label' => 'Physical Paid',     'value' => 'LKR '.number_format($summary['inplace_paid'] ?? 0), 'icon' => 'fa-money-bill',   'color' => '#c4b5fd'],
                ];
                foreach ($stat_items as $item): ?>
                <div class="glass-card p-4 flex flex-col">
                    <div class="stat-icon mb-3" style="width:36px;height:36px;font-size:0.9rem; background:rgba(255,255,255,0.08);"><i class="fas <?php echo $item['icon']; ?>" style="color:<?php echo $item['color']; ?>;"></i></div>
                    <p class="stat-label" style="font-size:0.55rem;"><?php echo $item['label']; ?></p>
                    <h3 style="color:white; font-weight:800; font-size:0.85rem; margin-top:2px;"><?php echo $item['value']; ?></h3>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Filter Panel -->
            <div class="glass-card p-6 anim-up-2">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                    <input type="hidden" name="property_id" value="<?php echo (int)$property_id; ?>">
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">From</label>
                        <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="glass-input mt-2 w-full px-4 py-2 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">To</label>
                        <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" class="glass-input mt-2 w-full px-4 py-2 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">Type</label>
                        <select name="type" class="glass-input mt-2 w-full px-4 py-2 rounded-xl text-sm">
                            <option value="">All</option>
                            <option value="online"  <?php echo $type === 'online'  ? 'selected' : ''; ?>>Online</option>
                            <option value="inplace" <?php echo $type === 'inplace' ? 'selected' : ''; ?>>Physical</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">Status</label>
                        <select name="status" class="glass-input mt-2 w-full px-4 py-2 rounded-xl text-sm">
                            <option value="">All</option>
                            <?php foreach (array_keys($status_styles) as $item): ?>
                                <option value="<?php echo $item; ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $item)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex gap-3 lg:col-span-2">
                        <button class="btn-primary flex-1 justify-center py-2"><i class="fas fa-filter"></i> Apply</button>
                        <a href="reports.php?property_id=<?php echo (int)$property_id; ?>" class="btn-glass flex-1 justify-center py-2"><i class="fas fa-times"></i> Reset</a>
                    </div>
                </form>
            </div>

            <!-- Charts -->
            <div class="glass-card p-6 anim-up">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-black" style="color:white;">Insights</h2>
                        <p class="text-sm" style="color:var(--text-secondary);">Showing data for <?php echo date('F Y', strtotime($chart_start)); ?></p>
                    </div>
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <input type="hidden" name="property_id" value="<?php echo (int)$property_id; ?>">
                        <input type="hidden" name="from" value="<?php echo htmlspecialchars($from); ?>">
                        <input type="hidden" name="to" value="<?php echo htmlspecialchars($to); ?>">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-widest block" style="color:var(--text-muted);">Month</label>
                            <select name="chart_month" class="glass-input mt-2 px-3 py-2 rounded-xl text-sm">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $chart_month === $m ? 'selected' : ''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-widest block" style="color:var(--text-muted);">Year</label>
                            <input type="number" name="chart_year" min="2000" max="2100" value="<?php echo $chart_year; ?>" class="glass-input mt-2 w-24 px-3 py-2 rounded-xl text-sm">
                        </div>
                        <button class="btn-primary py-2 px-4"><i class="fas fa-sync-alt"></i> Update</button>
                    </form>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 rounded-2xl p-4" style="background:rgba(0,0,0,0.2);">
                        <h3 class="text-sm font-bold mb-3" style="color:var(--text-secondary);">Monthly Bookings Trend</h3>
                        <canvas id="monthlyBookingsChart" height="90"></canvas>
                    </div>
                    <div class="rounded-2xl p-4" style="background:rgba(0,0,0,0.2);">
                        <h3 class="text-sm font-bold mb-3" style="color:var(--text-secondary);">Revenue Split</h3>
                        <canvas id="revenueSplitChart" height="130"></canvas>
                        <div class="mt-4 p-3 rounded-xl" style="background:rgba(255,255,255,0.07); border:1px solid var(--glass-border);">
                            <p class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">Commission (20%)</p>
                            <p class="text-lg font-black mt-1" style="color:white;">LKR <?php echo number_format($commission_total); ?></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    <div class="rounded-2xl p-4" style="background:rgba(0,0,0,0.2);">
                        <h3 class="text-sm font-bold mb-3" style="color:var(--text-secondary);">Most Booked Room Types</h3>
                        <canvas id="roomTypesChart" height="130"></canvas>
                    </div>
                    <div class="rounded-2xl p-4" style="background:rgba(0,0,0,0.2);">
                        <h3 class="text-sm font-bold mb-3" style="color:var(--text-secondary);">Bookings by Type</h3>
                        <canvas id="bookingTypeChart" height="130"></canvas>
                    </div>
                </div>
            </div>

            <!-- Booking Records Table -->
            <div class="glass-table anim-up-2">
                <div class="px-6 py-5 flex items-center justify-between" style="border-bottom:1px solid var(--glass-border);">
                    <h2 class="font-black" style="color:white;">Booking Records</h2>
                    <span class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">Total <?php echo count($bookings); ?></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr>
                                <th class="glass-th">Guest</th>
                                <th class="glass-th">Type</th>
                                <th class="glass-th">Dates</th>
                                <th class="glass-th">Total</th>
                                <th class="glass-th">Paid</th>
                                <th class="glass-th">Status</th>
                                <th class="glass-th text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr><td colspan="7" class="glass-td text-center py-16" style="color:var(--text-muted);">No bookings found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($bookings as $b): ?>
                            <tr class="glass-tr">
                                <td class="glass-td">
                                    <div class="font-bold" style="color:white;"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                                    <div class="text-[11px] mt-1" style="color:var(--text-muted);"><?php echo htmlspecialchars($b['guest_phone']); ?></div>
                                </td>
                                <td class="glass-td text-[11px] font-bold uppercase tracking-widest" style="color:var(--text-muted);"><?php echo htmlspecialchars($b['booking_type']); ?></td>
                                <td class="glass-td">
                                    <div class="font-bold" style="color:white;"><?php echo date('M d', strtotime($b['check_in_date'])); ?> – <?php echo date('M d', strtotime($b['check_out_date'])); ?></div>
                                </td>
                                <td class="glass-td font-bold" style="color:white;">LKR <?php echo number_format($b['total_price']); ?></td>
                                <td class="glass-td font-bold" style="color:white;">LKR <?php echo number_format($b['amount_paid']); ?></td>
                                <td class="glass-td">
                                    <span class="badge <?php echo $status_styles[$b['status']] ?? 'badge-checked_out'; ?>"><?php echo str_replace('_', ' ', $b['status']); ?></span>
                                </td>
                                <td class="glass-td text-right">
                                    <a href="booking_details.php?id=<?php echo $b['id']; ?>" class="btn-primary" style="padding:5px 12px; font-size:0.55rem;">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        const chartDefaults = {
            plugins: { legend: { labels: { color: 'rgba(255,255,255,0.6)', font: { family: 'Plus Jakarta Sans', size: 11 } } } },
            scales: { x: { ticks: { color: 'rgba(255,255,255,0.4)' }, grid: { color: 'rgba(255,255,255,0.06)' } }, y: { ticks: { color: 'rgba(255,255,255,0.4)', precision: 0 }, grid: { color: 'rgba(255,255,255,0.06)' }, beginAtZero: true } }
        };

        const trendCtx = document.getElementById('monthlyBookingsChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($trend_labels); ?>,
                    datasets: [{ label: 'Bookings', data: <?php echo json_encode($trend_counts); ?>, borderColor: '#60a5fa', backgroundColor: 'rgba(96,165,250,0.15)', tension: 0.3, fill: true, pointRadius: 3, pointBackgroundColor: '#60a5fa' }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: chartDefaults.scales }
            });
        }

        const revenueCtx = document.getElementById('revenueSplitChart');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'doughnut',
                data: { labels: ['Online', 'Physical'], datasets: [{ data: [<?php echo $revenue_online; ?>, <?php echo $revenue_inplace; ?>], backgroundColor: ['#3b82f6', '#8b5cf6'], borderWidth: 0 }] },
                options: { plugins: { legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,0.55)', font: { family: 'Plus Jakarta Sans' } } } }, cutout: '65%' }
            });
        }

        const roomCtx = document.getElementById('roomTypesChart');
        if (roomCtx) {
            new Chart(roomCtx, {
                type: 'pie',
                data: { labels: <?php echo json_encode($room_labels); ?>, datasets: [{ data: <?php echo json_encode($room_counts); ?>, backgroundColor: ['#3b82f6','#06b6d4','#10b981','#f59e0b','#ef4444','#a855f7'], borderWidth: 0 }] },
                options: { plugins: { legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,0.55)', font: { family: 'Plus Jakarta Sans' } } } } }
            });
        }

        const btCtx = document.getElementById('bookingTypeChart');
        if (btCtx) {
            new Chart(btCtx, {
                type: 'bar',
                data: { labels: ['Online', 'Physical'], datasets: [{ label: 'Bookings', data: <?php echo json_encode($booking_type_counts); ?>, backgroundColor: ['rgba(96,165,250,0.7)', 'rgba(167,139,250,0.7)'], borderRadius: 8, borderWidth: 0 }] },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: chartDefaults.scales }
            });
        }
    </script>
</body>
</html>
