<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<div class="layout-content">
    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="row align-items-center mb-3">
            <div class="col">
                <h4 class="font-weight-bold py-3 mb-0">Products — All Products</h4>
            </div>
            <div class="col-auto">
                <a href="products_add.php" class="btn btn-sm btn-primary">
                    <i class="feather icon-plus mr-2"></i> Add Product
                </a>
            </div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <?php
                // load products from DB
                require __DIR__ . '/config/db.php';
                $products = [];
                $stmt = $conn->prepare("SELECT id, name, category, description, price, unit, stock_quantity, status FROM products ORDER BY id DESC");
                if ($stmt) {
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($r = $res->fetch_assoc()) $products[] = $r;
                    $stmt->close();
                }
                ?>
                <table id="productsTable" class="table table-sm table-bordered table-hover mb-0" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Unit</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?php echo (int)$p['id']; ?></td>
                                <td><?php echo htmlspecialchars($p['name']); ?></td>
                                <td><?php echo htmlspecialchars($p['category']); ?></td>
                                <td>₱<?php echo number_format($p['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($p['unit']); ?></td>
                                <td><?php echo (int)$p['stock_quantity']; ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($p['status'])); ?></td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-icon btn-outline-primary edit-product"
                                        data-id="<?php echo (int)$p['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($p['name']); ?>"
                                        data-category="<?php echo htmlspecialchars($p['category']); ?>"
                                        data-description="<?php echo htmlspecialchars($p['description']); ?>"
                                        data-price="<?php echo number_format($p['price'], 2, '.', ''); ?>"
                                        data-unit="<?php echo htmlspecialchars($p['unit']); ?>"
                                        data-stock="<?php echo (int)$p['stock_quantity']; ?>"
                                        data-status="<?php echo htmlspecialchars($p['status']); ?>"
                                        title="Edit">
                                        <i class="feather icon-edit-2"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon btn-outline-danger delete-product" data-id="<?php echo (int)$p['id']; ?>" title="Delete">
                                        <i class="feather icon-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <!-- Edit modal -->
    <div id="editProductModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeEditModal()">&times;</button>
                </div>
                <form id="editProductForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="form-group">
                            <label>Name</label>
                            <input class="form-control" name="product_name" id="edit_name" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input class="form-control" name="category" id="edit_category" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" id="edit_description"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Price</label>
                                <input class="form-control" name="price" id="edit_price" type="number" step="0.01" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Unit</label>
                                <input class="form-control" name="unit" id="edit_unit" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Stock</label>
                                <input class="form-control" name="stock_quantity" id="edit_stock" type="number" min="0" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="status" id="edit_status">
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#productsTable').DataTable({
                order: [
                    [0, 'desc']
                ]
            });

            // Delete
            $('#productsTable').on('click', '.delete-product', function() {
                var id = $(this).data('id');
                if (!confirm('Delete product #' + id + '? This cannot be undone.')) return;
                $.post('handlers/product_delete.php', {
                    id: id
                }, function(resp) {
                    try {
                        var j = typeof resp === 'string' ? JSON.parse(resp) : resp;
                        if (j.ok) {
                            location.reload();
                        } else {
                            alert(j.msg || 'Delete failed');
                        }
                    } catch (e) {
                        alert('Delete failed');
                    }
                });
            });

            // Edit open
            $('#productsTable').on('click', '.edit-product', function() {
                var btn = $(this);
                $('#edit_id').val(btn.data('id'));
                $('#edit_name').val(btn.data('name'));
                $('#edit_category').val(btn.data('category'));
                $('#edit_description').val(btn.data('description'));
                $('#edit_price').val(btn.data('price'));
                $('#edit_unit').val(btn.data('unit'));
                $('#edit_stock').val(btn.data('stock'));
                $('#edit_status').val(btn.data('status'));
                openEditModal();
            });

            // submit edit
            $('#editProductForm').on('submit', function(e) {
                e.preventDefault();
                var data = $(this).serialize();
                $.post('handlers/product_update.php', data, function(resp) {
                    try {
                        var j = typeof resp === 'string' ? JSON.parse(resp) : resp;
                        if (j.ok) {
                            location.reload();
                        } else {
                            alert(j.msg || 'Update failed');
                        }
                    } catch (e) {
                        alert('Update failed');
                    }
                });
            });
        });

        function openEditModal() {
            $('#editProductModal').show();
        }

        function closeEditModal() {
            $('#editProductModal').hide();
        }
    </script>