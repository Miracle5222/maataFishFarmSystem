<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

$cid = (int) $_SESSION['client_id'];
$prefill_name = '';
$prefill_contact = '';
$prefill_address = '';

$s = $conn->prepare('SELECT first_name, last_name, email, phone, address FROM customers WHERE id = ? LIMIT 1');
if ($s) {
    $s->bind_param('i', $cid);
    $s->execute();
    $r = $s->get_result();
    if ($r && $row = $r->fetch_assoc()) {
        $prefill_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $prefill_contact = $row['phone'] ?: $row['email'];
            $prefill_address = $row['address'] ?: '';
    }
    $s->close();
}

include 'partials/header.php';
?>
<main style="padding:40px 20px;">
    <div class="container">
        <h1 style="color:#27ae60; margin-bottom:20px; font-size:28px; font-weight:600;">Your Shopping Cart</h1>

        <?php if (!empty($_SESSION['cart_error'])): ?>
            <div style="background:#ffebee; color:#c00; padding:12px; border-radius:6px; margin-bottom:20px; border-left:4px solid #c00;">
                <?php echo htmlspecialchars($_SESSION['cart_error']); ?>
            </div>
            <?php unset($_SESSION['cart_error']); ?>
        <?php endif; ?>

        <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px;">
            <div id="cartArea" style="min-height:120px;">
                <!-- JS will render cart items here -->
            </div>
            <div style="margin-top:20px; text-align:right; border-top:1px solid #eee; padding-top:12px;">
                <button id="checkoutNow" class="btn btn-primary" style="margin-right:12px;">Proceed to Checkout</button>
                <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
        </div>

        <!-- Checkout section (form) -->
        <div id="checkoutSection" style="display:none;">
            <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                <h2 style="color:#233; margin-bottom:20px; font-size:24px;">Checkout</h2>
                
                <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px;">
                    <!-- Checkout Form -->
                    <form id="cartCheckoutForm" method="POST" action="../handlers/client_cart_order.php">
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            <div>
                                <label style="display:block; margin-bottom:6px; font-weight:600; color:#333;">Full Name *</label>
                                <input type="text" name="customer_name" class="form-control" required value="<?php echo htmlspecialchars($prefill_name); ?>" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                            </div>

                            <div>
                                <label style="display:block; margin-bottom:6px; font-weight:600; color:#333;">Contact (Phone or Email) *</label>
                                <input type="text" name="customer_contact" class="form-control" required value="<?php echo htmlspecialchars($prefill_contact); ?>" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                            </div>

                            <div>
                                <label style="display:block; margin-bottom:6px; font-weight:600; color:#333;">Pickup Date *</label>
                                <input type="date" name="pickup_date" class="form-control" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                            </div>

                            <input type="hidden" name="cart" id="cartPayload">
                        </div>
                    </form>

                    <!-- Order Summary -->
                    <div style="background:#f7f9f7; padding:20px; border-radius:8px; height:fit-content; border:1px solid #e0e0e0;">
                        <h3 style="color:#233; margin-bottom:16px; font-size:18px; font-weight:600;">Order Summary</h3>
                        <div id="summaryItems" style="max-height:280px; overflow-y:auto; margin-bottom:16px; border-bottom:1px solid #ddd; padding-bottom:12px;"></div>
                        <div style="background:white; padding:12px; border-radius:6px; margin-bottom:16px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-weight:600; color:#333;">Subtotal</span>
                                <span id="summarySubtotal" style="font-size:16px; color:#27ae60;">₱0.00</span>
                            </div>
                        </div>
                        <div style="text-align:right; font-weight:700; font-size:20px; color:#27ae60;">Total: <span id="summaryTotal">₱0.00</span></div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="margin-top:24px; text-align:right; border-top:1px solid #eee; padding-top:16px;">
                    <button type="submit" form="cartCheckoutForm" class="btn btn-primary" style="padding:12px 28px; margin-right:12px;">Place Order</button>
                    <button type="button" id="cancelCheckout" class="btn btn-secondary" style="padding:12px 28px;">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'partials/footer.php'; ?>

<script>
    var cartItems = [];

    function loadCart() {
        fetch('../handlers/client_cart_api.php?action=list', { credentials: 'include' })
            .then(function(res) { 
                if (!res.ok) {
                    console.error('Response status:', res.status);
                    return res.text().then(function(text) {
                        console.error('Response body:', text);
                        throw new Error('HTTP ' + res.status);
                    });
                }
                return res.json(); 
            })
            .then(function(data) {
                console.log('Cart data:', data);
                if (data.success) {
                    cartItems = data.items || [];
                    renderCart();
                } else {
                    console.error('API error:', data.error, data.debug);
                    alert('Error loading cart: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function(e) { console.error('loadCart error', e); alert('Error loading cart: ' + e.message); });
    }

    function renderCart() {
        var area = document.getElementById('cartArea');
        area.innerHTML = '';
        
        if (cartItems.length === 0) {
            area.innerHTML = '<div style="text-align:center; color:#999; padding:40px 20px;">Your cart is empty. <a href="index.php" style="color:#27ae60; text-decoration:none; font-weight:600;">Browse products</a></div>';
            return;
        }

        var table = document.createElement('div');
        table.style.display = 'flex';
        table.style.flexDirection = 'column';
        table.style.gap = '12px';

        cartItems.forEach(function(item) {
            var row = document.createElement('div');
            row.style.display = 'flex';
            row.style.justifyContent = 'space-between';
            row.style.alignItems = 'center';
            row.style.padding = '12px';
            row.style.background = '#f9f9f9';
            row.style.borderRadius = '6px';
            row.style.borderLeft = '3px solid #27ae60';
            row.setAttribute('data-cart-id', item.id);
            row.setAttribute('data-selected', 'true');

            // Add checkbox
            var checkboxDiv = document.createElement('div');
            checkboxDiv.style.marginRight = '12px';
            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = true;
            checkbox.style.cssText = 'width:18px; height:18px; cursor:pointer;';
            checkbox.addEventListener('change', function() {
                row.setAttribute('data-selected', checkbox.checked ? 'true' : 'false');
                renderSummary();
            });
            checkboxDiv.appendChild(checkbox);

            var infoDiv = document.createElement('div');
            infoDiv.style.flex = '1';
            infoDiv.innerHTML = '<strong style="font-size:15px; color:#233;">' + escapeHtml(item.name) + '</strong><br><small style="color:#666;">₱' + item.unit_price.toFixed(2) + ' / ' + escapeHtml(item.unit) + '</small>';

            var qtyDiv = document.createElement('div');
            qtyDiv.style.display = 'flex';
            qtyDiv.style.alignItems = 'center';
            qtyDiv.style.gap = '10px';
            qtyDiv.style.marginRight = '16px';

            var btnMinus = document.createElement('button');
            btnMinus.textContent = '−';
            btnMinus.style.cssText = 'background:#e0e0e0; border:none; width:32px; height:32px; border-radius:4px; cursor:pointer; font-weight:600; color:#333;';
            btnMinus.onclick = function(e) { e.preventDefault(); updateQty(item.id, item.quantity - 1); };

            var qtySpan = document.createElement('span');
            qtySpan.textContent = item.quantity;
            qtySpan.style.cssText = 'min-width:24px; text-align:center; font-weight:600;';

            var btnPlus = document.createElement('button');
            btnPlus.textContent = '+';
            btnPlus.style.cssText = 'background:#27ae60; border:none; width:32px; height:32px; border-radius:4px; cursor:pointer; font-weight:600; color:white;';
            btnPlus.onclick = function(e) { e.preventDefault(); updateQty(item.id, item.quantity + 1); };

            qtyDiv.appendChild(btnMinus);
            qtyDiv.appendChild(qtySpan);
            qtyDiv.appendChild(btnPlus);

            var priceDiv = document.createElement('div');
            priceDiv.style.textAlign = 'right';
            priceDiv.style.minWidth = '100px';
            priceDiv.innerHTML = '<strong style="font-size:16px; color:#27ae60;">₱' + item.subtotal.toFixed(2) + '</strong><br><small style="color:#999;">x' + item.quantity + '</small>';

            var removeBtn = document.createElement('button');
            removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
            removeBtn.style.cssText = 'background:#fee; border:none; color:#c00; padding:8px 12px; border-radius:4px; cursor:pointer; margin-left:12px;';
            removeBtn.onclick = function(e) { e.preventDefault(); removeFromCart(item.id); };

            row.appendChild(checkboxDiv);
            row.appendChild(infoDiv);
            row.appendChild(qtyDiv);
            row.appendChild(priceDiv);
            row.appendChild(removeBtn);
            table.appendChild(row);
        });
        area.appendChild(table);

        var total = cartItems.reduce(function(sum, i) { return sum + i.subtotal; }, 0);
        var totalDiv = document.createElement('div');
        totalDiv.style.marginTop = '16px';
        totalDiv.style.fontSize = '18px';
        totalDiv.style.fontWeight = '700';
        totalDiv.style.textAlign = 'right';
        totalDiv.style.color = '#27ae60';
        totalDiv.innerHTML = 'Cart Total: ₱' + total.toFixed(2);
        area.appendChild(totalDiv);
    }

    function updateQty(cartId, newQty) {
        var formData = new FormData();
        formData.append('action', 'update');
        formData.append('cart_id', cartId);
        formData.append('quantity', newQty);

        fetch('../handlers/client_cart_api.php', { method: 'POST', body: formData, credentials: 'include' })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    loadCart();
                    renderSummary();
                } else {
                    alert('Error updating cart');
                }
            })
            .catch(function(e) { console.error('updateQty error', e); });
    }

    function removeFromCart(cartId) {
        if (!confirm('Remove this item?')) return;
        
        var formData = new FormData();
        formData.append('action', 'remove');
        formData.append('cart_id', cartId);

        fetch('../handlers/client_cart_api.php', { method: 'POST', body: formData, credentials: 'include' })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    loadCart();
                    renderSummary();
                } else {
                    alert('Error removing item');
                }
            })
            .catch(function(e) { console.error('removeFromCart error', e); });
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>\"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '\"': '&quot;', "'": "&#39;" } [m];
        });
    }

    function renderSummary() {
        var list = document.getElementById('summaryItems');
        var total = 0;
        list.innerHTML = '';
        
        // Get selected cart item IDs from checkboxes
        var selectedIds = new Set();
        document.querySelectorAll('[data-cart-id]').forEach(function(row) {
            if (row.getAttribute('data-selected') === 'true') {
                selectedIds.add(parseInt(row.getAttribute('data-cart-id')));
            }
        });
        
        cartItems.forEach(function(it) {
            if (selectedIds.has(it.id)) {
                var el = document.createElement('div');
                el.style.cssText = 'display:flex; justify-content:space-between; margin-bottom:8px; font-size:14px;';
                var name = escapeHtml(it.name) + ' x' + it.quantity;
                var price = '₱' + it.subtotal.toFixed(2);
                total += it.subtotal;
                el.innerHTML = '<div style="color:#555;">' + name + '</div><div style="color:#333; font-weight:600;">' + price + '</div>';
                list.appendChild(el);
            }
        });
        
        document.getElementById('summarySubtotal').textContent = '₱' + total.toFixed(2);
        document.getElementById('summaryTotal').textContent = '₱' + total.toFixed(2);
    }

    document.getElementById('checkoutNow').addEventListener('click', function() {
        if (cartItems.length === 0) {
            alert('Cart is empty');
            return;
        }
        document.getElementById('checkoutSection').style.display = 'block';
        window.scrollTo({ top: document.getElementById('checkoutSection').offsetTop - 20, behavior: 'smooth' });
        renderSummary();
    });

    document.getElementById('cancelCheckout').addEventListener('click', function() {
        document.getElementById('checkoutSection').style.display = 'none';
    });

    document.getElementById('cartCheckoutForm').addEventListener('submit', function(e) {
        // Get selected cart item IDs from checkboxes
        var selectedIds = new Set();
        document.querySelectorAll('[data-cart-id]').forEach(function(row) {
            if (row.getAttribute('data-selected') === 'true') {
                selectedIds.add(parseInt(row.getAttribute('data-cart-id')));
            }
        });
        
        if (selectedIds.size === 0) {
            e.preventDefault();
            alert('Please select at least one item to order');
            return;
        }
        
        // Convert ONLY selected cart items to format expected by handler (use fish_id)
        var cartData = cartItems.filter(function(it) {
            return selectedIds.has(it.id);
        }).map(function(it) {
            return { id: it.fish_id, name: it.name, price: it.unit_price, unit: it.unit, qty: it.quantity };
        });
        document.getElementById('cartPayload').value = JSON.stringify(cartData);
        // Clear cart from display after submission
        setTimeout(function() {
            cartItems = [];
            renderCart();
        }, 100);
    });

    loadCart();
</script>
