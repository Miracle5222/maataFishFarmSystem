<?php include 'auth_admin.php'; ?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- [ Layout content ] Start -->
<div class="layout-content">
    <!-- [ content ] Start -->
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Orders — Menu Order</h4>

        <div class="row mt-4">
            <!-- Menu Items -->
            <div class="col-lg-8">
                <!-- Products Section -->
                <div class="card mb-3">
                    <div class="card-header" style="background-color: #27ae60; color: white;">
                        <h6 class="mb-0">🍽️ Menu & Drinks</h6>
                    </div>
                    <div class="card-body">
                        <div id="productItemsContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;">
                            <!-- Product items will be loaded here -->
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
                    <form id="menuOrderForm" method="POST" action="handlers/admin_menu_order.php">
                        <div class="row">
                            <!-- Order Form (only order review / notes for admin menu orders) -->
                            <div class="col-lg-8">
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
    var menuOrder = {
        items: [],
        total: 0
    };

    // Load items on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadMenuItems();
    });

    function loadMenuItems() {
        fetch('handlers/admin_menu_items.php', { credentials: 'include' })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                console.log('Menu items response:', data);
                if (data.success) {
                    renderProductItems(data.products);
                } else {
                    var errorMsg = data.error || 'Unknown error';
                    console.error('API error:', data);
                    var debugInfo = data.debug ? ' (Session: ' + JSON.stringify(data.debug) + ')' : '';
                    document.getElementById('productItemsContainer').innerHTML = '<p class="text-danger">Error loading items: ' + errorMsg + debugInfo + '</p>';
                }
            })
            .catch(function(e) { 
                console.error('Error loading items:', e);
                document.getElementById('productItemsContainer').innerHTML = '<p class="text-danger">Error loading items: ' + e.message + '</p>';
            });
    }


    function renderProductItems(items) {
        var container = document.getElementById('productItemsContainer');
        if (!items || items.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">No menu items available</p>';
            return;
        }

        container.innerHTML = items.map(function(prod) {
            return `
                <div style="border: 1px solid #eee; border-radius: 6px; overflow: hidden;">
                    <img src="assets/img/products/${prod.image || 'placeholder.png'}" 
                         alt="${escapeHtml(prod.name)}" 
                         style="width: 100%; height: 100px; object-fit: cover; background: #f0f0f0; border-bottom: 1px solid #eee;">
                    <div style="padding: 10px;">
                        <h6 class="mb-1" style="font-size: 13px;">${escapeHtml(prod.name)}</h6>
                        <p class="text-muted small mb-2">${escapeHtml(prod.category)}</p>
                        <p class="text-success mb-2" style="font-weight: 700; font-size: 14px;">₱${parseFloat(prod.price).toFixed(2)}</p>
                        <div class="d-flex gap-1">
                            <input type="number" id="qty_product_${prod.id}" min="1" value="1" max="${parseInt(prod.stock)}" 
                                   style="width: 50px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;">
                            <button type="button" class="btn btn-sm btn-primary flex-grow-1" 
                                    onclick="addMenuItemToOrder('product', ${prod.id}, '${escapeHtml(prod.name)}', ${parseFloat(prod.price)}, 'pcs')">Add</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function addMenuItemToOrder(type, id, name, price, unit) {
        var qtyElem = document.getElementById('qty_' + type + '_' + id);
        var qty = parseFloat(qtyElem.value) || 0;

        if (qty <= 0) {
            alert('Please enter a valid quantity');
            return;
        }

        // Check if item already exists
        var existingIdx = menuOrder.items.findIndex(function(it) {
            return it.type === type && it.id === id;
        });

        var subtotal = price * qty;

        if (existingIdx >= 0) {
            menuOrder.items[existingIdx].quantity += qty;
            menuOrder.items[existingIdx].subtotal = menuOrder.items[existingIdx].quantity * menuOrder.items[existingIdx].price;
        } else {
            menuOrder.items.push({
                type: type,
                id: id,
                name: name,
                price: price,
                unit: unit,
                quantity: qty,
                subtotal: subtotal
            });
        }

        updateOrderSummary();
        qtyElem.value = (type === 'fish' ? '1' : '1');
    }

    function removeOrderItem(idx) {
        menuOrder.items.splice(idx, 1);
        updateOrderSummary();
    }

    function updateOrderSummary() {
        var summaryDiv = document.getElementById('orderSummaryItems');
        var reviewDiv = document.getElementById('reviewItems');

        summaryDiv.innerHTML = '';
        reviewDiv.innerHTML = '';
        menuOrder.total = 0;

        if (menuOrder.items.length === 0) {
            summaryDiv.innerHTML = '<p class="text-muted text-center py-3">No items added yet</p>';
            reviewDiv.innerHTML = '<p class="text-muted text-center py-3">No items to review</p>';
            document.getElementById('proceedCheckoutBtn').style.display = 'none';
            document.getElementById('emptyCartMsg').style.display = 'block';
            document.getElementById('orderTotal').textContent = '₱0.00';
            document.getElementById('reviewTotal').textContent = '₱0.00';
            return;
        }

        menuOrder.items.forEach(function(item, idx) {
            menuOrder.total += item.subtotal;

            // Summary
            var sumEl = document.createElement('div');
            sumEl.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e0e0e0; font-size: 13px;';
            sumEl.innerHTML = '<div><strong>' + escapeHtml(item.name) + '</strong><br><small class="text-muted">₱' + item.price.toFixed(2) + ' × ' + item.quantity + ' ' + item.unit + '</small></div><div style="text-align: right;"><strong style="color: #27ae60;">₱' + item.subtotal.toFixed(2) + '</strong><br><button type="button" style="background: none; border: none; color: #c00; cursor: pointer; font-size: 12px; padding: 0;" onclick="removeOrderItem(' + idx + ')">Remove</button></div>';
            summaryDiv.appendChild(sumEl);

            // Review
            var revEl = document.createElement('div');
            revEl.style.cssText = 'display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; padding: 8px 0; border-bottom: 1px solid #e0e0e0;';
            revEl.innerHTML = '<div>' + escapeHtml(item.name) + ' × ' + item.quantity + '</div><div style="font-weight: 600;">₱' + item.subtotal.toFixed(2) + '</div>';
            reviewDiv.appendChild(revEl);
        });

        document.getElementById('proceedCheckoutBtn').style.display = 'inline-block';
        document.getElementById('emptyCartMsg').style.display = 'none';
        document.getElementById('orderTotal').textContent = '₱' + menuOrder.total.toFixed(2);
        document.getElementById('reviewTotal').textContent = '₱' + menuOrder.total.toFixed(2);
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

    document.getElementById('menuOrderForm').addEventListener('submit', function(e) {
        if (menuOrder.items.length === 0) {
            e.preventDefault();
            alert('Please add items to the order');
            return;
        }

        document.getElementById('orderItemsPayload').value = JSON.stringify(menuOrder.items);
    });
</script>
