<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold py-3 mb-0">Reports — Sales Report</h4>
            <div class="d-flex gap-2">
                <form method="GET" class="form-inline" id="filterForm">
                    <select class="form-control mr-2" name="period" id="periodFilter">
                        <option value="7days" <?php echo ($_GET['period'] ?? '30days') == '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="30days" <?php echo ($_GET['period'] ?? '30days') == '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="90days" <?php echo ($_GET['period'] ?? '30days') == '90days' ? 'selected' : ''; ?>>Last 90 Days</option>
                        <option value="month" <?php echo ($_GET['period'] ?? '30days') == 'month' ? 'selected' : ''; ?>>By Month</option>
                        <option value="year" <?php echo ($_GET['period'] ?? '30days') == 'year' ? 'selected' : ''; ?>>By Year</option>
                        <option value="all" <?php echo ($_GET['period'] ?? '30days') == 'all' ? 'selected' : ''; ?>>All Time</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
                <button type="button" class="btn btn-success" onclick="window.print();" title="Print Report">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="btn btn-info" onclick="exportToCSV();" title="Export to CSV">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        
        <?php
        require __DIR__ . '/config/db.php';
        
        // Get period
        $period = isset($_GET['period']) ? $_GET['period'] : '30days';
        $dateFilter = '';
        $periodLabel = '';
        
        switch ($period) {
            case '7days':
                $dateFilter = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $periodLabel = 'Last 7 Days';
                break;
            case '30days':
                $dateFilter = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $periodLabel = 'Last 30 Days';
                break;
            case '90days':
                $dateFilter = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                $periodLabel = 'Last 90 Days';
                break;
            case 'month':
                $dateFilter = "AND YEAR(o.order_date) = YEAR(NOW()) AND MONTH(o.order_date) = MONTH(NOW())";
                $periodLabel = 'Current Month (' . date('F Y') . ')';
                break;
            case 'year':
                $dateFilter = "AND YEAR(o.order_date) = YEAR(NOW())";
                $periodLabel = 'Current Year (' . date('Y') . ')';
                break;
            default:
                $dateFilter = "";
                $periodLabel = 'All Time';
        }
        
        // Sales Overview Metrics
        $metrics = [];
        
        // Total Sales
        if ($period == '7days') {
            $total_sql = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_revenue,
                COUNT(DISTINCT o.customer_id) as unique_customers,
                AVG(o.total_amount) as avg_order_value
                FROM orders o 
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($period == '30days') {
            $total_sql = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_revenue,
                COUNT(DISTINCT o.customer_id) as unique_customers,
                AVG(o.total_amount) as avg_order_value
                FROM orders o 
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        } elseif ($period == '90days') {
            $total_sql = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_revenue,
                COUNT(DISTINCT o.customer_id) as unique_customers,
                AVG(o.total_amount) as avg_order_value
                FROM orders o 
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        } elseif ($period == 'month') {
            $total_sql = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_revenue,
                COUNT(DISTINCT o.customer_id) as unique_customers,
                AVG(o.total_amount) as avg_order_value
                FROM orders o 
                WHERE o.status IN ('confirmed', 'paid') AND YEAR(o.order_date) = YEAR(NOW()) AND MONTH(o.order_date) = MONTH(NOW())";
        } elseif ($period == 'year') {
            $total_sql = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_revenue,
                COUNT(DISTINCT o.customer_id) as unique_customers,
                AVG(o.total_amount) as avg_order_value
                FROM orders o 
                WHERE o.status IN ('confirmed', 'paid') AND YEAR(o.order_date) = YEAR(NOW())";
        } else {
            $total_sql = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_revenue,
                COUNT(DISTINCT o.customer_id) as unique_customers,
                AVG(o.total_amount) as avg_order_value
                FROM orders o 
                WHERE o.status IN ('confirmed', 'paid')";
        }
        
        $total_stmt = $conn->prepare($total_sql);
        if ($total_stmt) {
            $total_stmt->execute();
            $total_res = $total_stmt->get_result();
            $metrics = $total_res->fetch_assoc();
            $total_stmt->close();
        }
        
        // Order Status Distribution
        $status_dist = [];
        if ($period == '7days') {
            $status_sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as amount 
                FROM orders 
                WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                GROUP BY status";
        } elseif ($period == '30days') {
            $status_sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as amount 
                FROM orders 
                WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                GROUP BY status";
        } elseif ($period == '90days') {
            $status_sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as amount 
                FROM orders 
                WHERE order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY) 
                GROUP BY status";
        } elseif ($period == 'month') {
            $status_sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as amount 
                FROM orders 
                WHERE YEAR(order_date) = YEAR(NOW()) AND MONTH(order_date) = MONTH(NOW())
                GROUP BY status";
        } elseif ($period == 'year') {
            $status_sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as amount 
                FROM orders 
                WHERE YEAR(order_date) = YEAR(NOW())
                GROUP BY status";
        } else {
            $status_sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as amount 
                FROM orders 
                GROUP BY status";
        }
        
        $status_stmt = $conn->prepare($status_sql);
        if ($status_stmt) {
            $status_stmt->execute();
            $status_res = $status_stmt->get_result();
            while ($row = $status_res->fetch_assoc()) {
                $status_dist[] = $row;
            }
            $status_stmt->close();
        }
        
        // Top Selling Products
        $top_products = [];
        if ($period == '7days') {
            $top_sql = "SELECT 
                p.id, p.name, p.category, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY p.id
                ORDER BY total_sales DESC
                LIMIT 10";
        } elseif ($period == '30days') {
            $top_sql = "SELECT 
                p.id, p.name, p.category, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY p.id
                ORDER BY total_sales DESC
                LIMIT 10";
        } elseif ($period == '90days') {
            $top_sql = "SELECT 
                p.id, p.name, p.category, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                GROUP BY p.id
                ORDER BY total_sales DESC
                LIMIT 10";
        } elseif ($period == 'month') {
            $top_sql = "SELECT 
                p.id, p.name, p.category, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND YEAR(o.order_date) = YEAR(NOW()) AND MONTH(o.order_date) = MONTH(NOW())
                GROUP BY p.id
                ORDER BY total_sales DESC
                LIMIT 10";
        } elseif ($period == 'year') {
            $top_sql = "SELECT 
                p.id, p.name, p.category, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND YEAR(o.order_date) = YEAR(NOW())
                GROUP BY p.id
                ORDER BY total_sales DESC
                LIMIT 10";
        } else {
            $top_sql = "SELECT 
                p.id, p.name, p.category, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid')
                GROUP BY p.id
                ORDER BY total_sales DESC
                LIMIT 10";
        }
        
        $top_stmt = $conn->prepare($top_sql);
        if ($top_stmt) {
            $top_stmt->execute();
            $top_res = $top_stmt->get_result();
            while ($row = $top_res->fetch_assoc()) {
                $top_products[] = $row;
            }
            $top_stmt->close();
        }
        
        // Category Performance
        $categories = [];
        if ($period == '7days') {
            $cat_sql = "SELECT 
                p.category,
                COUNT(DISTINCT oi.order_id) as num_orders,
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY p.category
                ORDER BY total_sales DESC";
        } elseif ($period == '30days') {
            $cat_sql = "SELECT 
                p.category,
                COUNT(DISTINCT oi.order_id) as num_orders,
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY p.category
                ORDER BY total_sales DESC";
        } elseif ($period == '90days') {
            $cat_sql = "SELECT 
                p.category,
                COUNT(DISTINCT oi.order_id) as num_orders,
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                GROUP BY p.category
                ORDER BY total_sales DESC";
        } elseif ($period == 'month') {
            $cat_sql = "SELECT 
                p.category,
                COUNT(DISTINCT oi.order_id) as num_orders,
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND YEAR(o.order_date) = YEAR(NOW()) AND MONTH(o.order_date) = MONTH(NOW())
                GROUP BY p.category
                ORDER BY total_sales DESC";
        } elseif ($period == 'year') {
            $cat_sql = "SELECT 
                p.category,
                COUNT(DISTINCT oi.order_id) as num_orders,
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND YEAR(o.order_date) = YEAR(NOW())
                GROUP BY p.category
                ORDER BY total_sales DESC";
        } else {
            $cat_sql = "SELECT 
                p.category,
                COUNT(DISTINCT oi.order_id) as num_orders,
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid')
                GROUP BY p.category
                ORDER BY total_sales DESC";
        }
        
        $cat_stmt = $conn->prepare($cat_sql);
        if ($cat_stmt) {
            $cat_stmt->execute();
            $cat_res = $cat_stmt->get_result();
            while ($row = $cat_res->fetch_assoc()) {
                $categories[] = $row;
            }
            $cat_stmt->close();
        }
        
        // Daily Sales Trend
        $daily_sales = [];
        if ($period == '7days') {
            $daily_sql = "SELECT 
                DATE(o.order_date) as sale_date,
                COUNT(*) as num_orders,
                SUM(o.total_amount) as daily_revenue
                FROM orders o
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(o.order_date)
                ORDER BY sale_date ASC";
        } elseif ($period == '30days') {
            $daily_sql = "SELECT 
                DATE(o.order_date) as sale_date,
                COUNT(*) as num_orders,
                SUM(o.total_amount) as daily_revenue
                FROM orders o
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(o.order_date)
                ORDER BY sale_date ASC";
        } elseif ($period == '90days') {
            $daily_sql = "SELECT 
                DATE(o.order_date) as sale_date,
                COUNT(*) as num_orders,
                SUM(o.total_amount) as daily_revenue
                FROM orders o
                WHERE o.status IN ('confirmed', 'paid') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                GROUP BY DATE(o.order_date)
                ORDER BY sale_date ASC";
        } elseif ($period == 'month') {
            $daily_sql = "SELECT 
                DATE(o.order_date) as sale_date,
                COUNT(*) as num_orders,
                SUM(o.total_amount) as daily_revenue
                FROM orders o
                WHERE o.status IN ('confirmed', 'paid') AND YEAR(o.order_date) = YEAR(NOW()) AND MONTH(o.order_date) = MONTH(NOW())
                GROUP BY DATE(o.order_date)
                ORDER BY sale_date ASC";
        } elseif ($period == 'year') {
            $daily_sql = "SELECT 
                DATE(o.order_date) as sale_date,
                COUNT(*) as num_orders,
                SUM(o.total_amount) as daily_revenue
                FROM orders o
                WHERE o.status IN ('confirmed', 'paid') AND YEAR(o.order_date) = YEAR(NOW())
                GROUP BY DATE(o.order_date)
                ORDER BY sale_date ASC";
        } else {
            $daily_sql = "SELECT 
                DATE(o.order_date) as sale_date,
                COUNT(*) as num_orders,
                SUM(o.total_amount) as daily_revenue
                FROM orders o
                WHERE o.status IN ('confirmed', 'paid')
                GROUP BY DATE(o.order_date)
                ORDER BY sale_date ASC";
        }
        
        $daily_stmt = $conn->prepare($daily_sql);
        if ($daily_stmt) {
            $daily_stmt->execute();
            $daily_res = $daily_stmt->get_result();
            while ($row = $daily_res->fetch_assoc()) {
                $daily_sales[] = $row;
            }
            $daily_stmt->close();
        }
        
        // Fish Sales Details (only fish category products)
        $fish_sales = [];
        if ($period == '7days') {
            $fish_sql = "SELECT 
                p.id, p.name, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND p.category = 'fish' AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY p.id
                ORDER BY total_sales DESC";
        } elseif ($period == '30days') {
            $fish_sql = "SELECT 
                p.id, p.name, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND p.category = 'fish' AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY p.id
                ORDER BY total_sales DESC";
        } elseif ($period == '90days') {
            $fish_sql = "SELECT 
                p.id, p.name, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND p.category = 'fish' AND o.order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                GROUP BY p.id
                ORDER BY total_sales DESC";
        } elseif ($period == 'month') {
            $fish_sql = "SELECT 
                p.id, p.name, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND p.category = 'fish' AND YEAR(o.order_date) = YEAR(NOW()) AND MONTH(o.order_date) = MONTH(NOW())
                GROUP BY p.id
                ORDER BY total_sales DESC";
        } elseif ($period == 'year') {
            $fish_sql = "SELECT 
                p.id, p.name, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND p.category = 'fish' AND YEAR(o.order_date) = YEAR(NOW())
                GROUP BY p.id
                ORDER BY total_sales DESC";
        } else {
            $fish_sql = "SELECT 
                p.id, p.name, 
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as num_orders,
                AVG(oi.unit_price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'paid') AND p.category = 'fish'
                GROUP BY p.id
                ORDER BY total_sales DESC";
        }
        
        $fish_stmt = $conn->prepare($fish_sql);
        if ($fish_stmt) {
            $fish_stmt->execute();
            $fish_res = $fish_stmt->get_result();
            while ($row = $fish_res->fetch_assoc()) {
                $fish_sales[] = $row;
            }
            $fish_stmt->close();
        }
        
        // Menu Orders Summary
        $menu_orders_summary = [];
        if ($period == '7days') {
            $menu_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value,
                status
                FROM menu_orders
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY status";
        } elseif ($period == '30days') {
            $menu_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value,
                status
                FROM menu_orders
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY status";
        } elseif ($period == '90days') {
            $menu_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value,
                status
                FROM menu_orders
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                GROUP BY status";
        } elseif ($period == 'month') {
            $menu_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value,
                status
                FROM menu_orders
                WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())
                GROUP BY status";
        } elseif ($period == 'year') {
            $menu_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value,
                status
                FROM menu_orders
                WHERE YEAR(created_at) = YEAR(NOW())
                GROUP BY status";
        } else {
            $menu_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value,
                status
                FROM menu_orders
                GROUP BY status";
        }
        
        $menu_stmt = $conn->prepare($menu_sql);
        if ($menu_stmt) {
            $menu_stmt->execute();
            $menu_res = $menu_stmt->get_result();
            while ($row = $menu_res->fetch_assoc()) {
                $menu_orders_summary[] = $row;
            }
            $menu_stmt->close();
        }
        
        // Menu Items Details
        $menu_items_detail = [];
        if ($period == '7days') {
            $menu_items_sql = "SELECT 
                mi.item_type,
                mi.item_id,
                CASE WHEN mi.item_type = 'product' THEN p.name ELSE f.common_name END as item_name,
                SUM(mi.quantity) as total_qty,
                SUM(mi.subtotal) as total_sales,
                COUNT(DISTINCT mi.menu_order_id) as num_orders,
                AVG(mi.unit_price) as avg_price
                FROM menu_order_items mi
                LEFT JOIN products p ON mi.item_type = 'product' AND mi.item_id = p.id
                LEFT JOIN fish_species f ON mi.item_type = 'fish' AND mi.item_id = f.id
                JOIN menu_orders mo ON mi.menu_order_id = mo.id
                WHERE mo.status IN ('confirmed', 'paid') AND mo.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY mi.item_type, mi.item_id
                ORDER BY total_sales DESC
                LIMIT 15";
        } elseif ($period == '30days') {
            $menu_items_sql = "SELECT 
                mi.item_type,
                mi.item_id,
                CASE WHEN mi.item_type = 'product' THEN p.name ELSE f.common_name END as item_name,
                SUM(mi.quantity) as total_qty,
                SUM(mi.subtotal) as total_sales,
                COUNT(DISTINCT mi.menu_order_id) as num_orders,
                AVG(mi.unit_price) as avg_price
                FROM menu_order_items mi
                LEFT JOIN products p ON mi.item_type = 'product' AND mi.item_id = p.id
                LEFT JOIN fish_species f ON mi.item_type = 'fish' AND mi.item_id = f.id
                JOIN menu_orders mo ON mi.menu_order_id = mo.id
                WHERE mo.status IN ('confirmed', 'paid') AND mo.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY mi.item_type, mi.item_id
                ORDER BY total_sales DESC
                LIMIT 15";
        } elseif ($period == '90days') {
            $menu_items_sql = "SELECT 
                mi.item_type,
                mi.item_id,
                CASE WHEN mi.item_type = 'product' THEN p.name ELSE f.common_name END as item_name,
                SUM(mi.quantity) as total_qty,
                SUM(mi.subtotal) as total_sales,
                COUNT(DISTINCT mi.menu_order_id) as num_orders,
                AVG(mi.unit_price) as avg_price
                FROM menu_order_items mi
                LEFT JOIN products p ON mi.item_type = 'product' AND mi.item_id = p.id
                LEFT JOIN fish_species f ON mi.item_type = 'fish' AND mi.item_id = f.id
                JOIN menu_orders mo ON mi.menu_order_id = mo.id
                WHERE mo.status IN ('confirmed', 'paid') AND mo.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                GROUP BY mi.item_type, mi.item_id
                ORDER BY total_sales DESC
                LIMIT 15";
        } elseif ($period == 'month') {
            $menu_items_sql = "SELECT 
                mi.item_type,
                mi.item_id,
                CASE WHEN mi.item_type = 'product' THEN p.name ELSE f.common_name END as item_name,
                SUM(mi.quantity) as total_qty,
                SUM(mi.subtotal) as total_sales,
                COUNT(DISTINCT mi.menu_order_id) as num_orders,
                AVG(mi.unit_price) as avg_price
                FROM menu_order_items mi
                LEFT JOIN products p ON mi.item_type = 'product' AND mi.item_id = p.id
                LEFT JOIN fish_species f ON mi.item_type = 'fish' AND mi.item_id = f.id
                JOIN menu_orders mo ON mi.menu_order_id = mo.id
                WHERE mo.status IN ('confirmed', 'paid') AND YEAR(mo.created_at) = YEAR(NOW()) AND MONTH(mo.created_at) = MONTH(NOW())
                GROUP BY mi.item_type, mi.item_id
                ORDER BY total_sales DESC
                LIMIT 15";
        } elseif ($period == 'year') {
            $menu_items_sql = "SELECT 
                mi.item_type,
                mi.item_id,
                CASE WHEN mi.item_type = 'product' THEN p.name ELSE f.common_name END as item_name,
                SUM(mi.quantity) as total_qty,
                SUM(mi.subtotal) as total_sales,
                COUNT(DISTINCT mi.menu_order_id) as num_orders,
                AVG(mi.unit_price) as avg_price
                FROM menu_order_items mi
                LEFT JOIN products p ON mi.item_type = 'product' AND mi.item_id = p.id
                LEFT JOIN fish_species f ON mi.item_type = 'fish' AND mi.item_id = f.id
                JOIN menu_orders mo ON mi.menu_order_id = mo.id
                WHERE mo.status IN ('confirmed', 'paid') AND YEAR(mo.created_at) = YEAR(NOW())
                GROUP BY mi.item_type, mi.item_id
                ORDER BY total_sales DESC
                LIMIT 15";
        } else {
            $menu_items_sql = "SELECT 
                mi.item_type,
                mi.item_id,
                CASE WHEN mi.item_type = 'product' THEN p.name ELSE f.common_name END as item_name,
                SUM(mi.quantity) as total_qty,
                SUM(mi.subtotal) as total_sales,
                COUNT(DISTINCT mi.menu_order_id) as num_orders,
                AVG(mi.unit_price) as avg_price
                FROM menu_order_items mi
                LEFT JOIN products p ON mi.item_type = 'product' AND mi.item_id = p.id
                LEFT JOIN fish_species f ON mi.item_type = 'fish' AND mi.item_id = f.id
                JOIN menu_orders mo ON mi.menu_order_id = mo.id
                WHERE mo.status IN ('confirmed', 'paid')
                GROUP BY mi.item_type, mi.item_id
                ORDER BY total_sales DESC
                LIMIT 15";
        }
        
        $menu_items_stmt = $conn->prepare($menu_items_sql);
        if ($menu_items_stmt) {
            $menu_items_stmt->execute();
            $menu_items_res = $menu_items_stmt->get_result();
            while ($row = $menu_items_res->fetch_assoc()) {
                $menu_items_detail[] = $row;
            }
            $menu_items_stmt->close();
        }
        ?>
        
        <!-- Print Styles -->
        <style>
            @media print {
                .d-flex.gap-2 button:not(:first-child) { display: none; }
                .btn { display: none; }
                .layout-navbar, .layout-sidenav, .d-flex.justify-content-between { display: none !important; }
                .layout-content { margin-left: 0; }
                body { background: white; }
                .card { break-inside: avoid; page-break-inside: avoid; }
                .row { page-break-inside: avoid; }
                .bg-gradient { background: white !important; }
                .text-white { color: #333 !important; }
                .badge { display: inline-block; }
                h4 { margin-bottom: 0.5rem; }
                .print-header { text-align: center; margin-bottom: 2rem; border-bottom: 2px solid #333; padding-bottom: 1rem; }
                .print-header h4 { margin: 0; font-size: 24px; }
                .print-header p { margin: 0.25rem 0; font-size: 12px; }
                table { width: 100%; margin-bottom: 1rem; }
                .table-responsive { border: none; }
            }
        </style>
        
        <!-- Print Header (only visible when printing) -->
        <div class="print-header" style="display: none;">
            <h4>Sales Report</h4>
            <p>Period: <?php echo $periodLabel; ?></p>
            <p>Generated: <?php echo date('F d, Y \a\t h:i A'); ?></p>
        </div>
        
        <script>
        // Add print header visibility on print
        window.addEventListener('beforeprint', function() {
            document.querySelector('.print-header').style.display = 'block';
        });
        
        window.addEventListener('afterprint', function() {
            document.querySelector('.print-header').style.display = 'none';
        });
        
        // Export to CSV functionality
        function exportToCSV() {
            let csv = '';
            const period = '<?php echo $periodLabel; ?>';
            const generated = '<?php echo date('F d, Y h:i A'); ?>';
            
            // Header
            csv += 'Maata Fish Farm System - Sales Report\n';
            csv += 'Period: ' + period + '\n';
            csv += 'Generated: ' + generated + '\n\n';
            
            // Category Performance Table
            csv += 'CATEGORY PERFORMANCE\n';
            csv += 'Category,Orders,Qty Sold,Total Sales,Avg Price\n';
            const categoryRows = document.querySelectorAll('table')[0]?.querySelectorAll('tbody tr') || [];
            categoryRows.forEach(row => {
                const cols = row.querySelectorAll('td');
                if (cols.length >= 5) {
                    csv += cols[0].textContent.trim() + ',' +
                           cols[1].textContent.trim() + ',' +
                           cols[2].textContent.trim() + ',' +
                           cols[3].textContent.trim().replace('₱', '') + ',' +
                           cols[4].textContent.trim().replace('₱', '') + '\n';
                }
            });
            
            csv += '\n';
            
            // Best Sellers Table
            csv += 'TOP 10 BEST SELLERS\n';
            csv += 'Product,Category,Sold,Revenue,Orders\n';
            const productRows = document.querySelectorAll('table')[1]?.querySelectorAll('tbody tr') || [];
            productRows.forEach(row => {
                const cols = row.querySelectorAll('td');
                if (cols.length >= 5) {
                    csv += cols[0].textContent.trim() + ',' +
                           cols[1].textContent.trim() + ',' +
                           cols[2].textContent.trim().replace(' units', '') + ',' +
                           cols[3].textContent.trim().replace('₱', '') + ',' +
                           cols[4].textContent.trim() + '\n';
                }
            });
            
            csv += '\n';
            
            // Fish Species Sales Details
            csv += 'FISH SPECIES SALES DETAILS\n';
            csv += 'Fish Species,Units Sold,Total Sales,Orders,Avg Price\n';
            const fishRows = document.querySelectorAll('table')[2]?.querySelectorAll('tbody tr') || [];
            fishRows.forEach(row => {
                const cols = row.querySelectorAll('td');
                if (cols.length >= 5) {
                    csv += cols[0].textContent.trim() + ',' +
                           cols[1].textContent.trim().replace(' kg', '') + ',' +
                           cols[2].textContent.trim().replace('₱', '') + ',' +
                           cols[3].textContent.trim() + ',' +
                           cols[4].textContent.trim().replace('₱', '') + '\n';
                }
            });
            
            csv += '\n';
            
            // Menu Orders Summary
            csv += 'MENU ORDERS SUMMARY\n';
            csv += 'Status,Orders,Total Sales,Avg Order\n';
            const menuStatusRows = document.querySelectorAll('table')[3]?.querySelectorAll('tbody tr') || [];
            menuStatusRows.forEach(row => {
                const cols = row.querySelectorAll('td');
                if (cols.length >= 4) {
                    csv += cols[0].textContent.trim() + ',' +
                           cols[1].textContent.trim() + ',' +
                           cols[2].textContent.trim().replace('₱', '') + ',' +
                           cols[3].textContent.trim().replace('₱', '') + '\n';
                }
            });
            
            csv += '\n';
            
            // Menu Items Details
            csv += 'TOP MENU ITEMS ORDERED\n';
            csv += 'Item Name,Type,Quantity,Total Sales,Orders,Avg Price\n';
            const menuItemsRows = document.querySelectorAll('table')[4]?.querySelectorAll('tbody tr') || [];
            menuItemsRows.forEach(row => {
                const cols = row.querySelectorAll('td');
                if (cols.length >= 6) {
                    csv += cols[0].textContent.trim() + ',' +
                           cols[1].textContent.trim() + ',' +
                           cols[2].textContent.trim() + ',' +
                           cols[3].textContent.trim().replace('₱', '') + ',' +
                           cols[4].textContent.trim() + ',' +
                           cols[5].textContent.trim().replace('₱', '') + '\n';
                }
            });
            
            // Download CSV
            const element = document.createElement('a');
            element.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv));
            element.setAttribute('download', 'sales-report-' + new Date().toISOString().split('T')[0] + '.csv');
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        }
        </script>
        
        <!-- Period Info Alert -->
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle"></i> <strong>Report Period:</strong> <?php echo $periodLabel; ?> 
            <small class="text-muted">(Generated: <?php echo date('M d, Y'); ?>)</small>
        </div>
        
        <!-- Sales Metrics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card h-100 bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-white">
                        <h6 class="text-uppercase mb-2" style="opacity: 0.9;">Total Revenue</h6>
                        <h3 class="font-weight-bold mb-0">₱<?php echo number_format($metrics['total_revenue'] ?? 0, 2); ?></h3>
                        <small style="opacity: 0.8;">From <?php echo $metrics['total_orders'] ?? 0; ?> orders</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card h-100 bg-gradient" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body text-white">
                        <h6 class="text-uppercase mb-2" style="opacity: 0.9;">Avg Order Value</h6>
                        <h3 class="font-weight-bold mb-0">₱<?php echo number_format($metrics['avg_order_value'] ?? 0, 2); ?></h3>
                        <small style="opacity: 0.8;">Per transaction</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card h-100 bg-gradient" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body text-white">
                        <h6 class="text-uppercase mb-2" style="opacity: 0.9;">Total Orders</h6>
                        <h3 class="font-weight-bold mb-0"><?php echo $metrics['total_orders'] ?? 0; ?></h3>
                        <small style="opacity: 0.8;">Completed & Paid</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card h-100 bg-gradient" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body text-white">
                        <h6 class="text-uppercase mb-2" style="opacity: 0.9;">Unique Customers</h6>
                        <h3 class="font-weight-bold mb-0"><?php echo $metrics['unique_customers'] ?? 0; ?></h3>
                        <small style="opacity: 0.8;">Active buyers</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Daily Sales Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesTrendChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Order Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Category & Product Performance -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Category Performance</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Orders</th>
                                    <th>Qty Sold</th>
                                    <th>Total Sales</th>
                                    <th>Avg Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">No sales data available</td></tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><strong><?php echo ucfirst(htmlspecialchars($cat['category'])); ?></strong></td>
                                        <td><?php echo $cat['num_orders']; ?></td>
                                        <td><?php echo $cat['total_qty']; ?></td>
                                        <td><strong>₱<?php echo number_format($cat['total_sales'], 2); ?></strong></td>
                                        <td>₱<?php echo number_format($cat['avg_price'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Top 10 Best Sellers</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                    <th>Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_products)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">No sales data available</td></tr>
                                <?php else: ?>
                                    <?php foreach ($top_products as $prod): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                        <td><span class="badge badge-light"><?php echo ucfirst(htmlspecialchars($prod['category'])); ?></span></td>
                                        <td><?php echo $prod['total_qty']; ?> units</td>
                                        <td><strong style="color: #28a745;">₱<?php echo number_format($prod['total_sales'], 2); ?></strong></td>
                                        <td><?php echo $prod['num_orders']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fish Sales Details & Menu Orders -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Fish Species Sales Details</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Fish Species</th>
                                    <th>Units Sold</th>
                                    <th>Total Sales</th>
                                    <th>Orders</th>
                                    <th>Avg Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fish_sales)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">No fish sales data available</td></tr>
                                <?php else: ?>
                                    <?php foreach ($fish_sales as $fish): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($fish['name']); ?></strong></td>
                                        <td><?php echo $fish['total_qty']; ?> kg</td>
                                        <td><strong style="color: #28a745;">₱<?php echo number_format($fish['total_sales'], 2); ?></strong></td>
                                        <td><?php echo $fish['num_orders']; ?></td>
                                        <td>₱<?php echo number_format($fish['avg_price'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header with-elements">
                        <h5 class="card-header-title">Menu Orders Summary</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Status</th>
                                    <th>Orders</th>
                                    <th>Total Sales</th>
                                    <th>Avg Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($menu_orders_summary)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3">No menu orders data available</td></tr>
                                <?php else: ?>
                                    <?php foreach ($menu_orders_summary as $menu_stat): ?>
                                    <tr>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                    echo ($menu_stat['status'] == 'paid') ? 'badge-success' : 
                                                         (($menu_stat['status'] == 'confirmed') ? 'badge-info' : 
                                                         (($menu_stat['status'] == 'pending') ? 'badge-warning' : 'badge-danger'));
                                                ?>
                                            ">
                                                <?php echo ucfirst($menu_stat['status']); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo $menu_stat['total_orders']; ?></strong></td>
                                        <td><strong>₱<?php echo number_format($menu_stat['total_sales'] ?? 0, 2); ?></strong></td>
                                        <td>₱<?php echo number_format($menu_stat['avg_order_value'] ?? 0, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menu Items Details -->
        <div class="card mb-4">
            <div class="card-header with-elements">
                <h5 class="card-header-title">Top Menu Items Ordered</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Total Sales</th>
                            <th>Orders</th>
                            <th>Avg Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($menu_items_detail)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">No menu items data available</td></tr>
                        <?php else: ?>
                            <?php foreach ($menu_items_detail as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['item_name'] ?? 'Unknown'); ?></strong></td>
                                <td><span class="badge badge-primary"><?php echo ucfirst($item['item_type']); ?></span></td>
                                <td><?php echo $item['total_qty']; ?></td>
                                <td><strong style="color: #28a745;">₱<?php echo number_format($item['total_sales'], 2); ?></strong></td>
                                <td><?php echo $item['num_orders']; ?></td>
                                <td>₱<?php echo number_format($item['avg_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Order Status Breakdown -->
        <div class="card">
            <div class="card-header with-elements">
                <h5 class="card-header-title">Order Status Breakdown</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Total Amount</th>
                            <th>Percentage</th>
                            <th>Visual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_all = array_sum(array_column($status_dist, 'count'));
                        foreach ($status_dist as $stat):
                            $percentage = $total_all > 0 ? ($stat['count'] / $total_all) * 100 : 0;
                            $status_colors = [
                                'pending' => '#ff9800',
                                'confirmed' => '#2196f3',
                                'paid' => '#4caf50',
                                'cancelled' => '#f44336'
                            ];
                            $color = $status_colors[$stat['status']] ?? '#666';
                        ?>
                        <tr>
                            <td><strong><?php echo ucfirst(htmlspecialchars($stat['status'])); ?></strong></td>
                            <td><?php echo $stat['count']; ?></td>
                            <td>₱<?php echo number_format($stat['amount'] ?? 0, 2); ?></td>
                            <td><?php echo round($percentage, 1); ?>%</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $color; ?>;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Prepare data for charts
const dailySalesData = <?php echo json_encode(array_reverse($daily_sales)); ?>;
const statusDistData = <?php echo json_encode($status_dist); ?>;

// Daily Sales Trend Chart
if (dailySalesData.length > 0) {
    const ctx1 = document.getElementById('salesTrendChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: dailySalesData.map(d => new Date(d.sale_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'})),
            datasets: [
                {
                    label: 'Daily Revenue (₱)',
                    data: dailySalesData.map(d => parseFloat(d.daily_revenue)),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Order Status Distribution Chart
if (statusDistData.length > 0) {
    const ctx2 = document.getElementById('statusChart').getContext('2d');
    const colors = {
        'pending': '#ff9800',
        'confirmed': '#2196f3',
        'paid': '#4caf50',
        'cancelled': '#f44336'
    };
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: statusDistData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
            datasets: [{
                data: statusDistData.map(d => d.count),
                backgroundColor: statusDistData.map(d => colors[d.status] || '#999'),
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}
</script>

<?php include 'partials/footer.php'; ?>
