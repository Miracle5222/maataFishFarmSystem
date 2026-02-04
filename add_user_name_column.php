<?php
require 'config/db.php';

echo "<h2>Adding user_name Column to Activity Logs</h2>";

// Check if column already exists
$result = $conn->query("DESCRIBE activity_logs");
$column_exists = false;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'user_name') {
            $column_exists = true;
            break;
        }
    }
}

if ($column_exists) {
    echo "<p style='color: green;'>✓ Column 'user_name' already exists in activity_logs table</p>";
} else {
    echo "<p>Adding 'user_name' column to activity_logs table...</p>";
    
    // Add the column - insert it after user_type
    $sql = "ALTER TABLE activity_logs ADD COLUMN user_name VARCHAR(255) NULL AFTER user_type";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Successfully added 'user_name' column</p>";
        
        // Now populate existing records with names from users table
        echo "<p>Populating existing activity logs with user names...</p>";
        $sql = "UPDATE activity_logs al
                JOIN users u ON al.user_id = u.id
                SET al.user_name = u.full_name
                WHERE al.user_name IS NULL OR al.user_name = ''";
        
        if ($conn->query($sql)) {
            $affected = $conn->affected_rows;
            echo "<p style='color: green;'>✓ Updated $affected activity log records with user names</p>";
        } else {
            echo "<p style='color: red;'>✗ Error updating records: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Error adding column: " . $conn->error . "</p>";
    }
}

// Show updated table structure
echo "<h3>Updated Activity Logs Table Structure</h3>";
$result = $conn->query("DESCRIBE activity_logs");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Sample Activity Log with user_name</h3>";
$result = $conn->query("SELECT id, user_id, user_type, user_name, activity_type, entity_name FROM activity_logs LIMIT 5");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>User Type</th><th>User Name</th><th>Activity</th><th>Entity</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $uname = $row['user_name'] ?: '<span style="color: red;">(empty)</span>';
        echo "<tr><td>{$row['id']}</td><td>{$row['user_id']}</td><td>{$row['user_type']}</td><td>$uname</td><td>{$row['activity_type']}</td><td>{$row['entity_name']}</td></tr>";
    }
    echo "</table>";
}

?>
