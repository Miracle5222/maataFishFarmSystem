<?php
require 'config/db.php';
$result = $conn->query('SELECT id, customer_id FROM boat_rentals WHERE status = "completed"');
while($row = $result->fetch_assoc()) {
    echo 'Boat ' . $row['id'] . ' cust ' . $row['customer_id'] . "\n";
}
?>