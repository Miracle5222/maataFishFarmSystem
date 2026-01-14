-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2026 at 06:00 PM
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

--
-- Dumping data for table `availability`
--

INSERT INTO `availability` (`id`, `available_date`, `available_time_start`, `available_time_end`, `max_capacity`, `current_reservations`, `is_available`, `notes`, `created_at`, `updated_at`) VALUES
(4, '2025-12-22', '10:00:00', '20:00:00', 2, 0, 1, 'no kids', '2025-12-22 18:31:33', '2025-12-22 18:31:33'),
(5, '2025-12-23', '10:00:00', '20:00:00', 50, 0, 1, 'no kids', '2025-12-22 18:32:17', '2025-12-22 18:37:32'),
(6, '2025-12-23', '22:00:00', '23:00:00', 3, 0, 1, 'asdf', '2025-12-22 18:35:00', '2025-12-22 18:35:00'),
(7, '2025-12-27', '10:00:00', '20:00:00', 20, 0, 1, '', '2025-12-22 18:47:23', '2025-12-22 18:47:23'),
(8, '2025-12-28', '10:00:00', '20:00:00', 20, 0, 1, '', '2025-12-22 18:47:24', '2025-12-22 18:47:24'),
(9, '2026-01-03', '10:00:00', '20:00:00', 20, 0, 1, '', '2025-12-22 18:47:24', '2025-12-22 18:47:24'),
(10, '2026-01-04', '10:00:00', '20:00:00', 20, 0, 1, '', '2025-12-22 18:47:24', '2025-12-22 18:47:24'),
(11, '2026-01-10', '10:00:00', '20:00:00', 20, 0, 1, '', '2025-12-22 18:47:24', '2025-12-22 18:47:24'),
(12, '2026-01-11', '10:00:00', '20:00:00', 20, 0, 1, '', '2025-12-22 18:47:24', '2025-12-22 18:47:24'),
(13, '2026-01-17', '10:00:00', '20:00:00', 25, 0, 1, '', '2025-12-22 18:47:24', '2025-12-23 13:03:46');

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
  `fish_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) NOT NULL
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
(1, 'bing', 'bing', 'bing@gmail.com', '09454739384', 'Purok 2, Pob. Mahayag Zamboanga del Sur', NULL, NULL, '2026-01-06 17:41:19', '2026-01-07 11:29:04', 'bbfb69514dcc79fe1ad2a150ae16bf3a029a173b', 'online_customer'),
(2, 'bing', 'bing', 'bingbing@gmail.com', '09454739384', 'Purok 2, Pob. Mahayag Zamboanga del Sur', NULL, NULL, '2026-01-06 17:42:10', '2026-01-06 17:42:10', 'bbfb69514dcc79fe1ad2a150ae16bf3a029a173b', 'online_customer'),
(3, 'zebzeb', '', 'zebra@gmail.com', '09454739386', NULL, NULL, NULL, '2026-01-07 11:18:19', '2026-01-07 11:18:19', NULL, 'diner'),
(4, 'sam', 'sam', 'sam@gmail.com', '0945499878', 'purok 4', NULL, NULL, '2026-01-13 05:38:50', '2026-01-13 05:38:50', '2032dabadb4325aa4893c30e1fa284579a2e48fd', 'online_customer'),
(5, 'xam', 'maagad', 'xam@gmail.com', '09454739384', 'purok 4', NULL, NULL, '2026-01-13 05:42:24', '2026-01-13 05:42:24', '2032dabadb4325aa4893c30e1fa284579a2e48fd', 'online_customer'),
(6, 'xam', 'maagad', 'xammaagad@gmail.com', '09454939684', 'purok 2', NULL, NULL, '2026-01-13 05:45:56', '2026-01-13 05:45:56', '2032dabadb4325aa4893c30e1fa284579a2e48fd', 'online_customer'),
(7, 'xammaagad', '', 'xamaagad@gmail.com', '09454739384', NULL, NULL, NULL, '2026-01-13 05:47:41', '2026-01-13 05:47:41', NULL, 'diner'),
(8, 'roneil', 'bansas', 'roneilbansas5222@gmail.com', '09454739384', NULL, NULL, NULL, '2026-01-13 05:53:16', '2026-01-13 05:53:16', NULL, 'diner'),
(9, 'roger', 'roger', 'roger@gmail.com', '09454739384', 'Purok 2, Pob. Mahayag Zamboanga del Sur', NULL, NULL, '2026-01-13 06:01:22', '2026-01-13 06:01:22', 'f7c3bc1d808e04732adf679965ccc34ca7ae3441', 'online_customer'),
(10, 'roger', 'roger', 'roger@gmail.com', '09454739384', 'Purok 2, Pob. Mahayag Zamboanga del Sur', NULL, NULL, '2026-01-13 06:01:55', '2026-01-13 06:01:55', 'f7c3bc1d808e04732adf679965ccc34ca7ae3441', 'online_customer'),
(11, 'roger', 'roger', 'roger@gmail.com', '09454739384', 'Purok 2, Pob. Mahayag Zamboanga del Sur', NULL, NULL, '2026-01-13 06:03:53', '2026-01-13 06:03:53', 'f7c3bc1d808e04732adf679965ccc34ca7ae3441', 'online_customer'),
(12, 'xam', 'xam', 'xam@gmail.com', '09454739384', 'Purok 2, Pob. Mahayag Zamboanga del Sur', NULL, NULL, '2026-01-13 06:09:38', '2026-01-13 06:09:38', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'online_customer'),
(13, 'xam', 'xa', 'xam2@gmail.com', '09454739384', 'Purok 2, Pob. Mahayag Zamboanga del Sur', NULL, NULL, '2026-01-13 16:26:42', '2026-01-13 16:26:42', 'ccbe91b1f19bd31a1365363870c0eec2296a61c1', 'online_customer'),
(14, 'xam', 'xam', 'xamss@gmail.com', '09454739384', 'Purok 2, Pob. Mahayag Zamboanga del Sur', NULL, NULL, '2026-01-13 16:36:43', '2026-01-13 16:36:43', '601f1889667efaebb33b8c12572835da3f027f78', 'online_customer'),
(15, 'anderson', 'anderson', 'anderson123@gmail.com', '09454739384', 'Purok 2, Pob. Mahayag Zamboanga del Sur', NULL, NULL, '2026-01-13 16:58:13', '2026-01-13 16:58:43', '601f1889667efaebb33b8c12572835da3f027f78', 'online_customer');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL COMMENT 'Amount spent',
  `currency` varchar(3) DEFAULT 'USD',
  `transaction_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL COMMENT 'Brief description of what was purchased',
  `category` varchar(50) DEFAULT NULL COMMENT 'e.g., Food, Transport, Utilities, Unknown',
  `subcategory` varchar(50) DEFAULT NULL COMMENT 'More specific classification',
  `payment_method` enum('Cash','Card','Digital','Other') DEFAULT 'Other',
  `vendor` varchar(100) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `status` enum('Recorded','Reviewed','Categorized','Reimbursable') DEFAULT 'Recorded',
  `receipt_available` tinyint(1) DEFAULT 0,
  `receipt_image_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL COMMENT 'Additional context or clarification needed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `amount`, `currency`, `transaction_date`, `description`, `category`, `subcategory`, `payment_method`, `vendor`, `location`, `status`, `receipt_available`, `receipt_image_path`, `notes`, `created_at`, `updated_at`, `created_by`) VALUES
(3, '250.00', 'PHP', '2026-01-07', 'kaho', 'Maintenance', 'Equipment', 'Card', 'nadf', 'purok 2', 'Recorded', 1, 'assets/img/receipts/rcpt_695d53df1ee68.png', '', '2026-01-06 18:26:39', '2026-01-06 18:49:17', 'admin');

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
(5, 'Tilapia', '', '200.00', 496, '', 'A widely farmed freshwater fish known for its mild flavor, fast growth, and high protein content, making it a staple in global aquaculture. It is hardy, adaptable to different water conditions, and primarily herbivorous, feeding on algae and plants. While popular for food, tilapia can become invasive if introduced into non-native ecosystems, outcompeting local species.', 'available', '2025-12-20 15:37:31', '2026-01-07 11:29:06', '1766245871_813cc3af268a.jpg'),
(6, 'Catfish (Hito)', '', '200.00', 489, '', 'A bottom-feeding fish often identified by its long, whisker-like barbels around the mouth, which it uses to sense food in murky water. It has smooth, scaleless skin and comes in various sizes, from small species kept in aquariums to large river giants. Valued in aquaculture and fishing, catfish are hardy and adaptable, living in freshwater and some brackish environments worldwide.', 'available', '2025-12-20 15:50:12', '2025-12-22 09:00:33', 'fish_6_6946c5b46696b.jpg'),
(7, 'Koi', '', '200.00', 580, '', 'A domesticated ornamental variety of the common carp, prized for its vibrant colors and elegant patterns, often seen in decorative outdoor ponds. Koi symbolize good fortune, perseverance, and beauty in many cultures, especially in Japan. They are social, long-living fish that can grow quite large, and their care requires clean, well-maintained water and ample space.', 'available', '2025-12-20 15:51:48', '2026-01-13 16:58:43', 'fish_7_6946c614bd74a.jpg');

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
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','confirmed','paid','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `menu_orders`
--

INSERT INTO `menu_orders` (`id`, `order_number`, `admin_id`, `total_amount`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'AMENU176639171342652160', 1, '300.00', 'paid', 'Direct menu order from farm by admin. Contact: . Notes: ', '2025-12-22 08:21:53', '2025-12-22 10:28:21'),
(2, 'AMENU176639186394843759', 1, '65.00', 'paid', 'Direct menu order from farm by admin. Contact: . Notes: ', '2025-12-22 08:24:23', '2025-12-22 09:05:59'),
(3, 'AMENU176639203748929274', 1, '50.00', 'paid', 'Direct menu order from farm by admin. Contact: . Notes: ', '2025-12-22 08:27:17', '2025-12-22 09:05:52'),
(4, 'AMENU176639236147993932', 1, '670.00', 'paid', 'Direct menu order from farm by admin. Contact: . Notes: ', '2025-12-22 08:32:41', '2025-12-22 09:05:45'),
(5, 'AMENU176639258297047548', 1, '175.00', 'paid', 'Direct menu order from farm by admin. Contact: . Notes: ', '2025-12-22 08:36:22', '2025-12-22 08:39:46'),
(6, 'AMENU176639334777632319', 1, '50.00', 'paid', 'Direct menu order from farm by admin. Contact: . Notes: no onions', '2025-12-22 08:49:07', '2025-12-22 08:49:07'),
(7, 'AMENU176639343824284317', 1, '50.00', 'paid', 'no tappings', '2025-12-22 08:50:38', '2025-12-22 10:28:15'),
(8, 'AMENU176648701172520682', 1, '300.00', 'paid', '', '2025-12-23 10:50:11', '2025-12-23 10:50:11'),
(9, 'AMENU176649510808363963', 1, '50.00', 'paid', '', '2025-12-23 13:05:08', '2025-12-23 13:05:08'),
(10, 'AMENU176649597099689832', 11, '50.00', 'paid', '', '2025-12-23 13:19:30', '2025-12-23 13:19:30'),
(11, 'AMENU176778327451638340', 1, '300.00', 'paid', '', '2026-01-07 10:54:34', '2026-01-07 10:54:34');

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

--
-- Dumping data for table `menu_order_items`
--

INSERT INTO `menu_order_items` (`id`, `menu_order_id`, `item_type`, `item_id`, `quantity`, `unit_price`, `subtotal`, `created_at`) VALUES
(1, 1, 'product', 26, '1.00', '50.00', '50.00', '2025-12-22 08:21:53'),
(2, 1, 'product', 21, '1.00', '250.00', '250.00', '2025-12-22 08:21:53'),
(3, 2, 'product', 26, '1.00', '50.00', '50.00', '2025-12-22 08:24:23'),
(4, 2, 'product', 25, '1.00', '15.00', '15.00', '2025-12-22 08:24:23'),
(5, 3, 'product', 26, '1.00', '50.00', '50.00', '2025-12-22 08:27:17'),
(6, 4, 'product', 22, '3.00', '140.00', '420.00', '2025-12-22 08:32:41'),
(7, 4, 'product', 21, '1.00', '250.00', '250.00', '2025-12-22 08:32:41'),
(8, 5, 'product', 26, '2.00', '50.00', '100.00', '2025-12-22 08:36:22'),
(9, 5, 'product', 25, '5.00', '15.00', '75.00', '2025-12-22 08:36:22'),
(10, 6, 'product', 26, '1.00', '50.00', '50.00', '2025-12-22 08:49:07'),
(11, 7, 'product', 26, '1.00', '50.00', '50.00', '2025-12-22 08:50:38'),
(12, 8, 'product', 21, '1.00', '250.00', '250.00', '2025-12-23 10:50:11'),
(13, 8, 'product', 26, '1.00', '50.00', '50.00', '2025-12-23 10:50:11'),
(14, 9, 'product', 26, '1.00', '50.00', '50.00', '2025-12-23 13:05:08'),
(15, 10, 'product', 26, '1.00', '50.00', '50.00', '2025-12-23 13:19:30'),
(16, 11, 'product', 21, '1.00', '250.00', '250.00', '2026-01-07 10:54:34'),
(17, 11, 'product', 26, '1.00', '50.00', '50.00', '2026-01-07 10:54:34');

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
(7, 'ORD17663816844949657', 8, '2025-12-22 05:34:44', '2025-12-22', '1800.00', 'paid', 'Online order from website', '2025-12-22 05:34:44', '2025-12-22 09:13:48'),
(13, 'ORD17663879739934655', 8, '2025-12-22 07:19:33', '2025-12-22', '200.00', 'paid', 'Online order from website', '2025-12-22 07:19:33', '2025-12-22 09:13:53'),
(14, 'ORD17663940332124966', 9, '2025-12-22 09:00:33', '2025-10-22', '200.00', 'paid', 'Online order from website', '2025-12-22 09:00:33', '2025-12-23 10:59:05'),
(15, 'ORD17667147513205804', 9, '2025-12-26 02:05:51', '2025-12-26', '200.00', 'pending', 'Online order from website', '2025-12-26 02:05:51', '2025-12-26 02:05:51'),
(16, 'ORD17677844031501034', 1, '2026-01-07 11:13:23', '2026-01-08', '400.00', 'pending', 'Online order from website', '2026-01-07 11:13:23', '2026-01-07 11:13:23'),
(17, 'ORD17677853437404218', 1, '2026-01-07 11:29:03', '2026-01-07', '400.00', 'pending', 'Online order from website', '2026-01-07 11:29:03', '2026-01-07 11:29:03'),
(18, 'ORD17683235238933570', 15, '2026-01-13 16:58:43', '2026-01-14', '200.00', 'pending', 'Online order from website', '2026-01-13 16:58:43', '2026-01-13 16:58:43');

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
(3, 13, 6, 1, '200.00', '200.00'),
(4, 14, 6, 1, '200.00', '200.00'),
(5, 15, 7, 1, '200.00', '200.00'),
(6, 16, 7, 2, '200.00', '400.00'),
(7, 17, 7, 1, '200.00', '200.00'),
(8, 17, 5, 1, '200.00', '200.00'),
(9, 18, 7, 1, '200.00', '200.00');

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
(21, 'Deep-Fry Hito', 'food', 'Crispy deep-fried catfish', '21_6947c3c2848f5.jpg', '250.00', 'order', 56, 'available', '2025-12-21 09:54:10', '2026-01-07 10:54:34'),
(22, 'Adobo Hito', 'food', 'Traditional Filipino adobo', '22_6947c6b333bfd.jpg', '140.00', 'order', 97, 'available', '2025-12-21 10:06:43', '2025-12-22 08:32:41'),
(23, 'Sisig', 'food', 'Sizzling hot sisig', '23_6947c74eeb08a.jpg', '130.00', 'order', 50, 'available', '2025-12-21 10:09:18', '2025-12-21 10:09:18'),
(24, 'French Fries', 'snack', 'Golden crispy fries', '24_6947c79fd82bc.jpg', '60.00', 'order', 150, 'available', '2025-12-21 10:10:39', '2025-12-21 10:10:39'),
(25, 'Coke', 'drink', 'Various cold beverages', '25_6947c7db6e9d8.jpg', '15.00', 'pcs', 144, 'available', '2025-12-21 10:11:39', '2025-12-22 08:36:22'),
(26, 'Lumpia', 'food', 'Traditional Limpua', '26_6947c831e078b.webp', '50.00', 'order', 39, 'available', '2025-12-21 10:13:05', '2026-01-07 10:54:34');

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
  `reservation_type` enum('dine-in','farm visit','private-events','cottage') DEFAULT NULL,
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
(1, 'RES-20251222-2515', 9, 'dine-in', 20, '2025-12-22', '12:43:00', 'birthday', 'pending', '09454739384', 'bingbing@gmail.com', '2025-12-22 04:43:20', '2026-01-07 10:52:45'),
(2, 'RES-20251222-4514', 9, 'dine-in', 30, '2025-12-22', '12:59:00', 'asdf', 'pending', '09454739384', 'maoy@gmail.com', '2025-12-22 04:59:42', '2026-01-07 10:52:52'),
(3, 'RES-20251222-7660', 9, 'private-events', 30, '2025-12-22', '13:15:00', 'adf', 'confirmed', '09454739384', 'testtest@gmail.com', '2025-12-22 05:15:26', '2026-01-07 10:53:03'),
(4, 'RES-20251222-1312', 9, 'dine-in', 2, '2025-12-22', '13:33:00', 'dfgdfgfdgdfg', 'confirmed', '09454739384', 'testing@gmail.com', '2025-12-22 05:33:45', '2026-01-07 10:53:14'),
(6, 'RES-20260107-7093', 3, 'cottage', 5, '2026-01-15', '19:18:00', 'sf', 'pending', '09454739386', 'zebra@gmail.com', '2026-01-07 11:18:19', '2026-01-07 11:18:19'),
(7, 'RES-20260113-2616', 7, 'cottage', 2, '2026-01-13', '13:47:00', 'adf', 'pending', '09454739384', 'xamaagad@gmail.com', '2026-01-13 05:47:41', '2026-01-13 05:47:41'),
(8, 'RES-20260113-0186', 8, 'farm visit', 2, '2026-01-13', '13:53:00', 'sdf', 'pending', '09454739384', 'roneilbansas5222@gmail.com', '2026-01-13 05:53:16', '2026-01-13 05:53:16');

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

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `first_name`, `last_name`, `email`, `phone`, `position`, `department`, `hire_date`, `status`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'james', 'smith', 'james@gmail.com', '09454739378', 'waiter', 'waiter', '2025-12-23', 'active', 0, '2025-12-22 17:55:39', '2025-12-22 17:55:39'),
(2, 'roneils', 'bansas', 'donghinban0928@gmail.com', '09454739386', 'waiter', 'operations', '2025-12-23', 'active', 10, '2025-12-22 18:06:44', '2025-12-22 18:08:57'),
(3, 'will', 'smith', 'will@gmail.com', '09454739386', 'waiter', 'operations', '2025-12-23', 'active', 11, '2025-12-22 18:11:28', '2025-12-22 18:11:28');

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
(1, 'admin', 'admin@gmail.com', '$2y$10$tZIZZOiSLWDWKz8BQcXg6ORfRgBTDarWddkeHtNJTySKNxGf31s0O', 'admin', 'admin', 'active', '2025-12-15 03:39:55', '2025-12-22 17:48:20'),
(10, 'usernames', 'donghinban0928@gmail.com', '$2y$10$A0KTWFfqBw8zcwrQpIkQmehzx4jhoIdrvx5kYlbXYTKuKNRIS76te', 'sam smith', 'staff', 'active', '2025-12-22 18:06:44', '2026-01-13 16:24:56'),
(11, 'willsmith', 'will@gmail.com', '$2y$10$CkO3KhH4zLfmDKd6cTnhc.e3qYL.ZPewgarNXsNsWc8AZByjBrxJK', 'will smith', 'manager', 'active', '2025-12-22 18:11:28', '2025-12-22 18:12:05');

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
  ADD KEY `idx_product` (`fish_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

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
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `availability`
--
ALTER TABLE `availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fish_species`
--
ALTER TABLE `fish_species`
  MODIFY `fish_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `menu_orders`
--
ALTER TABLE `menu_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `menu_order_items`
--
ALTER TABLE `menu_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `menu_order_items`
--
ALTER TABLE `menu_order_items`
  ADD CONSTRAINT `fk_menu_order_items_menu_orders` FOREIGN KEY (`menu_order_id`) REFERENCES `menu_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
