<?php
/**
 * Boat Rental Handler
 * Handles rental completion, cancellation, and status updates
 */

include '../auth_admin.php';
require '../config/db.php';

// Check authorization
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_POST['action'] ?? '';
$rental_id = intval($_POST['rental_id'] ?? 0);

if ($action === 'complete_rental' && $rental_id > 0) {
    // Get rental details
    $rental = $conn->prepare("SELECT * FROM boat_rentals WHERE id = ?");
    $rental->bind_param('i', $rental_id);
    $rental->execute();
    $rental_data = $rental->get_result()->fetch_assoc();
    $rental->close();
    
    if (!$rental_data) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Rental not found']);
        exit;
    }
    
    // Update rental status to completed
    $update = $conn->prepare("UPDATE boat_rentals SET status = 'completed' WHERE id = ?");
    $update->bind_param('i', $rental_id);
    
    if ($update->execute()) {
        // Update boat status back to available
        $boat_update = $conn->prepare("UPDATE boat_inventory SET status = 'available' WHERE boat_name = ?");
        $boat_update->bind_param('s', $rental_data['boat_name']);
        $boat_update->execute();
        $boat_update->close();
        
        // Log activity
        require '../handlers/activity_logger.php';
        logActivity(
            $conn,
            $_SESSION['user_id'],
            $_SESSION['role'],
            'UPDATE',
            'boat_rentals',
            $rental_id,
            'Boat Rental Completion',
            "Marked rental ID {$rental_id} as completed. Boat: {$rental_data['boat_name']}",
            null,
            ['rental_id' => $rental_id, 'boat_name' => $rental_data['boat_name']]
        );
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Rental marked as completed']);
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update rental']);
    }
    $update->close();
} 
elseif ($action === 'cancel_rental' && $rental_id > 0) {
    // Get rental details
    $rental = $conn->prepare("SELECT * FROM boat_rentals WHERE id = ?");
    $rental->bind_param('i', $rental_id);
    $rental->execute();
    $rental_data = $rental->get_result()->fetch_assoc();
    $rental->close();
    
    if (!$rental_data) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Rental not found']);
        exit;
    }
    
    // Only allow cancellation if pending or active
    if (!in_array($rental_data['status'], ['pending', 'active'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot cancel completed or already cancelled rentals']);
        exit;
    }
    
    // Update rental status to cancelled
    $update = $conn->prepare("UPDATE boat_rentals SET status = 'cancelled' WHERE id = ?");
    $update->bind_param('i', $rental_id);
    
    if ($update->execute()) {
        // Update boat status back to available
        $boat_update = $conn->prepare("UPDATE boat_inventory SET status = 'available' WHERE boat_name = ?");
        $boat_update->bind_param('s', $rental_data['boat_name']);
        $boat_update->execute();
        $boat_update->close();
        
        // Log activity
        require '../handlers/activity_logger.php';
        logActivity(
            $conn,
            $_SESSION['user_id'],
            $_SESSION['role'],
            'UPDATE',
            'boat_rentals',
            $rental_id,
            'Boat Rental Cancellation',
            "Cancelled rental ID {$rental_id}. Boat: {$rental_data['boat_name']}",
            null,
            ['rental_id' => $rental_id, 'boat_name' => $rental_data['boat_name'], 'reason' => $_POST['reason'] ?? '']
        );
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Rental cancelled successfully']);
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to cancel rental']);
    }
    $update->close();
}
else {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>
