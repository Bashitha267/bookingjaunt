<?php
require_once 'config.php';
$stmt = $pdo->query("DESCRIBE advertisements");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
?>
