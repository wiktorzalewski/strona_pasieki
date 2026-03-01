-- Add email columns to users table
ALTER TABLE `users`
ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `username`,
ADD COLUMN `email_verified` TINYINT(1) DEFAULT 0 AFTER `email`,
ADD COLUMN `email_token` VARCHAR(64) DEFAULT NULL AFTER `email_verified`,
ADD COLUMN `reset_token` VARCHAR(64) DEFAULT NULL AFTER `email_token`,
ADD COLUMN `reset_expires` DATETIME DEFAULT NULL AFTER `reset_token`,
ADD UNIQUE KEY `unique_email` (`email`);

-- Create availability_notifications table
CREATE TABLE IF NOT EXISTS `availability_notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `sent_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;
