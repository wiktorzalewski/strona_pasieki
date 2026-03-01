<?php
/**
 * auth.php — System bezpieczeństwa panelu admina
 * Pasieka Pod Gruszką
 * 
 * Zabezpieczenia:
 * - Hasło bcrypt
 * - Brute-force protection (5 prób → 15 min blokady)
 * - CSRF tokeny
 * - Auto-logout po 30 min
 * - Security headers
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// KONFIGURACJA — ZMIEŃ HASŁO PONIŻEJ!
// ============================================================
// Domyślne hasło: "Admin2026!" — ZMIEŃ JE po pierwszym logowaniu!
// Aby wygenerować nowy hash, otwórz: admin/generate_hash.php
// Domyślne hasło: "admin" (jeśli używasz seeda z schema.sql)
// Użytkownicy są teraz w bazie danych w tabeli 'users'.


// Klucz szyfrowania AES-256 dla sejfu haseł (32 bajty = 256 bitów)
// ZMIEŃ NA WŁASNY LOSOWY KLUCZ!
define('VAULT_ENCRYPTION_KEY', 'ABJq6fnyUM_ZRXv?');

// Ścieżka do pliku z hasłami
define('VAULT_FILE', __DIR__ . '/../hasla.env');

// Ścieżka do logów prób logowania
define('LOGIN_LOG_FILE', '/tmp/pasieka_login_attempts.log');
define('LOCKOUT_FILE', '/tmp/pasieka_lockout.json');

// Ustawienia bezpieczeństwa
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minut w sekundach
define('SESSION_TIMEOUT', 1800); // 30 minut w sekundach

// ============================================================
// SECURITY HEADERS
// ============================================================
function setSecurityHeaders() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: no-referrer');
    header('Content-Security-Policy: default-src \'self\'; style-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src https://cdnjs.cloudflare.com https://fonts.gstatic.com; script-src \'self\' \'unsafe-inline\'; img-src \'self\' data:;');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// ============================================================
// CSRF PROTECTION
// ============================================================
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================
// BRUTE-FORCE PROTECTION
// ============================================================
function getClientIP() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function isLockedOut() {
    // Sprawdź sesyjny licznik prób
    if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        if (isset($_SESSION['lockout_time']) && (time() - $_SESSION['lockout_time'] < LOCKOUT_DURATION)) {
            return true;
        }
        // Blokada wygasła — resetuj
        unset($_SESSION['login_attempts'], $_SESSION['lockout_time']);
    }
    
    // Sprawdź też plik (backup)
    try {
        if (file_exists(LOCKOUT_FILE)) {
            $data = json_decode(@file_get_contents(LOCKOUT_FILE), true) ?: [];
            $ip = getClientIP();
            if (isset($data[$ip]) && $data[$ip]['attempts'] >= MAX_LOGIN_ATTEMPTS) {
                if (time() - $data[$ip]['last_attempt'] < LOCKOUT_DURATION) {
                    return true;
                }
            }
        }
    } catch (Exception $e) {}
    
    return false;
}

function recordFailedAttempt() {
    // Sesyjny licznik (zawsze działa)
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
    $_SESSION['login_attempts']++;
    $_SESSION['lockout_time'] = time();
    
    // Plik (backup, może nie działać)
    try {
        
        $data = json_decode(@file_get_contents(LOCKOUT_FILE), true) ?: [];
        $ip = getClientIP();
        
        if (!isset($data[$ip])) {
            $data[$ip] = ['attempts' => 0, 'last_attempt' => 0];
        }
        
        // Reset jeśli minęło więcej niż LOCKOUT_DURATION
        if (time() - $data[$ip]['last_attempt'] > LOCKOUT_DURATION) {
            $data[$ip] = ['attempts' => 0, 'last_attempt' => 0];
        }
        
        $data[$ip]['attempts']++;
        $data[$ip]['last_attempt'] = time();
        
        @file_put_contents(LOCKOUT_FILE, json_encode($data));
    } catch (Exception $e) {
        // Nie blokuj logowania jeśli zapis logów nie działa
    }
    
    // Loguj próbę
    logLoginAttempt(false);
}

function resetFailedAttempts() {
    unset($_SESSION['login_attempts'], $_SESSION['lockout_time']);
    try {
        $data = json_decode(@file_get_contents(LOCKOUT_FILE), true) ?: [];
        $ip = getClientIP();
        unset($data[$ip]);
        @file_put_contents(LOCKOUT_FILE, json_encode($data));
    } catch (Exception $e) {}
}

function getRemainingLockoutTime() {
    $data = json_decode(@file_get_contents(LOCKOUT_FILE), true) ?: [];
    $ip = getClientIP();
    if (isset($data[$ip])) {
        $elapsed = time() - $data[$ip]['last_attempt'];
        return max(0, LOCKOUT_DURATION - $elapsed);
    }
    return 0;
}

// ============================================================
// LOGIN LOGGING
// ============================================================
function logLoginAttempt($success) {
    try {
        $dir = dirname(LOGIN_LOG_FILE);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        
        $entry = sprintf(
            "[%s] IP: %s | Status: %s | User-Agent: %s\n",
            date('Y-m-d H:i:s'),
            getClientIP(),
            $success ? 'SUCCESS' : 'FAILED',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        @file_put_contents(LOGIN_LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        // Nie blokuj logowania jeśli logi nie działają
    }
}

// ============================================================
// SESSION MANAGEMENT
// ============================================================
function checkSession() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: index.php');
        exit();
    }
    
    // Auto-logout po nieaktywności
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_message'] = 'Sesja wygasła z powodu nieaktywności.';
        header('Location: index.php');
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}

require_once __DIR__ . '/../includes/db.php';

function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(false);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time();
        resetFailedAttempts();
        logLoginAttempt(true);
        return true;
    }
    recordFailedAttempt();
    return false;
}

// ============================================================
// VAULT ENCRYPTION (AES-256-CBC)
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
    $decrypted = decryptData($content);
    if ($decrypted === false) return [];
    
    return json_decode($decrypted, true) ?: [];
}

function saveVault($data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $encrypted = encryptData($json);
    $dir = dirname(VAULT_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents(VAULT_FILE, $encrypted, LOCK_EX);
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
                ['name' => 'kontakt@pasiekapodgruszka.pl', 'login' => '', 'password' => '', 'url' => '', 'notes' => ''],
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
?>
