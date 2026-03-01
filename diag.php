<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "PHP: " . PHP_VERSION . "\n";

try {
    echo "1. Loading helper...\n";
    require_once __DIR__ . '/admin/helper.php';
    echo "2. Helper OK\n";
    
    echo "3. DB connection...\n";
    $pdo = getDB();
    echo $pdo ? "4. DB OK\n" : "4. DB FAIL\n";
    
    echo "5. Tables:\n";
    foreach (['products', 'kits', 'recipes', 'users', 'activity_log', 'settings'] as $t) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            echo "  - $t: $count rows\n";
        } catch (Exception $e) {
            echo "  - $t: ERROR - " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "FATAL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
echo "</pre>";
