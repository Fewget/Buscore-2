-- Add bus type column to buses table if it doesn't exist
ALTER TABLE `buses` ADD COLUMN IF NOT EXISTS `type` ENUM('private', 'government') NOT NULL DEFAULT 'private' AFTER `company_name`;

-- Add an index for better query performance
ALTER TABLE `buses` ADD INDEX `idx_bus_type` (`type`);
