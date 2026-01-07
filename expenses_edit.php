<?php
require 'auth_admin.php';
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','manager','admin'])) {
    header('Location: index.php?error=Access denied');
    exit;
}
require 'config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: expenses.php?error=' . urlencode('Invalid id'));
    exit;
}

// prepare statement
$stmt = $conn->prepare('SELECT id, amount, currency, transaction_date, description, category, subcategory, payment_method, vendor, location, status, receipt_available, receipt_image_path, notes FROM expenses WHERE id = ? LIMIT 1');
$fetch_error = null;
if (!$stmt) {
    $fetch_error = 'DB prepare error: ' . ($conn->error ?? '');
} else {
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        $fetch_error = 'DB execute error: ' . ($stmt->error ?? '');
    }
}

// Try to fetch using get_result() (requires mysqlnd). If unavailable, fall back to bind_result/fetch.
$row = null;
$res = null;
if ($stmt && method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    if ($res) $row = $res->fetch_assoc();
}
if ($row === null && $stmt) {
    // fallback: bind variables and fetch
    $stmt->bind_result($f_id, $f_amount, $f_currency, $f_transaction_date, $f_description, $f_category, $f_subcategory, $f_payment_method, $f_vendor, $f_location, $f_status, $f_receipt_available, $f_receipt_image_path, $f_notes);
    if ($stmt->fetch()) {
        $row = [
            'id' => $f_id,
            'amount' => $f_amount,
            'currency' => $f_currency,
            'transaction_date' => $f_transaction_date,
            'description' => $f_description,
            'category' => $f_category,
            'subcategory' => $f_subcategory,
            'payment_method' => $f_payment_method,
            'vendor' => $f_vendor,
            'location' => $f_location,
            'status' => $f_status,
            'receipt_available' => $f_receipt_available,
            'receipt_image_path' => $f_receipt_image_path,
            'notes' => $f_notes,
        ];
    }
}

$stmt->close();
if (!$row) {
    if (!$fetch_error) $fetch_error = 'No record found for id ' . $id;
    include 'partials/head.php';
    include 'partials/sidenav.php';
    include 'partials/navbar.php';
    ?>
    <div class="layout-content">
        <div class="container-fluid flex-grow-1 container-p-y">
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-warning">Expense not found or could not be loaded.</div>
                    <div class="card"><div class="card-body"><strong>Debug:</strong> <?php echo htmlspecialchars($fetch_error); ?></div></div>
                    <a href="expenses.php" class="btn btn-secondary mt-2">Back to Expenses</a>
                </div>
            </div>
        </div>
    </div>
    <?php
    include 'partials/footer.php';
    exit;
}

include 'partials/head.php';
include 'partials/sidenav.php';
include 'partials/navbar.php';
// Debug mode: show fetched row and any DB error when ?debug=1 is present
if (!empty($_GET['debug'])) {
    echo '<div class="container mt-3"><pre style="white-space:pre-wrap;background:#f8f9fa;border:1px solid #ddd;padding:10px;">';
    echo "Debug fetch_error: " . htmlspecialchars($fetch_error ?? '');
    echo "\n\nRow:\n";
    echo htmlspecialchars(print_r($row, true));
    echo '</pre></div>';
}
?>
<div class="layout-content">
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="row mt-3">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Edit Expense #<?php echo htmlspecialchars($row['id'] ?? '');?></h4></div>
                    <div class="card-body">
                        <form method="post" action="handlers/expenses_update.php" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo (int)($row['id'] ?? 0);?>">
                            <div class="form-group">
                                <label>Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($row['amount'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Currency</label>
                                <input type="text" name="currency" maxlength="3" class="form-control" value="<?php echo htmlspecialchars($row['currency'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Transaction Date</label>
                                <input type="date" name="transaction_date" class="form-control" value="<?php echo htmlspecialchars($row['transaction_date'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" name="description" maxlength="255" class="form-control" value="<?php echo htmlspecialchars($row['description'] ?? ''); ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Category</label>
                                    <select name="category" id="expense_category" class="form-control" required>
                                        <option value="">-- Select Category --</option>
                                        <option value="Operating" <?php echo (($row['category'] ?? '')=='Operating')?'selected':''; ?>>Operating</option>
                                        <option value="Inventory" <?php echo (($row['category'] ?? '')=='Inventory')?'selected':''; ?>>Inventory</option>
                                        <option value="Payroll" <?php echo (($row['category'] ?? '')=='Payroll')?'selected':''; ?>>Payroll</option>
                                        <option value="Maintenance" <?php echo (($row['category'] ?? '')=='Maintenance')?'selected':''; ?>>Maintenance</option>
                                        <option value="Utilities" <?php echo (($row['category'] ?? '')=='Utilities')?'selected':''; ?>>Utilities</option>
                                        <option value="Marketing" <?php echo (($row['category'] ?? '')=='Marketing')?'selected':''; ?>>Marketing</option>
                                        <option value="Misc" <?php echo (($row['category'] ?? '')=='Misc')?'selected':''; ?>>Misc</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Subcategory</label>
                                    <select name="subcategory" id="expense_subcategory" class="form-control">
                                        <option value="">-- Select Subcategory --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method" class="form-control">
                                    <?php foreach (['Cash','Card','Digital','Other'] as $pm): ?>
                                        <option value="<?php echo $pm;?>" <?php echo (($row['payment_method'] ?? '')==$pm)?'selected':'';?>><?php echo $pm;?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Vendor</label>
                                <input type="text" name="vendor" maxlength="100" class="form-control" value="<?php echo htmlspecialchars($row['vendor'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" maxlength="150" class="form-control" value="<?php echo htmlspecialchars($row['location'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <?php foreach (['Recorded','Reviewed','Categorized','Reimbursable'] as $st): ?>
                                        <option value="<?php echo $st;?>" <?php echo (($row['status'] ?? '')==$st)?'selected':'';?>><?php echo $st;?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group form-check">
                                <input type="checkbox" name="receipt_available" value="1" class="form-check-input" id="receiptChk" <?php echo !empty($row['receipt_available']) ? 'checked' : ''; ?> >
                                <label class="form-check-label" for="receiptChk">Receipt Available</label>
                            </div>
                            <div class="form-group">
                                <label>Receipt Image (optional)</label>
                                <?php if (!empty($row['receipt_image_path'])): ?>
                                    <div><a href="<?php echo htmlspecialchars($row['receipt_image_path']); ?>" target="_blank">Current</a></div>
                                <?php endif; ?>
                                <input type="file" name="receipt_image" accept="image/*" class="form-control-file">
                            </div>
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($row['notes'] ?? ''); ?></textarea>
                            </div>
                            <button class="btn btn-primary">Update Expense</button>
                            <a href="expenses.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var mapping = {
        'Operating': ['Supplies','Rent','Licenses','Insurance'],
        'Inventory': ['Fish Purchase','Feed','Chemicals','Packaging'],
        'Payroll': ['Salaries','Benefits','Bonuses','Contractors'],
        'Maintenance': ['Repairs','Equipment','Tools','Cleaning'],
        'Utilities': ['Electricity','Water','Internet','Gas'],
        'Marketing': ['Ads','Promotions','Events','Materials'],
        'Misc': ['Other']
    };

    var cat = document.getElementById('expense_category');
    var sub = document.getElementById('expense_subcategory');

    function populateSubcats(selected){
        sub.innerHTML = '<option value="">-- Select Subcategory --</option>';
        if (!selected || !mapping[selected]) return;
        mapping[selected].forEach(function(s){
            var opt = document.createElement('option');
            opt.value = s;
            opt.textContent = s;
            if (s === (<?php echo json_encode($row['subcategory'] ?? ''); ?>)) opt.selected = true;
            sub.appendChild(opt);
        });
    }

    cat.addEventListener('change', function(){ populateSubcats(this.value); });
    if (cat.value) populateSubcats(cat.value);
});
</script>
