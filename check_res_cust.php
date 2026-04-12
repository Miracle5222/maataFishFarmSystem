<?php
require 'config/db.php';
$result = $conn->query('SELECT id, customer_id FROM reservations WHERE status = "completed" AND reservation_type = "cottage"');
while($row = $result->fetch_assoc()) {
    echo 'Res ' . $row['id'] . ' cust ' . $row['customer_id'] . "\n";
}
?>