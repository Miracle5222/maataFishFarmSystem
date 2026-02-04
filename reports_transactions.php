<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Reports — Transactions</h4>
        <div class="text-right mb-3">
            <button onclick="window.print()" class="btn btn-primary">Print Report</button>
        </div>
        <style>
        @media print {
            .btn, .layout-navbar, .layout-sidenav, .layout-footer, .text-right,
            .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            body {
                margin: 0;
            }
            .table-responsive {
                overflow: visible !important;
            }
        }
        </style>
        <div class="card mt-3">
            <div class="table-responsive">
                <table id="transactionsTable" class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Transaction ID</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                            <th>Order Date</th>
                            <th>Pickup Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require __DIR__ . '/config/db.php';
                        $transactions = [];

                        // Query to get order items with details
                        $stmt = $conn->prepare("
                            SELECT 
                                oi.id as transaction_id,
                                o.order_number,
                                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                                COALESCE(p.name, f.name) as item_name,
                                oi.quantity,
                                oi.unit_price,
                                oi.subtotal,
                                o.order_date,
                                o.pickup_date
                            FROM order_items oi
                            JOIN orders o ON oi.order_id = o.id
                            JOIN customers c ON o.customer_id = c.id
                            LEFT JOIN products p ON oi.product_id = p.id
                            LEFT JOIN fish_species f ON oi.product_id = f.fish_id
                            ORDER BY o.order_date DESC, oi.id ASC
                        ");
                        if ($stmt) {
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) {
                                $transactions[] = $r;
                            }
                            $stmt->close();
                        }

                        if (empty($transactions)) {
                            echo '<tr><td colspan="9" class="text-center text-muted py-4">No transactions found.</td></tr>';
                        } else {
                            foreach ($transactions as $t):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['transaction_id']); ?></td>
                            <td><?php echo htmlspecialchars($t['order_number']); ?></td>
                            <td><?php echo htmlspecialchars($t['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['item_name']); ?></td>
                            <td><?php echo (int)$t['quantity']; ?></td>
                            <td>₱<?php echo number_format($t['unit_price'], 2); ?></td>
                            <td>₱<?php echo number_format($t['subtotal'], 2); ?></td>
                            <td><?php echo date('M j, Y g:iA', strtotime($t['order_date'])); ?></td>
                            <td><?php echo $t['pickup_date'] ? date('M j, Y g:iA', strtotime($t['pickup_date'])) : '-'; ?></td>
                        </tr>
                        <?php
                            endforeach;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(function() {
    // Initialize DataTable
    $('#transactionsTable').DataTable({
        pageLength: 25,
        order: [[8, 'desc']], // Order by order_date desc
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: 'lfrtip'
    });
});
</script>