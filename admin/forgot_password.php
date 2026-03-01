<?php
/**
 * forgot_password.php — Resetowanie hasła (krok 1)
 */
require_once __DIR__ . '/helper.php';

$pdo = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $email = trim($_POST['email'] ?? '');
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Sprawdź czy użytkownik istnieje i ma zweryfikowany email
        $stmt = $pdo->prepare("SELECT id, username, email_verified FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['email_verified']) {
                $token = generateToken();
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")->execute([$token, $expiry, $user['id']]);
                
                $link = "https://pasiekapodgruszka.pl/admin/reset_password.php?token=$token";
                $message = "<h1>Reset hasła</h1>
                            <p>Witaj " . htmlspecialchars($user['username']) . ",</p>
                            <p>Otrzymaliśmy prośbę o reset hasła do Twojego konta administratora.</p>
                            <p>Kliknij poniższy link, aby ustawić nowe hasło (ważny przez godzinę):</p>
                            <p><a href='$link' class='btn'>Zresetuj hasło</a></p>
                            <p>Jeśli to nie Ty prosiłeś o reset, zignoruj tę wiadomość.</p>";
                
                sendEmail($email, "Reset hasła - Pasieka Pod Gruszką", $message);
                $success = "Jeśli podany adres email istnieje i jest zweryfikowany, wysłaliśmy na niego link do resetowania hasła.";
            } else {
                $error = "Adres email nie został zweryfikowany. Skontaktuj się z właścicielem strony.";
            }
        } else {
            // Bezpieczeństwo: Nie informuj czy email istnieje
            $success = "Jeśli podany adres email istnieje i jest zweryfikowany, wysłaliśmy na niego link do resetowania hasła.";
        }
    } else {
        $error = "Nieprawidłowy adres email.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Zapomniałem hasła — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <i class="fa-solid fa-key"></i>
            </div>
            <h1>Reset Hasła</h1>
            <p class="login-subtitle">Odzyskaj dostęp do panelu</p>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i> <?php echo $success; ?>
                </div>
                <div class="login-footer">
                    <a href="index.php" class="btn-login" style="text-decoration:none; display:block;">
                        <i class="fa-solid fa-arrow-left"></i> Powrót do logowania
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-group">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" required placeholder="Adres Email" autofocus>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fa-solid fa-paper-plane"></i> WYŚLIJ LINK
                    </button>
                    
                    <div class="login-footer">
                        <a href="index.php" class="back-link">
                            <i class="fa-solid fa-arrow-left"></i> Powrót do logowania
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
