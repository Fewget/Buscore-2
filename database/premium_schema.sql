-- Premium package types
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

-- Bus owner subscriptions
CREATE TABLE IF NOT EXISTS `bus_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bus_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bus_id` (`bus_id`),
  KEY `package_id` (`package_id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payment transactions
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

-- Add foreign key constraints
ALTER TABLE `bus_subscriptions`
  ADD CONSTRAINT `bus_subscriptions_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bus_subscriptions_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `premium_packages` (`id`),
  ADD CONSTRAINT `bus_subscriptions_ibfk_3` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

ALTER TABLE `premium_payments`
  ADD CONSTRAINT `premium_payments_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `bus_subscriptions` (`id`);

-- Insert default packages
INSERT INTO `premium_packages` (`name`, `description`, `features`, `price`, `duration_days`, `is_active`) VALUES
('Company Name', 'Display your company name on the bus details page', '["display_company_name"]', 500.00, 30, 1),
('Bus Name', 'Display your bus name on the bus details page', '["display_bus_name"]', 1000.00, 30, 1),
('Premium Combo', 'Display both company name and bus name on the bus details page', '["display_company_name", "display_bus_name"]', 1400.00, 30, 1);
