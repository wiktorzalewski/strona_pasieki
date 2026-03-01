<?php
/**
 * sessions.php — Menadżer Aktywnych Sesji Adminów
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requireOwner(); // Tylko właściciel

$pdo = getDB();
$success = '';
$error = '';
$currentSession = session_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    if ($action === 'revoke') {
        $sessionId = $_POST['session_id'] ?? '';
        if ($sessionId === $currentSession) {
            $error = 'Nie możesz zakończyć własnej aktywnej sesji.';
        } else {
            $pdo->prepare("DELETE FROM admin_sessions WHERE session_id=?")->execute([$sessionId]);
            // Zniszcz sesję PHP jeśli istnieje
            session_write_close();
            // Zapis pliku sesji PHP — usuń go
            $sessionFile = session_save_path() . '/sess_' . $sessionId;
            if (file_exists($sessionFile)) @unlink($sessionFile);
            session_start();
            logActivity('Sesje', "Unieważniono sesję: " . substr($sessionId, 0, 8) . '...');
            $success = 'Sesja została zakończona.';
        }
    }
    if ($action === 'revoke_all_others') {
        $pdo->prepare("DELETE FROM admin_sessions WHERE session_id != ?")->execute([$currentSession]);
        $pdo->exec("DELETE FROM admin_sessions WHERE session_id NOT IN (SELECT * FROM (SELECT session_id FROM admin_sessions WHERE session_id = '$currentSession') t)");
        logActivity('Sesje', 'Unieważniono wszystkie inne sesje');
        $success = 'Wszystkie inne sesje zostały zakończone.';
    }
    if ($action === 'clean_expired') {
        $pdo->exec("DELETE FROM admin_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
        $success = 'Wygasłe sesje usunięte.';
    }
}

$sessions = [];
try {
    $sessions = $pdo ? $pdo->query("
        SELECT *, 
               TIMESTAMPDIFF(MINUTE, last_activity, NOW()) as idle_min,
               TIMESTAMPDIFF(MINUTE, created_at, NOW()) as age_min
        FROM admin_sessions 
        ORDER BY last_activity DESC
    ")->fetchAll() : [];
} catch (Exception $e) { $error = 'Tabela nie istnieje. Uruchom migrację pierwszy.'; }

// Aktualizuj własną sesję
try {
    if ($pdo) {
        $userId = $_SESSION['user_id'] ?? 0;
        $pdo->prepare("INSERT INTO admin_sessions (session_id, user_id, username, ip_address, user_agent) VALUES (?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE last_activity=NOW(), username=?, ip_address=?, user_agent=?")
            ->execute([
                $currentSession, $userId, currentUsername(),
                $_SERVER['REMOTE_ADDR'] ?? '', substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                currentUsername(), $_SERVER['REMOTE_ADDR'] ?? '', substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
            ]);
    }
} catch (Exception $e) {}

function getDeviceIcon($ua) {
    $ua = strtolower($ua);
    if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false) return 'fa-mobile-screen-button';
    if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) return 'fa-tablet-screen-button';
    return 'fa-desktop';
}
function getBrowserName($ua) {
    if (strpos($ua, 'Firefox') !== false) return 'Firefox';
    if (strpos($ua, 'Edge') !== false || strpos($ua, 'Edg/') !== false) return 'Edge';
    if (strpos($ua, 'Safari') !== false && strpos($ua, 'Chrome') === false) return 'Safari';
    if (strpos($ua, 'Chrome') !== false) return 'Chrome';
    if (strpos($ua, 'OPR') !== false || strpos($ua, 'Opera') !== false) return 'Opera';
    return 'Inny';
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Menadżer Sesji — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .session-card { background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:20px 24px; margin-bottom:12px; display:flex; align-items:center; gap:18px; transition:all 0.3s; }
        .session-card:hover { border-color: var(--border-hover); }
        .session-card.current-session { border-color: rgba(46,204,113,0.4); background:rgba(46,204,113,0.04); }
        .session-card.idle-long { border-color: rgba(243,156,18,0.3); }
        .session-avatar { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
    </style>
</head>
<body>
<nav class="admin-nav">
    <div class="nav-left">
        <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
        <i class="fa-solid fa-desktop" style="color:#e74c3c;"></i>
        <span class="nav-title">Menadżer Sesji</span>
    </div>
    <div class="nav-right">
        <span class="nav-user"><i class="fa-solid fa-crown" style="color:#ffc107;"></i> <?php echo htmlspecialchars(currentUsername()); ?></span>
        <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</nav>

<main class="admin-container">
    <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Akcje globalne -->
    <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
        <h2 style="margin:0;flex:1;"><i class="fa-solid fa-shield-halved"></i> Aktywne sesje adminów</h2>
        <?php $otherSessions = array_filter($sessions, fn($s)=>$s['session_id']!==$currentSession); ?>
        <?php if (!empty($otherSessions)): ?>
        <form method="POST" style="margin:0;">
            <input type="hidden" name="action" value="revoke_all_others">
            <button class="btn-small btn-danger" onclick="return confirm('Zakończyć wszystkie inne sesje?')">
                <i class="fa-solid fa-user-slash"></i> Zakończ wszystkie inne
            </button>
        </form>
        <?php endif; ?>
        <form method="POST" style="margin:0;">
            <input type="hidden" name="action" value="clean_expired">
            <button class="btn-small btn-outline"><i class="fa-solid fa-broom"></i> Wyczyść wygasłe</button>
        </form>
    </div>

    <?php if (empty($sessions)): ?>
    <div class="settings-card" style="text-align:center;padding:40px;">
        <i class="fa-solid fa-desktop" style="font-size:3rem;opacity:0.2;"></i>
        <p style="color:var(--text-muted);margin-top:15px;">Brak zapisanych sesji. Zaloguj się ponownie, aby zobaczyć sesje.</p>
    </div>
    <?php else: ?>
    <div style="margin-bottom:20px;">
        <?php foreach ($sessions as $s):
            $isCurrent = $s['session_id'] === $currentSession;
            $isIdle = $s['idle_min'] > 15;
            $isExpired = $s['idle_min'] > 30;
            $browser = getBrowserName($s['user_agent'] ?? '');
            $deviceIcon = getDeviceIcon($s['user_agent'] ?? '');
            $cardClass = $isCurrent ? 'current-session' : ($isIdle ? 'idle-long' : '');
        ?>
        <div class="session-card <?php echo $cardClass; ?>">
            <div class="session-avatar" style="background:<?php echo $isCurrent ? 'rgba(46,204,113,0.15)' : 'rgba(255,255,255,0.05)'; ?>;">
                <i class="fa-solid <?php echo $deviceIcon; ?>" style="color:<?php echo $isCurrent ? '#2ecc71' : 'var(--text-muted)'; ?>;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <strong style="font-size:1rem;"><?php echo htmlspecialchars($s['username']); ?></strong>
                    <?php if ($isCurrent): ?>
                    <span class="badge badge-success"><i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Twoja sesja</span>
                    <?php elseif ($isExpired): ?>
                    <span class="badge badge-danger">Wygasła (>30min)</span>
                    <?php elseif ($isIdle): ?>
                    <span class="badge badge-warning">Nieaktywna</span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:0.82rem;color:var(--text-muted);">
                    <span><i class="fa-solid fa-globe"></i> <?php echo htmlspecialchars($s['ip_address'] ?? '—'); ?></span>
                    <span><i class="fa-brands fa-chrome"></i> <?php echo $browser; ?></span>
                    <span><i class="fa-solid fa-clock"></i> Ostatnia aktyw.: <?php echo $s['idle_min']; ?> min temu</span>
                    <span><i class="fa-solid fa-calendar"></i> Zalogowano: <?php echo date('d.m.Y H:i', strtotime($s['created_at'])); ?></span>
                    <span style="font-family:monospace;font-size:0.78rem;opacity:0.4;">ID: <?php echo substr($s['session_id'], 0, 12); ?>...</span>
                </div>
            </div>
            <?php if (!$isCurrent): ?>
            <form method="POST" style="margin:0;flex-shrink:0;">
                <input type="hidden" name="action" value="revoke">
                <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($s['session_id']); ?>">
                <button class="btn-small btn-danger" onclick="return confirm('Zakończyć sesję użytkownika <?php echo htmlspecialchars($s['username']); ?>?')">
                    <i class="fa-solid fa-ban"></i> Zakończ
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info">
        <i class="fa-solid fa-circle-info"></i>
        Sesje starsze niż 30 minut są automatycznie uznawane za wygasłe. Sesje są rejestrowane przy każdym załadowaniu stron administracyjnych.
    </div>

    <?php endif; ?>
</main>

<script>
// Auto-odświeżanie co 60s
setTimeout(() => location.reload(), 60000);
</script>
</body>
</html>
