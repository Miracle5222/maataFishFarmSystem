<?php
// Simple test to see what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/db.php';

echo "<h2>Activity Logs Diagnostic</h2>";

// Check connection
if ($conn) {
    echo "<p>✓ Database connected</p>";
} else {
    echo "<p>✗ Database NOT connected</p>";
    exit;
}

// Check if table exists
$result = $conn->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'maata' AND TABLE_NAME = 'activity_logs'");
if ($result && $result->num_rows > 0) {
    echo "<p>✓ activity_logs table exists</p>";
    
    // Count records
    $count = $conn->query("SELECT COUNT(*) as cnt FROM activity_logs");
    if ($count) {
        $row = $count->fetch_assoc();
        echo "<p>✓ Total records in activity_logs: " . $row['cnt'] . "</p>";
        
        if ($row['cnt'] == 0) {
            echo "<p><strong>Note:</strong> Table is empty. No data to display yet.</p>";
        } else {
            // Show first 5 records
            echo "<h3>Sample Records:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Activity</th><th>Entity</th><th>Timestamp</th></tr>";
            
            $sample = $conn->query("SELECT id, user_id, user_type, activity_type, entity_type, entity_name, timestamp FROM activity_logs ORDER BY timestamp DESC LIMIT 5");
            while ($rec = $sample->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $rec['id'] . "</td>";
                echo "<td>" . $rec['user_id'] . " (" . $rec['user_type'] . ")</td>";
                echo "<td>" . $rec['activity_type'] . "</td>";
                echo "<td>" . $rec['entity_type'] . "</td>";
                echo "<td>" . $rec['entity_name'] . "</td>";
                echo "<td>" . $rec['timestamp'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "<p>✗ activity_logs table DOES NOT EXIST</p>";
    echo "<p>You need to create it. Click <a href='create_activity_logs_table.php'>here</a> to create the table.</p>";
}

$conn->close();
?>
