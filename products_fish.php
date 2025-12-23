<?php include 'auth_admin.php'; ?>
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
                <a href="products_add.php?category=fish" class="btn btn-sm btn-primary">
                    <i class="feather icon-plus mr-2"></i> Add New Fish Species
                </a>
            </div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <?php
                require __DIR__ . '/config/db.php';
                $fish = [];
                $stmt = $conn->prepare('SELECT fish_id, name, local_name, price_per_kg, stock, harvest_schedule, description, status, image FROM fish_species ORDER BY name ASC');
                if ($stmt) {
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($r = $res->fetch_assoc()) $fish[] = $r;
                    $stmt->close();
                }
                ?>
                <table id="fishTable" class="table table-sm table-bordered table-hover mb-0" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Fish Name</th>

                            <th>Price/kg</th>
                            <th>Current Stock</th>

                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fish as $f): ?>
                            <tr>
                                <td><?php echo (int)$f['fish_id']; ?></td>
                                <td><?php echo htmlspecialchars($f['name']); ?></td>

                                <td>₱<?php echo number_format($f['price_per_kg'], 2); ?></td>
                                <td><?php echo (int)$f['stock']; ?></td>

                                <td><?php echo htmlspecialchars(ucfirst($f['status'])); ?></td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-icon btn-outline-info view-fish"
                                        data-id="<?php echo (int)$f['fish_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($f['name']); ?>"
                                        data-local="<?php echo htmlspecialchars($f['local_name']); ?>"
                                        data-price="<?php echo number_format($f['price_per_kg'], 2, '.', ''); ?>"
                                        data-stock="<?php echo (int)$f['stock']; ?>"
                                        data-harvest="<?php echo htmlspecialchars($f['harvest_schedule']); ?>"
                                        data-desc="<?php echo htmlspecialchars($f['description']); ?>"
                                        data-status="<?php echo htmlspecialchars($f['status']); ?>"
                                        data-image="<?php echo htmlspecialchars($f['image'] ?? ''); ?>"
                                        title="View"><i class="feather icon-eye"></i></button>
                                    <button class="btn btn-sm btn-icon btn-outline-primary edit-fish"
                                        data-id="<?php echo (int)$f['fish_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($f['name']); ?>"
                                        data-local="<?php echo htmlspecialchars($f['local_name']); ?>"
                                        data-price="<?php echo number_format($f['price_per_kg'], 2, '.', ''); ?>"
                                        data-stock="<?php echo (int)$f['stock']; ?>"
                                        data-harvest="<?php echo htmlspecialchars($f['harvest_schedule']); ?>"
                                        data-desc="<?php echo htmlspecialchars($f['description']); ?>"
                                        data-status="<?php echo htmlspecialchars($f['status']); ?>"
                                        title="Edit"><i class="feather icon-edit-2"></i></button>
                                    <button class="btn btn-sm btn-icon btn-outline-danger delete-fish" data-id="<?php echo (int)$f['fish_id']; ?>" title="Delete"><i class="feather icon-trash-2"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <!-- Edit Fish Modal -->
    <div id="editFishModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Fish Species</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeEditFish()">&times;</button>
                </div>
                <form id="editFishForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="fish_id">
                        <div class="form-group">
                            <label>Name</label>
                            <input class="form-control" name="name" id="fish_name" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Price/kg</label>
                                <input class="form-control" name="price_per_kg" id="fish_price" type="number" step="0.01" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Stock</label>
                                <input class="form-control" name="stock" id="fish_stock" type="number" min="0" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Status</label>
                                <select class="form-control" name="status" id="fish_status">
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" id="fish_description"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Image (leave empty to keep current)</label>
                            <input type="file" class="form-control-file" name="image" id="fish_image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditFish()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Fish Modal (placed outside the edit form) -->
    <div id="viewFishModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewFishTitle">Fish Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeViewFish()">&times;</button>
                </div>
                <div class="modal-body">
                    <div style="display:flex; gap:16px; align-items:flex-start;">
                        <div style="flex:0 0 320px;">
                            <img id="viewFishImage" src="assets/img/fish-placeholder.png" alt="Fish image" style="width:100%; height:auto; border-radius:6px; object-fit:cover;">
                        </div>
                        <div style="flex:1;">
                            <dl>
                                <dt>Name</dt>
                                <dd id="viewFishName"></dd>
                                <dt>Local Name</dt>
                                <dd id="viewFishLocal"></dd>
                                <dt>Price / kg</dt>
                                <dd id="viewFishPrice"></dd>
                                <dt>Stock</dt>
                                <dd id="viewFishStock"></dd>
                                <dt>Harvest Schedule</dt>
                                <dd id="viewFishHarvest"></dd>
                                <dt>Status</dt>
                                <dd id="viewFishStatus"></dd>
                                <dt>Description</dt>
                                <dd id="viewFishDesc"></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeViewFish()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(function() {
            var tbl = $('#fishTable').DataTable({
                order: [
                    [0, 'desc']
                ]
            });

            $('#fishTable').on('click', '.delete-fish', function() {
                var id = $(this).data('id');
                if (!confirm('Delete fish #' + id + '?')) return;
                $.post('handlers/fish_delete.php', {
                    id: id
                }, function(resp) {
                    try {
                        var j = typeof resp === 'string' ? JSON.parse(resp) : resp;
                        if (j.ok) location.reload();
                        else alert(j.msg || 'Delete failed');
                    } catch (e) {
                        alert('Delete failed');
                    }
                });
            });

            $('#fishTable').on('click', '.edit-fish', function() {
                var b = $(this);
                $('#fish_id').val(b.data('id'));
                $('#fish_name').val(b.data('name'));
                $('#fish_price').val(b.data('price'));
                $('#fish_stock').val(b.data('stock'));
                $('#fish_description').val(b.data('desc'));
                $('#fish_status').val(b.data('status'));
                // clear any selected file
                $('#fish_image').val('');
                openEditFish();
            });

            // View
            $('#fishTable').on('click', '.view-fish', function() {
                var b = $(this);
                var imageFile = b.data('image');
                var imgSrc = imageFile ? ('assets/img/fish_species/' + imageFile) : 'assets/img/fish-placeholder.png';
                $('#viewFishImage').attr('src', imgSrc).attr('alt', b.data('name'));
                $('#viewFishTitle').text(b.data('name'));
                $('#viewFishName').text(b.data('name'));
                $('#viewFishLocal').text(b.data('local') || '-');
                $('#viewFishPrice').text('₱' + b.data('price'));
                $('#viewFishStock').text(b.data('stock'));
                $('#viewFishHarvest').text(b.data('harvest') || '-');
                $('#viewFishStatus').text(b.data('status'));
                $('#viewFishDesc').text(b.data('desc') || '-');
                openViewFish();
            });

            $('#editFishForm').on('submit', function(e) {
                e.preventDefault();
                var form = document.getElementById('editFishForm');
                var fd = new FormData(form);
                $.ajax({
                    url: 'handlers/fish_update.php',
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function(resp) {
                        try {
                            var j = typeof resp === 'string' ? JSON.parse(resp) : resp;
                            if (j.ok) location.reload();
                            else alert(j.msg || 'Update failed');
                        } catch (e) {
                            alert('Update failed');
                        }
                    },
                    error: function() {
                        alert('Update failed');
                    }
                });
            });
        });

        function openEditFish() {
            $('#editFishModal').show();
        }

        function closeEditFish() {
            $('#editFishModal').hide();
        }

        function openViewFish() {
            $('#viewFishModal').show();
        }

        function closeViewFish() {
            $('#viewFishModal').hide();
        }
    </script>