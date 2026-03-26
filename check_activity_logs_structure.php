<?php
$conn = new mysqli('localhost', 'root', '', 'maata');

echo "Activity Logs Table Structure:\n";
$result = $conn->query("SHOW COLUMNS FROM activity_logs");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  • {$row['Field']} ({$row['Type']})\n";
    }
} else {
    echo "  Table doesn't exist or is empty\n";
}

echo "\nActivity Logs Sample Data:\n";
$result = $conn->query("SELECT * FROM activity_logs LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    foreach ($row as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
} else {
    echo "  No data in activity_logs\n";
}

$conn->close();
?>
