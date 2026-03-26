<?php
require __DIR__ . '/config/db.php';

echo "=== CARTS TABLE SCHEMA ===\n\n";
$result = $conn->query('DESCRIBE carts');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'];
    if ($row['Key']) echo " [KEY: " . $row['Key'] . "]";
    if ($row['Null'] === 'NO') echo " [NOT NULL]";
    echo "\n";
}

echo "\n=== SAMPLE CART DATA ===\n";
$sample = $conn->query('SELECT id, customer_id, fish_id, quantity, unit_price, item_type FROM carts LIMIT 3');
if ($sample && $sample->num_rows > 0) {
    while ($row = $sample->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | C_ID: " . $row['customer_id'] . " | F_ID: " . $row['fish_id'] . " | Qty: " . $row['quantity'] . " | Price: " . $row['unit_price'] . " | Type: " . $row['item_type'] . "\n";
    }
} else {
    echo "No cart data yet\n";
}

$conn->close();
?>
