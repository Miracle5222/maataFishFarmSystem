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

// 7. Total Sales (Combined)
$metrics['combined_sales'] = $metrics['total_sales'] + $metrics['total_menu_sales'];

// 8. Today's Sales
$today = date('Y-m-d');
$today_stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) = ?
    UNION ALL
    SELECT COALESCE(SUM(total_amount), 0) as total FROM menu_orders WHERE DATE(created_at) = ?');
$today_stmt->bind_param('ss', $today, $today);
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
$month_stmt = $conn->prepare('SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) >= ?
    UNION ALL
    SELECT COALESCE(SUM(total_amount), 0) as total FROM menu_orders WHERE DATE(created_at) >= ?');
$month_stmt->bind_param('ss', $month_start, $month_start);
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
?>

<!-- [ Layout content ] Start -->
<div class="layout-content">

    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Dashboard</h4>
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item"><a href="#">Library</a></li>
                <li class="breadcrumb-item active">Data</li>
            </ol>
        </div>
        <div class="row">
            <!-- 1st row Start -->
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo number_format($metrics['today_sales'], 2); ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-primary">Today</span> Sales</p>
                            </div>
                            <div class="lnr lnr-cart display-4 text-primary"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo number_format($metrics['month_sales'], 2); ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-success">This Month</span> Sales</p>
                            </div>
                            <div class="lnr lnr-chart-bars display-4 text-success"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo number_format($metrics['combined_sales'], 2); ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-warning">Total</span> Sales</p>
                            </div>
                            <div class="lnr lnr-diamond display-4 text-warning"></div>
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
                            <div class="lnr lnr-hourglass display-4 text-danger"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo $metrics['total_customers']; ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-info">Total</span> Customers</p>
                            </div>
                            <div class="lnr lnr-users display-4 text-info"></div>
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
                            <div class="lnr lnr-calendar display-4 text-secondary"></div>
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
                                <p class="text-muted mb-0"><span class="badge badge-light">Regular</span> Orders</p>
                            </div>
                            <div class="lnr lnr-inbox display-4 text-muted"></div>
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
                            <div class="lnr lnr-restaurant display-4 text-muted"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="">
                                <h2 class="mb-2"><?php echo $metrics['total_products']; ?></h2>
                                <p class="text-muted mb-0"><span class="badge badge-primary">Available</span> Products</p>
                            </div>
                            <div class="lnr lnr-gift display-4 text-primary"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-body">
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
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="media mb-3">
                                    <i class="lnr lnr-chart-bars display-4 d-block text-primary mr-3"></i>
                                    <div class="media-body">
                                        <h6 class="mb-0">Regular Orders</h6>
                                        <p class="text-muted small mb-0">₱<?php echo number_format($metrics['total_sales'], 2); ?> Revenue</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="media mb-3">
                                    <i class="lnr lnr-rocket display-4 d-block text-warning mr-3"></i>
                                    <div class="media-body">
                                        <h6 class="mb-0">Menu Orders</h6>
                                        <p class="text-muted small mb-0">₱<?php echo number_format($metrics['total_menu_sales'], 2); ?> Revenue</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <!-- 3rd row Start -->
            <div class="col-xl-7">
                <div class="card mb-4">
                    <div class="card-header with-elements pb-0">
                        <h6 class="card-header-title mb-0">Sales Summary</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <td><strong>Today's Sales:</strong></td>
                                    <td class="text-right"><strong>₱<?php echo number_format($metrics['today_sales'], 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><strong>This Month's Sales:</strong></td>
                                    <td class="text-right"><strong>₱<?php echo number_format($metrics['month_sales'], 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Sales (All Orders):</strong></td>
                                    <td class="text-right"><strong>₱<?php echo number_format($metrics['combined_sales'], 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Regular Orders Revenue:</td>
                                    <td class="text-right">₱<?php echo number_format($metrics['total_sales'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Menu Orders Revenue:</td>
                                    <td class="text-right">₱<?php echo number_format($metrics['total_menu_sales'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card mb-4">
                    <div class="card-header with-elements pb-0">
                        <h6 class="card-header-title mb-0">System Overview</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <td>Total Customers:</td>
                                    <td class="text-right"><strong><?php echo $metrics['total_customers']; ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Total Reservations:</td>
                                    <td class="text-right"><strong><?php echo $metrics['total_reservations']; ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Regular Orders:</td>
                                    <td class="text-right"><strong><?php echo $metrics['total_orders']; ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Menu Orders:</td>
                                    <td class="text-right"><strong><?php echo $metrics['total_menu_orders']; ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Products Available:</td>
                                    <td class="text-right"><strong><?php echo $metrics['total_products']; ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Fish Species:</td>
                                    <td class="text-right"><strong><?php echo $metrics['total_fish']; ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- 3rd row Start -->
        </div>

    </div>
    <!-- [ content ] End -->

    <?php include 'partials/footer.php'; ?>