<?php
// Set output type first before anything else
if (isset($_GET['action']) && $_GET['action'] === 'get') {
    header('Content-Type: application/json');
}

include '../config/db.php';
include '../auth_admin.php';
include 'activity_logger.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $cottage_number = trim($_POST['cottage_number']);
        $price = (float) $_POST['price'];
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        $start_times = $_POST['start_times'] ?? [];
        $end_times = $_POST['end_times'] ?? [];
        $status = $_POST['status'];

        // Log incoming data
        error_log("ADD COTTAGE - Number: $cottage_number, Price: $price, From: $date_from, To: $date_to, Status: $status");
        error_log("Start times: " . json_encode($start_times) . ", End times: " . json_encode($end_times));

        if (empty($cottage_number) || $price <= 0 || empty($date_from) || empty($date_to) || empty($start_times[0]) || empty($end_times[0])) {
            error_log("Validation failed - missing required fields");
            $redirect = $_SERVER['HTTP_REFERER'] ?? '../cottage_management.php?error=Invalid data';
            header('Location: ' . $redirect);
            exit;
        }

        // Validate date range
        if (strtotime($date_from) > strtotime($date_to)) {
            $redirect = $_SERVER['HTTP_REFERER'] ?? '../cottage_management.php?error=Invalid date range';
            header('Location: ' . $redirect);
            exit;
        }

        // Ensure cottages table exists and has the necessary columns
        $conn->query('CREATE TABLE IF NOT EXISTS cottages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cottage_number VARCHAR(50) NOT NULL UNIQUE,
            price DECIMAL(10,2) NOT NULL,
            available_date DATE NOT NULL,
            available_time_start TIME NOT NULL,
            available_time_end TIME NOT NULL,
            status ENUM("available", "unavailable") DEFAULT "available",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )');

        // Add date range columns if they don't exist
        $check_from = $conn->query("SHOW COLUMNS FROM cottages LIKE 'available_date_from'");
        if ($check_from->num_rows == 0) {
            $conn->query("ALTER TABLE cottages ADD COLUMN available_date_from DATE AFTER available_date");
        }
        
        $check_to = $conn->query("SHOW COLUMNS FROM cottages LIKE 'available_date_to'");
        if ($check_to->num_rows == 0) {
            $conn->query("ALTER TABLE cottages ADD COLUMN available_date_to DATE AFTER available_date_from");
        }

        // Insert cottage (use first date/time as the main record, store date range)
        $stmt = $conn->prepare('INSERT INTO cottages (cottage_number, price, available_date, available_date_from, available_date_to, available_time_start, available_time_end, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            $redirect = $_SERVER['HTTP_REFERER'] ?? '../cottage_management.php?error=Database error';
            header('Location: ' . $redirect);
            exit;
        }
        $stmt->bind_param('sdssssss', $cottage_number, $price, $date_from, $date_from, $date_to, $start_times[0], $end_times[0], $status);
        if (!$stmt->execute()) {
            $redirect = $_SERVER['HTTP_REFERER'] ?? '../cottage_management.php?error=Failed to add cottage';
            header('Location: ' . $redirect);
            exit;
        }
        $cottage_id = $stmt->insert_id;
        $stmt->close();

        // Create availability records for date range and all time slots
        $current_date = new DateTime($date_from);
        $end_date = new DateTime($date_to);
        $end_date->modify('+1 day'); // Include the end date
        
        // Ensure cottage_availability table exists
        $conn->query('CREATE TABLE IF NOT EXISTS cottage_availability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cottage_id INT NOT NULL,
            available_date DATE NOT NULL,
            available_time_start TIME NOT NULL,
            available_time_end TIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cottage_id) REFERENCES cottages(id) ON DELETE CASCADE
        )');
        
        // Insert availability records for each date and each time slot
        $avail_stmt = $conn->prepare('INSERT INTO cottage_availability (cottage_id, available_date, available_time_start, available_time_end) VALUES (?, ?, ?, ?)');
        
        if (!$avail_stmt) {
            $redirect = $_SERVER['HTTP_REFERER'] ?? '../cottage_management.php?error=Database error';
            header('Location: ' . $redirect);
            exit;
        }
        
        while ($current_date < $end_date) {
            $date_str = $current_date->format('Y-m-d');
            
            // Add availability for each time slot
            foreach ($start_times as $index => $start_time) {
                if (!empty($start_time) && !empty($end_times[$index])) {
                    $avail_stmt->bind_param('isss', $cottage_id, $date_str, $start_time, $end_times[$index]);
                    if (!$avail_stmt->execute()) {
                        $redirect = $_SERVER['HTTP_REFERER'] ?? '../cottage_management.php?error=Failed to create availability';
                        header('Location: ' . $redirect);
                        exit;
                    }
                }
            }
            
            $current_date->modify('+1 day');
        }
        $avail_stmt->close();

        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = '../assets/img/cottages/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Ensure cottage_images table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'cottage_images'");
            if ($table_check->num_rows == 0) {
                $create_table_sql = 'CREATE TABLE cottage_images (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    cottage_id INT NOT NULL,
                    filename VARCHAR(500) NOT NULL,
                    is_main TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )';
                if (!$conn->query($create_table_sql)) {
                    die('Table creation failed: ' . $conn->error);
                }
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = basename($_FILES['images']['name'][$key]);
                    $file_path = $upload_dir . uniqid() . '_' . $file_name;
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $stmt = $conn->prepare('INSERT INTO cottage_images (cottage_id, filename) VALUES (?, ?)');
                        if (!$stmt) {
                            die('Prepare failed: ' . $conn->error);
                        }
                        $stmt->bind_param('is', $cottage_id, basename($file_path));
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        // Use HTTP_REFERER or fall back to default
        $redirect = $_SERVER['HTTP_REFERER'] ?? '../cottage_management.php?success=1';
        if (strpos($redirect, '?') !== false) {
            $redirect = strstr($redirect, '?', true) . '?success=1';
        } else {
            $redirect = '../cottage_management.php?success=1';
        }
        // Log the activity
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_type = $_SESSION['role'] ?? 'staff';
        logActivity(
            $conn,
            $user_id,
            $user_type,
            'CREATE',
            'cottage',
            $cottage_id,
            $cottage_number,
            "Created cottage #$cottage_number - Price: ₱$price/night | Status: " . ucfirst($status) . " | Available from $available_date_from to $available_date_to"
        );
        header('Location: ' . $redirect);
        break;

    case 'edit':
        $id = (int) $_POST['id'];
        $cottage_number = trim($_POST['cottage_number']);
        $price = (float) $_POST['price'];
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        $start_times = $_POST['start_times'] ?? [];
        $end_times = $_POST['end_times'] ?? [];
        $status = $_POST['status'];

        if (empty($cottage_number) || $price <= 0 || $id <= 0 || empty($date_from) || empty($date_to) || empty($start_times[0]) || empty($end_times[0])) {
            header('Location: ../cottage_management.php?error=Invalid data');
            exit;
        }

        // Validate date range
        if (strtotime($date_from) > strtotime($date_to)) {
            header('Location: ../cottage_management.php?error=Invalid date range');
            exit;
        }

        // Add date range columns if they don't exist
        $check_from = $conn->query("SHOW COLUMNS FROM cottages LIKE 'available_date_from'");
        if ($check_from->num_rows == 0) {
            $conn->query("ALTER TABLE cottages ADD COLUMN available_date_from DATE AFTER available_date");
        }
        
        $check_to = $conn->query("SHOW COLUMNS FROM cottages LIKE 'available_date_to'");
        if ($check_to->num_rows == 0) {
            $conn->query("ALTER TABLE cottages ADD COLUMN available_date_to DATE AFTER available_date_from");
        }

        // Update cottage basic info (use first date/time as the main record, store date range)
        $stmt = $conn->prepare('UPDATE cottages SET cottage_number = ?, price = ?, available_date = ?, available_date_from = ?, available_date_to = ?, available_time_start = ?, available_time_end = ?, status = ? WHERE id = ?');
        $stmt->bind_param('sdssssssi', $cottage_number, $price, $date_from, $date_from, $date_to, $start_times[0], $end_times[0], $status, $id);
        if (!$stmt->execute()) {
            header('Location: ../cottage_management.php?error=Failed to update cottage: ' . urlencode($stmt->error));
            exit;
        }
        $stmt->close();

        // Delete existing availability records for this cottage
        $del_stmt = $conn->prepare('DELETE FROM cottage_availability WHERE cottage_id = ?');
        $del_stmt->bind_param('i', $id);
        $del_stmt->execute();
        $del_stmt->close();

        // Create new availability records for date range and all time slots
        $current_date = new DateTime($date_from);
        $end_date = new DateTime($date_to);
        $end_date->modify('+1 day'); // Include the end date
        
        // Insert availability records for each date and each time slot
        $avail_stmt = $conn->prepare('INSERT INTO cottage_availability (cottage_id, available_date, available_time_start, available_time_end) VALUES (?, ?, ?, ?)');
        
        if (!$avail_stmt) {
            header('Location: ../cottage_management.php?error=Database error: ' . urlencode($conn->error));
            exit;
        }
        
        while ($current_date < $end_date) {
            $date_str = $current_date->format('Y-m-d');
            
            // Add availability for each time slot
            foreach ($start_times as $index => $start_time) {
                if (!empty($start_time) && !empty($end_times[$index])) {
                    $avail_stmt->bind_param('isss', $id, $date_str, $start_time, $end_times[$index]);
                    if (!$avail_stmt->execute()) {
                        header('Location: ../cottage_management.php?error=Failed to create availability: ' . urlencode($avail_stmt->error));
                        exit;
                    }
                }
            }
            
            $current_date->modify('+1 day');
        }
        $avail_stmt->close();

        // Handle new image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = '../assets/img/cottages/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Ensure cottage_images table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'cottage_images'");
            if ($table_check->num_rows == 0) {
                $create_table_sql = 'CREATE TABLE cottage_images (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    cottage_id INT NOT NULL,
                    filename VARCHAR(500) NOT NULL,
                    is_main TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )';
                if (!$conn->query($create_table_sql)) {
                    die('Table creation failed: ' . $conn->error);
                }
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = basename($_FILES['images']['name'][$key]);
                    $file_path = $upload_dir . uniqid() . '_' . $file_name;
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $stmt = $conn->prepare('INSERT INTO cottage_images (cottage_id, filename) VALUES (?, ?)');
                        if (!$stmt) {
                            die('Prepare failed: ' . $conn->error);
                        }
                        $stmt->bind_param('is', $id, basename($file_path));
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        // Log the activity
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_type = $_SESSION['role'] ?? 'staff';
        $cottage_number_display = '';
        $get_cottage_num = $conn->prepare('SELECT cottage_number FROM cottages WHERE id = ?');
        if ($get_cottage_num) {
            $get_cottage_num->bind_param('i', $id);
            $get_cottage_num->execute();
            $res = $get_cottage_num->get_result();
            if ($row = $res->fetch_assoc()) {
                $cottage_number_display = $row['cottage_number'];
            }
            $get_cottage_num->close();
        }
        logActivity(
            $conn,
            $user_id,
            $user_type,
            'EDIT',
            'cottage',
            $id,
            $cottage_number_display,
            "Updated cottage: $cottage_number_display"
        );

        header('Location: ../cottage_management.php?success=1');
        break;

    case 'delete':
        $id = (int) $_GET['id'];

        if ($id <= 0) {
            header('Location: ../cottage_management.php?error=Invalid ID');
            exit;
        }

        // Get cottage details before deleting
        $get_cottage = $conn->prepare('SELECT cottage_number FROM cottages WHERE id = ?');
        $cottage_number = '';
        if ($get_cottage) {
            $get_cottage->bind_param('i', $id);
            $get_cottage->execute();
            $res = $get_cottage->get_result();
            if ($row = $res->fetch_assoc()) {
                $cottage_number = $row['cottage_number'];
            }
            $get_cottage->close();
        }

        $stmt = $conn->prepare('DELETE FROM cottages WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        // Log the activity
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_type = $_SESSION['role'] ?? 'staff';
        logActivity(
            $conn,
            $user_id,
            $user_type,
            'DELETE',
            'cottage',
            $id,
            $cottage_number,
            "Deleted cottage: $cottage_number"
        );

        header('Location: ../cottage_management.php?success=1');
        break;

    case 'toggle_status':
        $id = (int) $_GET['id'];

        if ($id <= 0) {
            header('Location: ../cottage_management.php?error=Invalid ID');
            exit;
        }

        // Get current status
        $stmt = $conn->prepare('SELECT status FROM cottages WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cottage = $result->fetch_assoc();
        $stmt->close();

        if ($cottage) {
            $new_status = $cottage['status'] === 'available' ? 'unavailable' : 'available';
            $stmt = $conn->prepare('UPDATE cottages SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $new_status, $id);
            $stmt->execute();
            $stmt->close();
        }

        header('Location: ../cottage_management.php?success=1');
        break;

    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['error' => 'Invalid cottage ID']);
            exit;
        }
        
        // Get cottage details
        $stmt = $conn->prepare('SELECT c.*, COUNT(ci.id) as image_count FROM cottages c LEFT JOIN cottage_images ci ON c.id = ci.cottage_id WHERE c.id = ? GROUP BY c.id');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cottage = $result->fetch_assoc();
        $stmt->close();
        
        if (!$cottage) {
            echo json_encode(['error' => 'Cottage not found']);
            exit;
        }
        
        // Get availability records
        $avail_stmt = $conn->prepare('SELECT * FROM cottage_availability WHERE cottage_id = ? ORDER BY available_date ASC, available_time_start ASC');
        $avail_stmt->bind_param('i', $id);
        $avail_stmt->execute();
        $avail_result = $avail_stmt->get_result();
        
        $availability = [];
        while ($row = $avail_result->fetch_assoc()) {
            $availability[] = $row;
        }
        $avail_stmt->close();
        
        // Get images
        $img_stmt = $conn->prepare('SELECT filename FROM cottage_images WHERE cottage_id = ?');
        $img_stmt->bind_param('i', $id);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();
        
        $images = [];
        while ($row = $img_result->fetch_assoc()) {
            $images[] = $row['filename'];
        }
        $img_stmt->close();
        
        $cottage['availability'] = $availability;
        $cottage['images'] = $images;
        
        echo json_encode($cottage);
        exit;

    default:
        header('Location: ../cottage_management.php?error=Invalid action');
        break;
}
?>