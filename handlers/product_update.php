<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../auth_admin.php';
header('Content-Type: application/json');

$ok = false;
$msg = '';

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('Invalid product ID');
    }

    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $status = trim($_POST['status'] ?? 'available');
    $description = trim($_POST['description'] ?? '');

    if (!$name) throw new Exception('Product name required');
    if (!$category) throw new Exception('Category required');
    if ($price <= 0) throw new Exception('Valid price required');
    if (!in_array($category, ['food', 'snack', 'drink'])) throw new Exception('Invalid category');
    if (!in_array($status, ['available', 'unavailable'])) throw new Exception('Invalid status');

    // Get current product image
    $stmt = $conn->prepare('SELECT image FROM products WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception('Product not found');
    }

    $image = $row['image'];

    // Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['image']['tmp_name'];
        $fname = $_FILES['image']['name'];
        $size = $_FILES['image']['size'];

        if ($size > 5242880) throw new Exception('Image too large (max 5MB)');

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            throw new Exception('Invalid image type');
        }

        // Get original extension from uploaded filename
        $ext = pathinfo($fname, PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $ext = explode('/', $mime)[1]; // fallback: get from MIME type
        }

        // Remove old image if exists
        if ($image && file_exists(__DIR__ . '/../assets/img/products/' . $image)) {
            @unlink(__DIR__ . '/../assets/img/products/' . $image);
        }

        // Generate new filename
        $image = $id . '_' . uniqid() . '.' . strtolower($ext);
        $destPath = __DIR__ . '/../assets/img/products/' . $image;

        if (!move_uploaded_file($tmp, $destPath)) {
            throw new Exception('Failed to save image');
        }
    }

    // Update product
    $stmt = $conn->prepare('UPDATE products SET name=?, category=?, price=?, stock_quantity=?, status=?, description=?, image=? WHERE id=?');
    $stmt->bind_param('ssdssssi', $name, $category, $price, $stock_quantity, $status, $description, $image, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $ok = true;
        $msg = 'Product updated successfully';
    } else {
        throw new Exception('No changes made');
    }
    $stmt->close();

} catch (Exception $e) {
    $msg = $e->getMessage();
}

echo json_encode(['ok' => $ok ? 1 : 0, 'msg' => $msg]);
