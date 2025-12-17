<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">

    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Orders — View Orders</h4>
        <div class="card mt-3">
            <div class="card-body">
                <p class="text-muted">This page lists current orders. The table below shows sample orders with product image, client, quantity and total amount.</p>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Order #</th>
                                <th>Client</th>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#1001</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="assets/img/avatars/1-small.png" alt="avatar" class="rounded-circle mr-2" width="36" height="36">
                                        <div>
                                            <div class="font-weight-bold">John Doe</div>
                                            <div class="text-muted small">john@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="assets/img/tilapia.jpg" alt="Tilapia" class="mr-2" style="width:56px;height:40px;object-fit:cover;border-radius:4px;">
                                        <div>
                                            <div class="font-weight-bold">Tilapia (Fresh)</div>
                                            <div class="text-muted small">Fish - 1 kg</div>
                                        </div>
                                    </div>
                                </td>
                                <td>2</td>
                                <td>₱400.00</td>
                                <td><span class="badge badge-light-success">Completed</span></td>
                                <td>2025-12-10</td>
                                <td class="text-right">
                                    <form method="POST" action="orders_update.php" class="form-inline d-inline-block">
                                        <input type="hidden" name="order_id" value="1001">
                                        <select name="status" class="form-control form-control-sm mr-1">
                                            <option value="Pending">Pending</option>
                                            <option value="Preparing">Preparing</option>
                                            <option value="Completed" selected>Completed</option>
                                            <option value="Cancelled">Cancelled</option>
                                            <option value="On Hold">On Hold</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                    <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="View"><i class="feather icon-eye"></i></a>
                                    <a href="#" class="btn btn-sm btn-icon btn-outline-secondary" title="Invoice"><i class="feather icon-file-text"></i></a>
                                </td>
                            </tr>
                            <tr>
                                <td>#1002</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="assets/img/avatars/2-small.png" alt="avatar" class="rounded-circle mr-2" width="36" height="36">
                                        <div>
                                            <div class="font-weight-bold">Mary Smith</div>
                                            <div class="text-muted small">mary@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="assets/img/catfish.jpg" alt="Catfish" class="mr-2" style="width:56px;height:40px;object-fit:cover;border-radius:4px;">
                                        <div>
                                            <div class="font-weight-bold">Deep-Fry Hito</div>
                                            <div class="text-muted small">Food - 1 order</div>
                                        </div>
                                    </div>
                                </td>
                                <td>3</td>
                                <td>₱450.00</td>
                                <td><span class="badge badge-light-warning">Preparing</span></td>
                                <td>2025-12-11</td>
                                <td class="text-right">
                                    <form method="POST" action="orders_update.php" class="form-inline d-inline-block">
                                        <input type="hidden" name="order_id" value="1002">
                                        <select name="status" class="form-control form-control-sm mr-1">
                                            <option value="Pending">Pending</option>
                                            <option value="Preparing" selected>Preparing</option>
                                            <option value="Completed">Completed</option>
                                            <option value="Cancelled">Cancelled</option>
                                            <option value="On Hold">On Hold</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                    <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="View"><i class="feather icon-eye"></i></a>
                                    <a href="#" class="btn btn-sm btn-icon btn-outline-danger" title="Cancel"><i class="feather icon-x"></i></a>
                                </td>
                            </tr>
                            <tr>
                                <td>#1003</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="assets/img/avatars/3-small.png" alt="avatar" class="rounded-circle mr-2" width="36" height="36">
                                        <div>
                                            <div class="font-weight-bold">Antonio Cruz</div>
                                            <div class="text-muted small">antonio@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="assets/img/Koi.jpg" alt="Koi" class="mr-2" style="width:56px;height:40px;object-fit:cover;border-radius:4px;">
                                        <div>
                                            <div class="font-weight-bold">Japanese Koi</div>
                                            <div class="text-muted small">Ornamental - 2 pcs</div>
                                        </div>
                                    </div>
                                </td>
                                <td>2</td>
                                <td>₱10,000.00</td>
                                <td><span class="badge badge-light-info">On Hold</span></td>
                                <td>2025-12-12</td>
                                <td class="text-right">
                                    <form method="POST" action="orders_update.php" class="form-inline d-inline-block">
                                        <input type="hidden" name="order_id" value="1003">
                                        <select name="status" class="form-control form-control-sm mr-1">
                                            <option value="Pending">Pending</option>
                                            <option value="Preparing">Preparing</option>
                                            <option value="Completed">Completed</option>
                                            <option value="Cancelled">Cancelled</option>
                                            <option value="On Hold" selected>On Hold</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                    <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="View"><i class="feather icon-eye"></i></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->

    <?php include 'partials/footer.php'; ?>