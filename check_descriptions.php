<?php
require 'config/db.php';

$result = $conn->query('SELECT fish_id, name, description FROM fish_species LIMIT 3');
while ($row = $result->fetch_assoc()) {
    echo 'Fish ' . $row['fish_id'] . ': ' . $row['name'] . ' - Description: ' . ($row['description'] ?: 'NULL') . PHP_EOL;
}
$conn->close();
?>