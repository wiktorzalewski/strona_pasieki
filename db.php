<?php
$host = 'localhost';
$db_user = '<user_1>';
$db_pass = '<password>';
$db_name = 'miody';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    // Ustawienie trybu błędów na wyjątki
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Domyślne fetchowanie jako tablica asocjacyjna
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- SPRAWDZANIE TRYBU KONSERWACJI (MAINTENANCE MODE) ---
    // Sprawdzamy tabelę 'settings'
    try {
        $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode' LIMIT 1");
        $mode = $stmt->fetchColumn(); 

        if ($mode === '1') {
            // Jeśli tryb konserwacji jest włączony (1), przekieruj na maintenance.html
            // Ale nie rób pętli przekierowań jeśli już tam jesteśmy
            $currentData = basename($_SERVER['PHP_SELF']);
            if ($currentData !== 'maintenance.html') {
                header("Location: /maintenance.html");
                exit();
            }
        }
    } catch (PDOException $e) {
        // Jeśli tabela settings nie istnieje (np. przed importem), ignorujemy błąd
        // żeby nie zablokować strony errorami
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
