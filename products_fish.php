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
                $colRes = $conn->query("SHOW COLUMNS FROM fish_species LIKE 'last_stock_out_reason'");
                if ($colRes && $colRes->num_rows === 0) {
                    $conn->query("ALTER TABLE fish_species ADD COLUMN last_stock_out_reason TEXT NULL");
                }
                $stmt = $conn->prepare('SELECT fish_id, name, local_name, price_per_kg, stock, harvest_schedule, description, status, image, last_stock_out_reason FROM fish_species ORDER BY name ASC');
                if ($stmt) {
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($r = $res->fetch_assoc()) $fish[] = $r;
                    $stmt->close();
                }
                ?>
                <div class="d-flex justify-content-end mb-3 m-4">
                    <button id="stockInBtn" type="button" class="btn btn-sm btn-success mr-2"><i class="feather icon-plus mr-1"></i> Stock-In</button>
                    <button id="stockOutBtn" type="button" class="btn btn-sm btn-warning"><i class="feather icon-minus mr-1"></i> Stock-Out</button>
                </div>
                <table id="fishTable" class="table table-sm table-bordered table-hover mb-0" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Fish Name</th>

                            <th>Price/kg</th>
                            <th>Current Stock</th>

                            <th>Status</th>
                            <th>Description</th>
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
                                <td style="max-width:250px; font-size:11px; white-space:normal; overflow-wrap:break-word; word-wrap:break-word; background:#f9f9f9; color:#333;">
                                    <?php echo var_export($f['description'], true); ?>
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-icon btn-outline-info view-fish"
                                        data-id="<?php echo (int)$f['fish_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($f['name']); ?>"
                                        data-local="<?php echo htmlspecialchars($f['local_name']); ?>"
                                        data-price="<?php echo number_format($f['price_per_kg'], 2, '.', ''); ?>"
                                        data-stock="<?php echo (int)$f['stock']; ?>"
                                        data-harvest="<?php echo htmlspecialchars($f['harvest_schedule']); ?>"
                                        data-desc='<?php echo htmlspecialchars(json_encode(($f["description"] === "0" || $f["description"] === 0) ? "" : $f["description"]), ENT_QUOTES, "UTF-8"); ?>'
                                        data-status="<?php echo htmlspecialchars($f['status']); ?>"
                                        data-stock-reason='<?php echo htmlspecialchars(json_encode($f['last_stock_out_reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'
                                        data-image="<?php echo htmlspecialchars($f['image'] ?? ''); ?>"
                                        title="View"><i class="feather icon-eye"></i></button>
                                    <button class="btn btn-sm btn-icon btn-outline-primary edit-fish"
                                        data-id="<?php echo (int)$f['fish_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($f['name']); ?>"
                                        data-local="<?php echo htmlspecialchars($f['local_name']); ?>"
                                        data-price="<?php echo number_format($f['price_per_kg'], 2, '.', ''); ?>"
                                        data-stock="<?php echo (int)$f['stock']; ?>"
                                        data-harvest="<?php echo htmlspecialchars($f['harvest_schedule']); ?>"
                                        data-desc='<?php echo htmlspecialchars(json_encode(($f["description"] === "0" || $f["description"] === 0) ? "" : $f["description"]), ENT_QUOTES, "UTF-8"); ?>'
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

                        <div class="form-group">
                            <label>Local Name</label>
                            <input class="form-control" name="local_name" id="fish_local">
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
                            <label>Harvest Schedule</label>
                            <input class="form-control" name="harvest_schedule" id="fish_harvest">
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
                    <div style="display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
                        <div style="flex:0 0 320px;">
                            <img id="viewFishImage" src="assets/img/fish-placeholder.png" alt="Fish image" style="width:100%; height:auto; border-radius:6px; object-fit:cover;">
                        </div>
                        <div style="flex:1; min-width:320px;">
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
                                <dt id="viewFishReasonLabel" style="display:none;">Stock-Out Reason</dt>
                                <dd id="viewFishReason" style="display:none; white-space:pre-wrap;"></dd>
                            </dl>
                        </div>
                    </div>
                    <div id="viewFishStockOutHistory" style="margin-top:24px; display:none;">
                        <h6 class="mb-2">Stock-Out History</h6>
                        <div class="table-responsive" style="max-height:280px; overflow-y:auto; display:block;">
                            <table class="table table-sm table-bordered mb-0" id="stockOutHistoryTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="min-width:160px;">Date / Time</th>
                                        <th style="min-width:70px;">Qty</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div id="stockOutHistoryEmpty" class="text-muted mt-2" style="display:none;">No stock-out history found.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeViewFish()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="stockModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockModalTitle">Stock Adjustment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeStockModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="stockFishSelect"><strong>Select Fish Species</strong></label>
                        <select id="stockFishSelect" class="form-control"></select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Current Stock</strong></label>
                            <input id="stockCurrent" class="form-control" type="text" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="stockQuantity"><strong>Quantity</strong></label>
                            <input id="stockQuantity" class="form-control" type="number" min="1" value="1">
                        </div>
                    </div>
                    <div class="form-group" id="stockReasonGroup" style="display:none;">
                        <label for="stockReason"><strong>Reason</strong></label>
                        <textarea id="stockReason" class="form-control" rows="3" placeholder="Enter stock-out reason"></textarea>
                    </div>
                    <div id="stockModalError" class="text-danger" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeStockModal()">Cancel</button>
                    <button type="button" id="stockModalSubmit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        var fishData = <?php echo json_encode(array_column($fish, null, 'fish_id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        var stockModalType = '';
        $(function() {
            var tbl = $('#fishTable').DataTable({
                order: [
                    [0, 'desc']
                ],
                dom: 'lfrtip'
            });

            $('#stockInBtn').on('click', function() {
                stockModalType = 'in';
                $('#stockModalTitle').text('Stock-In Fish');
                $('#stockModalSubmit').text('Stock In');
                openStockModal('in');
            });

            $('#stockOutBtn').on('click', function() {
                stockModalType = 'out';
                $('#stockModalTitle').text('Stock-Out Fish');
                $('#stockModalSubmit').text('Stock Out');
                openStockModal('out');
            });

            $('#stockFishSelect').on('change', updateStockCurrent);

            $('#stockModalSubmit').on('click', function() {
                var fishId = parseInt($('#stockFishSelect').val(), 10);
                var qty = parseInt($('#stockQuantity').val(), 10);
                var reason = $('#stockReason').val().trim();
                var errorEl = $('#stockModalError');

                if (!fishId || !fishData[fishId]) {
                    errorEl.text('Please select a fish species.').show();
                    return;
                }
                if (!qty || qty < 1) {
                    errorEl.text('Enter a valid quantity.').show();
                    return;
                }
                if (stockModalType === 'out' && !reason) {
                    errorEl.text('Reason is required for stock-out.').show();
                    return;
                }

                var fish = fishData[fishId];
                var currentStock = parseInt(fish.stock, 10) || 0;
                var newStock = stockModalType === 'out' ? currentStock - qty : currentStock + qty;
                if (newStock < 0) {
                    errorEl.text('Stock cannot go below zero.').show();
                    return;
                }

                errorEl.hide();
                var fd = new FormData();
                fd.append('id', fishId);
                fd.append('name', fish.name);
                fd.append('local_name', fish.local_name || '');
                fd.append('price_per_kg', fish.price_per_kg || 0);
                fd.append('stock', newStock);
                fd.append('harvest_schedule', fish.harvest_schedule || '');
                fd.append('description', fish.description || '');
                fd.append('status', fish.status || 'available');
                fd.append('stock_action', stockModalType);
                fd.append('stock_reason', reason);

                $.ajax({
                    url: 'handlers/fish_update.php',
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
                                errorEl.text(j.msg || 'Failed to update stock.').show();
                            }
                        } catch (e) {
                            errorEl.text('Failed to update stock.').show();
                        }
                    },
                    error: function() {
                        errorEl.text('Failed to update stock.').show();
                    }
                });
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
                $('#fish_local').val(b.data('local'));
                $('#fish_price').val(b.data('price'));
                $('#fish_stock').val(b.data('stock'));
                $('#fish_harvest').val(b.data('harvest'));
                var desc = b.data('desc');
                try { desc = desc ? JSON.parse(desc) : ''; } catch (e) { desc = desc || ''; }
                $('#fish_description').val(desc);
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
                var stockReason = b.attr('data-stock-reason') || '';
                try {
                    stockReason = stockReason ? JSON.parse(stockReason) : '';
                } catch (e) {
                    stockReason = stockReason || '';
                }
                if (stockReason) {
                    $('#viewFishReasonLabel').show();
                    $('#viewFishReason').show().text(stockReason);
                } else {
                    $('#viewFishReasonLabel').hide();
                    $('#viewFishReason').hide().text('');
                }
                loadStockOutHistory(b.data('id'));
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

        function openStockModal(type) {
            stockModalType = type || 'in';
            var select = $('#stockFishSelect');
            select.empty();
            $.each(Object.values(fishData), function(index, fish) {
                select.append($('<option>').val(fish.fish_id).text(fish.name));
            });
            $('#stockQuantity').val('1');
            $('#stockReason').val('');
            $('#stockModalError').hide();
            if (stockModalType === 'out') {
                $('#stockReasonGroup').show();
            } else {
                $('#stockReasonGroup').hide();
            }
            updateStockCurrent();
            $('#stockModal').show();
        }

        function closeStockModal() {
            $('#stockModal').hide();
        }

        function updateStockCurrent() {
            var fishId = parseInt($('#stockFishSelect').val(), 10);
            if (fishId && fishData[fishId]) {
                $('#stockCurrent').val(fishData[fishId].stock);
            } else {
                $('#stockCurrent').val('0');
            }
        }

        function loadStockOutHistory(fishId) {
            $('#stockOutHistoryTable tbody').empty();
            $('#viewFishStockOutHistory').hide();
            $('#stockOutHistoryEmpty').hide();
            if (!fishId) {
                $('#stockOutHistoryEmpty').show();
                return;
            }
            $.getJSON('handlers/fish_stock_out_logs.php', { fish_id: fishId }, function(resp) {
                if (!resp.ok || !Array.isArray(resp.rows) || resp.rows.length === 0) {
                    $('#stockOutHistoryEmpty').show();
                    return;
                }
                resp.rows.forEach(function(row) {
                    var tr = $('<tr>');
                    tr.append($('<td>').text(row.created_at));
                    tr.append($('<td>').text(row.quantity));
                    tr.append($('<td>').text(row.reason));
                    $('#stockOutHistoryTable tbody').append(tr);
                });
                $('#viewFishStockOutHistory').show();
            }).fail(function() {
                $('#stockOutHistoryEmpty').show();
            });
        }
    </script>