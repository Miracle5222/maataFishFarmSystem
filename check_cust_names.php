<?php
require 'config/db.php';
$result = $conn->query('SELECT id, first_name, last_name FROM customers WHERE id IN (32,47)');
while($row = $result->fetch_assoc()) {
    echo 'Cust ' . $row['id'] . ' ' . $row['first_name'] . ' ' . $row['last_name'] . "\n";
}
?>