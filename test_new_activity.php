<?php
session_start();
require 'config/db.php';
require 'handlers/activity_logger.php';

// Simulate a logged-in admin
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['user_name'] = 'Test Administrator';

echo "=== Testing Activity Logging System ===\n\n";

// Log a test activity
echo "1. Logging test activity...\n";
$result = logActivity(
    $conn,
    1,
    'admin',
    'EDIT',
    'product',
    999,
    'Test Product for Verification',
    'This is a test activity to verify logging is working',
    ['old_price' => '100'],
    ['new_price' => '150'],
    'Test Administrator'
);

if ($result) {
    echo "   ✓ Activity logged successfully\n";
} else {
    echo "   ✗ Failed to log activity\n";
}

// Check if it was inserted
echo "\n2. Verifying record was inserted...\n";
$check = $conn->query("SELECT * FROM activity_logs WHERE entity_type = 'product' AND entity_id = 999 ORDER BY id DESC LIMIT 1");

if ($check && $check->num_rows > 0) {
    $record = $check->fetch_assoc();
    echo "   ✓ Record found!\n";
    echo "     - ID: " . $record['id'] . "\n";
    echo "     - User Name: " . ($record['user_name'] ?? 'NULL') . "\n";
    echo "     - Activity: " . $record['activity_type'] . "\n";
    echo "     - Entity: " . $record['entity_type'] . " (" . $record['entity_name'] . ")\n";
    echo "     - Timestamp: " . date('M d g:ia', strtotime($record['timestamp'])) . "\n";
} else {
    echo "   ✗ Record NOT found after insert!\n";
}

// Show all recent records
echo "\n3. Recent activity log entries:\n";
$recent = $conn->query("SELECT id, user_name, activity_type, entity_type, entity_name, timestamp FROM activity_logs ORDER BY id DESC LIMIT 5");
$count = 0;
while ($row = $recent->fetch_assoc()) {
    $count++;
    $user = $row['user_name'] ?? 'Unknown';
    echo "   $count. $user - " . $row['activity_type'] . " " . $row['entity_type'] . " (" . $row['entity_name'] . ") - " . date('M d g:ia', strtotime($row['timestamp'])) . "\n";
}

echo "\n✓ Test complete!\n";
echo "→ Activity logs page should now display correctly\n";
echo "→ New activities will have user_name populated\n";
?>
