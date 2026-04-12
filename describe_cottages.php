<?php
require 'config/db.php';
$result = $conn->query('DESCRIBE cottages');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' ' . $row['Type'] . "\n";
}
?>