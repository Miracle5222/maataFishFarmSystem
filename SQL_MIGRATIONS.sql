-- ============================================================================
-- MAATA FISH FARM SYSTEM - DATABASE MIGRATIONS FOR ONLINE DEPLOYMENT
-- ============================================================================
-- Run these SQL commands in your hosting platform's phpMyAdmin or database tool
-- Recommended: Copy-paste entire file into phpMyAdmin SQL tab and execute
-- ============================================================================

-- ============================================================================
-- 1. CREATE AVAILABILITY_TABLES (For Table Management System)
-- ============================================================================
CREATE TABLE IF NOT EXISTS availability_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT NOT NULL DEFAULT 1,
    notes TEXT,
    status ENUM('available', 'not available') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. CREATE BOAT_INVENTORY TABLE (For Boat Management)
-- ============================================================================
CREATE TABLE IF NOT EXISTS boat_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    boat_name VARCHAR(100) NOT NULL UNIQUE,
    boat_type VARCHAR(50),
    capacity INT NOT NULL DEFAULT 1,
    rental_price DECIMAL(10, 2) DEFAULT 0,
    status ENUM('available', 'unavailable', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_boat_name (boat_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. CREATE BOAT_RENTALS TABLE (For Boat Rental Bookings)
-- ============================================================================
CREATE TABLE IF NOT EXISTS boat_rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    boat_name VARCHAR(100) NOT NULL,
    customer_id INT NULL,
    num_people INT DEFAULT 1,
    rental_date DATE NOT NULL,
    rental_time TIME,
    duration_hours INT DEFAULT 1,
    total_cost DECIMAL(10, 2) DEFAULT 0,
    status ENUM('pending', 'active', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_boat_name (boat_name),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_rental_date (rental_date),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. ALTER RESERVATIONS TABLE - Add table_id Column
-- ============================================================================
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS table_id INT NULL AFTER cottage_id;
ALTER TABLE reservations ADD INDEX IF NOT EXISTS idx_table_id (table_id);
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Optional: Add foreign key constraint for referential integrity
-- Uncomment if you want database to enforce table existence
-- ALTER TABLE reservations ADD CONSTRAINT fk_table_id FOREIGN KEY (table_id) REFERENCES availability_tables(id) ON DELETE SET NULL;

-- ============================================================================
-- 5. ALTER CUSTOMERS TABLE - Add Government ID Verification Columns
-- ============================================================================
ALTER TABLE customers ADD COLUMN IF NOT EXISTS government_id_verified TINYINT(1) DEFAULT 0;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS government_id_image VARCHAR(255) NULL;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ============================================================================
-- 6. OPTIONAL: Create ACTIVITY_LOGS TABLE (For Activity Tracking)
-- ============================================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(255),
    affected_id INT,
    affected_type VARCHAR(50),
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. VERIFY TABLES CREATED (Run these SELECT statements to verify)
-- ============================================================================
-- SELECT 'availability_tables' AS table_name, COUNT(*) as row_count FROM information_schema.TABLES WHERE TABLE_NAME='availability_tables' AND TABLE_SCHEMA=DATABASE();
-- SELECT 'boat_inventory' AS table_name, COUNT(*) as row_count FROM information_schema.TABLES WHERE TABLE_NAME='boat_inventory' AND TABLE_SCHEMA=DATABASE();
-- SELECT 'boat_rentals' AS table_name, COUNT(*) as row_count FROM information_schema.TABLES WHERE TABLE_NAME='boat_rentals' AND TABLE_SCHEMA=DATABASE();
-- SHOW COLUMNS FROM reservations WHERE Field IN ('table_id', 'updated_at');
-- SHOW COLUMNS FROM customers WHERE Field IN ('government_id_verified', 'government_id_image', 'created_at', 'updated_at');

-- ============================================================================
-- 7. ADD TOTAL_AMOUNT COLUMN TO RESERVATIONS (For Revenue Tracking)
-- ============================================================================
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10, 2) DEFAULT 0;

-- ============================================================================
-- EXECUTION INSTRUCTIONS:
-- ============================================================================
-- 1. Log into your hosting platform's cPanel
-- 2. Open phpMyAdmin
-- 3. Select your database (maata or similar)
-- 4. Click on "SQL" tab
-- 5. Copy-paste this entire file content
-- 6. Click "Go" to execute
-- 7. Wait for completion message
-- 8. Check "Verification" queries above to confirm all tables created
-- ============================================================================

-- END OF MIGRATIONS
