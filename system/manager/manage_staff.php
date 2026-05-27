<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['manager']);

$message = '';
$error = '';
$manager_id = $_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // Add Staff
        if ($action === 'add_staff') {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
                $error = "All fields are required to add staff.";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email already exists.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'site_staff')");
                    if ($stmt->execute([$first_name, $last_name, $email, $hashed_password])) {
                        $staff_id = $pdo->lastInsertId();
                        $stmt = $pdo->prepare("INSERT INTO staff_manager (staff_id, manager_id) VALUES (?, ?)");
                        $stmt->execute([$staff_id, $manager_id]);
                        $message = "Staff added successfully.";
                    } else {
                        $error = "Failed to add staff.";
                    }
                }
            }
        }

        // Delete user
        if ($action === 'delete_user') {
            $user_id = (int)$_POST['user_id'];
            // Verify this staff belongs to this manager
            $stmt = $pdo->prepare("SELECT id FROM staff_manager WHERE staff_id = ? AND manager_id = ?");
            $stmt->execute([$user_id, $manager_id]);
            if ($stmt->fetch()) {
                $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($stmtDel->execute([$user_id])) {
                    $message = "Staff member removed successfully.";
                } else {
                    $error = "Failed to remove staff.";
                }
            } else {
                $error = "Unauthorized to remove this user.";
            }
        }
    }
}

// Fetch Staff for this manager
$stmtStaff = $pdo->prepare("
    SELECT u.*
    FROM users u 
    JOIN staff_manager sm ON u.id = sm.staff_id 
    WHERE sm.manager_id = ?
");
$stmtStaff->execute([$manager_id]);
$staff_members = $stmtStaff->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - Manager Console</title>
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
                <div class="w-12 h-12 bg-emerald-500/20 text-emerald-400 rounded-xl flex items-center justify-center text-xl shadow-inner border border-emerald-500/30">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-black text-white">My Staff</h1>
                    <p class="text-xs text-sky-300 font-bold uppercase tracking-widest hidden sm:block">Manage your team</p>
                </div>
            </div>
            <span class="text-[11px] font-bold uppercase tracking-widest text-white bg-emerald-600 px-4 py-2 rounded-xl shadow-lg shadow-emerald-900/50">
                Staff: <?php echo count($staff_members); ?>
            </span>
        </header>

        <div class="p-4 lg:p-8">
            <?php if ($message): ?>
                <div class="bg-emerald-500/20 text-emerald-300 p-4 rounded-xl mb-6 font-bold border border-emerald-500/30">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-500/20 text-red-300 p-4 rounded-xl mb-6 font-bold border border-red-500/30">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add Staff -->
                <div class="bg-white/5 p-6 rounded-2xl border border-white/10 glass-card h-fit">
                    <h3 class="font-bold text-white mb-4"><i class="fas fa-user-plus mr-2 text-emerald-400"></i>Add New Staff</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_staff">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">First Name</label>
                                <input type="text" name="first_name" required class="w-full px-4 py-2 custom-input">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Last Name</label>
                                <input type="text" name="last_name" required class="w-full px-4 py-2 custom-input">
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Email Address</label>
                            <input type="email" name="email" required class="w-full px-4 py-2 custom-input">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Temporary Password</label>
                            <input type="password" name="password" required class="w-full px-4 py-2 custom-input">
                        </div>
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-3 rounded-xl font-bold shadow-lg transition-all mt-4">
                            Create Staff Account
                        </button>
                    </form>
                </div>

                <!-- Staff List -->
                <div class="lg:col-span-2">
                    <div class="glass-card overflow-hidden h-full">
                        <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                            <h2 class="font-bold text-white">Your Team Members</h2>
                        </div>
                        
                        <?php if (count($staff_members) > 0): ?>
                            <div class="p-6 space-y-3">
                                <?php foreach ($staff_members as $staff): ?>
                                    <div class="flex items-center justify-between bg-white/5 p-4 rounded-xl border border-white/10 hover:bg-white/10 transition-colors">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-emerald-500/20 text-emerald-300 rounded-lg flex items-center justify-center font-bold text-lg border border-emerald-500/30">
                                                S
                                            </div>
                                            <div>
                                                <p class="font-bold text-white"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></p>
                                                <p class="text-xs text-sky-300"><?php echo htmlspecialchars($staff['email']); ?></p>
                                                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider">Added: <?php echo date('M d, Y', strtotime($staff['created_at'])); ?></p>
                                            </div>
                                        </div>
                                        <form method="POST" onsubmit="return confirm('Remove this staff member permanently?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $staff['id']; ?>">
                                            <button class="text-red-400 hover:text-red-300 px-4 py-2 bg-red-500/10 hover:bg-red-500/20 rounded-lg text-xs font-bold transition-all border border-red-500/20"><i class="fas fa-user-minus mr-2"></i>Remove</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-12 text-center">
                                <div class="w-16 h-16 bg-white/10 text-sky-300 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-users text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-bold text-white">No staff assigned</h3>
                                <p class="text-xs text-sky-300/70 mt-2">Add staff using the form to build your team.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
