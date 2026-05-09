<?php
require_once 'config.php';
$stmt = $pdo->query("SELECT * FROM amenities_master");
$amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($amenities, JSON_PRETTY_PRINT);
?>
