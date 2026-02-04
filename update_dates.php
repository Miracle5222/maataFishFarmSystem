<?php
include 'config/db.php';

// Update all cottages with NULL dates to have current dates
$updateSql = "UPDATE cottages SET available_date_from = '2026-01-28', available_date_to = '2026-01-31' WHERE available_date_from IS NULL OR available_date_to IS NULL";

if ($conn->query($updateSql)) {
    echo "Successfully updated cottages with dates.\n";
    
    // Verify
    $result = $conn->query("SELECT id, cottage_number, available_date_from, available_date_to FROM cottages");
    while($row = $result->fetch_assoc()) {
        echo "Cottage " . $row['id'] . " (Number: " . $row['cottage_number'] . "): " . $row['available_date_from'] . " to " . $row['available_date_to'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
