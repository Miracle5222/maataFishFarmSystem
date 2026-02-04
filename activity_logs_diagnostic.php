<?php
/**
 * Diagnostic: Check activity logs table and data
 */

session_start();
require 'config/db.php';

echo "<h1>Activity Logs Diagnostic</h1>";
echo "<hr>";

// 1. Check table structure
echo "<h2>1. Table Structure</h2>";
$describe = $conn->query("DESCRIBE activity_logs");
echo "<table border='1' cellpadding='8'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while ($col = $describe->fetch_assoc()) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
}
echo "</table>";

// 2. Check record count
echo "<h2>2. Record Count</h2>";
$count = $conn->query("SELECT COUNT(*) as cnt FROM activity_logs")->fetch_assoc();
echo "<p>Total records: <strong>" . $count['cnt'] . "</strong></p>";

// 3. Show recent 5 records with exact columns
echo "<h2>3. Recent Records (Raw Data)</h2>";
$query = "SELECT 
            id, 
            user_id, 
            user_type, 
            user_name,
            activity_type, 
            entity_type, 
            entity_name, 
            description, 
            timestamp
         FROM activity_logs
         ORDER BY timestamp DESC 
         LIMIT 5";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='8' style='width: 100%;'>";
    echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Name</th><th>Activity</th><th>Entity</th><th>Entity Name</th><th>Description</th><th>Timestamp</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td>" . ($row['user_name'] ?? '<em>NULL</em>') . "</td>";
        echo "<td>{$row['activity_type']}</td>";
        echo "<td>{$row['entity_type']}</td>";
        echo "<td>{$row['entity_name']}</td>";
        echo "<td>" . substr($row['description'], 0, 40) . "...</td>";
        echo "<td>" . date('M d g:ia', strtotime($row['timestamp'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>No records found!</strong></p>";
}

// 4. Check session
echo "<h2>4. Current Session</h2>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
echo "<p>Role: " . ($_SESSION['role'] ?? 'NOT SET') . "</p>";
echo "<p>User Name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "</p>";

// 5. Test query that activity_logs.php uses
echo "<h2>5. Test Activity Logs Page Query</h2>";
echo "<p>This is the query activity_logs.php executes:</p>";
$test_query = "SELECT 
                al.id, 
                al.user_id, 
                al.user_type, 
                al.user_name,
                al.activity_type, 
                al.entity_type, 
                al.entity_name, 
                al.description, 
                al.timestamp
             FROM activity_logs al
             WHERE 1=1
             ORDER BY al.timestamp DESC 
             LIMIT 10";

echo "<pre style='background: #f0f0f0; padding: 10px;'>$test_query</pre>";

$test_result = $conn->query($test_query);
if ($test_result && $test_result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Query executed successfully</p>";
    echo "<p>Returned " . $test_result->num_rows . " rows</p>";
    
    // Check column count
    echo "<p>Number of columns per row: " . $test_result->field_count . "</p>";
} else {
    echo "<p style='color: red;'>✗ Query returned no results</p>";
}

?>
