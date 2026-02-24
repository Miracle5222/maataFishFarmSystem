<?php
// handlers/admin_fish_order.php
// Handles fish order creation by admin
session_start();
require __DIR__ . '/../config/db.php';
require __DIR__ . '/activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin_fish_order.php?error=Invalid request');
    exit;
}

// Verify admin is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ../admin_login.php?error=Please login');
    exit;
}

$aid = (int) $_SESSION['user_id'];
error_log("[admin_fish_order] Admin {$aid} creating fish order");

$customer_name = trim($_POST['customer_name'] ?? 'Direct Order');
$customer_contact = trim($_POST['customer_contact'] ?? '');
$order_notes = trim($_POST['order_notes'] ?? '');
$order_items_json = $_POST['order_items'] ?? '[]';

$order_items = json_decode($order_items_json, true);
error_log("[admin_fish_order] Received items: " . json_encode($order_items));

if (!is_array($order_items) || empty($order_items)) {
    header('Location: ../admin_fish_order.php?error=No items in order');
    exit;
}

error_log("[admin_fish_order] Processing " . count($order_items) . " items");

// Calculate total and validate items
$total = 0.0;
$valid_items = [];

foreach ($order_items as $item) {
    $item_id = (int) ($item['id'] ?? 0);
    $qty = (float) ($item['quantity'] ?? 0);
    $price = (float) ($item['price'] ?? 0);

    if ($item_id <= 0 || $qty <= 0) continue;

    $unit_price = $price;
    $subtotal = $unit_price * $qty;
    $total += $subtotal;

    $valid_items[] = [
        'item_id' => $item_id,
        'quantity' => $qty,
        'unit_price' => $unit_price,
        'subtotal' => $subtotal
    ];
}

if (empty($valid_items)) {
    header('Location: ../admin_fish_order.php?error=No valid items to order');
    exit;
}

// Generate order number
$order_number = 'AFISH' . time() . substr(explode('.', (string)microtime(true))[1], 0, 3) . rand(10000, 99999);
error_log("[admin_fish_order] Generated order_number: {$order_number}, total: {$total}");

try {
    $conn->begin_transaction();

    // Check if fish_orders table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'fish_orders'");
    if ($tableCheck->num_rows === 0) {
        // Create fish_orders table
        $createTable = "CREATE TABLE fish_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            admin_id INT,
            customer_name VARCHAR(255),
            customer_contact VARCHAR(255),
            total_amount DECIMAL(12, 2),
            status ENUM('pending', 'paid', 'completed', 'cancelled') DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        if (!$conn->query($createTable)) {
            throw new Exception("Failed to create fish_orders table: " . $conn->error);
        }
    }

    // Check if fish_order_items table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'fish_order_items'");
    if ($tableCheck->num_rows === 0) {
        // Create fish_order_items table
        $createTable = "CREATE TABLE fish_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fish_order_id INT NOT NULL,
            fish_id INT,
            quantity DECIMAL(10, 2),
            unit_price DECIMAL(10, 2),
            subtotal DECIMAL(12, 2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fish_order_id) REFERENCES fish_orders(id) ON DELETE CASCADE
        )";
        if (!$conn->query($createTable)) {
            throw new Exception("Failed to create fish_order_items table: " . $conn->error);
        }
    }

    // Insert into fish_orders
    $status = 'paid';
    $admin_id = $aid > 0 ? $aid : null;

    $stmt = $conn->prepare('INSERT INTO fish_orders (order_number, admin_id, customer_name, customer_contact, total_amount, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    if (!$stmt) {
        throw new Exception("Fish order prepare failed: " . $conn->error);
    }

    error_log("[admin_fish_order] Inserting fish order: order_number={$order_number}, admin_id={$admin_id}, total={$total}, status={$status}");

    $stmt->bind_param('sissdss', $order_number, $admin_id, $customer_name, $customer_contact, $total, $status, $order_notes);
    if (!$stmt->execute()) {
        error_log("[admin_fish_order] Fish order execute error: " . $stmt->error);
        throw new Exception("Fish order insert failed: " . $stmt->error);
    }

    $fish_order_id = $stmt->insert_id;
    $affected = $stmt->affected_rows;
    $stmt->close();

    error_log("[admin_fish_order] Fish order created: fish_order_id={$fish_order_id}, order_number={$order_number}, affected_rows={$affected}");

    // Insert fish_order_items and update stock
    foreach ($valid_items as $item) {
        $item_id = $item['item_id'];
        $qty = $item['quantity'];
        $unit_price = $item['unit_price'];
        $subtotal = $item['subtotal'];

        // Insert into fish_order_items
        $item_ins = $conn->prepare('INSERT INTO fish_order_items (fish_order_id, fish_id, quantity, unit_price, subtotal, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        if (!$item_ins) {
            throw new Exception("Fish order item prepare failed: " . $conn->error);
        }

        $item_ins->bind_param('iiddd', $fish_order_id, $item_id, $qty, $unit_price, $subtotal);
        if (!$item_ins->execute()) {
            error_log("[admin_fish_order] Fish order item execute error: " . $item_ins->error);
            throw new Exception("Fish order item insert failed: " . $item_ins->error);
        }
        error_log("[admin_fish_order] fish_order_item inserted, id=" . $item_ins->insert_id . ", fish_order_id={$fish_order_id}");
        $item_ins->close();

        // Update fish stock
        $stock_update = $conn->prepare('UPDATE fish_species SET stock = GREATEST(stock - ?, 0) WHERE fish_id = ?');
        if (!$stock_update) {
            throw new Exception("Fish stock update prepare failed: " . $conn->error);
        }
        $stock_update->bind_param('di', $qty, $item_id);
        $stock_update->execute();
        $stock_update->close();
    }

    $conn->commit();

    error_log("[admin_fish_order] Fish order completed successfully: fish_order_id={$fish_order_id}");

    // Log activity for fish order creation
    $description = "Created fish order {$order_number} with ₱" . number_format($total, 2) . " total and " . count($valid_items) . " items";
    logActivity($conn, $aid, 'admin', 'CREATE', 'fish_order', $fish_order_id, $order_number, $description);

    // Redirect to the receipt for this order
    header('Location: ../fish_order_receipt.php?id=' . urlencode($fish_order_id));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("[admin_fish_order] Error creating fish order: " . $e->getMessage());
    header('Location: ../admin_fish_order.php?error=' . urlencode('Failed to place order: ' . $e->getMessage()));
    exit;
}

$conn->close();
?>
