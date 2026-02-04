<?php
include '../config/db.php';

header('Content-Type: application/json');

$cottage_id = isset($_GET['cottage_id']) ? intval($_GET['cottage_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (!$cottage_id || !$date) {
    echo json_encode(['booked_times' => []]);
    exit;
}

try {
    // Fetch booked time slots for the specific cottage and date
    $query = "SELECT DISTINCT reservation_time 
              FROM reservations 
              WHERE cottage_id = ? 
              AND reservation_date = ? 
              AND status IN ('confirmed', 'pending')
              ORDER BY reservation_time";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['booked_times' => [], 'error' => $conn->error]);
        exit;
    }
    
    $stmt->bind_param('is', $cottage_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_times = [];
    while ($row = $result->fetch_assoc()) {
        $booked_times[] = $row['reservation_time'];
    }
    
    $stmt->close();
    
    echo json_encode(['booked_times' => $booked_times]);
} catch (Exception $e) {
    echo json_encode(['booked_times' => [], 'error' => $e->getMessage()]);
}
?>
