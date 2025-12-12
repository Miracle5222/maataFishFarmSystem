<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

                <div class="layout-content">
                    <div class="container-fluid flex-grow-1 container-p-y">
                        <div class="row align-items-center mb-3">
                            <div class="col">
                                <h4 class="font-weight-bold py-3 mb-0">Products — Menu Items</h4>
                            </div>
                            <div class="col-auto">
                                <a href="products_menu_add.php" class="btn btn-sm btn-primary">
                                    <i class="feather icon-plus mr-2"></i> Add Menu Item
                                </a>
                            </div>
                        </div>
                        <div class="card">
                            <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td><strong>Deep-Fry Hito</strong></td>
                                        <td><span class="badge badge-light-primary">Food</span></td>
                                        <td>₱150.00</td>
                                        <td><span class="badge badge-success">100</span></td>
                                        <td>Crispy deep-fried catfish</td>
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
                                        <td><strong>Adobo Hito</strong></td>
                                        <td><span class="badge badge-light-primary">Food</span></td>
                                        <td>₱140.00</td>
                                        <td><span class="badge badge-success">100</span></td>
                                        <td>Traditional Filipino adobo</td>
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
                                        <td><strong>Sisig</strong></td>
                                        <td><span class="badge badge-light-primary">Food</span></td>
                                        <td>₱130.00</td>
                                        <td><span class="badge badge-success">90</span></td>
                                        <td>Sizzling hot sisig</td>
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
                                        <td><strong>French Fries</strong></td>
                                        <td><span class="badge badge-light-warning">Snack</span></td>
                                        <td>₱60.00</td>
                                        <td><span class="badge badge-success">150</span></td>
                                        <td>Golden crispy fries</td>
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
                                        <td>5</td>
                                        <td><strong>Softdrinks</strong></td>
                                        <td><span class="badge badge-light-info">Drink</span></td>
                                        <td>₱30.00</td>
                                        <td><span class="badge badge-success">200</span></td>
                                        <td>Various cold beverages</td>
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
