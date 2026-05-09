<?php
require 'config.php';
echo "--- property_boosts ---\n";
$stmt = $pdo->query("DESCRIBE property_boosts");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "\n--- boost_packages ---\n";
$stmt = $pdo->query("DESCRIBE boost_packages");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
