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
            <h4 class="font-weight-bold py-3 mb-0">Reports — Sales Report<?php 
                if ($selected_category != 'all') {
                    $category_names = [
                        'online_fish' => 'Online Fish Orders',
                        'walkin_fish' => 'Walk-in Fish Orders', 
                        'online_menu' => 'Online Menu Orders',
                        'walkin_menu' => 'Walk-in / Direct Menu Orders',
                        'online_cottage' => 'Online Cottage Reservations',
                        'walkin_cottage' => 'Walk-in Cottage Reservations',
                        'boat' => 'Boat Rentals',
                        'entrance' => 'Entrance'
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
                            <option value="entrance" <?php echo ($selected_category == 'entrance') ? 'selected' : ''; ?>>ENTRANCE</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                        <a href="?period=30days" class="btn btn-secondary">Reset</a>
                    </div>
                    
                </form>
            </div>
        </div>
        
        <?php
        require __DIR__ . '/config/db.php';
        
        // Check if custom date range is provided
        $use_custom_dates = false;
        $custom_start_date = '';
        $custom_end_date = '';
        
        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
            $use_custom_dates = true;
            $custom_start_date = date('Y-m-d', strtotime($_GET['start_date']));
            $custom_end_date = date('Y-m-d', strtotime($_GET['end_date']));
            $periodLabel = 'Custom Range (' . date('M d, Y', strtotime($custom_start_date)) . ' to ' . date('M d, Y', strtotime($custom_end_date)) . ')';
        } else {
            // Get period and set date filters
            $period = isset($_GET['period']) ? $_GET['period'] : '30days';
            $today_date = date('Y-m-d');
            $month_start = date('Y-m-01');
            $year_start = date('Y-01-01');
            
            switch ($period) {
                case 'today':
                    $periodLabel = 'Today (' . date('F d, Y') . ')';
                    break;
                case '7days':
                    $periodLabel = 'Last 7 Days';
                    break;
                case '30days':
                    $periodLabel = 'Last 30 Days';
                    break;
                case '90days':
                    $periodLabel = 'Last 90 Days';
                    break;
                case 'month':
                    $periodLabel = 'This Month (' . date('F Y') . ')';
                    break;
                case 'year':
                    $periodLabel = 'This Year (' . date('Y') . ')';
                    break;
                default:
                    $periodLabel = 'All Time';
            }
        }
        
        // Get admin name for "Prepared By" section
        $admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'System Administrator');
        $report_timestamp = date('F d, Y \a\t h:i A');
        
        // Function to calculate date filters based on period or custom range
        function getDateFilters($period = '30days', $use_custom_dates = false, $custom_start = '', $custom_end = '') {
            $today_date = date('Y-m-d');
            $month_start = date('Y-m-01');
            $year_start = date('Y-01-01');
            
            if ($use_custom_dates) {
                return [
                    'today' => $custom_start,
                    'month' => $custom_start,
                    'year' => $custom_start,
                    'all_time' => '1900-01-01',
                    'today_end' => $custom_end,
                    'month_end' => $custom_end,
                    'year_end' => $custom_end,
                    'all_time_end' => '2099-12-31'
                ];
            }
            
            switch ($period) {
                case 'today':
                    return [
                        'today' => $today_date,
                        'month' => $month_start,
                        'year' => $year_start,
                        'all_time' => '1900-01-01',
                        'today_end' => $today_date,
                        'month_end' => '2099-12-31',
                        'year_end' => '2099-12-31',
                        'all_time_end' => '2099-12-31'
                    ];
                case '7days':
                    $start = date('Y-m-d', strtotime('-7 days'));
                    return [
                        'today' => $start,
                        'month' => $start,
                        'year' => $start,
                        'all_time' => '1900-01-01',
                        'today_end' => $today_date,
                        'month_end' => $today_date,
                        'year_end' => $today_date,
                        'all_time_end' => '2099-12-31'
                    ];
                case '30days':
                    $start = date('Y-m-d', strtotime('-30 days'));
                    return [
                        'today' => $start,
                        'month' => $start,
                        'year' => $start,
                        'all_time' => '1900-01-01',
                        'today_end' => $today_date,
                        'month_end' => $today_date,
                        'year_end' => $today_date,
                        'all_time_end' => '2099-12-31'
                    ];
                case '90days':
                    $start = date('Y-m-d', strtotime('-90 days'));
                    return [
                        'today' => $start,
                        'month' => $start,
                        'year' => $start,
                        'all_time' => '1900-01-01',
                        'today_end' => $today_date,
                        'month_end' => $today_date,
                        'year_end' => $today_date,
                        'all_time_end' => '2099-12-31'
                    ];
                case 'month':
                    return [
                        'today' => $month_start,
                        'month' => $month_start,
                        'year' => $year_start,
                        'all_time' => '1900-01-01',
                        'today_end' => '2099-12-31',
                        'month_end' => '2099-12-31',
                        'year_end' => '2099-12-31',
                        'all_time_end' => '2099-12-31'
                    ];
                case 'year':
                    return [
                        'today' => $year_start,
                        'month' => $year_start,
                        'year' => $year_start,
                        'all_time' => '1900-01-01',
                        'today_end' => '2099-12-31',
                        'month_end' => '2099-12-31',
                        'year_end' => '2099-12-31',
                        'all_time_end' => '2099-12-31'
                    ];
                default: // all_time
                    return [
                        'today' => '1900-01-01',
                        'month' => '1900-01-01',
                        'year' => '1900-01-01',
                        'all_time' => '1900-01-01',
                        'today_end' => '2099-12-31',
                        'month_end' => '2099-12-31',
                        'year_end' => '2099-12-31',
                        'all_time_end' => '2099-12-31'
                    ];
            }
        }
        
        $dates = getDateFilters(isset($_GET['period']) ? $_GET['period'] : '30days', $use_custom_dates, $custom_start_date, $custom_end_date);
        $today_date = $dates['today'];
        $month_start = $dates['month'];
        $year_start = $dates['year'];
        $today_end = $dates['today_end'];
        $month_end = $dates['month_end'];
        $year_end = $dates['year_end'];
        $all_time_end = $dates['all_time_end'];
        
        // ===== DASHBOARD METRICS - TODAY =====
        $today_sales = 0;
        $today_sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) >= '$today_date' AND DATE(created_at) <= '$today_end' AND status IN ('paid', 'completed')
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM fish_orders WHERE DATE(created_at) >= '$today_date' AND DATE(created_at) <= '$today_end' AND status = 'paid'
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM menu_orders WHERE DATE(created_at) >= '$today_date' AND DATE(created_at) <= '$today_end' AND status IN ('paid', 'completed')
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM boat_rentals WHERE DATE(created_at) >= '$today_date' AND DATE(created_at) <= '$today_end' AND status = 'completed'
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM reservations WHERE DATE(created_at) >= '$today_date' AND DATE(created_at) <= '$today_end' AND reservation_type = 'cottage' AND status = 'completed'";
        $today_stmt = $conn->prepare($today_sql);
        if ($today_stmt) {
            $today_stmt->execute();
            $today_res = $today_stmt->get_result();
            while ($row = $today_res->fetch_assoc()) {
                $today_sales += $row['total'];
            }
            $today_stmt->close();
        }
        
        // ===== DASHBOARD METRICS - THIS MONTH =====
        $month_sales = 0;
        $month_sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) >= '$month_start' AND DATE(created_at) <= '$month_end' AND status IN ('paid', 'completed')
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM fish_orders WHERE DATE(created_at) >= '$month_start' AND DATE(created_at) <= '$month_end' AND status = 'paid'
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM menu_orders WHERE DATE(created_at) >= '$month_start' AND DATE(created_at) <= '$month_end' AND status IN ('paid', 'completed')
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM boat_rentals WHERE DATE(created_at) >= '$month_start' AND DATE(created_at) <= '$month_end' AND status = 'completed'
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM reservations WHERE DATE(created_at) >= '$month_start' AND DATE(created_at) <= '$month_end' AND reservation_type = 'cottage' AND status = 'completed'";
        $month_stmt = $conn->prepare($month_sql);
        if ($month_stmt) {
            $month_stmt->execute();
            $month_res = $month_stmt->get_result();
            while ($row = $month_res->fetch_assoc()) {
                $month_sales += $row['total'];
            }
            $month_stmt->close();
        }
        
        // ===== DASHBOARD METRICS - THIS YEAR =====
        $year_sales = 0;
        $year_sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) >= '$year_start' AND DATE(created_at) <= '$year_end' AND status IN ('paid', 'completed')
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM fish_orders WHERE DATE(created_at) >= '$year_start' AND DATE(created_at) <= '$year_end' AND status = 'paid'
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM menu_orders WHERE DATE(created_at) >= '$year_start' AND DATE(created_at) <= '$year_end' AND status IN ('paid', 'completed')
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM boat_rentals WHERE DATE(created_at) >= '$year_start' AND DATE(created_at) <= '$year_end' AND status = 'completed'
                      UNION ALL SELECT COALESCE(SUM(total_amount), 0) FROM reservations WHERE DATE(created_at) >= '$year_start' AND DATE(created_at) <= '$year_end' AND reservation_type = 'cottage' AND status = 'completed'";
        $year_stmt = $conn->prepare($year_sql);
        if ($year_stmt) {
            $year_stmt->execute();
            $year_res = $year_stmt->get_result();
            while ($row = $year_res->fetch_assoc()) {
                $year_sales += $row['total'];
            }
            $year_stmt->close();
        }
        
        // Function to get service revenue breakdown
        function getServiceMetrics($conn, $serviceName, $serviceType, $date_start, $date_end) {
            $metrics = ['today' => 0, 'month' => 0, 'year' => 0, 'all_time' => 0, 'today_count' => 0, 'month_count' => 0, 'year_count' => 0, 'all_count' => 0];
            
            $queries = [];
            
            if ($serviceType === 'online_fish' || $serviceType === 'fish') {
                $queries = [
                    'today' => "SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt FROM orders o WHERE o.is_manual = 0 AND o.status IN ('paid', 'completed') AND DATE(o.created_at) >= '$date_start' AND DATE(o.created_at) <= '$date_end' AND EXISTS (SELECT 1 FROM order_items oi JOIN fish_species fs ON oi.product_id = fs.fish_id WHERE oi.order_id = o.id)",
                    'month' => "SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt FROM orders o WHERE o.is_manual = 0 AND o.status IN ('paid', 'completed') AND DATE(o.created_at) >= '$date_start' AND DATE(o.created_at) <= '$date_end' AND EXISTS (SELECT 1 FROM order_items oi JOIN fish_species fs ON oi.product_id = fs.fish_id WHERE oi.order_id = o.id)",
                    'year' => "SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt FROM orders o WHERE o.is_manual = 0 AND o.status IN ('paid', 'completed') AND DATE(o.created_at) >= '$date_start' AND DATE(o.created_at) <= '$date_end' AND EXISTS (SELECT 1 FROM order_items oi JOIN fish_species fs ON oi.product_id = fs.fish_id WHERE oi.order_id = o.id)",
                    'all_time' => "SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt FROM orders o WHERE o.is_manual = 0 AND o.status IN ('paid', 'completed') AND EXISTS (SELECT 1 FROM order_items oi JOIN fish_species fs ON oi.product_id = fs.fish_id WHERE oi.order_id = o.id)"
                ];
            } elseif ($serviceType === 'walkin_fish') {
                $queries = [
                    'today' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM fish_orders WHERE status = 'paid' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'month' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM fish_orders WHERE status = 'paid' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'year' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM fish_orders WHERE status = 'paid' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'all_time' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM fish_orders WHERE status = 'paid'"
                ];
            } elseif ($serviceType === 'online_menu') {
                $queries = [
                    'today' => "SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt FROM orders o WHERE o.is_manual = 0 AND o.status IN ('paid', 'completed') AND DATE(o.created_at) >= '$date_start' AND DATE(o.created_at) <= '$date_end' AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id)",
                    'month' => "SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt FROM orders o WHERE o.is_manual = 0 AND o.status IN ('paid', 'completed') AND DATE(o.created_at) >= '$date_start' AND DATE(o.created_at) <= '$date_end' AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id)",
                    'year' => "SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt FROM orders o WHERE o.is_manual = 0 AND o.status IN ('paid', 'completed') AND DATE(o.created_at) >= '$date_start' AND DATE(o.created_at) <= '$date_end' AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id)",
                    'all_time' => "SELECT COALESCE(SUM(o.total_amount), 0) as total, COUNT(DISTINCT o.id) as cnt FROM orders o WHERE o.is_manual = 0 AND o.status IN ('paid', 'completed') AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id)"
                ];
            } elseif ($serviceType === 'walkin_menu') {
                $queries = [
                    'today' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM menu_orders WHERE status IN ('paid', 'completed') AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'month' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM menu_orders WHERE status IN ('paid', 'completed') AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'year' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM menu_orders WHERE status IN ('paid', 'completed') AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'all_time' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM menu_orders WHERE status IN ('paid', 'completed')"
                ];
            } elseif ($serviceType === 'online_cottage') {
                $queries = [
                    'today' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 0 AND status = 'completed' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'month' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 0 AND status = 'completed' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'year' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 0 AND status = 'completed' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'all_time' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 0 AND status = 'completed'"
                ];
            } elseif ($serviceType === 'walkin_cottage') {
                $queries = [
                    'today' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 1 AND status = 'completed' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'month' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 1 AND status = 'completed' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'year' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 1 AND status = 'completed' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'all_time' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM reservations WHERE reservation_type = 'cottage' AND is_manual = 1 AND status = 'completed'"
                ];
            } elseif ($serviceType === 'boat') {
                $queries = [
                    'today' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM boat_rentals WHERE status = 'completed' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'month' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM boat_rentals WHERE status = 'completed' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'year' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM boat_rentals WHERE status = 'completed' AND DATE(created_at) >= '$date_start' AND DATE(created_at) <= '$date_end'",
                    'all_time' => "SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as cnt FROM boat_rentals WHERE status = 'completed'"
                ];
            }
            
            foreach (['today', 'month', 'year', 'all_time'] as $key) {
                if (isset($queries[$key])) {
                    $stmt = $conn->prepare($queries[$key]);
                    if ($stmt) {
                        $stmt->execute();
                        $res = $stmt->get_result();
                        $row = $res->fetch_assoc();
                        $metrics[$key] = (float)($row['total'] ?? 0);
                        $metrics[$key . '_count'] = (int)($row['cnt'] ?? 0);
                        $stmt->close();
                    }
                }
            }
            
            return $metrics;
        }
        
        // Get metrics for all services
        $online_fish = getServiceMetrics($conn, 'Online Fish', 'online_fish', $today_date, $today_end);
        $walkin_fish = getServiceMetrics($conn, 'Walk-In Fish', 'walkin_fish', $today_date, $today_end);
        $online_menu = getServiceMetrics($conn, 'Online Menu', 'online_menu', $today_date, $today_end);
        $walkin_menu = getServiceMetrics($conn, 'Walk-In Menu', 'walkin_menu', $today_date, $today_end);
        $online_cottage = getServiceMetrics($conn, 'Online Cottage', 'online_cottage', $today_date, $today_end);
        $walkin_cottage = getServiceMetrics($conn, 'Walk-In Cottage', 'walkin_cottage', $today_date, $today_end);
        $boat = getServiceMetrics($conn, 'Boat Rentals', 'boat', $today_date, $today_end);
        
        // ===== ENTRANCE FEE METRICS =====
        $ENTRANCE_FEE = 50; // fixed fee per guest
        $entrance = ['today' => 0, 'month' => 0, 'year' => 0, 'all_time' => 0, 'today_count' => 0, 'month_count' => 0, 'year_count' => 0, 'all_count' => 0];
        
        // Calculate entrance fee metrics
        $entr_stmt = $conn->prepare("SELECT new_values, timestamp FROM activity_logs WHERE entity_type = 'entrance_fee' AND activity_type = 'CREATE'");
        if ($entr_stmt) {
            $entr_stmt->execute();
            $entr_res = $entr_stmt->get_result();
            while ($row = $entr_res->fetch_assoc()) {
                $data = json_decode($row['new_values'], true);
                $num = (int)($data['num_guests'] ?? 0);
                $revenue = $num * $ENTRANCE_FEE;
                $date = date('Y-m-d', strtotime($row['timestamp']));
                
                $entrance['all_time'] += $revenue;
                $entrance['all_count'] += $num;
                
                if ($date >= $month_start && $date <= $month_end) {
                    $entrance['month'] += $revenue;
                    $entrance['month_count'] += $num;
                }
                if ($date >= $year_start && $date <= $year_end) {
                    $entrance['year'] += $revenue;
                    $entrance['year_count'] += $num;
                }
                if ($date >= $today_date && $date <= $today_end) {
                    $entrance['today'] += $revenue;
                    $entrance['today_count'] += $num;
                }
            }
            $entr_stmt->close();
        }
        ?>
        
        <style>
            @media print {
                body { margin: 0; padding: 0.5in; font-size: 11px; }
                .no-print { display: none !important; }
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
            }
            
            @media screen {
                .print-header, .print-footer, .service-breakdown { display: none !important; }
            }
            
            .service-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 1.5rem;
                border-left: 4px solid #007bff;
            }
            
            .service-metrics {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0.5rem;
                margin-top: 0.5rem;
            }
            
            .metric {
                background: white;
                padding: 0.5rem;
                border-radius: 4px;
                text-align: center;
                border: 1px solid #e0e0e0;
            }
            
            .metric-label {
                font-size: 11px;
                color: #666;
                font-weight: 600;
            }
            
            .metric-value {
                font-size: 16px;
                font-weight: bold;
                color: #28a745;
                margin-top: 0.3rem;
            }
            
            .metric-count {
                font-size: 10px;
                color: #999;
                margin-top: 0.2rem;
            }
        </style>
        
        <!-- PRINT HEADER -->
        <div class="print-header" style="display: none;">
            <div style="text-align: center; margin-bottom: 15px;">
                <img src="assets/img/maataLogo.png" alt="Maata Logo" style="max-width: 120px; max-height: 80px; object-fit: contain;">
            </div>
            <h2 style="margin: 0; font-size: 20px; font-weight: bold; text-align: center;">MAATA FISH FARM SYSTEM</h2>
            <h3 style="margin: 5px 0; text-align: center; font-size: 14px;"><?php 
                if ($selected_category == 'all') {
                    echo 'COMPREHENSIVE SALES REPORT';
                } else {
                    $category_names = [
                        'online_fish' => 'ONLINE FISH ORDERS SALES REPORT',
                        'walkin_fish' => 'WALK-IN FISH ORDERS SALES REPORT', 
                        'online_menu' => 'ONLINE MENU ORDERS SALES REPORT',
                        'walkin_menu' => 'WALK-IN / DIRECT MENU ORDERS SALES REPORT',
                        'online_cottage' => 'ONLINE COTTAGE RESERVATIONS SALES REPORT',
                        'walkin_cottage' => 'WALK-IN COTTAGE RESERVATIONS SALES REPORT',
                        'boat' => 'BOAT RENTALS SALES REPORT',
                        'entrance' => 'ENTRANCE FEES SALES REPORT'
                    ];
                    echo $category_names[$selected_category];
                }
            ?></h3>
            <div style="text-align: center; margin: 10px 0; font-size: 11px;">
                <strong>Report Period:</strong> <?php echo $periodLabel; ?><br>
                <strong>Generated:</strong> <?php echo $report_timestamp; ?>
            </div>
            <hr style="margin: 10px 0;"/>
        </div>
        
        <!-- ON-SCREEN HEADER - PRINT READY -->
        <div class="no-print">
            <div class="alert alert-info" style="background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; margin-bottom: 20px;">
                <strong style="color: #004085;">Report Period:</strong> <span style="color: #004085;"><?php echo $periodLabel; ?> (Generated: <?php echo date('M d, Y'); ?>)</span>
                <?php if ($selected_category != 'all'): ?>
                    <br><strong style="color: #004085;">Category:</strong> <span style="color: #004085;"><?php 
                        $category_names = [
                            'online_fish' => 'Online Fish Orders',
                            'walkin_fish' => 'Walk-in Fish Orders', 
                            'online_menu' => 'Online Menu Orders',
                            'walkin_menu' => 'Walk-in / Direct Menu Orders',
                            'online_cottage' => 'Online Cottage Reservations',
                            'walkin_cottage' => 'Walk-in Cottage Reservations',
                            'boat' => 'Boat Rentals',
                            'entrance' => 'Entrance'
                        ];
                        echo $category_names[$selected_category];
                    ?></span>
                <?php endif; ?>
            </div>
            <p style="text-align: center; color: #666; font-size: 12px; margin-bottom: 20px;">Print this page to generate an official sales report</p>
        </div>
        
        <!-- SUMMARY DASHBOARD -->
        <?php if ($selected_category == 'all'): ?>
        <div class="service-card">
            <h3>OVERALL SALES SUMMARY</h3>
            <div class="service-metrics">
                <div class="metric">
                    <div class="metric-label">Today's Sales</div>
                    <div class="metric-value">₱<?php 
                        if ($selected_category == 'all') {
                            echo number_format($today_sales, 2);
                        } elseif ($selected_category == 'online_fish') {
                            echo number_format($online_fish['today'], 2);
                        } elseif ($selected_category == 'walkin_fish') {
                            echo number_format($walkin_fish['today'], 2);
                        } elseif ($selected_category == 'online_menu') {
                            echo number_format($online_menu['today'], 2);
                        } elseif ($selected_category == 'walkin_menu') {
                            echo number_format($walkin_menu['today'], 2);
                        } elseif ($selected_category == 'online_cottage') {
                            echo number_format($online_cottage['today'], 2);
                        } elseif ($selected_category == 'walkin_cottage') {
                            echo number_format($walkin_cottage['today'], 2);
                        } elseif ($selected_category == 'boat') {
                            echo number_format($boat['today'], 2);
                        } elseif ($selected_category == 'entrance') {
                            echo number_format($entrance['today'], 2);
                        }
                    ?></div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Month Sales</div>
                    <div class="metric-value">₱<?php 
                        if ($selected_category == 'all') {
                            echo number_format($month_sales, 2);
                        } elseif ($selected_category == 'online_fish') {
                            echo number_format($online_fish['month'], 2);
                        } elseif ($selected_category == 'walkin_fish') {
                            echo number_format($walkin_fish['month'], 2);
                        } elseif ($selected_category == 'online_menu') {
                            echo number_format($online_menu['month'], 2);
                        } elseif ($selected_category == 'walkin_menu') {
                            echo number_format($walkin_menu['month'], 2);
                        } elseif ($selected_category == 'online_cottage') {
                            echo number_format($online_cottage['month'], 2);
                        } elseif ($selected_category == 'walkin_cottage') {
                            echo number_format($walkin_cottage['month'], 2);
                        } elseif ($selected_category == 'boat') {
                            echo number_format($boat['month'], 2);
                        } elseif ($selected_category == 'entrance') {
                            echo number_format($entrance['month'], 2);
                        }
                    ?></div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Year Sales</div>
                    <div class="metric-value">₱<?php 
                        if ($selected_category == 'all') {
                            echo number_format($year_sales, 2);
                        } elseif ($selected_category == 'online_fish') {
                            echo number_format($online_fish['year'], 2);
                        } elseif ($selected_category == 'walkin_fish') {
                            echo number_format($walkin_fish['year'], 2);
                        } elseif ($selected_category == 'online_menu') {
                            echo number_format($online_menu['year'], 2);
                        } elseif ($selected_category == 'walkin_menu') {
                            echo number_format($walkin_menu['year'], 2);
                        } elseif ($selected_category == 'online_cottage') {
                            echo number_format($online_cottage['year'], 2);
                        } elseif ($selected_category == 'walkin_cottage') {
                            echo number_format($walkin_cottage['year'], 2);
                        } elseif ($selected_category == 'boat') {
                            echo number_format($boat['year'], 2);
                        } elseif ($selected_category == 'entrance') {
                            echo number_format($entrance['year'], 2);
                        }
                    ?></div>
                </div>
                <div class="metric">
                    <div class="metric-label">All Time Sales</div>
                    <div class="metric-value">₱<?php 
                        if ($selected_category == 'all') {
                            echo number_format(
                                $online_fish['all_time'] + $walkin_fish['all_time'] + 
                                $online_menu['all_time'] + $walkin_menu['all_time'] + 
                                $online_cottage['all_time'] + $walkin_cottage['all_time'] + 
                                $boat['all_time'] + $entrance['all_time'], 2);
                        } elseif ($selected_category == 'online_fish') {
                            echo number_format($online_fish['all_time'], 2);
                        } elseif ($selected_category == 'walkin_fish') {
                            echo number_format($walkin_fish['all_time'], 2);
                        } elseif ($selected_category == 'online_menu') {
                            echo number_format($online_menu['all_time'], 2);
                        } elseif ($selected_category == 'walkin_menu') {
                            echo number_format($walkin_menu['all_time'], 2);
                        } elseif ($selected_category == 'online_cottage') {
                            echo number_format($online_cottage['all_time'], 2);
                        } elseif ($selected_category == 'walkin_cottage') {
                            echo number_format($walkin_cottage['all_time'], 2);
                        } elseif ($selected_category == 'boat') {
                            echo number_format($boat['all_time'], 2);
                        } elseif ($selected_category == 'entrance') {
                            echo number_format($entrance['all_time'], 2);
                        }
                    ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- DETAILED SERVICE REPORTS -->
        
        <!-- 1. ONLINE FISH ORDERS -->
        <?php if ($selected_category == 'all' || $selected_category == 'online_fish'): ?>
        <div class="service-card">
            <h4>ONLINE FISH ORDERS</h4>
            <p style="color: #666; font-size: 12px; margin: 5px 0;">Online customer fish purchases</p>
            <div class="service-metrics">
                <div class="metric">
                    <div class="metric-label">Today</div>
                    <div class="metric-value">₱<?php echo number_format($online_fish['today'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_fish['today_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Month</div>
                    <div class="metric-value">₱<?php echo number_format($online_fish['month'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_fish['month_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Year</div>
                    <div class="metric-value">₱<?php echo number_format($online_fish['year'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_fish['year_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">All Time</div>
                    <div class="metric-value">₱<?php echo number_format($online_fish['all_time'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_fish['all_count']; ?> orders</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 2. WALK-IN FISH ORDERS -->
        <?php if ($selected_category == 'all' || $selected_category == 'walkin_fish'): ?>
        <div class="service-card">
            <h4>WALK-IN FISH ORDERS</h4>
            <p style="color: #666; font-size: 12px; margin: 5px 0;">Walk-in customer fish purchases</p>
            <div class="service-metrics">
                <div class="metric">
                    <div class="metric-label">Today</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_fish['today'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_fish['today_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Month</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_fish['month'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_fish['month_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Year</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_fish['year'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_fish['year_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">All Time</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_fish['all_time'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_fish['all_count']; ?> orders</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 3. ONLINE MENU ORDERS -->
        <?php if ($selected_category == 'all' || $selected_category == 'online_menu'): ?>
        <div class="service-card">
            <h4>ONLINE MENU ORDERS</h4>
            <p style="color: #666; font-size: 12px; margin: 5px 0;">Online restaurant menu orders</p>
            <div class="service-metrics">
                <div class="metric">
                    <div class="metric-label">Today</div>
                    <div class="metric-value">₱<?php echo number_format($online_menu['today'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_menu['today_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Month</div>
                    <div class="metric-value">₱<?php echo number_format($online_menu['month'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_menu['month_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Year</div>
                    <div class="metric-value">₱<?php echo number_format($online_menu['year'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_menu['year_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">All Time</div>
                    <div class="metric-value">₱<?php echo number_format($online_menu['all_time'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_menu['all_count']; ?> orders</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 4. WALK-IN MENU ORDERS -->
        <?php if ($selected_category == 'all' || $selected_category == 'walkin_menu'): ?>
        <div class="service-card">
            <h4>WALK-IN / DIRECT MENU ORDERS</h4>
            <p style="color: #666; font-size: 12px; margin: 5px 0;">Direct menu orders created by staff</p>
            <div class="service-metrics">
                <div class="metric">
                    <div class="metric-label">Today</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_menu['today'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_menu['today_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Month</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_menu['month'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_menu['month_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Year</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_menu['year'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_menu['year_count']; ?> orders</div>
                </div>
                <div class="metric">
                    <div class="metric-label">All Time</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_menu['all_time'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_menu['all_count']; ?> orders</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 5. ONLINE COTTAGE RESERVATIONS -->
        <?php if ($selected_category == 'all' || $selected_category == 'online_cottage'): ?>
        <div class="service-card">
            <h4>ONLINE COTTAGE RESERVATIONS</h4>
            <p style="color: #666; font-size: 12px; margin: 5px 0;">Online cottage bookings</p>
            <div class="service-metrics">
                <div class="metric">
                    <div class="metric-label">Today</div>
                    <div class="metric-value">₱<?php echo number_format($online_cottage['today'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_cottage['today_count']; ?> bookings</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Month</div>
                    <div class="metric-value">₱<?php echo number_format($online_cottage['month'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_cottage['month_count']; ?> bookings</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Year</div>
                    <div class="metric-value">₱<?php echo number_format($online_cottage['year'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_cottage['year_count']; ?> bookings</div>
                </div>
                <div class="metric">
                    <div class="metric-label">All Time</div>
                    <div class="metric-value">₱<?php echo number_format($online_cottage['all_time'], 2); ?></div>
                    <div class="metric-count"><?php echo $online_cottage['all_count']; ?> bookings</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 6. WALK-IN COTTAGE RESERVATIONS -->
        <?php if ($selected_category == 'all' || $selected_category == 'walkin_cottage'): ?>
        <div class="service-card">
            <h4>WALK-IN COTTAGE RESERVATIONS</h4>
            <p style="color: #666; font-size: 12px; margin: 5px 0;">Walk-in cottage bookings</p>
            <div class="service-metrics">
                <div class="metric">
                    <div class="metric-label">Today</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_cottage['today'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_cottage['today_count']; ?> bookings</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Month</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_cottage['month'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_cottage['month_count']; ?> bookings</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Year</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_cottage['year'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_cottage['year_count']; ?> bookings</div>
                </div>
                <div class="metric">
                    <div class="metric-label">All Time</div>
                    <div class="metric-value">₱<?php echo number_format($walkin_cottage['all_time'], 2); ?></div>
                    <div class="metric-count"><?php echo $walkin_cottage['all_count']; ?> bookings</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 7. BOAT RENTALS -->
        <?php if ($selected_category == 'all' || $selected_category == 'boat'): ?>
        <div class="service-card">
            <h4>BOAT RENTALS</h4>
            <p style="color: #666; font-size: 12px; margin: 5px 0;">Boat rental transactions</p>
            <div class="service-metrics">
                <div class="metric">
                    <div class="metric-label">Today</div>
                    <div class="metric-value">₱<?php echo number_format($boat['today'], 2); ?></div>
                    <div class="metric-count"><?php echo $boat['today_count']; ?> rentals</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Month</div>
                    <div class="metric-value">₱<?php echo number_format($boat['month'], 2); ?></div>
                    <div class="metric-count"><?php echo $boat['month_count']; ?> rentals</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Year</div>
                    <div class="metric-value">₱<?php echo number_format($boat['year'], 2); ?></div>
                    <div class="metric-count"><?php echo $boat['year_count']; ?> rentals</div>
                </div>
                <div class="metric">
                    <div class="metric-label">All Time</div>
                    <div class="metric-value">₱<?php echo number_format($boat['all_time'], 2); ?></div>
                    <div class="metric-count"><?php echo $boat['all_count']; ?> rentals</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 8. ENTRANCE -->
        <?php if ($selected_category == 'all' || $selected_category == 'entrance'): ?>
        <div class="service-card">
            <h4>ENTRANCE</h4>
            <p style="color: #666; font-size: 12px; margin: 5px 0;">Entrance fee collections</p>
            <div class="service-metrics">
                <div class="metric">
                    <div class="metric-label">Today</div>
                    <div class="metric-value">₱<?php echo number_format($entrance['today'], 2); ?></div>
                    <div class="metric-count"><?php echo $entrance['today_count']; ?> guests</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Month</div>
                    <div class="metric-value">₱<?php echo number_format($entrance['month'], 2); ?></div>
                    <div class="metric-count"><?php echo $entrance['month_count']; ?> guests</div>
                </div>
                <div class="metric">
                    <div class="metric-label">This Year</div>
                    <div class="metric-value">₱<?php echo number_format($entrance['year'], 2); ?></div>
                    <div class="metric-count"><?php echo $entrance['year_count']; ?> guests</div>
                </div>
                <div class="metric">
                    <div class="metric-label">All Time</div>
                    <div class="metric-value">₱<?php echo number_format($entrance['all_time'], 2); ?></div>
                    <div class="metric-count"><?php echo $entrance['all_count']; ?> guests</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="print-footer">
            <div style="margin-bottom: 1.5rem;">
                <strong>Report Summary:</strong><br>
                This comprehensive sales report provides detailed breakdowns of revenue by service type across multiple time periods (Today, This Month, This Year, and All Time). Only paid and completed transactions are included in these figures.
            </div>
            <table style="margin-top: 2rem; width: 100%;">
                <tr>
                    <td style="border: none; padding: 0; width: 45%;">
                        <div>
                            <p style="margin: 0; font-weight: bold;">Prepared By:</p>
                            <p style="margin: 5px 0; font-size: 10px;"><?php echo "____________________________"; ?></p>
                            <p style="margin: 5px 0; font-size: 10px;"><?php echo "Signature/Date" ?></p>
                        </div>
                    </td>
                    <td style="border: none; padding: 0; width: 10%;"></td>
                    <td style="border: none; padding: 0; width: 45%;">
                        <div>
                            <p style="margin: 0; font-weight: bold;">Approved By:</p>
                            <p style="margin: 5px 0; font-size: 10px;"><?php echo "____________________________"; ?></p>
                            <p style="margin: 5px 0; font-size: 10px;"><?php echo "Signature/Date" ?></p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<?php include 'partials/footer.php'; ?>


