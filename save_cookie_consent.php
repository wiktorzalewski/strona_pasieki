<?php
header('Content-Type: application/json');

$logFile = __DIR__ . '/logs/cookies_consent.json';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Pobierz dane z żądania
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action !== 'accept_cookies') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Zbieranie danych
$ip = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$timestamp = date('Y-m-d H:i:s');
$referer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
$language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown';

// Struktura wpisu
$newEntry = [
    'ip' => $ip,
    'timestamp' => $timestamp,
    'user_agent' => $userAgent,
    'referer' => $referer,
    'language' => $language,
    'consent' => 'accepted'
];

// --- ZAPIS DO BAZY DANYCH ---
require_once __DIR__ . '/includes/db.php';
try {
    $stmt = $pdo->prepare("INSERT INTO cookie_consents (ip_address, user_agent, consent_type) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $userAgent, 'accepted']);
} catch (Exception $e) {
    // Ignorujemy błędy bazy, aby nie psuć odpowiedzi JSON
}
// -----------------------------

// Odczyt istniejących logów
$logs = [];
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    $logs = json_decode($content, true);
    if (!is_array($logs)) {
        $logs = [];
    }
}

// Dodanie nowego wpisu
$logs[] = $newEntry;

// Sortowanie po IP
usort($logs, function ($a, $b) {
    return strcmp($a['ip'], $b['ip']);
});

// Zapis do pliku
if (file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Write failed']);
}
