<?php
include 'config/db.php';

echo "Checking cottage dates:\n";
$result = $conn->query("SELECT id, cottage_number, available_date_from, available_date_to FROM cottages LIMIT 5");
while($row = $result->fetch_assoc()) {
    echo "Cottage " . $row['id'] . " (Number: " . $row['cottage_number'] . "):\n";
    echo "  From: " . ($row['available_date_from'] ?: 'NULL') . "\n";
    echo "  To: " . ($row['available_date_to'] ?: 'NULL') . "\n";
}
?>
