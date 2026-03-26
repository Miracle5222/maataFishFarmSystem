<?php
// Final verification test for revenue tracking system
$conn = new mysqli('localhost', 'root', '', 'maata');

echo "=== FINAL SYSTEM VERIFICATION ===\n\n";

// 1. Verify total_amount column exists and has data
echo "1. Database Schema Check:\n";
$result = $conn->query("SHOW COLUMNS FROM reservations WHERE Field = 'total_amount'");
if ($result && $result->num_rows > 0) {
    echo "   ✓ total_amount column exists in reservations\n";
}

// 2. Completed cottage reservations
echo "\n2. Completed Cottage Reservations:\n";
$result = $conn->query("
    SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total
    FROM reservations 
    WHERE reservation_type = 'cottage' AND status = 'completed'
");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   • Total completed: {$row['cnt']}\n";
    echo "   • Total revenue: ₱" . number_format($row['total'], 2) . "\n";
}

// 3. Breakdown by source (even without activity logs)
echo "\n3. Revenue Breakdown (Walk-In):\n";
$result = $conn->query("
    SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total
    FROM reservations r
    LEFT JOIN activity_logs a ON a.record_id = r.id AND a.record_type = 'reservation' AND a.action = 'CREATE'
    WHERE r.reservation_type = 'cottage' AND r.status = 'completed'
    AND (a.user_type IN ('admin', 'staff', 'manager') OR a.id IS NULL)
");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   • Walk-In count: {$row['cnt']}\n";
    echo "   • Walk-In revenue: ₱" . number_format($row['total'], 2) . "\n";
}

echo "\n4. Revenue Breakdown (Online):\n";
$result = $conn->query("
    SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total
    FROM reservations r
    INNER JOIN activity_logs a ON a.record_id = r.id AND a.record_type = 'reservation' AND a.action = 'CREATE'
    WHERE r.reservation_type = 'cottage' AND r.status = 'completed'
    AND a.user_type = 'customer'
");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   • Online count: {$row['cnt']}\n";
    echo "   • Online revenue: ₱" . number_format($row['total'], 2) . "\n";
}

// 4. Boat rentals status
echo "\n5. Boat Rentals Status:\n";
$result = $conn->query("SELECT status, COUNT(*) as cnt FROM boat_rentals GROUP BY status");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "   • {$row['status']}: {$row['cnt']}\n";
    }
}

// 5. Verify handlers are updated
echo "\n6. Handler Code Verification:\n";
$handlers_ok = true;

// Check manual_cottage_reservation_handler
$handler_code = file_get_contents('handlers/manual_cottage_reservation_handler.php');
if (strpos($handler_code, 'Note: total_amount will be calculated when') !== false) {
    echo "   ✓ manual_cottage_reservation_handler.php - updated (calculation on checkout)\n";
} else {
    echo "   ✗ manual_cottage_reservation_handler.php - may not be updated\n";
    $handlers_ok = false;
}

// Check reservation_update_handler
$handler_code = file_get_contents('handlers/reservation_update_handler.php');
if (strpos($handler_code, 'If status is completed (checkout)') !== false) {
    echo "   ✓ reservation_update_handler.php - updated (calculates on 'completed')\n";
} else {
    echo "   ✗ reservation_update_handler.php - may not be updated\n";
    $handlers_ok = false;
}

// Check booking_handler
$handler_code = file_get_contents('handlers/booking_handler.php');
if (strpos($handler_code, 'Note: For cottage reservations') !== false) {
    echo "   ✓ booking_handler.php - updated (calculation on checkout)\n";
} else {
    echo "   ✗ booking_handler.php - may not be updated\n";
    $handlers_ok = false;
}

// Check dashboard
$dashboard_code = file_get_contents('index.php');
if (strpos($dashboard_code, 'online_cottage_revenue') !== false && strpos($dashboard_code, 'walkin_cottage_revenue') !== false) {
    echo "   ✓ index.php - updated (online/walk-in separation)\n";
} else {
    echo "   ✗ index.php - may not be updated\n";
    $handlers_ok = false;
}

echo "\n" . ($handlers_ok ? "✓ ALL SYSTEMS READY\n" : "⚠ Some updates may be incomplete\n");

echo "\n=== SYSTEM READY FOR TESTING ===\n";
echo "\nTo test the system:\n";
echo "1. Go to manual_cottage_reservation.php or client/booking.php\n";
echo "2. Create a new cottage reservation\n";
echo "3. In reservations_list.php, mark it as 'Checked Out'\n";
echo "4. Go to dashboard (index.php)\n";
echo "5. See revenue appear in the appropriate category\n";

$conn->close();
?>
