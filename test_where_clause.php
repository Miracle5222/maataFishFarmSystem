<?php
$conn = new mysqli('localhost', 'root', '', 'maata');

echo "Test WHERE Clause:\n\n";

// Test 1: Simple query without aggregate
echo "Without aggregate (LIMIT 5):\n";
$query = "
    SELECT r.id, r.reservation_number
    FROM reservations r
    LEFT JOIN activity_logs a ON a.entity_id = r.id AND a.entity_type = 'reservation' AND a.activity_type = 'CREATE'
    WHERE r.reservation_type = 'cottage' 
    AND r.status = 'completed'
    AND (a.user_type IN ('admin', 'staff', 'manager') OR a.id IS NULL)
    LIMIT 5
";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  ID {$row['id']}: {$row['reservation_number']}\n";
    }
    echo "  Total rows: " . $result->num_rows . "\n";
} else {
    echo "  No rows returned\n";
}

// Test 2: Check just the OR condition
echo "\nJust a.id IS NULL:\n";
$query = "
    SELECT COUNT(*) as cnt
    FROM reservations r
    LEFT JOIN activity_logs a ON a.entity_id = r.id AND a.entity_type = 'reservation' AND a.activity_type = 'CREATE'
    WHERE r.reservation_type = 'cottage' 
    AND r.status = 'completed'
    AND a.id IS NULL
";
$result = $conn->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "  Count: {$row['cnt']}\n";
} else {
    echo "  Query error: " . $conn->error . "\n";
}

// Test 3: Check the IN condition
echo "\nJust a.user_type IN condition:\n";
$query = "
    SELECT COUNT(*) as cnt
    FROM reservations r
    LEFT JOIN activity_logs a ON a.entity_id = r.id AND a.entity_type = 'reservation' AND a.activity_type = 'CREATE'
    WHERE r.reservation_type = 'cottage' 
    AND r.status = 'completed'
    AND a.user_type IN ('admin', 'staff', 'manager')
";
$result = $conn->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "  Count: {$row['cnt']}\n";
} else {
    echo "  Query error: " . $conn->error . "\n";
}

// Test 4: OR combined
echo "\nBoth conditions with OR:\n";
$query = "
    SELECT COUNT(*) as cnt
    FROM reservations r
    LEFT JOIN activity_logs a ON a.entity_id = r.id AND a.entity_type = 'reservation' AND a.activity_type = 'CREATE'
    WHERE r.reservation_type = 'cottage' 
    AND r.status = 'completed'
    AND (a.user_type IN ('admin', 'staff', 'manager') OR a.id IS NULL)
";
$result = $conn->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "  Count: {$row['cnt']}\n";
} else {
    echo "  Query error: " . $conn->error . "\n";
}

$conn->close();
?>
