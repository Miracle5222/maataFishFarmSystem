<?php
/**
 * Test INSERT with string binding for DECIMAL quantities
 */
require __DIR__ . '/config/db.php';

echo "=== Testing DECIMAL Insert with String Binding ===\n\n";

// Insert a test order first
$test_order_id = null;
$test_stmt = $conn->prepare('INSERT INTO orders (order_number, customer_id, total_amount, status, notes) VALUES (?, ?, ?, ?, ?)');
if ($test_stmt) {
    $test_order_num = 'TEST_' . date('YmdHis');
    $test_cid = 1;
    $test_total = 80.00;
    $test_status = 'pending';
    $test_notes = 'String binding test';
    
    $test_stmt->bind_param('sdss', $test_order_num, $test_cid, $test_total, $test_status, $test_notes);
    $test_stmt->execute();
    $test_order_id = $test_stmt->insert_id;
    $test_stmt->close();
    echo "✓ Test order created: ID=$test_order_id, Number=$test_order_num\n";
}

if ($test_order_id) {
    echo "\nInserting order item with quantity 0.40 using STRING binding...\n";
    
    // Test inserting with string binding
    $qty_str = "0.40";
    $price_str = "200.00";
    $subtotal_str = "80.00";
    $product_id = 6;
    
    echo "  - qty_str: $qty_str\n";
    echo "  - price_str: $price_str\n";
    echo "  - subtotal_str: $subtotal_str\n";
    
    $item_stmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
    if ($item_stmt) {
        $item_stmt->bind_param('iisss', $test_order_id, $product_id, $qty_str, $price_str, $subtotal_str);
        if ($item_stmt->execute()) {
            echo "✓ Insert executed successfully\n";
            
            // Verify what was stored
            $verify = $conn->query("SELECT quantity, unit_price, subtotal FROM order_items WHERE order_id = $test_order_id");
            if ($verify && $row = $verify->fetch_assoc()) {
                echo "\n✓ Verification - Data stored in database:\n";
                echo "  - quantity: " . $row['quantity'] . " (type: " . gettype($row['quantity']) . ")\n";
                echo "  - unit_price: " . $row['unit_price'] . "\n";
                echo "  - subtotal: " . $row['subtotal'] . "\n";
                
                if ($row['quantity'] == '0.40' || $row['quantity'] == 0.4) {
                    echo "\n✅ SUCCESS! Quantity 0.40 stored correctly!\n";
                } else {
                    echo "\n❌ FAILED! Quantity stored as " . $row['quantity'] . " instead of 0.40\n";
                }
            }
        } else {
            echo "❌ Insert failed: " . $item_stmt->error . "\n";
        }
        $item_stmt->close();
    }
}

$conn->close();
?>
