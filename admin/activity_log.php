<?php
/**
 * activity_log.php — Dziennik aktywności
 */
require_once __DIR__ . '/helper.php';
requireLogin();
// Tylko właściciel i admin mogą widzieć logi
if (!isOwner() && !hasPermission('admin')) {
    header('Location: dashboard.php');
    exit();
}

$pdo = getDB();
$success = '';
$error = '';

// Czyszczenie starych logów (opcjonalnie)
if (isset($_POST['action']) && $_POST['action'] === 'clear_old' && isOwner()) {
    try {
        $pdo->exec("DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $success = 'Starsze logi (powyżej 30 dni) zostały usunięte.';
    } catch (Exception $e) { $error = 'Błąd: ' . $e->getMessage(); }
}

// Pobieranie logów
$logs = [];
if ($pdo) {
    try {
        $logs = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 200")->fetchAll();
    } catch (Exception $e) {
        $error = 'Błąd bazy danych. Czy migracja została uruchomiona? ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Dziennik Aktywności — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-list-ul" style="color: #3498db;"></i>
            <span class="nav-title">Dziennik Aktywności</span>
        </div>
        <div class="nav-right">
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="settings-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2><i class="fa-solid fa-history"></i> Ostatnie działania</h2>
                <?php if (isOwner()): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="clear_old">
                    <button type="submit" class="btn-small btn-outline" onclick="return confirm('Usunąć logi starsze niż 30 dni?')">
                        <i class="fa-solid fa-broom"></i> Wyczyść stare logi
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <div class="crud-table-wrap">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Użytkownik</th>
                            <th>Akcja</th>
                            <th>Szczegóły</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">Brak wpisów w dzienniku.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td data-label="Data"><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td data-label="Użytkownik">
                                <strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong>
                                <?php if ($log['user_id']): ?><small style="display:block; color:var(--text-muted);">ID: <?php echo $log['user_id']; ?></small><?php endif; ?>
                            </td>
                            <td data-label="Akcja">
                                <span class="badge badge-info"><?php echo htmlspecialchars($log['action']); ?></span>
                            </td>
                            <td data-label="Szczegóły" style="font-size:0.9rem; max-width:400px;"><?php echo htmlspecialchars($log['details'] ?? '—'); ?></td>
                            <td data-label="IP"><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
