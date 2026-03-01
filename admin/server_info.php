<?php
/**
 * server_info.php — Informacje o serwerze
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('server_info');

$stats = getServerStats();
$dbCounts = getDbCounts();

// Rozmiary katalogów
function getDirSize($path) {
    $size = 0;
    $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
    foreach (new RecursiveIteratorIterator($it) as $file) {
        $size += $file->getSize();
    }
    return $size;
}

$projectRoot = dirname(__DIR__);
$sizes = [];
$dirs = ['assets', 'admin', 'includes', 'old_html'];
foreach ($dirs as $dir) {
    $fullPath = $projectRoot . '/' . $dir;
    if (is_dir($fullPath)) {
        $bytes = getDirSize($fullPath);
        $sizes[$dir] = $bytes < 1048576 
            ? round($bytes / 1024, 1) . ' KB' 
            : round($bytes / 1048576, 1) . ' MB';
    }
}

// Rozszerzenia PHP
$extensions = get_loaded_extensions();
sort($extensions);

// Opcache
$opcacheEnabled = function_exists('opcache_get_status') && @opcache_get_status();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Info o Serwerze — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-server" style="color: #9b59b6;"></i>
            <span class="nav-title">Info o Serwerze</span>
        </div>
        <div class="nav-right">
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <div class="dashboard-header">
            <h1><i class="fa-solid fa-server"></i> Informacje o Serwerze</h1>
            <p class="dash-subtitle">Przegląd techniczny środowiska</p>
        </div>

        <!-- GŁÓWNE INFO -->
        <div class="info-grid">
            <div class="info-card">
                <h3><i class="fa-brands fa-php" style="color: #777BB4;"></i> PHP</h3>
                <div class="info-row"><span>Wersja</span><strong><?php echo phpversion(); ?></strong></div>
                <div class="info-row"><span>SAPI</span><strong><?php echo php_sapi_name(); ?></strong></div>
                <div class="info-row"><span>Memory limit</span><strong><?php echo ini_get('memory_limit'); ?></strong></div>
                <div class="info-row"><span>Upload max</span><strong><?php echo ini_get('upload_max_filesize'); ?></strong></div>
                <div class="info-row"><span>POST max</span><strong><?php echo ini_get('post_max_size'); ?></strong></div>
                <div class="info-row"><span>Max exec time</span><strong><?php echo ini_get('max_execution_time'); ?>s</strong></div>
                <div class="info-row"><span>OPcache</span><strong><?php echo $opcacheEnabled ? '✅ Włączony' : '❌ Wyłączony'; ?></strong></div>
            </div>

            <div class="info-card">
                <h3><i class="fa-solid fa-database" style="color: #3498db;"></i> MySQL</h3>
                <div class="info-row"><span>Wersja</span><strong><?php echo $stats['mysql_version'] ?? 'Brak połączenia'; ?></strong></div>
                <div class="info-row"><span>Baza</span><strong>miody</strong></div>
                <div class="info-row"><span>Produkty</span><strong><?php echo $dbCounts['products'] ?? '?'; ?></strong></div>
                <div class="info-row"><span>Zestawy</span><strong><?php echo $dbCounts['kits'] ?? '?'; ?></strong></div>
                <div class="info-row"><span>Przepisy</span><strong><?php echo $dbCounts['recipes'] ?? '?'; ?></strong></div>
                <div class="info-row"><span>Zdjęcia</span><strong><?php echo $dbCounts['gallery_images'] ?? '?'; ?></strong></div>
            </div>

            <div class="info-card">
                <h3><i class="fa-solid fa-hard-drive" style="color: #e67e22;"></i> Serwer</h3>
                <?php if (isset($stats['disk_total'])): ?>
                <div class="info-row"><span>Dysk razem</span><strong><?php echo $stats['disk_total']; ?></strong></div>
                <div class="info-row"><span>Użyte</span><strong><?php echo $stats['disk_used']; ?> (<?php echo $stats['disk_percent']; ?>%)</strong></div>
                <div class="info-row"><span>Wolne</span><strong><?php echo $stats['disk_free']; ?></strong></div>
                <?php endif; ?>
                <?php if (isset($stats['uptime'])): ?>
                <div class="info-row"><span>Uptime</span><strong><?php echo $stats['uptime']; ?></strong></div>
                <?php endif; ?>
                <?php if (isset($stats['load'])): ?>
                <div class="info-row"><span>Load avg</span><strong><?php echo $stats['load']; ?></strong></div>
                <?php endif; ?>
                <div class="info-row"><span>OS</span><strong><?php echo PHP_OS; ?></strong></div>
            </div>
        </div>

        <!-- ROZMIARY KATALOGÓW -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-folder-tree"></i> Rozmiary katalogów</h2>
            <?php foreach ($sizes as $dir => $size): ?>
                <div class="info-row" style="padding: 10px 0; border-bottom: 1px solid var(--border);">
                    <span><i class="fa-solid fa-folder" style="color: var(--gold);"></i> /<?php echo $dir; ?>/</span>
                    <strong><?php echo $size; ?></strong>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ROZSZERZENIA PHP -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-puzzle-piece"></i> Załadowane rozszerzenia PHP (<?php echo count($extensions); ?>)</h2>
            <div class="extensions-grid">
                <?php foreach ($extensions as $ext): ?>
                    <span class="ext-badge <?php 
                        echo in_array($ext, ['openssl', 'pdo_mysql', 'mbstring', 'json', 'curl', 'gd']) ? 'ext-important' : ''; 
                    ?>"><?php echo $ext; ?></span>
                <?php endforeach; ?>
            </div>
        </div>

    </main>
</body>
</html>
