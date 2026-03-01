<?php
/**
 * Temporary dashboard diagnostic - REMOVE AFTER USE
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "<pre>Dashboard Diagnostic\n";

try {
    echo "Step 1: Loading admin/helper.php...\n";
    require_once __DIR__ . '/admin/helper.php';
    echo "Step 1: OK\n";

    echo "Step 2: getSetting()...\n";
    $maintenanceOn = getSetting('maintenance_mode', '0') === '1';
    echo "Step 2: OK - maintenance=" . ($maintenanceOn ? 'ON' : 'OFF') . "\n";

    echo "Step 3: getServerStats()...\n";
    $stats = getServerStats();
    echo "Step 3: OK\n";

    echo "Step 4: getDbCounts()...\n";
    $dbCounts = getDbCounts();
    echo "Step 4: OK - " . json_encode($dbCounts) . "\n";

    echo "Step 5: loadVault()...\n";
    $vault = loadVault();
    $vaultEntries = 0;
    foreach ($vault as $cat) {
        $vaultEntries += count($cat['entries'] ?? []);
    }
    echo "Step 5: OK - entries=$vaultEntries\n";

    echo "Step 6: isOwner() check...\n";
    echo "Session user_role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "\n";
    echo "Step 6: OK\n";

    echo "\nAll dashboard steps passed! The 500 error may be in template HTML.\n";
} catch (Throwable $e) {
    echo "\nFATAL: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
echo "</pre>";
