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
    <title>Login - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/hero_bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-6">

    <div class="glass-card max-w-md w-full p-12 rounded-[2.5rem]">
        <div class="text-center mb-10">
            <a href="index.php" class="text-4xl font-extrabold text-indigo-600 tracking-tight">Bookingjaunt</a>
            <h2 class="text-lg font-semibold text-gray-500 mt-3">Welcome back to your journey</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-50/80 backdrop-blur-sm text-red-600 p-4 rounded-2xl mb-8 text-sm font-bold flex items-center gap-3">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-7">
            <div>
                <label class="block text-sm font-bold mb-2 ml-1">Email Address</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_GET['email'] ?? '') ?>" placeholder="name@example.com" class="w-full px-5 py-4 rounded-2xl border border-white/50 bg-white/50 outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
            </div>
            <div>
                <label class="block text-sm font-bold mb-2 ml-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full px-5 py-4 rounded-2xl border border-white/50 bg-white/50 outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
            </div>
            <div class="flex items-center justify-between text-sm px-1">
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="group-hover:text-indigo-600 transition-colors">Keep me signed in</span>
                </label>
                <a href="#" class="text-indigo-600 font-bold hover:underline">Forgot?</a>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-5 rounded-2xl font-bold text-lg hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition-all transform hover:-translate-y-1">Sign In</button>
        </form>
        
        <p class="text-center mt-10 text-gray-500 text-sm font-medium">
            New to Bookingjaunt? <a href="register.php" class="text-indigo-600 font-extrabold hover:underline">Create an account</a>
        </p>
    </div>

</body>
</html>
