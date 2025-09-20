-- Create premium_packages table if it doesn't exist
CREATE TABLE IF NOT EXISTS `premium_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `features` text NOT NULL COMMENT 'JSON array of features',
  `price` decimal(10,2) NOT NULL,
  `duration_days` int(11) NOT NULL COMMENT '0 for lifetime',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create bus_subscriptions table
CREATE TABLE IF NOT EXISTS `bus_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bus_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `subscription_type` enum('trial','paid') NOT NULL DEFAULT 'paid',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bus_id` (`bus_id`),
  KEY `package_id` (`package_id`),
  KEY `owner_id` (`owner_id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create premium_payments table
CREATE TABLE IF NOT EXISTS `premium_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_details` text COMMENT 'JSON response from payment gateway',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add foreign key constraints with ON DELETE/UPDATE CASCADE
ALTER TABLE `bus_subscriptions`
  ADD CONSTRAINT `fk_bus_subscription` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subscription_package` FOREIGN KEY (`package_id`) REFERENCES `premium_packages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subscription_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `premium_payments`
  ADD CONSTRAINT `fk_payment_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `bus_subscriptions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Insert default packages if they don't exist
INSERT IGNORE INTO `premium_packages` (`id`, `name`, `description`, `features`, `price`, `duration_days`, `is_active`) VALUES
(1, 'Company Name', 'Display your company name on the bus details page', '["display_company_name"]', 500.00, 30, 1),
(2, 'Bus Name', 'Display your bus name on the bus details page', '["display_bus_name"]', 1000.00, 30, 1),
(3, 'Premium Combo', 'Display both company name and bus name on the bus details page', '["display_company_name", "display_bus_name"]', 1400.00, 30, 1),
(4, 'Free Trial', '7-day free trial of premium features', '["display_company_name", "display_bus_name"]', 0.00, 7, 1);

-- Add is_premium and premium_expiry columns to buses table if they don't exist
SET @dbname = DATABASE();
SET @tablename = 'buses';
SET @premiumColumn = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'is_premium');
SET @expiryColumn = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'premium_expiry');

SET @s = IF(@premiumColumn = 0, 'ALTER TABLE buses ADD COLUMN is_premium TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = IF(@expiryColumn = 0, 'ALTER TABLE buses ADD COLUMN premium_expiry DATETIME DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
