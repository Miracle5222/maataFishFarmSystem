<?php
// menu_order_receipt.php
session_start();
require __DIR__ . '/config/db.php';

// Only admin/staff should view this receipt
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: admin_login.php?error=Please login');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    echo "Invalid order id";
    exit;
}

// Fetch order
$stmt = $conn->prepare("SELECT o.*, u.full_name as admin_name FROM menu_orders o LEFT JOIN users u ON o.admin_id = u.id WHERE o.id = ? LIMIT 1");
if (!$stmt) {
    echo "DB prepare error (order): " . htmlspecialchars($conn->error);
    exit;
}

if (!$stmt->bind_param('i', $id)) {
    echo "DB bind_param error (order): " . htmlspecialchars($stmt->error);
    exit;
}

if (!$stmt->execute()) {
    echo "DB execute error (order): " . htmlspecialchars($stmt->error);
    exit;
}

$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo "Order not found";
    exit;
}
$order = $res->fetch_assoc();
$stmt->close();

// Fetch items
$item_stmt = $conn->prepare("SELECT m.*, p.name as product_name, f.name as fish_name
    FROM menu_order_items m
    LEFT JOIN products p ON (m.item_type = 'product' AND m.item_id = p.id)
    LEFT JOIN fish_species f ON (m.item_type = 'fish' AND m.item_id = f.fish_id)
    WHERE m.menu_order_id = ?");
if (!$item_stmt) {
    echo "DB prepare error (items): " . htmlspecialchars($conn->error);
    exit;
}
if (!$item_stmt->bind_param('i', $id)) {
    echo "DB bind_param error (items): " . htmlspecialchars($item_stmt->error);
    exit;
}
if (!$item_stmt->execute()) {
    echo "DB execute error (items): " . htmlspecialchars($item_stmt->error);
    exit;
}
$items_res = $item_stmt->get_result();
$items = [];
while ($r = $items_res->fetch_assoc()) {
    $r['display_name'] = $r['item_type'] === 'product' ? ($r['product_name'] ?? 'Product') : ($r['fish_name'] ?? 'Fish');
    $items[] = $r;
}
$item_stmt->close();

// Fetch farm info if available
$farm = null;
$fi = $conn->query("SELECT * FROM farm_info LIMIT 1");
if ($fi === false) {
    error_log('farm_info query error: ' . $conn->error);
} elseif ($fi && $fi->num_rows > 0) {
    $farm = $fi->fetch_assoc();
}

// Format
$created_at = isset($order['created_at']) ? date('M d, Y g:ia', strtotime($order['created_at'])) : '';
$total = number_format((float)$order['total_amount'], 2);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        .receipt { max-width: 800px; margin: 20px auto; background:#fff; padding:20px; border:1px solid #e9ecef; }
        .print-header { text-align: center; margin-bottom: 20px; }
        .print-header img { height: 60px; margin-right: 15px; vertical-align: middle; }
        .print-header h2 { margin: 0; color: #27ae60; }
        table.receipt-table th, table.receipt-table td{ padding:8px; border-bottom:1px solid #eee; }
        @media print { .no-print{display:none;} }
    </style>
</head>
<body>
<div class="container">
    <div class="receipt">
        <div class="print-header">
            <img src="assets/img/maataLogo.png" alt="Maata Logo">
            <div style="display:inline-block; vertical-align: middle; text-align:left;">
                <h2>Maata Fish Farm</h2>
                <div style="font-size:12px; color:#666;">
                    <div>Quality Aquaculture Products</div>
                    <div>Contact: 09661337498 | Address: New Basak, Dumingag, Zamboanga del Sur</div>
                    <div>Email: admin@gmail.com | Website: https://maatafishfarm.gt.tc/</div>
                </div>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-sm-8">
                <p style="margin:0;"><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'Direct Order'); ?></p>
                <p style="margin:0;"><strong>Contact:</strong> <?php echo htmlspecialchars($order['customer_contact'] ?? ''); ?></p>
                <?php if (!empty($order['notes'])): ?>
                <p style="margin:0;"><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-sm-4 text-right">
                <p style="margin:0;"><strong>Order #</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                <p style="margin:0;"><strong>Date:</strong> <?php echo $created_at; ?></p>
                <p style="margin:0;"><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
            </div>
        </div>

        <table class="table receipt-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)) {
                    foreach ($items as $it) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($it['display_name']); ?></td>
                        <td class="text-center"><?php echo (int)$it['quantity']; ?></td>
                        <td class="text-right"><?php echo number_format((float)$it['unit_price'], 2); ?></td>
                        <td class="text-right"><?php echo number_format((float)$it['subtotal'], 2); ?></td>
                    </tr>
                <?php }
                } else { ?>
                    <tr><td colspan="4" class="text-center text-muted">No items</td></tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Total</th>
                    <th class="text-right"><?php echo $total; ?></th>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top:20px; font-size:12px;">
            <p>Thank you for your order. For inquiries contact us at <?php echo htmlspecialchars($farm['phone'] ?? '09661337498'); ?> or <?php echo htmlspecialchars($farm['email'] ?? 'admin@gmail.com'); ?>.</p>
        </div>

        <div class="no-print" style="margin-top:15px;">
            <button class="btn btn-primary" onclick="window.print()">Print</button>
            <a href="menu_orders_view.php" class="btn btn-secondary">Back to Orders</a>
        </div>
    </div>
</div>
</body>
</html>
