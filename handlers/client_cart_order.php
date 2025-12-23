<?php
// handlers/client_cart_order.php
// Accepts POST: cart (json), customer_name, customer_contact, delivery_date, delivery_address
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../client/cart.php?error=Invalid request');
    exit;
}
if (empty($_SESSION['client_id'])) {
    header('Location: ../client/login.php?error=Please login');
    exit;
}

$cid = (int) $_SESSION['client_id'];
error_log("[client_cart_order] Starting order for customer_id={$cid}");
$cartJson = $_POST['cart'] ?? '[]';
error_log("[client_cart_order] Raw cart JSON: " . $cartJson);
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_contact = trim($_POST['customer_contact'] ?? '');
$pickup_date = $_POST['pickup_date'] ?: null;

$cart = json_decode($cartJson, true);
error_log("[client_cart_order] Decoded cart: " . json_encode($cart));
if (!is_array($cart) || empty($cart)) {
    error_log("[client_cart_order] Cart empty or invalid JSON for customer_id={$cid}: {$cartJson}");
    header('Location: ../client/cart.php?error=Cart is empty');
    exit;
}
if ($customer_name === '' || $customer_contact === '') {
    error_log("[client_cart_order] Missing customer info for customer_id={$cid}");
    header('Location: ../client/cart.php?error=Missing customer info');
    exit;
}

error_log("[client_cart_order] Cart validated, processing items for customer_id={$cid}: " . count($cart) . ' items');

// calculate totals and verify items from cart data
$total = 0.0;
$items = [];
foreach ($cart as $it) {
    $fish_id = (int) ($it['id'] ?? 0);
    $qty = (int) ($it['qty'] ?? 0);
    if ($fish_id <= 0 || $qty <= 0) continue;
    // Try both possible column names (fish_id or id) to be tolerant of schema differences
    $fish = null;
    $try1 = $conn->prepare('SELECT fish_id AS fid, name, price_per_kg, stock FROM fish_species WHERE fish_id = ? AND status = "available" LIMIT 1');
    if ($try1) {
        $try1->bind_param('i', $fish_id);
        $try1->execute();
        $r1 = $try1->get_result();
        if ($r1 && $r1->num_rows) {
            $row = $r1->fetch_assoc();
            $fish = ['fid' => (int)$row['fid'], 'name' => $row['name'], 'price_per_kg' => $row['price_per_kg'], 'stock' => $row['stock']];
        }
        $try1->close();
    }
    if (!$fish) {
        $try2 = $conn->prepare('SELECT id AS fid, name, price_per_kg, stock FROM fish_species WHERE id = ? AND status = "available" LIMIT 1');
        if ($try2) {
            $try2->bind_param('i', $fish_id);
            $try2->execute();
            $r2 = $try2->get_result();
            if ($r2 && $r2->num_rows) {
                $row = $r2->fetch_assoc();
                $fish = ['fid' => (int)$row['fid'], 'name' => $row['name'], 'price_per_kg' => $row['price_per_kg'], 'stock' => $row['stock']];
            }
            $try2->close();
        }
    }
    if (!$fish) continue;
    $unit_price = (float) $fish['price_per_kg'];
    $subtotal = $unit_price * $qty;
    $total += $subtotal;
    $items[] = ['fish_id' => $fish['fid'], 'quantity' => $qty, 'unit_price' => $unit_price, 'subtotal' => $subtotal];
}
if (empty($items)) {
    error_log("[client_cart_order] Items array empty after processing: " . json_encode($items));
    header('Location: ../client/cart.php?error=No valid items to order');
    exit;
}

// create order under logged-in customer
// Generate unique order number: ORD{timestamp}{microseconds}{random}
$order_number = 'ORD' . time() . substr(explode('.', (string)microtime(true))[1], 0, 3) . rand(10000, 99999);
error_log("[client_cart_order] Generated order_number: {$order_number}, items count: " . count($items));
error_log("[client_cart_order] Items to insert: " . json_encode($items));
$notes = 'Online order from website';
$status = 'pending';
error_log("[client_cart_order] About to prepare INSERT for customer_id={$cid}, total={$total}");
$ins = $conn->prepare('INSERT INTO orders (order_number, customer_id, pickup_date, total_amount, status, notes) VALUES (?, ?, ?, ?, ?, ?)');
if (!$ins) {
    error_log("[client_cart_order] PREPARE FAILED: " . $conn->error);
    header('Location: ../client/cart.php?error=Failed to create order: prepare error');
    exit;
}
error_log("[client_cart_order] Prepare succeeded, binding params");
// types: s (order_number), i (customer_id), s (pickup_date), d (total_amount), s (status), s (notes)
$ins->bind_param('sisdss', $order_number, $cid, $pickup_date, $total, $status, $notes);
error_log("[client_cart_order] Params bound, executing INSERT");
$ok = $ins->execute();
error_log("[client_cart_order] Execute result: " . ($ok ? 'SUCCESS' : 'FAILED - ' . $ins->error));
$order_id = $ins->insert_id;
error_log("[client_cart_order] Insert ID: {$order_id}");
$ins->close();
if (!$ok || !$order_id) {
    error_log("[client_cart_order] ORDER INSERT FAILED: ok={$ok}, order_id={$order_id}");
    header('Location: ../client/cart.php?error=Failed to create order');
    exit;
}

// Ensure customer's type is set to online_customer for ordering
try {
    $ctype = 'online_customer';
    $upc = $conn->prepare('UPDATE customers SET customer_type = ?, updated_at = NOW() WHERE id = ?');
    if ($upc) {
        $upc->bind_param('si', $ctype, $cid);
        $upc->execute();
        $upc->close();
    }
} catch (Exception $e) {
    error_log("[client_cart_order] Failed to set customer_type: " . $e->getMessage());
}

// insert order items and decrement fish stock
// Log order creation for debugging
error_log("[client_cart_order] Order created: order_number={$order_number}, order_id={$order_id}, customer_id={$cid}, total={$total}");
foreach ($items as $it) {
    $insi = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)');
    if ($insi) {
        $insi->bind_param('iiidd', $order_id, $it['fish_id'], $it['quantity'], $it['unit_price'], $it['subtotal']);
        $insi->execute();
        error_log("[client_cart_order] Inserted order_item for order_id={$order_id}, product_id={$it['fish_id']}, qty={$it['quantity']}");
        $insi->close();
    }
    // Update stock: try by id first, then by fish_id if nothing updated
    $updated = 0;
    $up = $conn->prepare('UPDATE fish_species SET stock = GREATEST(stock - ?, 0) WHERE id = ?');
    if ($up) {
        $up->bind_param('ii', $it['quantity'], $it['fish_id']);
        $up->execute();
        $updated = $up->affected_rows;
        $up->close();
    }
    if (!$updated) {
        $up2 = $conn->prepare('UPDATE fish_species SET stock = GREATEST(stock - ?, 0) WHERE fish_id = ?');
        if ($up2) {
            $up2->bind_param('ii', $it['quantity'], $it['fish_id']);
            $up2->execute();
            $updated = $up2->affected_rows;
            $up2->close();
        }
    }
    error_log("[client_cart_order] Stock update attempted for product={$it['fish_id']}, qty={$it['quantity']}, rows_updated={$updated}");
}

// clear customer's cart from database
$clr = $conn->prepare('DELETE FROM carts WHERE customer_id = ?');
if ($clr) {
    $clr->bind_param('i', $cid);
    $clr->execute();
    $clr->close();
}

// success — redirect to orders page so customer can view their new order
header('Location: ../client/orders.php?success=Order placed successfully! Order #' . urlencode($order_number));
