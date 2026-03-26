<?php
// Test script to verify decimal quantity handling in cart
session_start();
require 'handlers/db.php';

// Get current cart for logged-in user
if (!isset($_SESSION['customer_id'])) {
    echo "Not logged in. Cart test not available.";
    exit;
}

$cid = $_SESSION['customer_id'];

// Fetch current cart items
$stmt = $conn->prepare('SELECT id, fish_id, quantity, unit_price FROM carts WHERE customer_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $cid);
$stmt->execute();
$res = $stmt->get_result();

echo "<h2>Current Cart Contents (Debug)</h2>";
echo "<pre>";
while ($row = $res->fetch_assoc()) {
    echo "Cart ID: " . $row['id'] . "\n";
    echo "Fish ID: " . $row['fish_id'] . "\n";
    echo "Quantity: " . $row['quantity'] . " (type: " . gettype($row['quantity']) . ")\n";
    echo "Unit Price: " . $row['unit_price'] . "\n";
    echo "---\n";
}
echo "</pre>";

$stmt->close();

// Also test PDF parsing to verify it's float
echo "<h2>Testing Decimal Precision</h2>";
$test_qty = 0.3;
echo "PHP floatval(0.3) = " . floatval($test_qty) . "<br>";
echo "MySQL stored as DECIMAL: check your database directly<br>";
echo "<br><strong>To verify the fix:</strong><br>";
echo "1. Clear your browser cache<br>";
echo "2. Add a fish with 0.3 kg quantity to cart<br>";
echo "3. Check the cart - it should show ₱200.00/kg × 0.30 kg = ₱60.00<br>";
echo "4. The quantity in database should be 0.30, not 1 or 0<br>";
?>
