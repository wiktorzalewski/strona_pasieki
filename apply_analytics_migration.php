<?php
/**
 * apply_analytics_migration.php
 * Creates page_views table for Simple Analytics.
 * Run once, then delete.
 */
require_once __DIR__ . '/admin/helper.php';
$pdo = getDB();
if (!$pdo) die("Błąd połączenia z bazą danych.\n");

try {
    echo "Tworzenie tabeli page_views...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page VARCHAR(255) NOT NULL,
        views INT UNSIGNED DEFAULT 1,
        date DATE NOT NULL,
        UNIQUE KEY unique_page_date (page, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK - Tabela page_views gotowa.\n";
    echo "Migracja zakończona! Możesz usunąć ten plik.\n";
} catch (Exception $e) {
    echo "BŁĄD: " . $e->getMessage() . "\n";
}
