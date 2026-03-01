<?php
date_default_timezone_set('Europe/Warsaw');

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
         || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

if (session_status() === PHP_SESSION_NONE) {
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

session_unset();
session_destroy();

// Wyczyść ciasteczko sesji
if (ini_get('session.use_cookies')) {
    setcookie('ADMINSID', '', time() - 3600, '/');
}

header('Location: index.php');
exit();
