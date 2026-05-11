<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: ../../admin.php");
	exit();
}

$search = trim($_GET['search'] ?? '');
$day = $_GET['day'] ?? '';
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';
$status = $_GET['status'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
	$like = '%' . $search . '%';
	$where[] = "(p.property_name LIKE ? OR p.contact_number LIKE ? OR b.guest_name LIKE ? OR r.room_name LIKE ?)";
	$params = array_merge($params, [$like, $like, $like, $like]);
}
if ($status !== '') {
	$where[] = "b.status = ?";
	$params[] = $status;
}
if ($payment_status !== '') {
	$where[] = "b.payment_status = ?";
	$params[] = $payment_status;
}
if ($day !== '') {
	$where[] = "DATE(b.created_at) = ?";
	$params[] = $day;
}
if ($month !== '') {
	$where[] = "DATE_FORMAT(b.created_at, '%Y-%m') = ?";
	$params[] = $month;
}
if ($year !== '') {
	$where[] = "YEAR(b.created_at) = ?";
	$params[] = $year;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$summary_sql = "SELECT COUNT(*) AS total_bookings,
					   COALESCE(SUM(b.total_price), 0) AS total_revenue,
					   COALESCE(SUM(b.amount_paid), 0) AS total_paid
				FROM bookings b
				JOIN properties p ON b.property_id = p.id
				LEFT JOIN property_rooms r ON b.room_id = r.id
				$where_sql";
$summary_stmt = $pdo->prepare($summary_sql);
$summary_stmt->execute($params);
$summary = $summary_stmt->fetch();

$total_bookings = (int)($summary['total_bookings'] ?? 0);
$total_revenue = (float)($summary['total_revenue'] ?? 0);
$total_paid = (float)($summary['total_paid'] ?? 0);
$total_due = max(0, $total_revenue - $total_paid);

$list_sql = "SELECT b.*, p.property_name, p.contact_number, r.room_name
			 FROM bookings b
			 JOIN properties p ON b.property_id = p.id
			 LEFT JOIN property_rooms r ON b.room_id = r.id
			 $where_sql
			 ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($list_sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$booking_status_styles = [
	'pending' => 'bg-yellow-100 text-yellow-700',
	'confirmed' => 'bg-blue-100 text-blue-700',
	'checked_in' => 'bg-green-100 text-green-700',
	'checked_out' => 'bg-gray-200 text-gray-700',
	'cancelled' => 'bg-red-100 text-red-700'
];

$payment_status_styles = [
	'pending' => 'bg-orange-100 text-orange-700',
	'complete' => 'bg-emerald-100 text-emerald-700'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bookings - Bookingjaunt</title>
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
			<div class="flex flex-col gap-1">
				<h1 class="text-2xl font-black text-[#003580]">Booking History</h1>
				<p class="text-xs text-gray-500 font-bold uppercase tracking-widest hidden sm:block">All property bookings with payment status</p>
			</div>
		</header>

		<div class="p-4 lg:p-8 space-y-8">
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
				<div class="bg-white p-6 rounded-2xl border border-gray-100">
					<p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Bookings</p>
					<h3 class="text-2xl font-black text-[#003580] mt-2"><?php echo number_format($total_bookings); ?></h3>
				</div>
				<div class="bg-white p-6 rounded-2xl border border-gray-100">
					<p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Revenue</p>
					<h3 class="text-2xl font-black text-[#003580] mt-2">$<?php echo number_format($total_revenue, 2); ?></h3>
				</div>
				<div class="bg-white p-6 rounded-2xl border border-gray-100">
					<p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Amount Paid</p>
					<h3 class="text-2xl font-black text-[#003580] mt-2">$<?php echo number_format($total_paid, 2); ?></h3>
				</div>
				<div class="bg-white p-6 rounded-2xl border border-gray-100">
					<p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Balance Due</p>
					<h3 class="text-2xl font-black text-[#003580] mt-2">$<?php echo number_format($total_due, 2); ?></h3>
				</div>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
					<div class="lg:col-span-2">
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Search</label>
						<input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Property, guest, room, phone" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm focus:ring-2 focus:ring-[#006ce4] outline-none">
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Day</label>
						<input type="date" name="day" value="<?php echo htmlspecialchars($day); ?>" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Month</label>
						<input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Year</label>
						<input type="number" name="year" min="2000" max="2100" value="<?php echo htmlspecialchars($year); ?>" placeholder="2026" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Booking Status</label>
						<select name="status" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
							<option value="">All</option>
							<?php foreach (array_keys($booking_status_styles) as $item): ?>
								<option value="<?php echo $item; ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $item)); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Payment Status</label>
						<select name="payment_status" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
							<option value="">All</option>
							<?php foreach (array_keys($payment_status_styles) as $item): ?>
								<option value="<?php echo $item; ?>" <?php echo $payment_status === $item ? 'selected' : ''; ?>><?php echo ucfirst($item); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="flex gap-3">
						<button class="px-5 py-2 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest">Apply</button>
						<a href="bookings.php" class="px-5 py-2 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-widest">Reset</a>
					</div>
				</form>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
				<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
					<h2 class="font-bold text-[#003580]">Booking Records</h2>
					<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Showing <?php echo count($bookings); ?></span>
				</div>

				<?php if (empty($bookings)): ?>
					<div class="p-12 text-center">
						<div class="w-16 h-16 bg-blue-50 text-[#006ce4] rounded-full flex items-center justify-center mx-auto mb-4">
							<i class="fas fa-calendar-check text-2xl"></i>
						</div>
						<h3 class="text-lg font-bold text-gray-700">No bookings found</h3>
						<p class="text-xs text-gray-400 mt-2">Try adjusting your filters to see more results.</p>
					</div>
				<?php else: ?>
					<div class="overflow-x-auto">
						<table class="w-full text-left">
							<thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
								<tr>
									<th class="px-6 py-4">Property</th>
									<th class="px-6 py-4">Booking Details</th>
									<th class="px-6 py-4">Booking Status</th>
									<th class="px-6 py-4">Payment Stats</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-100">
								<?php foreach ($bookings as $booking): ?>
									<?php
										$booking_status = $booking['status'] ?? 'pending';
										$payment_status_value = $booking['payment_status'] ?? 'pending';
										$total_price = (float)($booking['total_price'] ?? 0);
										$amount_paid = (float)($booking['amount_paid'] ?? 0);
										$balance = max(0, $total_price - $amount_paid);
										$paid_percent = $total_price > 0 ? round(($amount_paid / $total_price) * 100) : 0;
									?>
									<tr class="hover:bg-gray-50 transition-colors">
										<td class="px-6 py-5">
											<div class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($booking['property_name']); ?></div>
											<div class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Contact</div>
											<div class="text-xs text-gray-600"><?php echo htmlspecialchars($booking['contact_number'] ?? 'N/A'); ?></div>
										</td>
										<td class="px-6 py-5">
											<div class="text-xs font-bold text-gray-700"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
											<div class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Room</div>
											<div class="text-xs text-gray-600"><?php echo htmlspecialchars($booking['room_name'] ?? 'N/A'); ?></div>
											<div class="text-[10px] text-gray-400 uppercase tracking-widest mt-2">Dates</div>
											<div class="text-xs text-gray-600">
												<?php echo htmlspecialchars($booking['check_in_date']); ?> to <?php echo htmlspecialchars($booking['check_out_date']); ?>
											</div>
											<div class="text-[10px] text-gray-400 uppercase tracking-widest mt-2">Guests</div>
											<div class="text-xs text-gray-600"><?php echo (int)$booking['adults']; ?> adults, <?php echo (int)$booking['children']; ?> children</div>
										</td>
										<td class="px-6 py-5">
											<span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest <?php echo $booking_status_styles[$booking_status] ?? 'bg-gray-100 text-gray-600'; ?>">
												<?php echo ucwords(str_replace('_', ' ', $booking_status)); ?>
											</span>
											<div class="text-[10px] text-gray-400 uppercase tracking-widest mt-3">Type</div>
											<div class="text-xs text-gray-600"><?php echo htmlspecialchars($booking['booking_type'] ?? 'online'); ?></div>
										</td>
										<td class="px-6 py-5">
											<div class="text-xs text-gray-600">Total: $<?php echo number_format($total_price, 2); ?></div>
											<div class="text-xs text-gray-600">Paid: $<?php echo number_format($amount_paid, 2); ?></div>
											<div class="text-xs text-gray-600">Balance: $<?php echo number_format($balance, 2); ?></div>
											<div class="mt-3 w-full h-2 bg-gray-100 rounded-full overflow-hidden">
												<div class="h-full bg-[#006ce4]" style="width: <?php echo $paid_percent; ?>%"></div>
											</div>
											<div class="mt-2">
												<span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest <?php echo $payment_status_styles[$payment_status_value] ?? 'bg-gray-100 text-gray-600'; ?>">
													<?php echo ucfirst($payment_status_value); ?>
												</span>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</main>

</body>

</html>
