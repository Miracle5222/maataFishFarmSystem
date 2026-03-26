<?php
session_start();
include '../config/db.php';
include 'activity_logger.php';

// Check admin/staff authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'manager'])) {
    header('Location: ../admin_login.php?error=unauthorized');
    exit;
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $reservation_id = intval($_GET['id']);

    try {
        // Get reservation details before deletion for logging
        $stmt = $conn->prepare("SELECT r.id, r.reservation_number, r.reservation_type, r.num_guests, r.reservation_date, r.reservation_time, c.first_name, c.last_name 
                               FROM reservations r
                               LEFT JOIN customers c ON r.customer_id = c.id
                               WHERE r.id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            header("Location: ../reservations_list.php?error=" . urlencode("Reservation not found"));
            exit;
        }

        $reservation = $result->fetch_assoc();
        $stmt->close();

        // Delete the reservation
        $delete_stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
        if (!$delete_stmt) {
            throw new Exception("Delete prepare failed: " . $conn->error);
        }

        $delete_stmt->bind_param("i", $reservation_id);
        if ($delete_stmt->execute()) {
            // Log the activity
            $user_id = $_SESSION['user_id'];
            $user_type = $_SESSION['role'];
            $customer_name = ($reservation['first_name'] ?? '') . ' ' . ($reservation['last_name'] ?? '');

            logActivity(
                $conn,
                $user_id,
                $user_type,
                'DELETE',
                'reservation',
                $reservation_id,
                $reservation['reservation_number'],
                "Deleted {$reservation['reservation_type']} reservation for {$customer_name} | Date: {$reservation['reservation_date']} | Time: {$reservation['reservation_time']}"
            );

            $delete_stmt->close();
            header("Location: ../reservations_list.php?success=1");
            exit;
        } else {
            throw new Exception("Delete failed: " . $delete_stmt->error);
        }
    } catch (Exception $e) {
        error_log("Reservation delete error: " . $e->getMessage());
        header("Location: ../reservations_list.php?error=" . urlencode("An error occurred while deleting the reservation: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../reservations_list.php?error=" . urlencode("Invalid reservation ID"));
    exit;
}
