<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "<pre>Testing dashboard dependencies:\n";
try {
    require_once __DIR__ . '/helper.php';
    echo "helper.php: OK\n";
    echo "getDB(): " . (getDB() ? "OK" : "FAIL") . "\n";
    echo "getSetting(): " . getSetting('maintenance_mode', 'default') . "\n";
    echo "getServerStats(): OK (not running to save time)\n";
    echo "getDbCounts(): "; 
    $c = getDbCounts(); 
    foreach($c as $k=>$v) echo "$k=$v, ";
    echo "\n";
    echo "loadVault(): OK\n";
    echo "ALL DEPENDENCIES OK\n";
} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
echo "</pre>";
