<!-- [ Layout container ] Start -->
<?php
require_once __DIR__ . '/../config/db.php';
// Ensure session is started by auth_admin.php which should be included before this partial
$user_id = $_SESSION['user_id'] ?? null;
$nav_user = null;
if ($user_id) {
    $u_stmt = $conn->prepare('SELECT id, username, full_name FROM users WHERE id = ? LIMIT 1');
    if ($u_stmt) {
        $u_stmt->bind_param('i', $user_id);
        $u_stmt->execute();
        if (method_exists($u_stmt, 'get_result')) {
            $u_res = $u_stmt->get_result();
            $nav_user = $u_res->fetch_assoc();
        } else {
            $u_stmt->bind_result($nid, $nusername, $nfull_name);
            if ($u_stmt->fetch()) {
                $nav_user = ['id' => $nid, 'username' => $nusername, 'full_name' => $nfull_name];
            }
        }
        $u_stmt->close();
    }
}
// Get recent pending reservations (top 5) and pending count
$recent_reservations = [];
$pending_count = 0;
$status = 'pending';
$r_stmt = $conn->prepare('SELECT r.id, r.reservation_number, r.reservation_type, r.num_guests, r.reservation_date, r.reservation_time, r.contact_email, r.contact_phone, r.created_at, c.first_name, c.last_name FROM reservations r LEFT JOIN customers c ON r.customer_id = c.id WHERE r.status = ? ORDER BY r.created_at DESC LIMIT 5');
if ($r_stmt) {
    $r_stmt->bind_param('s', $status);
    $r_stmt->execute();
    $r_res = $r_stmt->get_result();
    while ($row = $r_res->fetch_assoc()) {
        $recent_reservations[] = $row;
    }
    $r_stmt->close();
}
$pc_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM reservations WHERE status = ?');
if ($pc_stmt) {
    $pc_stmt->bind_param('s', $status);
    $pc_stmt->execute();
    $pc_res = $pc_stmt->get_result();
    $pending_count = (int)($pc_res->fetch_assoc()['cnt'] ?? 0);
    $pc_stmt->close();
}
?>
<div class="layout-container">
    <!-- [ Layout navbar ( Header ) ] Start -->
    <nav class="layout-navbar navbar navbar-expand-lg align-items-lg-center bg-dark container-p-x" id="layout-navbar">

        <!-- Brand demo (see assets/css/demo/demo.css) -->
        <a href="index.php" class="navbar-brand app-brand demo d-lg-none py-0 mr-4">
            <span class="app-brand-logo demo">
                <img src="assets/img/logo-dark.png" alt="Brand Logo" class="img-fluid">
            </span>

        </a>

        <!-- Sidenav toggle (see assets/css/demo/demo.css) -->
        <div class="layout-sidenav-toggle navbar-nav d-lg-none align-items-lg-center mr-auto">
            <a class="nav-item nav-link px-0 mr-lg-4" href="javascript:">
                <i class="ion ion-md-menu text-large align-middle"></i>
            </a>
        </div>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#layout-navbar-collapse">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-collapse collapse" id="layout-navbar-collapse">
            <!-- Divider -->
            <hr class="d-lg-none w-100 my-2">

            <div class="navbar-nav align-items-lg-center">
                <!-- Search -->
                <!-- <label class="nav-item navbar-text navbar-search-box p-0 active">
                    <i class="feather icon-search navbar-icon align-middle"></i>
                    <span class="navbar-search-input pl-2">
                        <input type="text" class="form-control navbar-text mx-2" placeholder="Search...">
                    </span>
                </label> -->
            </div>

            <div class="navbar-nav align-items-lg-center ml-auto">
                <div class="demo-navbar-notifications nav-item dropdown mr-lg-3">
                    <a class="nav-link dropdown-toggle" href="javascript:void(0);" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor: pointer; pointer-events: auto;">
                        <i class="feather icon-bell navbar-icon align-middle"></i>
                        <?php if ($pending_count > 0): ?>
                        <span class="badge badge-danger"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                        <span class="d-lg-none align-middle">&nbsp; Notifications</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" style="z-index: 1040 !important; position: absolute !important; pointer-events: auto !important;">
                        <div class="bg-primary text-center text-white font-weight-bold p-3">
                            <?php echo $pending_count; ?> New Reservations
                        </div>
                        <div class="list-group list-group-flush">
                        <?php if (!empty($recent_reservations)): ?>
                            <?php foreach ($recent_reservations as $res): ?>
                            <a href="reservations_list.php" class="list-group-item list-group-item-action media d-flex align-items-center" style="pointer-events: auto; cursor: pointer;">
                                <div class="ui-icon ui-icon-sm feather icon-calendar bg-secondary border-0 text-white"></div>
                                <div class="media-body line-height-condenced ml-3">
                                    <div class="text-dark"><?php echo htmlspecialchars($res['reservation_number']); ?> — <?php echo htmlspecialchars(trim(($res['first_name'] ?? '') . ' ' . ($res['last_name'] ?? '')) ?: ($res['contact_email'] ?? $res['contact_phone'] ?? 'Guest')); ?></div>
                                    <div class="text-light small mt-1"><?php echo htmlspecialchars($res['reservation_type']); ?> · <?php echo htmlspecialchars($res['reservation_date']); ?> <?php echo htmlspecialchars($res['reservation_time']); ?> · Guests: <?php echo htmlspecialchars($res['num_guests']); ?></div>
                                    <div class="text-light small mt-1"><?php echo htmlspecialchars($res['created_at']); ?></div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item text-center text-muted">No new reservations</div>
                        <?php endif; ?>
                        </div>
                        <a href="reservations_list.php" class="d-block text-center text-light small p-2 my-1" style="pointer-events: auto; cursor: pointer;">View all reservations</a>
                    </div>
                </div>


                <!-- Divider -->
                <div class="nav-item d-none d-lg-block text-big font-weight-light line-height-1 opacity-25 mr-3 ml-1">|</div>
                <div class="demo-navbar-user nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="javascript:void(0);" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor: pointer; pointer-events: auto;">
                        <span class="d-inline-flex flex-lg-row-reverse align-items-center align-middle">
                            <img src="assets/img/avatars/1.png" alt class="d-block ui-w-30 rounded-circle">
                            <span class="px-1 mr-lg-2 ml-2 ml-lg-0"><?php echo htmlspecialchars(
                                // prefer full user details from the page if available, else nav_user, else username, else Admin
                                ($user['full_name'] ?? $user['username'] ?? null) ?: ($nav_user['full_name'] ?? $nav_user['username'] ?? 'Admin')
                            ); ?></span>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" style="z-index: 1040 !important; position: absolute !important; pointer-events: auto !important;">
                        <a href="admin_profile.php" class="dropdown-item" style="pointer-events: auto; cursor: pointer;">
                            <i class="feather icon-user text-muted"></i> &nbsp; My profile</a>
                        <!-- <a href="admin_profile.php" class="dropdown-item">
                            <i class="feather icon-settings text-muted"></i> &nbsp; Account settings</a> -->
                        <div class="dropdown-divider"></div>
                        <a href="handlers/logout.php" class="dropdown-item" style="pointer-events: auto; cursor: pointer;" onclick="return confirm('Are you sure you want to sign out?');">
                            <i class="feather icon-power text-danger"></i> &nbsp; Log Out</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <!-- [ Layout navbar ( Header ) ] End -->