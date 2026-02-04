<?php
// handlers/fish_delete.php
require __DIR__ . '/../auth_admin.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/activity_logger.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => 0, 'msg' => 'Invalid request']);
    exit;
}
$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => 0, 'msg' => 'Invalid id']);
    exit;
}
$del = $conn->prepare('DELETE FROM fish_species WHERE fish_id = ?');
if (!$del) {
    echo json_encode(['ok' => 0, 'msg' => 'Prepare failed']);
    exit;
}

// Get fish name before deletion for logging
$getName = $conn->prepare('SELECT name FROM fish_species WHERE fish_id = ?');
if ($getName) {
    $getName->bind_param('i', $id);
    $getName->execute();
    $result = $getName->get_result();
    $fish = $result->fetch_assoc();
    $fish_name = $fish['name'] ?? 'Unknown Species';
    $getName->close();
}

$del->bind_param('i', $id);
$ok = $del->execute();
$del->close();
if ($ok) {
    // Log the activity
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_type = $_SESSION['role'] ?? 'staff';
    
    logActivity(
        $conn,
        $user_id,
        $user_type,
        'DELETE',
        'fish_species',
        $id,
        $fish_name,
        "Deleted fish species: $fish_name"
    );
    
    echo json_encode(['ok' => 1]);
} else {
    echo json_encode(['ok' => 0, 'msg' => 'Delete failed']);
}
