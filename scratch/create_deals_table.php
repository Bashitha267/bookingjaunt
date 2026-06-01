<?php
require_once __DIR__ . '/../config.php';

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'deals_of_the_day'");
    $exists = $stmt->rowCount() > 0;

    if (!$exists) {
        $sql = "CREATE TABLE deals_of_the_day (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            property_id     INT NOT NULL,
            room_id         INT NOT NULL,               -- links to property_rooms.id
            room_name       VARCHAR(255) NOT NULL,       -- snapshot of room name
            original_price  DECIMAL(10,2) NOT NULL,      -- original price_lkr per night
            deal_price      DECIMAL(10,2) NOT NULL,       -- discounted price_lkr per night
            deal_label      VARCHAR(100) DEFAULT NULL,    -- e.g. \"Weekend Deal\", \"Flash Sale\"
            valid_from      DATE NOT NULL,
            valid_until     DATE NOT NULL,
            is_active       TINYINT(1) DEFAULT 1,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES property_rooms(id) ON DELETE CASCADE
        );";
        $pdo->exec($sql);
        echo "Table 'deals_of_the_day' created successfully.\n";
    } else {
        echo "Table 'deals_of_the_day' already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
