<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$clientLoggedIn = isset($_SESSION['client_id']) && $_SESSION['client_id'];
// Fetch latest order for logged-in user to show quick status in header
$latest_order = null;
if ($clientLoggedIn) {
    @include_once __DIR__ . '/../../config/db.php';
    if (isset($conn) && $conn) {
        $uid = (int) ($_SESSION['client_id'] ?? 0);
        $ost = $conn->prepare('SELECT order_number, status FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 1');
        if ($ost) {
            $ost->bind_param('i', $uid);
            $ost->execute();
            $or = $ost->get_result();
            if ($or && $or->num_rows) {
                $latest_order = $or->fetch_assoc();
            }
            $ost->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maata Fish Farm | Quality Fish Products</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: #333;
            line-height: 1.6;
            background-color: #f8f9fa;
        }

        header {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 28px;
        }

        nav {
            display: flex;
            gap: 30px;
            list-style: none;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        nav a:hover {
            color: #52be80;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #52be80;
            color: white;
        }

        .btn-primary:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-secondary {
            background-color: transparent;
            color: white;
       
        }

        .btn-secondary:hover {
            background-color: white;
            color: #27ae60;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Shared modal and cart styles for client pages */
        .cf-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .cf-modal.cf-open {
            display: flex;
        }

        .cf-modal-dialog {
            background: white;
            border-radius: 8px;
            max-width: 720px;
            width: 100%;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .cf-modal-header,
        .cf-modal-footer {
            padding: 16px;
            border-bottom: 1px solid #eee;
        }

        .cf-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f7f9f7;
        }

        .cf-modal-body {
            padding: 16px;
        }

        .cart-panel {
            position: fixed;
            right: 18px;
            bottom: 18px;
            width: 320px;
            max-width: 90%;
            z-index: 999;
        }

        .cart-panel .card {
            border-radius: 8px;
        }

        footer {
            background-color: #1a1a1a;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 50px;
        }

        footer p {
            margin: 5px 0;
        }

        @media (max-width: 768px) {
            nav {
                display: none;
            }

            .header-container {
                flex-direction: column;
                gap: 15px;
            }

            .nav-buttons {
                width: 100%;
                justify-content: space-around;
            }
        }
    </style>

</head>

<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-fish"></i>
                Maata Fish Farm
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="menu.php">Menu</a>
                <a href="booking.php">Book Now</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
            </nav>
            <div class="nav-buttons">
                <a href="booking.php" class="btn btn-primary"><i class="fas fa-calendar"></i> Book</a>
                <?php if ($clientLoggedIn): ?>
                    <div class="user-dropdown" style="position:relative; display:inline-block;">
                        <button id="userMenuBtn" class="btn btn-secondary" style="display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-user-circle" style="font-size:20px;"></i>
                        </button>
                        <div id="userMenu" style="position:absolute; right:0; top:calc(100% + 8px); background:white; border-radius:6px; box-shadow:0 8px 24px rgba(0,0,0,0.12); display:none; min-width:220px; z-index:2000;">
                            <a href="cart.php" style="display:block; padding:10px 14px; color:#333; text-decoration:none; border-bottom:1px solid #f0f0f0;">🛒 Cart</a>
                            <a href="orders.php" style="display:block; padding:10px 14px; color:#333; text-decoration:none; border-bottom:1px solid #f0f0f0;">📋 Orders <?php if ($latest_order): ?><span style="float:right; background:#27ae60; color:#fff; padding:2px 8px; border-radius:12px; font-size:12px;"><?php echo htmlspecialchars($latest_order['status']); ?></span><?php endif; ?></a>
                            <a href="profile.php" style="display:block; padding:10px 14px; color:#333; text-decoration:none; border-bottom:1px solid #f0f0f0;">👤 My Account</a>
                            <a href="../handlers/client_logout.php" style="display:block; padding:10px 14px; color:#c00; text-decoration:none;">🚪 Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="cart.php" class="btn btn-secondary"><i class="fas fa-shopping-cart"></i> Cart</a>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <script>
        var clientLoggedIn = <?php echo $clientLoggedIn ? 'true' : 'false'; ?>;
        var clientId = <?php echo isset($_SESSION['client_id']) ? (int)$_SESSION['client_id'] : 'null'; ?>;

        function addToCart(id, name, price, unit) {
            if (!clientLoggedIn) {
                var next = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = 'login.php?next=' + next;
                return;
            }
            try {
                var qtyEl = document.getElementById('qty_' + id);
                var qty = parseInt(qtyEl ? qtyEl.value : 1) || 1;
                
                // Add to database cart via AJAX
                var formData = new FormData();
                formData.append('action', 'add');
                formData.append('product_id', id);
                formData.append('quantity', qty);
                
                fetch('../handlers/client_cart_api.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                })
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
                    console.log('Cart API response:', data);
                    if (data.success) {
                        try { if (window.toastr) toastr.success('Added to cart'); else alert('Added to cart'); } catch (e) {}
                    } else {
                        var errMsg = data.error || 'Failed to add';
                        if (data.debug) errMsg += ' (' + JSON.stringify(data.debug) + ')';
                        alert('Error: ' + errMsg);
                    }
                })
                .catch(function(e) { 
                    console.error('addToCart error:', e); 
                    alert('Unable to add to cart: ' + e.message); 
                });
            } catch (e) { console.error('addToCart error', e); alert('Unable to add to cart: ' + e.message); }
        }

        // user menu toggle
        (function(){
            var btn = document.getElementById('userMenuBtn');
            var menu = document.getElementById('userMenu');
            if (!btn || !menu) return;
            btn.addEventListener('click', function(e){ e.stopPropagation(); menu.style.display = (menu.style.display === 'block') ? 'none' : 'block'; });
            document.addEventListener('click', function(){ if(menu.style.display === 'block') menu.style.display = 'none'; });
        })();
    </script>