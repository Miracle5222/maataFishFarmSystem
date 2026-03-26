<?php
require __DIR__ . '/config/db.php';

echo "=== Menu Orders Table Schema ===\n\n";
$result = $conn->query('DESCRIBE menu_orders');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== Orders Table Schema ===\n\n";
$result = $conn->query('DESCRIBE orders');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== Fish Orders Table Schema ===\n\n";
$result = $conn->query('DESCRIBE fish_orders');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

$conn->close();
?>
