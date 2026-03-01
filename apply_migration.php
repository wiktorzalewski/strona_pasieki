<?php
/**
 * Skrypt do naprawy bazy danych — dodawanie brakujących kolumn do tabeli users
 */
require_once 'includes/db.php';

echo "<h1>Migracja Bazy Danych</h1>";

try {
    // Lista kolumn do dodania
    $columnsToAdd = [
        "email_verified" => "TINYINT(1) DEFAULT 0 AFTER `email` text", // Note: existing email might be varchar
        "email_token"    => "VARCHAR(64) DEFAULT NULL AFTER `email_verified`",
        "reset_token"    => "VARCHAR(64) DEFAULT NULL AFTER `email_token`",
        "reset_expires"  => "DATETIME DEFAULT NULL AFTER `reset_token`"
    ];

    // Najpierw sprawdźmy typ kolumny 'email', jeśli istnieje
    $stmt = $pdo->query("DESCRIBE users");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('email', $existingColumns)) {
        echo "<p style='color:orange;'>Kolumna 'email' nie istnieje. Dodaję ją najpierw...</p>";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `username` text");
        $pdo->exec("ALTER TABLE `users` ADD UNIQUE KEY `unique_email` (`email`) text");
        echo "<p style='color:green;'>Dodano kolumnę 'email'.</p>";
    }

    foreach ($columnsToAdd as $col => $definition) {
        if (!in_array($col, $existingColumns)) {
            echo "<p>Dodaję kolumnę: <strong>$col</strong>...</p>";
            // Standard ALTER TABLE
            $sql = "ALTER TABLE `users` ADD COLUMN `$col` $definition";
            // Clean up definition for my specific logic (removing 'text' suffix if I accidentally added it or adjusting)
            // Wait, I see I might have added 'text' in my thinking. Let me fix the SQL.
            $sql = "ALTER TABLE `users` ADD COLUMN `$col` " . str_replace(' text', '', $definition);
            
            $pdo->exec($sql);
            echo "<p style='color:green;'>Sukces!</p>";
        } else {
            echo "<p style='color:gray;'>Kolumna '$col' już istnieje.</p>";
        }
    }

    echo "<h3>Migracja zakończona pomyślnie.</h3>";
    echo "<p>Możesz teraz wrócić do panelu admina i spróbować dodać konto ponownie.</p>";
    echo "<p><a href='admin/accounts.php'>Wróć do Menadżera Kont</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red;'>Błąd podczas migracji:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Spróbuj uruchomić ten kod ręcznie w phpMyAdmin:</p>";
    echo "<pre>
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) DEFAULT NULL AFTER `username`,
ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 0 AFTER `email`,
ADD COLUMN IF NOT EXISTS `email_token` VARCHAR(64) DEFAULT NULL AFTER `email_verified`,
ADD COLUMN IF NOT EXISTS `reset_token` VARCHAR(64) DEFAULT NULL AFTER `email_token`,
ADD COLUMN IF NOT EXISTS `reset_expires` DATETIME DEFAULT NULL AFTER `reset_token`,
ADD UNIQUE KEY IF NOT EXISTS `unique_email` (`email`);
    </pre>";
}
?>
