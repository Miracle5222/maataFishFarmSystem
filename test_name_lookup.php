<?php
/**
 * Test the exact code from activity_logs.php
 */
require 'config/db.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Testing Activity Logs Name Lookup Code</h2>";

// Get the most recent staff activity
$sql = "SELECT id, user_id, user_type FROM activity_logs WHERE user_type = 'staff' ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    echo "<p class='error'>No staff activities found in activity_logs</p>";
    echo "<p>Let me check what activities we have:</p>";
    $result = $conn->query("SELECT user_id, user_type, COUNT(*) as count FROM activity_logs GROUP BY user_id, user_type");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  - User #{$row['user_id']}, Type: {$row['user_type']}, Count: {$row['count']}<br>";
        }
    }
    exit;
}

$activity = $result->fetch_assoc();
$row = $activity;  // Simulate what activity_logs.php does

echo "<p><strong>Testing with activity:</strong></p>";
echo "- ID: {$row['id']}<br>";
echo "- User ID: {$row['user_id']}<br>";
echo "- User Type: {$row['user_type']}<br><br>";

// Now run the EXACT code from activity_logs.php
echo "<h3>Running Name Lookup Code</h3>";

$person_name = null;
if ($row['user_type'] === 'admin' || $row['user_type'] === 'staff') {
    echo "<p>User type is: {$row['user_type']}</p>";
    
    // For staff/admin: Try to get from staff table with user_id
    echo "<p>1. Trying staff table with user_id = {$row['user_id']}</p>";
    $user_stmt = $conn->prepare("SELECT first_name, last_name FROM staff WHERE user_id = ? LIMIT 1");
    if ($user_stmt) {
        $user_stmt->bind_param('i', $row['user_id']);
        $execute_result = $user_stmt->execute();
        echo "   - Query executed: " . ($execute_result ? "YES" : "NO (ERROR: " . $user_stmt->error . ")") . "<br>";
        
        $user_result = $user_stmt->get_result();
        echo "   - Result set retrieved<br>";
        
        if ($user_row = $user_result->fetch_assoc()) {
            echo "   ✓ Found in staff table!<br>";
            echo "     - first_name: '" . ($user_row['first_name'] ?? 'NULL') . "'<br>";
            echo "     - last_name: '" . ($user_row['last_name'] ?? 'NULL') . "'<br>";
            
            $fname = trim($user_row['first_name'] ?? '');
            $lname = trim($user_row['last_name'] ?? '');
            echo "     - After trim: fname='" . $fname . "', lname='" . $lname . "'<br>";
            
            if (!empty($fname) || !empty($lname)) {
                $person_name = trim($fname . ' ' . $lname);
                echo "     - Final person_name: '" . $person_name . "'<br>";
            } else {
                echo "     - Both names empty, person_name remains null<br>";
            }
        } else {
            echo "   ✗ NOT found in staff table<br>";
        }
        $user_stmt->close();
    } else {
        echo "   ✗ Prepare failed: " . $conn->error . "<br>";
    }
    
    // Fallback to users table if staff lookup failed
    if (empty($person_name)) {
        echo "<p>2. Fallback: Trying users table with id = {$row['user_id']}</p>";
        $user_stmt = $conn->prepare("SELECT full_name, username FROM users WHERE id = ? LIMIT 1");
        if ($user_stmt) {
            $user_stmt->bind_param('i', $row['user_id']);
            $execute_result = $user_stmt->execute();
            echo "   - Query executed: " . ($execute_result ? "YES" : "NO (ERROR: " . $user_stmt->error . ")") . "<br>";
            
            $user_result = $user_stmt->get_result();
            if ($user_row = $user_result->fetch_assoc()) {
                echo "   ✓ Found in users table!<br>";
                echo "     - full_name: '" . ($user_row['full_name'] ?? 'NULL') . "'<br>";
                echo "     - username: '" . ($user_row['username'] ?? 'NULL') . "'<br>";
                
                $person_name = trim($user_row['full_name'] ?? $user_row['username'] ?? '');
                echo "     - Final person_name: '" . $person_name . "'<br>";
            } else {
                echo "   ✗ NOT found in users table<br>";
            }
            $user_stmt->close();
        } else {
            echo "   ✗ Prepare failed: " . $conn->error . "<br>";
        }
    }
}

echo "<h3>Final Result</h3>";
if (!empty($person_name)) {
    $person = htmlspecialchars($person_name . ' (' . ucfirst($row['user_type']) . ')');
    echo "<p><strong>Person Display:</strong> " . $person . "</p>";
    echo "<p style='color: green;'>✓ SUCCESS - Name will show in activity logs</p>";
} else {
    $person = htmlspecialchars(ucfirst($row['user_type']));
    echo "<p><strong>Person Display:</strong> " . $person . "</p>";
    echo "<p style='color: red;'>✗ FAILED - Only role will show, name is blank</p>";
    echo "<p><strong>ROOT CAUSE:</strong> The staff record doesn't have a user_id that matches the user_id in activity_logs</p>";
}

?>
