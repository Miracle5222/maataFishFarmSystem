<?php
require 'config/db.php';

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'activity_logs'");
if ($result && $result->num_rows > 0) {
    echo "✓ activity_logs table exists\n";
} else {
    echo "✗ activity_logs table does NOT exist\n";
    exit;
}

// Count records
$count_result = $conn->query("SELECT COUNT(*) as total FROM activity_logs");
if ($count_result) {
    $row = $count_result->fetch_assoc();
    echo "Total records: " . $row['total'] . "\n";
} else {
    echo "Error counting records: " . $conn->error . "\n";
}

// Show sample records
echo "\nSample records:\n";
$sample = $conn->query("SELECT id, user_id, user_type, activity_type, entity_type, entity_name, timestamp FROM activity_logs ORDER BY timestamp DESC LIMIT 5");
if ($sample && $sample->num_rows > 0) {
    while ($row = $sample->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | User: " . $row['user_id'] . " (" . $row['user_type'] . ") | Activity: " . $row['activity_type'] . " | Entity: " . $row['entity_type'] . " - " . $row['entity_name'] . " | Time: " . $row['timestamp'] . "\n";
    }
} else {
    echo "No records found\n";
    if ($sample === false) {
        echo "Query error: " . $conn->error . "\n";
    }
}

$conn->close();
?>
