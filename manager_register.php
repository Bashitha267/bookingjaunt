<?php
require_once 'config.php';
session_start();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Invalid or missing invitation token.");
}

// Validate token
$stmt = $pdo->prepare("SELECT * FROM manager_invite_tokens WHERE token = ? AND used_by IS NULL AND (expires_at IS NULL OR expires_at > NOW())");
$stmt->execute([$token]);
$invite = $stmt->fetch();

if (!$invite) {
    die("This invitation token is invalid or has already been used.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email address is already registered.";
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone_number, password, role) VALUES (?, ?, ?, ?, ?, 'manager')");
            if ($stmt->execute([$first_name, $last_name, $email, $phone_number, $hashed_password])) {
                $new_user_id = $pdo->lastInsertId();

                // Mark token as used
                $stmt = $pdo->prepare("UPDATE manager_invite_tokens SET used_by = ?, used_at = NOW() WHERE id = ?");
                $stmt->execute([$new_user_id, $invite['id']]);

                // Auto login
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['role'] = 'manager';

                header("Location: system/manager/dashboard.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Registration - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(rgba(0, 15, 40, 0.4), rgba(0, 15, 40, 0.8)), url('assets/login-bg.png') no-repeat center center;
            background-size: cover;
        }

        .blue-gradient { background: linear-gradient(135deg, #006ce4 0%, #003580 100%); }
        
        .glass-container {
            background: rgba(0, 20, 50, 0.6);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .custom-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }

        .custom-input:focus-within {
            background: rgba(255, 255, 255, 0.08);
            border-color: #006ce4;
            box-shadow: 0 0 0 4px rgba(0, 108, 228, 0.15);
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-6">

    <div class="glass-container max-w-lg w-full p-8 rounded-[2rem]">
        <div class="flex flex-col items-center mb-6 text-center">
            <h1 class="text-2xl font-extrabold text-white tracking-tighter mb-1" style="font-family: 'Playfair Display', serif;">Bookingjaunt</h1>
            <p class="text-blue-300 text-[10px] font-bold tracking-[0.2em] uppercase">Manager Registration</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/20 text-red-200 p-3 rounded-lg mb-6 text-xs font-bold border border-red-500/30">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-blue-400 uppercase tracking-widest ml-1">First Name</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-blue-400 uppercase tracking-widest ml-1">Last Name</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-blue-400 uppercase tracking-widest ml-1">Email Address</label>
                <div class="flex items-center px-4 py-3 custom-input group">
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-blue-400 uppercase tracking-widest ml-1">Phone Number (Optional)</label>
                <div class="flex items-center px-4 py-3 custom-input group">
                    <input type="text" name="phone_number" value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>" class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-blue-400 uppercase tracking-widest ml-1">Password</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <input type="password" name="password" required class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-blue-400 uppercase tracking-widest ml-1">Confirm Password</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <input type="password" name="confirm_password" required class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full blue-gradient text-white py-4 rounded-xl font-bold text-sm shadow-xl shadow-blue-900/40 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest mt-4">
                Create Manager Account
            </button>
        </form>
    </div>

</body>
</html>
