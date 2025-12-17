<?php
include 'auth_admin.php';
// products_add.php - handle add product form submission and show the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/config/db.php';

    // Collect and sanitize inputs
    $name = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'kg');
    $stock_quantity = (int) ($_POST['stock_quantity'] ?? 0);
    $status = trim($_POST['status'] ?? 'available');

    // Basic validation
    $errors = [];
    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if ($category === '') {
        $errors[] = 'Category is required.';
    }
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero.';
    }

    if (empty($errors)) {
        // Insert product
        $stmt = $conn->prepare("INSERT INTO products (name, category, description, price, unit, stock_quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sssdsis', $name, $category, $description, $price, $unit, $stock_quantity, $status);
            $ok = $stmt->execute();
            $product_id = $conn->insert_id;
            $stmt->close();

            if ($ok && $product_id > 0) {
                // Ensure upload directory exists
                $uploadDir = __DIR__ . '/assets/img/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Ensure products table has an `image` column to store main image path
                $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL;");

                // Create product_images table if not exists
                $conn->query("CREATE TABLE IF NOT EXISTS product_images (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    product_id INT NOT NULL,
                    filename VARCHAR(255) NOT NULL,
                    is_main TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // Helper for saving uploaded file
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB

                // Handle main image
                if (!empty($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $f = $_FILES['product_image'];
                    if ($f['size'] <= $maxSize && in_array(mime_content_type($f['tmp_name']), $allowedTypes, true)) {
                        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                        $filename = $product_id . '_' . uniqid() . '.' . $ext;
                        $dest = $uploadDir . $filename;
                        if (move_uploaded_file($f['tmp_name'], $dest)) {
                            // Save a reference in product_images for gallery and mark as main
                            $ins = $conn->prepare('INSERT INTO product_images (product_id, filename, is_main) VALUES (?, ?, 1)');
                            if ($ins) {
                                $ins->bind_param('is', $product_id, $filename);
                                $ins->execute();
                                $ins->close();
                            }
                            // Also save main image filename on products.image for easy access
                            $upd = $conn->prepare('UPDATE products SET image = ? WHERE id = ?');
                            if ($upd) {
                                $upd->bind_param('si', $filename, $product_id);
                                $upd->execute();
                                $upd->close();
                            }
                        }
                    }
                }

                // Handle gallery images
                if (!empty($_FILES['product_gallery']) && is_array($_FILES['product_gallery']['name'])) {
                    for ($i = 0; $i < count($_FILES['product_gallery']['name']); $i++) {
                        $err = $_FILES['product_gallery']['error'][$i];
                        if ($err !== UPLOAD_ERR_OK) {
                            continue;
                        }
                        $tmp = $_FILES['product_gallery']['tmp_name'][$i];
                        $orig = $_FILES['product_gallery']['name'][$i];
                        $size = $_FILES['product_gallery']['size'][$i];
                        if ($size <= $maxSize && in_array(mime_content_type($tmp), $allowedTypes, true)) {
                            $ext = pathinfo($orig, PATHINFO_EXTENSION);
                            $filename = $product_id . '_' . uniqid() . '.' . $ext;
                            $dest = $uploadDir . $filename;
                            if (move_uploaded_file($tmp, $dest)) {
                                $ins = $conn->prepare('INSERT INTO product_images (product_id, filename, is_main) VALUES (?, ?, 0)');
                                if ($ins) {
                                    $ins->bind_param('is', $product_id, $filename);
                                    $ins->execute();
                                    $ins->close();
                                }
                            }
                        }
                    }
                }

                $conn->close();
                header('Location: products_list.php?success=1');
                exit;
            }
        } else {
            $errors[] = 'Failed to prepare database statement.';
        }
    }

    // If we reach here, there were errors — store them to show below
}
?>
<?php include 'partials/head.php'; ?>
<?php include 'partials/sidenav.php'; ?>
<?php include 'partials/navbar.php'; ?>

<div class="layout-content">
    <div class="container-fluid flex-grow-1 container-p-y">
        <h4 class="font-weight-bold py-3 mb-0">Products — Add New Product</h4>
        <div class="card mt-3">
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Product added successfully.</div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="productName">Product Name *</label>
                        <input type="text" id="productName" name="product_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="">-- Select Category --</option>
                            <option value="fish">Fish</option>
                            <option value="food">Food</option>
                            <option value="snack">Snack</option>
                            <option value="drink">Drink</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price">Price *</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">₱</span>
                                    </div>
                                    <input type="number" id="price" name="price" class="form-control" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="unit">Unit *</label>
                                <select id="unit" name="unit" class="form-control" required>
                                    <option value="">-- Select Unit --</option>
                                    <option value="kg">kg</option>
                                    <option value="piece">piece</option>
                                    <option value="order">order</option>
                                    <option value="pcs">pcs</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="stock">Stock Quantity *</label>
                                <input type="number" id="stock" name="stock_quantity" class="form-control" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="productImage">Main Product Image</label>
                        <input type="file" id="productImage" name="product_image" class="form-control-file" accept="image/*">
                        <small class="form-text text-muted">Upload a primary product image (JPG, PNG). Max 5MB recommended.</small>
                    </div>


                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="feather icon-save mr-2"></i> Save Product
                        </button>
                        <a href="products_list.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>