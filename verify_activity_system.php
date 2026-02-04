<?php
require 'config/db.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h1>✓ Activity Logging - Complete Verification</h1>";

// Step 1: Verify table structure
echo "<h2>1. Database Table Structure</h2>";
$describe = $conn->query("DESCRIBE activity_logs");
$required_cols = ['id', 'user_id', 'user_type', 'user_name', 'activity_type', 'entity_type', 'entity_id', 'entity_name', 'description', 'timestamp'];
$actual_cols = [];
$missing_cols = [];

while ($col = $describe->fetch_assoc()) {
    $actual_cols[] = $col['Field'];
}

foreach ($required_cols as $req) {
    if (!in_array($req, $actual_cols)) {
        $missing_cols[] = $req;
    }
}

if (empty($missing_cols)) {
    echo "<p style='color: green;'>✓ All required columns exist</p>";
} else {
    echo "<p style='color: red;'>✗ Missing columns: " . implode(', ', $missing_cols) . "</p>";
    foreach ($missing_cols as $col) {
        $type = 'VARCHAR(255)';
        if ($col === 'user_id' || $col === 'entity_id') $type = 'INT';
        if ($col === 'timestamp') $type = 'DATETIME';
        if ($col === 'old_values' || $col === 'new_values') $type = 'JSON';
        
        echo "<p>Adding $col...</p>";
        $sql = "ALTER TABLE activity_logs ADD COLUMN $col $type";
        if ($col === 'user_name') $sql .= " AFTER user_type";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Added $col</p>";
        } else {
            echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
        }
    }
}

// Step 2: Check handler includes
echo "<h2>2. Handler Files Check</h2>";
$handlers = [
    'handlers/product_update.php',
    'handlers/fish_update.php',
    'handlers/product_delete.php',
    'handlers/fish_delete.php'
];

foreach ($handlers as $handler) {
    if (file_exists($handler)) {
        $content = file_get_contents($handler);
        $has_logger = strpos($content, 'activity_logger.php') !== false;
        $has_logActivity = strpos($content, 'logActivity(') !== false;
        
        $status = $has_logger && $has_logActivity ? '✓' : '✗';
        $color = $has_logger && $has_logActivity ? 'green' : 'red';
        echo "<p style='color: $color;'>$status $handler - Logger: " . ($has_logger ? 'YES' : 'NO') . ", logActivity: " . ($has_logActivity ? 'YES' : 'NO') . "</p>";
    }
}

// Step 3: Test logActivity function
echo "<h2>3. Test logActivity Function</h2>";
require 'handlers/activity_logger.php';

$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['user_name'] = 'Test Admin';

$test = logActivity(
    $conn,
    1,
    'admin',
    'TEST',
    'test',
    1,
    'Test',
    'Testing activity logger function',
    null,
    null,
    'Test Admin'
);

if ($test) {
    echo "<p style='color: green;'>✓ logActivity() executed successfully</p>";
    
    // Check if it was actually inserted
    $check = $conn->query("SELECT * FROM activity_logs WHERE activity_type = 'TEST' AND entity_type = 'test' ORDER BY id DESC LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        echo "<p style='color: green;'>✓ Test record found in database</p>";
        echo "<p>Record ID: " . $row['id'] . ", User: " . $row['user_name'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Record NOT found in database after insert</p>";
    }
} else {
    echo "<p style='color: red;'>✗ logActivity() failed</p>";
}

// Step 4: Show recent activities
echo "<h2>4. Recent Activities in Database</h2>";
$recent = $conn->query("SELECT id, user_id, user_type, user_name, activity_type, entity_type, entity_name, timestamp FROM activity_logs ORDER BY timestamp DESC LIMIT 15");

if ($recent && $recent->num_rows > 0) {
    echo "<p>Showing <strong>" . $recent->num_rows . "</strong> records</p>";
    echo "<table border='1' cellpadding='8' style='width: 100%; margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>When</th><th>User</th><th>Type</th><th>Activity</th><th>Entity</th><th>Name</th></tr>";
    while ($r = $recent->fetch_assoc()) {
        $time = date('M d g:ia', strtotime($r['timestamp']));
        echo "<tr>";
        echo "<td>{$r['id']}</td>";
        echo "<td>$time</td>";
        echo "<td>{$r['user_id']}</td>";
        echo "<td>{$r['user_type']}</td>";
        echo "<td>{$r['activity_type']}</td>";
        echo "<td>{$r['entity_type']}</td>";
        echo "<td>" . ($r['user_name'] ?: '<em style="color: red;">NULL</em>') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No records found</p>";
}

// Step 5: Summary
echo "<h2>5. Summary</h2>";
echo "<p>✓ All checks complete!</p>";
echo "<p>To test:</p>";
echo "<ol>";
echo "<li>Go to Products or Fish Species</li>";
echo "<li>Create or edit something</li>";
echo "<li>Check Activity Logs page</li>";
echo "<li>New activity should appear with your name</li>";
echo "</ol>";

?>
