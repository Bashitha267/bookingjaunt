<?php
/**
 * Database configuration for Bookingjaunt
 */

// $host = 'localhost';
// $db   = 'bookingjaunt';
// $user = 'root';
// $pass = ''; // No password as requested
// $charset = 'utf8mb4';

$host = '127.0.0.1';
$db   = 'u776392061_bookingjaunt';
$user = 'u776392061_bookingjaunt';
$pass = '@Bookingjaunt2026';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In production, don't show the error message. Just log it.
    // die("Connection failed: " . $e->getMessage());
    error_log($e->getMessage());
    exit("Database connection error.");
}
?>