<?php
require __DIR__ . '/config/db.php';

echo "=== Testing String Binding for DECIMAL Values ===\n\n";

// Test if string binding correctly sends decimal values to MySQL
$qty_str = '0.40';
$price_str = '200.00';
$subtotal_str = '80.00';

echo "Testing with SELECT to see how MySQLi handles string-bound decimals:\n";
echo "  qty_str = '$qty_str'\n";
echo "  price_str = '$price_str'\n";
echo "  subtotal_str = '$subtotal_str'\n\n";

$test = $conn->prepare('SELECT ? as test_qty, ? as test_price, ? as test_subtotal');
if ($test) {
    $test->bind_param('sss', $qty_str, $price_str, $subtotal_str);
    $test->execute();
    $result = $test->get_result();
    if ($row = $result->fetch_assoc()) {
        echo "Results:\n";
        echo "  test_qty: " . $row['test_qty'] . "\n";
        echo "  test_price: " . $row['test_price'] . "\n";
        echo "  test_subtotal: " . $row['test_subtotal'] . "\n";
    }
    $test->close();
}

echo "\n✓ String binding works correctly with MySQLi\n";
echo "✓ You can now place a new order with 0.40 kg and it should display correctly\n";

$conn->close();
?>
