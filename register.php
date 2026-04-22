<?php
require_once 'config.php';
session_start();

// Handle AJAX requests for Email Check and Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] == 'check_email') {
        $email = $_POST['email'];
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo json_encode(['exists' => true, 'redirect' => 'login.php?email=' . urlencode($email)]);
        } else {
            echo json_encode(['exists' => false]);
        }
        exit;
    }

    if ($_POST['action'] == 'register_user') {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        $role = $_POST['role'] ?? 'user';

        try {
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $email, $password, $role]);
            
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['role'] = $role;

            echo json_encode(['success' => true, 'redirect' => ($role == 'owner' ? 'register.php?step=property' : 'index.php')]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Registration failed. Email might already exist.']);
            exit;
        }
    }
    
    // Property registration logic remains largely the same but refined
    if ($_POST['action'] == 'register_property') {
        $owner_id = $_SESSION['user_id'] ?? 0;
        if (!$owner_id) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login.']);
            exit;
        }

        $data = $_POST;
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO properties (owner_id, business_type, property_name, street_address, city, country, contact_number, business_email, check_in_time, check_out_time, cancellation_policy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $owner_id, $data['business_type'], $data['property_name'], 
                $data['street_address'], $data['city'], $data['country'], 
                $data['contact_number'], $data['business_email'], 
                $data['check_in_time'] ?? '14:00', $data['check_out_time'] ?? '12:00',
                $data['cancellation_policy'] ?? ''
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'redirect' => 'index.php']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in or create an account - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f5f5f5; }
        .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(0,0,0,0.05); }
        .btn-primary { background-color: #006ce4; color: white; transition: all 0.2s; }
        .btn-primary:hover { background-color: #0056b3; }
        .step-content { display: none; }
        .step-content.active { display: block; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="p-6 flex justify-between items-center max-w-7xl mx-auto w-full">
        <a href="index.php" class="text-2xl font-bold text-[#006ce4]">Bookingjaunt</a>
        <div class="flex items-center gap-4 text-sm text-gray-500">
            <span>English (US)</span>
            <img src="https://flagcdn.com/w20/us.png" width="20" alt="US">
            <i class="far fa-question-circle text-lg"></i>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center p-6">
        
        <!-- Step 1: Email Entry (Booking.com Style) -->
        <div id="email-step" class="glass-card max-w-md w-full p-10 rounded-xl shadow-sm">
            <h1 class="text-2xl font-bold mb-6">Sign in or create an account</h1>
            <p class="text-sm text-gray-600 mb-8">Enter your email address to start your booking journey.</p>
            
            <form id="email-form" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold mb-2">Email address</label>
                    <input type="email" id="email-input" name="email" required placeholder="Enter your email address" class="w-full px-4 py-3 rounded-md border border-gray-300 focus:border-[#006ce4] focus:ring-1 focus:ring-[#006ce4] outline-none">
                </div>
                <button type="submit" class="w-full btn-primary py-3 rounded-md font-bold text-lg shadow-sm">Continue with email</button>
            </form>

            <div class="mt-10 border-t pt-8">
                <p class="text-center text-xs text-gray-500 mb-6">or use one of these options</p>
                <div class="flex justify-center gap-6">
                    <button class="w-12 h-12 border rounded-full flex items-center justify-center hover:bg-gray-50 transition-all"><i class="fab fa-google text-xl text-red-500"></i></button>
                    <button class="w-12 h-12 border rounded-full flex items-center justify-center hover:bg-gray-50 transition-all"><i class="fab fa-facebook-f text-xl text-blue-600"></i></button>
                    <button class="w-12 h-12 border rounded-full flex items-center justify-center hover:bg-gray-50 transition-all"><i class="fab fa-apple text-xl"></i></button>
                </div>
            </div>

            <p class="text-center mt-10 text-xs text-gray-400 border-t pt-6">
                By signing in or creating an account, you agree with our <a href="#" class="text-[#006ce4] hover:underline">Terms & Conditions</a> and <a href="#" class="text-[#006ce4] hover:underline">Privacy Statement</a>
            </p>
        </div>

        <!-- Step 2: Full Registration (Appears if email is new) -->
        <div id="registration-step" class="hidden glass-card max-w-lg w-full p-10 rounded-xl shadow-sm">
            <h1 class="text-2xl font-bold mb-2">Create a password</h1>
            <p class="text-sm text-gray-600 mb-8">Set your password to finish creating your account.</p>
            
            <form id="registration-form" class="space-y-6">
                <input type="hidden" name="action" value="register_user">
                <input type="hidden" name="email" id="final-email">
                <input type="hidden" name="role" id="user-role" value="user">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2">First Name</label>
                        <input type="text" name="first_name" required class="w-full px-4 py-3 rounded-md border border-gray-300 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2">Last Name</label>
                        <input type="text" name="last_name" required class="w-full px-4 py-3 rounded-md border border-gray-300 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 rounded-md border border-gray-300 outline-none">
                    <p class="text-xs text-gray-500 mt-2">At least 8 characters, including a mix of letters and numbers.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-3 rounded-md border border-gray-300 outline-none">
                </div>
                
                <div class="flex items-center gap-3">
                    <input type="checkbox" id="is-owner" onchange="document.getElementById('user-role').value = this.checked ? 'owner' : 'user'">
                    <label for="is-owner" class="text-sm font-semibold text-gray-700">I want to list my property (Register as Owner)</label>
                </div>

                <button type="submit" class="w-full btn-primary py-3 rounded-md font-bold text-lg shadow-sm">Create account</button>
            </form>
        </div>

        <!-- Property Wizard would follow here if role is 'owner' -->
        <!-- (This part is already in the database and handled by the logic) -->

    </main>

    <footer class="p-8 text-center text-xs text-gray-500 border-t bg-white">
        All rights reserved. Copyright (2026) Bookingjaunt.com™
    </footer>

    <script>
        // Email check step
        document.getElementById('email-form').onsubmit = async (e) => {
            e.preventDefault();
            const email = document.getElementById('email-input').value;
            
            const response = await fetch('register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=check_email&email=${encodeURIComponent(email)}`
            });
            const result = await response.json();
            
            if (result.exists) {
                window.location.href = result.redirect;
            } else {
                document.getElementById('email-step').classList.add('hidden');
                document.getElementById('registration-step').classList.remove('hidden');
                document.getElementById('final-email').value = email;
            }
        };

        // Full registration step
        document.getElementById('registration-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const response = await fetch('register.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                if (result.redirect.includes('property')) {
                    // Redirect to property listing landing page or start wizard
                    window.location.href = 'list_your_property.php';
                } else {
                    window.location.href = result.redirect;
                }
            } else {
                alert(result.message);
            }
        };
    </script>

</body>
</html>
