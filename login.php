<?php
require_once 'config.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role'] = $user['role'];

            // Check if user has a property
            $stmt = $pdo->prepare("SELECT id FROM properties WHERE owner_id = ? LIMIT 1");
            $stmt->execute([$user['id']]);
            $has_property = $stmt->fetch();

            if ($user['role'] === 'admin') {
                header("Location: system/admin/dashboard.php");
            } elseif ($has_property) {
                header("Location: system/hotel/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
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
    <title>Login - Bookingjaunt Luxury</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <style>body {
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

        .blue-gradient {
            background: linear-gradient(135deg, #006ce4 0%, #003580 100%);
        }

        .blue-text {
            color: #006ce4;
        }

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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .custom-input:focus-within {
            background: rgba(255, 255, 255, 0.08);
            border-color: #006ce4;
            box-shadow: 0 0 0 4px rgba(0, 108, 228, 0.15);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
    </style>
</head>

<body class="flex flex-col items-center justify-center min-h-screen p-6">

    <div class="glass-container max-w-sm w-full p-8 rounded-[2rem] animate-fade-in">
        <div class="flex flex-col items-center mb-6 text-center">
            <div class="w-24 h-18 flex items-center justify-center mb-1">
                <img src="assets/white_logo.png" class="w-24 h-20 object-contain">
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tighter mb-1"
                style="font-family: 'Playfair Display', serif;">Bookingjaunt</h1>
            <p class="text-gray-300 text-[9px] font-bold tracking-[0.2em] opacity-80 uppercase">Where every booking
                feels like a vacation</p>
        </div>

        <?php if ($error): ?>
            <div
                class="bg-red-500/20 backdrop-blur-md text-red-200 p-3 rounded-lg mb-6 text-xs font-bold flex items-center gap-2 border border-red-500/30">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Email
                    Address</label>
                <div class="flex items-center px-4 py-3 custom-input group">
                    <i
                        class="fas fa-envelope text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
                        placeholder="name@example.com"
                        class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                </div>
            </div>

            <div class="space-y-1">
                <label
                    class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Password</label>
                <div class="flex items-center px-4 py-3 custom-input group">
                    <i class="fas fa-lock text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                    <input type="password" name="password" required placeholder="••••••••"
                        class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                </div>
            </div>

            <div class="flex items-center justify-between text-[9px] font-bold px-1">
                <label class="flex items-center gap-1.5 cursor-pointer group text-gray-400">
                    <input type="checkbox"
                        class="w-3 h-3 rounded border-gray-600 bg-transparent text-[#006ce4] focus:ring-[#006ce4]">
                    <span class="group-hover:text-white transition-colors uppercase tracking-wider">Remember me</span>
                </label>
                <a href="#" class="text-[#006ce4] hover:text-white transition-colors uppercase tracking-wider">Forgot
                    Password?</a>
            </div>

            <button type="submit"
                class="w-full blue-gradient text-white py-4 rounded-xl font-bold text-sm shadow-xl shadow-blue-900/40 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest mt-2">
                Sign In
            </button>
        </form>

        <div class="text-center mt-10">
            <p class="text-gray-400 text-[11px] font-medium uppercase tracking-wider">
                New to Bookingjaunt? <a href="register.php"
                    class="text-white font-black hover:text-[#006ce4] transition-colors ml-1 border-b border-white/20 hover:border-[#006ce4]">Register
                    Now</a>
            </p>
        </div>
    </div>

    <div class="mt-8 text-center text-[10px] text-gray-500 font-bold uppercase tracking-[0.4em] opacity-40">
        © 2026 Experience Sri Lanka, effortlessly – by Bookingjaunt
    </div>

</body>

</html>