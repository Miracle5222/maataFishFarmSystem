<?php
// handlers/admin_fish_items.php
// Returns available fish for fish ordering
session_start();
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Verify admin is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$fish_items = [];

// Get fish (include out-of-stock so UI can label them)
$fish_stmt = $conn->prepare('SELECT fish_id as id, name, price_per_kg as price, stock, image FROM fish_species WHERE status = "available" ORDER BY name');
if ($fish_stmt) {
    $fish_stmt->execute();
    $fish_res = $fish_stmt->get_result();
    while ($row = $fish_res->fetch_assoc()) {
        $fish_items[] = $row;
    }
    $fish_stmt->close();
}

echo json_encode([
    'success' => true,
    'items' => $fish_items
]);

$conn->close();
?>
