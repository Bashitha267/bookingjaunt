<?php
/**
 * SMTP Mail Configuration for Bookingjaunt
 * Compatible with Hostinger cPanel SMTP settings.
 */

// Simple environment variable loader for .env file
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            // Parse name=value
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B=");
                // Remove quotes if present
                $value = trim($value, '"\'');
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}

// Load .env file from the root directory if it exists
loadEnv(__DIR__ . '/.env');

// Fallback configuration if env variables are not defined in .env
define('MAIL_SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.hostinger.com');
define('MAIL_SMTP_USER', getenv('SMTP_USER') ?: 'noreply@bookingjaunt.com');
define('MAIL_SMTP_PASS', getenv('SMTP_PASS') ?: 'your_smtp_password');
define('MAIL_SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('MAIL_SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls'); // 'tls' or 'ssl'
define('MAIL_SENDER_EMAIL', getenv('SMTP_SENDER_EMAIL') ?: 'noreply@bookingjaunt.com');
define('MAIL_SENDER_NAME', getenv('SMTP_SENDER_NAME') ?: 'Bookingjaunt');
define('MAIL_SMTP_DEBUG', getenv('SMTP_DEBUG') !== false ? (int)getenv('SMTP_DEBUG') : 0); // 0 = off, 1 = client, 2 = client + server
define('MAIL_LOGO_URL', getenv('MAIL_LOGO_URL') ?: 'https://bookingjaunt.com/assets/white_logo.png');
