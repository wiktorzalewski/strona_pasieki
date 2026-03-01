<?php
/**
 * verify_email.php — Weryfikacja adresu email admina
 */
require_once __DIR__ . '/helper.php';

$pdo = getDB();
$msg = '';
$error = '';

$token = $_GET['token'] ?? '';

if ($token && $pdo) {
    // Znajdź usera z tym tokenem
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Potwierdź email
        $pdo->prepare("UPDATE users SET email_verified = 1, email_token = NULL WHERE id = ?")->execute([$user['id']]);
        $msg = "Adres email dla użytkownika <strong>" . htmlspecialchars($user['username']) . "</strong> został zweryfikowany.";
    } else {
        $error = "Nieprawidłowy lub wygasły token weryfikacyjny.";
    }
} else {
    $error = "Brak tokenu weryfikacyjnego.";
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weryfikacja Email</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg-body); }
        .verify-box { background: var(--bg-card); padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow); max-width: 400px; text-align: center; border: 1px solid var(--border); }
        .verify-icon { font-size: 3rem; margin-bottom: 20px; display: block; }
    </style>
</head>
<body>
    <div class="verify-box">
        <?php if ($msg): ?>
            <i class="fa-solid fa-check-circle verify-icon" style="color:var(--green);"></i>
            <h2>Sukces!</h2>
            <p><?php echo $msg; ?></p>
            <a href="index.php" class="btn-main" style="margin-top:20px;display:inline-block;">Zaloguj się</a>
        <?php else: ?>
            <i class="fa-solid fa-circle-xmark verify-icon" style="color:var(--red);"></i>
            <h2>Błąd</h2>
            <p><?php echo $error; ?></p>
            <a href="index.php" class="btn-outline" style="margin-top:20px;display:inline-block;">Powrót</a>
        <?php endif; ?>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</body>
</html>
