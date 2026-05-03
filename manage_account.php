<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $whatsapp = trim($_POST['whatsapp_number']);
    $nic = trim($_POST['nic_passport']);
    $country = trim($_POST['country']);
    $address = trim($_POST['address']);

    // Simple validation
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_msg = "First name, Last name, and Email are required.";
    } else {
        try {
            $update = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ?, whatsapp_number = ?, nic_passport = ?, country = ?, address = ? WHERE id = ?");
            $update->execute([$first_name, $last_name, $email, $phone, $whatsapp, $nic, $country, $address, $user_id]);
            
            $_SESSION['user_name'] = $first_name . ' ' . $last_name; // Update session name
            $success_msg = "Profile updated successfully!";
            
            // Refresh user data
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $error_msg = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Handle Password Update
if (isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (!password_verify($current_pass, $user['password'])) {
        $error_msg = "Current password is incorrect.";
    } elseif ($new_pass !== $confirm_pass) {
        $error_msg = "New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error_msg = "New password must be at least 6 characters.";
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$hashed_pass, $user_id]);
        $success_msg = "Password changed successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Account - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: '#003580',
                        secondary: '#006ce4',
                        palm: '#008009',
                        gold: '#febb02',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f6f9; }
        .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="min-h-screen">
    <?php include 'navbar.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8 md:py-12">
        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl font-black font-display text-primary">Manage Account</h1>
            <p class="text-sm text-neutral-500 mt-1">Update your personal information and security settings</p>
        </div>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-xl mb-8 flex items-center gap-3">
                <i class="fas fa-check-circle text-xl"></i>
                <span class="font-bold"><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl mb-8 flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-xl"></i>
                <span class="font-bold"><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-8">
            <!-- Profile Section -->
            <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm overflow-hidden">
                <div class="p-6 md:p-8 border-b border-neutral-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary/10 text-primary rounded-2xl flex items-center justify-center text-xl">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-primary uppercase tracking-tight">Personal Information</h3>
                        <p class="text-xs text-neutral-400 font-bold">PUBLIC AND CONTACT DETAILS</p>
                    </div>
                </div>
                <form method="POST" class="p-6 md:p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary/10 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary/10 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary/10 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">Phone Number</label>
                            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary/10 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($user['whatsapp_number'] ?? ''); ?>" class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary/10 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">NIC / Passport</label>
                            <input type="text" name="nic_passport" value="<?php echo htmlspecialchars($user['nic_passport'] ?? ''); ?>" class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary/10 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">Country</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>" class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary/10 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled class="w-full px-4 py-3 bg-neutral-100 border border-neutral-200 rounded-2xl text-sm text-neutral-400 cursor-not-allowed">
                            <p class="text-[9px] text-neutral-400 mt-1 font-bold">USERNAME CANNOT BE CHANGED</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">Residential Address</label>
                            <textarea name="address" rows="3" class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary/10 transition-all resize-none"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="mt-8">
                        <button type="submit" name="update_profile" class="bg-primary text-white px-8 py-3.5 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-opacity-90 transition-all shadow-lg shadow-primary/20">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Password Section -->
            <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm overflow-hidden mb-12">
                <div class="p-6 md:p-8 border-b border-neutral-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center text-xl">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-primary uppercase tracking-tight">Security & Password</h3>
                        <p class="text-xs text-neutral-400 font-bold">PROTECT YOUR ACCOUNT</p>
                    </div>
                </div>
                <form method="POST" class="p-6 md:p-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">Current Password</label>
                            <input type="password" name="current_password" required class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-red-500/10 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">New Password</label>
                            <input type="password" name="new_password" required class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-red-500/10 transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" required class="w-full px-4 py-3 bg-neutral-50 border border-neutral-100 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-red-500/10 transition-all">
                        </div>
                    </div>
                    <div class="mt-8">
                        <button type="submit" name="update_password" class="bg-neutral-800 text-white px-8 py-3.5 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-black transition-all">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-neutral-200 py-10 no-print">
        <div class="max-w-[1400px] mx-auto px-4 text-center">
            <p class="text-neutral-400 text-sm font-medium">© <?php echo date('Y'); ?> Bookingjaunt. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
