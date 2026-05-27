<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['site_staff']);

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Change Password
        if ($_POST['action'] === 'change_password') {
            $current = $_POST['current_password'];
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];
            
            if ($new !== $confirm) {
                $error_msg = "New passwords do not match.";
            } else {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($current, $user['password'])) {
                    $hash = password_hash($new, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hash, $_SESSION['user_id']]);
                    $success_msg = "Password changed successfully.";
                } else {
                    $error_msg = "Incorrect current password.";
                }
            }
        }
        
        // Change Background
        if ($_POST['action'] === 'change_background') {
            if (isset($_FILES['background']) && $_FILES['background']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/manager_bg/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $tmp_name = $_FILES['background']['tmp_name'];
                $name = basename($_FILES['background']['name']);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allowed)) {
                    $new_name = 'bg_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                    $dest = $upload_dir . $new_name;
                    
                    if (move_uploaded_file($tmp_name, $dest)) {
                        $db_path = 'uploads/manager_bg/' . $new_name;
                        
                        // Check if manager_bg column exists, if not add it (robustness)
                        try {
                            $stmt = $pdo->prepare("UPDATE users SET manager_bg = ? WHERE id = ?");
                            $stmt->execute([$db_path, $_SESSION['user_id']]);
                            $success_msg = "Background updated successfully.";
                        } catch(PDOException $e) {
                            $pdo->exec("ALTER TABLE users ADD COLUMN manager_bg VARCHAR(255) NULL");
                            $stmt = $pdo->prepare("UPDATE users SET manager_bg = ? WHERE id = ?");
                            $stmt->execute([$db_path, $_SESSION['user_id']]);
                            $success_msg = "Background updated successfully.";
                        }
                    } else {
                        $error_msg = "Failed to upload image.";
                    }
                } else {
                    $error_msg = "Invalid file type. Only JPG, PNG, and WEBP are allowed.";
                }
            } else {
                $error_msg = "Please select an image file.";
            }
        }
    }
}

// Get current background
$current_bg = '';
try {
    $stmt = $pdo->prepare("SELECT manager_bg FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_bg = $stmt->fetchColumn();
} catch(PDOException $e) {
    // Column might not exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Manager Console</title>
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
                <div class="w-12 h-12 bg-slate-500/20 text-slate-300 rounded-xl flex items-center justify-center text-xl shadow-inner border border-slate-500/30">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-black text-white">Account Settings</h1>
                    <p class="text-xs text-sky-300 font-bold uppercase tracking-widest hidden sm:block">Manage your console preferences</p>
                </div>
            </div>
        </header>

        <div class="p-4 lg:p-8 max-w-4xl mx-auto space-y-6">
            <?php if ($success_msg): ?>
                <div class="bg-emerald-500/20 text-emerald-300 p-4 rounded-xl font-bold border border-emerald-500/30">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="bg-red-500/20 text-red-300 p-4 rounded-xl font-bold border border-red-500/30">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Change Background -->
                <div class="glass-card p-6">
                    <h3 class="font-bold text-white mb-4"><i class="fas fa-image mr-2 text-sky-400"></i>Console Background</h3>
                    <p class="text-xs text-sky-300/70 mb-4">Personalize your manager console with a custom background image.</p>
                    
                    <?php if ($current_bg): ?>
                        <div class="mb-4 rounded-xl overflow-hidden h-32 border border-white/20 relative group">
                            <img src="../../<?php echo htmlspecialchars($current_bg); ?>" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-white text-xs font-bold uppercase tracking-widest">Current Background</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="change_background">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-sky-300 mb-2">Upload New Image</label>
                            <input type="file" name="background" accept="image/*" required class="w-full text-sm text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-sky-500/20 file:text-sky-300 hover:file:bg-sky-500/30 border border-dashed border-white/20 p-4 rounded-xl bg-black/20">
                        </div>
                        <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 text-white py-3 rounded-xl font-bold transition-all mt-2">
                            Update Background
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="glass-card p-6 h-fit">
                    <h3 class="font-bold text-white mb-4"><i class="fas fa-lock mr-2 text-sky-400"></i>Change Password</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="change_password">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Current Password</label>
                            <input type="password" name="current_password" required class="w-full px-4 py-2 custom-input">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">New Password</label>
                            <input type="password" name="new_password" required class="w-full px-4 py-2 custom-input">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Confirm New Password</label>
                            <input type="password" name="confirm_password" required class="w-full px-4 py-2 custom-input">
                        </div>
                        <button type="submit" class="w-full bg-slate-600 hover:bg-slate-500 text-white py-3 rounded-xl font-bold transition-all mt-4">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
