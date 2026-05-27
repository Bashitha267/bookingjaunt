<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['admin']);

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // Generate Invite Link
        if ($action === 'generate_invite') {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO manager_invite_tokens (token, created_by) VALUES (?, ?)");
            if ($stmt->execute([$token, $_SESSION['user_id']])) {
                $invite_link = "http://" . $_SERVER['HTTP_HOST'] . "/bookingjaunt/manager_register.php?token=" . $token;
                $message = "Invite link generated successfully.";
            } else {
                $error = "Failed to generate invite link.";
            }
        }

        // Add Staff & Assign to Manager
        if ($action === 'add_staff') {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $manager_id = $_POST['manager_id'] !== '' ? $_POST['manager_id'] : null;

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

        // Delete/Block user
        if ($action === 'delete_user') {
            $user_id = $_POST['user_id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('manager', 'site_staff', 'staff')");
            if ($stmt->execute([$user_id])) {
                $message = "User deleted successfully.";
            } else {
                $error = "Failed to delete user.";
            }
        }
    }
}

// Fetch Managers
$stmtManagers = $pdo->query("SELECT * FROM users WHERE role = 'manager'");
$managers = $stmtManagers->fetchAll();

// Fetch Staff
$stmtStaff = $pdo->query("
    SELECT u.*, sm.manager_id, m.first_name as m_first_name, m.last_name as m_last_name 
    FROM users u 
    LEFT JOIN staff_manager sm ON u.id = sm.staff_id 
    LEFT JOIN users m ON sm.manager_id = m.id 
    WHERE u.role IN ('site_staff', 'staff')
");
$staff_members = $stmtStaff->fetchAll();

// Group staff by manager
$staff_by_manager = [];
$unassigned_staff = [];
foreach ($staff_members as $staff) {
    if ($staff['manager_id']) {
        $staff_by_manager[$staff['manager_id']][] = $staff;
    } else {
        $unassigned_staff[] = $staff;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management - Bookingjaunt Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Inherits the glassmorphism styles from sidebar.php -->
</head>
<body class="flex min-h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen relative z-10">
        <header class="sticky top-0 z-40 px-4 lg:px-8 py-4 flex justify-between items-center glass-card border-b">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 rounded-xl flex items-center justify-center text-white hover:bg-white/10 transition-all">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-white">Team Management</h1>
                    <p class="text-xs text-blue-300 font-medium hidden lg:block uppercase tracking-wider">Manage Managers & Staff</p>
                </div>
            </div>
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
            
            <?php if (isset($invite_link)): ?>
                <div class="bg-blue-500/20 text-blue-300 p-6 rounded-xl mb-6 border border-blue-500/30">
                    <h3 class="font-bold mb-2">Manager Invite Link Generated!</h3>
                    <div class="flex gap-2">
                        <input type="text" id="inviteLinkInput" value="<?php echo htmlspecialchars($invite_link); ?>" class="w-full bg-black/20 border border-white/20 rounded-lg px-4 py-2 text-white outline-none" readonly>
                        <button onclick="copyInviteLink()" class="bg-[#006ce4] hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-bold transition-all whitespace-nowrap">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <script>
                    function copyInviteLink() {
                        var copyText = document.getElementById("inviteLinkInput");
                        copyText.select();
                        copyText.setSelectionRange(0, 99999); 
                        navigator.clipboard.writeText(copyText.value);
                        alert("Copied the link: " + copyText.value);
                    }
                </script>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Actions Column -->
                <div class="space-y-6">
                    <!-- Generate Invite -->
                    <div class="bg-white p-6 rounded-2xl glass-card">
                        <h3 class="font-bold text-white mb-4">Invite Manager</h3>
                        <p class="text-xs text-gray-300 mb-4">Generate a unique, one-time link to invite a new manager.</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="generate_invite">
                            <button type="submit" class="w-full bg-[#006ce4] hover:bg-blue-600 text-white py-3 rounded-xl font-bold shadow-lg transition-all">
                                Generate Invite Link
                            </button>
                        </form>
                    </div>

                    <!-- Add Staff -->
                    <div class="bg-white p-6 rounded-2xl glass-card">
                        <h3 class="font-bold text-white mb-4">Add Site Staff</h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add_staff">
                            <div class="grid grid-cols-2 gap-4">
                                <input type="text" name="first_name" placeholder="First Name" required class="w-full px-4 py-2 bg-black/20 border border-white/20 rounded-lg text-white placeholder-gray-400 outline-none focus:border-blue-500">
                                <input type="text" name="last_name" placeholder="Last Name" required class="w-full px-4 py-2 bg-black/20 border border-white/20 rounded-lg text-white placeholder-gray-400 outline-none focus:border-blue-500">
                            </div>
                            <input type="email" name="email" placeholder="Email Address" required class="w-full px-4 py-2 bg-black/20 border border-white/20 rounded-lg text-white placeholder-gray-400 outline-none focus:border-blue-500">
                            <input type="password" name="password" placeholder="Password" required class="w-full px-4 py-2 bg-black/20 border border-white/20 rounded-lg text-white placeholder-gray-400 outline-none focus:border-blue-500">
                            
                            <select name="manager_id" class="w-full px-4 py-2 bg-black/20 border border-white/20 rounded-lg text-white outline-none focus:border-blue-500">
                                <option value="" class="text-black">No Manager (Direct Admin)</option>
                                <?php foreach ($managers as $mgr): ?>
                                    <option value="<?php echo $mgr['id']; ?>" class="text-black"><?php echo htmlspecialchars($mgr['first_name'] . ' ' . $mgr['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-3 rounded-xl font-bold shadow-lg transition-all">
                                Create Staff Account
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Hierarchy View Column -->
                <div class="lg:col-span-2">
                    <div class="bg-white p-6 rounded-2xl glass-card">
                        <h3 class="font-bold text-white mb-6 text-lg">Team Structure</h3>
                        
                        <!-- Unassigned Staff (Admin's direct reports) -->
                        <?php if (count($unassigned_staff) > 0): ?>
                            <div class="mb-8">
                                <h4 class="font-bold text-blue-300 mb-3 border-b border-white/10 pb-2">Admin's Direct Staff</h4>
                                <div class="space-y-3">
                                    <?php foreach ($unassigned_staff as $staff): ?>
                                        <div class="flex items-center justify-between bg-black/20 p-3 rounded-xl border border-white/5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-purple-500/20 text-purple-300 rounded-lg flex items-center justify-center font-bold">
                                                    S
                                                </div>
                                                <div>
                                                    <p class="font-bold text-white text-sm"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></p>
                                                    <p class="text-xs text-gray-400"><?php echo htmlspecialchars($staff['email']); ?></p>
                                                </div>
                                            </div>
                                            <form method="POST" onsubmit="return confirm('Delete this staff member?');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $staff['id']; ?>">
                                                <button class="text-red-400 hover:text-red-300 px-3 py-1 bg-red-500/10 rounded-lg text-xs font-bold transition-colors">Delete</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Managers and their staff -->
                        <div class="space-y-6">
                            <?php foreach ($managers as $mgr): ?>
                                <div class="bg-black/20 rounded-xl border border-white/10 p-4">
                                    <!-- Manager Row -->
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 bg-blue-500/20 text-blue-300 rounded-lg flex items-center justify-center font-bold text-xl shadow-inner border border-blue-500/30">
                                                M
                                            </div>
                                            <div>
                                                <p class="font-bold text-white text-base"><?php echo htmlspecialchars($mgr['first_name'] . ' ' . $mgr['last_name']); ?></p>
                                                <p class="text-xs text-blue-300 uppercase tracking-widest font-bold">Manager &bull; <?php echo htmlspecialchars($mgr['email']); ?></p>
                                            </div>
                                        </div>
                                        <form method="POST" onsubmit="return confirm('Delete this manager and orphan their staff?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $mgr['id']; ?>">
                                            <button class="text-red-400 hover:text-red-300 px-3 py-1 bg-red-500/10 rounded-lg text-xs font-bold transition-colors"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                    
                                    <!-- Staff List -->
                                    <div class="pl-8 space-y-2 border-l-2 border-white/10 ml-6">
                                        <?php if (isset($staff_by_manager[$mgr['id']])): ?>
                                            <?php foreach ($staff_by_manager[$mgr['id']] as $staff): ?>
                                                <div class="flex items-center justify-between bg-black/10 p-2 rounded-lg border border-white/5 relative before:absolute before:w-4 before:border-b-2 before:border-white/10 before:-left-6 before:top-1/2">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-8 h-8 bg-purple-500/20 text-purple-300 rounded-md flex items-center justify-center font-bold text-xs">
                                                            S
                                                        </div>
                                                        <div>
                                                            <p class="font-bold text-white text-xs"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></p>
                                                        </div>
                                                    </div>
                                                    <form method="POST" onsubmit="return confirm('Delete this staff member?');">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $staff['id']; ?>">
                                                        <button class="text-red-400 hover:text-red-300 px-2 py-1 bg-red-500/10 rounded text-[10px] font-bold transition-colors">Remove</button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-xs text-gray-500 italic relative before:absolute before:w-4 before:border-b-2 before:border-white/10 before:-left-6 before:top-1/2">No staff assigned</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
