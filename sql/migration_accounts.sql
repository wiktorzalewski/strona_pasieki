-- Migracja: System kont adminów
-- Uruchom w phpMyAdmin

-- Rozbudowa tabeli users o role, uprawnienia, aktywność itp.
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `role` ENUM('owner','admin','editor') DEFAULT 'editor' AFTER `password_hash`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `permissions` JSON DEFAULT NULL AFTER `role`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1 AFTER `permissions`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_login` DATETIME DEFAULT NULL AFTER `is_active`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `created_by` INT DEFAULT NULL AFTER `last_login`;

-- Ustaw konto admin (id=1) jako ownera z pełnymi uprawnieniami
UPDATE `users` SET 
    `role` = 'owner',
    `permissions` = '{"products":true,"kits":true,"recipes":true,"gallery":true,"maintenance":true,"vault":true,"server_info":true,"accounts":true}',
    `is_active` = 1
WHERE `username` = 'admin';

-- Zaktualizuj hash hasła admina (hasło: YOUR_ADMIN_PASSWORD)
UPDATE `users` SET `password_hash` = '$2y$12$Of4RoHOkhe2zs9bHp7t8bup7gg26.SV1ss3Evp6RtUBqIQjcgxpXi' WHERE `username` = 'admin';
