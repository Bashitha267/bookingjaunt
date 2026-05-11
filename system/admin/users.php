<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: ../../admin.php");
	exit();
}

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
	$user_id = (int)$_POST['user_id'];
	$action = $_POST['action'];

	if ($user_id > 0 && $action === 'disable') {
		$stmt = $pdo->prepare("UPDATE users SET is_disabled = 1 WHERE id = ?");
		$stmt->execute([$user_id]);
		$feedback = 'User disabled successfully.';
	}

	if ($user_id > 0 && $action === 'enable') {
		$stmt = $pdo->prepare("UPDATE users SET is_disabled = 0 WHERE id = ?");
		$stmt->execute([$user_id]);
		$feedback = 'User enabled successfully.';
	}

	if ($user_id > 0 && $action === 'remove') {
		$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
		$stmt->execute([$user_id]);
		$feedback = 'User removed successfully.';
	}
}

$search = trim($_GET['search'] ?? '');
$params = [];
$where_sql = '';

if ($search !== '') {
	$like = '%' . $search . '%';
	$where_sql = "WHERE (email LIKE ? OR phone_number LIKE ? OR whatsapp_number LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?)";
	$params = [$like, $like, $like, $like];
}

$sql = "SELECT id, first_name, last_name, email, phone_number, whatsapp_number, nic_passport, role, created_at, is_disabled
		FROM users
		$where_sql
		ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

function h($value) {
	return htmlspecialchars((string)$value);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Users - Bookingjaunt</title>
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
				<h1 class="text-2xl font-black text-[#003580]">Users</h1>
				<p class="text-xs text-gray-500 font-bold uppercase tracking-widest hidden sm:block">All registered users</p>
			</div>
		</header>

		<div class="p-4 lg:p-8 space-y-6">
			<?php if ($feedback !== ''): ?>
				<div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-xl text-sm">
					<?php echo h($feedback); ?>
				</div>
			<?php endif; ?>

			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<form method="GET" class="flex flex-col md:flex-row gap-4 md:items-end">
					<div class="flex-1">
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Search</label>
						<input type="text" name="search" value="<?php echo h($search); ?>" placeholder="Email, name, or contact" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm focus:ring-2 focus:ring-[#006ce4] outline-none">
					</div>
					<div class="flex gap-3">
						<button class="px-5 py-2 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest">Search</button>
						<a href="users.php" class="px-5 py-2 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-widest">Reset</a>
					</div>
				</form>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
				<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
					<h2 class="font-bold text-[#003580]">User Directory</h2>
					<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total <?php echo count($users); ?></span>
				</div>

				<?php if (empty($users)): ?>
					<div class="p-10 text-center">
						<div class="w-16 h-16 bg-blue-50 text-[#006ce4] rounded-full flex items-center justify-center mx-auto mb-4">
							<i class="fas fa-users text-2xl"></i>
						</div>
						<h3 class="text-lg font-bold text-gray-700">No users found</h3>
						<p class="text-xs text-gray-400 mt-2">Try adjusting your search to see more results.</p>
					</div>
				<?php else: ?>
					<div class="overflow-x-auto">
						<table class="w-full text-left">
							<thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
								<tr>
									<th class="px-6 py-4">Name</th>
									<th class="px-6 py-4">Email</th>
									<th class="px-6 py-4">Contact</th>
									<th class="px-6 py-4">Role</th>
									<th class="px-6 py-4">NIC/Passport</th>
									<th class="px-6 py-4">Created</th>
									<th class="px-6 py-4 text-right">Actions</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-100">
								<?php foreach ($users as $user): ?>
									<tr class="hover:bg-gray-50 transition-colors">
										<td class="px-6 py-4 text-sm font-semibold text-gray-800">
											<?php echo h(trim($user['first_name'] . ' ' . $user['last_name'])); ?>
										</td>
										<td class="px-6 py-4 text-sm text-gray-600"><?php echo h($user['email']); ?></td>
										<td class="px-6 py-4 text-sm text-gray-600"><?php echo h($user['phone_number'] ?: $user['whatsapp_number'] ?: 'N/A'); ?></td>
										<td class="px-6 py-4 text-sm text-gray-600"><?php echo h($user['role']); ?></td>
										<td class="px-6 py-4 text-sm text-gray-600"><?php echo h($user['nic_passport'] ?: 'N/A'); ?></td>
										<td class="px-6 py-4 text-sm text-gray-600"><?php echo h($user['created_at']); ?></td>
										<td class="px-6 py-4">
											<div class="flex items-center justify-end gap-2">
												<?php if ((int)$user['is_disabled'] === 1): ?>
													<form method="POST" onsubmit="return confirm('Enable this user?');">
														<input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
														<input type="hidden" name="action" value="enable">
														<button type="submit" class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-emerald-200 text-emerald-700 hover:bg-emerald-50">
															Enable
														</button>
													</form>
												<?php else: ?>
													<form method="POST" onsubmit="return confirm('Disable this user?');">
														<input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
														<input type="hidden" name="action" value="disable">
														<button type="submit" class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-amber-200 text-amber-700 hover:bg-amber-50">
															Disable
														</button>
													</form>
												<?php endif; ?>
												<form method="POST" onsubmit="return confirm('Remove this user? This will delete related records.');">
													<input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
													<input type="hidden" name="action" value="remove">
													<button type="submit" class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-red-200 text-red-600 hover:bg-red-50">
														Remove
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
