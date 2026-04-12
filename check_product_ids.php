<?php
require 'config/db.php';
$result = $conn->query('SELECT DISTINCT product_id FROM order_items LIMIT 10');
while($row = $result->fetch_assoc()) {
    echo $row['product_id'] . "\n";
}
?>