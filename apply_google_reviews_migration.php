<?php
/**
 * apply_google_reviews_migration.php
 * Tworzy tabelę google_reviews i dodaje ustawienia do tabeli settings.
 * Uruchom raz, potem usuń.
 */
require_once __DIR__ . '/admin/helper.php';
$pdo = getDB();
if (!$pdo) die("Błąd połączenia.\n");

try {
    echo "=== Migracja: Google Reviews ===\n\n";

    echo "1. Tabela google_reviews...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS google_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reviewer_name VARCHAR(200) NOT NULL,
        reviewer_photo VARCHAR(500) DEFAULT '',
        rating TINYINT NOT NULL DEFAULT 5,
        text TEXT DEFAULT '',
        time_description VARCHAR(100) DEFAULT '',
        review_time BIGINT DEFAULT 0,
        is_visible TINYINT(1) DEFAULT 1,
        fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    echo "2. Ustawienia...\n";
    $settings = [
        'google_places_api_key'  => '',
        'google_place_id'        => '',
        'google_reviews_visible' => '1',
        'google_reviews_count'   => '5',
        'google_overall_rating'  => '5.0',
        'google_total_ratings'   => '0',
        'google_place_name'      => 'Pasieka Pod Gruszką',
        'google_reviews_last_sync' => '',
    ];
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_key=setting_key");
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
        echo "  $k = OK\n";
    }

    echo "\nGotowe! Możesz teraz usunąć ten plik.\n";
} catch (Exception $e) {
    echo "BŁĄD: " . $e->getMessage() . "\n";
}
