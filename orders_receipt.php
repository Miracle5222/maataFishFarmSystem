<?php
session_start();
require __DIR__ . '/config/db.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: admin_login.php?error=Please login');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "Invalid order id";
    exit;
}

// Fetch order and customer
$stmt = $conn->prepare('SELECT o.*, c.first_name, c.last_name, c.email FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ? LIMIT 1');
if (!$stmt) {
    echo "DB prepare error: " . htmlspecialchars($conn->error);
    exit;
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "Order not found";
    exit;
}

// Fetch items
$items = [];
$itemStmt = $conn->prepare('SELECT oi.quantity, oi.unit_price, oi.subtotal, p.name AS product_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
if ($itemStmt) {
    $itemStmt->bind_param('i', $id);
    $itemStmt->execute();
    $itemRes = $itemStmt->get_result();
    while ($ir = $itemRes->fetch_assoc()) {
        $items[] = $ir;
    }
    $itemStmt->close();
}

$created_at = isset($order['order_date']) ? date('M d, Y g:ia', strtotime($order['order_date'])) : '';
$total = number_format((float)$order['total_amount'], 2);

// Fetch farm info if available
$farm = null;
$fi = $conn->query("SELECT * FROM farm_info LIMIT 1");
if ($fi && $fi->num_rows > 0) $farm = $fi->fetch_assoc();
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
                <p style="margin:0;"><strong>Customer:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                <p style="margin:0;"><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
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
                        <td><?php echo htmlspecialchars($it['product_name'] ?: 'Item'); ?></td>
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
        </div>
    </div>
</div>
</body>
</html>
