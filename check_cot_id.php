<?php
require 'config/db.php';
$result = $conn->query('SELECT id, cottage_id FROM reservations WHERE status = "completed" AND reservation_type = "cottage"');
while($row = $result->fetch_assoc()) {
    echo 'Res ' . $row['id'] . ' cot ' . $row['cottage_id'] . "\n";
}
?>