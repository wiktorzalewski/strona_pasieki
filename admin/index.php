<?php
/**
 * index.php — Strona logowania panelu admina
 * Logowanie z bazy danych (tabela users)
 */
require_once __DIR__ . '/helper.php';

// Jeśli już zalogowany
if (!empty($_SESSION['admin_logged_in'])) {
    session_write_close();
    header('Location: dashboard.php');
    exit();
}

// Fallback — hardcoded credentials
define('FALLBACK_USER', 'admin');
define('FALLBACK_HASH', '$2y$12$Of4RoHOkhe2zs9bHp7t8bup7gg26.SV1ss3Evp6RtUBqIQjcgxpXi');

$error = '';
$info = '';

// Komunikat o wygaśnięciu sesji
if (isset($_GET['expired']) || (isset($_SESSION['logout_reason']) && $_SESSION['logout_reason'] === 'timeout')) {
    $info = 'Twoja sesja wygasła po 30 minutach nieaktywności. Zaloguj się ponownie.';
    unset($_SESSION['logout_reason']);
}

// Obsługa logowania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $loggedIn = false;
    
    // Próbuj z bazy danych
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=miody;charset=utf8mb4", 'miody_admin', 'YOUR_DB_PASSWORD');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Sprawdź czy konto aktywne
            if (isset($user['is_active']) && $user['is_active'] == 0) {
                $error = 'Konto jest zablokowane. Skontaktuj się z administratorem.';
            } else {
                // Zaloguj
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'] ?? 'owner';
                $_SESSION['user_permissions'] = json_decode($user['permissions'] ?? '{}', true) ?: [];
                $_SESSION['last_activity'] = time();
                $_SESSION['login_time'] = time();
                
                try {
                    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                } catch (Exception $e) {}
                
                logActivity('Logowanie', "Użytkownik: $username");
                $loggedIn = true;
            }
        }
    } catch (PDOException $e) {
        // Baza niedostępna — użyj fallbacka
    }
    
    // Fallback na hardcoded credentials
    if (!$loggedIn && !$error) {
        if ($username === FALLBACK_USER && password_verify($password, FALLBACK_HASH)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['user_role'] = 'owner';
            $_SESSION['user_permissions'] = [];
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = time();
            logActivity('Logowanie (Fallback)', "Użytkownik: admin");
            $loggedIn = true;
        }
    }
    
    if ($loggedIn) {
        session_write_close();
        header('Location: dashboard.php');
        exit();
    } elseif (!$error) {
        logActivity('Błędne logowanie', "Użytkownik: $username");
        $error = 'Nieprawidłowy login lub hasło.';
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Panel Admina — Pasieka Pod Gruszką</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1>Panel Admina</h1>
            <p class="login-subtitle">Pasieka Pod Gruszką</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($info): ?>
                <div class="alert alert-info" style="background:rgba(255,193,7,0.12);border:1px solid rgba(255,193,7,0.3);color:#ffc107;">
                    <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($info); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="username" placeholder="Login" required>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-key"></i>
                    <input type="password" name="password" placeholder="Hasło" required>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> ZALOGUJ SIĘ
                </button>
                <div style="margin-top:15px;text-align:center;">
                    <a href="forgot_password.php" style="color:#888;font-size:0.85rem;text-decoration:none;">Zapomniałeś hasła?</a>
                </div>
            </form>

            <div class="login-footer">
                <a href="https://pasiekapodgruszka.pl" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Powrót na stronę
                </a>
            </div>
        </div>
    </div>
</body>
</html>
