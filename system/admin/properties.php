<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: ../../admin.php");
	exit();
}

$block_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_id'])) {
	$block_id = (int)$_POST['block_id'];
	if ($block_id > 0) {
		$stmt = $pdo->prepare("UPDATE properties SET allow_payout_requests = 0 WHERE id = ?");
		$stmt->execute([$block_id]);
		$block_message = 'Property blocked for payout requests.';
	}
}

$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$params = [];
$where = [];

$where[] = "p.business_type != 'vehicle'";

if ($search !== '') {
	$like = '%' . $search . '%';
	$where[] = "(p.property_name LIKE ? OR u.email LIKE ? OR p.contact_number LIKE ? OR p.mobile_telephone LIKE ? OR p.fixed_telephone LIKE ?)";
	$params = array_merge($params, [$like, $like, $like, $like, $like]);
}

$business_types = ['hotel', 'reception_hall', 'hostel', 'rest_hall'];
if ($type !== '' && in_array($type, $business_types, true)) {
	$where[] = "p.business_type = ?";
	$params[] = $type;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT p.id, p.property_name, p.contact_number, p.mobile_telephone, p.fixed_telephone,
			   p.allow_payout_requests, p.business_type, u.email AS owner_email
		FROM properties p
		JOIN users u ON p.owner_id = u.id
		$where_sql
		ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

function format_contact_number($row) {
	$contact = trim((string)($row['contact_number'] ?? ''));
	$mobile = trim((string)($row['mobile_telephone'] ?? ''));
	$fixed = trim((string)($row['fixed_telephone'] ?? ''));
	if ($contact !== '') {
		return $contact;
	}
	if ($mobile !== '') {
		return $mobile;
	}
	if ($fixed !== '') {
		return $fixed;
	}
	return 'N/A';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Properties - Bookingjaunt</title>
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
				<h1 class="text-2xl font-black text-[#003580]">Properties</h1>
				<p class="text-xs text-gray-500 font-bold uppercase tracking-widest hidden sm:block">All registered properties</p>
			</div>
		</header>

		<div class="p-4 lg:p-8 space-y-6">
			<?php if ($block_message !== ''): ?>
				<div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-xl text-sm">
					<?php echo htmlspecialchars($block_message); ?>
				</div>
			<?php endif; ?>

			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
					<div class="md:col-span-2">
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Search</label>
						<input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, owner email, contact number" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm focus:ring-2 focus:ring-[#006ce4] outline-none">
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Type</label>
						<select name="type" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm">
							<option value="">All Types</option>
							<?php foreach ($business_types as $item): ?>
								<option value="<?php echo $item; ?>" <?php echo $type === $item ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $item)); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="flex gap-3">
						<button class="px-5 py-2 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest">Apply</button>
						<a href="properties.php" class="px-5 py-2 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-widest">Reset</a>
					</div>
				</form>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
				<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
					<h2 class="font-bold text-[#003580]">Property Directory</h2>
					<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total <?php echo count($properties); ?></span>
				</div>

				<?php if (empty($properties)): ?>
					<div class="p-10 text-center">
						<div class="w-16 h-16 bg-blue-50 text-[#006ce4] rounded-full flex items-center justify-center mx-auto mb-4">
							<i class="fas fa-hotel text-2xl"></i>
						</div>
						<h3 class="text-lg font-bold text-gray-700">No properties found</h3>
						<p class="text-xs text-gray-400 mt-2">Once properties are added, they will appear here.</p>
					</div>
				<?php else: ?>
					<div class="overflow-x-auto">
						<table class="w-full text-left">
							<thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
								<tr>
									<th class="px-6 py-4">Property Name</th>
									<th class="px-6 py-4">Owner Email</th>
									<th class="px-6 py-4">Contact Number</th>
									<th class="px-6 py-4">Type</th>
									<th class="px-6 py-4">Status</th>
									<th class="px-6 py-4 text-right">Actions</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-100">
								<?php foreach ($properties as $property): ?>
									<tr class="hover:bg-gray-50 transition-colors">
										<td class="px-6 py-4 text-sm font-semibold text-gray-800">
											<?php echo htmlspecialchars($property['property_name']); ?>
										</td>
										<td class="px-6 py-4 text-sm text-gray-600">
											<?php echo htmlspecialchars($property['owner_email']); ?>
										</td>
										<td class="px-6 py-4 text-sm text-gray-600">
											<?php echo htmlspecialchars(format_contact_number($property)); ?>
										</td>
										<td class="px-6 py-4 text-sm text-gray-600">
											<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $property['business_type']))); ?>
										</td>
										<td class="px-6 py-4 text-sm text-gray-600">
											<?php echo (int)$property['allow_payout_requests'] === 1 ? 'Active' : 'Blocked'; ?>
										</td>
										<td class="px-6 py-4">
											<div class="flex items-center justify-end gap-2">
												<form method="POST" onsubmit="return confirm('Block this property for payouts?');">
													<input type="hidden" name="block_id" value="<?php echo (int)$property['id']; ?>">
													<button type="submit" class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-red-200 text-red-600 hover:bg-red-50">
														Block
													</button>
												</form>
												<a href="property_details.php?id=<?php echo (int)$property['id']; ?>" class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-gray-200 text-gray-700 hover:bg-gray-50">
													View
												</a>
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
