<?php
require 'config.php';
try {
    $stmt = $pdo->query("DESCRIBE properties business_type");
    $row = $stmt->fetch();
    echo "Current Type: " . $row['Type'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
