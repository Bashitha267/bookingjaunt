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
            header("Location: index.php");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: url('assets/login-bg.png') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            overflow: hidden;
        }

        .orange-gradient {
            background: linear-gradient(135deg, #f37021 0%, #ff8c00 100%);
        }

        .orange-text {
            color: #f37021;
        }

        .glass-container {
            background: rgba(15, 15, 15, 0.4);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .input-underline {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: transparent;
        }

        .input-underline:focus {
            border-bottom: 1px solid #fbbd23;
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

    <div class="glass-container max-w-md w-full p-10 rounded-[2.5rem] animate-fade-in">
        <div class="flex flex-col items-center mb-8 text-center">
            <div class="w-32 h-24 flex items-center justify-center mb-4">
                <img src="assets/logo.png" class="w-28 h-20 object-contain">
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tighter mb-1"
                style="font-family: 'Playfair Display', serif;">Bookingjaunt</h1>
            <p class="text-gray-300 text-[10px] font-bold tracking-[0.2em] opacity-80 uppercase">Where every booking feels like a vacation</p>
        </div>

        <?php if ($error): ?>
            <div
                class="bg-red-500/20 backdrop-blur-md text-red-200 p-4 rounded-xl mb-8 text-sm font-bold flex items-center gap-3 border border-red-500/30">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <div class="relative group">
                <label class="block text-[9px] font-bold text-[#f37021] mb-1 uppercase tracking-[0.2em] ml-1 text-left">Email Address</label>
                <div class="flex items-center">
                    <i class="fas fa-envelope text-gray-500 mr-4 transition-colors group-focus-within:text-[#f37021] text-xs"></i>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
                        placeholder="name@example.com"
                        class="w-full py-2 input-underline outline-none text-white text-sm placeholder-gray-500 transition-all">
                </div>
            </div>

            <div class="relative group">
                <label class="block text-[9px] font-bold text-[#f37021] mb-1 uppercase tracking-[0.2em] ml-1 text-left">Password</label>
                <div class="flex items-center">
                    <i class="fas fa-lock text-gray-500 mr-4 transition-colors group-focus-within:text-[#f37021] text-xs"></i>
                    <input type="password" name="password" required placeholder="••••••••"
                        class="w-full py-2 input-underline outline-none text-white text-sm placeholder-gray-500 transition-all">
                </div>
            </div>

            <div class="flex items-center justify-between text-[10px] font-bold px-1">
                <label class="flex items-center gap-2 cursor-pointer group text-gray-400">
                    <input type="checkbox"
                        class="w-3.5 h-3.5 rounded border-gray-600 bg-transparent text-[#f37021] focus:ring-[#f37021]">
                    <span class="group-hover:text-white transition-colors uppercase tracking-wider">Remember me</span>
                </label>
                <a href="#" class="text-[#f37021] hover:text-white transition-colors uppercase tracking-wider">Forgot Password?</a>
            </div>

            <button type="submit"
                class="w-full orange-gradient text-white py-4 rounded-2xl font-bold text-sm shadow-xl shadow-orange-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                Sign In
            </button>
        </form>

        <div class="text-center mt-10">
            <p class="text-gray-400 text-[11px] font-medium uppercase tracking-wider">
                New to Bookingjaunt? <a href="register.php"
                    class="text-white font-black hover:text-[#f37021] transition-colors ml-1 border-b border-white/20 hover:border-[#f37021]">Register Now</a>
            </p>
        </div>
    </div>

    <div class="mt-8 text-center text-[10px] text-gray-500 font-bold uppercase tracking-[0.4em] opacity-40">
        © 2026 Experience Sri Lanka, effortlessly – by Bookingjaunt
    </div>

</body>

</html>