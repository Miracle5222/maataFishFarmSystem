<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>

<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<?php
// Get database connection
require __DIR__ . '/config/db.php';

// Dashboard Metrics
$metrics = [];

// 1. Total Customers
$cust_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM customers');
$cust_stmt->execute();
$cust_res = $cust_stmt->get_result();
$metrics['total_customers'] = $cust_res->fetch_assoc()['cnt'] ?? 0;
$cust_stmt->close();

// 2. Total Reservations
$res_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM reservations');
$res_stmt->execute();
$res_res = $res_stmt->get_result();
$metrics['total_reservations'] = $res_res->fetch_assoc()['cnt'] ?? 0;
$res_stmt->close();

// 3. Total Orders
$ord_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM orders');
$ord_stmt->execute();
$ord_res = $ord_stmt->get_result();
$metrics['total_orders'] = $ord_res->fetch_assoc()['cnt'] ?? 0;
$ord_stmt->close();

// 4. Total Menu Orders
$menu_ord_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM menu_orders');
$menu_ord_stmt->execute();
$menu_ord_res = $menu_ord_stmt->get_result();
$metrics['total_menu_orders'] = $menu_ord_res->fetch_assoc()['cnt'] ?? 0;
$menu_ord_stmt->close();

// 5. Total Sales (from orders)
$sales_stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount), 0) as total FROM orders');
$sales_stmt->execute();
$sales_res = $sales_stmt->get_result();
$metrics['total_sales'] = $sales_res->fetch_assoc()['total'] ?? 0;
$sales_stmt->close();

// 6. Total Menu Sales
$menu_sales_stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount), 0) as total FROM menu_orders');
$menu_sales_stmt->execute();
$menu_sales_res = $menu_sales_stmt->get_result();
$metrics['total_menu_sales'] = $menu_sales_res->fetch_assoc()['total'] ?? 0;
$menu_sales_stmt->close();

// 7. Total Sales (Combined) - Note: This will be updated after calculating all revenue streams
$metrics['combined_sales'] = $metrics['total_sales'] + $metrics['total_menu_sales'];

// 8. Today's Sales
$today = date('Y-m-d');
$today_stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) = ? AND status IN ("paid", "completed")
    UNION ALL
    SELECT COALESCE(SUM(total_amount), 0) as total FROM menu_orders WHERE DATE(created_at) = ? AND status IN ("paid", "completed")
    UNION ALL
    SELECT COALESCE(SUM(total_amount), 0) as total FROM fish_orders WHERE DATE(created_at) = ? AND status = "paid"
    UNION ALL
    SELECT COALESCE(SUM(total_amount), 0) as total FROM boat_rentals WHERE DATE(created_at) = ? AND status = "completed"
    UNION ALL
    SELECT COALESCE(SUM(total_amount), 0) as total FROM reservations WHERE DATE(created_at) = ? AND reservation_type = "cottage" AND status = "completed"');
$today_stmt->bind_param('sssss', $today, $today, $today, $today, $today);
$today_stmt->execute();
$today_res = $today_stmt->get_result();
$today_total = 0;
while ($row = $today_res->fetch_assoc()) {
    $today_total += $row['total'];
}
$metrics['today_sales'] = $today_total;
$today_stmt->close();

// 9. This Month's Sales
$month_start = date('Y-m-01');
$month_stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) >= ? AND status IN ("paid", "completed")
    UNION ALL
    SELECT COALESCE(SUM(total_amount), 0) as total FROM menu_orders WHERE DATE(created_at) >= ? AND status IN ("paid", "completed")
    UNION ALL
    SELECT COALESCE(SUM(total_amount), 0) as total FROM fish_orders WHERE DATE(created_at) >= ? AND status = "paid"
    UNION ALL
    SELECT COALESCE(SUM(total_amount), 0) as total FROM boat_rentals WHERE DATE(created_at) >= ? AND status = "completed"
    UNION ALL
    SELECT COALESCE(SUM(total_amount), 0) as total FROM reservations WHERE DATE(created_at) >= ? AND reservation_type = "cottage" AND status = "completed"');
$month_stmt->bind_param('sssss', $month_start, $month_start, $month_start, $month_start, $month_start);
$month_stmt->execute();
$month_res = $month_stmt->get_result();
$month_total = 0;
while ($row = $month_res->fetch_assoc()) {
    $month_total += $row['total'];
}
$metrics['month_sales'] = $month_total;
$month_stmt->close();

// 10. Pending Orders
$pending_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM menu_orders WHERE status = "pending"');
$pending_stmt->execute();
$pending_res = $pending_stmt->get_result();
$metrics['pending_orders'] = $pending_res->fetch_assoc()['cnt'] ?? 0;
$pending_stmt->close();

// 11. Products Count
$prod_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM products');
$prod_stmt->execute();
$prod_res = $prod_stmt->get_result();
$metrics['total_products'] = $prod_res->fetch_assoc()['cnt'] ?? 0;
$prod_stmt->close();

// 12. Fish Species Count
$fish_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM fish_species');
$fish_stmt->execute();
$fish_res = $fish_stmt->get_result();
$metrics['total_fish'] = $fish_res->fetch_assoc()['cnt'] ?? 0;
$fish_stmt->close();

// 13. Monthly Sales Data (Last 12 months) for Chart
// 13. Monthly Sales Data (Last 12 months) for Chart
$months_data = [];
$combined_values = [];
$fish_values = [];
$menu_values = [];
for ($i = 11; $i >= 0; $i--) {
    $date = date('Y-m-01', strtotime("-$i months"));
    $next_date = date('Y-m-01', strtotime("+1 month", strtotime($date)));
    $month_label = date('M', strtotime($date));

    // Combined (fish orders + menu_orders)
    $combined_total = 0.0;
    $ord_stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount),0) as total FROM orders WHERE created_at >= ? AND created_at < ?');
    if ($ord_stmt) {
        $ord_stmt->bind_param('ss', $date, $next_date);
        $ord_stmt->execute();
        $ord_res = $ord_stmt->get_result();
        $combined_total += (float)($ord_res->fetch_assoc()['total'] ?? 0);
        $ord_stmt->close();
    }
    $menu_stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount),0) as total FROM menu_orders WHERE created_at >= ? AND created_at < ?');
    if ($menu_stmt) {
        $menu_stmt->bind_param('ss', $date, $next_date);
        $menu_stmt->execute();
        $menu_res = $menu_stmt->get_result();
        $combined_total += (float)($menu_res->fetch_assoc()['total'] ?? 0);
        $menu_stmt->close();
    }

    // Fish sales from order_items (join orders)
    // Note: some installations may not have `item_type` on `order_items` (migration not applied).
    // Use product_id join to fish_species which is always present in this schema.
    $fish_total = 0.0;
    $fish_stmt = $conn->prepare('SELECT COALESCE(SUM(oi.subtotal),0) as total FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.product_id IN (SELECT fish_id FROM fish_species) AND o.created_at >= ? AND o.created_at < ?');
    if ($fish_stmt) {
        $fish_stmt->bind_param('ss', $date, $next_date);
        $fish_stmt->execute();
        $fish_res = $fish_stmt->get_result();
        $fish_total = (float)($fish_res->fetch_assoc()['total'] ?? 0);
        $fish_stmt->close();
    }

    // Menu-only sales (menu_orders)
    $menu_total = 0.0;
    $menu_chart_stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount),0) as total FROM menu_orders WHERE created_at >= ? AND created_at < ?');
    if ($menu_chart_stmt) {
        $menu_chart_stmt->bind_param('ss', $date, $next_date);
        $menu_chart_stmt->execute();
        $mres = $menu_chart_stmt->get_result();
        $menu_total = (float)($mres->fetch_assoc()['total'] ?? 0);
        $menu_chart_stmt->close();
    }

    $months_data[] = ['label' => $month_label];
    $combined_values[] = round($combined_total,2);
    $fish_values[] = round($fish_total,2);
    $menu_values[] = round($menu_total,2);
}

// 14. Top 10 Customers by Total Spending
$top_customers = [];
$top_cust_stmt = $conn->prepare('
    SELECT c.id, c.first_name, c.last_name, c.email, 
           COUNT(o.id) as order_count, 
           COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM customers c
    LEFT JOIN orders o ON c.id = o.customer_id
    GROUP BY c.id, c.first_name, c.last_name, c.email
    HAVING order_count > 0 OR total_spent > 0
    ORDER BY total_spent DESC, order_count DESC
    LIMIT 10
');
$top_cust_stmt->execute();
$top_cust_res = $top_cust_stmt->get_result();
while ($row = $top_cust_res->fetch_assoc()) {
    $top_customers[] = $row;
}
$top_cust_stmt->close();

// 15. Order Type Distribution Data
$order_dist_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM orders');
$order_dist_stmt->execute();
$order_dist_res = $order_dist_stmt->get_result();
$regular_orders_count = $order_dist_res->fetch_assoc()['cnt'] ?? 0;
$order_dist_stmt->close();

$menu_dist_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM menu_orders');
$menu_dist_stmt->execute();
$menu_dist_res = $menu_dist_stmt->get_result();
$menu_orders_count = $menu_dist_res->fetch_assoc()['cnt'] ?? 0;
$menu_dist_stmt->close();

// ===== BOAT RENTALS REVENUE (calculated early for revenue distribution) =====
$walkin_boat_revenue = 0;
$walkin_boat_count = 0;
$online_boat_revenue = 0;
$online_boat_count = 0;
$boat_stmt = $conn->prepare('
    SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt 
    FROM boat_rentals 
    WHERE status = "completed"
');
if ($boat_stmt) {
    $boat_stmt->execute();
    $boat_res = $boat_stmt->get_result();
    if ($boat_row = $boat_res->fetch_assoc()) {
        $walkin_boat_revenue = (float)($boat_row['total'] ?? 0);
        $walkin_boat_count = (int)($boat_row['cnt'] ?? 0);
    }
    $boat_stmt->close();
}
$total_boat_revenue = $walkin_boat_revenue + $online_boat_revenue;
$total_boat_count = $walkin_boat_count + $online_boat_count;

// ===== ENTRANCE FEE METRICS =====
$ENTRANCE_FEE = 50; // fixed fee per guest for now
$entrance_total_guests = 0; // (kept for potential use)
$entrance_total_revenue = 0.0;
$entrance_today_guests = 0;
$entrance_today_revenue = 0.0;
$today_date = date('Y-m-d');

$entr_stmt = $conn->prepare("SELECT new_values, timestamp FROM activity_logs WHERE entity_type = 'entrance_fee' AND activity_type = 'CREATE'");
if ($entr_stmt) {
    $entr_stmt->execute();
    $entr_res = $entr_stmt->get_result();
    while ($erow = $entr_res->fetch_assoc()) {
        $nv = json_decode($erow['new_values'], true);
        $num = intval($nv['num_guests'] ?? 0);
        $fee_per_guest = isset($nv['fee_per_guest']) ? (float)$nv['fee_per_guest'] : $ENTRANCE_FEE;
        if ($num > 0) {
            $revenue = $num * $fee_per_guest;
            $entrance_total_revenue += $revenue;
            if (date('Y-m-d', strtotime($erow['timestamp'])) === $today_date) {
                $entrance_today_guests += $num;
                $entrance_today_revenue += $revenue;
            }
        }
    }
    $entr_stmt->close();
}

// Online Fish Orders (from orders table with is_manual = 0, containing fish items only)
// Only count PAID orders (pending orders should not count as sales yet)
$online_fish_stmt = $conn->prepare('
    SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt 
    FROM orders o
    WHERE o.is_manual = 0 AND o.status IN ("paid", "completed")
    AND EXISTS (
        SELECT 1 FROM order_items oi 
        JOIN fish_species fs ON oi.product_id = fs.fish_id 
        WHERE oi.order_id = o.id
    )
');
$online_fish_revenue = 0;
$online_fish_count = 0;
if ($online_fish_stmt) {
    $online_fish_stmt->execute();
    $online_fish_res = $online_fish_stmt->get_result();
    $online_fish_row = $online_fish_res->fetch_assoc();
    $online_fish_revenue = (float)($online_fish_row['total'] ?? 0);
    $online_fish_count = (int)($online_fish_row['cnt'] ?? 0);
    $online_fish_stmt->close();
}

// Online Menu Orders (from orders table with is_manual = 0, containing menu items only)
// Only count PAID orders (pending orders should not count as sales yet)
$online_menu_stmt = $conn->prepare('
    SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt 
    FROM orders o
    WHERE o.is_manual = 0 AND o.status IN ("paid", "completed")
    AND EXISTS (
        SELECT 1 FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = o.id
    )
');
$online_menu_revenue = 0;
$online_menu_count = 0;
if ($online_menu_stmt) {
    $online_menu_stmt->execute();
    $online_menu_res = $online_menu_stmt->get_result();
    $online_menu_row = $online_menu_res->fetch_assoc();
    $online_menu_revenue = (float)($online_menu_row['total'] ?? 0);
    $online_menu_count = (int)($online_menu_row['cnt'] ?? 0);
    $online_menu_stmt->close();
}

// Walk-In Fish Orders (from fish_orders table)
$walkin_fish_stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM fish_orders WHERE status = "paid"');
$walkin_fish_revenue = 0;
$walkin_fish_count = 0;
if ($walkin_fish_stmt) {
    $walkin_fish_stmt->execute();
    $walkin_fish_res = $walkin_fish_stmt->get_result();
    $walkin_fish_row = $walkin_fish_res->fetch_assoc();
    $walkin_fish_revenue = (float)($walkin_fish_row['total'] ?? 0);
    $walkin_fish_count = (int)($walkin_fish_row['cnt'] ?? 0);
    $walkin_fish_stmt->close();
}

// ===== COTTAGE RENTALS REVENUE =====

// Online Cottage Reservations (is_manual = 0)
$online_cottage_stmt = $conn->prepare('
    SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt 
    FROM reservations 
    WHERE reservation_type = "cottage" 
    AND status = "completed"
    AND is_manual = 0
');
$online_cottage_revenue = 0;
$online_cottage_count = 0;
if ($online_cottage_stmt) {
    $online_cottage_stmt->execute();
    $online_cottage_res = $online_cottage_stmt->get_result();
    if ($online_cottage_row = $online_cottage_res->fetch_assoc()) {
        $online_cottage_revenue = (float)($online_cottage_row['total'] ?? 0);
        $online_cottage_count = (int)($online_cottage_row['cnt'] ?? 0);
    }
    $online_cottage_stmt->close();
}

// Walk-In/Manual Cottage Reservations (is_manual = 1)
$walkin_cottage_stmt = $conn->prepare('
    SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt 
    FROM reservations 
    WHERE reservation_type = "cottage" 
    AND status = "completed"
    AND is_manual = 1
');
$walkin_cottage_revenue = 0;
$walkin_cottage_count = 0;
if ($walkin_cottage_stmt) {
    $walkin_cottage_stmt->execute();
    $walkin_cottage_res = $walkin_cottage_stmt->get_result();
    if ($walkin_cottage_row = $walkin_cottage_res->fetch_assoc()) {
        $walkin_cottage_revenue = (float)($walkin_cottage_row['total'] ?? 0);
        $walkin_cottage_count = (int)($walkin_cottage_row['cnt'] ?? 0);
    }
    $walkin_cottage_stmt->close();
}

$total_cottage_revenue = $walkin_cottage_revenue + $online_cottage_revenue;
$total_cottage_count = $walkin_cottage_count + $online_cottage_count;

// ===== CALCULATE TOTAL REVENUE AND REVENUE DISTRIBUTION =====
// Update combined sales to include ALL revenue streams (separated by order type)
$metrics['combined_sales'] = (float)$online_fish_revenue + (float)$online_menu_revenue + (float)$walkin_fish_revenue + (float)$metrics['total_menu_sales'] + (float)$total_cottage_revenue + (float)$total_boat_revenue + (float)$entrance_total_revenue;

// Revenue distribution for ALL revenue sources
$total_revenue_for_dist = $metrics['combined_sales'];
$revenue_dist = [];

// Only add non-zero revenue streams to the pie chart
if ($online_fish_revenue > 0) {
    $revenue_dist[] = ['label' => 'Online Fish Orders', 'value' => (float)$online_fish_revenue, 'percentage' => round(($online_fish_revenue / max($total_revenue_for_dist, 0.01)) * 100, 1)];
}
if ($walkin_fish_revenue > 0) {
    $revenue_dist[] = ['label' => 'Walk-in Fish Orders', 'value' => (float)$walkin_fish_revenue, 'percentage' => round(($walkin_fish_revenue / max($total_revenue_for_dist, 0.01)) * 100, 1)];
}
if ($online_menu_revenue > 0) {
    $revenue_dist[] = ['label' => 'Online Menu Orders', 'value' => (float)$online_menu_revenue, 'percentage' => round(($online_menu_revenue / max($total_revenue_for_dist, 0.01)) * 100, 1)];
}
if ($metrics['total_menu_sales'] > 0) {
    $revenue_dist[] = ['label' => 'Direct Menu Orders', 'value' => (float)$metrics['total_menu_sales'], 'percentage' => round(($metrics['total_menu_sales'] / max($total_revenue_for_dist, 0.01)) * 100, 1)];
}
if ($total_cottage_revenue > 0) {
    $revenue_dist[] = ['label' => 'Cottage Rentals', 'value' => (float)$total_cottage_revenue, 'percentage' => round(($total_cottage_revenue / max($total_revenue_for_dist, 0.01)) * 100, 1)];
}
if ($total_boat_revenue > 0) {
    $revenue_dist[] = ['label' => 'Boat Rentals', 'value' => (float)$total_boat_revenue, 'percentage' => round(($total_boat_revenue / max($total_revenue_for_dist, 0.01)) * 100, 1)];
}
if ($entrance_total_revenue > 0) {
    $revenue_dist[] = ['label' => 'Entrance Fees', 'value' => (float)$entrance_total_revenue, 'percentage' => round(($entrance_total_revenue / max($total_revenue_for_dist, 0.01)) * 100, 1)];
}

// If no revenue, add a placeholder
if (empty($revenue_dist)) {
    $revenue_dist = [['label' => 'No Revenue', 'value' => 0, 'percentage' => 0]];
}

// Convert chart data to JSON for JavaScript
$months_labels = $months_data ? array_map(function($m) { return $m['label']; }, $months_data) : [];
// use the separate series computed above
$combined_values = isset($combined_values) ? $combined_values : [];
$fish_values = isset($fish_values) ? $fish_values : [];
$menu_values = isset($menu_values) ? $menu_values : [];

$combined_chart_json = json_encode(['labels' => $months_labels, 'values' => $combined_values]);
$fish_chart_json = json_encode(['labels' => $months_labels, 'values' => $fish_values]);
$menu_chart_json = json_encode(['labels' => $months_labels, 'values' => $menu_values]);
$revenue_dist_json = json_encode($revenue_dist);
$top_customers_json = json_encode(array_map(function($c) { 
    return ['name' => $c['first_name'] . ' ' . $c['last_name'], 'orders' => $c['order_count'], 'spent' => $c['total_spent']]; 
}, $top_customers));

// Fish total sales overall (from order_items)
$fish_total_stmt = $conn->prepare('SELECT COALESCE(SUM(subtotal),0) as total FROM order_items WHERE product_id IN (SELECT fish_id FROM fish_species)');
$fish_total = 0;
if ($fish_total_stmt) {
    $fish_total_stmt->execute();
    $ftres = $fish_total_stmt->get_result();
    $fish_total = (float)($ftres->fetch_assoc()['total'] ?? 0);
    $fish_total_stmt->close();
}

// Menu total already in metrics
$menu_total = $metrics['total_menu_sales'];

// Total expenses (used to compute net revenue)
$expenses_stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) as total FROM expenses');
$total_expenses = 0.0;
if ($expenses_stmt) {
    $expenses_stmt->execute();
    $er = $expenses_stmt->get_result();
    $total_expenses = (float)($er->fetch_assoc()['total'] ?? 0);
    $expenses_stmt->close();
}

// Compute net revenue BEFORE STORAGE - will be recalculated after all revenue streams are known
$combined_sales_temp = max(0.0, (float)$metrics['total_sales'] + (float)$metrics['total_menu_sales']);
$fish_share = $combined_sales_temp > 0 ? ((float)$metrics['total_sales'] / $combined_sales_temp) : 0.0;
$menu_share = $combined_sales_temp > 0 ? ((float)$metrics['total_menu_sales'] / $combined_sales_temp) : 0.0;

// Calculate net revenue after all revenue streams are known
// Net Combined Revenue = Total Revenue - Total Expenses
$net_combined = (float)$metrics['combined_sales'] - (float)$total_expenses;

?>

<!-- [ Layout content ] Start -->

<!-- [ Layout content ] Start -->
<div class="layout-content">

    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Dashboard</h4>
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                <li class="breadcrumb-item active">Analytics</li>
            </ol>
        </div>

        <!-- KPI Cards Row -->
        <div class="row">
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($metrics['today_sales'], 2); ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-primary">Today</span> Sales</p>
                            </div>
                            <div class="feather icon-shopping-cart display-4 text-primary"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($metrics['month_sales'], 2); ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-success">This Month</span> Sales</p>
                            </div>
                            <div class="feather icon-bar-chart-2 display-4 text-success"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($metrics['combined_sales'], 2); ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-warning">Total</span> Sales</p>
                            </div>
                            <div class="feather icon-dollar-sign display-4 text-warning"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo $metrics['pending_orders']; ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-danger">Pending</span> Orders</p>
                            </div>
                            <div class="feather icon-clock display-4 text-danger"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entrance Fee Metrics Row -->
        <div class="row">
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo number_format($entrance_today_guests); ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-primary">Today</span> Entrance Guests</p>
                            </div>
                            <div class="feather icon-users display-4 text-info"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($entrance_total_revenue, 2); ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-info">Total</span> Entrance Revenue</p>
                            </div>
                            <div class="feather icon-dollar-sign display-4 text-primary"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue by Category Row -->
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($online_fish_revenue, 2); ?></h2>
                                <p class="text-muted mb-0"><i class="feather icon-shopping-bag"></i> <strong>Online Fish Orders</strong></p>
                                <small class="text-muted"><?php echo $online_fish_count; ?> completed</small>
                            </div>
                            <div class="feather icon-shopping-cart display-4 text-primary" style="opacity: 0.5;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($walkin_fish_revenue, 2); ?></h2>
                                <p class="text-muted mb-0"><i class="feather icon-shopping-bag"></i> <strong>Walk-In Fish Orders</strong></p>
                                <small class="text-muted"><?php echo $walkin_fish_count; ?> completed</small>
                            </div>
                            <div class="feather icon-shopping-cart display-4 text-danger" style="opacity: 0.5;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($online_menu_revenue, 2); ?></h2>
                                <p class="text-muted mb-0"><i class="feather icon-utensils"></i> <strong>Online Menu Orders</strong></p>
                                <small class="text-muted"><?php echo $online_menu_count; ?> completed</small>
                            </div>
                            <div class="feather icon-coffee display-4 text-info" style="opacity: 0.5;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($metrics['total_menu_sales'], 2); ?></h2>
                                <p class="text-muted mb-0"><i class="feather icon-utensils"></i> <strong>Direct Menu Orders</strong></p>
                                <small class="text-muted">Walk-in Restaurant Sales</small>
                            </div>
                            <div class="feather icon-coffee display-4 text-warning" style="opacity: 0.5;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($online_cottage_revenue, 2); ?></h2>
                                <p class="text-muted mb-0"><i class="feather icon-home"></i> <strong>Online Cottage</strong></p>
                                <small class="text-muted"><?php echo $online_cottage_count; ?> completed</small>
                            </div>
                            <div class="feather icon-home display-4 text-success" style="opacity: 0.5;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($walkin_cottage_revenue, 2); ?></h2>
                                <p class="text-muted mb-0"><i class="feather icon-home"></i> <strong>Walk-In Cottage</strong></p>
                                <small class="text-muted"><?php echo $walkin_cottage_count; ?> completed</small>
                            </div>
                            <div class="feather icon-home display-4 text-warning" style="opacity: 0.5;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($total_boat_revenue, 2); ?></h2>
                                <p class="text-muted mb-0"><i class="feather icon-anchor"></i> <strong>Boat Revenue</strong></p>
                                <small class="text-muted"><?php echo $total_boat_count; ?> completed</small>
                            </div>
                            <div class="feather icon-anchor display-4 text-danger" style="opacity: 0.5;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2">₱<?php echo number_format($entrance_total_revenue, 2); ?></h2>
                                <p class="text-muted mb-0"><i class="feather icon-gate"></i> <strong>Entrance Fee</strong></p>
                                <small class="text-muted"><?php echo $entrance_total_guests; ?> guests</small>
                            </div>
                            <div class="feather icon-users display-4 text-secondary" style="opacity: 0.5;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- Sales Trend Chart -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header with-elements">
                        <h6 class="card-header-title mb-0">Sales Trend (Last 12 Months)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="salesTrendChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Revenue Breakdown Table (moved from below) -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header with-elements pb-0">
                        <h6 class="card-header-title mb-0">Revenue Breakdown</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <td><strong>Online Fish Orders:</strong></td>
                                    <td class="text-right">₱<?php echo number_format($online_fish_revenue, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Online Menu Orders:</strong></td>
                                    <td class="text-right">₱<?php echo number_format($online_menu_revenue, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Walk-In Fish Orders:</strong></td>
                                    <td class="text-right">₱<?php echo number_format($walkin_fish_revenue, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Direct Menu Orders:</strong></td>
                                    <td class="text-right">₱<?php echo number_format($metrics['total_menu_sales'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Online Cottage:</strong></td>
                                    <td class="text-right">₱<?php echo number_format($online_cottage_revenue, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Walk-In Cottage:</strong></td>
                                    <td class="text-right">₱<?php echo number_format($walkin_cottage_revenue, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Boat Rentals:</strong></td>
                                    <td class="text-right">₱<?php echo number_format($total_boat_revenue, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Entrance Fees:</strong></td>
                                    <td class="text-right">₱<?php echo number_format($entrance_total_revenue, 2); ?></td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>Total Revenue:</strong></td>
                                    <td class="text-right"><strong>₱<?php echo number_format($metrics['combined_sales'], 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Expenses:</strong></td>
                                    <td class="text-right text-danger">₱<?php echo number_format($total_expenses ?? 0, 2); ?></td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>Net Combined Revenue:</strong></td>
                                    <td class="text-right"><strong>₱<?php echo number_format($net_combined ?? 0, 2); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Metrics Cards -->
        <div class="row">
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo $metrics['total_customers']; ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-info">Total</span> Customers</p>
                            </div>
                            <div class="feather icon-users display-4 text-info"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo $metrics['total_reservations']; ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-secondary">Total</span> Reservations</p>
                            </div>
                            <div class="feather icon-calendar display-4 text-secondary"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo $metrics['total_orders']; ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-light">Fish</span> Orders</p>
                            </div>
                            <div class="feather icon-box display-4 text-muted"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo $metrics['total_menu_orders']; ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-light">Menu</span> Orders</p>
                            </div>
                            <div class="feather icon-shopping-bag display-4 text-muted"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Type Distribution & Products -->
        <div class="row">
            <!-- Revenue Distribution Pie Chart (moved from Charts Row) -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header with-elements">
                        <h6 class="card-header-title mb-0">Revenue Distribution</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueDistChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="">
                                <h2 class="mb-2"><?php echo $metrics['total_products']; ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-primary">Available</span> Products</p>
                            </div>
                            <div class="feather icon-tag display-4 text-primary"></div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo $metrics['total_fish']; ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-success">Fish</span> Species</p>
                            </div>
                            <div class="lnr lnr-leaf display-4 text-success"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header with-elements">
                        <h6 class="card-header-title mb-0">Order Count Distribution</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="orderDistChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers Ranking -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-4">
                    <div class="card-header with-elements">
                        <h6 class="card-header-title mb-0">Top 10 Customers by Total Spending</h6>
                        <div class="card-header-elements ml-auto">
                            <span class="badge badge-primary">Top Spenders</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Customer Name</th>
                                    <th>Email</th>
                                    <th style="width: 120px;">Orders</th>
                                    <th style="width: 150px;">Total Spent</th>
                                    <th style="width: 150px;">Avg per Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_customers)): ?>
                                    <?php foreach ($top_customers as $idx => $customer): ?>
                                    <tr>
                                        <td>
                                            <div class="badge badge-success"><?php echo $idx + 1; ?></div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                        </td>
                                        <td class="text-muted"><?php echo htmlspecialchars($customer['email'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $customer['order_count']; ?></span>
                                        </td>
                                        <td>
                                            <strong class="text-success">₱<?php echo number_format($customer['total_spent'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="text-muted">₱<?php echo number_format($customer['total_spent'] / max($customer['order_count'], 1), 2); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No customer data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- [ content ] End -->

    <?php include 'partials/footer.php'; ?>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Sales Trend Chart (Fish vs Menu)
const combinedChart = <?php echo $combined_chart_json; ?>;
const fishChart = <?php echo $fish_chart_json; ?>;
const menuChart = <?php echo $menu_chart_json; ?>;
const ctx = document.getElementById('salesTrendChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: combinedChart.labels,
        datasets: [
            {
                label: 'Fish Sales (₱)',
                data: fishChart.values,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40,167,69,0.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 4
            },
            {
                label: 'Menu Sales (₱)',
                data: menuChart.values,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255,193,7,0.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => '₱' + v.toLocaleString() }
            }
        }
    }
});

// Revenue Distribution Pie Chart
const revenueData = <?php echo $revenue_dist_json; ?>;
const revenueColors = ['#28a745', '#ffc107', '#17a2b8', '#007bff', '#6f42c1', '#e83e8c', '#fd7e14'];
const ctx2 = document.getElementById('revenueDistChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: revenueData.map(d => d.label),
        datasets: [{
            data: revenueData.map(d => d.value),
            backgroundColor: revenueColors.slice(0, revenueData.length),
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => {
                                const value = data.datasets[0].data[i];
                                const percentage = revenueData[i].percentage;
                                return {
                                    text: label + ': ₱' + value.toLocaleString(),
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: data.datasets[0].borderColor,
                                    lineWidth: data.datasets[0].borderWidth,
                                    hidden: isNaN(data.datasets[0].data[i]) || chart.getDatasetMeta(0).data[i].hidden,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const idx = context.dataIndex;
                        return revenueData[idx].label + ': ₱' + revenueData[idx].value.toLocaleString() + ' (' + revenueData[idx].percentage + '%)';
                    }
                }
            }
        }
    }
});

// Order Distribution Chart
const ctx3 = document.getElementById('orderDistChart').getContext('2d');
new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: ['Fish Orders', 'Menu Orders'],
        datasets: [{
            label: 'Order Count',
            data: [<?php echo $regular_orders_count; ?>, <?php echo $menu_orders_count; ?>],
            backgroundColor: ['#007bff', '#6f42c1'],
            borderColor: ['#0056b3', '#4a148c'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Count: ' + context.parsed.x;
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
