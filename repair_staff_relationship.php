<?php
/**
 * Fix Staff-Users Relationship
 * This script ensures all staff members have proper user_id values
 */

require 'config/db.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Staff-Users Relationship Repair</h2>";

// 1. Check for staff with NULL user_id
echo "<h3>1. Staff with NULL user_id</h3>";
$result = $conn->query("SELECT id, first_name, last_name, email FROM staff WHERE user_id IS NULL OR user_id = 0");
if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " staff records with missing user_id<br><br>";
    
    while ($staff = $result->fetch_assoc()) {
        echo "Fixing staff #{$staff['id']}: {$staff['first_name']} {$staff['last_name']} ({$staff['email']})<br>";
        
        // Try to find matching user by email
        $user_result = $conn->query("SELECT id FROM users WHERE LOWER(email) = LOWER('" . $conn->real_escape_string($staff['email']) . "')");
        
        if ($user_result && $user_row = $user_result->fetch_assoc()) {
            $user_id = $user_row['id'];
            $update = $conn->query("UPDATE staff SET user_id = $user_id WHERE id = {$staff['id']}");
            if ($update) {
                echo "  ✓ Updated staff #{$staff['id']} with user_id = {$user_id}<br>";
            } else {
                echo "  ✗ Failed to update: " . $conn->error . "<br>";
            }
        } else {
            echo "  ⚠ No user found with email: {$staff['email']}<br>";
        }
    }
} else {
    echo "✓ All staff records have user_id set<br>";
}

// 2. Check for orphaned users (users with role=staff or role=manager but no staff record)
echo "<h3>2. Users without Staff Records</h3>";
$result = $conn->query("
    SELECT u.id, u.username, u.full_name, u.role 
    FROM users u
    LEFT JOIN staff s ON u.id = s.user_id
    WHERE u.role IN ('staff', 'manager') AND s.id IS NULL
");

if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " staff/manager users without staff records:<br><br>";
    while ($user = $result->fetch_assoc()) {
        echo "- User #{$user['id']}: {$user['username']} ({$user['full_name']}) - Role: {$user['role']}<br>";
    }
    echo "<p><strong>Note:</strong> These users might need staff records created, or they might be orphaned users.</p>";
} else {
    echo "✓ All staff/manager users have matching staff records<br>";
}

// 3. Summary
echo "<h3>3. Final Summary</h3>";
$staff_count = $conn->query("SELECT COUNT(*) as count FROM staff")->fetch_assoc();
$staff_with_user = $conn->query("SELECT COUNT(*) as count FROM staff WHERE user_id IS NOT NULL AND user_id != 0")->fetch_assoc();
$staff_with_names = $conn->query("SELECT COUNT(*) as count FROM staff WHERE (first_name != '' AND first_name IS NOT NULL) OR (last_name != '' AND last_name IS NOT NULL)")->fetch_assoc();

echo "Total staff records: <strong>" . $staff_count['count'] . "</strong><br>";
echo "Staff with user_id: <strong>" . $staff_with_user['count'] . "</strong><br>";
echo "Staff with names: <strong>" . $staff_with_names['count'] . "</strong><br>";

?>
