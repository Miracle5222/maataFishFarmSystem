<?php
require __DIR__ . '/config/db.php';

echo "=== ORDER_ITEMS TABLE SCHEMA ===\n\n";
$result = $conn->query('DESCRIBE order_items');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'];
    if ($row['Key']) echo " [KEY: " . $row['Key'] . "]";
    if ($row['Null'] === 'NO') echo " [NOT NULL]";
    echo "\n";
}

echo "\n=== SAMPLE ORDER ITEMS (Recent 3) ===\n";
$sample = $conn->query('SELECT id, order_id, product_id, quantity, unit_price, subtotal FROM order_items ORDER BY id DESC LIMIT 3');
if ($sample && $sample->num_rows > 0) {
    while ($row = $sample->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Order: " . $row['order_id'] . " | Product: " . $row['product_id'] . " | Qty: " . $row['quantity'] . " | Price: " . $row['unit_price'] . " | Subtotal: " . $row['subtotal'] . "\n";
    }
} else {
    echo "No order items yet\n";
}

$conn->close();
?>
