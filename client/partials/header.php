<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$clientLoggedIn = isset($_SESSION['client_id']) && $_SESSION['client_id'];
// Fetch latest order for logged-in user to show quick status in header
$latest_order = null;
$feedback_messages = [];
$unread_feedback_count = 0;

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
        
        // Fetch feedback messages for the customer
        $fst = $conn->prepare('SELECT id, message, created_at FROM feedback_messages WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5');
        if ($fst) {
            $fst->bind_param('i', $uid);
            $fst->execute();
            $fr = $fst->get_result();
            while ($row = $fr->fetch_assoc()) {
                $feedback_messages[] = $row;
                $unread_feedback_count++;
            }
            $fst->close();
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
                <img src="../assets/img/maataLogo.png" alt="Maata Fish Farm" style="height: 60px; width: auto;">
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="menu.php">Menu</a>
                <a href="booking.php">Reserve Now</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
            </nav>
            <div class="nav-buttons">
                <a href="booking.php" class="btn btn-primary"><i class="fas fa-calendar"></i> Reserve</a>
                <?php if ($clientLoggedIn): ?>
                    <!-- Notification Bell -->
                    <div class="notification-dropdown" style="position:relative; display:inline-block;">
                        <button id="notificationBtn" class="btn btn-secondary" style="display:flex; align-items:center; gap:8px; position:relative;">
                            <i class="fas fa-bell" style="font-size:18px;"></i>
                            <?php if ($unread_feedback_count > 0): ?>
                                <span style="position:absolute; top:-8px; right:-8px; background:#e74c3c; color:white; border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold;">
                                    <?php echo min($unread_feedback_count, 9); ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        <div id="notificationMenu" style="position:absolute; right:0; top:calc(100% + 8px); background:white; border-radius:6px; box-shadow:0 8px 24px rgba(0,0,0,0.12); display:none; min-width:320px; z-index:2000; max-height:400px; overflow-y:auto;">
                            <div style="padding:12px 14px; border-bottom:1px solid #f0f0f0; background:#f7f9f7; font-weight:600; color:#333;">
                                📬 Notifications <?php if ($unread_feedback_count > 0): ?><span style="float:right; background:#27ae60; color:white; padding:2px 8px; border-radius:12px; font-size:11px;"><?php echo $unread_feedback_count; ?> new</span><?php endif; ?>
                            </div>
                            <?php if (!empty($feedback_messages)): ?>
                                <?php foreach ($feedback_messages as $msg): ?>
                                    <div style="padding:12px 14px; border-bottom:1px solid #f0f0f0; color:#333; cursor:pointer; transition:background 0.2s;" onmouseover="this.style.background='#f7f9f7';" onmouseout="this.style.background='white';">
                                        <p style="margin:0 0 4px 0; font-weight:500; color:#27ae60;">📨 Farm Feedback</p>
                                        <p style="margin:0 0 4px 0; font-size:13px; color:#555; word-break:break-word;"><?php echo htmlspecialchars(substr($msg['message'], 0, 80)); ?><?php if (strlen($msg['message']) > 80): ?>...<?php endif; ?></p>
                                        <small style="color:#999;"><?php echo date('M d, Y g:iA', strtotime($msg['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding:20px 14px; text-align:center; color:#999;">
                                    <i class="fas fa-inbox" style="font-size:32px; margin-bottom:8px; opacity:0.5;"></i>
                                    <p style="margin:8px 0 0 0;">No notifications yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="user-dropdown" style="position:relative; display:inline-block;">
                        <button id="userMenuBtn" class="btn btn-secondary" style="display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-user-circle" style="font-size:20px;"></i>
                        </button>
                        <div id="userMenu" style="position:absolute; right:0; top:calc(100% + 8px); background:white; border-radius:6px; box-shadow:0 8px 24px rgba(0,0,0,0.12); display:none; min-width:220px; z-index:2000;">
                            <a href="cart.php" style="display:block; padding:10px 14px; color:#333; text-decoration:none; border-bottom:1px solid #f0f0f0;">🛒 Cart</a>
                            <a href="orders.php" style="display:block; padding:10px 14px; color:#333; text-decoration:none; border-bottom:1px solid #f0f0f0;">📋 Orders <?php if ($latest_order): ?><span style="float:right; background:#27ae60; color:#fff; padding:2px 8px; border-radius:12px; font-size:12px;"><?php echo htmlspecialchars($latest_order['status']); ?></span><?php endif; ?></a>
                            <a href="reservations.php" style="display:block; padding:10px 14px; color:#333; text-decoration:none; border-bottom:1px solid #f0f0f0;">📅 Reservations</a>
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

        function addToCart(id, name, price, unit, type = 'fish') {
            if (!clientLoggedIn) {
                var next = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = 'login.php?next=' + next;
                return;
            }
            try {
                var qtyEl = document.getElementById('qty_' + id);
                var inputValue = qtyEl ? qtyEl.value : '1';
                var qty = parseFloat(inputValue) || 1;
                
                console.log('addToCart Debug:', {
                    fish_id: id,
                    qty_element_id: 'qty_' + id,
                    element_found: qtyEl ? true : false,
                    input_value: inputValue,
                    parsed_qty: qty
                });
                
                // Validate quantity is positive
                if (qty <= 0) {
                    alert('Please enter a valid quantity');
                    return;
                }
                
                // Add to database cart via AJAX
                var formData = new FormData();
                formData.append('action', 'add');
                formData.append('item_id', id);
                formData.append('item_type', type);
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

        // notification dropdown toggle
        (function(){
            var btn = document.getElementById('notificationBtn');
            var menu = document.getElementById('notificationMenu');
            if (!btn || !menu) return;
            btn.addEventListener('click', function(e){ 
                e.stopPropagation(); 
                menu.style.display = (menu.style.display === 'block' || menu.style.display === '') ? 'none' : 'block'; 
            });
            document.addEventListener('click', function(e){ 
                if(menu.style.display === 'block' && e.target !== btn && !btn.contains(e.target)) {
                    menu.style.display = 'none'; 
                }
            });
        })();
    </script>