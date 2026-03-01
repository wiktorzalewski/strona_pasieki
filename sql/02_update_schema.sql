-- Tabela powiadomień o dostępności
CREATE TABLE IF NOT EXISTS `availability_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;
