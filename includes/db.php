<?php
// Ustawienia bazy danych dla Raspberry Pi
$host = 'localhost';
$db_user = 'miody_admin';
$db_pass = 'YOUR_DB_PASSWORD';  // ZMIEŃ NA SWOJE HASŁO!
$db_name = 'miody';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    // Ustawienie trybu błędów na wyjątki
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Domyślne fetchowanie jako tablica asocjacyjna
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- SPRAWDZANIE TRYBU KONSERWACJI (MAINTENANCE MODE) ---
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('maintenance_mode','maintenance_scheduled_at')");
        $_mSettings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $_mSettings[$row['setting_key']] = $row['setting_value'];
        }
        $_mMode = $_mSettings['maintenance_mode'] ?? '0';
        $_mSchedule = $_mSettings['maintenance_scheduled_at'] ?? '';
        unset($_mSettings);

        // Auto-włącz przerwę jeśli zaplanowana data/czas minęła
        if ($_mMode !== '1' && !empty($_mSchedule) && strtotime($_mSchedule) <= time()) {
            $pdo->prepare("UPDATE settings SET setting_value='1' WHERE setting_key='maintenance_mode'")->execute();
            $pdo->prepare("UPDATE settings SET setting_value='' WHERE setting_key='maintenance_scheduled_at'")->execute();
            $_mMode = '1';
        }
        unset($_mSchedule);

        if ($_mMode === '1') {
            $_mFile = basename($_SERVER['PHP_SELF']);
            $_mIsAdmin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
            if ($_mFile !== 'maintenance.php' && !$_mIsAdmin) {
                header("Location: /maintenance.php");
                exit();
            }
            unset($_mFile, $_mIsAdmin);
        }
        unset($_mMode);
    } catch (PDOException $e) {
        // Jeśli tabela settings nie istnieje, ignorujemy błąd
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

/**
 * Pobiera wartość ustawienia z tabeli settings (cache w pamięci).
 * Dostępne zarówno po stronie publicznej jak i admina.
 */
if (!function_exists('getSetting')) {
    function getSetting($key, $default = null) {
        global $pdo;
        if (!$pdo) return $default;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? $val : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}
?>
