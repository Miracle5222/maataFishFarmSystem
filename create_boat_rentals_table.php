<?php
/**
 * Create boat_rentals table and boat_rental_rates settings table
 * Initializes boat rental system for the farm
 */

require 'config/db.php';

// 1. Create boat_rentals table
$sql_boat_rentals = "CREATE TABLE IF NOT EXISTS `boat_rentals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `boat_name` VARCHAR(100) NOT NULL,
  `rental_start` DATETIME NOT NULL,
  `rental_end` DATETIME,
  `hours_rented` DECIMAL(5,2) DEFAULT 0,
  `hourly_rate` DECIMAL(10,2) DEFAULT 100.00,
  `total_amount` DECIMAL(10,2) DEFAULT 0,
  `status` ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// 2. Create boat_rental_rates settings table
$sql_rates = "CREATE TABLE IF NOT EXISTS `boat_rental_rates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rate_type` VARCHAR(50) UNIQUE NOT NULL,
  `hourly_rate` DECIMAL(10,2) NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// 3. Create boat_inventory table (to track available boats)
$sql_boats = "CREATE TABLE IF NOT EXISTS `boat_inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `boat_name` VARCHAR(100) NOT NULL UNIQUE,
  `boat_type` VARCHAR(50),
  `capacity` INT DEFAULT 5,
  `description` TEXT,
  `status` ENUM('available', 'rented', 'maintenance', 'inactive') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    // Execute boat_rentals table creation
    if ($conn->query($sql_boat_rentals)) {
        echo "✅ boat_rentals table created successfully!<br>";
    } else {
        echo "❌ Error creating boat_rentals table: " . $conn->error . "<br>";
    }

    // Execute rates table creation
    if ($conn->query($sql_rates)) {
        echo "✅ boat_rental_rates table created successfully!<br>";
    } else {
        echo "❌ Error creating boat_rental_rates table: " . $conn->error . "<br>";
    }

    // Execute boat inventory table creation
    if ($conn->query($sql_boats)) {
        echo "✅ boat_inventory table created successfully!<br>";
    } else {
        echo "❌ Error creating boat_inventory table: " . $conn->error . "<br>";
    }

    // Insert default hourly rate
    $check_rate = $conn->query("SELECT * FROM boat_rental_rates WHERE rate_type = 'standard'");
    if ($check_rate->num_rows == 0) {
        $insert_rate = "INSERT INTO boat_rental_rates (rate_type, hourly_rate, updated_by) VALUES ('standard', 100.00, 0)";
        if ($conn->query($insert_rate)) {
            echo "✅ Default hourly rate (₱100/hour) inserted successfully!<br>";
        } else {
            echo "❌ Error inserting default rate: " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ️  Default hourly rate already exists.<br>";
    }

    // Insert sample boats (optional)
    $sample_boats = [
        "('Speedboat 1', 'Speed Boat', 4, 'High-speed boat for 4 passengers', 'available')",
        "('Fishing Boat', 'Fishing Boat', 6, 'Traditional fishing boat', 'available')",
        "('Paddleboat', 'Paddle Boat', 3, 'Peaceful paddle boat ride', 'available')"
    ];

    foreach ($sample_boats as $boat) {
        $insert_boat = "INSERT IGNORE INTO boat_inventory (boat_name, boat_type, capacity, description, status) VALUES " . $boat;
        $conn->query($insert_boat);
    }
    echo "✅ Sample boats inserted (if not already existing).<br><br>";

    echo "<strong style='color:green;'>Boat Rental System Tables Created Successfully!</strong>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

$conn->close();
?>
