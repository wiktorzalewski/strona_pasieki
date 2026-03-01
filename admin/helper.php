<?php
/**
 * helper.php — Wspólne funkcje panelu admina
 * Sesja, uprawnienia, baza, szyfrowanie
 */
date_default_timezone_set('Europe/Warsaw');

// Anty-cache — Cloudflare nie cachuje panelu admina
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('CDN-Cache-Control: no-store');  // Cloudflare-specific
header('Pragma: no-cache');
header('Expires: 0');

// Sesja z pełną konfiguracją cookie
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
             || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    session_name('ADMINSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly'  => true,
        'samesite'  => 'Lax'
    ]);
    session_start();
}

// ============================================================
// KONFIGURACJA
// ============================================================
if (!defined('VAULT_ENCRYPTION_KEY')) define('VAULT_ENCRYPTION_KEY', 'ABJq6fnyUM_ZRXv?');
if (!defined('VAULT_FILE')) define('VAULT_FILE', __DIR__ . '/../hasla.env');
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 1800); // 30 min

// ============================================================
// BAZA DANYCH
// ============================================================
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=miody;charset=utf8mb4", 'miody_admin', 'YOUR_DB_PASSWORD');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    return $pdo;
}

function getSetting($key, $default = '') {
    $pdo = getDB();
    if (!$pdo) return $default;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function setSetting($key, $value) {
    $pdo = getDB();
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// ============================================================
// AUTORYZACJA I UPRAWNIENIA
// ============================================================
function requireLogin() {
    if (empty($_SESSION['admin_logged_in'])) {
        session_write_close();
        header('Location: index.php');
        exit();
    }
    // Auto-logout po 30 min nieaktywności
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['logout_reason'] = 'timeout'; // komunikat o wygaśnięciu sesji
        session_write_close();
        header('Location: index.php?expired=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Czy zalogowany użytkownik jest ownerem (konto admin)
 */
function isOwner() {
    return ($_SESSION['user_role'] ?? '') === 'owner';
}

/**
 * Czy użytkownik ma uprawnienie do danej sekcji
 * Owner ma ZAWSZE dostęp do wszystkiego
 */
function hasPermission($perm) {
    if (isOwner()) return true;
    $perms = $_SESSION['user_permissions'] ?? [];
    return !empty($perms[$perm]);
}

/**
 * Wymusza uprawnienie — jeśli brak, redirect do dashboard
 */
function requirePermission($perm) {
    if (!hasPermission($perm)) {
        session_write_close();
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Wymusza rolę owner — tylko dla menadżera kont
 */
function requireOwner() {
    if (!isOwner()) {
        session_write_close();
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Zwraca nazwę zalogowanego użytkownika
 */
function currentUsername() {
    return $_SESSION['username'] ?? 'Gość';
}

/**
 * Zwraca liczbę nieprzeczytanych wiadomości kontaktowych
 */
function getUnreadMessagesCount() {
    $pdo = getDB();
    if (!$pdo) return 0;
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Loguje/aktualizuje sesję admina w tabeli admin_sessions
 */
function logAdminSession() {
    $pdo = getDB();
    if (!$pdo || empty($_SESSION['admin_logged_in'])) return;
    try {
        $sessionId = session_id();
        $userId = $_SESSION['user_id'] ?? 0;
        $username = currentUsername();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $pdo->prepare("INSERT INTO admin_sessions (session_id, user_id, username, ip_address, user_agent) VALUES (?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE last_activity=NOW(), username=?, ip_address=?, user_agent=?")
            ->execute([$sessionId, $userId, $username, $ip, $ua, $username, $ip, $ua]);
    } catch (Exception $e) {}
}


/**
 * Zapisuje aktywność w dzienniku
 */
function logActivity($action, $details = null) {
    $pdo = getDB();
    if (!$pdo) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            currentUsername(),
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    } catch (Exception $e) {
        // Ignoruj błędy logowania, żeby nie blokować aplikacji
    }
}

/**
 * Zwraca nazwę zalogowanego użytkownika
 */
function getContactEmail() {
    return getSetting('contact_email', 'kontakt@pasiekapodgruszka.pl');
}

// ============================================================
// VAULT — SZYFROWANIE AES-256-CBC
// ============================================================
function encryptData($data) {
    $key = hash('sha256', VAULT_ENCRYPTION_KEY, true);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . '::' . base64_decode($encrypted));
}

function decryptData($encryptedData) {
    $key = hash('sha256', VAULT_ENCRYPTION_KEY, true);
    $parts = explode('::', base64_decode($encryptedData), 2);
    if (count($parts) !== 2) return false;
    $iv = $parts[0];
    $encrypted = base64_encode($parts[1]);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}

function loadVault() {
    if (!file_exists(VAULT_FILE)) return [];
    $content = file_get_contents(VAULT_FILE);
    if (empty($content)) return [];
    $decrypted = decryptData($content);
    if ($decrypted === false) return [];
    return json_decode($decrypted, true) ?: [];
}

function saveVault($data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $encrypted = encryptData($json);
    return @file_put_contents(VAULT_FILE, $encrypted, LOCK_EX);
}

function initializeVault() {
    if (file_exists(VAULT_FILE) && filesize(VAULT_FILE) > 0) return;
    
    $defaultData = [
        [
            'category' => 'Hosting / Serwer',
            'icon' => 'fa-server',
            'entries' => [
                ['name' => 'Panel hostingu', 'login' => '', 'password' => '', 'url' => '', 'notes' => ''],
                ['name' => 'FTP', 'login' => '', 'password' => '', 'url' => '', 'notes' => ''],
                ['name' => 'SSH', 'login' => '', 'password' => '', 'url' => '', 'notes' => ''],
            ]
        ],
        [
            'category' => 'Baza Danych',
            'icon' => 'fa-database',
            'entries' => [
                ['name' => 'MySQL / phpMyAdmin', 'login' => '', 'password' => '', 'url' => '', 'notes' => ''],
            ]
        ],
        [
            'category' => 'Email',
            'icon' => 'fa-envelope',
            'entries' => [
                ['name' => 'kontakt@wikzal.pl', 'login' => '', 'password' => '', 'url' => '', 'notes' => ''],
            ]
        ],
        [
            'category' => 'Social Media',
            'icon' => 'fa-share-nodes',
            'entries' => [
                ['name' => 'Facebook', 'login' => '', 'password' => '', 'url' => '', 'notes' => ''],
                ['name' => 'Instagram', 'login' => '', 'password' => '', 'url' => '', 'notes' => ''],
            ]
        ],
        [
            'category' => 'Google',
            'icon' => 'fa-google',
            'entries' => [
                ['name' => 'Google Search Console', 'login' => '', 'password' => '', 'url' => 'https://search.google.com/search-console', 'notes' => ''],
                ['name' => 'Google Analytics', 'login' => '', 'password' => '', 'url' => '', 'notes' => ''],
            ]
        ],
        [
            'category' => 'Domeny',
            'icon' => 'fa-globe',
            'entries' => [
                ['name' => 'Rejestrator domen', 'login' => '', 'password' => '', 'url' => '', 'notes' => ''],
            ]
        ],
        [
            'category' => 'Inne',
            'icon' => 'fa-ellipsis',
            'entries' => []
        ],
    ];
    
    saveVault($defaultData);
}

// ============================================================
// STATYSTYKI SERWERA
// ============================================================
function getServerStats() {
    $stats = [];
    $stats['php_version'] = phpversion();
    
    $totalSpace = @disk_total_space('/');
    $freeSpace = @disk_free_space('/');
    if ($totalSpace && $freeSpace) {
        $usedSpace = $totalSpace - $freeSpace;
        $stats['disk_total'] = round($totalSpace / 1073741824, 1) . ' GB';
        $stats['disk_used'] = round($usedSpace / 1073741824, 1) . ' GB';
        $stats['disk_free'] = round($freeSpace / 1073741824, 1) . ' GB';
        $stats['disk_percent'] = round(($usedSpace / $totalSpace) * 100);
    }
    
    if (file_exists('/proc/uptime')) {
        $uptime = intval(file_get_contents('/proc/uptime'));
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        $stats['uptime'] = "{$days}d {$hours}h";
    }
    
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $stats['load'] = round($load[0], 2) . ' / ' . round($load[1], 2) . ' / ' . round($load[2], 2);
    }
    
    $pdo = getDB();
    if ($pdo) {
        try {
            $stats['mysql_version'] = $pdo->query("SELECT VERSION()")->fetchColumn();
        } catch (Exception $e) {}
    }
    
    return $stats;
}

function getDbCounts() {
    $pdo = getDB();
    if (!$pdo) return [];
    $counts = [];
    $tables = ['products', 'kits', 'recipes', 'gallery_images'];
    foreach ($tables as $table) {
        try {
            $counts[$table] = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        } catch (Exception $e) {
            $counts[$table] = '?';
        }
    }
    return $counts;
}

/**
 * Upload i optymalizacja zdjęcia (Konwersja do WebP + Resizing)
 */
function handleImageUpload($fieldName, $targetDir, $maxSize = 1200) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK || $_FILES[$fieldName]['size'] === 0) return null;
    
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $tmpName = $_FILES[$fieldName]['tmp_name'];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) return ['error' => 'Dozwolone formaty: ' . implode(', ', $allowed)];
    
    $fullDir = dirname(__DIR__) . '/' . $targetDir;
    if (!is_dir($fullDir)) @mkdir($fullDir, 0755, true);
    
    $filename = uniqid() . '.webp';
    $destPath = $fullDir . '/' . $filename;
    
    try {
        // Spróbuj użyć GD do konwersji i zmiany rozmiaru
        if (!function_exists('imagecreatefromstring')) {
            // Fallback do zwykłego move_uploaded_file jeśli brak GD
            $filename = uniqid() . '_' . preg_replace('/[^a-z0-9._-]/', '', strtolower($_FILES[$fieldName]['name']));
            if (move_uploaded_file($tmpName, $fullDir . '/' . $filename)) return ['path' => $targetDir . '/' . $filename];
            return ['error' => 'Nie udało się zapisać pliku.'];
        }

        $img = imagecreatefromstring(file_get_contents($tmpName));
        if (!$img) throw new Exception("Nie można odczytać obrazu.");

        $width = imagesx($img);
        $height = imagesy($img);

        // Skalowanie jeśli obraz jest za duży
        if ($width > $maxSize || $height > $maxSize) {
            if ($width > $height) {
                $newWidth = $maxSize;
                $newHeight = floor($height * ($maxSize / $width));
            } else {
                $newHeight = $maxSize;
                $newWidth = floor($width * ($maxSize / $height));
            }
            $tmpImg = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($tmpImg, false);
            imagesavealpha($tmpImg, true);
            imagecopyresampled($tmpImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($img);
            $img = $tmpImg;
        }

        imagewebp($img, $destPath, 85);
        imagedestroy($img);
        return ['path' => $targetDir . '/' . $filename];

    } catch (Exception $e) {
        return ['error' => 'Błąd optymalizacji: ' . $e->getMessage()];
    }
}

/**
 * Wysyłanie e-maili (HTML)
 */
function sendEmail($to, $subject, $message) {
    // Konfiguracja SMTP
    $smtpConfig = [
        'host' => 'poczta2686594.home.pl',
        'port' => 587,
        'username' => 'newsletter@pasiekapodgruszka.pl',
        'password' => 'MYVt190LHG0gwh85',
        'from' => 'newsletter@pasiekapodgruszka.pl',
        'fromName' => 'Pasieka Pod Gruszką'
    ];

    $host = $smtpConfig['host'];
    $port = $smtpConfig['port'];
    $username = $smtpConfig['username'];
    $password = $smtpConfig['password'];
    $from = $smtpConfig['from'];
    $fromName = $smtpConfig['fromName'];

    // Stylizacja wiadomości
    $body = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-top: 5px solid #ffc107; }
            h1 { color: #f39c12; margin-top: 0; }
            .footer { margin-top: 30px; font-size: 0.85rem; color: #777; text-align: center; border-top: 1px solid #eee; padding-top: 20px; }
            .btn { display: inline-block; padding: 10px 20px; background: #f39c12; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
            .btn:hover { background: #e67e22; }
        </style>
    </head>
    <body>
        <div class="container">
            ' . $message . '
            <div class="footer">
                &copy; ' . date('Y') . ' Pasieka Pod Gruszką. Wiadomość wygenerowana automatycznie.
            </div>
        </div>
    </body>
    </html>
    ';

    try {
        $socket = fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) return false;

        $read = function() use ($socket) {
            $response = '';
            while ($str = fgets($socket, 515)) {
                $response .= $str;
                if (substr($str, 3, 1) == ' ') break;
            }
            return $response;
        };
        $send = function($cmd) use ($socket) { fputs($socket, $cmd . "\r\n"); };

        $read();
        $send("EHLO " . $_SERVER['SERVER_NAME']); $read();

        if ($port == 587) {
            $send("STARTTLS"); $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send("EHLO " . $_SERVER['SERVER_NAME']); $read();
        }

        $send("AUTH LOGIN"); $read();
        $send(base64_encode($username)); $read();
        $send(base64_encode($password)); 
        if (strpos($read(), '235') === false) return false;

        $send("MAIL FROM: <$from>"); $read();
        $send("RCPT TO: <$to>"); $read();
        $send("DATA"); $read();

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <$from>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        $send($headers . "\r\n" . $body . "\r\n.");
        $result = $read();
        
        $send("QUIT");
        fclose($socket);

        return strpos($result, '250') !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generowanie bezpiecznego tokenu
 */
function generateToken($length = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length));
    }
    return bin2hex(openssl_random_pseudo_bytes($length));
}
