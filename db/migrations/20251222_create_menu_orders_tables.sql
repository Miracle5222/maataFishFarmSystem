-- Migration: create menu_orders and menu_order_items tables
-- Backup DB before running

CREATE TABLE IF NOT EXISTS `menu_orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(40) NOT NULL UNIQUE,
  `admin_id` INT(11) DEFAULT NULL,
  `customer_name` VARCHAR(150) DEFAULT NULL,
  `customer_contact` VARCHAR(150) DEFAULT NULL,
  `customer_email` VARCHAR(255) DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending','confirmed','paid','cancelled') NOT NULL DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `menu_order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `menu_order_id` INT(11) NOT NULL,
  `item_type` ENUM('fish','product') NOT NULL,
  `item_id` INT(11) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu_order` (`menu_order_id`),
  KEY `idx_item` (`item_type`,`item_id`),
  CONSTRAINT `fk_menu_order_items_menu_orders` FOREIGN KEY (`menu_order_id`) REFERENCES `menu_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
