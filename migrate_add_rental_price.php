<?php
// Run this script once to add `rental_price` to `boat_inventory` (per-boat hourly rate)
require __DIR__ . '/config/db.php';
header('Content-Type: text/plain');

function table_has_column($conn, $table, $column) {
    $res = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "` LIKE '" . $conn->real_escape_string($column) . "'");
    return $res && $res->num_rows > 0;
}

echo "Starting migration for boat_inventory...\n";

if (!table_has_column($conn, 'boat_inventory', 'rental_price')) {
    $sql = "ALTER TABLE boat_inventory ADD COLUMN rental_price DECIMAL(10,2) DEFAULT NULL AFTER capacity";
    if ($conn->query($sql) === TRUE) {
        echo "Added column `rental_price`.\n";
    } else {
        echo "Failed to add `rental_price`: " . $conn->error . "\n";
    }
} else {
    echo "Column `rental_price` already exists.\n";
}

echo "Migration complete.\n";
?>