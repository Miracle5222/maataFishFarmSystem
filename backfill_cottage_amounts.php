<?php
// Backfill total_amount for existing cottage reservations
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "maata";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== BACKFILLING COTTAGE RESERVATION AMOUNTS ===\n\n";

// Get all cottage reservations without total_amount
$result = $conn->query("
    SELECT r.id, r.cottage_id, c.price
    FROM reservations r
    JOIN cottages c ON r.cottage_id = c.id
    WHERE r.reservation_type = 'cottage' AND (r.total_amount = 0 OR r.total_amount IS NULL)
");

if ($result && $result->num_rows > 0) {
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $total_amount = floatval($row['price']) * 2; // 2-hour minimum
        $update_stmt = $conn->prepare("UPDATE reservations SET total_amount = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("di", $total_amount, $row['id']);
            if ($update_stmt->execute()) {
                $count++;
                echo "   ✓ Reservation #{$row['id']} updated with amount: ₱" . number_format($total_amount, 2) . "\n";
            }
            $update_stmt->close();
        }
    }
    echo "\n✓ Backfill complete: {$count} reservations updated\n";
} else {
    echo "No reservations to backfill (or all already have amounts)\n";
}

// Show updated totals
echo "\n=== UPDATED COTTAGE REVENUE ===\n";
$result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM reservations WHERE reservation_type = 'cottage' AND status IN ('confirmed', 'completed')");
if ($result) {
    $row = $result->fetch_assoc();
    $total = floatval($row['total']);
    echo "Total cottage rental revenue: ₱" . number_format($total, 2) . "\n";
}

// Show recent reservations with amounts
echo "\n=== RECENT COTTAGE RESERVATIONS ===\n";
$result = $conn->query("
    SELECT r.id, r.reservation_number, r.status, r.total_amount, 
           CONCAT(c.first_name, ' ', c.last_name) as customer
    FROM reservations r
    JOIN customers c ON r.customer_id = c.id
    WHERE r.reservation_type = 'cottage'
    ORDER BY r.created_at DESC
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    while ($res = $result->fetch_assoc()) {
        echo "   • {$res['reservation_number']} | {$res['customer']} | Status: {$res['status']} | Amount: ₱" . number_format($res['total_amount'], 2) . "\n";
    }
}

$conn->close();
echo "\n✓ Cottage pricing system is now fully operational!\n";
?>
