<?php
// Test script to verify cottage pricing and revenue implementation
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "maata";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== COTTAGE PRICING & REVENUE SYSTEM TEST ===\n\n";

// 1. Verify total_amount column exists
echo "1. Checking total_amount column in reservations table...\n";
$result = $conn->query("SHOW COLUMNS FROM reservations WHERE Field = 'total_amount'");
if ($result && $result->num_rows > 0) {
    $column = $result->fetch_assoc();
    echo "   ✓ Column exists: " . $column['Field'] . " (" . $column['Type'] . ")\n\n";
} else {
    echo "   ✗ Column does NOT exist\n\n";
}

// 2. Check cottage prices in database
echo "2. Checking cottage prices in database...\n";
$cottages = $conn->query("SELECT id, cottage_number, price FROM cottages ORDER BY id");
if ($cottages && $cottages->num_rows > 0) {
    while ($cottage = $cottages->fetch_assoc()) {
        echo "   • Cottage {$cottage['cottage_number']}: ₱" . number_format($cottage['price'], 2) . "/hour\n";
    }
    echo "\n";
} else {
    echo "   ✗ No cottages found\n\n";
}

// 3. Check recent cottage reservations and their total_amount
echo "3. Checking recent cottage reservation records...\n";
$result = $conn->query("
    SELECT r.id, r.reservation_number, r.status, r.total_amount, 
           CONCAT(c.first_name, ' ', c.last_name) as customer, 
           r.reservation_date, r.reservation_time
    FROM reservations r
    JOIN customers c ON r.customer_id = c.id
    WHERE r.reservation_type = 'cottage'
    ORDER BY r.created_at DESC
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    while ($res = $result->fetch_assoc()) {
        echo "   • Res #{$res['reservation_number']} | {$res['customer']} | Status: {$res['status']} | Amount: ₱" . number_format($res['total_amount'], 2) . "\n";
    }
    echo "\n";
} else {
    echo "   No cottage reservations found yet\n\n";
}

// 4. Calculate current cottage revenue
echo "4. Calculating total cottage rental revenue...\n";
$result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM reservations WHERE reservation_type = 'cottage' AND status IN ('confirmed', 'completed')");
if ($result) {
    $row = $result->fetch_assoc();
    $total = floatval($row['total']);
    echo "   ✓ Total cottage rental revenue: ₱" . number_format($total, 2) . "\n";
    
    $count_result = $conn->query("SELECT COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND status IN ('confirmed', 'completed')");
    if ($count_result) {
        $count_row = $count_result->fetch_assoc();
        echo "   ✓ Number of completed cottage rentals: " . $count_row['cnt'] . "\n\n";
    }
} else {
    echo "   ✗ Error calculating revenue\n\n";
}

// 5. Verify handler files are updated
echo "5. Checking if handlers are updated with total_amount logic...\n";
$manual_handler = file_get_contents('../handlers/manual_cottage_reservation_handler.php');
$booking_handler = file_get_contents('../handlers/booking_handler.php');

if (strpos($manual_handler, 'total_amount = $cottage_price * 2') !== false) {
    echo "   ✓ manual_cottage_reservation_handler.php includes total_amount calculation\n";
} else {
    echo "   ✗ manual_cottage_reservation_handler.php NOT updated\n";
}

if (strpos($booking_handler, 'total_amount = $cottage_price * 2') !== false) {
    echo "   ✓ booking_handler.php includes total_amount calculation\n";
} else {
    echo "   ✗ booking_handler.php NOT updated\n";
}

if (strpos(file_get_contents('../client/booking.php'), 'data-price=') !== false) {
    echo "   ✓ client/booking.php includes price in cottage dropdown\n";
} else {
    echo "   ✗ client/booking.php NOT updated\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nNote: New cottage reservations will automatically include total_amount (price × 2 hours)\n";
echo "Dashboard cottage revenue will sum all confirmed/completed cottage reservations.\n";

$conn->close();
?>
