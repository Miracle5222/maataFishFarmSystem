<?php
// handlers/client_cart_api.php
// API for cart operations (add, remove, update, list) for fish species
session_start();
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$cid = (int) $_SESSION['client_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// GET /cart - list all cart items for user
if ($action === 'list' || ($_SERVER['REQUEST_METHOD'] === 'GET' && !$action)) {
    // Use id ordering to avoid depending on a created_at column that may not exist
    $stmt = $conn->prepare('SELECT id, fish_id, item_type, quantity, unit_price FROM carts WHERE customer_id = ? ORDER BY id ASC');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'List prepare failed', 'debug' => $conn->error]);
        exit;
    }
    $stmt->bind_param('i', $cid);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'List execute failed', 'debug' => $stmt->error]);
        $stmt->close();
        exit;
    }
    $res = $stmt->get_result();
    $items = [];
    $total = 0;
    while ($row = $res->fetch_assoc()) {
        $item_id = (int)$row['fish_id'];
        $item_type = $row['item_type'];
        $qty = (int)$row['quantity'];
        $price = (float)$row['unit_price'];
        
        // Fetch item name
        $name = '';
        if ($item_type === 'fish') {
            $fstmt = $conn->prepare('SELECT name FROM fish_species WHERE fish_id = ? LIMIT 1');
            $fstmt->bind_param('i', $item_id);
            $fstmt->execute();
            $fres = $fstmt->get_result();
            $frow = $fres->fetch_assoc();
            $fstmt->close();
            $name = $frow ? $frow['name'] : 'Fish #' . $item_id;
            $unit = 'kg';
        } else {
            $pstmt = $conn->prepare('SELECT name, unit FROM products WHERE id = ? LIMIT 1');
            $pstmt->bind_param('i', $item_id);
            $pstmt->execute();
            $pres = $pstmt->get_result();
            $prow = $pres->fetch_assoc();
            $pstmt->close();
            $name = $prow ? $prow['name'] : 'Product #' . $item_id;
            $unit = $prow['unit'] ?? 'piece';
        }
        
        $subtotal = $price * $qty;
        $total += $subtotal;
        $items[] = [
            'id' => (int)$row['id'],
            'item_id' => $item_id,
            'item_type' => $item_type,
            'name' => $name,
            'quantity' => $qty,
            'unit_price' => $price,
            'unit' => $unit,
            'subtotal' => round($subtotal, 2)
        ];
    }
    $stmt->close();
    echo json_encode(['success' => true, 'items' => $items, 'total' => round($total, 2)]);
    exit;
}

// POST /cart/add - add or update cart item
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int) ($_POST['item_id'] ?? $_POST['product_id'] ?? $_POST['fish_id'] ?? 0);
    $item_type = $_POST['item_type'] ?? 'fish';
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    
    // Log the request
    error_log("Cart Add Request - item_id: $item_id, item_type: $item_type, qty: $quantity, cid: $cid");
    
    if ($item_id <= 0 || !in_array($item_type, ['fish', 'product'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid item', 'debug' => ['item_id' => $item_id, 'item_type' => $item_type]]);
        exit;
    }
    
    // Fetch item details
    $item = null;
    $unit_price = 0;
    $stock = 0;
    if ($item_type === 'fish') {
        $stmt = $conn->prepare('SELECT fish_id, name, price_per_kg AS price, stock FROM fish_species WHERE fish_id = ? AND status = "available" LIMIT 1');
        $stmt->bind_param('i', $item_id);
    } else {
        $stmt = $conn->prepare('SELECT id, name, price, stock_quantity AS stock FROM products WHERE id = ? AND status = "available" LIMIT 1');
        $stmt->bind_param('i', $item_id);
    }
    
    if (!$stmt) {
        error_log("Item prepare failed: " . $conn->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error', 'debug' => ['stmt_error' => $conn->error]]);
        exit;
    }
    
    if (!$stmt->execute()) {
        error_log("Item execute failed: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Item lookup error', 'debug' => $stmt->error]);
        $stmt->close();
        exit;
    }
    
    $res = $stmt->get_result();
    $item = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    
    if (!$item) {
        error_log("Item not found: $item_id type $item_type");
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Item not found or unavailable', 'debug' => ['item_id' => $item_id, 'item_type' => $item_type]]);
        exit;
    }
    
    error_log("Item found: " . json_encode($item));
    $unit_price = (float) $item['price'];
    $stock = (int) $item['stock'];
    
    if ($quantity > $stock) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Not enough stock']);
        exit;
    }
    
    // Check if item already in cart
    $checkstmt = $conn->prepare('SELECT id, quantity FROM carts WHERE customer_id = ? AND fish_id = ? AND item_type = ? LIMIT 1');
    if (!$checkstmt) {
        error_log("Check prepare failed: " . $conn->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Check prepare error']);
        exit;
    }
    $checkstmt->bind_param('iis', $cid, $item_id, $item_type);
    $checkstmt->execute();
    $checkres = $checkstmt->get_result();
    $existing = $checkres ? $checkres->fetch_assoc() : null;
    $checkstmt->close();
    
    error_log("Existing cart item: " . ($existing ? json_encode($existing) : 'none'));
    
    if ($existing) {
        // Update quantity
        $newQty = (int)$existing['quantity'] + $quantity;
        if ($newQty > $stock) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Not enough stock for updated quantity']);
            exit;
        }
        $upd = $conn->prepare('UPDATE carts SET quantity = ?, updated_at = NOW() WHERE id = ?');
        if (!$upd) {
            error_log("Update prepare failed: " . $conn->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Update prepare failed']);
            exit;
        }
        $upd->bind_param('ii', $newQty, $existing['id']);
        $ok = $upd->execute();
        if (!$ok) {
            error_log("Update execute failed: " . $upd->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Update failed', 'debug' => $upd->error]);
            $upd->close();
            exit;
        }
        $upd->close();
        error_log("Updated cart item quantity to $newQty");
    } else {
        // Insert new cart item
        error_log("Inserting cart: cid=$cid, item_id=$item_id, item_type=$item_type, qty=$quantity, price=$unit_price");
        $ins = $conn->prepare('INSERT INTO carts (customer_id, fish_id, quantity, unit_price, item_type) VALUES (?, ?, ?, ?, ?)');
        if (!$ins) {
            error_log("Insert prepare failed: " . $conn->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Insert prepare failed', 'debug' => $conn->error]);
            exit;
        }
        $ins->bind_param('iiids', $cid, $item_id, $quantity, $unit_price, $item_type);
        $ok = $ins->execute();
        if (!$ok) {
            error_log("Insert execute failed: " . $ins->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Insert failed', 'debug' => $ins->error]);
            $ins->close();
            exit;
        }
        $ins->close();
        error_log("Successfully inserted cart item");
    }
    
    echo json_encode(['success' => true, 'message' => 'Added to cart']);
    exit;
}

// POST /cart/remove - remove cart item
if ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = (int) ($_POST['cart_id'] ?? 0);
    
    if ($cart_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid cart item']);
        exit;
    }
    
    // Verify ownership before delete
    $stmt = $conn->prepare('DELETE FROM carts WHERE id = ? AND customer_id = ?');
    $stmt->bind_param('ii', $cart_id, $cid);
    $ok = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Removed from cart' : 'Failed to remove']);
    exit;
}

// POST /cart/update - update cart item quantity
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = (int) ($_POST['cart_id'] ?? 0);
    $quantity = max(0, (int) ($_POST['quantity'] ?? 0));
    
    if ($cart_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid cart item']);
        exit;
    }
    
    if ($quantity <= 0) {
        // Delete if qty is 0
        $stmt = $conn->prepare('DELETE FROM carts WHERE id = ? AND customer_id = ?');
        $stmt->bind_param('ii', $cart_id, $cid);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Removed from cart']);
        exit;
    }
    
    // Update quantity
    $stmt = $conn->prepare('UPDATE carts SET quantity = ?, updated_at = NOW() WHERE id = ? AND customer_id = ?');
    $stmt->bind_param('iii', $quantity, $cart_id, $cid);
    $ok = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated' : 'Failed']);
    exit;
}

// POST /cart/clear - clear entire cart
if ($action === 'clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('DELETE FROM carts WHERE customer_id = ?');
    $stmt->bind_param('i', $cid);
    $ok = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $ok, 'message' => 'Cart cleared']);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
exit;
