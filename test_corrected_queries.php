<?php
$conn = new mysqli('localhost', 'root', '', 'maata');

echo "Revenue Query Test (Corrected):\n\n";

// Test 1: Total completed cottage revenue
$result = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total FROM reservations WHERE reservation_type='cottage' AND status='completed'");
$row = $result->fetch_assoc();
echo "TOTAL COMPLETED COTTAGES:\n";
echo "  Count: {$row['cnt']}\n";
echo "  Revenue: ₱" . number_format($row['total'], 2) . "\n\n";

// Test 2: Walk-In (with LEFT JOIN fallback)
$query = "SELECT COUNT(*) as cnt, COALESCE(SUM(r.total_amount), 0) as total FROM reservations r LEFT JOIN activity_logs a ON a.entity_id = r.id AND a.entity_type = 'reservation' AND a.activity_type = 'CREATE' WHERE r.reservation_type = 'cottage' AND r.status = 'completed' AND (a.user_type IN ('admin', 'staff', 'manager') OR a.id IS NULL)";
$result = $conn->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "WALK-IN COTTAGES:\n";
    echo "  Count: {$row['cnt']}\n";
    echo "  Revenue: ₱" . number_format($row['total'], 2) . "\n\n";
} else {
    echo "Query error: " . $conn->error . "\n\n";
}

// Test 3: Online (with INNER JOIN - requires activity log)
$query = "SELECT COUNT(*) as cnt, COALESCE(SUM(r.total_amount), 0) as total FROM reservations r INNER JOIN activity_logs a ON a.entity_id = r.id AND a.entity_type = 'reservation' AND a.activity_type = 'CREATE' WHERE r.reservation_type = 'cottage' AND r.status = 'completed' AND a.user_type = 'customer'";
$result = $conn->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "ONLINE COTTAGES:\n";
    echo "  Count: {$row['cnt']}\n";
    echo "  Revenue: ₱" . number_format($row['total'], 2) . "\n\n";
} else {
    echo "Query error: " . $conn->error . "\n\n";
}

// Test 4: Boat rentals
$query = "SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total FROM boat_rentals WHERE status='completed'";
$result = $conn->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "TOTAL COMPLETED BOAT RENTALS:\n";
    echo "  Count: {$row['cnt']}\n";
    echo "  Revenue: ₱" . number_format($row['total'], 2) . "\n";
} else {
    echo "Query error: " . $conn->error . "\n";
}

$conn->close();
?>
