<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: ../../admin.php");
	exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['create_amenity'])) {
		$category = trim($_POST['category'] ?? '');
		$amenity_name = trim($_POST['amenity_name'] ?? '');
		$icon = trim($_POST['icon'] ?? '');
		$is_popular = isset($_POST['is_popular']) ? 1 : 0;

		if ($category === '' || $amenity_name === '') {
			$errors[] = 'Category and amenity name are required.';
		} else {
			$stmt = $pdo->prepare("INSERT INTO amenities_master (category, amenity_name, icon, is_popular) VALUES (?, ?, ?, ?)");
			$stmt->execute([$category, $amenity_name, $icon !== '' ? $icon : null, $is_popular]);
			$success = 'Amenity added successfully.';
		}
	}

	if (isset($_POST['delete_amenity'])) {
		$amenity_id = (int)($_POST['amenity_id'] ?? 0);
		if ($amenity_id > 0) {
			$stmt = $pdo->prepare("DELETE FROM amenities_master WHERE id = ?");
			$stmt->execute([$amenity_id]);
			$success = 'Amenity removed successfully.';
		}
	}
}

$amenities = $pdo->query("SELECT * FROM amenities_master ORDER BY category, amenity_name")->fetchAll();
$grouped = [];
foreach ($amenities as $amenity) {
	$grouped[$amenity['category']][] = $amenity;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Amenities Master - Bookingjaunt</title>
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

	<main class="flex-1 ml-64 overflow-y-auto h-screen bg-[#f8fafc]">
		<header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-8 py-6">
			<h1 class="text-2xl font-black text-[#003580]">Amenities Master</h1>
			<p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Manage available amenities</p>
		</header>

		<div class="p-8 space-y-8">
			<?php if (!empty($errors)): ?>
				<div class="bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-3 rounded-xl">
					<?php echo htmlspecialchars(implode(' ', $errors)); ?>
				</div>
			<?php endif; ?>
			<?php if ($success !== ''): ?>
				<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-xl">
					<?php echo htmlspecialchars($success); ?>
				</div>
			<?php endif; ?>

			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<h2 class="font-bold text-[#003580] mb-4">Add New Amenity</h2>
				<form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Category</label>
						<input type="text" name="category" placeholder="Basic, Dining" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm" required>
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Amenity Name</label>
						<input type="text" name="amenity_name" placeholder="Free WiFi" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm" required>
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Icon Class</label>
						<input type="text" name="icon" placeholder="fa-wifi" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
						<p class="text-[10px] text-gray-400 mt-1">Font Awesome icon class</p>
					</div>
					<div class="flex items-center gap-2">
						<label class="text-xs font-semibold text-gray-600">
							<input type="checkbox" name="is_popular" class="mr-2">Popular
						</label>
						<button type="submit" name="create_amenity" class="ml-auto px-5 py-2 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest">Create</button>
					</div>
				</form>
			</div>

			<div class="space-y-6">
				<?php if (empty($grouped)): ?>
					<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
						<div class="w-16 h-16 bg-blue-50 text-[#006ce4] rounded-full flex items-center justify-center mx-auto mb-4">
							<i class="fas fa-list-ul text-2xl"></i>
						</div>
						<h3 class="text-lg font-bold text-gray-700">No amenities yet</h3>
						<p class="text-xs text-gray-400 mt-2">Add your first amenity using the form above.</p>
					</div>
				<?php else: ?>
					<?php foreach ($grouped as $category => $items): ?>
						<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
							<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
								<h3 class="font-bold text-[#003580]"><?php echo htmlspecialchars($category); ?></h3>
								<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400"><?php echo count($items); ?> items</span>
							</div>
							<div class="overflow-x-auto">
								<table class="w-full text-left">
									<thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
										<tr>
											<th class="px-6 py-4">Amenity</th>
											<th class="px-6 py-4">Icon</th>
											<th class="px-6 py-4">Popular</th>
											<th class="px-6 py-4 text-right">Action</th>
										</tr>
									</thead>
									<tbody class="divide-y divide-gray-100">
										<?php foreach ($items as $amenity): ?>
											<tr class="hover:bg-gray-50 transition-colors">
												<td class="px-6 py-4 text-sm font-semibold text-gray-700">
													<?php echo htmlspecialchars($amenity['amenity_name']); ?>
												</td>
												<td class="px-6 py-4 text-sm text-gray-500">
													<?php if (!empty($amenity['icon'])): ?>
														<span class="inline-flex items-center gap-2">
															<i class="fas <?php echo htmlspecialchars($amenity['icon']); ?>"></i>
															<?php echo htmlspecialchars($amenity['icon']); ?>
														</span>
													<?php else: ?>
														<span class="text-gray-400">N/A</span>
													<?php endif; ?>
												</td>
												<td class="px-6 py-4 text-sm">
													<?php if ((int)$amenity['is_popular'] === 1): ?>
														<span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest bg-emerald-100 text-emerald-700">Yes</span>
													<?php else: ?>
														<span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest bg-gray-100 text-gray-600">No</span>
													<?php endif; ?>
												</td>
												<td class="px-6 py-4 text-right">
													<form method="POST" onsubmit="return confirm('Remove this amenity?');" class="inline">
														<input type="hidden" name="amenity_id" value="<?php echo (int)$amenity['id']; ?>">
														<button type="submit" name="delete_amenity" class="text-red-500 text-xs font-bold uppercase tracking-widest">Remove</button>
													</form>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</main>

</body>

</html>
