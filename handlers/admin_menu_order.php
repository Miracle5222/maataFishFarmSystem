<?php
// handlers/admin_menu_order.php
// Handles menu order creation by admin for customers
session_start();
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin_menu_order.php?error=Invalid request');
    exit;
}

// Verify admin is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ../admin_login.php?error=Please login');
    exit;
}

$aid = (int) $_SESSION['user_id'];
error_log("[admin_menu_order] Admin {$aid} creating menu order");

$customer_name = trim($_POST['customer_name'] ?? 'Direct Order');
$customer_contact = trim($_POST['customer_contact'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? '');
$order_notes = trim($_POST['order_notes'] ?? '');
$order_items_json = $_POST['order_items'] ?? '[]';

$order_items = json_decode($order_items_json, true);
error_log("[admin_menu_order] Received items: " . json_encode($order_items));

if (!is_array($order_items) || empty($order_items)) {
    header('Location: ../admin_menu_order.php?error=No items in order');
    exit;
}

// For admin-placed menu orders we don't require customer input in the form.
// Use or create a default 'Direct Order' customer record when no details provided.

error_log("[admin_menu_order] Processing " . count($order_items) . " items");

// Calculate total and validate items
$total = 0.0;
$valid_items = [];

foreach ($order_items as $item) {
    $type = $item['type'] ?? null;
    $item_id = (int) ($item['id'] ?? 0);
    $qty = (float) ($item['quantity'] ?? 0);
    $price = (float) ($item['price'] ?? 0);

    if (!$type || $item_id <= 0 || $qty <= 0) continue;

    $unit_price = $price;
    $subtotal = $unit_price * $qty;
    $total += $subtotal;

    $valid_items[] = [
        'type' => $type,
        'item_id' => $item_id,
        'quantity' => $qty,
        'unit_price' => $unit_price,
        'subtotal' => $subtotal
    ];
}

if (empty($valid_items)) {
    header('Location: ../admin_menu_order.php?error=No valid items to order');
    exit;
}

// Generate order number
$order_number = 'AMENU' . time() . substr(explode('.', (string)microtime(true))[1], 0, 3) . rand(10000, 99999);
error_log("[admin_menu_order] Generated order_number: {$order_number}, total: {$total}");

try {
    $conn->begin_transaction();

    // For admin direct menu orders we do not store customer records here.
    // The menu order will be recorded as a direct/admin order without customer details.

    // Insert into menu_orders (separate table for admin-created menu orders)
    $notes =  $order_notes;
    $status = 'paid';

    $admin_id = $aid > 0 ? $aid : null;

    $stmt = $conn->prepare('INSERT INTO menu_orders (order_number, admin_id, total_amount, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    if (!$stmt) {
        throw new Exception("Menu order prepare failed: " . $conn->error);
    }

    // Log parameters for debugging
    error_log("[admin_menu_order] Inserting menu order: order_number={$order_number}, admin_id={$admin_id}, total={$total}, status={$status}, notes=" . substr($notes,0,200));

    $stmt->bind_param('sidss', $order_number, $admin_id, $total, $status, $notes);
    if (!$stmt->execute()) {
        error_log("[admin_menu_order] Menu order execute error: " . $stmt->error);
        throw new Exception("Menu order insert failed: " . $stmt->error);
    }

    $menu_order_id = $stmt->insert_id;
    $affected = $stmt->affected_rows;
    $stmt->close();

    error_log("[admin_menu_order] Menu order created: menu_order_id={$menu_order_id}, order_number={$order_number}, affected_rows={$affected}");

    // Insert menu_order_items and update stock
    foreach ($valid_items as $item) {
        $type = $item['type'];
        $item_id = $item['item_id'];
        $qty = $item['quantity'];
        $unit_price = $item['unit_price'];
        $subtotal = $item['subtotal'];

        // Insert into menu_order_items
        $item_ins = $conn->prepare('INSERT INTO menu_order_items (menu_order_id, item_type, item_id, quantity, unit_price, subtotal, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        if (!$item_ins) {
            throw new Exception("Menu order item prepare failed: " . $conn->error);
        }

        $item_ins->bind_param('isiddd', $menu_order_id, $type, $item_id, $qty, $unit_price, $subtotal);
        if (!$item_ins->execute()) {
            error_log("[admin_menu_order] Menu order item execute error: " . $item_ins->error . " -- data=" . json_encode(['menu_order_id'=>$menu_order_id,'type'=>$type,'item_id'=>$item_id,'qty'=>$qty,'price'=>$unit_price,'subtotal'=>$subtotal]));
            throw new Exception("Menu order item insert failed: " . $item_ins->error);
        }
        error_log("[admin_menu_order] menu_order_item inserted, id=" . $item_ins->insert_id . ", menu_order_id={$menu_order_id}");
        $item_ins->close();

        error_log("[admin_menu_order] Inserted menu_order_item: menu_order_id={$menu_order_id}, type={$type}, item_id={$item_id}, qty={$qty}");

        // Update stock based on type
        if ($type === 'fish') {
            $stock_update = $conn->prepare('UPDATE fish_species SET stock = GREATEST(stock - ?, 0) WHERE fish_id = ?');
            if (!$stock_update) {
                throw new Exception("Fish stock update prepare failed: " . $conn->error);
            }
            $stock_update->bind_param('di', $qty, $item_id);
            $stock_update->execute();
            $stock_update->close();
        } elseif ($type === 'product') {
            $stock_update = $conn->prepare('UPDATE products SET stock_quantity = GREATEST(stock_quantity - ?, 0) WHERE id = ?');
            if (!$stock_update) {
                throw new Exception("Product stock update prepare failed: " . $conn->error);
            }
            $qty_int = (int)$qty;
            $stock_update->bind_param('ii', $qty_int, $item_id);
            $stock_update->execute();
            $stock_update->close();
        }
    }

    $conn->commit();

    error_log("[admin_menu_order] Menu order completed successfully: menu_order_id={$menu_order_id}");

    // Redirect to menu orders view with success
    header('Location: ../menu_orders_view.php?success=Menu order created successfully! Order #' . urlencode($order_number));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("[admin_menu_order] Error creating menu order: " . $e->getMessage());
    header('Location: ../admin_menu_order.php?error=' . urlencode('Failed to place order: ' . $e->getMessage()));
    exit;
}

$conn->close();
?>
