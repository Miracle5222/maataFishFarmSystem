<?php
require 'config/db.php';
$result = $conn->query('SELECT id FROM cottages WHERE id = 18');
echo 'Found: ' . $result->num_rows . "\n";
?>