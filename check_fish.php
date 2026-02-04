<?php
require 'config/db.php';

$result = $conn->query('SELECT COUNT(*) as count FROM products WHERE category = "fish"');
$row = $result->fetch_assoc();
echo 'Products with category fish: ' . $row['count'] . PHP_EOL;

$result2 = $conn->query('SELECT COUNT(*) as count FROM fish_species');
$row2 = $result2->fetch_assoc();
echo 'Total fish_species: ' . $row2['count'] . PHP_EOL;

$conn->close();
?>