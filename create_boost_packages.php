<?php
require 'config.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS boost_packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        duration_days INT NOT NULL,
        price_lkr DECIMAL(10,2) NOT NULL,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Check if empty, then insert defaults
    $stmt = $pdo->query("SELECT COUNT(*) FROM boost_packages");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO boost_packages (name, duration_days, price_lkr) VALUES ('Basic Boost (7 Days)', 7, 5000.00)");
        $pdo->exec("INSERT INTO boost_packages (name, duration_days, price_lkr) VALUES ('Premium Boost (30 Days)', 30, 15000.00)");
    }

    $sql_alter = "ALTER TABLE property_boosts ADD COLUMN package_id INT NULL AFTER property_id, ADD FOREIGN KEY (package_id) REFERENCES boost_packages(id) ON DELETE SET NULL";
    $pdo->exec($sql_alter);

    echo "Tables and defaults created.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
