<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

$reservation_id = $_GET['id'] ?? null;

if (!$reservation_id || !is_numeric($reservation_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid reservation ID']);
    exit;
}

$stmt = $conn->prepare('SELECT r.id, r.reservation_number, r.reservation_date, r.reservation_time, r.reservation_type, r.num_guests, r.status, r.special_requests, r.contact_phone, r.contact_email, c.first_name, c.last_name FROM reservations r JOIN customers c ON r.customer_id = c.id WHERE r.id = ? LIMIT 1');

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

$stmt->bind_param('i', $reservation_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Reservation not found']);
    exit;
}

$reservation = $res->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'reservation' => $reservation
]);
?>
