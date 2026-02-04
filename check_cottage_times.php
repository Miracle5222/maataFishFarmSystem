<?php
include 'config/db.php';

// Check what's in the cottages table time columns
$result = $conn->query("SELECT id, cottage_number, available_time_start, available_time_end FROM cottages");

echo "Times in cottages table:\n";
while($row = $result->fetch_assoc()) {
    echo "Cottage " . $row['cottage_number'] . " (ID " . $row['id'] . "): " . ($row['available_time_start'] ?: 'NULL') . " to " . ($row['available_time_end'] ?: 'NULL') . "\n";
}
?>
