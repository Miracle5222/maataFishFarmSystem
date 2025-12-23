<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<div class="layout-content">
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="row align-items-center mb-3">
            <div class="col">
                <h4 class="font-weight-bold py-3 mb-0">Customers — Edit Customer Profile</h4>
            </div>
        </div>

        <?php
        require __DIR__ . '/config/db.php';
        
        $customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $customer = null;
        
        if ($customer_id > 0) {
            $stmt = $conn->prepare('SELECT * FROM customers WHERE id = ?');
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $customer = $res->fetch_assoc();
            $stmt->close();
        }
        
        if (!$customer) {
            echo '<div class="alert alert-danger">Customer not found.</div>';
            include 'partials/footer.php';
            exit;
        }
        ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="handlers/account_settings_handler.php" style="max-width: 600px;">
                    <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">

                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="barangay">Barangay</label>
                        <input type="text" class="form-control" id="barangay" name="barangay" value="<?php echo htmlspecialchars($customer['barangay']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="municipality">Municipality</label>
                        <input type="text" class="form-control" id="municipality" name="municipality" value="<?php echo htmlspecialchars($customer['municipality']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="customer_type">Customer Type</label>
                        <select class="form-control" id="customer_type" name="customer_type">
                            <option value="online_customer" <?php echo ($customer['customer_type'] === 'online_customer') ? 'selected' : ''; ?>>Online Customer (Orders Fish)</option>
                            <option value="diner" <?php echo ($customer['customer_type'] === 'diner') ? 'selected' : ''; ?>>Diner (Farm Reservation)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="customers_list.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>