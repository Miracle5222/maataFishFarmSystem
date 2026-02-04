<?php
require 'config/db.php';

echo "=== Activity Logs Table Migration ===\n\n";

// Check if column already exists
$check = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'activity_logs' AND COLUMN_NAME = 'user_name'");

if ($check && $check->num_rows > 0) {
    echo "✓ Column user_name already exists.\n";
} else {
    echo "→ Adding user_name column...\n";
    
    $sql = "ALTER TABLE activity_logs ADD COLUMN user_name VARCHAR(255) AFTER user_type";
    
    if ($conn->query($sql)) {
        echo "✓ Successfully added user_name column\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
        exit(1);
    }
}

// Verify
echo "\n✓ Current columns in activity_logs:\n";
$result = $conn->query('DESCRIBE activity_logs');
$columns = [];
while($col = $result->fetch_assoc()) {
    $columns[] = $col['Field'];
    echo "  - " . $col['Field'] . "\n";
}

echo "\n";
if (in_array('user_name', $columns)) {
    echo "✓ Migration successful! user_name column is ready.\n";
} else {
    echo "✗ Migration failed - user_name column not found.\n";
}
?>
