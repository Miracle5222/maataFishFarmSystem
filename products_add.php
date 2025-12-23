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
        // If category is fish, insert into fish_species table instead
        if (strtolower($category) === 'fish') {
            $local_name = trim($_POST['local_name'] ?? '');
            $harvest_schedule = trim($_POST['harvest_schedule'] ?? '');

            $stmt = $conn->prepare("INSERT INTO fish_species (name, local_name, price_per_kg, stock, harvest_schedule, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('ssdisss', $name, $local_name, $price, $stock_quantity, $harvest_schedule, $description, $status);
                $ok = $stmt->execute();
                if (!$ok) {
                    $errors[] = 'Fish insert execute failed: ' . $stmt->error;
                }
                $fish_id = $conn->insert_id;
                $stmt->close();

                if ($ok && $fish_id > 0) {
                    // Save uploaded image (main) for fish species if provided
                    $uploadDir = __DIR__ . '/assets/img/fish_species/';
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            $errors[] = 'Failed to create fish_species image directory.';
                        }
                    }

                    // Ensure fish_species has image column (use SHOW COLUMNS for compatibility)
                    if (empty($errors)) {
                        $colRes = $conn->query("SHOW COLUMNS FROM fish_species LIKE 'image'");
                        if ($colRes && $colRes->num_rows === 0) {
                            if (!$conn->query("ALTER TABLE fish_species ADD COLUMN image VARCHAR(255) NULL;")) {
                                $errors[] = 'Failed to add image column: ' . $conn->error;
                            }
                        }
                    }

                    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $maxSize = 5 * 1024 * 1024; // 5MB
                    
                    if (!empty($_FILES['product_image']['name'])) {
                        if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
                            $uploadErrors = [
                                UPLOAD_ERR_INI_SIZE => 'File exceeds server max size.',
                                UPLOAD_ERR_FORM_SIZE => 'File exceeds form max size.',
                                UPLOAD_ERR_PARTIAL => 'File upload incomplete.',
                                UPLOAD_ERR_NO_FILE => 'No file selected.',
                                UPLOAD_ERR_NO_TMP_DIR => 'Server temp directory missing.',
                                UPLOAD_ERR_CANT_WRITE => 'Cannot write to server disk.',
                                UPLOAD_ERR_EXTENSION => 'File extension blocked.',
                            ];
                            $errors[] = 'Image upload error: ' . ($uploadErrors[$_FILES['product_image']['error']] ?? 'Unknown');
                        } else {
                            $f = $_FILES['product_image'];
                            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                            
                            if (!in_array($ext, $allowedExts)) {
                                $errors[] = 'File type .' . $ext . ' not allowed. Use: ' . implode(', ', $allowedExts);
                            } elseif ($f['size'] > $maxSize) {
                                $errors[] = 'Image file too large (max 5MB).';
                            } else {
                                // Check MIME type using finfo or fallback to extension
                                $mimeType = 'application/octet-stream';
                                if (function_exists('finfo_file')) {
                                    $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']);
                                } elseif (function_exists('mime_content_type')) {
                                    $mimeType = mime_content_type($f['tmp_name']);
                                }
                                
                                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                                if (!in_array($mimeType, $allowedMimes, true)) {
                                    // If MIME doesn't match but extension is safe, allow it
                                    if (!in_array($ext, $allowedExts)) {
                                        $errors[] = 'Image MIME type invalid: ' . $mimeType;
                                    }
                                }
                                
                                if (empty($errors)) {
                                    $filename = 'fish_' . $fish_id . '_' . uniqid() . '.' . $ext;
                                    $dest = $uploadDir . $filename;
                                    if (@move_uploaded_file($f['tmp_name'], $dest)) {
                                        $upd = $conn->prepare('UPDATE fish_species SET image = ? WHERE fish_id = ?');
                                        if ($upd) {
                                            $upd->bind_param('si', $filename, $fish_id);
                                            if (!$upd->execute()) {
                                                $errors[] = 'DB update image failed: ' . $upd->error;
                                            }
                                            $upd->close();
                                        } else {
                                            $errors[] = 'Failed to prepare image update: ' . $conn->error;
                                        }
                                    } else {
                                        $errors[] = 'Failed to move uploaded image file to ' . $dest;
                                    }
                                }
                            }
                        }
                    }

                    // Only redirect if there were no errors during image upload
                    if (empty($errors)) {
                        $conn->close();
                        header('Location: products_fish.php?success=1');
                        exit;
                    }
                    // If there were errors, stay on form and show them
                    $conn->close();
                }
            } else {
                $errors[] = 'Failed to prepare fish insert statement: ' . $conn->error;
            }
        } else {
            // Insert product into products table (non-fish categories)
            $stmt = $conn->prepare("INSERT INTO products (name, category, description, price, unit, stock_quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('sssdsis', $name, $category, $description, $price, $unit, $stock_quantity, $status);
                $ok = $stmt->execute();
                if (!$ok) {
                    $errors[] = 'Product insert execute failed: ' . $stmt->error;
                }
                $product_id = $conn->insert_id;
                $stmt->close();

                if ($ok && $product_id > 0) {
                    // Ensure upload directory exists
                    $uploadDir = __DIR__ . '/assets/img/products/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Ensure products table has an `image` column to store main image path
                    $colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'image'");
                    if ($colRes && $colRes->num_rows === 0) {
                        $conn->query("ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL;");
                    }

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
                    header('Location: products_menu.php?success=1');
                    exit;
                }
            } else {
                $errors[] = 'Failed to prepare database statement: ' . $conn->error;
            }
        }
    }

    // If we reach here, there were errors — store them to show below
}
// If a category is provided via query (sidebar quick links), prefill the select
 $prefill_category = isset($_GET['category']) ? trim($_GET['category']) : '';
// Determine current category (POST takes precedence)
$current_category = $_POST['category'] ?? $prefill_category;

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
                    <div class="alert text-danger alert-danger">
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
                        <label for="productName"><?php echo (strtolower($current_category) === 'fish') ? 'Fish Name *' : 'Menu *'; ?></label>
                        <input type="text" id="productName" name="product_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="">-- Select Category --</option>
                            <option value="fish" <?php echo (strtolower($current_category) === 'fish') ? 'selected' : ''; ?>>Fish</option>
                            <option value="food" <?php echo (strtolower($current_category) === 'food') ? 'selected' : ''; ?>>Food</option>
                            <option value="snack" <?php echo (strtolower($current_category) === 'snack') ? 'selected' : ''; ?>>Snack</option>
                            <option value="drink" <?php echo (strtolower($current_category) === 'drink') ? 'selected' : ''; ?>>Drink</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
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

                                <?php if (strtolower($current_category) === 'fish'): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="localName">Local Name</label>
                                            <input type="text" id="localName" name="local_name" class="form-control" value="<?php echo htmlspecialchars($_POST['local_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="harvestSchedule">Harvest Schedule</label>
                                            <input type="text" id="harvestSchedule" name="harvest_schedule" class="form-control" value="<?php echo htmlspecialchars($_POST['harvest_schedule'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
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