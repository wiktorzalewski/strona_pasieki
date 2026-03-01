<?php
/**
 * notify_request.php — Zapisuje prośbę o powiadomienie
 */
header('Content-Type: application/json');
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda niedozwolona.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Fallback for form data
    $input = $_POST;
}

$productId = intval($input['product_id'] ?? 0);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$productId || !$email) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowe dane.']);
    exit;
}

try {
    // Sprawdź czy już nie ma takiej prośby (niewysłanej)
    $stmt = $pdo->prepare("SELECT id FROM availability_notifications WHERE product_id = ? AND email = ? AND sent_at IS NULL");
    $stmt->execute([$productId, $email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Już jesteś na liście powiadomień dla tego produktu.']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO availability_notifications (product_id, email, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$productId, $email]);
        echo json_encode(['success' => true, 'message' => 'Zapisano! Powiadomimy Cię, gdy produkt będzie dostępny.']);
    }
} catch (PDOException $e) {
    error_log("Notify Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Wystąpił błąd serwera.']);
}
