<?php
include 'config/db.php';

echo "All Cottages:<br>";
$result = $conn->query('SELECT id, cottage_number FROM cottages ORDER BY id DESC');
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Number: " . $row['cottage_number'] . "<br>";
}
?>
