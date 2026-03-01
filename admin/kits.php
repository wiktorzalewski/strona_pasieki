<?php
/**
 * kits.php — Zarządzanie zestawami
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('kits');

$pdo = getDB();
$success = '';
$error = '';


function makeSlug($text) {
    $pl = ['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ź'=>'z','ż'=>'z',
           'Ą'=>'a','Ć'=>'c','Ę'=>'e','Ł'=>'l','Ń'=>'n','Ó'=>'o','Ś'=>'s','Ź'=>'z','Ż'=>'z'];
    $text = strtr($text, $pl);
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($text))), '-');
}

// Sprawdź kolumny
$hasSortOrder = false;
if ($pdo) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM kits LIKE 'sort_order'")->fetchAll();
        $hasSortOrder = count($cols) > 0;
    } catch (Exception $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = makeSlug($name);
        $price_label = trim($_POST['price_label'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        $imagePath = 'assets/images/zestawy/placeholder.jpg';
        $upload = handleImageUpload('image', 'assets/images/zestawy');
        if ($upload && isset($upload['error'])) $error = $upload['error'];
        elseif ($upload && isset($upload['path'])) $imagePath = $upload['path'];
        
        if (!$error && $name) {
            try {
                $fields = 'slug, name, price_label, description, image_path, is_active';
                $ph = '?,?,?,?,?,?';
                $vals = [$slug, $name, $price_label, $description, $imagePath, $is_active];
                if ($hasSortOrder) { $fields .= ', sort_order'; $ph .= ',?'; $vals[] = $sort_order; }
                
                $pdo->prepare("INSERT INTO kits ($fields) VALUES ($ph)")->execute($vals);
                logActivity('Dodano zestaw', "Nazwa: $name");
                $success = "Dodano zestaw: $name";
            } catch (PDOException $e) { $error = 'Błąd: ' . $e->getMessage(); }
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = makeSlug($name);
        $price_label = trim($_POST['price_label'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        $set = 'slug=?, name=?, price_label=?, description=?, is_active=?';
        $params = [$slug, $name, $price_label, $description, $is_active];
        if ($hasSortOrder) { $set .= ', sort_order=?'; $params[] = $sort_order; }
        
        $upload = handleImageUpload('image', 'assets/images/zestawy');
        if ($upload && isset($upload['path'])) { $set .= ', image_path=?'; $params[] = $upload['path']; }
        
        if ($id) {
            try {
                $params[] = $id;
                $pdo->prepare("UPDATE kits SET $set WHERE id=?")->execute($params);
                logActivity('Edytowano zestaw', "Nazwa: $name, ID: $id");
                $success = "Zaktualizowano: $name";
            } catch (PDOException $e) { $error = 'Błąd: ' . $e->getMessage(); }
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) { 
            $pdo->prepare("DELETE FROM kits WHERE id=?")->execute([$id]); 
            logActivity('Usunięto zestaw', "ID: $id");
            $success = 'Zestaw usunięty.'; 
        }
    }
    
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) { $pdo->prepare("UPDATE kits SET is_active = NOT is_active WHERE id=?")->execute([$id]); $success = 'Status zmieniony.'; }
    }
}

$orderBy = $hasSortOrder ? 'sort_order, id' : 'id';
$kits = $pdo ? $pdo->query("SELECT * FROM kits ORDER BY $orderBy")->fetchAll() : [];
$editKit = null;
if (isset($_GET['edit']) && $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM kits WHERE id=?");
    $stmt->execute([intval($_GET['edit'])]);
    $editKit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Zestawy — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-gift" style="color: #e74c3c;"></i>
            <span class="nav-title">Zestawy</span>
        </div>
        <div class="nav-right">
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="settings-card">
            <h2><i class="fa-solid <?php echo $editKit ? 'fa-pen' : 'fa-plus'; ?>"></i> <?php echo $editKit ? 'Edytuj zestaw' : 'Dodaj zestaw'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editKit ? 'edit' : 'add'; ?>">
                <?php if ($editKit): ?><input type="hidden" name="id" value="<?php echo $editKit['id']; ?>"><?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-tag"></i> Nazwa zestawu *</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($editKit['name'] ?? ''); ?>" placeholder="np. Zestaw Trio">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-coins"></i> Cena (tekst)</label>
                        <input type="text" name="price_label" value="<?php echo htmlspecialchars($editKit['price_label'] ?? ''); ?>" placeholder="np. 95.00 PLN">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-align-left"></i> Opis</label>
                    <textarea name="description" rows="3"><?php echo htmlspecialchars($editKit['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-image"></i> Zdjęcie <?php echo $editKit ? '(opcjonalne)' : ''; ?></label>
                        <input type="file" name="image" accept="image/*" class="file-input">
                        <?php if ($editKit && !empty($editKit['image_path'])): ?>
                            <small>Obecne: <?php echo htmlspecialchars(basename($editKit['image_path'])); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if ($hasSortOrder): ?>
                    <div class="form-group">
                        <label><i class="fa-solid fa-sort"></i> Kolejność</label>
                        <input type="number" name="sort_order" min="0" value="<?php echo $editKit['sort_order'] ?? 0; ?>">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group" style="margin-top:10px;">
                    <label class="toggle-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo (!$editKit || ($editKit['is_active'] ?? 1)) ? 'checked' : ''; ?>>
                        <span class="toggle-switch"></span>
                        <span>Aktywny (widoczny na stronie)</span>
                    </label>
                </div>

                <div style="display:flex;gap:10px;margin-top:10px;">
                    <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> <?php echo $editKit ? 'Zapisz' : 'Dodaj'; ?></button>
                    <?php if ($editKit): ?><a href="kits.php" class="btn-small btn-outline">Anuluj</a><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="settings-card">
            <h2><i class="fa-solid fa-list"></i> Zestawy (<?php echo count($kits); ?>)</h2>
            <?php if (empty($kits)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:30px;">Brak zestawów.</p>
            <?php else: ?>
                <div class="crud-table-wrap">
                    <table class="crud-table">
                        <thead><tr><th>Zdjęcie</th><th>Nazwa</th><th>Cena</th><th>Status</th><th>Akcje</th></tr></thead>
                        <tbody>
                        <?php foreach ($kits as $k): ?>
                            <tr class="<?php echo ($k['is_active'] ?? 1) ? '' : 'row-inactive'; ?>">
                                <td data-label="Zdjęcie"><div class="thumb"><img src="/<?php echo htmlspecialchars($k['image_path']); ?>" alt="" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23333%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2255%22 text-anchor=%22middle%22 fill=%22%23666%22 font-size=%2230%22>?</text></svg>'"></div></td>
                                <td data-label="Nazwa">
                                    <strong><?php echo htmlspecialchars($k['name']); ?></strong>
                                    <small style="display:block;color:var(--text-muted);"><?php echo htmlspecialchars($k['slug']); ?></small>
                                </td>
                                <td data-label="Cena"><?php echo htmlspecialchars($k['price_label']); ?></td>
                                <td data-label="Status">
                                    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo $k['id']; ?>">
                                        <button type="submit" class="badge-btn <?php echo ($k['is_active'] ?? 1) ? 'badge-active' : 'badge-inactive'; ?>"><?php echo ($k['is_active'] ?? 1) ? '✅ Aktywny' : '❌ Ukryty'; ?></button>
                                    </form>
                                </td>
                                <td data-label="Akcje">
                                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                                        <a href="?edit=<?php echo $k['id']; ?>" class="btn-small btn-outline"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" onsubmit="return confirm('Usunąć zestaw?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $k['id']; ?>">
                                            <button type="submit" class="btn-small btn-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
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
