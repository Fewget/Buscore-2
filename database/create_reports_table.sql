CREATE TABLE IF NOT EXISTS `bus_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bus_number` varchar(20) NOT NULL,
  `issue_types` text NOT NULL,
  `description` text,
  `location` varchar(255) DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bus_number` (`bus_number`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
