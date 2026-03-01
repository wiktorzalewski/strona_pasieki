<?php
/**
 * messages.php — Menadżer Wiadomości Kontaktowych
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('messages');

$pdo = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([$id]);
    }
    if ($action === 'mark_all_read') {
        $pdo->exec("UPDATE contact_messages SET is_read=1");
        logActivity('Wiadomości', 'Oznaczono wszystkie jako przeczytane');
        $success = 'Wszystkie wiadomości oznaczone jako przeczytane.';
    }
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([$id]);
        logActivity('Wiadomości', "Usunięto wiadomość #$id");
        $success = 'Wiadomość usunięta.';
    }
}

// Filtr
$filter = $_GET['filter'] ?? 'all';
$where = $filter === 'unread' ? 'WHERE is_read=0' : '';

$messages = [];
$unreadCount = 0;
if ($pdo) {
    try {
        $messages = $pdo->query("SELECT * FROM contact_messages $where ORDER BY created_at DESC")->fetchAll();
        $unreadCount = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
    } catch (Exception $e) { $error = 'Tabela nie istnieje. Uruchom migrację.'; }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Wiadomości — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="admin-nav">
    <div class="nav-left">
        <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
        <i class="fa-solid fa-envelope" style="color:#3498db;"></i>
        <span class="nav-title">Wiadomości kontaktowe</span>
        <?php if ($unreadCount > 0): ?>
        <span style="background:#e74c3c;color:#fff;border-radius:20px;padding:2px 9px;font-size:0.78rem;font-weight:700;"><?php echo $unreadCount; ?></span>
        <?php endif; ?>
    </div>
    <div class="nav-right">
        <span class="nav-user"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars(currentUsername()); ?></span>
        <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</nav>

<main class="admin-container">
    <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <h2 style="margin:0;"><i class="fa-solid fa-inbox"></i> Skrzynka odbiorcza</h2>
        <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
            <a href="?filter=all" style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.85rem;font-weight:600;<?php echo $filter==='all'?'background:var(--gold);color:#000;':'background:var(--bg-input);color:var(--text);border:1px solid var(--border);';?>">Wszystkie</a>
            <a href="?filter=unread" style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.85rem;font-weight:600;<?php echo $filter==='unread'?'background:#e74c3c;color:#fff;':'background:var(--bg-input);color:var(--text);border:1px solid var(--border);';?>">Nieprzeczytane <?php echo $unreadCount > 0 ? "($unreadCount)" : ''; ?></a>
            <?php if ($unreadCount > 0): ?>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="mark_all_read">
                <button class="btn-small btn-outline" type="submit"><i class="fa-solid fa-check-double"></i> Oznacz wszystkie</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($messages)): ?>
    <div class="settings-card" style="text-align:center;padding:50px;">
        <i class="fa-solid fa-inbox" style="font-size:3rem;opacity:0.2;"></i>
        <p style="color:var(--text-muted);margin-top:15px;">Brak wiadomości<?php echo $filter==='unread'?' do przeczytania':''; ?>.</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px;">
    <?php foreach ($messages as $msg): ?>
        <div class="settings-card" style="padding:20px 24px;<?php echo !$msg['is_read'] ? 'border-left:3px solid #3498db;' : ''; ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                        <?php if (!$msg['is_read']): ?>
                        <span class="badge badge-info"><i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Nowa</span>
                        <?php endif; ?>
                        <strong style="font-size:1rem;"><?php echo htmlspecialchars($msg['name']); ?></strong>
                        <span style="color:var(--text-muted);font-size:0.85rem;">&lt;<?php echo htmlspecialchars($msg['email']); ?>&gt;</span>
                    </div>
                    <?php if ($msg['subject']): ?>
                    <div style="font-weight:600;margin-bottom:8px;color:var(--gold);"><?php echo htmlspecialchars($msg['subject']); ?></div>
                    <?php endif; ?>
                    <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.6;white-space:pre-wrap;"><?php echo htmlspecialchars($msg['message']); ?></p>
                    <small style="color:#555;margin-top:8px;display:block;">
                        <i class="fa-solid fa-clock"></i> <?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?>
                        <?php if ($msg['ip_address']): ?> &middot; IP: <?php echo htmlspecialchars($msg['ip_address']); ?><?php endif; ?>
                    </small>
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=Re: <?php echo urlencode($msg['subject'] ?: 'Odpowiedź'); ?>"
                       class="btn-small btn-success" title="Odpowiedz"><i class="fa-solid fa-reply"></i> Odpowiedz</a>
                    <?php if (!$msg['is_read']): ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="mark_read">
                        <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                        <button class="btn-small btn-outline" type="submit"><i class="fa-solid fa-check"></i></button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                        <button class="btn-small btn-danger" type="submit" onclick="return confirm('Usunąć tę wiadomość?')"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
