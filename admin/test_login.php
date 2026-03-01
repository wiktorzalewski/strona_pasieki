<?php
/**
 * test_login.php — DIAGNOSTYKA LOGOWANIA
 * USUŃ PO NAPRAWIENIU PROBLEMU!
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnostyka logowania</h2>";
echo "<pre>";

// 1. Test sesji
echo "1. Test sesji:\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['test'] = 'ok';
echo "   Session status: " . session_status() . " (2 = aktywna)\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session test: " . ($_SESSION['test'] ?? 'FAIL') . "\n\n";

// 2. Test hasła
$password = 'YOUR_TEST_PASSWORD';
$hash = '$2y$12$Of4RoHOkhe2zs9bHp7t8bup7gg26.SV1ss3Evp6RtUBqIQjcgxpXi';

echo "2. Test password_verify:\n";
echo "   Hasło: " . $password . "\n";
echo "   Hash: " . $hash . "\n";
echo "   Długość hash: " . strlen($hash) . " (powinno być 60)\n";
$result = password_verify($password, $hash);
echo "   password_verify(): " . ($result ? 'TRUE ✅' : 'FALSE ❌') . "\n\n";

// 3. Test — wygeneruj nowy hash z tego hasła
echo "3. Nowy hash z tego hasła:\n";
$newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "   Nowy hash: " . $newHash . "\n";
echo "   Verify nowego hash: " . (password_verify($password, $newHash) ? 'TRUE ✅' : 'FALSE ❌') . "\n\n";

// 4. Test zapisu plików
echo "4. Test zapisu plików:\n";
$logsDir = __DIR__ . '/logs';
echo "   Katalog logs: " . $logsDir . "\n";
echo "   Istnieje: " . (is_dir($logsDir) ? 'TAK' : 'NIE') . "\n";
if (!is_dir($logsDir)) {
    $mkdirResult = @mkdir($logsDir, 0755, true);
    echo "   Próba utworzenia: " . ($mkdirResult ? 'OK ✅' : 'FAIL ❌') . "\n";
}
$testFile = $logsDir . '/test.txt';
$writeResult = @file_put_contents($testFile, 'test');
echo "   Zapis pliku testowego: " . ($writeResult !== false ? 'OK ✅' : 'FAIL ❌') . "\n";
if (file_exists($testFile)) @unlink($testFile);

// 5. Test hasła z auth.php
echo "\n5. Test stałych z auth.php:\n";
require_once __DIR__ . '/auth.php';
echo "   ADMIN_USERNAME: " . ADMIN_USERNAME . "\n";
echo "   ADMIN_PASSWORD_HASH: " . ADMIN_PASSWORD_HASH . "\n";
echo "   Hash length: " . strlen(ADMIN_PASSWORD_HASH) . "\n";
echo "   password_verify z ADMIN_PASSWORD_HASH: " . (password_verify($password, ADMIN_PASSWORD_HASH) ? 'TRUE ✅' : 'FALSE ❌') . "\n";

echo "</pre>";
echo "<p><strong style='color:red;'>USUŃ TEN PLIK PO UŻYCIU!</strong></p>";
?>
