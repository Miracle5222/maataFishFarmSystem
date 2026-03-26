<?php
require 'config/db.php';

echo "Adding 'is_manual' column to reservations table...\n";

// Check if column already exists
$check_column = $conn->query("SHOW COLUMNS FROM reservations LIKE 'is_manual'");
if ($check_column && $check_column->num_rows > 0) {
    echo "✓ Column 'is_manual' already exists.\n";
} else {
    $alter_query = "ALTER TABLE reservations ADD COLUMN is_manual TINYINT(1) DEFAULT 0 COMMENT 'Walk-in (manual): 1=manual/walk-in, 0=online booking'";
    if ($conn->query($alter_query)) {
        echo "✓ Column 'is_manual' added successfully.\n";
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
    }
}

// Verify the column was added
$verify = $conn->query("SHOW COLUMNS FROM reservations LIKE 'is_manual'");
if ($verify && $verify->num_rows > 0) {
    echo "\n✓ Verification successful. Column 'is_manual' is ready.\n";
    $col = $verify->fetch_assoc();
    echo "Type: " . $col['Type'] . "\n";
} else {
    echo "\n✗ Verification failed. Column not found.\n";
}

$conn->close();
?>
