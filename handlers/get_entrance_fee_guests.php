g<?php
header('Content-Type: application/json; charset=utf-8');

include '../auth_admin.php';

// Check authorization
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require '../config/db.php';

$record_id = intval($_POST['record_id'] ?? 0);

if ($record_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
    exit;
}

// Fetch the activity log record
$stmt = $conn->prepare("SELECT new_values FROM activity_logs WHERE id = ? AND entity_type = 'entrance_fee' AND activity_type = 'CREATE'");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $record_id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query execution error: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    $stmt->close();
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

if (empty($row['new_values'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No guest data found for this record']);
    exit;
}

$new_values = json_decode($row['new_values'], true);
if (!$new_values) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error decoding guest data']);
    exit;
}

$guests = $new_values['guests'] ?? [];

// Return guest data as JSON
echo json_encode([
    'success' => true,
    'guests' => $guests,
    'count' => count($guests)
]);
exit;
?>
