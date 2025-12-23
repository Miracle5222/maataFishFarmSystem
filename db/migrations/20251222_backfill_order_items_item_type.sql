-- Migration: add item_type to order_items and backfill values
-- Backup your database before running.

-- 1) Add the column with default 'product'
ALTER TABLE `order_items`
  ADD COLUMN `item_type` VARCHAR(20) NOT NULL DEFAULT 'product' AFTER `product_id`;

-- 2) Backfill: if product_id matches a fish_species.fish_id, mark as 'fish'
UPDATE order_items oi
JOIN fish_species f ON oi.product_id = f.fish_id
SET oi.item_type = 'fish'
WHERE f.fish_id IS NOT NULL;

-- 3) Optionally ensure any remaining entries that match products are 'product' (default already set).
UPDATE order_items oi
JOIN products p ON oi.product_id = p.id
SET oi.item_type = 'product'
WHERE p.id IS NOT NULL;

-- 4) Ensure id is primary auto-increment (if not already)
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Commit/verify after running.
