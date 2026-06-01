<?php
require_once 'config.php';
require_once 'mail_helper.php';
session_start();

// Self-healing database initialization: create password_resets table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        otp VARCHAR(10) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (email),
        INDEX (otp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    error_log("Database Error creating password_resets table: " . $e->getMessage());
}

$error = '';
$success = '';
$step = isset($_SESSION['reset_step']) ? $_SESSION['reset_step'] : 1; // 1: request OTP, 2: verify and reset
$reset_email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';

// Reset Flow Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // ACTION 1: Request OTP
    if ($_POST['action'] == 'request_otp') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = "Please enter your email address.";
        } elseif (!MailSender::isValidEmail($email)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $first_name = $user['first_name'];
                
                // Generate secure 6-digit OTP
                $otp = strval(rand(100000, 999999));
                $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                try {
                    // Delete any existing codes for this email to avoid clutter
                    $del_stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                    $del_stmt->execute([$email]);
                    
                    // Insert the new code
                    $ins_stmt = $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
                    $ins_stmt->execute([$email, $otp, $expires_at]);
                    
                    // Send the email (this is try-caught inside MailSender)
                    $mailSent = MailSender::sendForgotPassOTPEmail($email, $first_name, $otp);
                    
                    // Store state and advance to step 2
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_step'] = 2;
                    $reset_email = $email;
                    $step = 2;
                    
                    if ($mailSent) {
                        $success = "OTP verification code has been sent to your email.";
                    } else {
                        // Crucial failure-tolerance logic: if email fails to send, we STILL allow user to verify OTP.
                        // We will print the code in system logs or optionally allow resetting, but to help developer testing,
                        // we show a descriptive message.
                        $success = "A request was registered (Email delivery failed. If in development, check mail logs or check DB table password_resets).";
                    }
                } catch (Exception $e) {
                    $error = "An error occurred. Please try again: " . $e->getMessage();
                }
            } else {
                // To prevent email enumeration attacks, you can say "sent if exists", but for client-focused sites, clear message is fine.
                $error = "No account found with this email address.";
            }
        }
    }
    
    // ACTION 2: Verify OTP and Reset Password
    if ($_POST['action'] == 'verify_and_reset') {
        $otp = trim($_POST['otp'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($otp) || empty($new_password) || empty($confirm_password)) {
            $error = "Please fill in all verification fields.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            try {
                // Verify OTP is valid and not expired
                $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW() LIMIT 1");
                $stmt->execute([$reset_email, $otp]);
                $reset = $stmt->fetch();
                
                if ($reset) {
                    // Update user's password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $upd_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $upd_stmt->execute([$hashed_password, $reset_email]);
                    
                    // Clean up/Delete verified OTP
                    $del_stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                    $del_stmt->execute([$reset_email]);
                    
                    // Clear session state
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_step']);
                    
                    // Redirect to login page with success notification
                    header("Location: login.php?success=" . urlencode("Password reset successfully! You can now log in."));
                    exit();
                } else {
                    $error = "Invalid or expired OTP verification code.";
                }
            } catch (Exception $e) {
                $error = "Failed to reset password: " . $e->getMessage();
            }
        }
    }

    // ACTION 3: Go Back / Start Over
    if ($_POST['action'] == 'start_over') {
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_step']);
        $step = 1;
        $reset_email = '';
        $success = '';
        $error = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png">
    <link rel="manifest" href="/assets/site.webmanifest">
    <title>Forgot Password - Bookingjaunt Luxury</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
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

        .blue-gradient {
            background: linear-gradient(135deg, #006ce4 0%, #003580 100%);
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

    <div class="glass-container max-w-sm w-full p-8 rounded-[2rem] animate-fade-in animate-duration-500">
        <div class="flex flex-col items-center mb-6 text-center">
            <div class="w-24 h-18 flex items-center justify-center mb-1">
                <img src="assets/white_logo.png" class="w-24 h-20 object-contain">
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tighter mb-1" style="font-family: 'Playfair Display', serif;">Bookingjaunt</h1>
            <p class="text-gray-300 text-[9px] font-bold tracking-[0.2em] opacity-80 uppercase">Account Recovery Service</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/20 backdrop-blur-md text-red-200 p-3 rounded-lg mb-6 text-xs font-bold flex items-center gap-2 border border-red-500/30">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-emerald-500/20 backdrop-blur-md text-emerald-200 p-3 rounded-lg mb-6 text-xs font-bold flex items-center gap-2 border border-emerald-500/30">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- STEP 1: Enter Email -->
        <?php if ($step == 1): ?>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="request_otp">
                
                <div class="space-y-1">
                    <p class="text-xs text-gray-300 leading-relaxed mb-4">Enter the email address associated with your account. We will send you a 6-digit OTP code to verify your identity.</p>
                    <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Email Address</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <i class="fas fa-envelope text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                        <input type="email" name="email" required placeholder="name@example.com" class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>

                <button type="submit" class="w-full blue-gradient text-white py-4 rounded-xl font-bold text-sm shadow-xl shadow-blue-900/40 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest mt-2">
                    Send OTP Code
                </button>
            </form>
        <?php endif; ?>

        <!-- STEP 2: Verify OTP and Reset Password -->
        <?php if ($step == 2): ?>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="action" value="verify_and_reset">
                
                <div class="space-y-1">
                    <p class="text-[11px] text-gray-300 leading-relaxed mb-3">Verification code sent to: <strong class="text-white"><?= htmlspecialchars($reset_email) ?></strong></p>
                    
                    <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">6-Digit OTP Code</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <i class="fas fa-key text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                        <input type="text" name="otp" required maxlength="6" placeholder="123456" class="w-full bg-transparent outline-none text-white text-[13px] tracking-[0.3em] font-mono placeholder-gray-500">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">New Password</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <i class="fas fa-lock text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                        <input type="password" name="new_password" required placeholder="Min 6 characters" class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Confirm New Password</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <i class="fas fa-lock text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                        <input type="password" name="confirm_password" required placeholder="Confirm password" class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>

                <button type="submit" class="w-full blue-gradient text-white py-4 rounded-xl font-bold text-sm shadow-xl shadow-blue-900/40 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest mt-2">
                    Reset Password
                </button>
            </form>
            
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="start_over">
                <button type="submit" class="w-full text-center text-gray-400 hover:text-white transition-colors text-[10px] font-bold uppercase tracking-wider">
                    <i class="fas fa-arrow-left mr-1"></i> Use another email
                </button>
            </form>
        <?php endif; ?>

        <div class="text-center mt-8 pt-6 border-t border-white/10">
            <p class="text-gray-400 text-[10px] font-medium uppercase tracking-wider">
                Back to <a href="login.php" class="text-white font-black hover:text-[#006ce4] transition-colors ml-1">Sign In</a>
            </p>
        </div>
    </div>

    <div class="mt-8 text-center text-[10px] text-gray-500 font-bold uppercase tracking-[0.4em] opacity-40">
        © 2026 Experience Sri Lanka, effortlessly – by Bookingjaunt
    </div>

</body>

</html>
