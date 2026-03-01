<?php
/**
 * redirects.php — Menadżer Przekierowań (301/302)
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('redirects');

$pdo = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $from = '/' . ltrim(trim($_POST['from_path'] ?? ''), '/');
        $to = trim($_POST['to_url'] ?? '');
        $code = in_array(intval($_POST['redirect_code']??301), [301,302]) ? intval($_POST['redirect_code']) : 301;

        if (empty($from) || $from === '/' || empty($to)) {
            $error = 'Wypełnij poprawnie ścieżkę źródłową i docelową.';
        } else {
            try {
                $pdo->prepare("INSERT INTO redirects (from_path, to_url, redirect_code) VALUES (?,?,?)")->execute([$from, $to, $code]);
                logActivity('Redirecty', "Dodano: $from → $to ($code)");
                $success = "Redirect dodany: $from → $to";
            } catch (Exception $e) {
                $error = 'Taka ścieżka źródłowa już istnieje.';
            }
        }
    }
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE redirects SET is_active=1-is_active WHERE id=?")->execute([$id]);
    }
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM redirects WHERE id=?")->execute([$id]);
        logActivity('Redirecty', "Usunięto redirect #$id");
        $success = 'Redirect usunięty.';
    }
    if ($action === 'reset_hits') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE redirects SET hits=0 WHERE id=?")->execute([$id]);
    }
}

$redirects = [];
try {
    $redirects = $pdo ? $pdo->query("SELECT * FROM redirects ORDER BY created_at DESC")->fetchAll() : [];
} catch (Exception $e) { $error = 'Tabela nie istnieje. Uruchom migrację.'; }

$totalHits = array_sum(array_column($redirects, 'hits'));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Redirect Manager — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="admin-nav">
    <div class="nav-left">
        <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
        <i class="fa-solid fa-route" style="color:#9b59b6;"></i>
        <span class="nav-title">Redirect Manager</span>
    </div>
    <div class="nav-right">
        <span class="nav-user"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars(currentUsername()); ?></span>
        <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</nav>

<main class="admin-container">
    <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="alert alert-info" style="margin-bottom:20px;">
        <i class="fa-solid fa-circle-info"></i>
        Redirecty działają dzięki sprawdzaniu w <code>includes/header.php</code>. Ścieżki są sprawdzane dla każdego żądania publicznej strony.
    </div>

    <!-- Statystyki -->
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));margin-bottom:20px;">
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-right-left" style="color:#9b59b6;"></i></div><div class="stat-info"><span class="stat-number"><?php echo count($redirects); ?></span><span class="stat-label">Redirectów</span></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-circle-check" style="color:#2ecc71;"></i></div><div class="stat-info"><span class="stat-number"><?php echo count(array_filter($redirects, fn($r)=>$r['is_active'])); ?></span><span class="stat-label">Aktywnych</span></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-arrow-pointer" style="color:#3498db;"></i></div><div class="stat-info"><span class="stat-number"><?php echo $totalHits; ?></span><span class="stat-label">Łącznie uderzeń</span></div></div>
    </div>

    <!-- Formularz dodawania -->
    <div class="settings-card" style="margin-bottom:24px;">
        <h2><i class="fa-solid fa-plus-circle"></i> Dodaj redirect</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-row" style="gap:14px;align-items:end;">
                <div class="form-group" style="margin:0;flex:1;">
                    <label><i class="fa-solid fa-arrow-right-to-bracket"></i> Ścieżka źródłowa</label>
                    <input type="text" name="from_path" placeholder="/stara-strona" required>
                    <small>Pełna ścieżka, np. /stary-miod-lipowy</small>
                </div>
                <div style="font-size:1.5rem;color:var(--text-muted);margin-bottom:22px;">&rarr;</div>
                <div class="form-group" style="margin:0;flex:1;">
                    <label><i class="fa-solid fa-arrow-right-from-bracket"></i> Cel przekierowania</label>
                    <input type="text" name="to_url" placeholder="/produkt/miod-lipowy lub https://..." required>
                    <small>Nowa ścieżka lub pełny URL</small>
                </div>
                <div class="form-group" style="margin:0;width:130px;">
                    <label><i class="fa-solid fa-hashtag"></i> Kod HTTP</label>
                    <select name="redirect_code">
                        <option value="301">301 Trwały</option>
                        <option value="302">302 Tymcz.</option>
                    </select>
                </div>
                <button type="submit" class="btn-save" style="margin-bottom:22px;"><i class="fa-solid fa-plus"></i> Dodaj</button>
            </div>
        </form>
    </div>

    <!-- Lista redirectów -->
    <div class="settings-card" style="padding:0;overflow:hidden;">
        <div style="padding:20px 24px 12px;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fa-solid fa-list"></i> Skonfigurowane redirecty</h3>
        </div>
        <?php if (empty($redirects)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:30px;">Brak redirectów. Dodaj pierwszy!</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr>
                <th>Kod</th><th>Źródło</th><th></th><th>Cel</th><th>Uderzenia</th><th>Status</th><th>Akcje</th>
            </tr></thead>
            <tbody>
            <?php foreach ($redirects as $r): ?>
            <tr>
                <td><span class="badge <?php echo $r['redirect_code']==301?'badge-warning':'badge-info'; ?>"><?php echo $r['redirect_code']; ?></span></td>
                <td><code style="font-size:0.85rem;color:var(--text);"><?php echo htmlspecialchars($r['from_path']); ?></code></td>
                <td style="color:var(--text-muted);">→</td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.85rem;">
                    <a href="<?php echo htmlspecialchars($r['to_url']); ?>" target="_blank" style="color:var(--blue);text-decoration:none;"><?php echo htmlspecialchars($r['to_url']); ?></a>
                </td>
                <td>
                    <strong><?php echo number_format($r['hits']); ?></strong>
                    <?php if ($r['hits'] > 0): ?>
                    <form method="POST" style="display:inline;margin-left:4px;">
                        <input type="hidden" name="action" value="reset_hits">
                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                        <button style="background:none;border:none;color:#555;cursor:pointer;font-size:0.75rem;" title="Resetuj licznik">↺</button>
                    </form>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                        <button class="badge badge-btn <?php echo $r['is_active'] ? 'badge-active' : 'badge-inactive'; ?>" type="submit">
                            <?php echo $r['is_active'] ? 'Aktywny' : 'Wyłączony'; ?>
                        </button>
                    </form>
                </td>
                <td>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                        <button class="btn-small btn-danger" type="submit" onclick="return confirm('Usunąć redirect?')"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
