<?php
include 'config/db.php';

$query = "
    SELECT
        o.id AS order_id,
        o.order_date,
        c.name AS customer_name,
        c.email AS customer_email,
        oi.quantity,
        oi.price,
        (oi.quantity * oi.price) AS total_amount,
        CASE
            WHEN oi.item_type = 'fish' THEN fs.name
            WHEN oi.item_type = 'product' THEN p.name
            ELSE 'Unknown'
        END AS item_name,
        oi.item_type
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN fish_species fs ON oi.item_id = fs.id AND oi.item_type = 'fish'
    LEFT JOIN products p ON oi.item_id = p.id AND oi.item_type = 'product'
    ORDER BY o.order_date DESC, o.id DESC
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "Transactions found:\n";
    while ($row = $result->fetch_assoc()) {
        echo "Order ID: " . $row['order_id'] . ", Date: " . $row['order_date'] . ", Customer: " . $row['customer_name'] . ", Item: " . $row['item_name'] . " (" . $row['item_type'] . "), Qty: " . $row['quantity'] . ", Total: $" . number_format($row['total_amount'], 2) . "\n";
    }
} else {
    echo "No transactions found.\n";
}

$conn->close();
?>