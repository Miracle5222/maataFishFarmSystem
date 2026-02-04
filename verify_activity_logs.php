<?php
require 'config/db.php';

echo "<h1>Activity Logs Status Check</h1>";

// 1. Check table exists
echo "<h2>1. Table Check</h2>";
$tables = $conn->query("SHOW TABLES LIKE 'activity_logs'");
if ($tables->num_rows === 0) {
    echo "<p style='color: red;'>❌ Table does not exist - creating...</p>";
    $sql = "CREATE TABLE activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_type VARCHAR(50),
        user_name VARCHAR(255),
        activity_type VARCHAR(50),
        entity_type VARCHAR(50),
        entity_id INT,
        entity_name VARCHAR(255),
        description LONGTEXT,
        old_values JSON,
        new_values JSON,
        ip_address VARCHAR(45),
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id),
        INDEX(timestamp),
        INDEX(activity_type)
    )";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✅ Table created</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Table exists</p>";
}

// 2. Count records
echo "<h2>2. Data Check</h2>";
$result = $conn->query("SELECT COUNT(*) as c FROM activity_logs");
$row = $result->fetch_assoc();
echo "<p>Records in table: <strong>" . $row['c'] . "</strong></p>";

// 3. Show table structure
echo "<h2>3. Table Structure</h2>";
$cols = $conn->query("DESCRIBE activity_logs");
echo "<ul>";
while ($col = $cols->fetch_assoc()) {
    echo "<li><code>" . $col['Field'] . "</code> - " . $col['Type'] . "</li>";
}
echo "</ul>";

// 4. Test the query that activity_logs.php uses
echo "<h2>4. Query Test</h2>";
$test = $conn->query("SELECT id, user_id, user_type, user_name, activity_type, entity_name, timestamp FROM activity_logs ORDER BY timestamp DESC LIMIT 5");
if ($test) {
    echo "<p style='color: green;'>✅ Query works - " . $test->num_rows . " rows returned</p>";
    if ($test->num_rows > 0) {
        echo "<p style='color: blue;'><strong>Sample records:</strong></p>";
        while ($r = $test->fetch_assoc()) {
            echo "<pre>" . json_encode($r, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Query error: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<p><a href='activity_logs.php'>Back to Activity Logs</a></p>";
?>
