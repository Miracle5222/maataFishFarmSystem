<?php
require 'config/db.php';

$q = $conn->query('SELECT COUNT(*) as cnt FROM activity_logs');
$r = $q->fetch_assoc();
echo 'Total records: ' . $r['cnt'] . "\n";

// Show last 3 records
echo "\nLast 3 records:\n";
$recent = $conn->query('SELECT id, user_name, activity_type, entity_type, entity_name, timestamp FROM activity_logs ORDER BY id DESC LIMIT 3');
while ($row = $recent->fetch_assoc()) {
    echo $row['id'] . ": " . $row['user_name'] . " - " . $row['activity_type'] . " " . $row['entity_type'] . " (" . $row['entity_name'] . ") - " . date('M d g:ia', strtotime($row['timestamp'])) . "\n";
}
?>
