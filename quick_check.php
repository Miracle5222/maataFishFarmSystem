<?php
/**
 * Quick database check script
 */
require 'config/db.php';

echo "=== STAFF TABLE ===\n";
$result = $conn->query("DESCRIBE staff");
if ($result) {
    echo "Structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['Field']}: {$row['Type']} (Null: {$row['Null']}, Key: {$row['Key']})\n";
    }
}

echo "\n=== STAFF DATA ===\n";
$result = $conn->query("SELECT * FROM staff LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "Sample staff record:\n";
    foreach ($row as $key => $value) {
        echo "  $key = " . ($value === null ? 'NULL' : $value) . "\n";
    }
}

echo "\n=== USERS TABLE ===\n";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "Structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['Field']}: {$row['Type']} (Null: {$row['Null']}, Key: {$row['Key']})\n";
    }
}

echo "\n=== USERS DATA ===\n";
$result = $conn->query("SELECT * FROM users LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "Sample user record:\n";
    foreach ($row as $key => $value) {
        echo "  $key = " . ($value === null ? 'NULL' : $value) . "\n";
    }
}

echo "\n=== ACTIVITY LOGS TABLE ===\n";
$result = $conn->query("DESCRIBE activity_logs");
if ($result) {
    echo "Structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['Field']}: {$row['Type']} (Null: {$row['Null']}, Key: {$row['Key']})\n";
    }
}

echo "\n=== SUMMARY ===\n";
$r1 = $conn->query("SELECT COUNT(*) as count FROM staff");
$c1 = $r1->fetch_assoc();
echo "Staff records: {$c1['count']}\n";

$r2 = $conn->query("SELECT COUNT(*) as count FROM users");
$c2 = $r2->fetch_assoc();
echo "User records: {$c2['count']}\n";

$r3 = $conn->query("SELECT COUNT(*) as count FROM activity_logs");
$c3 = $r3->fetch_assoc();
echo "Activity log records: {$c3['count']}\n";

$r4 = $conn->query("SELECT COUNT(*) as count FROM activity_logs WHERE user_type = 'staff'");
$c4 = $r4->fetch_assoc();
echo "Staff activities: {$c4['count']}\n";

$r5 = $conn->query("SELECT COUNT(*) as count FROM activity_logs WHERE user_type = 'admin'");
$c5 = $r5->fetch_assoc();
echo "Admin activities: {$c5['count']}\n";

?>
