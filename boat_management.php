<?php 
include 'auth_admin.php';
require 'config/db.php';

// Check authorization - admin/staff/manager
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
    header('Location: index.php');
    exit;
}

// Handle delete boat BEFORE any output
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_boat' && isset($_POST['boat_id'])) {
        $boat_id = intval($_POST['boat_id']);
        
        // Get boat name for logging
        $get_boat = $conn->prepare("SELECT boat_name FROM boat_inventory WHERE id = ?");
        if ($get_boat) {
            $get_boat->bind_param('i', $boat_id);
            $get_boat->execute();
            $boat_data = $get_boat->get_result()->fetch_assoc();
            $get_boat->close();
            
            if ($boat_data) {
                $delete_stmt = $conn->prepare("DELETE FROM boat_inventory WHERE id = ?");
                if ($delete_stmt) {
                    $delete_stmt->bind_param('i', $boat_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    
                    // Log activity
                    require 'handlers/activity_logger.php';
                    logActivity(
                        $conn,
                        $_SESSION['user_id'],
                        $_SESSION['role'],
                        'DELETE',
                        'boat_inventory',
                        $boat_id,
                        'Boat Deleted',
                        "Deleted boat: {$boat_data['boat_name']}",
                        null,
                        ['boat_id' => $boat_id, 'boat_name' => $boat_data['boat_name']]
                    );
                }
            }
        }
        
        // Redirect to prevent form resubmission
        header('Location: boat_management.php?success=1');
        exit;
    }
}

include 'partials/head.php';
include 'partials/sidenav.php';
include 'partials/navbar.php';

$message = '';
$message_type = '';

// Detect if `rental_price` column exists (so migration can be optional)
$has_rental_price = false;
$col_check = $conn->query("SHOW COLUMNS FROM `boat_inventory` LIKE 'rental_price'");
if ($col_check && $col_check->num_rows > 0) {
    $has_rental_price = true;
}

// Handle add boat
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_boat') {
        $boat_name = trim($_POST['boat_name'] ?? '');
        $boat_type = trim($_POST['boat_type'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 0);
        $rental_price = floatval($_POST['rental_price'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (empty($boat_name) || empty($boat_type) || $capacity < 1) {
            $message = '❌ Please fill in all required fields (capacity minimum 1)';
            $message_type = 'error';
        } else {
            // Prepare INSERT depending on whether rental_price column exists
            if ($has_rental_price) {
                $sql = "INSERT INTO boat_inventory (boat_name, boat_type, capacity, rental_price, description, status) VALUES (?, ?, ?, ?, ?, 'available')";
                $add_stmt = $conn->prepare($sql);
                if ($add_stmt) $add_stmt->bind_param('ssids', $boat_name, $boat_type, $capacity, $rental_price, $description);
            } else {
                $sql = "INSERT INTO boat_inventory (boat_name, boat_type, capacity, description, status) VALUES (?, ?, ?, ?, 'available')";
                $add_stmt = $conn->prepare($sql);
                if ($add_stmt) $add_stmt->bind_param('ssds', $boat_name, $boat_type, $capacity, $description);
            }

            if ($add_stmt) {
                if ($add_stmt->execute()) {
                    $message = '✅ Boat "' . htmlspecialchars($boat_name) . '" added successfully!';
                    $message_type = 'success';

                    // Log activity
                    require 'handlers/activity_logger.php';
                    logActivity(
                        $conn,
                        $_SESSION['user_id'],
                        $_SESSION['role'],
                        'CREATE',
                        'boat_inventory',
                        $conn->insert_id,
                        'Boat Added',
                        "Added new boat: {$boat_name} ({$boat_type}), Capacity: {$capacity}, Rate: ₱{$rental_price}",
                        null,
                        ['boat_name' => $boat_name, 'boat_type' => $boat_type, 'capacity' => $capacity, 'rental_price' => $rental_price]
                    );
                } else {
                    $message = '❌ Database error: ' . $add_stmt->error;
                    $message_type = 'error';
                }
                $add_stmt->close();
            } else {
                $message = '❌ Database error: ' . $conn->error . ' | Tables may not be initialized. Please visit <a href="create_boat_rentals_table.php" style="color:white; text-decoration:underline;">Setup Page</a>';
                $message_type = 'error';
            }
        }
    }
    
    // Handle update boat status
    if ($_POST['action'] === 'update_status') {
        $boat_id = intval($_POST['boat_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        
        if ($boat_id > 0 && in_array($new_status, ['available', 'rented', 'maintenance', 'inactive'])) {
            $update_stmt = $conn->prepare("UPDATE boat_inventory SET status = ? WHERE id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param('si', $new_status, $boat_id);
                if ($update_stmt->execute()) {
                    $message = '✅ Boat status updated!';
                    $message_type = 'success';
                    
                    // Log activity
                    require 'handlers/activity_logger.php';
                    logActivity(
                        $conn,
                        $_SESSION['user_id'],
                        $_SESSION['role'],
                        'UPDATE',
                        'boat_inventory',
                        $boat_id,
                        'Boat Status Updated',
                        "Updated boat ID {$boat_id} status to: {$new_status}",
                        null,
                        ['boat_id' => $boat_id, 'status' => $new_status]
                    );
                    // If boat made available, cancel any active/pending rentals for this boat
                    if ($new_status === 'available') {
                        // get boat name
                        $bn_stmt = $conn->prepare("SELECT boat_name FROM boat_inventory WHERE id = ? LIMIT 1");
                        if ($bn_stmt) {
                            $bn_stmt->bind_param('i', $boat_id);
                            $bn_stmt->execute();
                            $bn_res = $bn_stmt->get_result();
                            if ($bn_row = $bn_res->fetch_assoc()) {
                                $boat_name_to_clear = $bn_row['boat_name'];
                                $cancel_stmt = $conn->prepare("UPDATE boat_rentals SET status = 'cancelled' WHERE boat_name = ? AND status IN ('pending','active')");
                                if ($cancel_stmt) {
                                    $cancel_stmt->bind_param('s', $boat_name_to_clear);
                                    if ($cancel_stmt->execute()) {
                                        $num = $cancel_stmt->affected_rows;
                                        if ($num > 0) {
                                            // Log cancellation activity
                                            logActivity(
                                                $conn,
                                                $_SESSION['user_id'],
                                                $_SESSION['role'],
                                                'UPDATE',
                                                'boat_rentals',
                                                null,
                                                'Boat Rentals Cancelled',
                                                "Cancelled {$num} rental(s) for boat: {$boat_name_to_clear} because boat marked available",
                                                null,
                                                ['boat_name' => $boat_name_to_clear, 'cancelled_count' => $num]
                                            );
                                        }
                                    }
                                    $cancel_stmt->close();
                                }
                            }
                            $bn_stmt->close();
                        }
                    }
                } else {
                    $message = '❌ Failed to update status';
                    $message_type = 'error';
                }
                $update_stmt->close();
            }
        }
    }
}

// Get all boats
$boats = [];
if ($has_rental_price) {
    $boats_stmt = $conn->prepare("SELECT id, boat_name, boat_type, capacity, rental_price, description, status FROM boat_inventory ORDER BY boat_name");
} else {
    $boats_stmt = $conn->prepare("SELECT id, boat_name, boat_type, capacity, description, status FROM boat_inventory ORDER BY boat_name");
}
if ($boats_stmt) {
    $boats_stmt->execute();
    $boats_res = $boats_stmt->get_result();
    while ($boat = $boats_res->fetch_assoc()) {
        $boats[] = $boat;
    }
    $boats_stmt->close();
}
?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Boat Management</h4>
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item"><a href="#">Reservation</a></li>
                <li class="breadcrumb-item active">Boat Management</li>
            </ol>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="background-color:#28a745;color:white;border:none;">
                <i class="feather icon-check-circle"></i> Operation completed successfully!
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color:white;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo ($message_type === 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert" style="<?php echo ($message_type === 'success') ? 'background-color:#28a745;color:white;border:none;' : 'background-color:#dc3545;color:white;border:none;'; ?>">
                <i class="feather icon-<?php echo ($message_type === 'success') ? 'check-circle' : 'alert-circle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color:white;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Add Boat Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-header-title">➕ Add New Boat</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_boat">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="boatName"><strong>Boat Name</strong></label>
                            <input type="text" class="form-control" id="boatName" name="boat_name" placeholder="e.g., Speedboat 1" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="boatType"><strong>Boat Type</strong></label>
                            <input type="text" class="form-control" id="boatType" name="boat_type" placeholder="e.g., Speed Boat, Fishing Boat" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="capacity"><strong>Capacity (Passengers)</strong></label>
                            <input type="number" class="form-control" id="capacity" name="capacity" placeholder="e.g., 4" min="1" max="50" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="rentalPrice"><strong>Hourly Rent (₱)</strong></label>
                            <input type="number" class="form-control" id="rentalPrice" name="rental_price" placeholder="e.g., 100.00" step="0.01" min="0" required>
                            <small class="form-text text-muted">Per-boat hourly rate. Enter 0 to use the standard rate.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description"><strong>Description (Optional)</strong></label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief description of the boat..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="feather icon-plus"></i> Add Boat
                    </button>
                </form>
            </div>
        </div>

        <!-- Boats List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-header-title">🚤 Available Boats</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($boats)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Boat Name</th>
                                <th>Type</th>
                                <th>Capacity</th>
                                <th>Hourly Rate</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($boats as $boat): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($boat['boat_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($boat['boat_type']); ?></td>
                                <td><?php echo $boat['capacity']; ?> pax</td>
                                <td>₱<?php echo number_format(floatval($boat['rental_price'] ?? 0), 2); ?></td>
                                <td><small><?php echo htmlspecialchars($boat['description'] ?: 'N/A'); ?></small></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="boat_id" value="<?php echo $boat['id']; ?>">
                                        <select name="status" class="form-control form-control-sm" style="width: auto; display: inline-block;" onchange="this.form.submit();">
                                            <option value="available" <?php echo $boat['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="rented" <?php echo $boat['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                            <option value="maintenance" <?php echo $boat['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            <option value="inactive" <?php echo $boat['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete boat: ' + '<?php echo addslashes($boat['boat_name']); ?>' + '?');">
                                        <input type="hidden" name="action" value="delete_boat">
                                        <input type="hidden" name="boat_id" value="<?php echo $boat['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete">
                                            <i class="feather icon-trash-2"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-4">
                    <p>No boats added yet. Add a boat above to get started!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<?php include 'partials/footer.php'; ?>
