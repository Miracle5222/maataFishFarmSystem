<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

                <div class="layout-content">
                    <div class="container-fluid flex-grow-1 container-p-y">
                        <h4 class="font-weight-bold py-3 mb-0">Products — Manage Inventory</h4>
                        <div class="card mt-3">
                            <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Unit</th>
                                        <th>Reorder Level</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td><strong>Tilapia</strong></td>
                                        <td><span class="badge badge-light-success">Fish</span></td>
                                        <td><span class="badge badge-success">500 kg</span></td>
                                        <td>kg</td>
                                        <td>50 kg</td>
                                        <td><span class="badge badge-light-success">In Stock</span></td>
                                        <td>2024-12-10</td>
                                        <td class="text-right">
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="Update Stock">
                                                <i class="feather icon-edit-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td><strong>Deep-Fry Hito</strong></td>
                                        <td><span class="badge badge-light-primary">Food</span></td>
                                        <td><span class="badge badge-success">100 orders</span></td>
                                        <td>order</td>
                                        <td>20 orders</td>
                                        <td><span class="badge badge-light-success">In Stock</span></td>
                                        <td>2024-12-10</td>
                                        <td class="text-right">
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="Update Stock">
                                                <i class="feather icon-edit-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td><strong>French Fries</strong></td>
                                        <td><span class="badge badge-light-warning">Snack</span></td>
                                        <td><span class="badge badge-success">150 orders</span></td>
                                        <td>order</td>
                                        <td>25 orders</td>
                                        <td><span class="badge badge-light-success">In Stock</span></td>
                                        <td>2024-12-10</td>
                                        <td class="text-right">
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="Update Stock">
                                                <i class="feather icon-edit-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td><strong>Softdrinks</strong></td>
                                        <td><span class="badge badge-light-info">Drink</span></td>
                                        <td><span class="badge badge-success">200 orders</span></td>
                                        <td>order</td>
                                        <td>40 orders</td>
                                        <td><span class="badge badge-light-success">In Stock</span></td>
                                        <td>2024-12-10</td>
                                        <td class="text-right">
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="Update Stock">
                                                <i class="feather icon-edit-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td><strong>Catfish (Hito)</strong></td>
                                        <td><span class="badge badge-light-success">Fish</span></td>
                                        <td><span class="badge badge-warning">25 kg</span></td>
                                        <td>kg</td>
                                        <td>50 kg</td>
                                        <td><span class="badge badge-light-warning">Low Stock</span></td>
                                        <td>2024-12-09</td>
                                        <td class="text-right">
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="Update Stock">
                                                <i class="feather icon-edit-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">⚠️ Low Stock Alert</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Products below reorder level:</p>
                            <div class="list-group">
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-md">
                                            <strong>Catfish (Hito)</strong>
                                            <div class="text-muted small">Current: 25 kg | Reorder Level: 50 kg</div>
                                        </div>
                                        <div class="col-md-auto">
                                            <a href="#" class="btn btn-sm btn-primary">Reorder</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

<?php include 'partials/footer.php'; ?>
