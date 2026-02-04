<?php
/**
 * Complete Diagnostic for Staff Name Display Issue
 */

require 'config/db.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs - Staff Name Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin: 20px 0; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; }
        .success { color: green; }
        .warning { color: orange; }
        h2 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        h3 { color: #666; margin-top: 30px; }
        code { background-color: #f4f4f4; padding: 2px 5px; }
    </style>
</head>
<body>

<h1>Activity Logs - Staff Name Display Diagnostic</h1>

<?php

// 1. Check activity logs with staff activities
echo "<h2>1. Recent Activity Logs - All Users</h2>";
$result = $conn->query("
    SELECT id, user_id, user_type, activity_type, entity_name, timestamp
    FROM activity_logs
    ORDER BY id DESC
    LIMIT 20
");

if ($result) {
    echo "<table>";
    echo "<tr><th>ID</th><th>User ID</th><th>User Type</th><th>Activity</th><th>Entity</th><th>Timestamp</th></tr>";
    $staff_count = 0;
    $admin_count = 0;
    while ($row = $result->fetch_assoc()) {
        if ($row['user_type'] === 'staff') $staff_count++;
        if ($row['user_type'] === 'admin') $admin_count++;
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td><strong>{$row['user_id']}</strong></td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td>{$row['activity_type']}</td>";
        echo "<td>{$row['entity_name']}</td>";
        echo "<td>{$row['timestamp']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>Summary: <strong>$staff_count staff activities</strong>, <strong>$admin_count admin activities</strong></p>";
}

// 2. Check staff table
echo "<h2>2. Staff Table - All Records</h2>";
$result = $conn->query("SELECT id, user_id, first_name, last_name, email FROM staff");
if ($result) {
    echo "<p>Total staff records: <strong>" . $result->num_rows . "</strong></p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>User ID</th><th>First Name</th><th>Last Name</th><th>Email</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $uid = is_null($row['user_id']) ? '<span class="error">NULL</span>' : $row['user_id'];
        $fname = $row['first_name'] ?: '<span class="warning">(empty)</span>';
        $lname = $row['last_name'] ?: '<span class="warning">(empty)</span>';
        echo "<tr><td>{$row['id']}</td><td>$uid</td><td>$fname</td><td>$lname</td><td>{$row['email']}</td></tr>";
    }
    echo "</table>";
}

// 3. Check users table
echo "<h2>3. Users Table - All Records</h2>";
$result = $conn->query("SELECT id, username, full_name, role FROM users");
if ($result) {
    echo "<p>Total user records: <strong>" . $result->num_rows . "</strong></p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $fname = $row['full_name'] ?: '<span class="warning">(empty)</span>';
        echo "<tr><td>{$row['id']}</td><td>{$row['username']}</td><td>$fname</td><td>{$row['role']}</td></tr>";
    }
    echo "</table>";
}

// 4. Test the lookup queries with actual data
echo "<h2>4. Test Staff Lookup Queries</h2>";

// Get a staff activity
$result = $conn->query("SELECT user_id FROM activity_logs WHERE user_type = 'staff' ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $test_user_id = $row['user_id'];
    echo "<p>Testing with staff activity user_id = <strong>$test_user_id</strong></p>";
    
    echo "<h3>A. Query 1: Direct staff table lookup</h3>";
    $stmt = $conn->prepare("SELECT first_name, last_name FROM staff WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $test_user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $fname = trim($row['first_name'] ?? '');
            $lname = trim($row['last_name'] ?? '');
            echo "<p><span class='success'>✓ FOUND in staff table</span></p>";
            echo "<p>First Name: '<strong>$fname</strong>'</p>";
            echo "<p>Last Name: '<strong>$lname</strong>'</p>";
            if (!empty($fname) || !empty($lname)) {
                $result_name = trim($fname . ' ' . $lname);
                echo "<p>Result Name: '<strong>$result_name</strong>'</p>";
            } else {
                echo "<p><span class='error'>× Both names are empty!</span></p>";
            }
        } else {
            echo "<p><span class='error'>✗ NOT FOUND in staff table</span></p>";
            echo "<p>Query: <code>SELECT first_name, last_name FROM staff WHERE user_id = $test_user_id</code></p>";
        }
        $stmt->close();
    }
    
    echo "<h3>B. Query 2: Fallback to users table</h3>";
    $stmt = $conn->prepare("SELECT full_name, username FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $test_user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            echo "<p><span class='success'>✓ FOUND in users table</span></p>";
            echo "<p>Full Name: '<strong>" . ($row['full_name'] ?? '(empty)') . "</strong>'</p>";
            echo "<p>Username: '<strong>" . ($row['username'] ?? '(empty)') . "</strong>'</p>";
        } else {
            echo "<p><span class='error'>✗ NOT FOUND in users table</span></p>";
        }
        $stmt->close();
    }
    
    echo "<h3>C. Query 3: Check if there's a mismatch</h3>";
    $query = "SELECT s.id, s.user_id, s.first_name, s.last_name, u.id as user_id_from_users, u.full_name, u.username
              FROM staff s
              LEFT JOIN users u ON s.user_id = u.id
              WHERE s.user_id = ? OR u.id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('ii', $test_user_id, $test_user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        echo "<table>";
        echo "<tr><th>Staff ID</th><th>Staff User ID</th><th>Staff Name</th><th>Users ID</th><th>Users Full Name</th></tr>";
        if ($res->num_rows > 0) {
            while ($r = $res->fetch_assoc()) {
                $staff_name = ($r['first_name'] ? $r['first_name'] : '') . ' ' . ($r['last_name'] ? $r['last_name'] : '');
                echo "<tr>";
                echo "<td>{$r['id']}</td>";
                echo "<td>{$r['user_id']}</td>";
                echo "<td>$staff_name</td>";
                echo "<td>" . ($r['user_id_from_users'] ?: 'NULL') . "</td>";
                echo "<td>" . ($r['full_name'] ?: 'NULL') . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'><span class='error'>No matching records found</span></td></tr>";
        }
        echo "</table>";
        $stmt->close();
    }
} else {
    echo "<p><span class='warning'>No staff activities found in activity_logs to test with</span></p>";
}

// 5. List all user_ids in activity_logs that don't have matching names
echo "<h2>5. Problem Activities - User IDs with No Matching Names</h2>";
$result = $conn->query("SELECT DISTINCT user_id, user_type FROM activity_logs WHERE user_type = 'staff' ORDER BY user_id");
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>User ID</th><th>User Type</th><th>Staff Name Found?</th><th>Users Name Found?</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $uid = $row['user_id'];
        $type = $row['user_type'];
        
        // Check staff table
        $stmt = $conn->prepare("SELECT first_name, last_name FROM staff WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $staff_found = ($res->num_rows > 0) ? '<span class="success">✓ YES</span>' : '<span class="error">✗ NO</span>';
        $stmt->close();
        
        // Check users table
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $users_found = ($res->num_rows > 0) ? '<span class="success">✓ YES</span>' : '<span class="error">✗ NO</span>';
        $stmt->close();
        
        echo "<tr><td>$uid</td><td>$type</td><td>$staff_found</td><td>$users_found</td></tr>";
    }
    echo "</table>";
}

?>

</body>
</html>
