<?php include 'partials/header.php'; ?>
<?php include '../config/db.php'; ?>

<main>
    <section class="hero" style="
        background: linear-gradient(rgba(39, 174, 96, 0.8), rgba(39, 174, 96, 0.8)), url('https://via.placeholder.com/1200x400');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 100px 20px;
        text-align: center;
        margin: 0;
    ">
        <div class="container">
            <h1 style="font-size: 48px; margin-bottom: 20px;">Welcome to Maata Fish Farm</h1>
            <p style="font-size: 20px; margin-bottom: 15px;">Family-owned Aquaculture & Food Service</p>
            <p style="font-size: 18px; margin-bottom: 30px;">Fresh Fish Daily • Authentic Filipino Cuisine • Event Hosting</p>
            <a href="menu.php" class="btn btn-primary" style="font-size: 16px; padding: 12px 30px;">
                <i class="fas fa-utensils"></i> View Our Food Menu
            </a>
            <a href="booking.php" class="btn btn-secondary" style="font-size: 16px; padding: 12px 30px;">
                <i class="fas fa-calendar"></i> Reserve Now
            </a>


        </div>
    </section>

    <section style="padding: 50px 20px;">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 40px; color: #27ae60; font-size: 32px;">What We Offer</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                    <i class="fas fa-fish" style="font-size: 40px; color: #27ae60; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px;">Fresh Fish</h3>
                    <p>Tilapia, Japanese Koi, and Catfish from our 2-hectare pond farm. Available daily at ₱200/kg</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                    <i class="fas fa-utensils" style="font-size: 40px; color: #27ae60; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px;">Food Service</h3>
                    <p>Authentic Filipino cuisine featuring our fresh-caught fish. Entrance fee: ₱30/person</p>
                </div>
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                    <i class="fas fa-birthday-cake" style="font-size: 40px; color: #27ae60; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px;">Event Hosting</h3>
                    <p>Host your celebrations, family gatherings, and private events at our dining facility</p>
                </div>
            </div>
        </div>
    </section>

    <section style="padding: 50px 20px; background-color: white;">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 24px; color: #27ae60; font-size: 28px;">Fresh Fish — Available for Order</h2>

            <?php
            // load fish species from fish_species table
            $fish = [];
            $stmt = $conn->prepare("SELECT fish_id, name, description, price_per_kg AS price, 'kg' AS unit, stock AS stock_quantity, image FROM fish_species WHERE status = 'available' ORDER BY name ASC");
            if ($stmt) {
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) $fish[] = $r;
                $stmt->close();
            }
            ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                <?php foreach ($fish as $p): ?>
                    <div class="card" style="overflow:hidden;">
                        <?php $img = !empty($p['image']) ? '../assets/img/fish_species/' . $p['image'] : '../assets/img/fish-placeholder.png'; ?>
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" style="width:100%; height:180px; object-fit:cover;" onerror="this.src='../assets/img/fish-placeholder.png'">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($p['name']); ?></h5>
                            <p class="card-text text-muted" style="min-height:44px"><?php echo htmlspecialchars(substr($p['description'] ?? '', 0, 100)); ?></p>
                            <p class="font-weight-bold" style="color:#27ae60">₱<?php echo number_format($p['price'], 2); ?> / <?php echo $p['unit']; ?></p>
                            <div style="display:flex; gap:8px; align-items:center; margin-top:8px;">
                                <input type="number" min="1" value="1" id="qty_<?php echo $p['fish_id']; ?>" style="width:80px; padding:6px; border:1px solid #ddd; border-radius:4px;" max="<?php echo (int)$p['stock_quantity']; ?>">
                                <?php if ((int)$p['stock_quantity'] > 0): ?>
                                    <button class="btn btn-success btn-sm" onclick="addToCart(<?php echo $p['fish_id']; ?>,'<?php echo htmlspecialchars(addslashes($p['name'])); ?>',<?php echo $p['price']; ?>,'kg')"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>Out of stock</button>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted d-block mt-2">Stock: <?php echo (int)$p['stock_quantity']; ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>

<?php include 'partials/footer.php'; ?>