<?php
/**
 * reset_password.php — Ustawianie nowego hasła (krok 2)
 */
require_once __DIR__ . '/helper.php';

$pdo = getDB();
$success = '';
$error = '';
$token = $_GET['token'] ?? '';
$user = null;

if ($token && $pdo) {
    // Sprawdź token i czy nie wygasł
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW() AND is_active = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "Link do resetowania hasła jest nieprawidłowy lub wygasł.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pass1 = $_POST['pass1'] ?? '';
        $pass2 = $_POST['pass2'] ?? '';

        if (strlen($pass1) < 6) {
            $error = "Hasło musi mieć co najmniej 6 znaków.";
        } elseif ($pass1 !== $pass2) {
            $error = "Hasła nie są identyczne.";
        } else {
            // Zmień hasło i usuń token
            $hash = password_hash($pass1, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?")->execute([$hash, $user['id']]);
            $success = "Hasło zostało zmienione. Możesz się teraz zalogować.";
        }
    }
} else {
    $error = "Brak tokenu resetującego.";
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Ustaw Nowe Hasło — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <i class="fa-solid fa-lock-open"></i>
            </div>
            
            <?php if ($success): ?>
                <div style="text-align:center;">
                    <h1>Hasło zmienione!</h1>
                    <div class="alert alert-success" style="margin-top:20px;">
                        <i class="fa-solid fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                    <div class="login-footer">
                        <a href="index.php" class="btn-login" style="text-decoration:none; display:block;">
                            ZALOGUJ SIĘ
                        </a>
                    </div>
                </div>
            <?php elseif ($user): ?>
                <h1>Nowe Hasło</h1>
                <p class="login-subtitle">Użytkownik: <strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-group">
                        <i class="fa-solid fa-key"></i>
                        <input type="password" name="pass1" required minlength="6" placeholder="Nowe hasło" autofocus>
                    </div>
                    <div class="input-group">
                        <i class="fa-solid fa-shield-halved"></i>
                        <input type="password" name="pass2" required minlength="6" placeholder="Powtórz hasło">
                    </div>
                    <button type="submit" class="btn-login">
                        <i class="fa-solid fa-floppy-disk"></i> ZAPISZ HASŁO
                    </button>
                </form>
            <?php else: ?>
                <div style="text-align:center;">
                    <h1>Błąd</h1>
                    <div class="alert alert-error" style="margin-top:20px;">
                        <i class="fa-solid fa-circle-xmark"></i> <?php echo $error; ?>
                    </div>
                    <div class="login-footer">
                        <a href="index.php" class="back-link">
                            <i class="fa-solid fa-arrow-left"></i> Powrót do logowania
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
