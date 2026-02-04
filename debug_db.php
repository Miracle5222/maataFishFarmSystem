<?php
include 'config/db.php';

echo "Checking reservations table structure...\n";

$result = $conn->query('DESCRIBE reservations');
if (!$result) {
    die('Error: ' . $conn->error);
}

$columns = [];
while($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\nChecking if cottage_id column exists: " . (in_array('cottage_id', $columns) ? 'YES' : 'NO') . "\n";
?>