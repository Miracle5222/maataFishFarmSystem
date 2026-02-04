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

        <style>
        @media print {
            .btn, .layout-navbar, .layout-sidenav, .layout-footer,
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
            .print-header, .print-footer {
                display: block !important;
            }
            .no-print {
                display: none !important;
            }
        }
        .print-header, .print-footer {
            display: none;
        }
        </style>

        <div class="print-header" style="text-align: center; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                <img src="assets/img/maataLogo.png" alt="Maata Logo" style="height: 60px; margin-right: 15px;">
                <div>
                    <h2 style="margin: 0; color: #27ae60;">Maata Fish Farm</h2>
                    <p style="margin: 5px 0; font-size: 14px;">Quality Aquaculture Products</p>
                </div>
            </div>
            <div style="font-size: 12px; color: #666;">
                <p><strong>Contact:</strong> 09661337498 | <strong>Address:</strong> New Basak, Dumingag, Zamboanga del Sur</p>
                <p><strong>Email:</strong> admin@gmail.com | <strong>Website:</strong> https://maatafishfarm.gt.tc/</p>
            </div>
            <hr style="border: 1px solid #27ae60; margin: 15px 0;">
            <h3 style="margin: 10px 0; color: #27ae60;">Expenses Report</h3>
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
                                <th>Category</th>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Receipt #</th>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th class="no-print">Receipt</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $q = $conn->prepare('SELECT id, category, item_name, quantity, unit, receipt_number, amount, currency, transaction_date, description, receipt_image_path, created_by FROM expenses ORDER BY id DESC');
                        
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
                                        echo '<td>' . htmlspecialchars(ucfirst($r['category'] ?? '')) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['item_name'] ?? '') . '</td>';
                                        echo '<td>' . number_format((float)($r['quantity'] ?? 0), 2) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['unit'] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['receipt_number'] ?? '-') . '</td>';
                                        echo '<td>' . number_format((float)($r['amount'] ?? 0), 2) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['currency'] ?? 'PHP') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['transaction_date'] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars($r['description'] ?? '-') . '</td>';
                                        if (!empty($r['receipt_image_path'])) {
                                            echo '<td class="no-print"><a href="' . htmlspecialchars($r['receipt_image_path']) . '" target="_blank">View</a></td>';
                                        } else {
                                            echo '<td class="no-print">-</td>';
                                        }
                                        echo '<td>' . htmlspecialchars($r['created_by'] ?? '') . '</td>';
                                        echo '<td>';
                                        echo '<a href="expenses.php" class="btn btn-sm btn-outline-primary mr-1">Edit</a>';
                                        echo '<form method="post" action="handlers/expenses_delete.php" style="display:inline;" onsubmit="return confirm(\'Delete this expense?\');">';
                                        echo '<input type="hidden" name="id" value="' . htmlspecialchars($r['id']) . '">';
                                        echo '<button class="btn btn-sm btn-outline-danger">Delete</button>';
                                        echo '</form>';
                                        echo '</td>';
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

        <div class="print-footer" style="margin-top: 30px;">
            <div style="display: flex; gap: 100px;">
                <div style="text-align: left;">
                    <p><strong>Prepared by:</strong></p>
                    <p style="margin-top: 40px; border-top: 1px solid #000; width: 200px;"></p>
                    <p style="font-size: 10px; color: #666; margin-top: 5px;">(Signature over printed name)</p>
                    <!-- <p style="margin-top: 10px;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Staff'); ?></p> -->
                    <p style="font-size: 12px; color: #666;">Date: <?php echo date('M j, Y'); ?></p>
                </div>
                <div style="text-align: left;">
                    <p><strong>Approved by:</strong></p>
                    <p style="margin-top: 40px; border-top: 1px solid #000; width: 200px;"></p>
                    <p style="font-size: 10px; color: #666; margin-top: 5px;">(Signature over printed name)</p>
            
                    <p style="font-size: 12px; color: #666;">Owner</p>
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
