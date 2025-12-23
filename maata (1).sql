-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 09:10 AM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `maata`
--

-- --------------------------------------------------------

--
-- Table structure for table `availability`
--

CREATE TABLE `availability` (
  `id` int(11) NOT NULL,
  `available_date` date NOT NULL,
  `available_time_start` time NOT NULL,
  `available_time_end` time NOT NULL,
  `max_capacity` int(11) DEFAULT 50,
  `current_reservations` int(11) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_bookings`
--

CREATE TABLE `calendar_bookings` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `fish_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password` varchar(45) DEFAULT NULL,
  `customer_type` enum('online_customer','diner') DEFAULT 'online_customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `email`, `phone`, `address`, `barangay`, `municipality`, `created_at`, `updated_at`, `password`, `customer_type`) VALUES
(8, 'roneil', 'bansas', 'rocasjose77@gmail.com', '09454739388', 'Purok 2, Pob. Mahayag Zamboanga del Sur', 'poblacion', 'mahayag', '2025-12-20 17:33:57', '2025-12-22 07:19:34', 'bbfb69514dcc79fe1ad2a150ae16bf3a029a173b', 'online_customer'),
(9, 'roneil', 'bansas', 'roneilbansas5222@gmail.com', '09454739384', 'Purok 2, Pob. Mahayag Zamboanga del Sur', '', '', '2025-12-20 17:40:07', '2025-12-22 05:33:45', 'bbfb69514dcc79fe1ad2a150ae16bf3a029a173b', 'diner');

-- --------------------------------------------------------

--
-- Table structure for table `farm_info`
--

CREATE TABLE `farm_info` (
  `id` int(11) NOT NULL,
  `farm_name` varchar(100) NOT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `location_barangay` varchar(100) DEFAULT NULL,
  `location_municipality` varchar(100) DEFAULT NULL,
  `location_province` varchar(100) DEFAULT NULL,
  `location_region` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `farm_size_hectares` decimal(5,2) DEFAULT NULL,
  `water_system` text DEFAULT NULL,
  `established_year` int(11) DEFAULT NULL,
  `dining_service_started` date DEFAULT NULL,
  `facebook_page` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `farm_info`
--

INSERT INTO `farm_info` (`id`, `farm_name`, `owner_name`, `location_barangay`, `location_municipality`, `location_province`, `location_region`, `phone`, `email`, `farm_size_hectares`, `water_system`, `established_year`, `dining_service_started`, `facebook_page`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Maata Fish Farm', 'Rogelio Maata', 'New Basak', 'Dumingag', 'Zamboanga del Sur', 'Mindanao', '+63-XXXXXXXXX', 'maatafishfarm@gmail.com', '2.00', 'Diesel-powered water pump system', 2018, '2024-05-01', 'https://facebook.com/maatafishfarm', 'Family-owned aquaculture farm and restaurant specializing in fresh fish and authentic Filipino cuisine', '2025-12-15 03:39:55', '2025-12-15 03:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `fish_species`
--

CREATE TABLE `fish_species` (
  `fish_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `local_name` varchar(100) DEFAULT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `harvest_schedule` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `fish_species`
--

INSERT INTO `fish_species` (`fish_id`, `name`, `local_name`, `price_per_kg`, `stock`, `harvest_schedule`, `description`, `status`, `created_at`, `updated_at`, `image`) VALUES
(5, 'Tilapia', '', '200.00', 497, '', 'A widely farmed freshwater fish known for its mild flavor, fast growth, and high protein content, making it a staple in global aquaculture. It is hardy, adaptable to different water conditions, and primarily herbivorous, feeding on algae and plants. While popular for food, tilapia can become invasive if introduced into non-native ecosystems, outcompeting local species.', 'available', '2025-12-20 15:37:31', '2025-12-22 05:51:45', '1766245871_813cc3af268a.jpg'),
(6, 'Catfish (Hito)', '', '200.00', 490, '', 'A bottom-feeding fish often identified by its long, whisker-like barbels around the mouth, which it uses to sense food in murky water. It has smooth, scaleless skin and comes in various sizes, from small species kept in aquariums to large river giants. Valued in aquaculture and fishing, catfish are hardy and adaptable, living in freshwater and some brackish environments worldwide.', 'available', '2025-12-20 15:50:12', '2025-12-22 07:19:34', 'fish_6_6946c5b46696b.jpg'),
(7, 'Koi', '', '200.00', 585, '', 'A domesticated ornamental variety of the common carp, prized for its vibrant colors and elegant patterns, often seen in decorative outdoor ponds. Koi symbolize good fortune, perseverance, and beauty in many cultures, especially in Japan. They are social, long-living fish that can grow quite large, and their care requires clean, well-maintained water and ample space.', 'available', '2025-12-20 15:51:48', '2025-12-22 06:01:18', 'fish_7_6946c614bd74a.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `last_update` date DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 10,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `menu_orders`
--

CREATE TABLE `menu_orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(40) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `customer_contact` varchar(150) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','confirmed','paid','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `menu_order_items`
--

CREATE TABLE `menu_order_items` (
  `id` int(11) NOT NULL,
  `menu_order_id` int(11) NOT NULL,
  `item_type` enum('fish','product') NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `pickup_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','paid','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_date`, `pickup_date`, `total_amount`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(7, 'ORD17663816844949657', 8, '2025-12-22 05:34:44', '2025-12-22', '1800.00', 'pending', 'Online order from website', '2025-12-22 05:34:44', '2025-12-22 05:52:14'),
(13, 'ORD17663879739934655', 8, '2025-12-22 07:19:33', '2025-12-22', '200.00', 'confirmed', 'Online order from website', '2025-12-22 07:19:33', '2025-12-22 07:19:48');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 7, 7, 9, '200.00', '1800.00'),
(2, 12, 6, 1, '200.00', '200.00'),
(3, 13, 6, 1, '200.00', '200.00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('fish','food','snack','drink') NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` enum('kg','piece','order','pcs') DEFAULT 'kg',
  `stock_quantity` int(11) DEFAULT 0,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `description`, `image`, `price`, `unit`, `stock_quantity`, `status`, `created_at`, `updated_at`) VALUES
(21, 'Deep-Fry Hito', 'food', 'Crispy deep-fried catfish', '21_6947c3c2848f5.jpg', '250.00', 'order', 60, 'available', '2025-12-21 09:54:10', '2025-12-21 10:03:37'),
(22, 'Adobo Hito', 'food', 'Traditional Filipino adobo', '22_6947c6b333bfd.jpg', '140.00', 'order', 100, 'available', '2025-12-21 10:06:43', '2025-12-21 10:06:43'),
(23, 'Sisig', 'food', 'Sizzling hot sisig', '23_6947c74eeb08a.jpg', '130.00', 'order', 50, 'available', '2025-12-21 10:09:18', '2025-12-21 10:09:18'),
(24, 'French Fries', 'snack', 'Golden crispy fries', '24_6947c79fd82bc.jpg', '60.00', 'order', 150, 'available', '2025-12-21 10:10:39', '2025-12-21 10:10:39'),
(25, 'Coke', 'drink', 'Various cold beverages', '25_6947c7db6e9d8.jpg', '15.00', 'pcs', 150, 'available', '2025-12-21 10:11:39', '2025-12-21 10:11:39'),
(26, 'Lumpia', 'food', 'Traditional Limpua', '26_6947c831e078b.webp', '50.00', 'order', 50, 'available', '2025-12-21 10:13:05', '2025-12-21 10:13:05');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `filename`, `is_main`, `created_at`) VALUES
(0, 19, '19_6947bb14ba911.jpg', 1, '2025-12-21 09:17:08'),
(2, 17, '17_6942d30913b02.jpg', 1, '2025-12-17 15:58:01'),
(3, 18, '18_6942d45dc8fc1.webp', 1, '2025-12-17 16:03:41');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `reservation_number` varchar(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `reservation_type` enum('dine-in','farm visit','private-events') DEFAULT NULL,
  `num_guests` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','completed','cancelled','confirmed') DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `reservation_number`, `customer_id`, `reservation_type`, `num_guests`, `reservation_date`, `reservation_time`, `special_requests`, `status`, `contact_phone`, `contact_email`, `created_at`, `updated_at`) VALUES
(1, 'RES-20251222-2515', 9, 'dine-in', 20, '2025-12-22', '12:43:00', 'birthday', 'pending', '09454739384', 'roneilbansas5222@gmail.com', '2025-12-22 04:43:20', '2025-12-22 04:43:20'),
(2, 'RES-20251222-4514', 9, 'dine-in', 30, '2025-12-22', '12:59:00', 'asdf', 'pending', '09454739384', 'roneilbansas5222@gmail.com', '2025-12-22 04:59:42', '2025-12-22 04:59:42'),
(3, 'RES-20251222-7660', 9, 'private-events', 30, '2025-12-22', '13:15:00', 'adf', 'pending', '09454739384', 'roneilbansas5222@gmail.com', '2025-12-22 05:15:26', '2025-12-22 05:15:26'),
(4, 'RES-20251222-1312', 9, 'dine-in', 2, '2025-12-22', '13:33:00', 'dfgdfgfdgdfg', 'confirmed', '09454739384', 'roneilbansas5222@gmail.com', '2025-12-22 05:33:45', '2025-12-22 05:43:21');

-- --------------------------------------------------------

--
-- Table structure for table `reservation_items`
--

CREATE TABLE `reservation_items` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `special_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `revenue_reports`
--

CREATE TABLE `revenue_reports` (
  `id` int(11) NOT NULL,
  `report_month` date NOT NULL,
  `dining_revenue` decimal(10,2) DEFAULT 0.00,
  `fish_sales_revenue` decimal(10,2) DEFAULT 0.00,
  `nursery_revenue` decimal(10,2) DEFAULT 0.00,
  `event_revenue` decimal(10,2) DEFAULT 0.00,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `expenses` decimal(10,2) DEFAULT 0.00,
  `net_profit` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sales_reports`
--

CREATE TABLE `sales_reports` (
  `id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `order_count` int(11) DEFAULT 0,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `total_customers` int(11) DEFAULT 0,
  `average_order_value` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','staff','manager') DEFAULT 'staff',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@maatafishfarm.com', '$2y$10$Kg.HBTXfi21R6HY4XfGPIuSOVn1tgsuX1S8RlFtF2UeVgQHqAoZ2i', 'admin', 'admin', 'active', '2025-12-15 03:39:55', '2025-12-17 15:05:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `availability`
--
ALTER TABLE `availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date_time` (`available_date`,`available_time_start`),
  ADD KEY `idx_available_date` (`available_date`);

--
-- Indexes for table `calendar_bookings`
--
ALTER TABLE `calendar_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_booking_date` (`booking_date`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`customer_id`,`fish_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_product` (`fish_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `farm_info`
--
ALTER TABLE `farm_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fish_species`
--
ALTER TABLE `fish_species`
  ADD PRIMARY KEY (`fish_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product` (`product_id`);

--
-- Indexes for table `menu_orders`
--
ALTER TABLE `menu_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_admin_id` (`admin_id`);

--
-- Indexes for table `menu_order_items`
--
ALTER TABLE `menu_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_menu_order` (`menu_order_id`),
  ADD KEY `idx_item` (`item_type`,`item_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_delivery_date` (`pickup_date`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reservation_number` (`reservation_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reservation_date` (`reservation_date`);

--
-- Indexes for table `reservation_items`
--
ALTER TABLE `reservation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `revenue_reports`
--
ALTER TABLE `revenue_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_report_month` (`report_month`);

--
-- Indexes for table `sales_reports`
--
ALTER TABLE `sales_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_report_date` (`report_date`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `fish_species`
--
ALTER TABLE `fish_species`
  MODIFY `fish_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `menu_orders`
--
ALTER TABLE `menu_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_order_items`
--
ALTER TABLE `menu_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_order_items`
--
ALTER TABLE `menu_order_items`
  ADD CONSTRAINT `fk_menu_order_items_menu_orders` FOREIGN KEY (`menu_order_id`) REFERENCES `menu_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
