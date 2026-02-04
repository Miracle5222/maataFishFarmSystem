<?php
require 'config/db.php';

echo "<h1>Quick Activity Logs Fix</h1>";

// Check if user_name column exists
$columns = $conn->query("DESCRIBE activity_logs");
$has_user_name = false;

while ($col = $columns->fetch_assoc()) {
    if ($col['Field'] === 'user_name') {
        $has_user_name = true;
        break;
    }
}

if (!$has_user_name) {
    echo "<p>Adding user_name column to activity_logs...</p>";
    if ($conn->query("ALTER TABLE activity_logs ADD COLUMN user_name VARCHAR(255) NULL AFTER user_type")) {
        echo "<p style='color: green;'>✓ Column added</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ user_name column exists</p>";
}

// Show current structure
echo "<h2>Current Table Structure:</h2>";
$columns = $conn->query("DESCRIBE activity_logs");
echo "<ul>";
while ($col = $columns->fetch_assoc()) {
    echo "<li><code>" . $col['Field'] . "</code></li>";
}
echo "</ul>";

// Count records
$count = $conn->query("SELECT COUNT(*) as c FROM activity_logs")->fetch_assoc();
echo "<p>Total records: <strong>" . $count['c'] . "</strong></p>";

// Show recent
echo "<h2>Latest Records:</h2>";
$recent = $conn->query("SELECT id, user_id, user_type, user_name, activity_type, entity_name, timestamp FROM activity_logs ORDER BY id DESC LIMIT 5");
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>User ID</th><th>Type</th><th>Name</th><th>Activity</th><th>Entity</th></tr>";
while ($r = $recent->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$r['id']}</td>";
    echo "<td>{$r['user_id']}</td>";
    echo "<td>{$r['user_type']}</td>";
    echo "<td>" . ($r['user_name'] ?: '<em>NULL</em>') . "</td>";
    echo "<td>{$r['activity_type']}</td>";
    echo "<td>{$r['entity_name']}</td>";
    echo "</tr>";
}
echo "</table>";

?>
