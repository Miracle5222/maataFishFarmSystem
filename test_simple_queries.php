<?php
$conn = new mysqli('localhost', 'root', '', 'maata');

echo "Test Simple Queries:\n\n";

// Test 1: Just count all completed cottages
echo "1. Count all completed cottages:\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND status = 'completed'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   Count: {$row['cnt']}\n";
}

// Test 2: Count without JOIN
echo "\n2. Count without LEFT JOIN:\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   All reservations: {$row['cnt']}\n";
}

// Test 3: Check reservation types
echo "\n3. Check actual data:\n";
$result = $conn->query("
    SELECT r.id, r.reservation_number, r.reservation_type, r.status, r.total_amount 
    FROM reservations r 
    WHERE r.id IN (44, 45, 46)
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "   ID {$row['id']}: {$row['reservation_type']} | {$row['status']} | {$row['total_amount']}\n";
    }
} else {
    echo "   No data\n";
}

$conn->close();
?>
