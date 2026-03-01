<?php
/**
 * apply_new_features_migration.php
 * Creates tables for: contact_messages, discount_codes, redirects, admin_sessions, admin_notes
 * Run once, then delete.
 */
require_once __DIR__ . '/admin/helper.php';
$pdo = getDB();
if (!$pdo) die("Błąd połączenia z bazą danych.\n");

try {
    echo "=== Migracja nowych funkcji ===\n\n";

    // 1. Wiadomości kontaktowe
    echo "1. Tabela contact_messages...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) DEFAULT '',
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    // 2. Kody rabatowe
    echo "2. Tabela discount_codes...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS discount_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        type ENUM('percent','fixed') DEFAULT 'percent',
        value DECIMAL(10,2) NOT NULL,
        min_order DECIMAL(10,2) DEFAULT 0,
        max_uses INT DEFAULT 0,
        uses_count INT DEFAULT 0,
        expires_at DATE NULL,
        is_active TINYINT(1) DEFAULT 1,
        description VARCHAR(255) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    // 3. Redirecty
    echo "3. Tabela redirects...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS redirects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_path VARCHAR(500) NOT NULL UNIQUE,
        to_url VARCHAR(1000) NOT NULL,
        redirect_code SMALLINT DEFAULT 301,
        hits INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    // 4. Sesje adminów
    echo "4. Tabela admin_sessions...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(128) NOT NULL UNIQUE,
        user_id INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(500) NULL,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    // 5. Notatki adminów
    echo "5. Tabela admin_notes...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    echo "\nMigracja zakończona sukcesem!\nMożesz teraz usunąć ten plik.\n";
} catch (Exception $e) {
    echo "BŁĄD: " . $e->getMessage() . "\n";
}
