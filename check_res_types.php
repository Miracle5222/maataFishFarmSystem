<?php
require 'config/db.php';
$result = $conn->query('SELECT DISTINCT reservation_type FROM reservations');
while($row = $result->fetch_assoc()) {
    echo $row['reservation_type'] . "\n";
}
?>