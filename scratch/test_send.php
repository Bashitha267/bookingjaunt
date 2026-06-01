<?php
/**
 * Scratch script to test sending to nimeshspc2k17@gmail.com
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../mail_helper.php';

// Enable temporary verbose SMTP debug output to stdout for testing
if (!defined('MAIL_SMTP_DEBUG') || MAIL_SMTP_DEBUG === 0) {
    // We override configuration to level 2 (client + server messages) to show connection logs
    putenv("SMTP_DEBUG=2");
    $_ENV['SMTP_DEBUG'] = '2';
    $_SERVER['SMTP_DEBUG'] = '2';
}

$to = 'nimeshspc2k17@gmail.com';
echo "Attempting to send test email to: $to\n";
echo "Using SMTP Host: " . MAIL_SMTP_HOST . "\n";
echo "Using SMTP User: " . MAIL_SMTP_USER . "\n";
echo "--------------------------------------------------\n";

$htmlContent = "<h1>Bookingjaunt SMTP Integration Test</h1><p>If you receive this, the system is fully operational!</p>";
$sent = MailSender::send($to, "Nimesh", "Bookingjaunt Integration Test", $htmlContent);

echo "--------------------------------------------------\n";
if ($sent) {
    echo "SUCCESS: The test email was successfully sent!\n";
} else {
    echo "FAILED: The email could not be sent. Please check the SMTP debug logs above to find the reason (e.g., credentials / authorization issues).\n";
}
