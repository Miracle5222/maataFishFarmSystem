<?php
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['cottage_id'])) {
    $cottage_id = intval($_GET['cottage_id']);

    $stmt = $conn->prepare("SELECT filename, is_main FROM cottage_images WHERE cottage_id = ? ORDER BY is_main DESC, id ASC");
    $stmt->bind_param("i", $cottage_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($images);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
?>