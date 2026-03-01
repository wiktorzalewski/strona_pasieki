<?php
/**
 * backups.php — Narzędzie do kopii zapasowych
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('maintenance');

$pdo = getDB();
$success = '';
$error = '';
$backupDir = __DIR__ . '/backups';

if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}

// Obsługa akcji
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'db') {
        try {
            $filename = 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = $backupDir . '/' . $filename;
            
            $handle = fopen($filePath, 'w');
            fwrite($handle, "-- Pasieka Pod Gruszką - Database Backup\n");
            fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n\n");
            
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                // Create table
                $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($handle, $create['Create Table'] . ";\n\n");
                
                // Data
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $keys = array_keys($row);
                    $vals = array_map(function($v) use ($pdo) {
                        if ($v === null) return 'NULL';
                        return $pdo->quote($v);
                    }, array_values($row));
                    
                    fwrite($handle, "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $vals) . ");\n");
                }
                fwrite($handle, "\n");
            }
            fclose($handle);
            logActivity('Utworzono kopię bazy', $filename);
            $success = "Kopia bazy danych utworzona: $filename";
        } catch (Exception $e) {
            $error = "Błąd kopii bazy: " . $e->getMessage();
        }
    }
    
    if ($action === 'assets') {
        try {
            $filename = 'assets_backup_' . date('Y-m-d_H-i-s') . '.zip';
            $filePath = $backupDir . '/' . $filename;
            
            $zip = new ZipArchive();
            if ($zip->open($filePath, ZipArchive::CREATE) === TRUE) {
                $rootPath = dirname(__DIR__) . '/assets';
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);
                
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePathReal = $file->getRealPath();
                        $relativePath = 'assets/' . substr($filePathReal, strlen($rootPath) + 1);
                        $zip->addFile($filePathReal, $relativePath);
                    }
                }
                $zip->close();
                logActivity('Utworzono kopię assetów', $filename);
                $success = "Kopia plików (assetów) utworzona: $filename";
            } else {
                $error = "Nie udało się utworzyć pliku ZIP.";
            }
        } catch (Exception $e) {
            $error = "Błąd kopii assetów: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $path = $backupDir . '/' . $file;
        if (is_file($path)) {
            unlink($path);
            $success = "Usunięto plik: $file";
        }
    }
    
    if ($action === 'download' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $path = $backupDir . '/' . $file;
        if (is_file($path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        }
    }
}

$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $path = $backupDir . '/' . $f;
            $backups[] = [
                'name' => $f,
                'size' => round(filesize($path) / 1024 / 1024, 2) . ' MB',
                'date' => date('Y-m-d H:i:s', filemtime($path)),
                'type' => strpos($f, 'db') !== false ? 'Baza' : 'Pliki'
            ];
        }
    }
    usort($backups, function($a, $b) { return strcmp($b['date'], $a['date']); });
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopie Zapasowe — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-database" style="color:var(--gold);"></i>
            <span class="nav-title">Kopie Zapasowe</span>
        </div>
        <div class="nav-right">
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <div class="settings-card">
            <h2><i class="fa-solid fa-server"></i> Utwórz nową kopię</h2>
            <div style="display:flex; gap:15px; margin-top:20px;">
                <a href="?action=db" class="btn-main" style="flex:1; text-align:center;">
                    <i class="fa-solid fa-file-export"></i> Zrzut Bazy (SQL)
                </a>
                <a href="?action=assets" class="btn-save" style="flex:1; text-align:center;">
                    <i class="fa-solid fa-file-zipper"></i> Archiwum Assetów (ZIP)
                </a>
            </div>
            <p style="margin-top:15px; color:var(--text-muted); font-size:0.9rem;">
                <i class="fa-solid fa-circle-info"></i> Kopie zapisywane są w folderze <code>admin/backups/</code>. Pamiętaj, aby pobrać je lokalnie i usunąć ze strony dla bezpieczeństwa.
            </p>
        </div>

        <div class="settings-card">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Istniejące kopie (<?php echo count($backups); ?>)</h2>
            <div class="crud-table-wrap">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nazwa pliku</th>
                            <th>Typ</th>
                            <th>Rozmiar</th>
                            <th>Data powstania</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">Brak utworzonych kopii.</td></tr>
                        <?php else: ?>
                            <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td data-label="Nazwa"><code><?php echo htmlspecialchars($b['name']); ?></code></td>
                                    <td data-label="Typ">
                                        <span class="badge <?php echo $b['type'] === 'Baza' ? 'badge-info' : 'badge-admin'; ?>">
                                            <?php echo $b['type']; ?>
                                        </span>
                                    </td>
                                    <td data-label="Rozmiar"><?php echo $b['size']; ?></td>
                                    <td data-label="Data"><?php echo $b['date']; ?></td>
                                    <td data-label="Akcje">
                                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                                            <a href="?action=download&file=<?php echo urlencode($b['name']); ?>" class="btn-small btn-outline" title="Pobierz"><i class="fa-solid fa-download"></i></a>
                                            <a href="?action=delete&file=<?php echo urlencode($b['name']); ?>" class="btn-small btn-danger" onclick="return confirm('Usunąć ten plik kopii?');" title="Usuń"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
