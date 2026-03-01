<?php
/**
 * accounts.php — Menadżer kont adminów
 * Dostępne TYLKO dla roli owner (konto admin)
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requireOwner();

$pdo = getDB();
$success = '';
$error = '';

// Dostępne uprawnienia
$allPermissions = [
    'products'    => ['icon' => 'fa-honey-pot',          'label' => 'Produkty'],
    'kits'        => ['icon' => 'fa-gift',               'label' => 'Zestawy'],
    'recipes'     => ['icon' => 'fa-utensils',           'label' => 'Przepisy'],
    'gallery'     => ['icon' => 'fa-images',             'label' => 'Galeria'],
    'newsletter'  => ['icon' => 'fa-paper-plane',        'label' => 'Newsletter'],
    'google_reviews' => ['icon' => 'fa-google',          'label' => 'Opinie Google'],
    'messages'    => ['icon' => 'fa-envelope',           'label' => 'Wiadomości'],
    'discounts'   => ['icon' => 'fa-tag',                'label' => 'Kody rabatowe'],
    'redirects'   => ['icon' => 'fa-route',              'label' => 'Redirect Manager'],
    'maintenance' => ['icon' => 'fa-screwdriver-wrench', 'label' => 'Przerwa techniczna'],
    'vault'       => ['icon' => 'fa-vault',              'label' => 'Sejf haseł'],
    'server_info' => ['icon' => 'fa-server',             'label' => 'Info o serwerze'],
    'analytics'   => ['icon' => 'fa-chart-line',         'label' => 'Analityka'],
    'export'      => ['icon' => 'fa-file-csv',           'label' => 'Export CSV'],
    'backups'     => ['icon' => 'fa-database',           'label' => 'Kopie zapasowe'],
    'seo'         => ['icon' => 'fa-magnifying-glass',   'label' => 'Edytor SEO'],
    'page_config' => ['icon' => 'fa-gears',              'label' => 'Treść strony'],
    'activity_log'=> ['icon' => 'fa-list-ol',            'label' => 'Logi aktywności'],
];

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    // Dodaj konto
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? ''); // New
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'editor';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Zbierz uprawnienia
        $perms = [];
        foreach ($allPermissions as $key => $info) {
            $perms[$key] = isset($_POST['perm_' . $key]) ? true : false;
        }
        
        if (strlen($username) < 3) {
            $error = 'Login musi mieć co najmniej 3 znaki.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Podaj prawidłowy adres email.';
        } elseif (strlen($password) < 6) {
            $error = 'Hasło musi mieć co najmniej 6 znaków.';
        } elseif ($username === 'admin') {
            $error = 'Nie można utworzyć konta o nazwie "admin".';
        } else {
            // Sprawdź czy login lub email nie istnieje
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            if ($check->fetchColumn() > 0) {
                $error = 'Login lub email jest już zajęty.';
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $permsJson = json_encode($perms, JSON_UNESCAPED_UNICODE);
                    $token = generateToken(); // Helper function
                    
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, permissions, is_active, created_by, email_verified, email_token) VALUES (?,?,?,?,?,?,?,0,?)");
                    $stmt->execute([$username, $email, $hash, $role, $permsJson, $is_active, $_SESSION['user_id'], $token]);
                    logActivity('Utworzono konto', "Użytkownik: $username, Rola: $role");
                    
                    // Wyślij email weryfikacyjny
                    $link = "https://pasiekapodgruszka.pl/admin/verify_email.php?token=$token";
                    sendEmail($email, "Potwierdź adres email - Pasieka Pod Gruszką", 
                        "<h1>Witaj $username!</h1><p>Twoje konto administratora zostało utworzone.</p><p>Kliknij poniższy link, aby potwierdzić adres email (wymagane do resetowania hasła):</p><p><a href='$link' class='btn'>Potwierdź email</a></p>");
                    
                    $success = 'Utworzono konto. Email weryfikacyjny wysłany.';
                } catch (PDOException $e) {
                    $error = 'Błąd bazy: ' . $e->getMessage();
                }
            }
        }
    }
    
    // Edytuj konto
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? 'editor';
        $email = trim($_POST['email'] ?? ''); // New
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $newPassword = $_POST['password'] ?? '';
        
        // Nie pozwól edytować konta admin
        $target = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $target->execute([$id]);
        $targetData = $target->fetch();
        $targetUser = $targetData['username'] ?? '';
        $oldEmail = $targetData['email'] ?? '';
        
        if ($targetUser === 'admin') {
            $error = 'Nie można edytować konta właściciela.';
        } elseif ($id) {
            // Check email uniqueness if changed
            $emailChanged = ($email !== $oldEmail);
            if ($emailChanged) {
                $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                $check->execute([$email, $id]);
                if ($check->fetchColumn() > 0) {
                    $error = 'Ten email jest już zajęty przez innego użytkownika.';
                }
            }
            
            if (!$error) {
                $perms = [];
                foreach ($allPermissions as $key => $info) {
                    $perms[$key] = isset($_POST['perm_' . $key]) ? true : false;
                }
                $permsJson = json_encode($perms, JSON_UNESCAPED_UNICODE);
                
                $setClauses = 'role=?, permissions=?, is_active=?, email=?';
                $params = [$role, $permsJson, $is_active, $email];
                
                if ($emailChanged) {
                    $setClauses .= ', email_verified=0, email_token=?';
                    $token = generateToken();
                    $params[] = $token;
                    
                    // Send verification
                    $link = "https://pasiekapodgruszka.pl/admin/verify_email.php?token=$token";
                    sendEmail($email, "Potwierdź zmianę adresu email", 
                        "<h1>Witaj $targetUser!</h1><p>Twój adres email w panelu administratora został zmieniony.</p><p>Kliknij aby potwierdzić:</p><p><a href='$link' class='btn'>Potwierdź email</a></p>");
                }
                
                if (strlen($newPassword) >= 6) {
                    $setClauses .= ', password_hash=?';
                    $params[] = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                }
                
                try {
                    $params[] = $id;
                    $pdo->prepare("UPDATE users SET $setClauses WHERE id=?")->execute($params);
                    logActivity('Edytowano konto', "Użytkownik: $targetUser, ID: $id");
                    $success = 'Zaktualizowano konto.' . ($emailChanged ? ' Wysłano prośbę o potwierdzenie emaila.' : '');
                } catch (PDOException $e) {
                    $error = 'Błąd: ' . $e->getMessage();
                }
            }
        }
    }
    
    // Usuń konto
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $target = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $target->execute([$id]);
        $targetUser = $target->fetchColumn();
        
        if ($targetUser === 'admin') {
            $error = 'Nie można usunąć konta właściciela!';
        } elseif ($id) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            logActivity('Usunięto konto', "Użytkownik: $targetUser, ID: $id");
            $success = 'Usunięto konto: ' . htmlspecialchars($targetUser);
        }
    }
    
    // Toggle aktywności
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $target = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $target->execute([$id]);
        $targetUser = $target->fetchColumn();
        
        if ($targetUser === 'admin') {
            $error = 'Nie można zablokować konta właściciela!';
        } elseif ($id) {
            $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
            $success = 'Status zmieniony.';
        }
    }
}

// Pobierz listę kont
$users = [];
if ($pdo) {
    try {
        $users = $pdo->query("SELECT * FROM users ORDER BY FIELD(role,'owner','admin','editor'), username")->fetchAll();
    } catch (Exception $e) {
        $users = $pdo->query("SELECT * FROM users ORDER BY id")->fetchAll();
    }
}

// Edycja
$editUser = null;
if (isset($_GET['edit']) && $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editUser = $stmt->fetch();
    // Nie pozwól edytować ownera
    if ($editUser && $editUser['username'] === 'admin') {
        $editUser = null;
    }
}

$editPerms = [];
if ($editUser && !empty($editUser['permissions'])) {
    $editPerms = json_decode($editUser['permissions'], true) ?: [];
}

function roleBadge($role) {
    switch ($role) {
        case 'owner': return '<span class="badge badge-owner"><i class="fa-solid fa-crown"></i> Właściciel</span>';
        case 'admin': return '<span class="badge badge-admin"><i class="fa-solid fa-user-shield"></i> Admin</span>';
        case 'editor': return '<span class="badge badge-editor"><i class="fa-solid fa-user-pen"></i> Edytor</span>';
        default: return '<span class="badge badge-info">' . htmlspecialchars($role) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Konta — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-users-gear" style="color: #9b59b6;"></i>
            <span class="nav-title">Menadżer Kont</span>
        </div>
        <div class="nav-right">
            <span class="nav-user"><i class="fa-solid fa-crown" style="color:#ffc107;"></i> <?php echo htmlspecialchars(currentUsername()); ?></span>
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?></div><?php endif; ?>

        <!-- FORMULARZ -->
        <div class="settings-card">
            <h2>
                <i class="fa-solid <?php echo $editUser ? 'fa-user-pen' : 'fa-user-plus'; ?>"></i>
                <?php echo $editUser ? 'Edytuj konto' : 'Utwórz nowe konto'; ?>
            </h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editUser ? 'edit' : 'add'; ?>">
                <?php if ($editUser): ?><input type="hidden" name="id" value="<?php echo $editUser['id']; ?>"><?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-user"></i> Login <?php echo $editUser ? '(nie można zmienić)' : '*'; ?></label>
                        <?php if ($editUser): ?>
                            <input type="text" value="<?php echo htmlspecialchars($editUser['username']); ?>" disabled style="opacity:0.5;">
                        <?php else: ?>
                            <input type="text" name="username" required minlength="3" placeholder="np. jan_kowalski">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-envelope"></i> Email * <small>(wymagany do resetu hasła)</small></label>
                        <input type="email" name="email" required placeholder="adres@email.com" value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-key"></i> Hasło <?php echo $editUser ? '(zostaw puste = bez zmiany)' : '*'; ?></label>
                        <input type="password" name="password" <?php echo $editUser ? '' : 'required'; ?> minlength="6" placeholder="<?php echo $editUser ? 'Nowe hasło...' : 'Min. 6 znaków'; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-shield-halved"></i> Rola</label>
                        <select name="role">
                            <option value="editor" <?php echo (($editUser['role'] ?? '') === 'editor') ? 'selected' : ''; ?>>Edytor — ograniczony dostęp</option>
                            <option value="admin" <?php echo (($editUser['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin — szeroki dostęp</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;padding-top:20px;">
                        <label class="toggle-label">
                            <input type="checkbox" name="is_active" value="1" <?php echo (!$editUser || ($editUser['is_active'] ?? 1)) ? 'checked' : ''; ?>>
                            <span class="toggle-switch"></span>
                            <span>Konto aktywne</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-top:15px;">
                    <label><i class="fa-solid fa-list-check"></i> Uprawnienia</label>
                    <div class="permissions-grid">
                        <?php foreach ($allPermissions as $key => $info): ?>
                            <label class="perm-checkbox">
                                <input type="checkbox" name="perm_<?php echo $key; ?>" value="1"
                                    <?php echo (!empty($editPerms[$key])) ? 'checked' : ''; ?>>
                                <span class="perm-box">
                                    <i class="fa-solid <?php echo $info['icon']; ?>"></i>
                                    <?php echo $info['label']; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:15px;">
                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> <?php echo $editUser ? 'Zapisz zmiany' : 'Utwórz konto'; ?>
                    </button>
                    <?php if ($editUser): ?>
                        <a href="accounts.php" class="btn-small btn-outline">Anuluj</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- LISTA KONT -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-users"></i> Konta (<?php echo count($users); ?>)</h2>
            <?php if (empty($users)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:30px;">Brak kont.</p>
            <?php else: ?>
                <div class="crud-table-wrap">
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>Login</th>
                                <th>Rola</th>
                                <th>Uprawnienia</th>
                                <th>Status</th>
                                <th>Ostatnie logowanie</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u): 
                            $isAdmin = ($u['username'] === 'admin');
                            $userPerms = json_decode($u['permissions'] ?? '{}', true) ?: [];
                        ?>
                            <tr class="<?php echo ($u['is_active'] ?? 1) ? '' : 'row-inactive'; ?>">
                                <td data-label="Login">
                                    <strong>
                                        <?php if ($isAdmin): ?><i class="fa-solid fa-crown" style="color:#ffc107;"></i> <?php endif; ?>
                                        <?php echo htmlspecialchars($u['username']); ?>
                                    </strong>
                                </td>
                                <td data-label="Rola"><?php echo roleBadge($u['role'] ?? 'editor'); ?></td>
                                <td data-label="Uprawnienia">
                                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                    <?php if ($isAdmin): ?>
                                        <span class="badge badge-success" style="font-size:0.7rem;">Wszystko</span>
                                    <?php else: ?>
                                        <?php foreach ($allPermissions as $key => $info): ?>
                                            <?php if (!empty($userPerms[$key])): ?>
                                                <span class="badge badge-info" style="font-size:0.65rem;" title="<?php echo $info['label']; ?>">
                                                    <i class="fa-solid <?php echo $info['icon']; ?>"></i>
                                                </span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if (empty(array_filter($userPerms))): ?>
                                            <span class="badge badge-inactive" style="font-size:0.7rem;">Brak</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <?php if ($isAdmin): ?>
                                        <span class="badge badge-active">✅ Aktywny</span>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="badge-btn <?php echo ($u['is_active'] ?? 1) ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo ($u['is_active'] ?? 1) ? '✅ Aktywny' : '❌ Zablokowany'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Logowanie">
                                    <?php 
                                    if (!empty($u['last_login'])) {
                                        echo date('d.m.Y H:i', strtotime($u['last_login']));
                                    } else {
                                        echo '<span style="color:var(--text-muted);">Nigdy</span>';
                                    }
                                    ?>
                                </td>
                                <td data-label="Akcje">
                                    <?php if (!$isAdmin): ?>
                                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                                            <a href="?edit=<?php echo $u['id']; ?>" class="btn-small btn-outline" title="Edytuj">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <form method="POST" onsubmit="return confirm('Na pewno usunąć konto <?php echo htmlspecialchars($u['username']); ?>?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn-small btn-danger" title="Usuń">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);font-size:0.8rem;">—</span>
                                    <?php endif; ?>
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
