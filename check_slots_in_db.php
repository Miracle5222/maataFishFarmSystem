<?php
include 'config/db.php';

// Check what slots are in the database for cottage 22 (Cottage 4)
$result = $conn->query("SELECT DISTINCT available_time_start, available_time_end FROM cottage_availability WHERE cottage_id = 22 ORDER BY available_time_start, available_time_end");

echo "Current time slots in database for Cottage 4 (ID 22):\n";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['available_time_start'] . " to " . $row['available_time_end'] . "\n";
    }
} else {
    echo "  No slots found!\n";
}

echo "\nButton will show only the first slot:\n";
$first = $conn->query("SELECT available_time_start, available_time_end FROM cottages WHERE id = 22");
if ($row = $first->fetch_assoc()) {
    echo "  - " . $row['available_time_start'] . " to " . $row['available_time_end'] . "\n";
}
?>
