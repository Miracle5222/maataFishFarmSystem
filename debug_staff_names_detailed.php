<?php
require 'config/db.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Activity Logs Debug - Staff Names Issue</h2>";

// 1. Check staff activities in activity logs
echo "<h3>1. Recent Staff Activities:</h3>";
$result = $conn->query("
    SELECT id, user_id, user_type, activity_type, entity_name, timestamp 
    FROM activity_logs 
    WHERE user_type = 'staff' 
    ORDER BY id DESC 
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " staff activities<br><br>";
    while ($row = $result->fetch_assoc()) {
        echo "Activity ID: {$row['id']}, User ID: {$row['user_id']}, Type: {$row['user_type']}<br>";
    }
} else {
    echo "No staff activities found in activity_logs. Let me check admin activities first:<br>";
    $result = $conn->query("SELECT id, user_id, user_type FROM activity_logs LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "Activity ID: {$row['id']}, User ID: {$row['user_id']}, Type: {$row['user_type']}<br>";
        }
    }
}

// 2. Check staff table
echo "<h3>2. Staff Table Contents:</h3>";
$result = $conn->query("SELECT id, user_id, first_name, last_name, email FROM staff LIMIT 10");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>First Name</th><th>Last Name</th><th>Email</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $user_id = is_null($row['user_id']) ? 'NULL' : $row['user_id'];
        $fname = $row['first_name'] ?? '(empty)';
        $lname = $row['last_name'] ?? '(empty)';
        echo "<tr><td>{$row['id']}</td><td>{$user_id}</td><td>{$fname}</td><td>{$lname}</td><td>{$row['email']}</td></tr>";
    }
    echo "</table>";
}

// 3. Check users table
echo "<h3>3. Users Table Contents:</h3>";
$result = $conn->query("SELECT id, username, full_name, role FROM users LIMIT 10");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['username']}</td><td>{$row['full_name']}</td><td>{$row['role']}</td></tr>";
    }
    echo "</table>";
}

// 4. Test the JOIN query manually for a staff activity
echo "<h3>4. Testing JOIN Query for Staff Name Lookup:</h3>";
$test_id = null;

// Get a staff activity
$result = $conn->query("SELECT user_id FROM activity_logs WHERE user_type = 'staff' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $test_id = $row['user_id'];
    echo "Testing with user_id = {$test_id}<br><br>";
    
    // Test the JOIN query
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(NULLIF(CONCAT(TRIM(s.first_name), ' ', TRIM(s.last_name)), '  '), '') as staff_name,
            u.full_name,
            u.username,
            s.user_id as staff_user_id
        FROM users u
        LEFT JOIN staff s ON s.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    
    if ($stmt) {
        $stmt->bind_param('i', $test_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            echo "Query returned:<br>";
            echo "- staff_name: '" . ($row['staff_name'] ?? 'null') . "'<br>";
            echo "- full_name: '" . ($row['full_name'] ?? 'null') . "'<br>";
            echo "- username: '" . ($row['username'] ?? 'null') . "'<br>";
            echo "- staff_user_id: '" . ($row['staff_user_id'] ?? 'null') . "'<br>";
        } else {
            echo "Query returned NO ROWS!<br>";
        }
        $stmt->close();
    } else {
        echo "Prepare failed: " . $conn->error . "<br>";
    }
} else {
    echo "No staff activities found to test with<br>";
}

// 5. Let's check what user_id values are in activity_logs
echo "<h3>5. User ID Distribution in Activity Logs:</h3>";
$result = $conn->query("
    SELECT DISTINCT user_id, user_type, COUNT(*) as count
    FROM activity_logs
    GROUP BY user_id, user_type
    ORDER BY user_id
");

if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>User ID</th><th>User Type</th><th>Count</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['user_id']}</td><td>{$row['user_type']}</td><td>{$row['count']}</td></tr>";
    }
    echo "</table>";
}

?>
