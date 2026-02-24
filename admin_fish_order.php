<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Orders — Fish Order</h4>

        <div class="row mt-4">
            <!-- Fish Items -->
            <div class="col-lg-8">
                <!-- Fish Section -->
                <div class="card mb-3">
                    <div class="card-header" style="background-color: #27ae60; color: white;">
                        <h6 class="mb-0">🐟 Available Fish</h6>
                    </div>
                    <div class="card-body">
                        <div id="fishItemsContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;">
                            <!-- Fish items will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card" style="position: sticky; top: 20px;">
                    <div class="card-header" style="background-color: #27ae60; color: white;">
                        <h6 class="mb-0">Order Summary</h6>
                    </div>
                    <div class="card-body">
                        <div id="orderSummaryItems" style="max-height: 280px; overflow-y: auto; margin-bottom: 16px; border-bottom: 1px solid #ddd; padding-bottom: 12px;">
                            <p class="text-muted text-center py-3">No items added yet</p>
                        </div>

                        <div class="alert alert-light" role="alert">
                            <div class="d-flex justify-content-between">
                                <strong>Subtotal:</strong>
                                <span id="orderTotal" style="font-size: 18px; color: #27ae60; font-weight: 700;">₱0.00</span>
                            </div>
                        </div>

                        <button id="proceedCheckoutBtn" class="btn btn-primary btn-block mb-2" style="display: none;">Proceed to Checkout</button>
                        <p id="emptyCartMsg" class="text-muted text-center mb-0">Add items to proceed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout Section -->
        <div id="checkoutSection" style="display: none;" class="mt-4">
            <div class="card">
                <div class="card-header" style="background-color: #27ae60; color: white;">
                    <h6 class="mb-0">Checkout Details</h6>
                </div>
                <div class="card-body">
                    <form id="fishOrderForm" method="POST" action="handlers/admin_fish_order.php">
                        <div class="row">
                            <!-- Order Form -->
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label class="form-label">Customer Name (Optional)</label>
                                    <input type="text" name="customer_name" class="form-control" placeholder="Direct Order">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Customer Contact (Optional)</label>
                                    <input type="text" name="customer_contact" class="form-control" placeholder="Phone/Email">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Order Notes (Optional)</label>
                                    <textarea name="order_notes" class="form-control" rows="3"></textarea>
                                </div>

                                <input type="hidden" name="order_items" id="orderItemsPayload">
                            </div>

                            <!-- Order Review -->
                            <div class="col-lg-4">
                                <div class="alert alert-light" role="alert">
                                    <h6 class="mb-3">Order Review</h6>
                                    <div id="reviewItems" style="max-height: 250px; overflow-y: auto; margin-bottom: 12px; border-bottom: 1px solid #ddd; padding-bottom: 12px;"></div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <strong>Total:</strong>
                                        <span id="reviewTotal" style="font-size: 18px; font-weight: 700; color: #27ae60;">₱0.00</span>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-block mb-2">Place Order Now</button>
                                    <button type="button" id="cancelCheckoutBtn" class="btn btn-light btn-block">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ content ] End -->
</div>
<!-- [ Layout content ] End -->

<?php include 'partials/footer.php'; ?>

<script>
    var fishOrder = {
        items: [],
        total: 0
    };

    // Load items on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadFishItems();
    });

    function loadFishItems() {
        fetch('handlers/admin_fish_items.php', { credentials: 'include' })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                console.log('Fish items response:', data);
                if (data.success) {
                    renderFishItems(data.items);
                } else {
                    var errorMsg = data.error || 'Unknown error';
                    console.error('API error:', data);
                    document.getElementById('fishItemsContainer').innerHTML = '<p class="text-danger">Error loading items: ' + errorMsg + '</p>';
                }
            })
            .catch(function(e) { 
                console.error('Error loading items:', e);
                document.getElementById('fishItemsContainer').innerHTML = '<p class="text-danger">Error loading items: ' + e.message + '</p>';
            });
    }

    function renderFishItems(items) {
        var container = document.getElementById('fishItemsContainer');
        if (!items || items.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">No fish available</p>';
            return;
        }

        container.innerHTML = items.map(function(fish) {
            var stock = parseInt(fish.stock) || 0;
            var outOfStock = stock <= 0;
            var maxAttr = outOfStock ? 0 : stock;
            var weightInput = '<input type="number" id="qty_' + fish.id + '" min="0.1" step="0.1" value="1" max="' + maxAttr + '" ' + (outOfStock ? 'disabled' : '') + ' style="width: 70px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;" placeholder="kg" title="Enter weight in kg or g">';
            var addButtonClass = outOfStock ? 'btn btn-sm btn-secondary flex-grow-1' : 'btn btn-sm btn-primary flex-grow-1';
            var addButtonAttrs = outOfStock ? 'disabled' : 'onclick="addFishToOrder(' + fish.id + ', \'' + escapeHtml(fish.name) + '\', ' + parseFloat(fish.price) + ')"';
            var outLabel = outOfStock ? '<span style="display:inline-block;padding:4px 8px;border-radius:12px;background:#ffe6e6;color:#c0392b;font-size:12px;margin-left:8px;">Out of stock</span>' : '';

            return '<div style="border: 1px solid #eee; border-radius: 6px; overflow: hidden;">' +
                    '<img src="assets/img/fish_species/' + (fish.image || 'placeholder.png') + '" alt="' + escapeHtml(fish.name) + '" style="width: 100%; height: 100px; object-fit: cover; background: #f0f0f0; border-bottom: 1px solid #eee;">' +
                    '<div style="padding: 10px;">' +
                        '<div style="display:flex; align-items:center; justify-content:space-between;">' +
                            '<h6 class="mb-1" style="font-size: 13px; margin: 0;">' + escapeHtml(fish.name) + '</h6>' +
                            outLabel +
                        '</div>' +
                        '<p class="text-success mb-2" style="font-weight: 700; font-size: 14px;">₱' + parseFloat(fish.price).toFixed(2) + '/kg</p>' +
                        '<div style="display: flex; flex-direction: column; gap: 8px;">' +
                            '<div style="display: flex; align-items: center; gap: 8px;">' +
                                '<label style="font-size: 12px; margin: 0; white-space: nowrap;">Weight (kg/g):</label>' +
                                weightInput +
                            '</div>' +
                            '<button type="button" class="' + addButtonClass + '" ' + addButtonAttrs + '>' + (outOfStock ? 'Unavailable' : 'Add') + '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');
    }

    function addFishToOrder(id, name, price) {
        var weightElem = document.getElementById('qty_' + id);
        var weight = parseFloat(weightElem.value) || 0;

        if (weight <= 0) {
            alert('Please enter a valid weight in kg');
            return;
        }

        // Check if item already exists
        var existingIdx = fishOrder.items.findIndex(function(it) {
            return it.id === id;
        });

        var subtotal = price * weight;

        if (existingIdx >= 0) {
            fishOrder.items[existingIdx].quantity += weight;
            fishOrder.items[existingIdx].subtotal = fishOrder.items[existingIdx].quantity * fishOrder.items[existingIdx].price;
        } else {
            fishOrder.items.push({
                id: id,
                name: name,
                price: price,
                unit: 'kg',
                quantity: weight,
                subtotal: subtotal
            });
        }

        updateOrderSummary();
        weightElem.value = '1';
    }

    function removeOrderItem(idx) {
        fishOrder.items.splice(idx, 1);
        updateOrderSummary();
    }

    function updateOrderSummary() {
        var summaryDiv = document.getElementById('orderSummaryItems');
        var reviewDiv = document.getElementById('reviewItems');

        summaryDiv.innerHTML = '';
        reviewDiv.innerHTML = '';
        fishOrder.total = 0;

        if (fishOrder.items.length === 0) {
            summaryDiv.innerHTML = '<p class="text-muted text-center py-3">No items added yet</p>';
            reviewDiv.innerHTML = '<p class="text-muted text-center py-3">No items to review</p>';
            document.getElementById('proceedCheckoutBtn').style.display = 'none';
            document.getElementById('emptyCartMsg').style.display = 'block';
            document.getElementById('orderTotal').textContent = '₱0.00';
            document.getElementById('reviewTotal').textContent = '₱0.00';
            return;
        }

        fishOrder.items.forEach(function(item, idx) {
            fishOrder.total += item.subtotal;

            // Summary
            var sumEl = document.createElement('div');
            sumEl.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e0e0e0; font-size: 13px;';
            sumEl.innerHTML = '<div><strong>' + escapeHtml(item.name) + '</strong><br><small class="text-muted">₱' + item.price.toFixed(2) + '/kg × ' + item.quantity.toFixed(2) + ' kg</small></div><div style="text-align: right;"><strong style="color: #27ae60;">₱' + item.subtotal.toFixed(2) + '</strong><br><button type="button" style="background: none; border: none; color: #c00; cursor: pointer; font-size: 12px; padding: 0;" onclick="removeOrderItem(' + idx + ')">Remove</button></div>';
            summaryDiv.appendChild(sumEl);

            // Review
            var revEl = document.createElement('div');
            revEl.style.cssText = 'display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; padding: 8px 0; border-bottom: 1px solid #e0e0e0;';
            revEl.innerHTML = '<div><strong>' + escapeHtml(item.name) + '</strong><br><small class="text-muted">' + item.quantity.toFixed(2) + ' kg @ ₱' + item.price.toFixed(2) + '/kg</small></div><div style="font-weight: 600; text-align: right;">₱' + item.subtotal.toFixed(2) + '</div>';
            reviewDiv.appendChild(revEl);
        });

        document.getElementById('proceedCheckoutBtn').style.display = 'inline-block';
        document.getElementById('emptyCartMsg').style.display = 'none';
        document.getElementById('orderTotal').textContent = '₱' + fishOrder.total.toFixed(2);
        document.getElementById('reviewTotal').textContent = '₱' + fishOrder.total.toFixed(2);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    document.getElementById('proceedCheckoutBtn').addEventListener('click', function() {
        document.getElementById('checkoutSection').style.display = 'block';
        document.getElementById('checkoutSection').scrollIntoView({ behavior: 'smooth' });
    });

    document.getElementById('cancelCheckoutBtn').addEventListener('click', function() {
        document.getElementById('checkoutSection').style.display = 'none';
    });

    document.getElementById('fishOrderForm').addEventListener('submit', function(e) {
        if (fishOrder.items.length === 0) {
            e.preventDefault();
            alert('Please add items to the order');
            return;
        }

        document.getElementById('orderItemsPayload').value = JSON.stringify(fishOrder.items);
    });
</script>
