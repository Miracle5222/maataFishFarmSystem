-- Migration: add item_type column to order_items and set id auto-increment
-- Run this against your database (make a backup first)

ALTER TABLE `order_items`
  ADD COLUMN `item_type` VARCHAR(20) NOT NULL DEFAULT 'product' AFTER `product_id`;

-- Ensure order_items.id is auto-increment primary key (adjust only if not already set)
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Optional: ensure orders.id and customers.id are AUTO_INCREMENT
ALTER TABLE `orders` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE `customers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Commit at the SQL client when ready.
