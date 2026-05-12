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

$search = trim($_GET['search'] ?? '');
$type = $_GET['type'] ?? '';
$rating = $_GET['rating'] ?? '';

$where = ["r.property_id = ?", "r.status IN ('published', 'reported')"];
$params = [$property_id];

if ($search !== '') {
	$like = '%' . $search . '%';
	$where[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.phone_number LIKE ? OR b.guest_name LIKE ? OR b.guest_phone LIKE ? OR r.comment LIKE ?)";
	$params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($type !== '') {
	$where[] = "r.feedback_type = ?";
	$params[] = $type;
}
if ($rating !== '') {
	$where[] = "r.rating = ?";
	$params[] = (int)$rating;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT r.*, u.first_name, u.last_name, u.phone_number, b.guest_name, b.guest_phone
		FROM reviews r
		LEFT JOIN users u ON r.user_id = u.id
		LEFT JOIN bookings b ON r.booking_id = b.id
		$where_sql
		ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

function h($value) {
	return htmlspecialchars((string)$value);
}

$type_styles = [
	'positive' => 'bg-emerald-50 text-emerald-700',
	'negative' => 'bg-red-50 text-red-700'
];

$status_styles = [
	'published' => 'bg-emerald-100 text-emerald-700',
	'reported' => 'bg-red-100 text-red-700'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Feedbacks - Bookingjaunt</title>
	<script src="https://cdn.tailwindcss.com"></script>
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
									<?php echo h($prop['property_name']); ?>
								</option>
							<?php endforeach; ?>
						<?php else: ?>
							<option value="">No properties</option>
						<?php endif; ?>
					</select>
				</form>
			</div>
			<div class="flex items-center gap-3">
				<a href="../../index.php" target="_blank" class="hidden xl:flex items-center gap-2 px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-50 transition-all">
					<i class="fas fa-external-link-alt"></i> Visit Site
				</a>
			</div>
		</header>

		<div class="p-4 lg:p-8 space-y-6">
			<div>
				<h1 class="text-2xl font-black text-slate-800">Feedbacks</h1>
				<p class="text-slate-500 text-sm">Published or reported feedback for <?php echo h($property['property_name'] ?? 'your property'); ?></p>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
					<input type="hidden" name="property_id" value="<?php echo (int)$property_id; ?>">
					<div class="lg:col-span-2">
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Search</label>
						<input type="text" name="search" value="<?php echo h($search); ?>" placeholder="Guest name, phone, or comment" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm focus:ring-2 focus:ring-[#006ce4] outline-none">
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
					<div class="flex gap-3">
						<button class="px-5 py-2 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest">Apply</button>
						<a href="feedbacks.php?property_id=<?php echo (int)$property_id; ?>" class="px-5 py-2 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-widest">Reset</a>
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
						<h3 class="text-lg font-bold text-gray-700">No feedback yet</h3>
						<p class="text-xs text-gray-400 mt-2">Published feedback will appear here.</p>
					</div>
				<?php else: ?>
					<div class="overflow-x-auto">
						<table class="w-full text-left">
							<thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
								<tr>
									<th class="p-4">Guest</th>
									<th class="p-4">Comment</th>
									<th class="p-4">Rating</th>
									<th class="p-4">Type</th>
									<th class="p-4">Status</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-100 text-sm">
								<?php foreach ($reviews as $review):
									$is_negative = ($review['feedback_type'] ?? '') === 'negative';
									$row_class = $is_negative ? 'bg-red-50/60' : '';
									$type_class = $type_styles[$review['feedback_type'] ?? 'positive'] ?? 'bg-gray-100 text-gray-600';
									$status_class = $status_styles[$review['status'] ?? 'published'] ?? 'bg-gray-100 text-gray-600';
									$guest_name = trim(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? ''));
									$guest_name = $guest_name !== '' ? $guest_name : ($review['guest_name'] ?? 'Guest');
								?>
									<tr class="<?php echo $row_class; ?>">
										<td class="p-4 align-top">
											<div class="font-bold text-gray-800"><?php echo h($guest_name); ?></div>
											<div class="text-xs text-gray-500 mt-1"><?php echo h($review['phone_number'] ?? $review['guest_phone'] ?? ''); ?></div>
										</td>
										<td class="p-4 align-top">
											<p class="text-gray-700"><?php echo nl2br(h($review['comment'] ?? '')); ?></p>
											<?php if (!empty($review['report_note'])): ?>
												<p class="text-xs text-red-600 font-bold mt-2">Report: <?php echo h($review['report_note']); ?></p>
											<?php endif; ?>
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
												<?php echo h($review['status'] ?? 'published'); ?>
											</span>
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
