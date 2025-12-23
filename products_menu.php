<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

                <div class="layout-content">
                    <div class="container-fluid flex-grow-1 container-p-y">
                        <div class="row align-items-center mb-3">
                            <div class="col">
                                <h4 class="font-weight-bold py-3 mb-0">Menu — Food Items</h4>
                            </div>
                            <div class="col-auto">
                                <a href="products_add.php?category=food" class="btn btn-sm btn-primary">
                                    <i class="feather icon-plus mr-2"></i> Add Menu Item
                                </a>
                            </div>
                        </div>
                        <div class="card">
                            <div class="table-responsive">
                            <table id="menuTable" class="table table-sm mb-0">
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
                                    <?php
                                    require __DIR__ . '/config/db.php';
                                    $menu = [];
                                    // Fetch menu items: food, snack, drink (excluding fish)
                                    $stmt = $conn->prepare('SELECT id, name, category, description, price, unit, stock_quantity, status, image FROM products WHERE category IN ("food", "snack", "drink") ORDER BY category ASC, name ASC');
                                    if ($stmt) {
                                        $stmt->execute();
                                        $res = $stmt->get_result();
                                        while ($r = $res->fetch_assoc()) $menu[] = $r;
                                        $stmt->close();
                                    }
                                    
                                    if (empty($menu)) {
                                        echo '<tr><td colspan="8" class="text-center text-muted py-4">No menu items found. <a href="products_add.php?category=food">Add one now</a></td></tr>';
                                    } else {
                                        foreach ($menu as $m):
                                    ?>
                                    <tr>
                                        <td><?php echo (int)$m['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                                        <td><span class="badge badge-light-primary"><?php echo htmlspecialchars(ucfirst($m['category'])); ?></span></td>
                                        <td>₱<?php echo number_format($m['price'], 2); ?></td>
                                        <td><span class="badge badge-success"><?php echo (int)$m['stock_quantity']; ?></span></td>
                                        <td><?php echo htmlspecialchars(substr($m['description'] ?? '', 0, 40)); ?></td>
                                        <td><span class="badge badge-light-<?php echo ($m['status'] === 'available') ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars(ucfirst($m['status'])); ?></span></td>
                                        <td class="text-right">
                                            <button class="btn btn-sm btn-icon btn-outline-info view-menu" 
                                                data-id="<?php echo (int)$m['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($m['name']); ?>"
                                                data-category="<?php echo htmlspecialchars($m['category']); ?>"
                                                data-price="<?php echo number_format($m['price'], 2, '.', ''); ?>"
                                                data-stock="<?php echo (int)$m['stock_quantity']; ?>"
                                                data-desc="<?php echo htmlspecialchars($m['description']); ?>"
                                                data-status="<?php echo htmlspecialchars($m['status']); ?>"
                                                data-image="<?php echo htmlspecialchars($m['image'] ?? ''); ?>"
                                                title="View"><i class="feather icon-eye"></i></button>
                                            <button class="btn btn-sm btn-icon btn-outline-primary edit-menu" 
                                                data-id="<?php echo (int)$m['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($m['name']); ?>"
                                                data-category="<?php echo htmlspecialchars($m['category']); ?>"
                                                data-price="<?php echo number_format($m['price'], 2, '.', ''); ?>"
                                                data-stock="<?php echo (int)$m['stock_quantity']; ?>"
                                                data-desc="<?php echo htmlspecialchars($m['description']); ?>"
                                                data-status="<?php echo htmlspecialchars($m['status']); ?>"
                                                title="Edit"><i class="feather icon-edit-2"></i></button>
                                            <button class="btn btn-sm btn-icon btn-outline-danger delete-menu" data-id="<?php echo (int)$m['id']; ?>" title="Delete"><i class="feather icon-trash-2"></i></button>
                                        </td>
                                    </tr>
                                    <?php 
                                        endforeach;
                                    }
                                    ?>
                                </tbody>
                           
                            </table>
                            </div>
                        </div>
                    </div>
                </div>

<?php include 'partials/footer.php'; ?>

<!-- View Menu Modal -->
<!-- Edit Menu Modal -->
<div id="editMenuModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Menu Item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeEditMenu()">&times;</button>
            </div>
            <form id="editMenuForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_menu_id">
                    
                    <div class="form-group">
                        <label for="edit_menu_name">Item Name *</label>
                        <input type="text" id="edit_menu_name" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_menu_category">Category *</label>
                        <select id="edit_menu_category" name="category" class="form-control" required>
                            <option value="food">Food</option>
                            <option value="snack">Snack</option>
                            <option value="drink">Drink</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_menu_price">Price *</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">₱</span>
                            </div>
                            <input type="number" id="edit_menu_price" name="price" class="form-control" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_menu_stock">Stock Quantity *</label>
                        <input type="number" id="edit_menu_stock" name="stock_quantity" class="form-control" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_menu_status">Status</label>
                        <select id="edit_menu_status" name="status" class="form-control">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_menu_desc">Description</label>
                        <textarea id="edit_menu_desc" name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="edit_menu_image">Image</label>
                        <input type="file" id="edit_menu_image" name="image" class="form-control-file" accept="image/*">
                        <small class="form-text text-muted">Upload a new image (JPG, PNG). Max 5MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditMenu()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Menu Modal -->
<div id="viewMenuModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewMenuTitle">Menu Item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeViewMenu()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display:flex; gap:20px;">
                    <div style="flex:0 0 200px;">
                        <img id="viewMenuImage" src="assets/img/products/placeholder.png" alt="Menu image" style="width:100%; height:auto; border-radius:6px; object-fit:cover;">
                    </div>
                    <div style="flex:1;">
                        <dl>
                            <dt>Name</dt>
                            <dd id="viewMenuName"></dd>
                            <dt>Category</dt>
                            <dd id="viewMenuCategory"></dd>
                            <dt>Price</dt>
                            <dd id="viewMenuPrice"></dd>
                            <dt>Stock</dt>
                            <dd id="viewMenuStock"></dd>
                            <dt>Status</dt>
                            <dd id="viewMenuStatus"></dd>
                            <dt>Description</dt>
                            <dd id="viewMenuDesc"></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewMenu()">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(function() {
    // Initialize DataTable
    var table = $('#menuTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 10,
        columnDefs: [{
            orderable: false,
            targets: 7 // Actions column
        }]
    });

    $('#menuTable').on('click', '.view-menu', function() {
        var b = $(this);
        var imageFile = b.data('image');
        var imgSrc = imageFile ? ('assets/img/products/' + imageFile) : 'assets/img/products/placeholder.png';
        $('#viewMenuImage').attr('src', imgSrc).attr('alt', b.data('name'));
        $('#viewMenuTitle').text(b.data('name'));
        $('#viewMenuName').text(b.data('name'));
        $('#viewMenuCategory').text(b.data('category'));
        $('#viewMenuPrice').text('₱' + b.data('price'));
        $('#viewMenuStock').text(b.data('stock'));
        $('#viewMenuStatus').text(b.data('status'));
        $('#viewMenuDesc').text(b.data('desc') || '-');
        openViewMenu();
    });

    $('#menuTable').on('click', '.edit-menu', function() {
        var b = $(this);
        $('#edit_menu_id').val(b.data('id'));
        $('#edit_menu_name').val(b.data('name'));
        $('#edit_menu_category').val(b.data('category'));
        $('#edit_menu_price').val(b.data('price'));
        $('#edit_menu_stock').val(b.data('stock'));
        $('#edit_menu_status').val(b.data('status'));
        $('#edit_menu_desc').val(b.data('desc') || '');
        $('#edit_menu_image').val('');
        openEditMenu();
    });

    $('#editMenuForm').on('submit', function(e) {
        e.preventDefault();
        var form = document.getElementById('editMenuForm');
        var fd = new FormData(form);
        $.ajax({
            url: 'handlers/product_update.php',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(resp) {
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
            },
            error: function() {
                alert('Update failed');
            }
    });

    });

    $('#menuTable').on('click', '.delete-menu', function() {
        var id = $(this).data('id');
        if (!confirm('Delete menu item #' + id + '?')) return;
        $.post('handlers/product_delete.php', { id: id }, function(resp) {
            try {
                var j = typeof resp === 'string' ? JSON.parse(resp) : resp;
                if (j.ok) location.reload();
                else alert(j.msg || 'Delete failed');
            } catch (e) {
                alert('Delete failed');
            }
        });
    });
});

function openViewMenu() {
    $('#viewMenuModal').show();
}

function closeViewMenu() {
    $('#viewMenuModal').hide();
}

function openEditMenu() {
    $('#editMenuModal').show();
}

function closeEditMenu() {
    $('#editMenuModal').hide();
}
</script>
