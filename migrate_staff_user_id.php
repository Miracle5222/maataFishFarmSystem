<?php
/**
 * Migrate missing user_id values in staff table
 * This script attempts to match staff records to users records based on email
 */

require 'config/db.php';

echo "<h2>Staff Table Migration - Populate Missing user_id Values</h2>";

// First, let's see which staff records have missing/null user_id
echo "<h3>1. Staff records with missing user_id:</h3>";
$result = $conn->query("SELECT id, first_name, last_name, email, user_id FROM staff WHERE user_id IS NULL OR user_id = 0 ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " staff records with missing user_id<br><br>";
    
    $updated_count = 0;
    $errors = [];
    
    while ($row = $result->fetch_assoc()) {
        echo "Processing: {$row['first_name']} {$row['last_name']} (ID: {$row['id']}, Email: {$row['email']})<br>";
        
        // Try to find matching user by email
        $user_stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
        if ($user_stmt) {
            $user_stmt->bind_param('s', $row['email']);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_row = $user_result->fetch_assoc()) {
                $user_id = $user_row['id'];
                
                // Update staff record with user_id
                $update_stmt = $conn->prepare("UPDATE staff SET user_id = ? WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param('ii', $user_id, $row['id']);
                    if ($update_stmt->execute()) {
                        echo "  ✓ Updated staff ID {$row['id']} with user_id = {$user_id}<br>";
                        $updated_count++;
                    } else {
                        echo "  ✗ Failed to update: " . $update_stmt->error . "<br>";
                        $errors[] = "Staff ID {$row['id']}: " . $update_stmt->error;
                    }
                    $update_stmt->close();
                } else {
                    echo "  ✗ Prepare failed: " . $conn->error . "<br>";
                    $errors[] = "Staff ID {$row['id']}: Prepare failed";
                }
            } else {
                echo "  ⚠ No matching user found for email: {$row['email']}<br>";
            }
            $user_stmt->close();
        } else {
            echo "  ✗ Prepare failed: " . $conn->error . "<br>";
        }
    }
    
    echo "<br><h3>Summary:</h3>";
    echo "Updated: <strong>$updated_count</strong> staff records<br>";
    
    if (!empty($errors)) {
        echo "Errors: <strong>" . count($errors) . "</strong><br>";
        foreach ($errors as $error) {
            echo "- $error<br>";
        }
    }
} else {
    echo "No staff records with missing user_id found - all staff have user_id set!<br>";
}

// Show final status
echo "<h3>2. Final Staff Table Status:</h3>";
$result = $conn->query("
    SELECT 
        s.id, 
        s.first_name, 
        s.last_name, 
        s.email, 
        s.user_id, 
        u.username,
        u.full_name
    FROM staff s
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY s.id
");

if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>User ID</th><th>Username</th><th>User Full Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $user_id_display = $row['user_id'] ? $row['user_id'] : '<span style="color:red;">NULL</span>';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$user_id_display}</td>";
        echo "<td>{$row['username']}</td>";
        echo "<td>{$row['full_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

?>
