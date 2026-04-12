<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<?php
// Get selected category early to avoid undefined variable errors
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';
?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h4 class="font-weight-bold py-3 mb-0">Reports — Transactions<?php 
                if ($selected_category != 'all') {
                    $category_names = [
                        'online_fish' => 'Online Fish Orders',
                        'walkin_fish' => 'Walk-in Fish Orders',
                        'online_menu' => 'Online Menu Orders',
                        'walkin_menu' => 'Walk-in / Direct Menu Orders',
                        'online_cottage' => 'Online Cottage Reservations',
                        'walkin_cottage' => 'Walk-in Cottage Reservations',
                        'boat' => 'Boat Rentals',
                        'entrance' => 'Entrance Fees'
                    ];
                    echo ' (' . $category_names[$selected_category] . ')';
                }
            ?></h4>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" onclick="window.print();" title="Print Report">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        
        <!-- Date Range Filter -->
        <div class="card mb-4 no-print">
            <div class="card-body">
                <h5 class="card-title">Filter by Date Range</h5>
                <form method="GET" class="row g-3 align-items-end">
                    
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-control" id="category" name="category">
                            <option value="all" <?php echo ($selected_category == 'all') ? 'selected' : ''; ?>>ALL CATEGORIES</option>
                            <option value="online_fish" <?php echo ($selected_category == 'online_fish') ? 'selected' : ''; ?>>ONLINE FISH ORDERS</option>
                            <option value="walkin_fish" <?php echo ($selected_category == 'walkin_fish') ? 'selected' : ''; ?>>WALK-IN FISH ORDERS</option>
                            <option value="online_menu" <?php echo ($selected_category == 'online_menu') ? 'selected' : ''; ?>>ONLINE MENU ORDERS</option>
                            <option value="walkin_menu" <?php echo ($selected_category == 'walkin_menu') ? 'selected' : ''; ?>>WALK-IN / DIRECT MENU ORDERS</option>
                            <option value="online_cottage" <?php echo ($selected_category == 'online_cottage') ? 'selected' : ''; ?>>ONLINE COTTAGE RESERVATIONS</option>
                            <option value="walkin_cottage" <?php echo ($selected_category == 'walkin_cottage') ? 'selected' : ''; ?>>WALK-IN COTTAGE RESERVATIONS</option>
                            <option value="boat" <?php echo ($selected_category == 'boat') ? 'selected' : ''; ?>>BOAT RENTALS</option>
                            <option value="entrance" <?php echo ($selected_category == 'entrance') ? 'selected' : ''; ?>>ENTRANCE FEES</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                        <a href="reports_transactions.php" class="btn btn-secondary">Reset</a>
                    </div>
                    
                </form>
            </div>
        </div>
        
        <style>
            @media print {
                body { margin: 0; padding: 0.5in; font-size: 11px; }
                .no-print { display: none !important; }
                #layout-sidenav, #layout-navbar { display: none !important; }
                .container-fluid { margin: 0; padding: 0; }
                .print-header { display: block !important; }
                .page-break { page-break-after: always; }
                .service-section { page-break-inside: avoid; margin-bottom: 1.5rem; }
                .service-breakdown { display: block !important; }
                .print-footer { display: block !important; margin-top: 2rem; border-top: 2px solid #000; padding-top: 1rem; }
                .service-card { display: block !important; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 0.5rem; }
                table th { background-color: #e0e0e0; border: 1px solid #000; padding: 4px; text-align: left; font-weight: bold; }
                table td { border: 1px solid #999; padding: 4px; }
                h3 { margin: 0.5rem 0; font-size: 14px; }
                h4 { margin: 0.3rem 0; font-size: 12px; }
                .metric-row { display: flex; justify-content: space-around; margin: 0.5rem 0; }
                .metric-item { text-align: center; padding: 0.3rem 0.5rem; border: 1px solid #999; flex: 1; }
                /* Hide DataTables elements */
                .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate,
                .dt-buttons, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter,
                .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate {
                    display: none !important;
                }
            }
            
            @media screen {
                .print-header, .print-footer, .service-breakdown { display: none !important; }
            }
        </style>
        
        <!-- PRINT HEADER -->
        <div class="print-header" style="display: none;">
            <div style="text-align: center; margin-bottom: 15px;">
                <img src="assets/img/maataLogo.png" alt="Maata Logo" style="max-width: 120px; max-height: 80px; object-fit: contain;">
            </div>
            <h2 style="margin: 0; font-size: 20px; font-weight: bold; text-align: center;">MAATA FISH FARM SYSTEM</h2>
            <h3 style="margin: 5px 0; text-align: center; font-size: 14px;">TRANSACTIONS REPORT<?php 
                if ($selected_category != 'all') {
                    $category_names = [
                        'online_fish' => ' - ONLINE FISH ORDERS',
                        'walkin_fish' => ' - WALK-IN FISH ORDERS',
                        'online_menu' => ' - ONLINE MENU ORDERS',
                        'walkin_menu' => ' - WALK-IN / DIRECT MENU ORDERS',
                        'online_cottage' => ' - ONLINE COTTAGE RESERVATIONS',
                        'walkin_cottage' => ' - WALK-IN COTTAGE RESERVATIONS',
                        'boat' => ' - BOAT RENTALS',
                        'entrance' => ' - ENTRANCE FEES'
                    ];
                    echo $category_names[$selected_category];
                }
            ?></h3>
            <div style="text-align: center; margin: 10px 0; font-size: 11px;">
                <?php if (!empty($_GET['start_date']) && !empty($_GET['end_date'])): ?>
                    <strong>Report Period:</strong> <?php echo date('M d, Y', strtotime($_GET['start_date'])) . ' to ' . date('M d, Y', strtotime($_GET['end_date'])); ?><br>
                <?php else: ?>
                    <strong>Report Period:</strong> All Time<br>
                <?php endif; ?>
                <strong>Generated:</strong> <?php echo date('F d, Y \a\t h:i A'); ?>
            </div>
            <hr style="margin: 10px 0;"/>
        </div>
        <div class="card mt-3">
            <div class="table-responsive">
                <table id="transactionsTable" class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Transaction ID</th>
                            <th>Reference #</th>
                            <th>Customer</th>
                            <th>Item/Service</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                            <th>Transaction Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require __DIR__ . '/config/db.php';
                        $transactions = [];

                        // Build date filter conditions
                        $date_conditions = "";
                        $params = [];
                        $types = "";

                        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                            $start_date = date('Y-m-d', strtotime($_GET['start_date']));
                            $end_date = date('Y-m-d', strtotime($_GET['end_date']));
                            $date_conditions = " AND DATE(t.transaction_date) BETWEEN ? AND ?";
                            $params[] = $start_date;
                            $params[] = $end_date;
                            $types .= "ss";
                        }

                        // Build category filter
                        $category_condition = "";
                        if ($selected_category != 'all') {
                            switch ($selected_category) {
                                case 'online_fish':
                                    $category_condition = " AND t.transaction_type = 'fish_order' AND t.source = 'online'";
                                    break;
                                case 'walkin_fish':
                                    $category_condition = " AND t.transaction_type = 'fish_order' AND t.source = 'walkin'";
                                    break;
                                case 'online_menu':
                                    $category_condition = " AND t.transaction_type = 'menu_order' AND t.source = 'online'";
                                    break;
                                case 'walkin_menu':
                                    $category_condition = " AND t.transaction_type = 'menu_order' AND t.source = 'walkin'";
                                    break;
                                case 'online_cottage':
                                    $category_condition = " AND t.transaction_type = 'cottage_reservation' AND t.source = 'online'";
                                    break;
                                case 'walkin_cottage':
                                    $category_condition = " AND t.transaction_type = 'cottage_reservation' AND t.source = 'walkin'";
                                    break;
                                case 'boat':
                                    $category_condition = " AND t.transaction_type = 'boat_rental'";
                                    break;
                                case 'entrance':
                                    $category_condition = " AND t.transaction_type = 'entrance_fee'";
                                    break;
                            }
                        }

                        // Query to get all transactions with unified structure
                        $query = "
                            SELECT * FROM (
                                -- Online order items from orders
                                SELECT 
                                    CONCAT('FO-', oi.id) as transaction_id,
                                    o.order_number as reference_number,
                                    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                                    COALESCE(f.name, p.name) as item_name,
                                    oi.quantity,
                                    oi.unit_price,
                                    oi.subtotal,
                                    o.created_at as transaction_date,
                                    o.status,
                                    CASE WHEN f.fish_id IS NOT NULL THEN 'fish_order' ELSE 'menu_order' END as transaction_type,
                                    'online' as source
                                FROM order_items oi
                                JOIN orders o ON oi.order_id = o.id
                                JOIN customers c ON o.customer_id = c.id
                                LEFT JOIN fish_species f ON oi.product_id = f.fish_id
                                LEFT JOIN products p ON oi.product_id = p.id
                                WHERE o.status IN ('paid', 'completed')
                                
                                UNION ALL
                                
                                -- Walk-in fish orders
                                SELECT 
                                    CONCAT('WF-', fo.id) as transaction_id,
                                    fo.order_number as reference_number,
                                    COALESCE(fo.customer_name, 'Walk-in Customer') as customer_name,
                                    'Walk-in Fish Order' as item_name,
                                    1 as quantity,
                                    fo.total_amount as unit_price,
                                    fo.total_amount as subtotal,
                                    fo.created_at as transaction_date,
                                    fo.status,
                                    'fish_order' as transaction_type,
                                    'walkin' as source
                                FROM fish_orders fo
                                WHERE fo.status = 'paid'
                                
                                UNION ALL
                                
                                -- Direct menu orders
                                SELECT 
                                    CONCAT('MO-', moi.id) as transaction_id,
                                    mo.order_number as reference_number,
                                    'Direct/Admin Order' as customer_name,
                                    COALESCE(f.name, p.name) as item_name,
                                    moi.quantity,
                                    moi.unit_price,
                                    moi.subtotal,
                                    mo.created_at as transaction_date,
                                    mo.status,
                                    'menu_order' as transaction_type,
                                    'walkin' as source
                                FROM menu_order_items moi
                                JOIN menu_orders mo ON moi.menu_order_id = mo.id
                                LEFT JOIN fish_species f ON (moi.item_type = 'fish' AND moi.item_id = f.fish_id)
                                LEFT JOIN products p ON (moi.item_type = 'product' AND moi.item_id = p.id)
                                WHERE mo.status IN ('paid', 'completed')
                                
                                UNION ALL
                                
                                -- Cottage Reservations
                                SELECT 
                                    CONCAT('CR-', r.id) as transaction_id,
                                    r.reservation_number as reference_number,
                                    COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Walk-in Customer') as customer_name,
                                    CONCAT('Cottage ', cot.cottage_number) as item_name,
                                    1 as quantity,
                                    r.total_amount as unit_price,
                                    r.total_amount as subtotal,
                                    r.created_at as transaction_date,
                                    r.status,
                                    'cottage_reservation' as transaction_type,
                                    CASE WHEN r.is_manual = 0 THEN 'online' ELSE 'walkin' END as source
                                FROM reservations r
                                LEFT JOIN customers c ON r.customer_id = c.id
                                JOIN cottages cot ON r.cottage_id = cot.id
                                WHERE r.reservation_type = 'cottage' AND r.status = 'completed'
                                
                                UNION ALL
                                
                                -- Boat Rentals
                                SELECT 
                                    CONCAT('BR-', br.id) as transaction_id,
                                    'Boat Rental' as reference_number,
                                    COALESCE(CONCAT(c.first_name, ' ', c.last_name), 'Walk-in Customer') as customer_name,
                                    CONCAT('Boat Rental - ', br.boat_name) as item_name,
                                    1 as quantity,
                                    br.total_amount as unit_price,
                                    br.total_amount as subtotal,
                                    br.created_at as transaction_date,
                                    br.status,
                                    'boat_rental' as transaction_type,
                                    'online' as source
                                FROM boat_rentals br
                                LEFT JOIN customers c ON br.customer_id = c.id
                                WHERE br.status = 'completed'
                            ) t
                            WHERE 1=1 {$date_conditions} {$category_condition}
                            ORDER BY t.transaction_date DESC, t.transaction_id ASC
                        ";

                        $stmt = $conn->prepare($query);
                        if ($stmt) {
                            if (!empty($params)) {
                                $stmt->bind_param($types, ...$params);
                            }
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) {
                                $transactions[] = $r;
                            }
                            $stmt->close();
                        }

                        // Add entrance fee records from activity logs
                        if ($selected_category === 'all' || $selected_category === 'entrance') {
                            $entrance_sql = "SELECT id, new_values, timestamp FROM activity_logs WHERE entity_type = 'entrance_fee' AND activity_type = 'CREATE' ORDER BY timestamp DESC";
                            $entrance_stmt = $conn->prepare($entrance_sql);
                            if ($entrance_stmt) {
                                $entrance_stmt->execute();
                                $entrance_res = $entrance_stmt->get_result();
                                while ($row = $entrance_res->fetch_assoc()) {
                                    $new_values = json_decode($row['new_values'], true);
                                    $num_guests = isset($new_values['num_guests']) ? (int)$new_values['num_guests'] : 0;
                                    $fee_per_guest = isset($new_values['fee_per_guest']) ? (float)$new_values['fee_per_guest'] : 0;
                                    $total_amount = isset($new_values['total']) ? (float)$new_values['total'] : ($num_guests * $fee_per_guest);
                                    $timestamp = $row['timestamp'];
                                    
                                    if (!empty($start_date) && !empty($end_date)) {
                                        $dateOnly = date('Y-m-d', strtotime($timestamp));
                                        if ($dateOnly < $start_date || $dateOnly > $end_date) {
                                            continue;
                                        }
                                    }

                                    $transactions[] = [
                                        'transaction_id' => 'EF-' . $row['id'],
                                        'reference_number' => 'Entrance Fee',
                                        'customer_name' => 'Entrance Fee Collection',
                                        'item_name' => $num_guests . ' guest(s) @ ₱' . number_format($fee_per_guest, 2),
                                        'quantity' => $num_guests,
                                        'unit_price' => $fee_per_guest,
                                        'subtotal' => $total_amount,
                                        'transaction_date' => $timestamp,
                                        'status' => 'paid',
                                        'transaction_type' => 'entrance_fee',
                                        'source' => 'walkin'
                                    ];
                                }
                                $entrance_stmt->close();
                            }
                        }

                        usort($transactions, function($a, $b) {
                            return strtotime($b['transaction_date']) <=> strtotime($a['transaction_date']);
                        });

                        if (empty($transactions)) {
                            echo '<tr>';
                            echo '<td class="text-center text-muted py-4" colspan="1">No transactions found.</td>';
                            echo str_repeat('<td></td>', 8);
                            echo '</tr>';
                        } else {
                            foreach ($transactions as $t):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['transaction_id']); ?></td>
                            <td><?php echo htmlspecialchars($t['reference_number']); ?></td>
                            <td><?php echo htmlspecialchars($t['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['item_name']); ?></td>
                            <td><?php echo (int)$t['quantity']; ?></td>
                            <td>₱<?php echo number_format($t['unit_price'], 2); ?></td>
                            <td>₱<?php echo number_format($t['subtotal'], 2); ?></td>
                            <td><?php echo date('M j, Y g:iA', strtotime($t['transaction_date'])); ?></td>
                            <td><span class="badge badge-success"><?php echo htmlspecialchars($t['status']); ?></span></td>
                        </tr>
                        <?php
                            endforeach;
                        }
                        ?>



                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- PRINT FOOTER -->
        <div class="print-footer" style="display: none;">
            <div style="margin-top: 3rem; display: flex; justify-content: space-between;">
                <div style="text-align: center; flex: 1;">
                    <p style="margin: 0; font-size: 12px; font-weight: bold;">Prepared by:</p>
                    <div style="border-bottom: 1px solid #000; width: 200px; margin: 40px auto 5px auto;"></div>
                    <p style="margin: 0; font-size: 11px;"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'System Administrator'); ?></p>
                    <p style="margin: 0; font-size: 10px; color: #666;">Date: <?php echo date('F d, Y'); ?></p>
                </div>
                <div style="text-align: center; flex: 1;">
                    <p style="margin: 0; font-size: 12px; font-weight: bold;">Approved by:</p>
                    <div style="border-bottom: 1px solid #000; width: 200px; margin: 40px auto 5px auto;"></div>
                    <p style="margin: 0; font-size: 11px;">___________________________</p>
                    <p style="margin: 0; font-size: 10px; color: #666;">Date: _______________</p>
                </div>
            </div>
            <div style="text-align: center; margin-top: 2rem; font-size: 10px; color: #666; border-top: 1px solid #ccc; padding-top: 1rem;">
                <p>This report was generated by Maata Fish Farm System on <?php echo date('F d, Y \a\t h:i A'); ?></p>
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
    // Only initialize DataTable if not printing
    if (window.matchMedia && !window.matchMedia('print').matches) {
        $('#transactionsTable').DataTable({
            pageLength: 25,
            order: [[7, 'desc']],
            columns: [
                null,
                null,
                null,
                null,
                { type: 'num' },
                { type: 'num-fmt' },
                { type: 'num-fmt' },
                { type: 'date' },
                null
            ],
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: 'lfrtip'
        });
    }
});
</script>