<?php
require_once 'config.php';
try {
    $pdo->query("ALTER TABLE advertisements ADD COLUMN user_id INT NULL AFTER id, ADD COLUMN ad_title VARCHAR(255) NULL AFTER user_id");
    echo "Success";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
