<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<div class="layout-content">
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold py-3 mb-0">Reports — Expenses</h4>
            <div>
                <button class="btn btn-success" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>

        <?php
        require __DIR__ . '/config/db.php';
        
        // Check database connection
        if (!$conn) {
            echo '<div class="alert alert-danger">Database connection failed</div>';
            exit;
        }
        ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <!-- DataTables CSS from CDN -->
                    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

                    <table id="expensesTable" class="table table-striped table-sm" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Subcat</th>
                                <th>Method</th>
                                <th>Vendor</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Receipt</th>
                                <th>Notes</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $q = $conn->prepare('SELECT id, amount, currency, transaction_date, category, subcategory, payment_method, vendor, location, status, receipt_available, receipt_image_path, notes, created_by FROM expenses ORDER BY id DESC');
                        
                        if (!$q) {
                            echo '<tr><td colspan="13" class="alert alert-danger">Query error: ' . htmlspecialchars($conn->error) . '</td></tr>';
                        } else {
                            if (!$q->execute()) {
                                echo '<tr><td colspan="13" class="alert alert-danger">Execute error: ' . htmlspecialchars($q->error) . '</td></tr>';
                            } else {
                                $res = $q->get_result();
                                if ($res && $res->num_rows > 0) {
                                    while ($r = $res->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($r['id'] ?? '') . '</td>';
                                        echo '<td>' . number_format((float)($r['amount'] ?? 0), 2) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['currency'] ?? 'PHP') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['transaction_date'] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['category'] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['subcategory'] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['payment_method'] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['vendor'] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['location'] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['status'] ?? '') . '</td>';
                                        if (!empty($r['receipt_image_path'])) {
                                            echo '<td><a href="' . htmlspecialchars($r['receipt_image_path']) . '" target="_blank">View</a></td>';
                                        } else {
                                            echo '<td>' . ($r['receipt_available'] ? 'Yes' : 'No') . '</td>';
                                        }
                                        echo '<td>' . htmlspecialchars(substr($r['notes'] ?? '', 0, 80)) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['created_by'] ?? '') . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="13" class="text-center text-muted py-3">No expenses found</td></tr>';
                                }
                            }
                            $q->close();
                        }
                        ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'partials/footer.php'; ?>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        if (typeof jQuery === 'undefined') return;
        jQuery('#expensesTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            responsive: true
        });
    });
</script>
