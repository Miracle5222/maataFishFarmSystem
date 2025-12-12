<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

                <div class="layout-content">
                    <div class="container-fluid flex-grow-1 container-p-y">
                        <div class="row align-items-center mb-3">
                            <div class="col">
                                <h4 class="font-weight-bold py-3 mb-0">Products — Fish Species</h4>
                            </div>
                            <div class="col-auto">
                                <a href="products_fish_add.php" class="btn btn-sm btn-primary">
                                    <i class="feather icon-plus mr-2"></i> Add New Fish Species
                                </a>
                            </div>
                        </div>
                        <div class="card">
                            <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fish Name</th>
                                        <th>Local Name</th>
                                        <th>Price/kg</th>
                                        <th>Current Stock</th>
                                        <th>Harvest Schedule</th>
                                        <th>Status</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td><strong>Tilapia</strong></td>
                                        <td>Tilapia</td>
                                        <td>₱200.00</td>
                                        <td><span class="badge badge-success">500 kg</span></td>
                                        <td>Every 6 months</td>
                                        <td><span class="badge badge-light-success">Available</span></td>
                                        <td class="text-right">
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="Edit">
                                                <i class="feather icon-edit-2"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-danger" title="Delete">
                                                <i class="feather icon-trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td><strong>Catfish</strong></td>
                                        <td>Hito</td>
                                        <td>₱200.00</td>
                                        <td><span class="badge badge-success">300 kg</span></td>
                                        <td>Every 6 months</td>
                                        <td><span class="badge badge-light-success">Available</span></td>
                                        <td class="text-right">
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="Edit">
                                                <i class="feather icon-edit-2"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-danger" title="Delete">
                                                <i class="feather icon-trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td><strong>Japanese Koi</strong></td>
                                        <td>Japanese Koi</td>
                                        <td>₱200.00</td>
                                        <td><span class="badge badge-warning">100 kg</span></td>
                                        <td>Available</td>
                                        <td><span class="badge badge-light-success">Available</span></td>
                                        <td class="text-right">
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="Edit">
                                                <i class="feather icon-edit-2"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-danger" title="Delete">
                                                <i class="feather icon-trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td><strong>Fish Fry</strong></td>
                                        <td>Fingerlings</td>
                                        <td>₱50.00</td>
                                        <td><span class="badge badge-success">1000 pcs</span></td>
                                        <td>Available from nursery</td>
                                        <td><span class="badge badge-light-success">Available</span></td>
                                        <td class="text-right">
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-primary" title="Edit">
                                                <i class="feather icon-edit-2"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-icon btn-outline-danger" title="Delete">
                                                <i class="feather icon-trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

<?php include 'partials/footer.php'; ?>
