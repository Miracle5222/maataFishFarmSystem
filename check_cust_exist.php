<?php
require 'config/db.php';
$result = $conn->query('SELECT id FROM customers WHERE id IN (32,47)');
echo 'Found: ' . $result->num_rows . "\n";
?>