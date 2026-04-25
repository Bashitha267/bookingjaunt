<?php
require_once 'config.php';
try {
    $pdo->exec("ALTER TABLE properties ADD COLUMN IF NOT EXISTS rules_json TEXT");
    $pdo->exec("ALTER TABLE properties ADD COLUMN IF NOT EXISTS popular_amenities_json TEXT");
    $pdo->exec("ALTER TABLE properties ADD COLUMN IF NOT EXISTS custom_rules_json TEXT");
    echo "Columns added successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
