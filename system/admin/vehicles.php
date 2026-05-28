<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: ../../admin.php");
	exit();
}

// Handle delete
$delete_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
	$delete_id = (int)$_POST['delete_id'];
	if ($delete_id > 0) {
		$stmt = $pdo->prepare("DELETE FROM properties WHERE id = ? AND business_type = 'vehicle'");
		$stmt->execute([$delete_id]);
		$delete_message = 'Vehicle deleted successfully.';
	}
}

// Fetch all vehicles with owner info
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$where = ["p.business_type = 'vehicle'"];
$params = [];

if ($search !== '') {
    $where[] = "(p.property_name LIKE ? OR p.city LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($category !== '') {
    $where[] = "p.vehicle_category = ?";
    $params[] = $category;
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT p.*, u.first_name, u.last_name
        FROM properties p
        JOIN users u ON p.owner_id = u.id
        WHERE $whereClause
        ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Vehicles - Bookingjaunt</title>
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
				<div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-xl">
					<i class="fas fa-car"></i>
				</div>
				<div class="flex flex-col gap-1">
					<h1 class="text-2xl font-black text-[#003580]">Vehicles</h1>
					<p class="text-xs text-gray-500 font-bold uppercase tracking-widest hidden sm:block">All registered vehicles</p>
				</div>
			</div>
			<span class="text-[11px] font-bold uppercase tracking-widest text-white bg-[#006ce4] px-4 py-2 rounded-xl">
				Total: <?php echo count($vehicles); ?>
			</span>
		</header>

		<div class="p-4 lg:p-8 space-y-6">

			<?php if ($delete_message !== ''): ?>
				<div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium">
					<i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($delete_message); ?>
				</div>
			<?php endif; ?>

			<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
				<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
					<h2 class="font-bold text-[#003580]">Vehicle Directory</h2>
					<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400"><?php echo count($vehicles); ?> Vehicles Listed</span>
				</div>

				<!-- Search and Filter Form -->
				<div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
					<form method="GET" class="flex flex-wrap gap-4 items-center">
						<div class="flex-1 min-w-[200px]">
							<input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search vehicles, city, or owner..." class="w-full px-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-[#003580]">
						</div>
						<div class="w-full sm:w-auto">
							<select name="category" class="w-full sm:w-auto px-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-[#003580] bg-white text-gray-700">
								<option value="">All Vehicle Types</option>
								<option value="car" <?php echo $category === 'car' ? 'selected' : ''; ?>>Car</option>
								<option value="van" <?php echo $category === 'van' ? 'selected' : ''; ?>>Van</option>
								<option value="suv" <?php echo $category === 'suv' ? 'selected' : ''; ?>>SUV</option>
								<option value="bus" <?php echo $category === 'bus' ? 'selected' : ''; ?>>Bus</option>
								<option value="tuk_tuk" <?php echo $category === 'tuk_tuk' ? 'selected' : ''; ?>>Tuk Tuk</option>
								<option value="motorcycle" <?php echo $category === 'motorcycle' ? 'selected' : ''; ?>>Motorcycle</option>
							</select>
						</div>
						<div class="w-full sm:w-auto flex gap-2">
							<button type="submit" class="px-4 py-2 bg-[#003580] text-white rounded-xl text-sm font-bold hover:bg-[#002b66] transition-colors">
								<i class="fas fa-search mr-2"></i>Search
							</button>
							<?php if($search !== '' || $category !== ''): ?>
							<a href="vehicles.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-300 transition-colors flex items-center">
								Clear
							</a>
							<?php endif; ?>
						</div>
					</form>
				</div>

				<?php if (empty($vehicles)): ?>
					<div class="p-12 text-center">
						<div class="w-16 h-16 bg-purple-50 text-purple-500 rounded-full flex items-center justify-center mx-auto mb-4">
							<i class="fas fa-car text-2xl"></i>
						</div>
						<h3 class="text-lg font-bold text-gray-700">No vehicles found</h3>
						<p class="text-xs text-gray-400 mt-2">Once vehicles are registered, they will appear here.</p>
					</div>
				<?php else: ?>
					<div class="overflow-x-auto">
						<table class="w-full text-left">
							<thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
								<tr>
									<th class="px-6 py-4">ID</th>
									<th class="px-6 py-4">Vehicle</th>
									<th class="px-6 py-4">Owner</th>
									<th class="px-6 py-4">Category</th>
									<th class="px-6 py-4">District</th>
									<th class="px-6 py-4">Driver</th>
									<th class="px-6 py-4">Price</th>
									<th class="px-6 py-4">A/C</th>
									<th class="px-6 py-4">Created</th>
									<th class="px-6 py-4 text-right">Actions</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-100">
								<?php foreach ($vehicles as $vehicle): ?>
									<tr class="hover:bg-gray-50 transition-colors">
										<!-- ID -->
										<td class="px-6 py-4 text-xs text-gray-400 font-mono">#<?php echo (int)$vehicle['id']; ?></td>

										<!-- Cover Image + Name -->
										<td class="px-6 py-4">
											<div class="flex items-center gap-3">
												<div class="w-12 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 border border-gray-200">
													<?php if (!empty($vehicle['cover_image'])): ?>
														<img src="../../<?php echo htmlspecialchars($vehicle['cover_image']); ?>" class="w-full h-full object-cover" alt="Vehicle">
													<?php else: ?>
														<div class="w-full h-full flex items-center justify-center text-gray-300">
															<i class="fas fa-car text-lg"></i>
														</div>
													<?php endif; ?>
												</div>
												<div>
													<p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($vehicle['property_name']); ?></p>
													<?php if (!empty($vehicle['city'])): ?>
														<p class="text-[10px] text-gray-400 mt-0.5"><?php echo htmlspecialchars($vehicle['city']); ?></p>
													<?php endif; ?>
												</div>
											</div>
										</td>

										<!-- Owner -->
										<td class="px-6 py-4">
											<p class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($vehicle['first_name'] . ' ' . $vehicle['last_name']); ?></p>
										</td>

										<!-- Category -->
										<td class="px-6 py-4">
											<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-purple-50 text-purple-600 uppercase tracking-wide">
												<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $vehicle['vehicle_category'] ?? 'N/A'))); ?>
											</span>
										</td>

										<!-- District -->
										<td class="px-6 py-4 text-sm text-gray-600">
											<?php echo htmlspecialchars($vehicle['district'] ?? 'N/A'); ?>
										</td>

										<!-- Driver Option -->
										<td class="px-6 py-4">
											<?php
											$driver_option = $vehicle['driver_option'] ?? '';
											if ($driver_option === 'with_driver') {
												echo '<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-blue-50 text-blue-600 uppercase">With Driver</span>';
											} elseif ($driver_option === 'self_drive') {
												echo '<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-green-50 text-green-600 uppercase">Self Drive</span>';
											} elseif ($driver_option === 'both') {
												echo '<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-indigo-50 text-indigo-600 uppercase">Both</span>';
											} else {
												echo '<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-500 uppercase">N/A</span>';
											}
											?>
										</td>

										<!-- Price -->
										<td class="px-6 py-4 text-sm text-gray-700 font-semibold">
											<?php
											$pricing_type = $vehicle['pricing_type'] ?? '';
											if ($pricing_type === 'per_km') {
												echo 'LKR ' . number_format((float)($vehicle['price_per_km'] ?? 0), 2) . '/km';
											} else {
												echo 'LKR ' . number_format((float)($vehicle['price_per_day'] ?? 0), 2) . '/day';
											}
											?>
										</td>

										<!-- A/C Badge -->
										<td class="px-6 py-4">
											<?php if (!empty($vehicle['has_ac']) && $vehicle['has_ac']): ?>
												<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-emerald-50 text-emerald-600 uppercase"><i class="fas fa-snowflake mr-1"></i>A/C</span>
											<?php else: ?>
												<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-400 uppercase">No A/C</span>
											<?php endif; ?>
										</td>

										<!-- Created At -->
										<td class="px-6 py-4 text-xs text-gray-500">
											<?php echo date('M d, Y', strtotime($vehicle['created_at'])); ?>
										</td>

										<!-- Actions -->
										<td class="px-6 py-4">
											<div class="flex items-center justify-end gap-2">
												<a href="vehicle_details.php?id=<?php echo (int)$vehicle['id']; ?>" target="_blank"
												   class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all">
													<i class="fas fa-eye mr-1"></i>View
												</a>
												<form method="POST" onsubmit="return confirm('Delete this vehicle permanently?');">
													<input type="hidden" name="delete_id" value="<?php echo (int)$vehicle['id']; ?>">
													<button type="submit"
															class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-red-200 text-red-600 hover:bg-red-50 transition-all">
														<i class="fas fa-trash mr-1"></i>Delete
													</button>
												</form>
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
