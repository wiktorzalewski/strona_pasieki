<?php
/**
 * discounts.php — Generator i Menadżer Kodów Rabatowych
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('discounts');

$pdo = getDB();
$success = '';
$error = '';

function generateCode($prefix = '', $length = 8) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = strtoupper($prefix);
    for ($i = 0; $i < $length; $i++) $code .= $chars[random_int(0, strlen($chars)-1)];
    return $code;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate') {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $_POST['prefix'] ?? ''));
        $type = in_array($_POST['type'] ?? '', ['percent','fixed']) ? $_POST['type'] : 'percent';
        $value = floatval($_POST['value'] ?? 0);
        $minOrder = floatval($_POST['min_order'] ?? 0);
        $maxUses = intval($_POST['max_uses'] ?? 0);
        $expires = $_POST['expires_at'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $qty = max(1, min(50, intval($_POST['quantity'] ?? 1)));

        if ($value <= 0) { $error = 'Wartość rabatu musi być większa od 0.'; }
        elseif ($type === 'percent' && $value > 100) { $error = 'Rabat procentowy nie może przekraczać 100%.'; }
        else {
            $created = 0;
            for ($i = 0; $i < $qty; $i++) {
                $code = generateCode($prefix, 8 - strlen($prefix));
                try {
                    $stmt = $pdo->prepare("INSERT INTO discount_codes (code,type,value,min_order,max_uses,expires_at,description) VALUES (?,?,?,?,?,?,?)");
                    $stmt->execute([$code, $type, $value, $minOrder, $maxUses, $expires ?: null, $description]);
                    $created++;
                } catch (Exception $e) { /* duplikat — pomiń */ }
            }
            logActivity('Kody rabatowe', "Wygenerowano $created kodów");
            $success = "Wygenerowano $created kodów rabatowych.";
        }
    }

    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE discount_codes SET is_active = 1-is_active WHERE id=?")->execute([$id]);
    }
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM discount_codes WHERE id=?")->execute([$id]);
        logActivity('Kody rabatowe', "Usunięto kod #$id");
        $success = 'Kod usunięty.';
    }
}

$codes = [];
try {
    $codes = $pdo ? $pdo->query("SELECT * FROM discount_codes ORDER BY created_at DESC")->fetchAll() : [];
} catch (Exception $e) { $error = 'Tabela nie istnieje. Uruchom migrację.'; }

$totalActive = count(array_filter($codes, fn($c) => $c['is_active']));
$totalUsed = array_sum(array_column($codes, 'uses_count'));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Kody Rabatowe — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .code-badge { font-family: monospace; font-size: 1.05rem; background: rgba(255,193,7,0.1); border: 1px solid rgba(255,193,7,0.3); border-radius: 8px; padding: 4px 12px; color: var(--gold); letter-spacing: 2px; cursor: pointer; }
        .code-badge:hover { background: rgba(255,193,7,0.2); }
        .expired-row { opacity: 0.5; }
    </style>
</head>
<body>
<nav class="admin-nav">
    <div class="nav-left">
        <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
        <i class="fa-solid fa-tag" style="color:#e67e22;"></i>
        <span class="nav-title">Kody Rabatowe</span>
    </div>
    <div class="nav-right">
        <span class="nav-user"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars(currentUsername()); ?></span>
        <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</nav>

<main class="admin-container">
    <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Statystyki -->
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));margin-bottom:20px;">
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-tags" style="color:#e67e22;"></i></div><div class="stat-info"><span class="stat-number"><?php echo count($codes); ?></span><span class="stat-label">Łącznie kodów</span></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-circle-check" style="color:#2ecc71;"></i></div><div class="stat-info"><span class="stat-number"><?php echo $totalActive; ?></span><span class="stat-label">Aktywnych</span></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-chart-line" style="color:#3498db;"></i></div><div class="stat-info"><span class="stat-number"><?php echo $totalUsed; ?></span><span class="stat-label">Użyć łącznie</span></div></div>
    </div>

    <!-- Generator -->
    <div class="settings-card" style="margin-bottom:24px;">
        <h2><i class="fa-solid fa-wand-magic-sparkles"></i> Generator kodów</h2>
        <form method="POST">
            <input type="hidden" name="action" value="generate">
            <div class="form-row" style="gap:14px;margin-bottom:14px;">
                <div class="form-group" style="margin:0;">
                    <label><i class="fa-solid fa-font"></i> Prefix (opcjonalny)</label>
                    <input type="text" name="prefix" placeholder="np. MIOD" maxlength="4" style="text-transform:uppercase;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label><i class="fa-solid fa-hashtag"></i> Ilość kodów</label>
                    <input type="number" name="quantity" value="1" min="1" max="50">
                </div>
                <div class="form-group" style="margin:0;">
                    <label><i class="fa-solid fa-percent"></i> Typ rabatu</label>
                    <select name="type">
                        <option value="percent">% Procentowy</option>
                        <option value="fixed">PLN Kwotowy</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label><i class="fa-solid fa-coins"></i> Wartość rabatu</label>
                    <input type="number" name="value" step="0.01" min="0.01" placeholder="np. 10" required>
                </div>
            </div>
            <div class="form-row" style="gap:14px;margin-bottom:14px;">
                <div class="form-group" style="margin:0;">
                    <label><i class="fa-solid fa-cart-shopping"></i> Min. zamówienie (PLN)</label>
                    <input type="number" name="min_order" step="0.01" min="0" value="0" placeholder="0 = brak limitu">
                </div>
                <div class="form-group" style="margin:0;">
                    <label><i class="fa-solid fa-users"></i> Maks. użyć (0 = bez limitu)</label>
                    <input type="number" name="max_uses" min="0" value="0">
                </div>
                <div class="form-group" style="margin:0;">
                    <label><i class="fa-solid fa-calendar"></i> Ważny do</label>
                    <input type="date" name="expires_at" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-note-sticky"></i> Opis / notatka</label>
                <input type="text" name="description" placeholder="np. Kampania walentynkowa">
            </div>
            <button type="submit" class="btn-save"><i class="fa-solid fa-wand-magic-sparkles"></i> Generuj kody</button>
        </form>
    </div>

    <!-- Lista kodów -->
    <div class="settings-card" style="padding:0;overflow:hidden;">
        <div style="padding:20px 24px 12px;border-bottom:1px solid var(--border);">
            <h3 style="margin:0;"><i class="fa-solid fa-list"></i> Wszystkie kody</h3>
        </div>
        <?php if (empty($codes)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:30px;">Brak kodów. Wygeneruj pierwszy!</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr>
                <th>Kod</th><th>Typ</th><th>Wartość</th><th>Min. zam.</th><th>Użycia</th><th>Ważny do</th><th>Opis</th><th>Status</th><th>Akcje</th>
            </tr></thead>
            <tbody>
            <?php foreach ($codes as $c):
                $expired = $c['expires_at'] && strtotime($c['expires_at']) < time();
                $maxed = $c['max_uses'] > 0 && $c['uses_count'] >= $c['max_uses'];
            ?>
            <tr class="<?php echo ($expired || $maxed) ? 'expired-row' : ''; ?>">
                <td>
                    <span class="code-badge" onclick="navigator.clipboard.writeText('<?php echo $c['code']; ?>');this.textContent='✓ Skopiowano!';setTimeout(()=>{this.textContent='<?php echo $c['code']; ?>';},1500)">
                        <?php echo htmlspecialchars($c['code']); ?>
                    </span>
                </td>
                <td><?php echo $c['type'] === 'percent' ? '<i class="fa-solid fa-percent" style="color:#f39c12;"></i> Proc.' : '<i class="fa-solid fa-coins" style="color:#2ecc71;"></i> Kwota'; ?></td>
                <td><strong><?php echo $c['type'] === 'percent' ? $c['value'].'%' : number_format($c['value'],2).' PLN'; ?></strong></td>
                <td><?php echo $c['min_order'] > 0 ? number_format($c['min_order'],2).' PLN' : '<span style="color:#555;">—</span>'; ?></td>
                <td>
                    <?php echo $c['uses_count']; ?>
                    <?php if ($c['max_uses'] > 0): ?> / <?php echo $c['max_uses']; ?><?php endif; ?>
                    <?php if ($maxed): ?><span class="badge badge-danger" style="font-size:0.7rem;">WYCZERPANY</span><?php endif; ?>
                </td>
                <td><?php echo $c['expires_at'] ? '<span'.($expired?' style="color:#e74c3c;"':'').'>'.date('d.m.Y', strtotime($c['expires_at'])).'</span>' : '<span style="color:#555;">∞</span>'; ?></td>
                <td style="color:var(--text-muted);font-size:0.85rem;"><?php echo htmlspecialchars($c['description']); ?></td>
                <td>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                        <button class="badge badge-btn <?php echo $c['is_active'] ? 'badge-active' : 'badge-inactive'; ?>" type="submit">
                            <?php echo $c['is_active'] ? 'Aktywny' : 'Wyłączony'; ?>
                        </button>
                    </form>
                </td>
                <td>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                        <button class="btn-small btn-danger" type="submit" onclick="return confirm('Usunąć kod <?php echo $c['code']; ?>?')"><i class="fa-solid fa-trash"></i></button>
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

<script>
// Podgląd kodu w czasie rzeczywistym
document.querySelector('[name="prefix"]')?.addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g,'');
});
</script>
</body>
</html>
