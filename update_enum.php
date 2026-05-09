<?php
require 'config.php';
try {
    $sql = "ALTER TABLE properties MODIFY COLUMN business_type ENUM('hotel', 'reception_hall', 'hostel', 'rest_hall', 'villa', 'dayouts', 'safari', 'resort', 'apartment')";
    $pdo->exec($sql);
    echo "Successfully updated business_type enum to include villa, dayouts, safari, resort, and apartment.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
