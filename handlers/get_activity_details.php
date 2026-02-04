<?php
require __DIR__ . '/../config/db.php';
include __DIR__ . '/../auth_admin.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ID parameter']);
    exit;
}

$id = intval($_GET['id']);

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        id, user_id, user_type, user_name, activity_type, entity_type, entity_id, entity_name,
        description, old_values, new_values, timestamp, ip_address
    FROM activity_logs
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result && $row = $result->fetch_assoc()) {
    // Parse JSON fields
    $row['old_values'] = $row['old_values'] ? json_decode($row['old_values'], true) : null;
    $row['new_values'] = $row['new_values'] ? json_decode($row['new_values'], true) : null;

    // Resolve person name from the stored user_name field
    $person_name = $row['user_name'] ?? null;

    $row['person_name'] = !empty($person_name) ? $person_name : null; 

    // Format timestamp to 12-hour with am/pm
    $row['timestamp'] = date('M d, Y g:ia', strtotime($row['timestamp']));

    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Activity not found']);
}
?>
