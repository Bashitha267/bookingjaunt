<?php
require 'config.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS property_boosts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        start_date DATE NOT NULL,
        duration_days INT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        payment_status VARCHAR(50) DEFAULT 'pending',
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "property_boosts table created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
