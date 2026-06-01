<?php
/**
 * SMTP Mail Diagnostic Script for Bookingjaunt
 * Use this script to test if PHPMailer is installed, configurations are loaded,
 * and if SMTP email sending is working properly.
 * 
 * Usage: open in browser: http://localhost/bookingjaunt/test_mail.php?to=your_email@example.com
 */

require_once 'config.php';
require_once 'mail_helper.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookingjaunt Mail Diagnostic Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen p-8 text-slate-800">
    <div class="max-w-3xl mx-auto bg-white border border-slate-100 shadow-xl rounded-3xl p-8">
        <h1 class="text-2xl font-black text-slate-900 mb-2">Email System Diagnostics</h1>
        <p class="text-sm text-slate-500 mb-8">Verification panel for PHPMailer integration & Hostinger SMTP settings.</p>

        <!-- 1. Environment & Configuration Check -->
        <section class="mb-8">
            <h2 class="text-lg font-bold text-slate-950 mb-4 border-b border-slate-100 pb-2">1. System Status Check</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">PHPMailer Status</p>
                    <p class="text-sm font-semibold mt-1">
                        <?php
                        $composerAutoload = file_exists(__DIR__ . '/vendor/autoload.php');
                        $manualAutoload = file_exists(__DIR__ . '/phpmailer/src/PHPMailer.php');
                        
                        if ($composerAutoload) {
                            echo '<span class="text-emerald-600 font-bold"><i class="fas fa-check-circle mr-1"></i> Detected (Composer Autoloader)</span>';
                        } elseif ($manualAutoload) {
                            echo '<span class="text-emerald-600 font-bold"><i class="fas fa-check-circle mr-1"></i> Detected (Manual Upload in phpmailer/)</span>';
                        } else {
                            echo '<span class="text-rose-600 font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Missing</span>';
                        }
                        ?>
                    </p>
                </div>
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Configuration Loading</p>
                    <p class="text-sm font-semibold mt-1">
                        <?php
                        $envExists = file_exists(__DIR__ . '/.env');
                        if ($envExists) {
                            echo '<span class="text-emerald-600 font-bold"><i class="fas fa-check-circle mr-1"></i> Loaded via .env file</span>';
                        } else {
                            echo '<span class="text-amber-600 font-bold"><i class="fas fa-info-circle mr-1"></i> Loaded via default variables (No .env)</span>';
                        }
                        ?>
                    </p>
                </div>
            </div>

            <!-- SMTP Config details -->
            <div class="mt-4 p-5 bg-slate-900 text-slate-200 font-mono text-xs rounded-2xl leading-relaxed">
                <p class="text-[#febb02] font-bold mb-2">// Active Mail Configurations</p>
                <p>SMTP_HOST: <?php echo htmlspecialchars(MAIL_SMTP_HOST); ?></p>
                <p>SMTP_PORT: <?php echo htmlspecialchars(MAIL_SMTP_PORT); ?></p>
                <p>SMTP_USER: <?php echo htmlspecialchars(MAIL_SMTP_USER); ?></p>
                <p>SMTP_SECURE: <?php echo htmlspecialchars(MAIL_SMTP_SECURE ?: 'None'); ?></p>
                <p>SENDER_EMAIL: <?php echo htmlspecialchars(MAIL_SENDER_EMAIL); ?></p>
                <p>SENDER_NAME: <?php echo htmlspecialchars(MAIL_SENDER_NAME); ?></p>
                <p>SMTP_DEBUG: <?php echo htmlspecialchars(MAIL_SMTP_DEBUG); ?></p>
            </div>
        </section>

        <!-- 2. Test Mail Sender Form -->
        <section class="mb-8">
            <h2 class="text-lg font-bold text-slate-950 mb-4 border-b border-slate-100 pb-2">2. SMTP Email Test</h2>
            <p class="text-sm text-slate-500 mb-4">Enter a recipient email to send a test message. If mail debugging is enabled (SMTP_DEBUG = 1 or 2 in .env), check your error logs or development outputs.</p>
            
            <form method="GET" class="flex gap-4">
                <input type="email" name="to" required placeholder="recipient@example.com" value="<?php echo htmlspecialchars($_GET['to'] ?? ''); ?>" 
                       class="flex-1 px-5 py-4 bg-slate-50 border border-slate-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 text-sm font-semibold">
                <button type="submit" class="bg-blue-600 text-white px-8 py-4 rounded-xl font-bold text-sm uppercase tracking-wider hover:bg-blue-700 transition-colors">
                    Send Test Mail
                </button>
            </form>

            <?php
            if (isset($_GET['to'])) {
                $recipient = trim($_GET['to']);
                echo '<div class="mt-6 p-5 rounded-2xl border text-sm">';
                echo '<h3 class="font-bold mb-3">Diagnostic Results:</h3>';
                
                if (!MailSender::isValidEmail($recipient)) {
                    echo '<p class="text-rose-600 font-bold"><i class="fas fa-times-circle mr-1"></i> Invalid recipient email address format.</p>';
                } else {
                    echo '<p class="text-slate-500 mb-2">Initiating SMTP delivery to: <strong>' . htmlspecialchars($recipient) . '</strong>...</p>';
                    
                    // Simple template content wrapped in the base template
                    $testContent = '
                    <h2>Bookingjaunt SMTP Diagnostic Successful!</h2>
                    <p>This is a diagnostic email sent by the Bookingjaunt email subsystem. If you are reading this message, it means your SMTP server configurations are correct and working perfectly.</p>
                    <div class="highlight-box">
                        <strong>Diagnostic Information:</strong><br>
                        Timestamp: ' . date('Y-m-d H:i:s') . '<br>
                        Server Host: ' . htmlspecialchars(MAIL_SMTP_HOST) . ' (Port ' . htmlspecialchars(MAIL_SMTP_PORT) . ')
                    </div>
                    <p>Best regards,<br>Mail Diagnostic Agent</p>';

                    $testHtml = MailSender::getEmailBaseTemplate("Bookingjaunt Mail Test Success", $testContent);

                    $sent = MailSender::send($recipient, "Diagnostic Tester", "Bookingjaunt Mail Test Success", $testHtml);

                    if ($sent) {
                        echo '<p class="text-emerald-600 font-bold mt-2"><i class="fas fa-check-circle mr-1"></i> Test Email Sent Successfully! Check your inbox (and spam folder).</p>';
                    } else {
                        echo '<p class="text-rose-600 font-bold mt-2"><i class="fas fa-times-circle mr-1"></i> Delivery Failed. Check PHP error logs for SMTP detailed failure log.</p>';
                    }
                }
                echo '</div>';
            }
            ?>
        </section>

        <!-- 3. Hostinger cPanel Installation Guide -->
        <section>
            <h2 class="text-lg font-bold text-slate-950 mb-4 border-b border-slate-100 pb-2">3. cPanel Manual Installation Guide</h2>
            <div class="prose prose-sm text-xs text-slate-600 space-y-3 leading-relaxed">
                <p>If you don't have SSH/Composer access on your Hostinger cPanel hosting, follow these steps to install PHPMailer manually:</p>
                <ol class="list-decimal pl-5 space-y-2">
                    <li>Go to the official PHPMailer GitHub repository: <a href="https://github.com/PHPMailer/PHPMailer" target="_blank" class="text-blue-600 font-bold underline">github.com/PHPMailer/PHPMailer</a></li>
                    <li>Click the green <strong class="text-slate-800">Code</strong> button and choose <strong class="text-slate-800">Download ZIP</strong>.</li>
                    <li>Extract the ZIP on your local machine. You will see a folder (e.g. <code class="bg-slate-100 px-1 py-0.5 rounded font-mono text-rose-600">PHPMailer-master</code>).</li>
                    <li>Log into your Hostinger cPanel and open the <strong class="text-slate-800">File Manager</strong>.</li>
                    <li>Navigate to your website root directory (<code class="bg-slate-100 px-1 py-0.5 rounded font-mono">public_html</code> or project folder).</li>
                    <li>Create a new directory named <strong class="text-slate-800 font-mono">phpmailer</strong>.</li>
                    <li>Upload the <strong class="text-slate-800 font-mono">src/</strong> folder from the extracted ZIP inside the <code class="bg-slate-100 px-1 py-0.5 rounded font-mono">phpmailer/</code> folder on cPanel. The final structure must look like:
                        <ul class="list-disc pl-5 mt-1 font-mono text-slate-500">
                            <li>your-site-root/phpmailer/src/PHPMailer.php</li>
                            <li>your-site-root/phpmailer/src/Exception.php</li>
                            <li>your-site-root/phpmailer/src/SMTP.php</li>
                        </ul>
                    </li>
                    <li>Once uploaded, refresh this page. It should automatically detect the files and declare PHPMailer as <strong class="text-emerald-600">Detected</strong>!</li>
                </ol>
            </div>
        </section>
    </div>
</body>
</html>
