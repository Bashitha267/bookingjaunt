<?php
require 'config.php';
try {
    $pdo->exec("ALTER TABLE property_rooms ADD COLUMN description TEXT DEFAULT NULL, ADD COLUMN things_included TEXT DEFAULT NULL;");
    echo "Columns added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
