<?php
require 'config/db.php';

echo "<h2>Activity Logs Table Structure</h2>";
$result = $conn->query("DESCRIBE activity_logs");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Sample Activity Log Record</h2>";
$result = $conn->query("SELECT * FROM activity_logs LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "<pre>";
    foreach ($row as $key => $value) {
        echo "$key: " . ($value ?? 'NULL') . "\n";
    }
    echo "</pre>";
}
?>
