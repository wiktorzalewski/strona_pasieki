-- 1. Dodanie kolumny email do tabeli users (jeśli nie istnieje)
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) DEFAULT NULL AFTER `password_hash`;

-- 2. Dodanie kolumny email_verified (jeśli nie istnieje) - wymagane do resetu hasła!
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 0 AFTER `email`;

-- 3. Aktualizacja adresu email dla administratora
UPDATE `users` SET `email` = 'wiktorzalewski50@gmail.com' WHERE `username` = 'admin';

-- 4. Oznaczenie emaila jako zweryfikowany (KLUCZOWE!)
UPDATE `users` SET `email_verified` = 1 WHERE `username` = 'admin';
