-- Tabela zgód na pliki cookies
CREATE TABLE IF NOT EXISTS `cookie_consents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `consent_type` varchar(50) NOT NULL DEFAULT 'accepted',
  `consent_date` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
