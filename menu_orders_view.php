<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<div class="layout-content">
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Menu Orders — View</h4>
        <div class="card mt-3">
            <div class="table-responsive">
                <table id="menuOrdersTable" class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Order #</th>
      
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require __DIR__ . '/config/db.php';
                        $rows = [];
                        // Select only columns expected to exist in the current schema
                        $stmt = $conn->prepare('SELECT mo.id, mo.order_number, mo.admin_id, mo.total_amount, mo.status, mo.created_at, s.first_name AS admin_first, s.last_name AS admin_last FROM menu_orders mo LEFT JOIN staff s ON mo.admin_id = s.user_id ORDER BY mo.id DESC');
                        if (!$stmt) {
                            // show database error in table for easier debugging
                            $dberr = $conn->error;
                            echo '<tr><td colspan="9" class="text-danger text-center py-3">DB error: ' . htmlspecialchars($dberr) . '</td></tr>';
                        }
                        if ($stmt) {
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) $rows[] = $r;
                            $stmt->close();
                        }

                        if (empty($rows)) {
                            // DataTables requires the same number of cells as headers — output 7 tds total
                            echo '<tr>';
                            echo '<td class="text-center text-muted py-4" colspan="7">No menu orders found.</td>';
                            echo '</tr>';
                        } else {
                            foreach ($rows as $r):
                                // count items
                                $itemStmt = $conn->prepare('SELECT COUNT(*) as cnt FROM menu_order_items WHERE menu_order_id = ?');
                                $cnt = 0;
                                if ($itemStmt) {
                                    $itemStmt->bind_param('i', $r['id']);
                                    $itemStmt->execute();
                                    $cres = $itemStmt->get_result();
                                    if ($cres && $crow = $cres->fetch_assoc()) $cnt = $crow['cnt'];
                                    $itemStmt->close();
                                }

                                $placedBy = '-';
                                if (!empty($r['admin_first'] || $r['admin_last'])) $placedBy = trim($r['admin_first'] . ' ' . $r['admin_last']);
                                
                                // Determine status badge color
                                $statusClass = 'badge-secondary';
                                $status = strtolower($r['status']);
                                if ($status === 'paid') $statusClass = 'badge-success';
                                elseif ($status === 'pending') $statusClass = 'badge-warning';
                                elseif ($status === 'unpaid') $statusClass = 'badge-danger';
                                elseif ($status === 'cancelled') $statusClass = 'badge-dark';
                        ?>
                        <tr>
                            <td><?php echo (int)$r['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['order_number']); ?></strong></td>
                      
                   
                            <td><?php echo (int)$cnt; ?> item(s)</td>
                            <td><strong>₱<?php echo number_format($r['total_amount'],2); ?></strong></td>
                            <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($r['status'])); ?></span></td>
                            <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($r['created_at']))); ?></td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-icon btn-outline-info view-menu-order" data-id="<?php echo (int)$r['id']; ?>" title="View"><i class="feather icon-eye"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; } ?>
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
$(function(){
    $('#menuOrdersTable').DataTable({ order:[[0,'desc']], pageLength:10, columnDefs:[{ orderable:false, targets:6 }] });

    $('#menuOrdersTable').on('click', '.view-menu-order', function(){
        var id = $(this).data('id');
        window.location = 'menu_order_detail.php?id=' + id;
    });
});
</script>
