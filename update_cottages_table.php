<?php
require 'config/db.php';

// Add the missing columns to the existing cottages table
$alter_queries = [
    "ALTER TABLE cottages ADD COLUMN available_date DATE NOT NULL DEFAULT '2026-01-22'",
    "ALTER TABLE cottages ADD COLUMN available_time_start TIME NOT NULL DEFAULT '09:00:00'",
    "ALTER TABLE cottages ADD COLUMN available_time_end TIME NOT NULL DEFAULT '17:00:00'"
];

foreach ($alter_queries as $query) {
    if ($conn->query($query)) {
        echo "Column added successfully\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

echo "Table update completed\n";
?>