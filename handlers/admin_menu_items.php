<?php
// handlers/admin_menu_items.php
// Returns available fish and products for menu ordering
session_start();
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Debug: Log session info
error_log("[admin_menu_items] Session ID: " . session_id() . ", User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . ", Role: " . ($_SESSION['role'] ?? 'NOT SET'));

// Verify admin is logged in (check user_id and role, not admin_id)
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    error_log("[admin_menu_items] Admin not logged in. Session data: " . json_encode($_SESSION));
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'debug' => ['session_id' => session_id(), 'user_id' => $_SESSION['user_id'] ?? null, 'role' => $_SESSION['role'] ?? null]]);
    exit;
}

$fish_items = [];
$product_items = [];

// Get available fish
$fish_stmt = $conn->prepare('SELECT fish_id as id, name, price_per_kg as price, stock, image FROM fish_species WHERE status = "available" AND stock > 0 ORDER BY name');
if ($fish_stmt) {
    $fish_stmt->execute();
    $fish_res = $fish_stmt->get_result();
    while ($row = $fish_res->fetch_assoc()) {
        $fish_items[] = $row;
    }
    $fish_stmt->close();
}

// Get available products
$prod_stmt = $conn->prepare('SELECT id, name, category, price, stock_quantity as stock, image FROM products WHERE category IN ("food","snack","drink") AND status = "available" AND stock_quantity > 0 ORDER BY category, name');
if ($prod_stmt) {
    $prod_stmt->execute();
    $prod_res = $prod_stmt->get_result();
    while ($row = $prod_res->fetch_assoc()) {
        $product_items[] = $row;
    }
    $prod_stmt->close();
}

error_log("[admin_menu_items] Returning " . count($fish_items) . " fish and " . count($product_items) . " products");

echo json_encode([
    'success' => true,
    'fish' => $fish_items,
    'products' => $product_items
]);

$conn->close();
?>
