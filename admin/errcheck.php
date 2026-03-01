<?php
/**
 * Temporary error log checker
 * READ ONLY - safe to run
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "<pre>\n";

// Check PHP error log
$logPaths = [
    '/var/log/nginx/error.log',
    '/var/log/php8.2-fpm.log',
    '/var/log/php-fpm/error.log', 
    '/tmp/php_errors.log',
    ini_get('error_log')
];

foreach ($logPaths as $path) {
    if ($path && is_readable($path)) {
        echo "=== $path (last 20 lines) ===\n";
        $lines = file($path);
        $tail = array_slice($lines, -20);
        foreach ($tail as $line) echo htmlspecialchars($line);
        break;
    }
}

// Also test dashboard dependencies step by step
echo "\n=== Dashboard dependency test ===\n";
try {
    require_once __DIR__ . '/helper.php';
    echo "helper.php: OK\n";
    $stats = getServerStats(); echo "getServerStats(): OK\n";
    $dbCounts = getDbCounts(); echo "getDbCounts(): OK\n";
    $vault = loadVault(); echo "loadVault(): OK\n";
    echo "All OK!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "In: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
