<?php
// handlers/fish_update.php
ob_start();
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/activity_logger.php';

header('Content-Type: application/json');
// DEBUG: Log all POST data
error_log('[fish_update] POST: ' . var_export($_POST, true));
if (!empty($_FILES)) error_log('[fish_update] FILES: ' . var_export($_FILES, true));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['ok' => 0, 'msg' => 'Invalid request']);
    exit;
}
$id = (int) ($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$local = trim($_POST['local_name'] ?? '');
$price = (float) ($_POST['price_per_kg'] ?? 0);
$stock = (int) ($_POST['stock'] ?? 0);
$harvest = trim($_POST['harvest_schedule'] ?? '');
$desc = '';
if (isset($_POST['description'])) {
    $desc = trim((string)$_POST['description']);
} elseif (isset($_REQUEST['description'])) {
    $desc = trim((string)$_REQUEST['description']);
}
if ($desc === '0') $desc = '';
if ($desc === null || $desc === '') $desc = '';
error_log('[fish_update] Received description: ' . var_export($desc, true));
$status = trim($_POST['status'] ?? 'available');
$stock_action = trim($_POST['stock_action'] ?? '');
$stock_reason = trim($_POST['stock_reason'] ?? '');
if ($stock_action === 'out' && $stock_reason === '') {
    ob_clean();
    echo json_encode(['ok' => 0, 'msg' => 'Stock-out reason is required']);
    exit;
}
$colRes = $conn->query("SHOW COLUMNS FROM fish_species LIKE 'last_stock_out_reason'");
if ($colRes && $colRes->num_rows === 0) {
    $conn->query("ALTER TABLE fish_species ADD COLUMN last_stock_out_reason TEXT NULL");
}
if ($id <= 0 || $name === '' || $price <= 0) {
    ob_clean();
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
            ob_clean();
            echo json_encode(['ok' => 0, 'msg' => 'Invalid image type']);
            exit;
        }
        $newImageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetDir = __DIR__ . '/../assets/img/fish_species/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $targetPath = $targetDir . $newImageName;
        if (!move_uploaded_file($img['tmp_name'], $targetPath)) {
            ob_clean();
            echo json_encode(['ok' => 0, 'msg' => 'Failed to save image']);
            exit;
        }
        $imageUpdated = true;
    } else {
        ob_clean();
        echo json_encode(['ok' => 0, 'msg' => 'Image upload error']);
        exit;
    }
}

// if image updated, fetch old filename to delete after successful update
$oldImage = null;
$oldStock = null;
if ($imageUpdated) {
    $q = $conn->prepare('SELECT image, stock FROM fish_species WHERE fish_id = ?');
    if ($q) {
        $q->bind_param('i', $id);
        $q->execute();
        $res = $q->get_result();
        if ($r = $res->fetch_assoc()) {
            $oldImage = $r['image'];
            $oldStock = isset($r['stock']) ? (int)$r['stock'] : null;
        }
        $q->close();
    }
} else {
    $q = $conn->prepare('SELECT stock FROM fish_species WHERE fish_id = ?');
    if ($q) {
        $q->bind_param('i', $id);
        $q->execute();
        $res = $q->get_result();
        if ($r = $res->fetch_assoc()) {
            $oldStock = isset($r['stock']) ? (int)$r['stock'] : null;
        }
        $q->close();
    }
}

$fields = 'name = ?, local_name = ?, price_per_kg = ?, stock = ?, harvest_schedule = ?, description = ?, status = ?';
$sql = 'UPDATE fish_species SET ' . $fields . ' WHERE fish_id = ?';
$bindTypes = 'ssdisssi';
$bindValues = [$name, $local, $price, $stock, $harvest, $desc, $status, $id];

if ($imageUpdated && $stock_action === 'out') {
    $fields .= ', image = ?, last_stock_out_reason = ?';
    $sql = 'UPDATE fish_species SET ' . $fields . ' WHERE fish_id = ?';
    $bindTypes = 'ssdisssssi';
    $bindValues = [$name, $local, $price, $stock, $harvest, $desc, $status, $newImageName, $stock_reason, $id];
} elseif ($imageUpdated) {
    $fields .= ', image = ?';
    $sql = 'UPDATE fish_species SET ' . $fields . ' WHERE fish_id = ?';
    $bindTypes = 'ssdissssi';
    $bindValues = [$name, $local, $price, $stock, $harvest, $desc, $status, $newImageName, $id];
} elseif ($stock_action === 'out') {
    $fields .= ', last_stock_out_reason = ?';
    $sql = 'UPDATE fish_species SET ' . $fields . ' WHERE fish_id = ?';
    $bindTypes = 'ssdissssi';
    $bindValues = [$name, $local, $price, $stock, $harvest, $desc, $status, $stock_reason, $id];
}

$up = $conn->prepare($sql);
if (!$up) {
    ob_clean();
    echo json_encode(['ok' => 0, 'msg' => 'Prepare failed']);
    exit;
}
$up->bind_param($bindTypes, ...$bindValues);
$ok = $up->execute();
if (!$ok) {
    error_log('[fish_update] SQL ERROR: ' . $up->error);
}
error_log('[fish_update] SQL update executed. Description in DB should now be: ' . var_export($desc, true));
$up->close();
if ($ok) {
    // delete old image file if replaced
    if ($imageUpdated && $oldImage) {
        $oldPath = __DIR__ . '/../assets/img/fish_species/' . $oldImage;
        if (is_file($oldPath)) @unlink($oldPath);
    }
    
    if ($stock_action === 'out') {
        $conn->query("CREATE TABLE IF NOT EXISTS fish_stock_out_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fish_id INT NOT NULL,
            fish_name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            reason TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $outQty = 0;
        if ($oldStock !== null) {
            $outQty = max(0, $oldStock - $stock);
        }
        $logStmt = $conn->prepare('INSERT INTO fish_stock_out_logs (fish_id, fish_name, quantity, reason) VALUES (?, ?, ?, ?)');
        if ($logStmt) {
            $logStmt->bind_param('isis', $id, $name, $outQty, $stock_reason);
            $logStmt->execute();
            $logStmt->close();
        }
    }
    
    // Log the activity using correct session variables BEFORE sending response
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    $user_type = $_SESSION['role'] ?? 'staff';
    
    error_log('[fish_update] About to log activity for user_id=' . $user_id . ' | user_type=' . $user_type . ' | fish_id=' . $id . ' | name=' . $name);
    
    // Ensure connection is still valid
    if (!$conn) {
        error_log('[fish_update] ERROR: Connection lost before logging!');
        ob_clean();
        echo json_encode(['ok' => 0, 'msg' => 'Database connection lost']);
        exit;
    }
    
    // Build more specific description
    $changes = [];
    if (!empty($name)) $changes[] = "Name: $name";
    if (!empty($price)) $changes[] = "Price: ₱$price/kg";
    if (!empty($stock)) $changes[] = "Stock: $stock";
    if (!empty($status)) $changes[] = "Status: " . ucfirst($status);
    $specificDesc = "Updated fish species " . implode(" | ", $changes);
    
    $activityType = 'EDIT';
    $oldValues = null;
    $newValues = [
        'name' => $name,
        'price_per_kg' => $price,
        'stock' => $stock,
        'status' => $status
    ];
    if ($stock_action !== '') {
        $activityType = 'RESTOCK';
        $qty = null;
        if ($oldStock !== null) {
            $qty = abs($stock - $oldStock);
            $oldValues = ['stock' => $oldStock];
        }
        $direction = $stock_action === 'out' ? '-' : '+';
        $actionText = $stock_action === 'out' ? 'Stock-Out' : 'Stock-In';
        $specificDesc = $actionText . ' fish species ' . ($qty !== null ? "$direction{$qty} units. " : '') . 'New stock: ' . $stock . '.';
        if ($stock_action === 'out' && $stock_reason !== '') {
            $specificDesc .= ' Reason: ' . $stock_reason;
            $newValues['stock_reason'] = $stock_reason;
        }
    }
    $result = logActivity(
        $conn,
        $user_id,
        $user_type,
        $activityType,
        'fish_species',
        $id,
        $name,
        $specificDesc,
        $oldValues,
        $newValues
    );
    
    error_log('[fish_update] Activity log result: ' . ($result ? 'success' : 'failed'));
    
    if (!$result) {
        error_log('[fish_update] WARNING: logActivity returned false for fish_id=' . $id);
    }
    
    ob_clean();
    echo json_encode(['ok' => 1]);
} else {
    error_log('[fish_update] Update execute failed: ' . $up->error);
    // if DB update failed and we saved a new image, remove it to avoid orphan
    if ($imageUpdated) {
        @unlink(__DIR__ . '/../assets/img/fish_species/' . $newImageName);
    }
    ob_clean();
    echo json_encode(['ok' => 0, 'msg' => 'Update failed: ' . $up->error]);
}
