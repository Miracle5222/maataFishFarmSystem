<?php
/**
 * Test Activity Logging
 * This script tests if activity logging is working correctly
 */
require 'config/db.php';
require 'handlers/activity_logger.php';
require 'auth_admin.php';

echo "<h1>Activity Logging Test</h1>";

// Test 1: Direct logActivity call
echo "<h2>Test 1: Direct logActivity Call</h2>";
$result = logActivity(
    $conn,
    $_SESSION['user_id'] ?? 1,
    $_SESSION['role'] ?? 'admin',
    'CREATE',
    'test_entity',
    999,
    'Test Entity #999',
    'This is a test activity log entry'
);

echo "Result: " . ($result ? "SUCCESS ✓" : "FAILED ✗") . "<br>";

// Test 2: Check if the log was inserted
echo "<h2>Test 2: Verify Log Entry in Database</h2>";
$check = $conn->prepare('SELECT id, user_id, activity_type, entity_type FROM activity_logs WHERE entity_type = ? ORDER BY id DESC LIMIT 1');
if ($check) {
    $check->bind_param('s', $entity_type);
    $entity_type = 'test_entity';
    $check->execute();
    $res = $check->get_result();
    if ($row = $res->fetch_assoc()) {
        echo "Log Entry Found! ID: " . $row['id'] . "<br>";
        echo "User ID: " . $row['user_id'] . "<br>";
        echo "Activity Type: " . $row['activity_type'] . "<br>";
        echo "Entity Type: " . $row['entity_type'] . "<br>";
    } else {
        echo "No log entry found! ✗<br>";
    }
    $check->close();
} else {
    echo "Prepare failed: " . $conn->error . "<br>";
}

// Test 3: Check session variables
echo "<h2>Test 3: Session Variables</h2>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";

// Test 4: List recent activities
echo "<h2>Test 4: Recent Activity Logs</h2>";
$recent = $conn->query('SELECT id, user_id, activity_type, entity_type, entity_name, timestamp FROM activity_logs ORDER BY id DESC LIMIT 5');
if ($recent) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Activity</th><th>Entity</th><th>Name</th><th>Time</th></tr>";
    while ($row = $recent->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['activity_type'] . "</td>";
        echo "<td>" . $row['entity_type'] . "</td>";
        echo "<td>" . $row['entity_name'] . "</td>";
        echo "<td>" . $row['timestamp'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<a href='activity_logs.php'>View Full Activity Logs</a>";
?>
