<?php include 'partials/header.php';
require __DIR__ . '/../config/db.php';

$sections = [
    'food' => 'Menu',
    'snack' => 'Snacks',
    'drink' => 'Drinks'
];

$items = [];
$stmt = $conn->prepare('SELECT id, name, category, description, price, unit, stock_quantity, status, image FROM products WHERE category IN ("food","snack","drink") AND status = "available" ORDER BY category, name');
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $items[$r['category']][] = $r;
    }
    $stmt->close();
}
?>

<main>
    <section style="padding: 40px 20px;">
        <div class="container">
            <h1 style="color: #27ae60; margin-bottom: 10px;">Our Menu</h1>
            <p style="color: #666; margin-bottom: 30px;">Authentic Filipino cuisine featuring fresh-caught fish from our farm</p>
            <p style="background-color: #e8f5e9; padding: 15px; border-radius: 5px; margin-bottom: 30px; color: #27ae60; font-weight: 600;">
                <i class="fas fa-info-circle"></i> Entrance Fee: ₱30 per person
            </p>

            <?php foreach ($sections as $key => $title): ?>
                <?php if (!empty($items[$key])): ?>
                    <h2 style="margin-top: 30px; color: #2e7d32;"><?php echo htmlspecialchars($title); ?></h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 30px; margin-top: 15px;">
                        <?php foreach ($items[$key] as $m):
                            $img = !empty($m['image']) && file_exists(__DIR__ . '/../assets/img/products/' . $m['image']) ? '../assets/img/products/' . $m['image'] : 'https://via.placeholder.com/300x200?text=' . urlencode($m['name']);
                        ?>
                        <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <img src="<?php echo htmlspecialchars($img); ?>" style="width: 100%; height: 200px; object-fit: cover;">
                            <div style="background-color: #27ae60; color: white; padding: 15px; text-align: center;">
                                <h4 style="margin: 0;"><?php echo htmlspecialchars($m['name']); ?></h4>
                            </div>
                            <div style="padding: 20px;">
                                <p style="color: #666; margin-bottom: 15px;"><?php echo htmlspecialchars(substr($m['description'] ?? '', 0, 120)); ?></p>
                                <p style="font-size: 18px; color: #27ae60; font-weight: bold;">₱<?php echo number_format($m['price'], 2); ?> / <?php echo htmlspecialchars($m['unit']); ?></p>
                                <div style="display:flex; gap:8px; align-items:center; margin-top:8px;">
                                    <input type="number" min="1" value="1" id="qty_<?php echo $m['id']; ?>" style="width:80px; padding:6px; border:1px solid #ddd; border-radius:4px;" max="<?php echo (int)$m['stock_quantity']; ?>">
                                    <?php if ((int)$m['stock_quantity'] > 0): ?>
                                        <button class="btn btn-success btn-sm" onclick="addToCart(<?php echo $m['id']; ?>,'<?php echo htmlspecialchars(addslashes($m['name'])); ?>',<?php echo $m['price']; ?>,'<?php echo htmlspecialchars($m['unit']); ?>','product')"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                                    <?php else: ?>
                                        <button class="btn btn-danger btn-sm" disabled><i class="fas fa-exclamation-triangle"></i> Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted d-block mt-2" <?php if ((int)$m['stock_quantity'] <= 0): ?>style="color: red;"<?php endif; ?>>Stock: <?php echo (int)$m['stock_quantity']; ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

        </div>
    </section>
</main>

<?php include 'partials/footer.php'; ?>
