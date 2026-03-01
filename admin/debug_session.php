<?php
/**
 * debug_session.php — Diagnostyka sesji
 * USUŃ TEN PLIK PO NAPRAWIENIU PROBLEMU!
 */
date_default_timezone_set('Europe/Warsaw');
session_start();

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTYKA SESJI ===\n\n";
echo "Czas serwera: " . date('Y-m-d H:i:s') . "\n";
echo "Strefa: " . date_default_timezone_get() . "\n";
echo "PHP: " . phpversion() . "\n\n";

echo "=== SESSION INFO ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session name: " . session_name() . "\n";
echo "Session save path: " . session_save_path() . "\n";
echo "Session cookie params:\n";
print_r(session_get_cookie_params());

echo "\n=== SESSION DATA ===\n";
if (empty($_SESSION)) {
    echo "(PUSTA - nie ma danych sesji)\n";
} else {
    print_r($_SESSION);
}

echo "\n=== COOKIES ===\n";
if (empty($_COOKIE)) {
    echo "(BRAK COOKIES)\n";
} else {
    print_r($_COOKIE);
}

echo "\n=== REQUEST ===\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'brak') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'brak') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'brak') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'brak') . "\n";

echo "\n=== TEST ===\n";
if (!empty($_SESSION['admin_logged_in'])) {
    echo "STATUS: ZALOGOWANY ✅\n";
    echo "Login time: " . date('H:i:s', $_SESSION['login_time'] ?? 0) . "\n";
    echo "Last activity: " . date('H:i:s', $_SESSION['last_activity'] ?? 0) . "\n";
} else {
    echo "STATUS: NIEZALOGOWANY ❌\n";
    echo "Sesja jest pusta lub admin_logged_in nie jest ustawione.\n";
    echo "Zaloguj się na index.php, a potem wejdź tutaj ponownie.\n";
}
