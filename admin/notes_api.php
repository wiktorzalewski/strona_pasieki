<?php
/**
 * notes_api.php — AJAX endpoint: Szybkie Notatki
 */
require_once __DIR__ . '/helper.php';
requireLogin();
header('Content-Type: application/json');

$pdo = getDB();
$userId = $_SESSION['user_id'] ?? 0;

if (!$pdo || !$userId) {
    echo json_encode(['ok' => false, 'error' => 'Brak połączenia']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT content FROM admin_notes WHERE user_id=?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['ok' => true, 'content' => $row['content'] ?? '']);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $content = substr($data['content'] ?? '', 0, 10000);
    try {
        $pdo->prepare("INSERT INTO admin_notes (user_id, content) VALUES (?,?)
                       ON DUPLICATE KEY UPDATE content=?, updated_at=NOW()")
            ->execute([$userId, $content, $content]);
        echo json_encode(['ok' => true, 'saved_at' => date('H:i:s')]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Invalid method']);
