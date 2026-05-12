<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: ../../admin.php");
	exit();
}

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['review_id'])) {
	$review_id = (int)$_POST['review_id'];
	$action = $_POST['action'];

	if ($review_id > 0 && $action === 'publish') {
		$stmt = $pdo->prepare("UPDATE reviews SET status = 'published', published_at = NOW() WHERE id = ?");
		$stmt->execute([$review_id]);
		$flash = 'Feedback published.';
	}

	if ($review_id > 0 && $action === 'report') {
		$note = trim($_POST['report_note'] ?? '');
		$stmt = $pdo->prepare("UPDATE reviews SET status = 'reported', report_note = ?, reported_at = NOW() WHERE id = ?");
		$stmt->execute([$note, $review_id]);
		$flash = 'Feedback reported.';
	}
}

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$rating = $_GET['rating'] ?? '';
$order = $_GET['order'] ?? 'newest';

$where = [];
$params = [];

if ($search !== '') {
	$like = '%' . $search . '%';
	$where[] = "(p.property_name LIKE ? OR p.contact_number LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.phone_number LIKE ? OR b.guest_name LIKE ? OR b.guest_phone LIKE ?)";
	$params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}
if ($status !== '') {
	$where[] = "r.status = ?";
	$params[] = $status;
}
if ($type !== '') {
	$where[] = "r.feedback_type = ?";
	$params[] = $type;
}
if ($rating !== '') {
	$where[] = "r.rating = ?";
	$params[] = (int)$rating;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$order_sql = $order === 'oldest' ? 'ASC' : 'DESC';

$sql = "SELECT r.*, p.property_name, p.contact_number AS property_contact,
			   u.first_name, u.last_name, u.phone_number,
			   b.guest_name, b.guest_phone, b.check_in_date, b.check_out_date
		FROM reviews r
		LEFT JOIN properties p ON r.property_id = p.id
		LEFT JOIN users u ON r.user_id = u.id
		LEFT JOIN bookings b ON r.booking_id = b.id
		$where_sql
		ORDER BY r.created_at $order_sql";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

function h($value) {
	return htmlspecialchars((string)$value);
}

$status_styles = [
	'pending' => 'bg-amber-100 text-amber-700',
	'published' => 'bg-emerald-100 text-emerald-700',
	'reported' => 'bg-red-100 text-red-700'
];

$type_styles = [
	'positive' => 'bg-emerald-50 text-emerald-700',
	'negative' => 'bg-red-50 text-red-700'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Feedbacks - Bookingjaunt</title>
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
				<h1 class="text-2xl font-black text-[#003580]">Feedbacks</h1>
				<p class="text-xs text-gray-500 font-bold uppercase tracking-widest hidden sm:block">Review, publish, or report user feedback</p>
			</div>
		</header>

		<div class="p-4 lg:p-8 space-y-6">
			<?php if ($flash !== ''): ?>
				<div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-xl text-sm">
					<?php echo h($flash); ?>
				</div>
			<?php endif; ?>

			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
					<div class="lg:col-span-2">
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Search</label>
						<input type="text" name="search" value="<?php echo h($search); ?>" placeholder="Property, guest, phone, or owner" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm focus:ring-2 focus:ring-[#006ce4] outline-none">
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Status</label>
						<select name="status" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
							<option value="">All</option>
							<?php foreach (array_keys($status_styles) as $item): ?>
								<option value="<?php echo $item; ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo ucfirst($item); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Type</label>
						<select name="type" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
							<option value="">All</option>
							<?php foreach (array_keys($type_styles) as $item): ?>
								<option value="<?php echo $item; ?>" <?php echo $type === $item ? 'selected' : ''; ?>><?php echo ucfirst($item); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Rating</label>
						<select name="rating" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
							<option value="">All</option>
							<?php for ($i = 1; $i <= 5; $i++): ?>
								<option value="<?php echo $i; ?>" <?php echo (string)$rating === (string)$i ? 'selected' : ''; ?>><?php echo $i; ?> Star</option>
							<?php endfor; ?>
						</select>
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Order</label>
						<select name="order" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
							<option value="newest" <?php echo $order === 'newest' ? 'selected' : ''; ?>>Newest</option>
							<option value="oldest" <?php echo $order === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
						</select>
					</div>
					<div class="flex gap-3">
						<button class="px-5 py-2 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest">Apply</button>
						<a href="feedbacks.php" class="px-5 py-2 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-widest">Reset</a>
					</div>
				</form>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
				<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
					<h2 class="font-bold text-[#003580]">Feedback Records</h2>
					<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total <?php echo count($reviews); ?></span>
				</div>

				<?php if (empty($reviews)): ?>
					<div class="p-12 text-center">
						<div class="w-16 h-16 bg-blue-50 text-[#006ce4] rounded-full flex items-center justify-center mx-auto mb-4">
							<i class="fas fa-comment-dots text-2xl"></i>
						</div>
						<h3 class="text-lg font-bold text-gray-700">No feedback found</h3>
						<p class="text-xs text-gray-400 mt-2">Try adjusting your filters.</p>
					</div>
				<?php else: ?>
					<div class="overflow-x-auto">
						<table class="w-full text-left">
							<thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
								<tr>
									<th class="p-4">Feedback</th>
									<th class="p-4">Guest</th>
									<th class="p-4">Property</th>
									<th class="p-4">Rating</th>
									<th class="p-4">Type</th>
									<th class="p-4">Status</th>
									<th class="p-4">Actions</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-100 text-sm">
								<?php foreach ($reviews as $review):
									$is_negative = ($review['feedback_type'] ?? '') === 'negative';
									$row_class = $is_negative ? 'bg-red-50/60' : '';
									$status_class = $status_styles[$review['status'] ?? 'pending'] ?? 'bg-gray-100 text-gray-600';
									$type_class = $type_styles[$review['feedback_type'] ?? 'positive'] ?? 'bg-gray-100 text-gray-600';
									$guest_name = trim(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? ''));
									$guest_name = $guest_name !== '' ? $guest_name : ($review['guest_name'] ?? 'Guest');
								?>
									<tr class="<?php echo $row_class; ?>">
										<td class="p-4 align-top">
											<div class="font-bold text-gray-800">#<?php echo (int)$review['id']; ?></div>
											<div class="text-xs text-gray-500 mt-1">Booked: <?php echo h($review['check_in_date'] ?? ''); ?> → <?php echo h($review['check_out_date'] ?? ''); ?></div>
											<p class="text-sm text-gray-700 mt-2"><?php echo nl2br(h($review['comment'] ?? '')); ?></p>
											<?php if (!empty($review['report_note'])): ?>
												<p class="text-xs text-red-600 font-bold mt-2">Report: <?php echo h($review['report_note']); ?></p>
											<?php endif; ?>
										</td>
										<td class="p-4 align-top">
											<div class="font-bold text-gray-800"><?php echo h($guest_name); ?></div>
											<div class="text-xs text-gray-500 mt-1"><?php echo h($review['phone_number'] ?? $review['guest_phone'] ?? ''); ?></div>
										</td>
										<td class="p-4 align-top">
											<div class="font-bold text-gray-800"><?php echo h($review['property_name'] ?? ''); ?></div>
											<div class="text-xs text-gray-500 mt-1"><?php echo h($review['property_contact'] ?? ''); ?></div>
										</td>
										<td class="p-4 align-top">
											<div class="font-black text-[#003580] text-lg"><?php echo (int)($review['rating'] ?? 0); ?>/5</div>
										</td>
										<td class="p-4 align-top">
											<span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest <?php echo $type_class; ?>">
												<?php echo h($review['feedback_type'] ?? 'positive'); ?>
											</span>
										</td>
										<td class="p-4 align-top">
											<span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest <?php echo $status_class; ?>">
												<?php echo h($review['status'] ?? 'pending'); ?>
											</span>
										</td>
										<td class="p-4 align-top">
											<div class="flex flex-col gap-2">
												<?php if (!empty($review['booking_id'])): ?>
													<a href="../hotel/booking_details.php?id=<?php echo (int)$review['booking_id']; ?>" target="_blank" class="px-3 py-2 rounded-lg bg-gray-900 text-white text-[10px] font-bold uppercase tracking-widest text-center">
														View
													</a>
													<a href="../hotel/booking_details.php?id=<?php echo (int)$review['booking_id']; ?>&download=1" target="_blank" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 text-[10px] font-bold uppercase tracking-widest text-center">
														Invoice
													</a>
												<?php endif; ?>
												<?php if (($review['status'] ?? 'pending') !== 'published'): ?>
													<form method="POST">
														<input type="hidden" name="review_id" value="<?php echo (int)$review['id']; ?>">
														<input type="hidden" name="action" value="publish">
														<button class="w-full px-3 py-2 rounded-lg bg-emerald-600 text-white text-[10px] font-bold uppercase tracking-widest">Publish</button>
													</form>
												<?php endif; ?>
												<?php if (($review['status'] ?? 'pending') !== 'reported'): ?>
													<form method="POST" class="space-y-2">
														<input type="hidden" name="review_id" value="<?php echo (int)$review['id']; ?>">
														<input type="hidden" name="action" value="report">
														<input type="text" name="report_note" placeholder="Report note" class="w-full px-3 py-2 rounded-lg bg-gray-50 border border-gray-200 text-xs">
														<button class="w-full px-3 py-2 rounded-lg bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest">Report</button>
													</form>
												<?php endif; ?>
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
