<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: ../../admin.php");
	exit();
}

// 1. Determine the Year to filter
$selected_year = $_GET['year'] ?? date('Y');
$day = $_GET['day'] ?? '';
$month = $_GET['month'] ?? '';

// Commission logic (Keep as is for the current month summary)
$commission_rate = 0.2;
$commission_start = date('Y-m-01');
$commission_end = date('Y-m-t');
$commission_stmt = $pdo->prepare("SELECT 
						COALESCE(SUM(total_price), 0) AS total_price, 
						COALESCE(SUM(amount_paid), 0) AS total_paid 
					FROM bookings 
					WHERE booking_type = 'online' 
						AND DATE(created_at) BETWEEN ? AND ?");
$commission_stmt->execute([$commission_start, $commission_end]);
$commission_row = $commission_stmt->fetch();
$commission_total = ($commission_row['total_price'] ?? 0) * $commission_rate;
$commission_paid = ($commission_row['total_paid'] ?? 0) * $commission_rate;
$commission_due = max(0, $commission_total - $commission_paid);

// 2. FIXED: Generate Keys for JAN to DEC of the SELECTED YEAR
$chart_keys = [];
$chart_labels = [];
for ($m = 1; $m <= 12; $m++) {
    $month_num = str_pad($m, 2, "0", STR_PAD_LEFT);
    $chart_keys[] = $selected_year . '-' . $month_num;
    $chart_labels[] = date('M', mktime(0, 0, 0, $m, 1)) . ' ' . $selected_year;
}

$start_of_year = $selected_year . '-01-01 00:00:00';
$end_of_year = $selected_year . '-12-31 23:59:59';

// 3. Updated SQL to filter strictly by the selected year
$bookings_stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS count FROM bookings WHERE created_at BETWEEN ? AND ? GROUP BY ym");
$bookings_stmt->execute([$start_of_year, $end_of_year]);
$bookings_rows = $bookings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$properties_stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS count FROM properties WHERE created_at BETWEEN ? AND ? GROUP BY ym");
$properties_stmt->execute([$start_of_year, $end_of_year]);
$properties_rows = $properties_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$users_stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS count FROM users WHERE role != 'admin' AND created_at BETWEEN ? AND ? GROUP BY ym");
$users_stmt->execute([$start_of_year, $end_of_year]);
$users_rows = $users_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$booking_series = [];
$property_series = [];
$user_series = [];
foreach ($chart_keys as $key) {
	$booking_series[] = (int)($bookings_rows[$key] ?? 0);
	$property_series[] = (int)($properties_rows[$key] ?? 0);
	$user_series[] = (int)($users_rows[$key] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reports - Bookingjaunt</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<style>
		body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
		@media print { .no-print { display: none; } body { background: white; } }
	</style>
</head>
<body class="flex min-h-screen">

	<?php include 'sidebar.php'; ?>

	<main class="flex-1 lg:ml-64 overflow-y-auto h-screen bg-[#f8fafc]">
		<header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-4 lg:px-8 py-6 flex flex-col gap-3">
			<div class="flex items-center gap-4">
				<button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-[#003580] hover:bg-gray-100 transition-all">
					<i class="fas fa-bars-staggered"></i>
				</button>
				<div>
					<h1 class="text-2xl font-black text-[#003580]">Reports</h1>
					<p class="text-xs text-gray-500 font-bold uppercase tracking-widest hidden sm:block">Annual Insights for <?php echo $selected_year; ?></p>
				</div>
			</div>
			
			<div class="no-print flex flex-wrap items-end gap-4">
				<form method="GET" class="flex flex-wrap gap-3 items-end">
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Year Filter</label>
						<input type="number" name="year" min="2000" max="2100" value="<?php echo htmlspecialchars($selected_year); ?>" class="mt-2 px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
					</div>
					<button class="px-5 py-2 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest">Filter Year</button>
					<a href="reports.php" class="px-5 py-2 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-widest">Reset</a>
				</form>
				<button id="download-pdf" class="ml-auto px-5 py-2 rounded-xl bg-[#003580] text-white text-xs font-bold uppercase tracking-widest">
					Download PDF
				</button>
			</div>
		</header>

		<div class="p-4 lg:p-8 space-y-8">
			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<div class="flex items-center justify-between mb-6">
					<h2 class="font-bold text-[#003580]">Commission (This Month)</h2>
					<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400"><?php echo date('F Y'); ?></span>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
					<div class="p-5 rounded-2xl border border-gray-100">
						<p class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Paid Commission</p>
						<h3 class="text-xl font-black text-[#003580] mt-2">LKR <?php echo number_format($commission_paid, 2); ?></h3>
						<p class="text-[10px] text-gray-500 mt-1">Total: LKR <?php echo number_format($commission_total, 2); ?></p>
					</div>
					<div class="p-5 rounded-2xl border border-gray-100">
						<p class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Commission Due</p>
						<h3 class="text-xl font-black text-[#003580] mt-2">LKR <?php echo number_format($commission_due, 2); ?></h3>
						<p class="text-[10px] text-gray-500 mt-1">Rate: 20% of online bookings</p>
					</div>
				</div>
			</div>

			<div class="w-full">
				<div class="bg-white rounded-2xl border border-gray-100 p-6">
					<h3 class="font-bold text-[#003580] mb-6">Growth Trends (January - December <?php echo $selected_year; ?>)</h3>
					<div class="relative w-full" style="min-height: 400px; height: 65vh;">
						<canvas id="growthChart"></canvas>
					</div>
				</div>
			</div>
		</div>
	</main>

	<script>
		const labels = <?php echo json_encode($chart_labels); ?>;
		const bookingsData = <?php echo json_encode($booking_series); ?>;
		const propertiesData = <?php echo json_encode($property_series); ?>;
		const usersData = <?php echo json_encode($user_series); ?>;

		const ctx = document.getElementById('growthChart').getContext('2d');
		new Chart(ctx, {
			type: 'line',
			data: {
				labels: labels,
				datasets: [
					{
						label: 'Bookings',
						data: bookingsData,
						borderColor: '#006ce4',
						backgroundColor: 'rgba(0, 108, 228, 0.1)',
						tension: 0.4,
						fill: true,
						pointRadius: 5
					},
					{
						label: 'Properties',
						data: propertiesData,
						borderColor: '#f59e0b',
						backgroundColor: 'rgba(245, 158, 11, 0.05)',
						tension: 0.4,
						fill: true,
						pointRadius: 5
					},
					{
						label: 'Users',
						data: usersData,
						borderColor: '#10b981',
						backgroundColor: 'rgba(16, 185, 129, 0.05)',
						tension: 0.4,
						fill: true,
						pointRadius: 5
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'bottom',
						labels: {
							padding: 25,
							usePointStyle: true,
							font: { size: 12, weight: '600' }
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: { precision: 0, color: '#64748b' },
						grid: { color: '#f1f5f9' }
					},
					x: {
						ticks: { color: '#64748b' },
						grid: { display: false }
					}
				},
				interaction: {
					intersect: false,
					mode: 'index'
				}
			}
		});

		document.getElementById('download-pdf').addEventListener('click', () => {
			window.print();
		});

		function toggleSidebar() {
			const sidebar = document.querySelector('aside');
			if(sidebar) sidebar.classList.toggle('-translate-x-full');
		}
	</script>
</body>
</html>
