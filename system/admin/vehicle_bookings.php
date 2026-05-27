<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: ../../admin.php");
	exit();
}

// Pagination config
$per_page = 20;
$current_page_num = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page_num - 1) * $per_page;

// Status filter
$status = trim($_GET['status'] ?? '');

$where = ["b.booking_category = 'vehicle'"];
$params = [];

if ($status !== '') {
	$where[] = "b.status = ?";
	$params[] = $status;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Total count for pagination
$count_sql = "SELECT COUNT(*) FROM bookings b
              JOIN properties p ON b.property_id = p.id
              JOIN users u ON p.owner_id = u.id
              $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_records / $per_page));

// Fetch vehicle bookings
$list_sql = "SELECT b.*, p.property_name, p.vehicle_category, p.district,
                    u.first_name, u.last_name
             FROM bookings b
             JOIN properties p ON b.property_id = p.id
             JOIN users u ON p.owner_id = u.id
             $where_sql
             ORDER BY b.created_at DESC
             LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($list_sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Summary counts per status
$status_counts_sql = "SELECT b.status, COUNT(*) as cnt
                      FROM bookings b
                      WHERE b.booking_category = 'vehicle'
                      GROUP BY b.status";
$status_counts_stmt = $pdo->query($status_counts_sql);
$status_counts_raw = $status_counts_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$all_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];

$status_badge_styles = [
	'pending'   => 'bg-yellow-100 text-yellow-700',
	'confirmed' => 'bg-blue-100 text-blue-700',
	'completed' => 'bg-green-100 text-green-700',
	'cancelled' => 'bg-red-100 text-red-700',
];

$status_pill_styles = [
	'pending'   => 'bg-yellow-50 text-yellow-700 border-yellow-200',
	'confirmed' => 'bg-blue-50 text-blue-700 border-blue-200',
	'completed' => 'bg-green-50 text-green-700 border-green-200',
	'cancelled' => 'bg-red-50 text-red-700 border-red-200',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Vehicle Bookings - Bookingjaunt</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		body {
			font-family: 'Plus Jakarta Sans', sans-serif;
			background-color: #f8fafc;
		}
	</style>
</head>

<body class="flex min-h-screen overflow-hidden">

	<?php include 'sidebar.php'; ?>

	<main class="flex-1 lg:ml-64 overflow-y-auto h-screen bg-[#f8fafc]">
		<header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-4 lg:px-8 py-6 flex items-center gap-4">
			<button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-[#003580] hover:bg-gray-100 transition-all">
				<i class="fas fa-bars-staggered"></i>
			</button>
			<div class="flex items-center gap-4 flex-1">
				<div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl">
					<i class="fas fa-car-side"></i>
				</div>
				<div class="flex flex-col gap-1">
					<h1 class="text-2xl font-black text-[#003580]">Vehicle Bookings</h1>
					<p class="text-xs text-gray-500 font-bold uppercase tracking-widest hidden sm:block">All vehicle rental bookings</p>
				</div>
			</div>
			<span class="text-[11px] font-bold uppercase tracking-widest text-white bg-[#006ce4] px-4 py-2 rounded-xl">
				Total: <?php echo number_format($total_records); ?>
			</span>
		</header>

		<div class="p-4 lg:p-8 space-y-6">

			<!-- Summary Stat Cards -->
			<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
				<div class="bg-white p-5 rounded-2xl border border-gray-100">
					<p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">All Bookings</p>
					<h3 class="text-2xl font-black text-[#003580] mt-1"><?php echo number_format($total_records); ?></h3>
				</div>
				<?php foreach ($all_statuses as $s): ?>
					<div class="bg-white p-5 rounded-2xl border border-gray-100">
						<p class="text-[10px] font-bold uppercase tracking-widest text-gray-400"><?php echo ucfirst($s); ?></p>
						<h3 class="text-2xl font-black text-[#003580] mt-1"><?php echo number_format($status_counts_raw[$s] ?? 0); ?></h3>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Status Filter Pills -->
			<div class="bg-white rounded-2xl border border-gray-100 p-4">
				<p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Filter by Status</p>
				<div class="flex flex-wrap gap-2">
					<a href="vehicle_bookings.php"
					   class="px-4 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-widest border transition-all
					          <?php echo $status === '' ? 'bg-[#003580] text-white border-[#003580]' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'; ?>">
						All
					</a>
					<?php foreach ($all_statuses as $s): ?>
						<a href="vehicle_bookings.php?status=<?php echo urlencode($s); ?><?php echo $current_page_num > 1 ? '&page=' . $current_page_num : ''; ?>"
						   class="px-4 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-widest border transition-all
						          <?php echo $status === $s ? ($status_pill_styles[$s] ?? 'bg-gray-100 text-gray-600 border-gray-200') . ' font-black' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'; ?>">
							<?php echo ucfirst($s); ?>
							<span class="ml-1 opacity-60">(<?php echo $status_counts_raw[$s] ?? 0; ?>)</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Bookings Table -->
			<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
				<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
					<h2 class="font-bold text-[#003580]">Vehicle Booking Records</h2>
					<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
						Showing <?php echo count($bookings); ?> of <?php echo number_format($total_records); ?>
					</span>
				</div>

				<?php if (empty($bookings)): ?>
					<div class="p-12 text-center">
						<div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4">
							<i class="fas fa-car-side text-2xl"></i>
						</div>
						<h3 class="text-lg font-bold text-gray-700">No vehicle bookings found</h3>
						<p class="text-xs text-gray-400 mt-2">
							<?php echo $status !== '' ? 'No bookings match the selected status filter.' : 'Vehicle bookings will appear here once made.'; ?>
						</p>
					</div>
				<?php else: ?>
					<div class="overflow-x-auto">
						<table class="w-full text-left">
							<thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
								<tr>
									<th class="px-5 py-4">ID</th>
									<th class="px-5 py-4">Vehicle</th>
									<th class="px-5 py-4">Guest Name</th>
									<th class="px-5 py-4">Guest Phone</th>
									<th class="px-5 py-4">Pickup Date</th>
									<th class="px-5 py-4">Return Date</th>
									<th class="px-5 py-4">Driver</th>
									<th class="px-5 py-4">District</th>
									<th class="px-5 py-4">Total Price</th>
									<th class="px-5 py-4">Status</th>
									<th class="px-5 py-4">Created</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-100">
								<?php foreach ($bookings as $booking):
									$b_status = $booking['status'] ?? 'pending';
									$style = $status_badge_styles[$b_status] ?? 'bg-gray-100 text-gray-600';
									$driver_required = $booking['driver_required'] ?? $booking['with_driver'] ?? null;
								?>
									<tr class="hover:bg-gray-50 transition-colors">
										<!-- ID -->
										<td class="px-5 py-4 text-xs text-gray-400 font-mono">#<?php echo (int)$booking['id']; ?></td>

										<!-- Vehicle Name + Category -->
										<td class="px-5 py-4">
											<p class="text-sm font-bold text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($booking['property_name']); ?></p>
											<span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-purple-50 text-purple-600 uppercase">
												<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['vehicle_category'] ?? ''))); ?>
											</span>
										</td>

										<!-- Guest Name -->
										<td class="px-5 py-4 text-sm text-gray-700 font-semibold whitespace-nowrap">
											<?php echo htmlspecialchars($booking['guest_name'] ?? 'N/A'); ?>
										</td>

										<!-- Guest Phone -->
										<td class="px-5 py-4 text-sm text-gray-600">
											<?php echo htmlspecialchars($booking['guest_phone'] ?? $booking['guest_contact'] ?? 'N/A'); ?>
										</td>

										<!-- Pickup Date -->
										<td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">
											<?php
											$pickup = $booking['pickup_date'] ?? $booking['check_in_date'] ?? null;
											echo $pickup ? date('d M Y', strtotime($pickup)) : 'N/A';
											?>
										</td>

										<!-- Return Date -->
										<td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">
											<?php
											$return = $booking['return_date'] ?? $booking['check_out_date'] ?? null;
											echo $return ? date('d M Y', strtotime($return)) : 'N/A';
											?>
										</td>

										<!-- Driver -->
										<td class="px-5 py-4">
											<?php if ($driver_required == 1 || $driver_required === 'yes' || $driver_required === true): ?>
												<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-blue-50 text-blue-600 uppercase"><i class="fas fa-user mr-1"></i>Yes</span>
											<?php else: ?>
												<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-500 uppercase">No</span>
											<?php endif; ?>
										</td>

										<!-- District -->
										<td class="px-5 py-4 text-sm text-gray-600">
											<?php echo htmlspecialchars($booking['district'] ?? 'N/A'); ?>
										</td>

										<!-- Total Price -->
										<td class="px-5 py-4 text-sm font-bold text-[#003580] whitespace-nowrap">
											LKR <?php echo number_format((float)($booking['total_price'] ?? 0), 2); ?>
										</td>

										<!-- Status -->
										<td class="px-5 py-4">
											<span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest <?php echo $style; ?>">
												<?php echo ucfirst($b_status); ?>
											</span>
										</td>

										<!-- Created At -->
										<td class="px-5 py-4 text-xs text-gray-400 whitespace-nowrap">
											<?php echo date('d M Y', strtotime($booking['created_at'])); ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<!-- Pagination -->
					<?php if ($total_pages > 1): ?>
						<div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-4">
							<p class="text-xs text-gray-400 font-medium">
								Page <?php echo $current_page_num; ?> of <?php echo $total_pages; ?> &mdash; <?php echo number_format($total_records); ?> total records
							</p>
							<div class="flex items-center gap-2">
								<?php
								$base_url = 'vehicle_bookings.php?' . ($status !== '' ? 'status=' . urlencode($status) . '&' : '');
								$prev_page = max(1, $current_page_num - 1);
								$next_page = min($total_pages, $current_page_num + 1);
								?>
								<a href="<?php echo $base_url; ?>page=<?php echo $prev_page; ?>"
								   class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50 transition-all <?php echo $current_page_num === 1 ? 'opacity-40 pointer-events-none' : ''; ?>">
									<i class="fas fa-chevron-left text-xs"></i>
								</a>

								<?php
								$start_p = max(1, $current_page_num - 2);
								$end_p = min($total_pages, $current_page_num + 2);
								for ($p = $start_p; $p <= $end_p; $p++):
								?>
									<a href="<?php echo $base_url; ?>page=<?php echo $p; ?>"
									   class="w-9 h-9 flex items-center justify-center rounded-xl text-xs font-bold border transition-all
									          <?php echo $p === $current_page_num ? 'bg-[#003580] text-white border-[#003580]' : 'border-gray-200 text-gray-600 hover:bg-gray-50'; ?>">
										<?php echo $p; ?>
									</a>
								<?php endfor; ?>

								<a href="<?php echo $base_url; ?>page=<?php echo $next_page; ?>"
								   class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50 transition-all <?php echo $current_page_num === $total_pages ? 'opacity-40 pointer-events-none' : ''; ?>">
									<i class="fas fa-chevron-right text-xs"></i>
								</a>
							</div>
						</div>
					<?php endif; ?>

				<?php endif; ?>
			</div>
		</div>
	</main>

</body>

</html>
