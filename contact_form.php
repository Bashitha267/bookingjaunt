<?php
require_once 'config.php';
require_once 'mail_helper.php';
session_start();

// Generate CSRF Token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Verify CSRF Token
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = "Security validation failed. Please refresh and try again.";
    } else {
        // 2. Honey-pot check (Spam Prevention)
        // If the hidden 'website' field is filled, it is a bot submission
        $honeypot = $_POST['website'] ?? '';
        if (!empty($honeypot)) {
            // Silently ignore or show general success to confuse bots
            $success = "Your message was sent successfully! (Spam catch)";
        } else {
            // 3. Retrieve and sanitize input fields
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');

            // 4. Input Validations
            if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                $error = "All fields are required.";
            } elseif (!MailSender::isValidEmail($email)) {
                $error = "Please enter a valid email address.";
            } else {
                // 5. Prevent Header Injection
                // Clean newlines/carriage returns from fields that go into headers
                $cleanName = preg_replace('/[\r\n\t]+/', ' ', $name);
                $cleanSubject = preg_replace('/[\r\n\t]+/', ' ', $subject);

                // Sanitize message content (preventing HTML injection inside HTML emails)
                $safeMessage = nl2br(htmlspecialchars($message));

                // 6. Send Email using MailSender
                // Admin Notification Template
                $adminHtml = '
                <h2>New Contact Inquiry Received</h2>
                <p>You have received a new contact inquiry from the Bookingjaunt website.</p>
                <table class="detail-table">
                    <tr>
                        <th>Sender Name</th>
                        <td>' . htmlspecialchars($cleanName) . '</td>
                    </tr>
                    <tr>
                        <th>Sender Email</th>
                        <td>' . htmlspecialchars($email) . '</td>
                    </tr>
                    <tr>
                        <th>Subject</th>
                        <td>' . htmlspecialchars($cleanSubject) . '</td>
                    </tr>
                </table>
                <div class="highlight-box">
                    <strong>Message Content:</strong><br><br>
                    ' . $safeMessage . '
                </div>
                <p>You can reply directly to this email to contact the sender.</p>';

                $adminMailBody = MailSender::send(
                    MAIL_SENDER_EMAIL, // Configured sender/admin email
                    'Bookingjaunt Admin',
                    "New Inquiry: " . $cleanSubject,
                    MailSender::sendWelcomeEmail($email, $cleanName) ? // We wrap inside base template structure
                    str_replace('Welcome to Bookingjaunt', 'New Contact Inquiry', MailSender::sendWelcomeEmail('', '')) : $adminHtml
                );

                // Re-compile HTML body inside standard wrapper
                $adminMailHtml = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>New Contact Form Submission</title>
                    <style>
                        body { font-family: sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px; }
                        h2 { color: #003580; border-bottom: 2px solid #003580; padding-bottom: 10px; }
                        .field { margin-bottom: 15px; }
                        .label { font-weight: bold; color: #666; }
                        .value { margin-top: 5px; }
                        .message-box { background: #f9f9f9; border-left: 4px solid #003580; padding: 15px; margin-top: 15px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h2>New Contact Form Inquiry</h2>
                        <div class="field"><span class="label">Sender Name:</span> <div class="value">' . htmlspecialchars($cleanName) . '</div></div>
                        <div class="field"><span class="label">Sender Email:</span> <div class="value">' . htmlspecialchars($email) . '</div></div>
                        <div class="field"><span class="label">Subject:</span> <div class="value">' . htmlspecialchars($cleanSubject) . '</div></div>
                        <div class="field"><span class="label">Message:</span> <div class="message-box">' . $safeMessage . '</div></div>
                    </div>
                </body>
                </html>';

                // Send to Admin
                $sentToAdmin = MailSender::send(MAIL_SENDER_EMAIL, 'Bookingjaunt Admin', "Website Inquiry: " . $cleanSubject, $adminMailHtml);

                // Send confirmation receipt to user
                $userReceiptHtml = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>We received your message</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #374151; }
                        .container { max-width: 600px; margin: 40px auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; overflow: hidden; }
                        .header { background: #003580; color: #fff; padding: 30px; text-align: center; }
                        .content { padding: 35px; }
                        .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 11px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
                        .message-copy { background: #f0f7ff; border-left: 4px solid #006ce4; padding: 15px; margin-top: 15px; border-radius: 0 10px 10px 0; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1 style="margin:0;font-size:22px;">Bookingjaunt</h1>
                        </div>
                        <div class="content">
                            <h3>Hello ' . htmlspecialchars($cleanName) . ',</h3>
                            <p>Thank you for contacting Bookingjaunt. We have successfully received your inquiry and our support representatives will get back to you shortly.</p>
                            <p>For your records, here is a copy of your message:</p>
                            <div class="message-copy">
                                <strong>Subject:</strong> ' . htmlspecialchars($cleanSubject) . '<br><br>
                                ' . $safeMessage . '
                            </div>
                        </div>
                        <div class="footer">
                            <p>© 2026 Bookingjaunt. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>';

                MailSender::send($email, $cleanName, "We received your message: " . $cleanSubject, $userReceiptHtml);

                if ($sentToAdmin) {
                    $success = "Thank you! Your message has been sent successfully. We will get back to you shortly.";
                    // Clear inputs on success
                    $name = $email = $subject = $message = '';
                } else {
                    // Crucial failure-tolerance logic: if email fails, log error, but show supportive error.
                    $error = "Unable to process message at this moment. Please try again later or contact us directly.";
                }
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
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png">
    <link rel="manifest" href="/assets/site.webmanifest">
    <title>Contact Us - Bookingjaunt Luxury</title>
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

        /* Honeypot field wrapper */
        .website-field {
            display: none !important;
            visibility: hidden !important;
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

    <div class="glass-container max-w-lg w-full p-8 md:p-10 rounded-[2.5rem] animate-fade-in my-10">
        <div class="flex flex-col items-center mb-6 text-center">
            <div class="w-24 h-18 flex items-center justify-center mb-1">
                <img src="assets/white_logo.png" class="w-24 h-20 object-contain">
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tighter mb-1" style="font-family: 'Playfair Display', serif;">Get in Touch</h1>
            <p class="text-gray-300 text-[10px] font-bold tracking-[0.25em] opacity-80 uppercase">How can we assist you today?</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/20 backdrop-blur-md text-red-200 p-4 rounded-xl mb-6 text-xs font-bold flex items-center gap-2 border border-red-500/30">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-emerald-500/20 backdrop-blur-md text-emerald-200 p-4 rounded-xl mb-6 text-xs font-bold flex items-center gap-2 border border-emerald-500/30">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <!-- Honeypot anti-spam hidden input -->
            <div class="website-field">
                <label for="website">Leave this field blank</label>
                <input type="text" name="website" id="website" autocomplete="off">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Your Name</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <i class="fas fa-user text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                        <input type="text" name="name" required value="<?= htmlspecialchars($name ?? '') ?>" placeholder="John Doe" class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>
                
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Email Address</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <i class="fas fa-envelope text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                        <input type="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>" placeholder="name@example.com" class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Subject</label>
                <div class="flex items-center px-4 py-3 custom-input group">
                    <i class="fas fa-heading text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                    <input type="text" name="subject" required value="<?= htmlspecialchars($subject ?? '') ?>" placeholder="Inquiry about bookings..." class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Message</label>
                <div class="px-4 py-3 custom-input group">
                    <textarea name="message" required rows="5" placeholder="Write your inquiry here..." class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500 resize-none"><?= htmlspecialchars($message ?? '') ?></textarea>
                </div>
            </div>

            <button type="submit" class="w-full blue-gradient text-white py-4 rounded-xl font-bold text-sm shadow-xl shadow-blue-900/40 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest mt-2">
                <i class="fas fa-paper-plane mr-2 text-xs"></i> Send Message
            </button>
        </form>

        <div class="text-center mt-8 pt-6 border-t border-white/10 flex justify-center gap-6 text-gray-400 text-xs">
            <a href="index.php" class="hover:text-white transition-colors"><i class="fas fa-home mr-1"></i> Home</a>
            <span>·</span>
            <a href="login.php" class="hover:text-white transition-colors"><i class="fas fa-sign-in-alt mr-1"></i> Sign In</a>
        </div>
    </div>

    <div class="mb-10 text-center text-[10px] text-gray-400 font-bold uppercase tracking-[0.3em] opacity-60">
        © 2026 Experience Sri Lanka, effortlessly – by Bookingjaunt
    </div>

</body>

</html>
