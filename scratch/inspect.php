<?php
require 'config.php';
foreach (['properties', 'property_rooms', 'room_types', 'property_media'] as $t) {
    echo "\n*** $t ***\n";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            echo $r['Field'] . ' - ' . $r['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
