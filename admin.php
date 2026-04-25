<?php
require_once 'config.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role'] = $user['role'];
            header("Location: system/admin/dashboard.php");
            exit();
        } else {
            $error = "Invalid admin credentials.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            background-image: radial-gradient(at 0% 0%, rgba(0, 53, 128, 0.05) 0px, transparent 50%),
                              radial-gradient(at 100% 100%, rgba(0, 108, 228, 0.05) 0px, transparent 50%);
        }

        .auth-card {
            background: white;
            box-shadow: 0 25px 50px -12px rgba(0, 53, 128, 0.15);
            border: 1px solid rgba(0, 53, 128, 0.05);
        }

        .input-field {
            background: #ffffff;
            border: 2px solid #e5e7eb;
            transition: all 0.2s ease-in-out;
        }

        .input-field:focus {
            border-color: #003580;
            box-shadow: 0 0 0 4px rgba(0, 53, 128, 0.1);
            background: #ffffff;
        }

        .btn-primary {
            background: #003580;
            transition: all 0.2s ease-in-out;
        }

        .btn-primary:hover {
            background: #002560;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -5px rgba(0, 37, 96, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-slide-up {
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

    <div class="auth-card w-full max-w-[440px] rounded-[2.5rem] overflow-hidden animate-slide-up">
        <div class="p-8 md:p-12">
            <!-- Header -->
            <div class="text-center mb-10">
                <div class="w-16 h-16 bg-[#003580] rounded-2xl flex items-center justify-center text-white text-2xl mx-auto mb-6 shadow-lg shadow-blue-900/20">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Admin Portal</h1>
                <p class="text-gray-500 text-xs font-semibold mt-2 uppercase tracking-[0.2em] opacity-60">System Authentication</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 text-xs px-4 py-3 rounded-xl border border-red-100 mb-8 flex items-center gap-3 font-bold">
                    <i class="fas fa-circle-exclamation"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest ml-1">Username</label>
                    <div class="relative">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-user-circle text-lg"></i>
                        </span>
                        <input type="text" name="username" required 
                            class="input-field w-full pl-14 pr-5 py-4 rounded-2xl text-sm font-semibold text-gray-800 outline-none placeholder:text-gray-300"
                            placeholder="Enter username">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest ml-1">Password</label>
                    <div class="relative">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-key text-lg"></i>
                        </span>
                        <input type="password" name="password" required 
                            class="input-field w-full pl-14 pr-5 py-4 rounded-2xl text-sm font-semibold text-gray-800 outline-none placeholder:text-gray-300"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" 
                        class="btn-primary w-full text-white py-5 rounded-2xl font-bold text-xs uppercase tracking-[0.25em] flex items-center justify-center gap-3">
                        Authorize Access
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-gray-50/50 p-6 text-center border-t border-gray-100">
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.3em]">© 2026 Bookingjaunt Internal</p>
        </div>
    </div>

</body>

</html>
