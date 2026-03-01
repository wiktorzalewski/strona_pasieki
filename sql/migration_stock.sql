-- Migracja: dodanie kolumn stock i capacity do products, sort_order do kits
-- Uruchom w phpMyAdmin

-- Capacity (pojemność) dla produktów
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `capacity` varchar(50) DEFAULT NULL AFTER `usage_text`;

-- Stock (stan magazynowy) dla produktów
-- -1 = nieograniczony, 0 = niedostępny, >0 = konkretna ilość
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `stock` int(11) DEFAULT -1 AFTER `is_active`;

-- Stock i sort_order dla zestawów  
ALTER TABLE `kits` ADD COLUMN IF NOT EXISTS `stock` int(11) DEFAULT -1 AFTER `is_active`;
ALTER TABLE `kits` ADD COLUMN IF NOT EXISTS `sort_order` int(11) DEFAULT 0 AFTER `stock`;
