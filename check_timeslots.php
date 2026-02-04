<?php
include 'config/db.php';

// Check the cottage_availability table for a specific cottage
// Let's check cottage ID 22 (Number 4) based on your dates
$result = $conn->query("SELECT * FROM cottage_availability WHERE cottage_id = 22 ORDER BY available_date");

echo "Time slots for Cottage 4:\n";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Date: " . $row['available_date'] . " | Start: " . $row['available_time_start'] . " | End: " . $row['available_time_end'] . " | Status: " . $row['status'] . "\n";
    }
} else {
    echo "No time slots found\n";
}
?>
