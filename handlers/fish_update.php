<?php
// handlers/fish_update.php
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => 0, 'msg' => 'Invalid request']);
    exit;
}
$id = (int) ($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$local = trim($_POST['local_name'] ?? '');
$price = (float) ($_POST['price_per_kg'] ?? 0);
$stock = (int) ($_POST['stock'] ?? 0);
$harvest = trim($_POST['harvest_schedule'] ?? '');
$desc = trim($_POST['description'] ?? '');
$status = trim($_POST['status'] ?? 'available');
if ($id <= 0 || $name === '' || $price <= 0) {
    echo json_encode(['ok' => 0, 'msg' => 'Missing fields']);
    exit;
}
// handle optional image upload
$imageUpdated = false;
$newImageName = '';
if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $img = $_FILES['image'];
    if ($img['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($img['name'], PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['ok' => 0, 'msg' => 'Invalid image type']);
            exit;
        }
        $newImageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetDir = __DIR__ . '/../assets/img/fish_species/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $targetPath = $targetDir . $newImageName;
        if (!move_uploaded_file($img['tmp_name'], $targetPath)) {
            echo json_encode(['ok' => 0, 'msg' => 'Failed to save image']);
            exit;
        }
        $imageUpdated = true;
    } else {
        echo json_encode(['ok' => 0, 'msg' => 'Image upload error']);
        exit;
    }
}

// if image updated, fetch old filename to delete after successful update
$oldImage = null;
if ($imageUpdated) {
    $q = $conn->prepare('SELECT image FROM fish_species WHERE fish_id = ?');
    if ($q) {
        $q->bind_param('i', $id);
        $q->execute();
        $res = $q->get_result();
        if ($r = $res->fetch_assoc()) $oldImage = $r['image'];
        $q->close();
    }
}

$fields = 'name = ?, local_name = ?, price_per_kg = ?, stock = ?, harvest_schedule = ?, description = ?, status = ?';
if ($imageUpdated) $fields .= ', image = ?';

$sql = 'UPDATE fish_species SET ' . $fields . ' WHERE fish_id = ?';
$up = $conn->prepare($sql);
if (!$up) {
    echo json_encode(['ok' => 0, 'msg' => 'Prepare failed']);
    exit;
}
if ($imageUpdated) {
    $up->bind_param('ssdisissi', $name, $local, $price, $stock, $harvest, $desc, $status, $newImageName, $id);
} else {
    $up->bind_param('ssdisisi', $name, $local, $price, $stock, $harvest, $desc, $status, $id);
}
$ok = $up->execute();
$up->close();
if ($ok) {
    // delete old image file if replaced
    if ($imageUpdated && $oldImage) {
        $oldPath = __DIR__ . '/../assets/img/fish_species/' . $oldImage;
        if (is_file($oldPath)) @unlink($oldPath);
    }
    echo json_encode(['ok' => 1]);
} else {
    // if DB update failed and we saved a new image, remove it to avoid orphan
    if ($imageUpdated) {
        @unlink(__DIR__ . '/../assets/img/fish_species/' . $newImageName);
    }
    echo json_encode(['ok' => 0, 'msg' => 'Update failed']);
}
