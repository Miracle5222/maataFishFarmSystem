<?php
require 'config/db.php';

echo "<h1>Activity Logs Diagnostic</h1>";

// 1. Check table structure
echo "<h2>1. Activity Logs Table Structure</h2>";
$result = $conn->query("DESCRIBE activity_logs");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    $has_user_name = false;
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'user_name') $has_user_name = true;
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key'] ?: '-'}</td>";
        echo "<td>{$row['Default'] ?: '-'}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!$has_user_name) {
        echo "<p style='color: red;'><strong>⚠ PROBLEM: user_name column missing!</strong></p>";
        echo "<p>Adding column...</p>";
        if ($conn->query("ALTER TABLE activity_logs ADD COLUMN user_name VARCHAR(255) NULL AFTER user_type")) {
            echo "<p style='color: green;'>✓ Column added successfully</p>";
        } else {
            echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ user_name column exists</p>";
    }
}

// 2. Test data
echo "<h2>2. Recent Activity Logs</h2>";
$result = $conn->query("SELECT id, user_id, user_type, user_name, activity_type, entity_name, timestamp FROM activity_logs ORDER BY id DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<p>Found <strong>" . $result->num_rows . "</strong> records</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Type</th><th>Name</th><th>Activity</th><th>Entity</th><th>When</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $time = date('M d g:ia', strtotime($row['timestamp']));
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td>" . ($row['user_name'] ?: '<em style="color:red;">NULL</em>') . "</td>";
        echo "<td>{$row['activity_type']}</td>";
        echo "<td>{$row['entity_name']}</td>";
        echo "<td>$time</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No records found</p>";
}

// 3. Test the logActivity function
echo "<h2>3. Testing logActivity Function</h2>";
echo "<p>Attempting to log a test activity...</p>";

// Simulate session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['user_name'] = 'Test Admin';

require 'handlers/activity_logger.php';

$test_result = logActivity(
    $conn,
    1,
    'admin',
    'TEST',
    'test_entity',
    999,
    'Test Entity',
    'This is a test activity',
    ['old' => 'value'],
    ['new' => 'value'],
    'Test Admin'
);

if ($test_result) {
    echo "<p style='color: green;'>✓ Test activity logged successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to log test activity</p>";
}

// 4. Check errors log
echo "<h2>4. Recent PHP Errors</h2>";
$error_log = file_exists('../php_errors.log') ? '../php_errors.log' : (file_exists('php_errors.log') ? 'php_errors.log' : null);
if ($error_log && file_exists($error_log)) {
    echo "<p>Checking error log...</p>";
    $lines = file($error_log, FILE_IGNORE_NEW_LINES);
    $recent = array_slice($lines, -20);
    echo "<pre style='background: #f4f4f4; padding: 10px; overflow-x: auto; max-height: 300px;'>";
    foreach ($recent as $line) {
        if (strpos($line, 'activity_logger') !== false || strpos($line, 'logActivity') !== false) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>Error log not found or no errors</p>";
}

?>
