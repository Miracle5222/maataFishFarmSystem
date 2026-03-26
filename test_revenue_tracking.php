<?php
// Test script for revenue tracking by online vs walk-in
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "maata";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== REVENUE TRACKING VERIFICATION ===\n\n";

// 1. Test: Cottage Reservations by Source
echo "1. COTTAGE RENTALS BREAKDOWN:\n";
echo "   Online Cottage Rentals (created by customers):\n";
$result = $conn->query("
    SELECT COUNT(*) as cnt, COALESCE(SUM(r.total_amount), 0) as revenue
    FROM reservations r
    INNER JOIN activity_logs a ON a.record_id = r.id AND a.record_type = 'reservation'
    WHERE r.reservation_type = 'cottage' 
    AND r.status = 'completed'
    AND a.user_type = 'customer'
    AND a.action = 'CREATE'
");
if ($result) {
    $row = $result->fetch_assoc();
    echo "      • Count: {$row['cnt']} | Revenue: ₱" . number_format($row['revenue'], 2) . "\n";
}

echo "   Walk-In Cottage Rentals (created by staff/admin):\n";
$result = $conn->query("
    SELECT COUNT(*) as cnt, COALESCE(SUM(r.total_amount), 0) as revenue
    FROM reservations r
    INNER JOIN activity_logs a ON a.record_id = r.id AND a.record_type = 'reservation'
    WHERE r.reservation_type = 'cottage' 
    AND r.status = 'completed'
    AND a.user_type IN ('admin', 'staff', 'manager')
    AND a.action = 'CREATE'
");
if ($result) {
    $row = $result->fetch_assoc();
    echo "      • Count: {$row['cnt']} | Revenue: ₱" . number_format($row['revenue'], 2) . "\n";
}

// 2. Test: Boat Rentals by Source
echo "\n2. BOAT RENTALS BREAKDOWN:\n";
echo "   Online Boat Rentals (created by customers):\n";
$result = $conn->query("
    SELECT COUNT(*) as cnt, COALESCE(SUM(b.total_amount), 0) as revenue
    FROM boat_rentals b
    INNER JOIN activity_logs a ON a.record_id = b.id AND a.record_type = 'boat_rentals'
    WHERE b.status = 'completed'
    AND a.user_type = 'customer'
    AND a.action = 'CREATE'
");
if ($result) {
    $row = $result->fetch_assoc();
    echo "      • Count: {$row['cnt']} | Revenue: ₱" . number_format($row['revenue'], 2) . "\n";
}

echo "   Walk-In Boat Rentals (created by staff/admin):\n";
$result = $conn->query("
    SELECT COUNT(*) as cnt, COALESCE(SUM(b.total_amount), 0) as revenue
    FROM boat_rentals b
    INNER JOIN activity_logs a ON a.record_id = b.id AND a.record_type = 'boat_rentals'
    WHERE b.status = 'completed'
    AND a.user_type IN ('admin', 'staff', 'manager')
    AND a.action = 'CREATE'
");
if ($result) {
    $row = $result->fetch_assoc();
    echo "      • Count: {$row['cnt']} | Revenue: ₱" . number_format($row['revenue'], 2) . "\n";
}

// 3. Verify Only Completed Reservations are Counted
echo "\n3. STATUS VERIFICATION:\n";
$result = $conn->query("
    SELECT r.status, COUNT(*) as cnt
    FROM reservations r
    WHERE r.reservation_type = 'cottage'
    GROUP BY r.status
");
if ($result && $result->num_rows > 0) {
    echo "   Cottage Reservation Status Counts:\n";
    while ($row = $result->fetch_assoc()) {
        echo "      • {$row['status']}: {$row['cnt']}\n";
    }
}

// 4. Show Recent Completed Reservations with Amounts
echo "\n4. RECENT COMPLETED COTTAGE RESERVATIONS:\n";
$result = $conn->query("
    SELECT r.id, r.reservation_number, r.total_amount, 
           CONCAT(c.first_name, ' ', c.last_name) as customer,
           a.user_type as created_by
    FROM reservations r
    JOIN customers c ON r.customer_id = c.id
    LEFT JOIN activity_logs a ON a.record_id = r.id AND a.record_type = 'reservation' AND a.action = 'CREATE'
    WHERE r.reservation_type = 'cottage' AND r.status = 'completed'
    ORDER BY r.updated_at DESC
    LIMIT 5
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $source = $row['created_by'] === 'customer' ? 'Online' : 'Walk-In';
        echo "      • {$row['reservation_number']} | {$row['customer']} | {$source} | ₱" . number_format($row['total_amount'], 2) . "\n";
    }
} else {
    echo "      No completed cottages found - create one and mark as completed to test\n";
}

// 5. Summary Statistics
echo "\n5. REVENUE SUMMARY:\n";

// Online Fish
$result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE customer_id IS NOT NULL");
$online_fish = ($result ? (float)$result->fetch_assoc()['total'] : 0);

// Online Cottage
$result = $conn->query("
    SELECT COALESCE(SUM(r.total_amount), 0) as total
    FROM reservations r
    INNER JOIN activity_logs a ON a.record_id = r.id AND a.record_type = 'reservation'
    WHERE r.reservation_type = 'cottage' AND r.status = 'completed' AND a.user_type = 'customer' AND a.action = 'CREATE'
");
$online_cottage = ($result ? (float)$result->fetch_assoc()['total'] : 0);

// Walk-In Cottage
$result = $conn->query("
    SELECT COALESCE(SUM(r.total_amount), 0) as total
    FROM reservations r
    INNER JOIN activity_logs a ON a.record_id = r.id AND a.record_type = 'reservation'
    WHERE r.reservation_type = 'cottage' AND r.status = 'completed' AND a.user_type IN ('admin','staff','manager') AND a.action = 'CREATE'
");
$walkin_cottage = ($result ? (float)$result->fetch_assoc()['total'] : 0);

// Online Boat
$result = $conn->query("
    SELECT COALESCE(SUM(b.total_amount), 0) as total
    FROM boat_rentals b
    INNER JOIN activity_logs a ON a.record_id = b.id AND a.record_type = 'boat_rentals'
    WHERE b.status = 'completed' AND a.user_type = 'customer' AND a.action = 'CREATE'
");
$online_boat = ($result ? (float)$result->fetch_assoc()['total'] : 0);

// Walk-In Boat
$result = $conn->query("
    SELECT COALESCE(SUM(b.total_amount), 0) as total
    FROM boat_rentals b
    INNER JOIN activity_logs a ON a.record_id = b.id AND a.record_type = 'boat_rentals'
    WHERE b.status = 'completed' AND a.user_type IN ('admin','staff','manager') AND a.action = 'CREATE'
");
$walkin_boat = ($result ? (float)$result->fetch_assoc()['total'] : 0);

echo "   Online Fish Orders:       ₱" . number_format($online_fish, 2) . "\n";
echo "   Online Cottage Rentals:   ₱" . number_format($online_cottage, 2) . "\n";
echo "   Walk-In Cottage Rentals:  ₱" . number_format($walkin_cottage, 2) . "\n";
echo "   Online Boat Rentals:      ₱" . number_format($online_boat, 2) . "\n";
echo "   Walk-In Boat Rentals:     ₱" . number_format($walkin_boat, 2) . "\n";
echo "   ────────────────────────\n";
echo "   TOTAL:                    ₱" . number_format($online_fish + $online_cottage + $walkin_cottage + $online_boat + $walkin_boat, 2) . "\n";

echo "\n✓ Revenue Tracking System Verification Complete\n";
$conn->close();
?>
