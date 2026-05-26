<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: ../../admin.php");
	exit();
}

$success_msg = '';
$error_msg = '';

$package_type_options = [
	'sidebar_ad' => 'Sidebar Vertical Unit',
	'horizontal_strip_ad' => 'Results Horizontal Strip',
	'mobile_scroll_ad' => 'Mobile Scroll Unit'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
	$ad_id = (int) ($_POST['ad_id'] ?? 0);
	$new_status = $_POST['new_status'] ?? '';

	if ($ad_id > 0 && in_array($new_status, ['active', 'inactive'], true)) {
		$stmt = $pdo->prepare("UPDATE advertisements SET status = ? WHERE id = ?");
		$stmt->execute([$new_status, $ad_id]);
		$success_msg = "Advertisement status updated.";
	} else {
		$error_msg = "Invalid request. Please try again.";
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['edit_package', 'create_package'], true)) {
	$package_name = trim($_POST['package_name'] ?? '');
	$package_type = $_POST['package_type'] ?? '';
	$duration_days = (int) ($_POST['duration_days'] ?? 0);
	$price = (float) ($_POST['price'] ?? 0);
	$is_active = isset($_POST['is_active']) ? 1 : 0;

	if ($package_name === '' || !isset($package_type_options[$package_type]) || $duration_days < 1 || $price < 0) {
		$error_msg = "Please enter valid package details.";
	} else {
		try {
			if ($_POST['action'] === 'create_package') {
				$stmt = $pdo->prepare("INSERT INTO advertisement_packages (package_name, package_type, price, duration_days, is_active) VALUES (?, ?, ?, ?, ?)");
				$stmt->execute([$package_name, $package_type, $price, $duration_days, $is_active]);
				$success_msg = "Advertisement package created.";
			} else {
				$package_id = (int) ($_POST['package_id'] ?? 0);
				if ($package_id > 0) {
					$stmt = $pdo->prepare("UPDATE advertisement_packages SET package_name = ?, package_type = ?, price = ?, duration_days = ?, is_active = ? WHERE id = ?");
					$stmt->execute([$package_name, $package_type, $price, $duration_days, $is_active, $package_id]);
					$success_msg = "Advertisement package updated.";
				} else {
					$error_msg = "Invalid package selected.";
				}
			}
		} catch (PDOException $e) {
			$error_msg = "Unable to save package details.";
		}
	}
}

$ads = [];
try {
	$stmt = $pdo->query("SELECT a.*, u.first_name, u.last_name FROM advertisements a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
	$ads = $stmt->fetchAll();
} catch (PDOException $e) {
	$error_msg = "Unable to load advertisements.";
}

$ad_packages = [];
try {
	$stmt = $pdo->query("SELECT * FROM advertisement_packages ORDER BY created_at DESC");
	$ad_packages = $stmt->fetchAll();
} catch (PDOException $e) {
	// Graceful fail if table not ready
}

$current_ads = [];
$history_ads = [];
$today = new DateTime('today');

foreach ($ads as $ad) {
	$end_date = null;
	if (!empty($ad['created_at']) && !empty($ad['package_duration_days'])) {
		$end_date = (new DateTime($ad['created_at']))->modify('+' . (int) $ad['package_duration_days'] . ' days');
	}

	$is_expired = $end_date ? $end_date < $today : false;

	if ($ad['status'] === 'active' && !$is_expired) {
		$current_ads[] = $ad + ['end_date' => $end_date, 'is_expired' => $is_expired];
	} else {
		$history_ads[] = $ad + ['end_date' => $end_date, 'is_expired' => $is_expired];
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Manage Advertisements - Bookingjaunt</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
	</style>
</head>
<body class="flex min-h-screen overflow-hidden">
	<?php include 'sidebar.php'; ?>

	<main class="flex-1 ml-0 lg:ml-64 p-6 lg:p-10 overflow-y-auto">
		<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
			<div>
				<h1 class="text-3xl font-black text-slate-900">Manage Advertisements</h1>
				<p class="text-sm text-slate-500 font-semibold mt-1">Monitor active campaigns and review historical placements.</p>
			</div>
			<div class="flex items-center gap-3 text-xs font-bold text-slate-500">
				<span class="px-3 py-1 rounded-full bg-white border border-slate-200">Active: <?php echo count($current_ads); ?></span>
				<span class="px-3 py-1 rounded-full bg-white border border-slate-200">History: <?php echo count($history_ads); ?></span>
			</div>
		</div>

		<?php if ($success_msg): ?>
			<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
				<i class="fas fa-check-circle"></i>
				<span class="font-semibold text-sm"><?php echo htmlspecialchars($success_msg); ?></span>
			</div>
		<?php endif; ?>

		<?php if ($error_msg): ?>
			<div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
				<i class="fas fa-exclamation-circle"></i>
				<span class="font-semibold text-sm"><?php echo htmlspecialchars($error_msg); ?></span>
			</div>
		<?php endif; ?>

		<section class="mb-12">
			<div class="flex items-center justify-between mb-4">
				<h2 class="text-xl font-black text-slate-900">Advertisement Packages</h2>
				<span class="text-xs font-bold text-slate-500">Create and manage placements</span>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
				<form method="POST" class="bg-white border border-dashed border-slate-200 rounded-2xl p-6">
					<input type="hidden" name="action" value="create_package">
					<div class="text-xs font-bold text-slate-400 uppercase tracking-widest">New Package</div>
					<div class="mt-4 space-y-4">
						<div>
							<label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Package Name</label>
							<input type="text" name="package_name" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none focus:border-[#003580]">
						</div>
						<div>
							<label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Package Type</label>
							<select name="package_type" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none focus:border-[#003580]">
								<?php foreach ($package_type_options as $value => $label): ?>
									<option value="<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="grid grid-cols-2 gap-4">
							<div>
								<label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Duration (Days)</label>
								<input type="number" name="duration_days" min="1" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none focus:border-[#003580]">
							</div>
							<div>
								<label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Price (LKR)</label>
								<input type="number" name="price" min="0" step="0.01" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none focus:border-[#003580]">
							</div>
						</div>
						<div class="flex items-center gap-2">
							<input type="checkbox" name="is_active" checked class="w-4 h-4 text-[#003580]">
							<label class="text-sm font-bold text-slate-600">Active</label>
						</div>
					</div>
					<div class="mt-5 text-right">
						<button type="submit" class="px-4 py-2 rounded-lg bg-[#003580] text-white text-xs font-bold uppercase tracking-widest hover:bg-[#002560]">Create Package</button>
					</div>
				</form>

				<?php if (empty($ad_packages)): ?>
					<div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
						<i class="fas fa-box-open text-4xl text-slate-200 mb-3"></i>
						<p class="text-slate-500 font-semibold">No advertisement packages yet.</p>
					</div>
				<?php else: ?>
					<?php foreach ($ad_packages as $pkg): ?>
						<form method="POST" class="bg-white border border-slate-200 rounded-2xl p-6">
							<input type="hidden" name="action" value="edit_package">
							<input type="hidden" name="package_id" value="<?php echo (int) $pkg['id']; ?>">
							<div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Package #<?php echo (int) $pkg['id']; ?></div>
							<div class="mt-4 space-y-4">
								<div>
									<label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Package Name</label>
									<input type="text" name="package_name" value="<?php echo htmlspecialchars($pkg['package_name']); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none focus:border-[#003580]">
								</div>
								<div>
									<label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Package Type</label>
									<select name="package_type" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none focus:border-[#003580]">
										<?php foreach ($package_type_options as $value => $label): ?>
											<option value="<?php echo $value; ?>" <?php echo $pkg['package_type'] === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="grid grid-cols-2 gap-4">
									<div>
										<label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Duration (Days)</label>
										<input type="number" name="duration_days" min="1" value="<?php echo (int) $pkg['duration_days']; ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none focus:border-[#003580]">
									</div>
									<div>
										<label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">Price (LKR)</label>
										<input type="number" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($pkg['price']); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none focus:border-[#003580]">
									</div>
								</div>
								<div class="flex items-center gap-2">
									<input type="checkbox" name="is_active" <?php echo (int) $pkg['is_active'] === 1 ? 'checked' : ''; ?> class="w-4 h-4 text-[#003580]">
									<label class="text-sm font-bold text-slate-600">Active</label>
								</div>
							</div>
							<div class="mt-5 text-right">
								<button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-bold uppercase tracking-widest hover:bg-slate-800">Update Package</button>
							</div>
						</form>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="mb-12">
			<div class="flex items-center justify-between mb-4">
				<h2 class="text-xl font-black text-slate-900">Current Campaigns</h2>
				<span class="text-xs font-bold text-emerald-600">Live placements</span>
			</div>

			<?php if (empty($current_ads)): ?>
				<div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
					<i class="fas fa-bullhorn text-4xl text-slate-200 mb-3"></i>
					<p class="text-slate-500 font-semibold">No active advertisements.</p>
				</div>
			<?php else: ?>
				<div class="overflow-hidden border border-slate-200 bg-white rounded-2xl">
					<table class="min-w-full text-sm">
						<thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-widest">
							<tr>
								<th class="text-left px-6 py-4">Ad</th>
								<th class="text-left px-6 py-4">Owner</th>
								<th class="text-left px-6 py-4">Placement</th>
								<th class="text-left px-6 py-4">Price</th>
								<th class="text-left px-6 py-4">End Date</th>
								<th class="text-right px-6 py-4">Action</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-100">
							<?php foreach ($current_ads as $ad): ?>
								<tr class="hover:bg-slate-50/60">
									<td class="px-6 py-4">
										<div class="flex items-center gap-3">
											<div class="w-16 h-12 bg-slate-100 rounded-xl overflow-hidden flex items-center justify-center">
												<?php if (!empty($ad['image_path'])): ?>
													<img src="../../<?php echo htmlspecialchars($ad['image_path']); ?>" class="w-full h-full object-cover" alt="Ad image">
												<?php else: ?>
													<i class="fas fa-image text-slate-300"></i>
												<?php endif; ?>
											</div>
											<div>
												<div class="font-bold text-slate-900"><?php echo htmlspecialchars($ad['ad_title'] ?: $ad['owner_name']); ?></div>
												<div class="text-xs text-slate-400 font-semibold">#<?php echo (int) $ad['id']; ?></div>
											</div>
										</div>
									</td>
									<td class="px-6 py-4">
										<div class="font-semibold text-slate-700"><?php echo htmlspecialchars(trim(($ad['first_name'] ?? '') . ' ' . ($ad['last_name'] ?? '')) ?: 'Guest'); ?></div>
										<div class="text-xs text-slate-400 font-semibold"><?php echo htmlspecialchars($ad['owner_name'] ?? ''); ?></div>
									</td>
									<td class="px-6 py-4">
										<div class="font-semibold text-slate-700"><?php echo htmlspecialchars($ad['package_name'] ?? 'Custom'); ?></div>
										<div class="text-xs text-slate-400 font-semibold"><?php echo htmlspecialchars($ad['package_type'] ?? ''); ?></div>
									</td>
									<td class="px-6 py-4 font-semibold text-slate-700">LKR <?php echo number_format((float) ($ad['price'] ?? 0), 2); ?></td>
									<td class="px-6 py-4">
										<?php if (!empty($ad['end_date'])): ?>
											<div class="font-semibold text-slate-700"><?php echo $ad['end_date']->format('M d, Y'); ?></div>
										<?php else: ?>
											<div class="text-slate-400 text-xs font-semibold">Not set</div>
										<?php endif; ?>
									</td>
									<td class="px-6 py-4 text-right">
										<form method="POST" class="inline">
											<input type="hidden" name="action" value="toggle_status">
											<input type="hidden" name="ad_id" value="<?php echo (int) $ad['id']; ?>">
											<input type="hidden" name="new_status" value="inactive">
											<button type="submit" class="px-4 py-2 rounded-lg bg-rose-50 text-rose-600 text-xs font-bold hover:bg-rose-100">Deactivate</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>

		<section>
			<div class="flex items-center justify-between mb-4">
				<h2 class="text-xl font-black text-slate-900">History</h2>
				<span class="text-xs font-bold text-slate-500">Inactive or expired</span>
			</div>

			<?php if (empty($history_ads)): ?>
				<div class="bg-white border border-slate-200 rounded-2xl p-10 text-center">
					<i class="fas fa-clock text-4xl text-slate-200 mb-3"></i>
					<p class="text-slate-500 font-semibold">No historical advertisements.</p>
				</div>
			<?php else: ?>
				<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
					<?php foreach ($history_ads as $ad): ?>
						<div class="bg-white border border-slate-200 rounded-2xl p-6">
							<div class="flex items-start justify-between gap-4">
								<div>
									<p class="text-xs font-bold text-slate-400 uppercase tracking-widest">#<?php echo (int) $ad['id']; ?></p>
									<h3 class="text-lg font-black text-slate-900 mt-1"><?php echo htmlspecialchars($ad['ad_title'] ?: $ad['owner_name']); ?></h3>
									<p class="text-sm text-slate-500 font-semibold mt-2"><?php echo htmlspecialchars($ad['package_name'] ?? 'Custom'); ?></p>
								</div>
								<span class="text-xs font-bold px-3 py-1 rounded-full <?php echo $ad['status'] === 'inactive' ? 'bg-slate-100 text-slate-500' : 'bg-amber-100 text-amber-600'; ?>">
									<?php echo $ad['status'] === 'inactive' ? 'Inactive' : 'Expired'; ?>
								</span>
							</div>
							<div class="grid grid-cols-2 gap-4 mt-4 text-xs font-semibold text-slate-500">
								<div>
									<div class="uppercase tracking-widest">Owner</div>
									<div class="text-slate-700 mt-1"><?php echo htmlspecialchars(trim(($ad['first_name'] ?? '') . ' ' . ($ad['last_name'] ?? '')) ?: 'Guest'); ?></div>
								</div>
								<div>
									<div class="uppercase tracking-widest">Placed</div>
									<div class="text-slate-700 mt-1"><?php echo date('M d, Y', strtotime($ad['created_at'])); ?></div>
								</div>
								<div>
									<div class="uppercase tracking-widest">Price</div>
									<div class="text-slate-700 mt-1">LKR <?php echo number_format((float) ($ad['price'] ?? 0), 2); ?></div>
								</div>
								<div>
									<div class="uppercase tracking-widest">End Date</div>
									<div class="text-slate-700 mt-1"><?php echo !empty($ad['end_date']) ? $ad['end_date']->format('M d, Y') : 'Not set'; ?></div>
								</div>
							</div>
							<div class="mt-4 flex items-center justify-between">
								<span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest"><?php echo htmlspecialchars($ad['package_type'] ?? ''); ?></span>
								<?php if ($ad['status'] === 'inactive'): ?>
									<form method="POST">
										<input type="hidden" name="action" value="toggle_status">
										<input type="hidden" name="ad_id" value="<?php echo (int) $ad['id']; ?>">
										<input type="hidden" name="new_status" value="active">
										<button type="submit" class="px-4 py-2 rounded-lg bg-emerald-50 text-emerald-600 text-xs font-bold hover:bg-emerald-100">Reactivate</button>
									</form>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	</main>
</body>
</html>
