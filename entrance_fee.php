<?php
include 'auth_admin.php';

// Check authorization - admin, staff, and manager
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
    header('Location: index.php');
    exit;
}

require 'config/db.php';

// Handle form submission
$message = '';
$message_type = '';
$recorded_data = null;  // Store last recorded entrance fee

// Use session flash to avoid form resubmission prompt (PRG)
if (!isset($_SESSION)) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $fee_amount = floatval($_POST['fee_amount'] ?? 0);
        $fee_description = htmlspecialchars($_POST['fee_description'] ?? '');
        $fee_active = isset($_POST['fee_active']) ? 1 : 0;
        
        if ($fee_amount < 0) {
            $message = 'Entrance fee cannot be negative';
            $message_type = 'error';
        } else {
            // Update or insert entrance fee
            $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = 'entrance_fee'");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing
                $update = $conn->prepare("UPDATE settings SET setting_value = ?, description = ? WHERE setting_key = 'entrance_fee'");
                $update->bind_param('ds', $fee_amount, $fee_description);
                if ($update->execute()) {
                    $message = 'Entrance fee updated successfully';
                    $message_type = 'success';
                    
                    // Log activity
                    require 'handlers/activity_logger.php';
                    logActivity($conn, $_SESSION['user_id'], $_SESSION['role'], 'EDIT', 'entrance_fee', 1, 'Entrance Fee', "Updated entrance fee to ₱$fee_amount: $fee_description", ['previous' => $previous_fee ?? null], ['new' => $fee_amount]);
                } else {
                    $message = 'Failed to update entrance fee: ' . $conn->error;
                    $message_type = 'error';
                }
                $update->close();
            } else {
                // Insert new
                $insert = $conn->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES ('entrance_fee', ?, ?)");
                $insert->bind_param('ds', $fee_amount, $fee_description);
                if ($insert->execute()) {
                    $message = 'Entrance fee created successfully';
                    $message_type = 'success';
                    
                    // Log activity
                    require 'handlers/activity_logger.php';
                    logActivity($conn, $_SESSION['user_id'], $_SESSION['role'], 'CREATE', 'entrance_fee', 1, 'Entrance Fee', "Created entrance fee: ₱$fee_amount - $fee_description", null, ['amount' => $fee_amount, 'description' => $fee_description]);
                } else {
                    $message = 'Failed to create entrance fee: ' . $conn->error;
                    $message_type = 'error';
                }
                $insert->close();
            }
            $stmt->close();
        }
    } elseif ($action === 'record') {
        // Record an entrance fee collection (number of visitors and fee per person)
        $num_visitors = intval($_POST['num_visitors'] ?? 1);
        $fee_per = floatval($_POST['fee_per'] ?? 0);
        if ($num_visitors < 1) $num_visitors = 1;

        if ($fee_per < 0) {
            $message = 'Entrance fee cannot be negative';
            $message_type = 'error';
        } else {
            $total_amount = $num_visitors * $fee_per;

            require 'handlers/activity_logger.php';
            $description = "Collected ₱" . number_format($total_amount, 2) . " from {$num_visitors} visitor(s) at ₱" . number_format($fee_per, 2) . " each";

            $logged = logActivity(
                $conn,
                $_SESSION['user_id'],
                $_SESSION['role'],
                'CREATE',
                'entrance_fee',
                0,
                'Entrance Fee Collection',
                $description,
                null,
                ['num_visitors' => $num_visitors, 'fee_per' => $fee_per, 'total' => $total_amount]
            );

            if ($logged) {
                $message = 'Entrance fee recorded successfully';
                $message_type = 'success';
                // Store the recorded data to display it
                $recorded_data = [
                    'num_visitors' => $num_visitors,
                    'fee_per' => $fee_per,
                    'total_amount' => $total_amount,
                    'timestamp' => date('M d, Y g:ia')
                ];
            } else {
                $message = 'Failed to record entrance fee';
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete') {
        // Only allow admins to delete records
        if ($_SESSION['role'] !== 'admin') {
            $message = 'You do not have permission to delete records';
            $message_type = 'error';
        } else {
            $record_id = intval($_POST['record_id'] ?? 0);
            if ($record_id > 0) {
                // Delete the record from activity_logs
                $delete_stmt = $conn->prepare("DELETE FROM activity_logs WHERE id = ? AND entity_type = 'entrance_fee' AND activity_type = 'CREATE'");
                $delete_stmt->bind_param('i', $record_id);
                if ($delete_stmt->execute()) {
                    $message = 'Entrance fee record deleted successfully';
                    $message_type = 'success';
                    
                    // Log the deletion
                    require 'handlers/activity_logger.php';
                    logActivity($conn, $_SESSION['user_id'], $_SESSION['role'], 'DELETE', 'entrance_fee', $record_id, 'Entrance Fee Record', "Deleted entrance fee collection record ID: $record_id", null, null);
                } else {
                    $message = 'Failed to delete entrance fee record: ' . $conn->error;
                    $message_type = 'error';
                }
                $delete_stmt->close();
            } else {
                $message = 'Invalid record ID';
                $message_type = 'error';
            }
        }
    }
}

// After handling POST, store flash and redirect to avoid resubmission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['entrance_fee_flash'] = [
        'message' => $message,
        'type' => $message_type,
        'recorded_data' => $recorded_data
    ];
    header('Location: entrance_fee.php');
    exit;
}

// Get current entrance fee
$entrance_fee = null;
$fee_amount = 0;
$fee_description = '';

$stmt = $conn->prepare("SELECT setting_value, description FROM settings WHERE setting_key = 'entrance_fee'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $entrance_fee = $result->fetch_assoc();
        $fee_amount = floatval($entrance_fee['setting_value'] ?? 0);
        $fee_description = $entrance_fee['description'] ?? '';
    }
    $stmt->close();
}

// Get all entrance fee collection records
$entrance_fee_records = [];
$stmt = $conn->prepare("SELECT id, user_name, new_values, timestamp FROM activity_logs WHERE entity_type = 'entrance_fee' AND activity_type = 'CREATE' ORDER BY timestamp DESC LIMIT 100");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $new_values = json_decode($row['new_values'], true);
        $entrance_fee_records[] = [
            'id' => $row['id'],
            'user_name' => $row['user_name'],
            'num_visitors' => $new_values['num_visitors'] ?? 0,
            'fee_per' => $new_values['fee_per'] ?? 0,
            'total' => $new_values['total'] ?? 0,
            'timestamp' => $row['timestamp']
        ];
    }
    $stmt->close();
}

// (No history table shown on this page per request)
?>

<?php include 'partials/head.php'; ?>
<?php
// Read and clear flash (if any)
if (!empty($_SESSION['entrance_fee_flash'])) {
    $flash = $_SESSION['entrance_fee_flash'];
    $message = $flash['message'] ?? '';
    $message_type = $flash['type'] ?? '';
    $recorded_data = $flash['recorded_data'] ?? null;
    unset($_SESSION['entrance_fee_flash']);
}
?>
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
            <?php
                $isSuccess = ($message_type === 'success');
                $alertClass = $isSuccess ? 'alert-success' : 'alert-danger';
                $alertStyle = $isSuccess ? 'background-color:#d4edda;color:#155724;border-color:#c3e6cb;' : 'background-color:#f8d7da;color:#721c24;border-color:#f5c6cb;';
            ?>
            <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert" style="<?php echo $alertStyle; ?>">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Current Entrance Fee Card -->
       

        <!-- Calculate & Record Entrance Fee -->
        <form method="POST">
            <input type="hidden" name="action" value="record">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-header-title">Calculate & Record Entrance Fee</h5>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="numVisitors">Number of Visitors</label>
                            <input type="number" class="form-control form-control-lg" id="numVisitors" name="num_visitors" placeholder="Enter number of visitors" min="1" value="1" style="font-size: 16px;">
                            <small class="form-text text-muted">How many visitors?</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="visitorsEntranceFee">Entrance Fee Per Person (₱)</label>
                            <input type="number" class="form-control form-control-lg" id="visitorsEntranceFee" name="fee_per" placeholder="Enter fee amount" min="0" step="0.01" value="<?php echo $fee_amount; ?>" style="font-size: 16px;">
                            <small class="form-text text-muted">Fee per person</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="totalEntranceFee">Total Amount (₱)</label>
                            <div class="input-group input-group-lg">
                                <input type="text" class="form-control text-success font-weight-bold" id="totalEntranceFee" readonly value="<?php echo number_format($fee_amount, 2); ?>" style="font-size: 18px; background-color: #e8f5e9;">
                                <div class="input-group-append">
                                    <span class="input-group-text" style="background-color: #e8f5e9; border-left: none;">
                                        <i class="feather icon-check-circle text-success"></i>
                                    </span>
                                </div>
                            </div>
                            <small class="form-text text-muted">Automatic calculation</small>
                        </div>
                    </div>
                    <div class="alert alert-success mt-3 mb-3" style="background-color:#d4edda;color:#155724;border-color:#c3e6cb;">
                        <div class="text-center">
                            <small><i class="feather icon-info"></i> <strong>Calculation:</strong> <span id="numVisitorsDisplay">1</span> visitor(s) × ₱<span id="feePerPersonDisplay"><?php echo number_format($fee_amount, 2); ?></span> = <h4 class="text-success mb-0" style="display: inline;">₱<span id="totalAmountDisplay"><?php echo number_format($fee_amount, 2); ?></span></h4></small>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="feather icon-save"></i> Record Entrance Fee
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Display Recorded Entrance Fee -->
        <?php if ($recorded_data): ?>
        <div class="card border-success mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-header-title mb-0"><i class="feather icon-check-circle"></i> Entrance Fee Recorded</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center border-right">
                        <p class="text-muted mb-1">Number of Visitors</p>
                        <h3 class="text-primary"><?php echo $recorded_data['num_visitors']; ?></h3>
                    </div>
                    <div class="col-md-3 text-center border-right">
                        <p class="text-muted mb-1">Fee Per Person</p>
                        <h3 class="text-info">₱<?php echo number_format($recorded_data['fee_per'], 2); ?></h3>
                    </div>
                    <div class="col-md-3 text-center border-right">
                        <p class="text-muted mb-1">Total Amount</p>
                        <h2 class="text-success font-weight-bold">₱<?php echo number_format($recorded_data['total_amount'], 2); ?></h2>
                    </div>
                    <div class="col-md-3 text-center">
                        <p class="text-muted mb-1">Recorded At</p>
                        <p class="mb-0"><small><?php echo $recorded_data['timestamp']; ?></small></p>
                    </div>
                </div>
                <hr class="my-3">
                <div class="text-center">
                    <p class="text-muted mb-0"><i class="feather icon-info"></i> This record has been saved to the activity log and is now part of your entrance fee history.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Entrance Fee Records Table -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-header-title">Entrance Fee Collection Records</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Date & Time</th>
                            <th class="text-right">Visitors</th>
                            <th class="text-right">Fee/Person (₱)</th>
                            <th class="text-right">Total Amount (₱)</th>
                            <th>Recorded By</th>
                            <?php if (strtolower($_SESSION['role']) === 'admin'): ?>
                            <th class="text-center">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($entrance_fee_records)): ?>
                            <?php foreach ($entrance_fee_records as $record): ?>
                                <tr>
                                    <td><small><?php echo date('M d, Y g:ia', strtotime($record['timestamp'])); ?></small></td>
                                    <td class="text-right"><strong><?php echo $record['num_visitors']; ?></strong></td>
                                    <td class="text-right">₱<?php echo number_format($record['fee_per'], 2); ?></td>
                                    <td class="text-right"><strong class="text-success">₱<?php echo number_format($record['total'], 2); ?></strong></td>
                                    <td><small><?php echo htmlspecialchars($record['user_name']); ?></small></td>
                                    <?php if (strtolower($_SESSION['role']) === 'admin'): ?>
                                    <td class="text-center">
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this entrance fee record?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Record">
                                                <i class="feather icon-trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo strtolower($_SESSION['role']) === 'admin' ? '6' : '5'; ?>" class="text-center text-muted py-4">No entrance fee records yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->

    <?php include 'partials/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Function to calculate and update total
    function calculateTotal() {
        const numVisitors = parseInt($('#numVisitors').val()) || 1;
        const entranceFeePerPerson = parseFloat($('#visitorsEntranceFee').val()) || 0;
        const total = (numVisitors * entranceFeePerPerson).toFixed(2);
        
        // Update total display field
        $('#totalEntranceFee').val(parseFloat(total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        
        // Update calculation display
        $('#numVisitorsDisplay').text(numVisitors);
        $('#feePerPersonDisplay').text(parseFloat(entranceFeePerPerson).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#totalAmountDisplay').text(parseFloat(total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }
    
    // Event listeners for both inputs
    $('#numVisitors').on('input change', function() {
        // Ensure minimum of 1
        if (parseInt($(this).val()) < 1) {
            $(this).val(1);
        }
        calculateTotal();
    });
    
    $('#visitorsEntranceFee').on('input change', function() {
        // Ensure minimum of 0
        if (parseFloat($(this).val()) < 0) {
            $(this).val(0);
        }
        calculateTotal();
    });
    
    // Calculate on page load
    calculateTotal();
});
</script>

