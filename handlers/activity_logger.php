<?php
/**
 * Activity Logger Helper
 * Logs all admin and staff activities for audit trail
 */

function logActivity($conn, $user_id, $user_type, $activity_type, $entity_type, $entity_id, $entity_name, $description = '', $old_values = null, $new_values = null, $user_name = null) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // If user_name not provided, try to get it from session or database
        if (empty($user_name)) {
            if (!empty($_SESSION['user_name'])) {
                $user_name = $_SESSION['user_name'];
            } else {
                // Fallback: get from users table
                $stmt_user = $conn->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
                if ($stmt_user) {
                    $stmt_user->bind_param('i', $user_id);
                    $stmt_user->execute();
                    $result_user = $stmt_user->get_result();
                    if ($row_user = $result_user->fetch_assoc()) {
                        $user_name = $row_user['full_name'] ?? '';
                    }
                    $stmt_user->close();
                }
            }
        }
        
        $stmt = $conn->prepare("
            INSERT INTO activity_logs 
            (user_id, user_type, user_name, activity_type, entity_type, entity_id, entity_name, description, old_values, new_values, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            error_log("Activity logger prepare error: " . $conn->error);
            return false;
        }
        
        $old_json = $old_values ? json_encode($old_values) : null;
        $new_json = $new_values ? json_encode($new_values) : null;
        
        error_log("[activity_logger] About to bind params: user_id=$user_id, user_type=$user_type, user_name=$user_name");
        
        $stmt->bind_param(
            'issssisssss',
            $user_id,
            $user_type,
            $user_name,
            $activity_type,
            $entity_type,
            $entity_id,
            $entity_name,
            $description,
            $old_json,
            $new_json,
            $ip_address
        );
        
        error_log("[activity_logger] Bind params successful, executing INSERT...");
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("[activity_logger] Execute failed: " . $stmt->error);
        } else {
            error_log("[activity_logger] Execute successful, affected_rows=" . $stmt->affected_rows);
        }
        
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Activity logger exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Activity Types
 * EDIT - Modified an entity
 * DELETE - Deleted an entity
 * CREATE - Created a new entity
 * RESTOCK - Restocked/Updated inventory
 * APPROVE - Approved something (reservation, order, etc)
 * REJECT - Rejected something
 */
?>
