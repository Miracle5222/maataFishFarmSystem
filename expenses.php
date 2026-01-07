<?php
require 'auth_admin.php';

// Allow staff, manager, admin to access this page
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','manager','admin'])) {
    header('Location: index.php?error=Access denied');
    exit;
}

include 'partials/head.php';
include 'partials/sidenav.php';
include 'partials/navbar.php';
require 'config/db.php';
?>
<div class="layout-content">
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="row mt-3">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Record Expense</h4></div>
                    <div class="card-body">
                        <?php if (!empty($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show expense-auto-close text-dark" role="alert" style="background-color:#f8d7da;">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show expense-auto-close text-dark" role="alert" style="background-color:#d4edda;">
                                <?php echo htmlspecialchars($_GET['success']) ?: 'Saved.'; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="handlers/expenses_handler.php" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Currency</label>
                                <input type="text" name="currency" maxlength="3" class="form-control" value="PHP" required>
                            </div>
                            <div class="form-group">
                                <label>Transaction Date</label>
                                <input type="date" name="transaction_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" name="description" maxlength="255" class="form-control">
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Category</label>
                                    <select name="category" id="expense_category" class="form-control" required>
                                        <option value="">-- Select Category --</option>
                                        <option value="Operating">Operating</option>
                                        <option value="Inventory">Inventory</option>
                                        <option value="Payroll">Payroll</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Utilities">Utilities</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Misc">Misc</option>
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
                                    <option>Cash</option>
                                    <option>Card</option>
                                    <option>Digital</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Vendor</label>
                                <input type="text" name="vendor" maxlength="100" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" maxlength="150" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option>Recorded</option>
                                    <option>Reviewed</option>
                                    <option>Categorized</option>
                                    <option>Reimbursable</option>
                                </select>
                            </div>
                            <div class="form-group form-check">
                                <input type="checkbox" name="receipt_available" value="1" class="form-check-input" id="receiptChk">
                                <label class="form-check-label" for="receiptChk">Receipt Available</label>
                            </div>
                            <div class="form-group">
                                <label>Receipt Image (optional)</label>
                                <input type="file" name="receipt_image" accept="image/*" class="form-control-file">
                            </div>
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                            <button class="btn btn-primary">Save Expense</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Recent Expenses</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Amount</th>
                                        <th>Currency</th>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Subcat</th>
                                        <th>Method</th>
                                        <th>Vendor</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Receipt</th>
                                        <th>Notes</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $q = $conn->prepare('SELECT id, amount, currency, transaction_date, description, category, subcategory, payment_method, vendor, location, status, receipt_available, receipt_image_path, notes, created_at, created_by FROM expenses ORDER BY id DESC LIMIT 100');
                                if ($q) {
                                    $q->execute();
                                    $res = $q->get_result();
                                    while ($r = $res->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($r['id']) . '</td>';
                                        echo '<td>' . number_format($r['amount'],2) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['currency']) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['transaction_date']) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['category']) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['subcategory']) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['payment_method']) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['vendor']) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['location']) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['status']) . '</td>';
                                        if (!empty($r['receipt_image_path'])) {
                                            echo '<td><a href="' . htmlspecialchars($r['receipt_image_path']) . '" target="_blank">View</a></td>';
                                        } else {
                                            echo '<td>' . ($r['receipt_available'] ? 'Yes' : 'No') . '</td>';
                                        }
                                        echo '<td>' . htmlspecialchars(substr($r['notes'],0,60)) . '</td>';
                                        echo '<td>' . htmlspecialchars($r['created_by']) . '</td>';
                                        // actions: edit (modal), delete
                                        $id = (int)$r['id'];
                                        echo '<td>';
                                        echo '<button type="button" data-id="' . $id . '" class="btn btn-sm btn-outline-primary mr-1 edit-expense-btn">Edit</button>';
                                        echo '<form method="post" action="handlers/expenses_delete.php" style="display:inline;" onsubmit="return confirm(\'Delete this expense?\');">';
                                        echo '<input type="hidden" name="id" value="' . $id . '">';
                                        echo '<button class="btn btn-sm btn-outline-danger">Delete</button>';
                                        echo '</form>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

<!-- Edit Expense Modal -->
<div id="expenseModal" class="modal" tabindex="-1" role="dialog" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:1050;">
    <div class="modal-dialog" role="document" style="max-width:850px; margin:3rem auto;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Expense</h5>
                <button type="button" id="expenseModalClose" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" style="max-height:calc(100vh - 200px);overflow-y:auto;">
                <form id="expenseModalForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="m_id">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Amount</label>
                            <input id="m_amount" name="amount" type="number" step="0.01" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Currency</label>
                            <input id="m_currency" name="currency" type="text" maxlength="3" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Transaction Date</label>
                        <input id="m_transaction_date" name="transaction_date" type="date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input id="m_description" name="description" type="text" maxlength="255" class="form-control">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Category</label>
                            <select id="expense_category_modal" name="category" class="form-control">
                                <option value="">-- Select Category --</option>
                                <option value="Operating">Operating</option>
                                <option value="Inventory">Inventory</option>
                                <option value="Payroll">Payroll</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Misc">Misc</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Subcategory</label>
                            <select id="expense_subcategory_modal" name="subcategory" class="form-control"><option value="">-- Select Subcategory --</option></select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Payment Method</label>
                            <select id="m_payment_method" name="payment_method" class="form-control"><option>Cash</option><option>Card</option><option>Digital</option><option>Other</option></select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Vendor</label>
                            <input id="m_vendor" name="vendor" type="text" maxlength="100" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Location</label>
                            <input id="m_location" name="location" type="text" maxlength="150" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <select id="m_status" name="status" class="form-control"><option>Recorded</option><option>Reviewed</option><option>Categorized</option><option>Reimbursable</option></select>
                        </div>
                    </div>
                    <div class="form-group form-check">
                        <input id="m_receipt_available" name="receipt_available" type="checkbox" value="1" class="form-check-input"><label class="form-check-label">Receipt Available</label>
                    </div>
                    <div class="form-group">
                        <label>Receipt Image (optional)</label>
                        <input id="m_receipt_image" name="receipt_image" type="file" accept="image/*" class="form-control-file">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea id="m_notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="expenseModalCloseFooter" class="btn btn-secondary">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


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
            sub.appendChild(opt);
        });
    }

    cat.addEventListener('change', function(){ populateSubcats(this.value); });
    if (cat.value) populateSubcats(cat.value);
    // auto-close alerts after 4 seconds
    var autoAlerts = document.querySelectorAll('.expense-auto-close');
    autoAlerts.forEach(function(a){
        setTimeout(function(){
            a.classList.remove('show');
            // remove after fade
            setTimeout(function(){ if (a.parentNode) a.parentNode.removeChild(a); }, 300);
        }, 4000);
    });
    // Edit modal behavior
    function showModal(){
        var modal = document.getElementById('expenseModal');
        if (modal) modal.style.display = 'block';
    }
    function hideModal(){
        var modal = document.getElementById('expenseModal');
        if (modal) modal.style.display = 'none';
    }

    // open edit modal, fetch data
    document.querySelectorAll('.edit-expense-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            var id = this.getAttribute('data-id');
            if (!id) return;
            fetch('handlers/expenses_fetch.php?id=' + encodeURIComponent(id))
                .then(function(resp){ return resp.json(); })
                .then(function(json){
                    if (!json.success) { alert(json.error || 'Failed to load'); return; }
                    var d = json.row;
                    // populate modal fields
                    document.getElementById('m_id').value = d.id || '';
                    document.getElementById('m_amount').value = d.amount || '';
                    document.getElementById('m_currency').value = d.currency || '';
                    document.getElementById('m_transaction_date').value = d.transaction_date || '';
                    document.getElementById('m_description').value = d.description || '';
                    document.getElementById('expense_category_modal').value = d.category || '';
                    // populate subcats for modal
                    var mapping = {
                        'Operating': ['Supplies','Rent','Licenses','Insurance'],
                        'Inventory': ['Fish Purchase','Feed','Chemicals','Packaging'],
                        'Payroll': ['Salaries','Benefits','Bonuses','Contractors'],
                        'Maintenance': ['Repairs','Equipment','Tools','Cleaning'],
                        'Utilities': ['Electricity','Water','Internet','Gas'],
                        'Marketing': ['Ads','Promotions','Events','Materials'],
                        'Misc': ['Other']
                    };
                    var cm = document.getElementById('expense_category_modal');
                    var sm = document.getElementById('expense_subcategory_modal');
                    sm.innerHTML = '<option value="">-- Select Subcategory --</option>';
                    if (d.category && mapping[d.category]) {
                        mapping[d.category].forEach(function(s){
                            var opt = document.createElement('option'); opt.value = s; opt.textContent = s;
                            if (s === d.subcategory) opt.selected = true;
                            sm.appendChild(opt);
                        });
                    }
                    document.getElementById('m_payment_method').value = d.payment_method || '';
                    document.getElementById('m_vendor').value = d.vendor || '';
                    document.getElementById('m_location').value = d.location || '';
                    document.getElementById('m_status').value = d.status || '';
                    document.getElementById('m_receipt_available').checked = d.receipt_available ? true : false;
                    document.getElementById('m_notes').value = d.notes || '';
                    showModal();
                }).catch(function(err){ alert('Failed to load: ' + err.message); });
        });
    });

    // close modal (header and footer buttons)
    var modalClose = document.getElementById('expenseModalClose');
    if (modalClose) modalClose.addEventListener('click', hideModal);
    var modalCloseFooter = document.getElementById('expenseModalCloseFooter');
    if (modalCloseFooter) modalCloseFooter.addEventListener('click', hideModal);

    // submit modal form via AJAX
    var modalForm = document.getElementById('expenseModalForm');
    if (modalForm) {
        modalForm.addEventListener('submit', function(e){
            e.preventDefault();
            var fd = new FormData(modalForm);
            fetch('handlers/expenses_update_ajax.php', { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(j){
                    if (j.success) { hideModal(); location.reload(); }
                    else alert(j.error || 'Update failed');
                }).catch(function(err){ alert('Update failed: ' + err.message); });
        });
    }
});
</script>
