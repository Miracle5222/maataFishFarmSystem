<?php
session_start();
require 'config/db.php';

echo "<h2>Session Debug Info:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Availability Table Structure:</h2>";
$result = $conn->query("DESCRIBE availability");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Key</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Key']}</td><td>{$row['Extra']}</td></tr>";
}
echo "</table>";

echo "<h2>Test Direct Insert:</h2>";
$test_date = date('Y-m-d', strtotime('+5 days'));
$test_start = '10:00:00';
$test_end = '20:00:00';
$test_capacity = 50;
$test_status = 1;
$test_notes = 'Test record';

$stmt = $conn->prepare('
    INSERT INTO availability 
    (available_date, available_time_start, available_time_end, max_capacity, current_reservations, is_available, notes)
    VALUES (?, ?, ?, ?, 0, ?, ?)
');

if (!$stmt) {
    echo "Prepare Error: " . $conn->error;
} else {
    $stmt->bind_param('sssiis', $test_date, $test_start, $test_end, $test_capacity, $test_status, $test_notes);
    if ($stmt->execute()) {
        echo "Insert successful! New ID: " . $stmt->insert_id . "<br>";
        echo "Date: $test_date, Start: $test_start, End: $test_end<br>";
    } else {
        echo "Execute Error: " . $stmt->error;
    }
    $stmt->close();
}

echo "<h2>Current Records in Availability:</h2>";
$result = $conn->query("SELECT * FROM availability ORDER BY available_date DESC LIMIT 10");
echo "<table border='1'><tr><th>ID</th><th>Date</th><th>Start</th><th>End</th><th>Capacity</th><th>Status</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['available_date']}</td><td>{$row['available_time_start']}</td><td>{$row['available_time_end']}</td><td>{$row['max_capacity']}</td><td>{$row['is_available']}</td></tr>";
}
echo "</table>";

$conn->close();
?>
