<?php
include 'config/db.php';

// Add cottage_id column to reservations table if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM reservations LIKE 'cottage_id'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE reservations ADD COLUMN cottage_id INT NULL AFTER contact_email");
    echo "Added cottage_id column to reservations table\n";
} else {
    echo "cottage_id column already exists\n";
}
?>