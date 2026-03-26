<?php
include 'auth_admin.php';

// Check authorization - admin, staff, and manager
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
    header('Location: index.php');
    exit;
}

require 'config/db.php';

// Fixed entrance fee
$ENTRANCE_FEE = 50;

// Handle form submission
$message = '';
$message_type = '';
$submission_data = null;

// Check if this is a redirect after successful deletion
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = '✅ Entrance fee record deleted successfully!';
    $message_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_guests') {
    // Sanitize inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $num_guests = intval($_POST['num_guests'] ?? 0);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($phone) || $num_guests < 1) {
        $message = 'Please enter representative name, phone number, and number of guests';
        $message_type = 'error';
    } else {
        // Sanitize for database
        $first_name = htmlspecialchars($first_name);
        $last_name = htmlspecialchars($last_name);
        $phone = htmlspecialchars($phone);
        
        $total_amount = $num_guests * $ENTRANCE_FEE;
        
        // Insert representative into customers table
        $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, phone, customer_type) VALUES (?, ?, ?, 'diner')");
        if ($stmt) {
            $stmt->bind_param('sss', $first_name, $last_name, $phone);
            if ($stmt->execute()) {
                $customer_id = $conn->insert_id;
                
                // Log activity
                require 'handlers/activity_logger.php';
                $description = "Recorded entrance fee for {$num_guests} guest(s) represented by {$first_name} {$last_name}. Total: ₱" . number_format($total_amount, 2);
                
                $logged = logActivity(
                    $conn,
                    $_SESSION['user_id'],
                    $_SESSION['role'],
                    'CREATE',
                    'entrance_fee',
                    $customer_id,
                    'Entrance Fee Collection',
                    $description,
                    null,
                    ['num_guests' => $num_guests, 'fee_per_guest' => $ENTRANCE_FEE, 'total' => $total_amount, 'representative' => "$first_name $last_name", 'phone' => $phone]
                );
                
                if ($logged) {
                    $message = 'Entrance fee recorded successfully!';
                    $message_type = 'success';
                    
                    // Store submission data for display
                    $submission_data = [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'phone' => $phone,
                        'num_guests' => $num_guests,
                        'fee_per_guest' => $ENTRANCE_FEE,
                        'total_amount' => $total_amount,
                        'timestamp' => date('M d, Y g:ia')
                    ];
                } else {
                    $message = 'Customer added but activity logging failed';
                    $message_type = 'error';
                }
            } else {
                $message = 'Failed to insert customer record';
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Database error occurred';
            $message_type = 'error';
        }
    }
}

// Handle delete entrance fee record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_record') {
    $record_id = intval($_POST['record_id'] ?? 0);
    
    if ($record_id > 0) {
        // Delete the activity log record
        $delete_stmt = $conn->prepare("DELETE FROM activity_logs WHERE id = ? AND entity_type = 'entrance_fee'");
        if ($delete_stmt) {
            $delete_stmt->bind_param('i', $record_id);
            if ($delete_stmt->execute()) {
                $message = '✅ Entrance fee record deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to delete the record';
                $message_type = 'error';
            }
            $delete_stmt->close();
        } else {
            $message = 'Database error occurred';
            $message_type = 'error';
        }
        
        // Redirect to prevent form resubmission
        header('Location: entrance_fee.php?success=1');
        exit;
    } else {
        $message = 'Invalid record ID';
        $message_type = 'error';
    }
}

// Get all entrance fee collection records from activity logs
$records = [];
$stmt = $conn->prepare("SELECT id, user_name, new_values, timestamp FROM activity_logs WHERE entity_type = 'entrance_fee' AND activity_type = 'CREATE' ORDER BY timestamp DESC LIMIT 100");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $new_values = json_decode($row['new_values'], true);
        $records[] = [
            'id' => $row['id'],
            'user_name' => $row['user_name'],
            'num_guests' => $new_values['num_guests'] ?? 0,
            'fee_per_guest' => $new_values['fee_per_guest'] ?? $ENTRANCE_FEE,
            'total' => $new_values['total'] ?? 0,
            'timestamp' => $row['timestamp']
        ];
    }
    $stmt->close();
}



include 'partials/head.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Entrance Fee Management</h4>
        <div class="text-muted small mt-0 mb-4 d-block breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a></li>
                <li class="breadcrumb-item active">Entrance Fee</li>
            </ol>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo ($message_type === 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert" style="<?php echo ($message_type === 'success') ? 'background-color:#28a745;color:white;border:none;' : 'background-color:#dc3545;color:white;border:none;'; ?>">
                <i class="feather icon-<?php echo ($message_type === 'success') ? 'check-circle' : 'alert-circle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color:white; opacity:0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Entrance Fee Input Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-header-title">Record Entrance Fee</h5>
                <p class="text-muted mb-0">Fixed Entrance Fee: <strong>₱<?php echo $ENTRANCE_FEE; ?>.00</strong> per guest</p>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="record_guests">
                    
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="first_name"><strong>Representative First Name *</strong></label>
                            <input type="text" class="form-control form-control-lg" id="first_name" name="first_name" placeholder="First name" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="last_name"><strong>Representative Last Name *</strong></label>
                            <input type="text" class="form-control form-control-lg" id="last_name" name="last_name" placeholder="Last name" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="phone"><strong>Phone Number *</strong></label>
                            <input type="tel" class="form-control form-control-lg" id="phone" name="phone" placeholder="e.g., 09123456789" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="num_guests"><strong>Total Number of Guests *</strong></label>
                            <input type="number" class="form-control form-control-lg" id="num_guests" name="num_guests" placeholder="Enter total guests" min="1" max="500" required>
                        </div>
                    </div>

                    <!-- Total Calculation -->
                    <div class="alert alert-info mt-4" style="background-color:#cfe2ff;color:#084298;border-color:#b6d4fe;">
                        <div class="row text-center">
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Fee Per Guest</p>
                                <h4 class="mb-0">₱<?php echo number_format($ENTRANCE_FEE, 2); ?></h4>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Total Entrance Fee</p>
                                <h4 class="mb-0" style="color:#28a745;"><strong>₱<span id="totalFeeDisplay">0.00</span></strong></h4>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-right">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="feather icon-save"></i> Record Entrance Fee
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Display Recorded Result -->
        <?php if ($submission_data): ?>
        <div class="card border-success mt-4">
            <div class="card-header" style="background-color:#28a745;color:white;">
                <h5 class="card-header-title mb-0"><i class="feather icon-check-circle"></i> Entrance Fee Recorded Successfully</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4 text-center border-right">
                        <p class="text-muted mb-1">Representative</p>
                        <h5><?php echo $submission_data['first_name'] . ' ' . $submission_data['last_name']; ?></h5>
                        <small class="text-muted"><?php echo $submission_data['phone']; ?></small>
                    </div>
                    <div class="col-md-4 text-center border-right">
                        <p class="text-muted mb-1">Total Guests</p>
                        <h3 class="text-primary"><?php echo $submission_data['num_guests']; ?></h3>
                    </div>
                    <div class="col-md-4 text-center">
                        <p class="text-muted mb-1">Total Amount</p>
                        <h3 class="text-success"><strong>₱<?php echo number_format($submission_data['total_amount'], 2); ?></strong></h3>
                    </div>
                </div>
                <small class="text-muted">Recorded at: <?php echo $submission_data['timestamp']; ?></small>
            </div>
        </div>
        <?php endif; ?>

        <!-- Guests Added from Entrance Fee System Table -->
        <div class="card mt-4 mb-4">
            <div class="card-header">
                <h5 class="card-header-title mb-0"><i class="feather icon-users"></i> Guests Added from Entrance Fee System</h5>
            </div>
            <div class="table-responsive">
                <table id="entranceFeeGuestsTable" class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Date Time Added</th>
                            <th class="text-right">Guest Number</th>
                            <th class="text-right">Fee/Guest (₱)</th>
                            <th class="text-right">Total Amount (₱)</th>
                            <th>Recorded By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php 
                            $recordsWithGuests = array_filter($records, function($record) {
                                return $record['num_guests'] > 0;
                            });
                            ?>
                            <?php if (!empty($recordsWithGuests)): ?>
                                <?php foreach ($recordsWithGuests as $record): ?>
                                <tr>
                                    <td><?php echo (int)$record['id']; ?></td>
                                    <td><small><?php echo date('M d, Y g:ia', strtotime($record['timestamp'])); ?></small></td>
                                    <td class="text-right"><strong><?php echo $record['num_guests']; ?></strong></td>
                                    <td class="text-right">₱<?php echo number_format($record['fee_per_guest'], 2); ?></td>
                                    <td class="text-right"><strong style="color: #28a745;">₱<?php echo number_format($record['total'], 2); ?></strong></td>
                                    <td><small><?php echo htmlspecialchars($record['user_name']); ?></small></td>
                                    <td class="text-center">
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this entrance fee record?');">
                                            <input type="hidden" name="action" value="delete_record">
                                            <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete">
                                                <i class="feather icon-trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No entrance fee records with guests found</td>
                                </tr>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No entrance fee records yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


    </div>
    <!-- [ content ] End -->

    <?php include 'partials/footer.php'; ?>
</div>
<!-- [ Layout content ] End -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const ENTRANCE_FEE = <?php echo $ENTRANCE_FEE; ?>;

// Update total fee calculation when number of guests changes
document.getElementById('num_guests').addEventListener('input', function() {
    const numGuests = parseInt(this.value) || 0;
    const totalFee = numGuests * ENTRANCE_FEE;
    document.getElementById('totalFeeDisplay').textContent = totalFee.toFixed(2);
});

// Initialize total fee display
document.getElementById('num_guests').dispatchEvent(new Event('input'));
</script>


