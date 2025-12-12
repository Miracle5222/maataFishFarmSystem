-- Maata Fish Farm Database
-- Created for the fish farm management and booking system

-- Create Database
CREATE DATABASE IF NOT EXISTS maata_fish_farm;
USE maata_fish_farm;

-- =============================================
-- ADMIN USERS TABLE
-- =============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'staff', 'manager') DEFAULT 'staff',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- CUSTOMERS TABLE
-- =============================================
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    barangay VARCHAR(100),
    municipality VARCHAR(100),
    customer_type ENUM('fish_buyer', 'dining_customer', 'event_host', 'regular') DEFAULT 'regular',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_email (email)
);

-- =============================================
-- PRODUCTS TABLE (Fish and Food Items)
-- =============================================
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    category ENUM('fish', 'food', 'snack', 'drink') NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    unit ENUM('kg', 'piece', 'order', 'pcs') DEFAULT 'kg',
    stock_quantity INT DEFAULT 0,
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- =============================================
-- FISH SPECIES TABLE
-- =============================================
CREATE TABLE fish_species (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    local_name VARCHAR(100),
    price_per_kg DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    harvest_schedule VARCHAR(50),
    description TEXT,
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- ORDERS TABLE
-- =============================================
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_date DATE,
    delivery_address TEXT,
    total_amount DECIMAL(10, 2),
    status ENUM('pending', 'confirmed', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_delivery_date (delivery_date)
);

-- =============================================
-- ORDER ITEMS TABLE
-- =============================================
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- =============================================
-- RESERVATIONS TABLE
-- =============================================
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    reservation_type ENUM('dine-in', 'event', 'family-gathering', 'private-event') NOT NULL,
    num_guests INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_reservation_date (reservation_date)
);

-- =============================================
-- RESERVATIONS ITEMS TABLE
-- =============================================
CREATE TABLE reservation_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    special_notes TEXT,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- =============================================
-- STAFF TABLE
-- =============================================
CREATE TABLE staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    position VARCHAR(50),
    department VARCHAR(50),
    hire_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- CALENDAR BOOKINGS TABLE
-- =============================================
CREATE TABLE calendar_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT,
    order_id INT,
    booking_date DATE NOT NULL,
    booking_time TIME,
    event_type VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_booking_date (booking_date)
);

-- =============================================
-- AVAILABILITY TABLE
-- =============================================
CREATE TABLE availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    available_date DATE NOT NULL,
    available_time_start TIME NOT NULL,
    available_time_end TIME NOT NULL,
    max_capacity INT DEFAULT 50,
    current_reservations INT DEFAULT 0,
    is_available BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_available_date (available_date),
    UNIQUE KEY unique_date_time (available_date, available_time_start)
);

-- =============================================
-- SALES REPORTS TABLE
-- =============================================
CREATE TABLE sales_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_date DATE NOT NULL,
    order_count INT DEFAULT 0,
    total_sales DECIMAL(10, 2) DEFAULT 0,
    total_customers INT DEFAULT 0,
    average_order_value DECIMAL(10, 2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_report_date (report_date)
);

-- =============================================
-- REVENUE REPORTS TABLE
-- =============================================
CREATE TABLE revenue_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_month DATE NOT NULL,
    dining_revenue DECIMAL(10, 2) DEFAULT 0,
    fish_sales_revenue DECIMAL(10, 2) DEFAULT 0,
    nursery_revenue DECIMAL(10, 2) DEFAULT 0,
    event_revenue DECIMAL(10, 2) DEFAULT 0,
    total_revenue DECIMAL(10, 2) DEFAULT 0,
    expenses DECIMAL(10, 2) DEFAULT 0,
    net_profit DECIMAL(10, 2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_report_month (report_month)
);

-- =============================================
-- INVENTORY TABLE
-- =============================================
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    quantity INT DEFAULT 0,
    last_update DATE,
    reorder_level INT DEFAULT 10,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product (product_id)
);

-- =============================================
-- FARM INFORMATION TABLE
-- =============================================
CREATE TABLE farm_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_name VARCHAR(100) NOT NULL,
    owner_name VARCHAR(100),
    location_barangay VARCHAR(100),
    location_municipality VARCHAR(100),
    location_province VARCHAR(100),
    location_region VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    farm_size_hectares DECIMAL(5, 2),
    water_system TEXT,
    established_year INT,
    dining_service_started DATE,
    facebook_page VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- INSERT SAMPLE DATA
-- =============================================

-- Insert admin user
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@maatafishfarm.com', SHA2('password123', 256), 'Admin User', 'admin');

-- Insert farm information
INSERT INTO farm_info (
    farm_name, owner_name, location_barangay, location_municipality, 
    location_province, location_region, phone, email, farm_size_hectares, 
    water_system, established_year, dining_service_started, facebook_page, description
) VALUES (
    'Maata Fish Farm',
    'Rogelio Maata',
    'New Basak',
    'Dumingag',
    'Zamboanga del Sur',
    'Mindanao',
    '+63-XXXXXXXXX',
    'maatafishfarm@gmail.com',
    2.00,
    'Diesel-powered water pump system',
    2018,
    '2024-05-01',
    'https://facebook.com/maatafishfarm',
    'Family-owned aquaculture farm and restaurant specializing in fresh fish and authentic Filipino cuisine'
);

-- Insert fish species
INSERT INTO fish_species (name, local_name, price_per_kg, stock, harvest_schedule) VALUES
('Tilapia', 'Tilapia', 200.00, 500, 'Every 6 months'),
('Catfish', 'Hito', 200.00, 300, 'Every 6 months'),
('Japanese Koi', 'Japanese Koi', 200.00, 100, 'Available'),
('Fish Fry', 'Fingerlings', 50.00, 1000, 'Available from nursery');

-- Insert products
INSERT INTO products (name, category, description, price, unit, stock_quantity, status) VALUES
-- Fish
('Tilapia', 'fish', 'Fresh farm-raised tilapia', 200.00, 'kg', 500, 'available'),
('Catfish (Hito)', 'fish', 'Fresh farm-raised catfish', 200.00, 'kg', 300, 'available'),
('Japanese Koi', 'fish', 'Premium Japanese Koi', 200.00, 'kg', 100, 'available'),
('Fish Fry', 'fish', 'Fish fingerlings from nursery', 50.00, 'pcs', 1000, 'available'),

-- Food Items
('Deep-Fry Hito', 'food', 'Crispy deep-fried catfish', 150.00, 'order', 100, 'available'),
('Adobo Hito', 'food', 'Traditional Filipino adobo with catfish', 140.00, 'order', 100, 'available'),
('Native Chicken', 'food', 'Tender native chicken cooked to perfection', 120.00, 'order', 80, 'available'),
('Sisig', 'food', 'Sizzling hot sisig', 130.00, 'order', 90, 'available'),
('Calamares', 'food', 'Crispy fried squid with special sauce', 160.00, 'order', 70, 'available'),

-- Snacks
('French Fries', 'snack', 'Golden crispy fries', 60.00, 'order', 150, 'available'),
('Siomai', 'snack', 'Homemade dumplings', 50.00, 'order', 120, 'available'),
('Lumpia', 'snack', 'Crispy spring rolls', 55.00, 'order', 110, 'available'),

-- Drinks
('Softdrinks', 'drink', 'Various cold beverages', 30.00, 'order', 200, 'available'),
('Beer', 'drink', 'Selection of local and imported beers', 80.00, 'order', 150, 'available'),
('Juice', 'drink', 'Fresh natural juices', 40.00, 'order', 180, 'available');

-- =============================================
-- CREATE VIEWS FOR REPORTING
-- =============================================

-- Sales Summary View
CREATE VIEW sales_summary AS
SELECT 
    DATE(o.order_date) as sale_date,
    COUNT(DISTINCT o.id) as order_count,
    COUNT(DISTINCT o.customer_id) as customer_count,
    SUM(o.total_amount) as total_sales,
    AVG(o.total_amount) as avg_order_value
FROM orders o
WHERE o.status IN ('confirmed', 'delivered')
GROUP BY DATE(o.order_date)
ORDER BY sale_date DESC;

-- Monthly Revenue View
CREATE VIEW monthly_revenue AS
SELECT 
    DATE_TRUNC(CONCAT(YEAR(o.order_date), '-', MONTH(o.order_date), '-01')) as month,
    SUM(o.total_amount) as total_revenue
FROM orders o
WHERE o.status IN ('confirmed', 'delivered')
GROUP BY YEAR(o.order_date), MONTH(o.order_date)
ORDER BY month DESC;

-- Top Customers View
CREATE VIEW top_customers AS
SELECT 
    c.id,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    c.email,
    c.phone,
    COUNT(o.id) as order_count,
    SUM(o.total_amount) as total_spent
FROM customers c
LEFT JOIN orders o ON c.id = o.customer_id AND o.status IN ('confirmed', 'delivered')
GROUP BY c.id
ORDER BY total_spent DESC;

-- Reservation Schedule View
CREATE VIEW reservation_schedule AS
SELECT 
    r.id,
    r.reservation_number,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    c.phone,
    r.reservation_type,
    r.num_guests,
    r.reservation_date,
    r.reservation_time,
    r.status
FROM reservations r
JOIN customers c ON r.customer_id = c.id
ORDER BY r.reservation_date ASC, r.reservation_time ASC;

-- =============================================
-- CREATE INDEXES FOR OPTIMIZATION
-- =============================================
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_date ON orders(order_date);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_reservations_customer ON reservations(customer_id);
CREATE INDEX idx_reservations_date ON reservations(reservation_date);
CREATE INDEX idx_staff_status ON staff(status);
