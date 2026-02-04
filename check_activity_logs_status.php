<?php
require 'config/db.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Activity Logs - Complete Setup & Verification</h1>";

// Step 1: Check if table exists
echo "<h2>Step 1: Checking Table Structure</h2>";
$result = $conn->query("SHOW TABLES LIKE 'activity_logs'");
if (!$result || $result->num_rows === 0) {
    echo "<p style='color: red;'>✗ Table does not exist. Creating...</p>";
    
    $sql = "CREATE TABLE activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type VARCHAR(50),
        user_name VARCHAR(255),
        activity_type VARCHAR(50),
        entity_type VARCHAR(50),
        entity_id INT,
        entity_name VARCHAR(255),
        description LONGTEXT,
        old_values JSON,
        new_values JSON,
        ip_address VARCHAR(45),
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_timestamp (timestamp),
        INDEX idx_activity_type (activity_type)
    )";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Table created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Table exists</p>";
}

// Step 2: Verify columns
echo "<h2>Step 2: Verifying Columns</h2>";
$result = $conn->query("DESCRIBE activity_logs");
$columns = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row['Type'];
    }
}

$required_columns = ['id', 'user_id', 'user_type', 'user_name', 'activity_type', 'entity_type', 'entity_id', 'entity_name', 'description', 'timestamp'];
$missing_columns = [];

foreach ($required_columns as $col) {
    if (isset($columns[$col])) {
        echo "✓ $col: {$columns[$col]}<br>";
    } else {
        echo "✗ MISSING: $col<br>";
        $missing_columns[] = $col;
    }
}

// Add missing columns
if (!empty($missing_columns)) {
    echo "<p style='color: orange;'>Adding missing columns...</p>";
    
    $column_defs = [
        'user_name' => 'ALTER TABLE activity_logs ADD COLUMN user_name VARCHAR(255) NULL AFTER user_type',
        'activity_type' => 'ALTER TABLE activity_logs ADD COLUMN activity_type VARCHAR(50) NULL',
        'entity_type' => 'ALTER TABLE activity_logs ADD COLUMN entity_type VARCHAR(50) NULL',
        'entity_id' => 'ALTER TABLE activity_logs ADD COLUMN entity_id INT NULL',
        'entity_name' => 'ALTER TABLE activity_logs ADD COLUMN entity_name VARCHAR(255) NULL',
        'description' => 'ALTER TABLE activity_logs ADD COLUMN description LONGTEXT NULL',
    ];
    
    foreach ($missing_columns as $col) {
        if (isset($column_defs[$col])) {
            if ($conn->query($column_defs[$col])) {
                echo "✓ Added $col<br>";
            } else {
                if (strpos($conn->error, 'Duplicate column') === false) {
                    echo "✗ Error adding $col: " . $conn->error . "<br>";
                }
            }
        }
    }
}

// Step 3: Check data
echo "<h2>Step 3: Checking Data</h2>";
$count_result = $conn->query("SELECT COUNT(*) as count FROM activity_logs");
$count = $count_result->fetch_assoc();
echo "<p>Total records in activity_logs: <strong>" . $count['count'] . "</strong></p>";

if ($count['count'] == 0) {
    echo "<p style='color: orange;'>⚠ No records found. This is normal if no activities have been logged yet.</p>";
    echo "<p>Records will appear here after:</p>";
    echo "<ul>";
    echo "<li>An admin or staff member logs in</li>";
    echo "<li>They perform an action (create, edit, delete something)</li>";
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✓ Data found. Showing latest 5 records:</p>";
    $result = $conn->query("SELECT id, user_id, user_type, user_name, activity_type, entity_name, timestamp FROM activity_logs ORDER BY id DESC LIMIT 5");
    if ($result) {
        echo "<table border='1' cellpadding='8' style='margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Type</th><th>Name</th><th>Activity</th><th>Entity</th><th>Timestamp</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>{$row['user_type']}</td>";
            echo "<td>" . ($row['user_name'] ?: '<span style="color: red;">NULL</span>') . "</td>";
            echo "<td>{$row['activity_type']}</td>";
            echo "<td>{$row['entity_name']}</td>";
            echo "<td>" . ($row['timestamp'] ? date('M d, Y g:ia', strtotime($row['timestamp'])) : 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Step 4: Check related tables
echo "<h2>Step 4: Checking Related Tables</h2>";
$tables = ['users', 'staff', 'customers'];
foreach ($tables as $tbl) {
    $result = $conn->query("SHOW TABLES LIKE '$tbl'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as c FROM $tbl")->fetch_assoc();
        echo "✓ <strong>$tbl</strong>: {$count['c']} records<br>";
    } else {
        echo "✗ <strong>$tbl</strong>: Table missing<br>";
    }
}

// Step 5: Verify activity_logger.php exists
echo "<h2>Step 5: Checking Activity Logger Function</h2>";
if (file_exists('handlers/activity_logger.php')) {
    echo "<p style='color: green;'>✓ handlers/activity_logger.php exists</p>";
    $content = file_get_contents('handlers/activity_logger.php');
    if (strpos($content, 'function logActivity') !== false) {
        echo "<p style='color: green;'>✓ logActivity function found</p>";
    } else {
        echo "<p style='color: red;'>✗ logActivity function NOT found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ handlers/activity_logger.php does not exist</p>";
}

echo "<h2>Setup Complete!</h2>";
echo "<p>To see activities in the logs:</p>";
echo "<ol>";
echo "<li>Go to <a href='activity_logs.php'>Activity Logs page</a></li>";
echo "<li>If empty, have a staff/admin user make an activity (create/edit/delete something)</li>";
echo "<li>Refresh the Activity Logs page</li>";
echo "</ol>";

?>
