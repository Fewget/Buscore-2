-- Allow NULL user_id for guest ratings
ALTER TABLE `ratings` MODIFY `user_id` INT NULL;

-- Add guest_name and guest_email columns for guest ratings
ALTER TABLE `ratings` 
ADD COLUMN IF NOT EXISTS `guest_name` VARCHAR(100) NULL AFTER `user_id`,
ADD COLUMN IF NOT EXISTS `guest_email` VARCHAR(255) NULL AFTER `guest_name`;

-- Add is_guest_rating flag
ALTER TABLE `ratings`
ADD COLUMN IF NOT EXISTS `is_guest_rating` TINYINT(1) NOT NULL DEFAULT 0 AFTER `guest_email`;

-- Update foreign key to allow NULL for user_id
ALTER TABLE `ratings` DROP FOREIGN KEY `ratings_ibfk_2`;
ALTER TABLE `ratings` ADD CONSTRAINT `ratings_ibfk_2` 
FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
