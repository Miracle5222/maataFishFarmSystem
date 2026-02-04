-- Migration to change pickup_date from DATE to DATETIME in orders table
-- This allows storing both date and time for pickup scheduling

ALTER TABLE `orders` MODIFY COLUMN `pickup_date` DATETIME DEFAULT NULL;