<?php
include 'partials/header.php';
include '../config/db.php';
?>
<main>
    <section style="padding:40px 20px;">
        <div class="container">
            <h1 style="color:#27ae60; margin-bottom:20px;">Your Cart</h1>

            <div class="card" style="padding:16px; margin-bottom:16px;">
                <div id="cartArea">
                    <!-- JS will render cart items here -->
                </div>
                <div style="margin-top:12px; text-align:right;">
                    <button id="checkoutNow" class="btn btn-primary">Checkout</button>
                    <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
                </div>
            </div>

            <!-- Checkout section (form) -->
            <div id="checkoutSection" style="display:none;">
                <div class="card" style="padding:16px;">
                    <h4>Checkout</h4>
                    <form id="cartCheckoutForm" method="POST" action="../handlers/client_cart_order.php">
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <label>Your Name *</label>
                            <input type="text" name="customer_name" class="form-control" required>

                            <label>Contact (phone or email) *</label>
                            <input type="text" name="customer_contact" class="form-control" required>

                            <label>Delivery / Pickup Date</label>
                            <input type="date" name="delivery_date" class="form-control">

                            <input type="hidden" name="cart" id="cartPayload">

                            <div style="text-align:right; margin-top:8px;">
                                <button type="submit" class="btn btn-success">Place Order</button>
                                <button type="button" id="cancelCheckout" class="btn btn-secondary">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </section>
</main>

<?php include 'partials/footer.php'; ?>

<script>
    function getCart() {
        try {
            return JSON.parse(localStorage.getItem('mf_cart') || '[]');
        } catch (e) {
            return [];
        }
    }

    function saveCart(c) {
        localStorage.setItem('mf_cart', JSON.stringify(c));
        renderCart();
    }

    function renderCart() {
        var c = getCart();
        var area = document.getElementById('cartArea');
        area.innerHTML = '';
        if (c.length === 0) {
            area.innerHTML = '<div class="text-muted">Your cart is empty. Browse our <a href="index.php">fish products</a>.</div>';
            return;
        }
        var table = document.createElement('div');
        c.forEach(function(item, idx) {
            var row = document.createElement('div');
            row.style.display = 'flex';
            row.style.justifyContent = 'space-between';
            row.style.alignItems = 'center';
            row.style.padding = '8px 0';
            row.innerHTML = '<div><strong>' + escapeHtml(item.name) + '</strong><br><small>₱' + item.price.toFixed(2) + ' / ' + escapeHtml(item.unit) + '</small></div><div style="display:flex; align-items:center; gap:6px;"><button onclick="changeQty(' + idx + ',-1)" class="btn">-</button><span>' + item.qty + '</span><button onclick="changeQty(' + idx + ',1)" class="btn">+</button><button onclick="removeItem(' + idx + ')" class="btn btn-danger">Remove</button></div>';
            table.appendChild(row);
        });
        area.appendChild(table);
        var total = c.reduce(function(s, i) {
            return s + (i.price * i.qty)
        }, 0);
        var totalDiv = document.createElement('div');
        totalDiv.style.textAlign = 'right';
        totalDiv.style.marginTop = '12px';
        totalDiv.innerHTML = '<strong>Total: ₱' + total.toFixed(2) + '</strong>';
        area.appendChild(totalDiv);
    }

    function changeQty(i, delta) {
        var c = getCart();
        if (!c[i]) return;
        c[i].qty += delta;
        if (c[i].qty <= 0) c.splice(i, 1);
        saveCart(c);
    }

    function removeItem(i) {
        var c = getCart();
        c.splice(i, 1);
        saveCart(c);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>\"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '\"': '&quot;',
                "'": "&#39;"
            } [m];
        });
    }

    document.getElementById('checkoutNow').addEventListener('click', function() {
        var c = getCart();
        if (c.length === 0) {
            alert('Cart empty');
            return;
        }
        document.getElementById('checkoutSection').style.display = 'block';
        window.scrollTo({
            top: document.getElementById('checkoutSection').offsetTop - 20,
            behavior: 'smooth'
        });
    });

    document.getElementById('cancelCheckout').addEventListener('click', function() {
        document.getElementById('checkoutSection').style.display = 'none';
    });

    document.getElementById('cartCheckoutForm').addEventListener('submit', function() {
        document.getElementById('cartPayload').value = JSON.stringify(getCart()); // after submit, clear local cart
        setTimeout(function() {
            localStorage.removeItem('mf_cart');
        }, 500);
    });

    renderCart();
</script>