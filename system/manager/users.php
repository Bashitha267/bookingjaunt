<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['manager']);

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];

    if ($user_id > 0 && $action === 'edit') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone_number = trim($_POST['phone_number']);
        $whatsapp_number = trim($_POST['whatsapp_number']);
        
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, whatsapp_number = ? WHERE id = ?");
        if ($stmt->execute([$first_name, $last_name, $phone_number, $whatsapp_number, $user_id])) {
            $feedback = 'User details updated successfully.';
        } else {
            $feedback = 'Failed to update user details.';
        }
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
    <title>Users - Manager Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex min-h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen relative z-10">
        <header class="glass-header sticky top-0 z-40 px-4 lg:px-8 py-6 flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-white hover:bg-white/20 transition-all">
                <i class="fas fa-bars-staggered"></i>
            </button>
            <div class="flex items-center gap-4 flex-1">
                <div class="w-12 h-12 bg-sky-500/20 text-sky-400 rounded-xl flex items-center justify-center text-xl shadow-inner border border-sky-500/30">
                    <i class="fas fa-users"></i>
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-black text-white">Users Directory</h1>
                    <p class="text-xs text-sky-300 font-bold uppercase tracking-widest hidden sm:block">View and edit user details</p>
                </div>
            </div>
            <span class="text-[11px] font-bold uppercase tracking-widest text-white bg-sky-600 px-4 py-2 rounded-xl shadow-lg shadow-sky-900/50">
                Total: <?php echo count($users); ?>
            </span>
        </header>

        <div class="p-4 lg:p-8 space-y-6">
            <?php if ($feedback !== ''): ?>
                <div class="bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl font-bold">
                    <i class="fas fa-check-circle mr-2"></i><?php echo h($feedback); ?>
                </div>
            <?php endif; ?>

            <div class="glass-card p-6">
                <form method="GET" class="flex flex-col md:flex-row gap-4 md:items-end">
                    <div class="flex-1">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Search</label>
                        <input type="text" name="search" value="<?php echo h($search); ?>" placeholder="Email, name, or contact" class="mt-2 w-full px-4 py-2 custom-input">
                    </div>
                    <div class="flex gap-3">
                        <button class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold uppercase tracking-widest transition-all">Search</button>
                        <a href="users.php" class="px-5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold uppercase tracking-widest transition-all">Reset</a>
                    </div>
                </form>
            </div>

            <div class="glass-card overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                    <h2 class="font-bold text-white">Registered Users</h2>
                </div>

                <?php if (empty($users)): ?>
                    <div class="p-10 text-center">
                        <div class="w-16 h-16 bg-white/10 text-sky-300 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white">No users found</h3>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-[10px] font-bold text-sky-300 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Name</th>
                                    <th class="px-6 py-4">Email</th>
                                    <th class="px-6 py-4">Contact</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4">Created</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="px-6 py-4 text-sm font-semibold text-white">
                                            <?php echo h(trim($user['first_name'] . ' ' . $user['last_name'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-300"><?php echo h($user['email']); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-300">
                                            <i class="fas fa-phone text-[10px] mr-1 text-gray-500"></i> <?php echo h($user['phone_number'] ?: 'N/A'); ?><br>
                                            <i class="fab fa-whatsapp text-[10px] mr-1 text-green-500"></i> <?php echo h($user['whatsapp_number'] ?: 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-300 font-bold uppercase tracking-widest text-[10px]">
                                            <?php echo h($user['role']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-400"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td class="px-6 py-4 text-right">
                                            <button onclick='openEditModal(<?php echo json_encode($user); ?>)' class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 border border-sky-500/30 transition-all">
                                                Edit
                                            </button>
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

    <!-- Edit User Modal -->
    <div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeEditModal()"></div>
        <div class="bg-slate-800 border border-white/10 p-6 rounded-2xl shadow-2xl relative z-10 w-full max-w-md mx-4">
            <h3 class="text-xl font-bold text-white mb-4"><i class="fas fa-user-edit mr-2 text-sky-400"></i>Edit User Details</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="modalUserId" value="">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-sky-300 mb-2">First Name</label>
                        <input type="text" name="first_name" id="modalFirstName" required class="w-full px-4 py-2 custom-input outline-none focus:border-sky-400 border border-white/10">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-sky-300 mb-2">Last Name</label>
                        <input type="text" name="last_name" id="modalLastName" required class="w-full px-4 py-2 custom-input outline-none focus:border-sky-400 border border-white/10">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-sky-300 mb-2">Email Address (Read Only)</label>
                    <input type="text" id="modalEmail" readonly class="w-full px-4 py-2 custom-input opacity-50 outline-none border border-white/10">
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-sky-300 mb-2">Phone Number</label>
                        <input type="text" name="phone_number" id="modalPhone" class="w-full px-4 py-2 custom-input outline-none focus:border-sky-400 border border-white/10">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-sky-300 mb-2">WhatsApp</label>
                        <input type="text" name="whatsapp_number" id="modalWhatsapp" class="w-full px-4 py-2 custom-input outline-none focus:border-sky-400 border border-white/10">
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-lg bg-white/10 text-white text-sm font-bold hover:bg-white/20 transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-sm font-bold transition-all">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(user) {
            document.getElementById('modalUserId').value = user.id;
            document.getElementById('modalFirstName').value = user.first_name || '';
            document.getElementById('modalLastName').value = user.last_name || '';
            document.getElementById('modalEmail').value = user.email || '';
            document.getElementById('modalPhone').value = user.phone_number || '';
            document.getElementById('modalWhatsapp').value = user.whatsapp_number || '';
            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>
