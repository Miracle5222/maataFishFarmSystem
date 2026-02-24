<?php
// Run this script once (via browser or CLI) to add `num_people` to `boat_rentals`
require __DIR__ . '/config/db.php';
header('Content-Type: text/plain');

function table_has_column($conn, $table, $column) {
    $res = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "` LIKE '" . $conn->real_escape_string($column) . "'");
    return $res && $res->num_rows > 0;
}

echo "Starting migration for boat_rentals...\n";

if (!table_has_column($conn, 'boat_rentals', 'num_people')) {
    $sql = "ALTER TABLE boat_rentals ADD COLUMN num_people INT DEFAULT 1 AFTER total_amount";
    if ($conn->query($sql) === TRUE) {
        echo "Added column `num_people`.\n";
    } else {
        echo "Failed to add `num_people`: " . $conn->error . "\n";
    }
} else {
    echo "Column `num_people` already exists.\n";
}

if (table_has_column($conn, 'boat_rentals', 'customer_id')) {
    $res = $conn->query("SHOW FULL COLUMNS FROM boat_rentals WHERE Field = 'customer_id'");
    if ($res && $row = $res->fetch_assoc()) {
        if (strtoupper($row['Null']) === 'YES') {
            echo "`customer_id` is already nullable.\n";
        } else {
            $sql = "ALTER TABLE boat_rentals MODIFY COLUMN customer_id INT NULL";
            if ($conn->query($sql) === TRUE) {
                echo "Made `customer_id` nullable.\n";
            } else {
                echo "Failed to modify `customer_id`: " . $conn->error . "\n";
            }
        }
    }
} else {
    echo "`customer_id` column does not exist; skipping modification.\n";
}

echo "Migration complete.\n";

?>