<?php
require 'config/db.php';

echo "<h2>Debug: Staff Table and Activity Logs</h2>";

// Check staff table structure and data
echo "<h3>1. Staff Table Data:</h3>";
$result = $conn->query("SELECT id, user_id, first_name, last_name FROM staff LIMIT 10");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>First Name</th><th>Last Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['first_name']}</td>";
        echo "<td>{$row['last_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// Check users table
echo "<h3>2. Users Table Data:</h3>";
$result = $conn->query("SELECT id, username, full_name, role FROM users LIMIT 10");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['username']}</td>";
        echo "<td>{$row['full_name']}</td>";
        echo "<td>{$row['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// Check activity logs with staff activities
echo "<h3>3. Recent Activity Logs (Staff Activities):</h3>";
$result = $conn->query("SELECT id, user_id, user_type, activity_type, entity_name, timestamp FROM activity_logs WHERE user_type = 'staff' ORDER BY id DESC LIMIT 10");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>User Type</th><th>Activity Type</th><th>Entity</th><th>Timestamp</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td>{$row['activity_type']}</td>";
        echo "<td>{$row['entity_name']}</td>";
        echo "<td>{$row['timestamp']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// Test staff lookup query for a specific user
echo "<h3>4. Test Staff Lookup Query:</h3>";
$test_user_id = 5; // Change this to test different users
$stmt = $conn->prepare("SELECT first_name, last_name FROM staff WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $test_user_id);
$stmt->execute();
$result = $stmt->get_result();
echo "Testing for user_id = {$test_user_id}:<br>";
if ($row = $result->fetch_assoc()) {
    echo "Found: {$row['first_name']} {$row['last_name']}";
} else {
    echo "Not found in staff table";
}
$stmt->close();

// Test users lookup
echo "<h3>5. Test Users Table Lookup:</h3>";
$stmt = $conn->prepare("SELECT full_name, username FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $test_user_id);
$stmt->execute();
$result = $stmt->get_result();
echo "Testing for user_id = {$test_user_id}:<br>";
if ($row = $result->fetch_assoc()) {
    echo "Found: {$row['full_name']} ({$row['username']})";
} else {
    echo "Not found in users table";
}
$stmt->close();

?>
