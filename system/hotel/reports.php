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

if (!$property_id && $_SESSION['role'] !== 'admin') {
	die("Property not found.");
}

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';

$where = ["b.property_id = ?"];
$params = [$property_id];

if ($from !== '') {
	$where[] = "DATE(b.created_at) >= ?";
	$params[] = $from;
}
if ($to !== '') {
	$where[] = "DATE(b.created_at) <= ?";
	$params[] = $to;
}
if ($type !== '') {
	$where[] = "b.booking_type = ?";
	$params[] = $type;
}
if ($status !== '') {
	$where[] = "b.status = ?";
	$params[] = $status;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$summary_sql = "SELECT COUNT(*) AS total_bookings,
					   COALESCE(SUM(b.total_price), 0) AS total_value,
					   COALESCE(SUM(b.amount_paid), 0) AS total_paid,
					   COALESCE(SUM(CASE WHEN b.booking_type = 'online' THEN b.amount_paid ELSE 0 END), 0) AS online_paid,
					   COALESCE(SUM(CASE WHEN b.booking_type = 'inplace' THEN b.amount_paid ELSE 0 END), 0) AS inplace_paid
				FROM bookings b
				$where_sql";
$summary_stmt = $pdo->prepare($summary_sql);
$summary_stmt->execute($params);
$summary = $summary_stmt->fetch();

$boost_where = ["property_id = ?", "payment_status = 'success'"];
$boost_params = [$property_id];
if ($from !== '') {
	$boost_where[] = "DATE(created_at) >= ?";
	$boost_params[] = $from;
}
if ($to !== '') {
	$boost_where[] = "DATE(created_at) <= ?";
	$boost_params[] = $to;
}
$boost_sql = "SELECT COALESCE(SUM(amount), 0) AS boost_spend FROM property_boosts WHERE " . implode(' AND ', $boost_where);
$boost_stmt = $pdo->prepare($boost_sql);
$boost_stmt->execute($boost_params);
$boost_summary = $boost_stmt->fetch();
$boost_spend = $boost_summary['boost_spend'] ?? 0;

$list_sql = "SELECT b.*, pr.room_name
			 FROM bookings b
			 JOIN property_rooms pr ON b.room_id = pr.id
			 $where_sql
			 ORDER BY b.created_at DESC";
$list_stmt = $pdo->prepare($list_sql);
$list_stmt->execute($params);
$bookings = $list_stmt->fetchAll();

$status_styles = [
	'pending' => 'bg-amber-100 text-amber-700',
	'confirmed' => 'bg-emerald-100 text-emerald-700',
	'checked_in' => 'bg-blue-100 text-blue-700',
	'checked_out' => 'bg-slate-100 text-slate-700',
	'cancelled' => 'bg-red-100 text-red-700'
];

$chart_month = isset($_GET['chart_month']) ? (int)$_GET['chart_month'] : (int)date('n');
$chart_year = isset($_GET['chart_year']) ? (int)$_GET['chart_year'] : (int)date('Y');
if ($chart_month < 1 || $chart_month > 12) {
	$chart_month = (int)date('n');
}
if ($chart_year < 2000 || $chart_year > 2100) {
	$chart_year = (int)date('Y');
}

$chart_start = sprintf('%04d-%02d-01', $chart_year, $chart_month);
$chart_end = (new DateTime($chart_start))->modify('last day of this month')->format('Y-m-d');

$trend_stmt = $pdo->prepare("SELECT DATE(b.created_at) AS day, COUNT(*) AS total
						FROM bookings b
						WHERE b.property_id = ? AND DATE(b.created_at) BETWEEN ? AND ?
						GROUP BY DATE(b.created_at)
						ORDER BY day");
$trend_stmt->execute([$property_id, $chart_start, $chart_end]);
$trend_rows = $trend_stmt->fetchAll();
$trend_map = [];
foreach ($trend_rows as $row) {
	$trend_map[$row['day']] = (int)$row['total'];
}

$days_in_month = (int)date('t', strtotime($chart_start));
$trend_labels = [];
$trend_counts = [];
for ($d = 1; $d <= $days_in_month; $d++) {
	$day_value = sprintf('%04d-%02d-%02d', $chart_year, $chart_month, $d);
	$trend_labels[] = date('M d', strtotime($day_value));
	$trend_counts[] = $trend_map[$day_value] ?? 0;
}

$room_stmt = $pdo->prepare("SELECT pr.room_name, COUNT(*) AS total
					FROM bookings b
					JOIN property_rooms pr ON b.room_id = pr.id
					WHERE b.property_id = ? AND DATE(b.created_at) BETWEEN ? AND ?
					GROUP BY pr.room_name
					ORDER BY total DESC");
$room_stmt->execute([$property_id, $chart_start, $chart_end]);
$room_rows = $room_stmt->fetchAll();
$room_labels = [];
$room_counts = [];
foreach ($room_rows as $row) {
	$room_labels[] = $row['room_name'];
	$room_counts[] = (int)$row['total'];
}
if (empty($room_labels)) {
	$room_labels = ['No data'];
	$room_counts = [0];
}

$type_stmt = $pdo->prepare("SELECT b.booking_type, COUNT(*) AS total
					FROM bookings b
					WHERE b.property_id = ? AND DATE(b.created_at) BETWEEN ? AND ?
					GROUP BY b.booking_type");
$type_stmt->execute([$property_id, $chart_start, $chart_end]);
$type_rows = $type_stmt->fetchAll();
$type_map = ['online' => 0, 'inplace' => 0];
foreach ($type_rows as $row) {
	$type_key = $row['booking_type'];
	if (isset($type_map[$type_key])) {
		$type_map[$type_key] = (int)$row['total'];
	}
}
$booking_type_labels = ['Online', 'Physical'];
$booking_type_counts = [$type_map['online'], $type_map['inplace']];

$revenue_stmt = $pdo->prepare("SELECT
						COALESCE(SUM(CASE WHEN b.booking_type = 'online' THEN b.amount_paid ELSE 0 END), 0) AS online_paid,
						COALESCE(SUM(CASE WHEN b.booking_type = 'inplace' THEN b.amount_paid ELSE 0 END), 0) AS inplace_paid
					FROM bookings b
					WHERE b.property_id = ? AND DATE(b.created_at) BETWEEN ? AND ?");
$revenue_stmt->execute([$property_id, $chart_start, $chart_end]);
$revenue = $revenue_stmt->fetch();
$revenue_online = (float)($revenue['online_paid'] ?? 0);
$revenue_inplace = (float)($revenue['inplace_paid'] ?? 0);
$commission_rate = 0.2;
$commission_total = ($revenue['online_paid'] ?? 0) * $commission_rate;
$summary_commission = ($summary['online_paid'] ?? 0) * $commission_rate;
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
		body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
	</style>
</head>

<body class="flex min-h-screen overflow-hidden">
	<?php include 'sidebar.php'; ?>

	<main class="flex-1 lg:ml-64 overflow-y-auto h-screen bg-[#f8fafc]">
		<header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-4 lg:px-8 py-4 flex justify-between items-center">
			<div class="flex items-center gap-4">
				<button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-[#003580] hover:bg-gray-100 transition-all">
					<i class="fas fa-bars-staggered"></i>
				</button>
				<form method="GET" class="hidden sm:block">
					<label class="sr-only" for="propertySelect">Property</label>
					<select id="propertySelect" name="property_id" onchange="this.form.submit()" class="px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-600 uppercase tracking-widest focus:ring-2 focus:ring-[#003580] outline-none">
						<?php if (!empty($properties)): ?>
							<?php foreach ($properties as $prop): ?>
								<option value="<?php echo (int)$prop['id']; ?>" <?php echo (int)$prop['id'] === (int)$property_id ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars($prop['property_name']); ?>
								</option>
							<?php endforeach; ?>
						<?php else: ?>
							<option value="">No properties</option>
						<?php endif; ?>
					</select>
				</form>
			</div>
			<a href="../../index.php" target="_blank" class="hidden xl:flex items-center gap-2 px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-50 transition-all">
				<i class="fas fa-external-link-alt"></i> Visit Site
			</a>
		</header>

		<div class="p-4 lg:p-8 space-y-8">
			<div>
				<h1 class="text-2xl font-black text-slate-800">Reports</h1>
				<p class="text-slate-500 text-sm">Performance summary for <?php echo htmlspecialchars($property['property_name'] ?? 'your property'); ?></p>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-6">
				<div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
					<p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Bookings</p>
					<h3 class="text-2xl font-black text-[#003580]"><?php echo number_format($summary['total_bookings'] ?? 0); ?></h3>
				</div>
				<div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
					<p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Value</p>
					<h3 class="text-2xl font-black text-[#003580]">LKR <?php echo number_format($summary['total_value'] ?? 0); ?></h3>
				</div>
				<div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
					<p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Amount Paid</p>
					<h3 class="text-2xl font-black text-[#003580]">LKR <?php echo number_format($summary['total_paid'] ?? 0); ?></h3>
				</div>
				<div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
					<p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Online Paid</p>
					<h3 class="text-2xl font-black text-[#003580]">LKR <?php echo number_format($summary['online_paid'] ?? 0); ?></h3>
				</div>
				<div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
					<p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Commission (20%)</p>
					<h3 class="text-2xl font-black text-[#003580]">LKR <?php echo number_format($summary_commission); ?></h3>
				</div>
				<div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
					<p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Boost Spend</p>
					<h3 class="text-2xl font-black text-[#003580]">LKR <?php echo number_format($boost_spend); ?></h3>
				</div>
				<div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
					<p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Physical Paid</p>
					<h3 class="text-2xl font-black text-[#003580]">LKR <?php echo number_format($summary['inplace_paid'] ?? 0); ?></h3>
				</div>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
					<input type="hidden" name="property_id" value="<?php echo (int)$property_id; ?>">
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">From</label>
						<input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">To</label>
						<input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Type</label>
						<select name="type" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
							<option value="">All</option>
							<option value="online" <?php echo $type === 'online' ? 'selected' : ''; ?>>Online</option>
							<option value="inplace" <?php echo $type === 'inplace' ? 'selected' : ''; ?>>Physical</option>
						</select>
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Status</label>
						<select name="status" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
							<option value="">All</option>
							<?php foreach (array_keys($status_styles) as $item): ?>
								<option value="<?php echo $item; ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $item)); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="flex gap-3">
						<button class="px-5 py-2 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest">Apply</button>
						<a href="reports.php?property_id=<?php echo (int)$property_id; ?>" class="px-5 py-2 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-widest">Reset</a>
					</div>
				</form>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-6">
				<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
					<div>
						<h2 class="text-lg font-black text-slate-800">Insights</h2>
						<p class="text-sm text-slate-500">Showing data for <?php echo date('F Y', strtotime($chart_start)); ?></p>
					</div>
					<form method="GET" class="flex flex-wrap items-end gap-3">
						<input type="hidden" name="property_id" value="<?php echo (int)$property_id; ?>">
						<input type="hidden" name="from" value="<?php echo htmlspecialchars($from); ?>">
						<input type="hidden" name="to" value="<?php echo htmlspecialchars($to); ?>">
						<input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
						<input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
						<div>
							<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Month</label>
							<select name="chart_month" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
								<?php for ($m = 1; $m <= 12; $m++): ?>
									<option value="<?php echo $m; ?>" <?php echo $chart_month === $m ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div>
							<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Year</label>
							<input type="number" name="chart_year" min="2000" max="2100" value="<?php echo $chart_year; ?>" class="mt-2 w-28 px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
						</div>
						<button class="px-4 py-2 rounded-xl bg-[#003580] text-white text-xs font-bold uppercase tracking-widest">Update</button>
					</form>
				</div>

				<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
					<div class="lg:col-span-2 bg-gray-50 rounded-2xl p-4">
						<h3 class="text-sm font-bold text-slate-700 mb-3">Monthly Bookings Trend</h3>
						<canvas id="monthlyBookingsChart" height="140"></canvas>
					</div>
					<div class="bg-gray-50 rounded-2xl p-4">
						<h3 class="text-sm font-bold text-slate-700 mb-3">Revenue Split</h3>
						<canvas id="revenueSplitChart" height="180"></canvas>
						<div class="mt-4 p-3 rounded-xl bg-white border border-gray-100">
							<p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Commission (20%)</p>
							<p class="text-lg font-black text-[#003580]">LKR <?php echo number_format($commission_total); ?></p>
						</div>
					</div>
				</div>

				<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
					<div class="bg-gray-50 rounded-2xl p-4">
						<h3 class="text-sm font-bold text-slate-700 mb-3">Most Booked Room Types</h3>
						<canvas id="roomTypesChart" height="180"></canvas>
					</div>
					<div class="bg-gray-50 rounded-2xl p-4">
						<h3 class="text-sm font-bold text-slate-700 mb-3">Bookings by Type</h3>
						<canvas id="bookingTypeChart" height="180"></canvas>
					</div>
				</div>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
				<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
					<h2 class="font-bold text-[#003580]">Booking Records</h2>
					<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total <?php echo count($bookings); ?></span>
				</div>
				<div class="overflow-x-auto">
					<table class="w-full text-left">
						<thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
							<tr>
								<th class="p-4">Guest</th>
								<th class="p-4">Type</th>
								<th class="p-4">Dates</th>
								<th class="p-4">Total</th>
								<th class="p-4">Paid</th>
								<th class="p-4">Status</th>
								<th class="p-4 text-right">Action</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-gray-100 text-sm">
							<?php if (empty($bookings)): ?>
								<tr>
									<td colspan="7" class="p-10 text-center text-gray-400 font-medium">No bookings found.</td>
								</tr>
							<?php endif; ?>
							<?php foreach ($bookings as $b): ?>
								<tr class="hover:bg-gray-50/50">
									<td class="p-4">
										<div class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($b['guest_name']); ?></div>
										<div class="text-[11px] text-gray-500 mt-1"><?php echo htmlspecialchars($b['guest_phone']); ?></div>
									</td>
									<td class="p-4 text-[11px] font-bold uppercase tracking-widest text-gray-500"><?php echo htmlspecialchars($b['booking_type']); ?></td>
									<td class="p-4">
										<div class="text-[12px] font-bold text-gray-700">
											<?php echo date('M d', strtotime($b['check_in_date'])); ?> - <?php echo date('M d', strtotime($b['check_out_date'])); ?>
										</div>
									</td>
									<td class="p-4 font-bold text-gray-800">LKR <?php echo number_format($b['total_price']); ?></td>
									<td class="p-4 font-bold text-gray-800">LKR <?php echo number_format($b['amount_paid']); ?></td>
									<td class="p-4">
										<span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest <?php echo $status_styles[$b['status']] ?? 'bg-gray-100 text-gray-600'; ?>">
											<?php echo str_replace('_', ' ', $b['status']); ?>
										</span>
									</td>
									<td class="p-4 text-right">
										<a href="booking_details.php?id=<?php echo $b['id']; ?>" class="bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-widest hover:bg-blue-600 hover:text-white transition-all">View</a>
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
		const trendLabels = <?php echo json_encode($trend_labels); ?>;
		const trendCounts = <?php echo json_encode($trend_counts); ?>;
		const roomLabels = <?php echo json_encode($room_labels); ?>;
		const roomCounts = <?php echo json_encode($room_counts); ?>;
		const revenueData = <?php echo json_encode([$revenue_online, $revenue_inplace]); ?>;
		const bookingTypeData = <?php echo json_encode($booking_type_counts); ?>;

		const trendCtx = document.getElementById('monthlyBookingsChart');
		if (trendCtx) {
			new Chart(trendCtx, {
				type: 'line',
				data: {
					labels: trendLabels,
					datasets: [{
						label: 'Bookings',
						data: trendCounts,
						borderColor: '#003580',
						backgroundColor: 'rgba(0, 53, 128, 0.15)',
						tension: 0.3,
						fill: true,
						pointRadius: 2
					}]
				},
				options: {
					responsive: true,
					plugins: {
						legend: { display: false }
					},
					scales: {
						y: { beginAtZero: true, ticks: { precision: 0 } }
					}
				}
			});
		}

		const revenueCtx = document.getElementById('revenueSplitChart');
		if (revenueCtx) {
			new Chart(revenueCtx, {
				type: 'doughnut',
				data: {
					labels: ['Online', 'Physical'],
					datasets: [{
						data: revenueData,
						backgroundColor: ['#006ce4', '#94a3b8']
					}]
				},
				options: {
					plugins: {
						legend: { position: 'bottom' }
					}
				}
			});
		}

		const roomCtx = document.getElementById('roomTypesChart');
		if (roomCtx) {
			new Chart(roomCtx, {
				type: 'pie',
				data: {
					labels: roomLabels,
					datasets: [{
						data: roomCounts,
						backgroundColor: ['#1d4ed8', '#38bdf8', '#22c55e', '#f59e0b', '#ef4444', '#a855f7']
					}]
				},
				options: {
					plugins: {
						legend: { position: 'bottom' }
					}
				}
			});
		}

		const bookingTypeCtx = document.getElementById('bookingTypeChart');
		if (bookingTypeCtx) {
			new Chart(bookingTypeCtx, {
				type: 'bar',
				data: {
					labels: ['Online', 'Physical'],
					datasets: [{
						label: 'Bookings',
						data: bookingTypeData,
						backgroundColor: ['#0ea5e9', '#10b981']
					}]
				},
				options: {
					plugins: {
						legend: { display: false }
					},
					scales: {
						y: { beginAtZero: true, ticks: { precision: 0 } }
					}
				}
			});
		}
	</script>
</body>
</html>
