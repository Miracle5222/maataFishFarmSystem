<?php
require 'config/db.php';

echo "Orders Table Columns:\n";
echo str_repeat("=", 50) . "\n";

$result = $conn->query('SHOW COLUMNS FROM orders');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " | Type: " . $row['Type'] . "\n";
    }
}
?>
