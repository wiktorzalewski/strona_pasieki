<?php
/**
 * products.php — Zarządzanie produktami
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('products');

$pdo = getDB();
$success = '';
$error = '';


// Funkcja makeSlug (zostaje tutaj bo specyficzna dla treści)

function makeSlug($text) {
    $pl = ['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ź'=>'z','ż'=>'z',
           'Ą'=>'a','Ć'=>'c','Ę'=>'e','Ł'=>'l','Ń'=>'n','Ó'=>'o','Ś'=>'s','Ź'=>'z','Ż'=>'z'];
    $text = strtr($text, $pl);
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// Sprawdź dodatkowe kolumny
$hasStock = false;
$hasCapacity = false;
if ($pdo) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll();
        foreach ($cols as $c) {
            if ($c['Field'] === 'stock') $hasStock = true;
            if ($c['Field'] === 'capacity') $hasCapacity = true;
        }
    } catch (Exception $e) {}
}

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = makeSlug($name);
        $price = floatval($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $taste = trim($_POST['taste'] ?? '');
        $usage = trim($_POST['usage_text'] ?? '');
        $capacity = trim($_POST['capacity'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $stock = intval($_POST['stock'] ?? -1);
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        $imagePath = 'assets/images/products/placeholder.jpg';
        $upload = handleImageUpload('image', 'assets/images/products');
        if ($upload && isset($upload['error'])) {
            $error = $upload['error'];
        } elseif ($upload && isset($upload['path'])) {
            $imagePath = $upload['path'];
        }
        
        if (!$error && $name) {
            try {
                $fields = 'slug, name, price, description, taste, usage_text, image_path, is_active, sort_order';
                $placeholders = '?,?,?,?,?,?,?,?,?';
                $vals = [$slug, $name, $price, $description, $taste, $usage, $imagePath, $is_active, $sort_order];
                
                if ($hasStock) { $fields .= ', stock'; $placeholders .= ',?'; $vals[] = $stock; }
                if ($hasCapacity) { $fields .= ', capacity'; $placeholders .= ',?'; $vals[] = $capacity; }
                
                $pdo->prepare("INSERT INTO products ($fields) VALUES ($placeholders)")->execute($vals);
                logActivity('Dodano produkt', "Nazwa: $name");
                $success = "Dodano produkt: $name";
            } catch (PDOException $e) {
                $error = 'Błąd bazy: ' . $e->getMessage();
            }
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = makeSlug($name);
        $price = floatval($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $taste = trim($_POST['taste'] ?? '');
        $usage = trim($_POST['usage_text'] ?? '');
        $capacity = trim($_POST['capacity'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $stock = intval($_POST['stock'] ?? -1);
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        $setClauses = 'slug=?, name=?, price=?, description=?, taste=?, usage_text=?, is_active=?, sort_order=?';
        $params = [$slug, $name, $price, $description, $taste, $usage, $is_active, $sort_order];
        
        if ($hasStock) { $setClauses .= ', stock=?'; $params[] = $stock; }
        if ($hasCapacity) { $setClauses .= ', capacity=?'; $params[] = $capacity; }
        
        // Zdjęcie — opcjonalnie
        $upload = handleImageUpload('image', 'assets/images/products');
        if ($upload && isset($upload['error'])) {
            $error = $upload['error'];
        } elseif ($upload && isset($upload['path'])) {
            $setClauses .= ', image_path=?';
            $params[] = $upload['path'];
        }
        
        if (!$error && $id) {
            try {
                // Pobierz stary stan
                $oldData = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                $oldData->execute([$id]);
                $oldStock = $oldData->fetchColumn();
                
                $params[] = $id;
                $pdo->prepare("UPDATE products SET $setClauses WHERE id=?")->execute($params);
                logActivity('Edytowano produkt', "Nazwa: $name, ID: $id");
                $success = "Zaktualizowano: $name";
                
                // Sprawdź powiadomienia
                checkAndNotify($id, $name, $oldStock, $stock);
            } catch (PDOException $e) {
                $error = 'Błąd bazy: ' . $e->getMessage();
            }
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            try {
                $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
                logActivity('Usunięto produkt', "ID: $id");
                $success = 'Produkt usunięty.';
            } catch (PDOException $e) { $error = 'Błąd usuwania.'; }
        }
    }
    
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
            $success = 'Status zmieniony.';
        }
    }

    if ($action === 'quick_stock') {
        $id = intval($_POST['id'] ?? 0);
        $newStock = intval($_POST['stock'] ?? 0);
        // -1 means unlimited, allow it
        if ($id) {
            // Pobierz stary stan
            $stmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $prod = $stmt->fetch();
            $oldStock = $prod['stock'] ?? 0;
            $prodName = $prod['name'] ?? 'Produkt';
            
            $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?")->execute([$newStock, $id]);
            logActivity('Szybka zmiana stanu', "Produkt: $prodName, Nowy stan: $newStock");
            $success = 'Stan magazynowy zaktualizowany.';
            
            checkAndNotify($id, $prodName, $oldStock, $newStock);
        }
    }
}

// Pobierz produkty
$products = [];
if ($pdo) {
    try { $products = $pdo->query("SELECT * FROM products ORDER BY sort_order, id")->fetchAll(); }
    catch (PDOException $e) { $error = 'Błąd: ' . $e->getMessage(); }
}

// Edycja
$editProduct = null;
if (isset($_GET['edit']) && $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editProduct = $stmt->fetch();
}

function stockLabel($stock) {
    if ($stock < 0) return '<span class="badge badge-info">∞ Nieograniczony</span>';
    if ($stock == 0) return '<span class="badge badge-danger">Niedostępny</span>';
    if ($stock <= 10) return '<span class="badge badge-warning">Ostatnie ' . $stock . ' szt.</span>';
    return '<span class="badge badge-success">' . $stock . ' szt.</span>';
}

function checkAndNotify($productId, $productName, $oldStock, $newStock) {
    global $pdo;
    // Warunek: stary stan = 0 ORAZ (nowy > 0 lub nowy = -1)
    if ($oldStock == 0 && ($newStock > 0 || $newStock == -1)) {
        $stmt = $pdo->prepare("SELECT id, email FROM availability_notifications WHERE product_id = ? AND sent_at IS NULL");
        $stmt->execute([$productId]);
        $requests = $stmt->fetchAll();
        
        if ($requests) {
            $count = 0;
            foreach ($requests as $req) {
                $subject = "Dostępność produktu: $productName";
                $message = "<h1>Dzień dobry!</h1>
                            <p>Produkt <strong>$productName</strong>, na który czekałeś, jest ponownie dostępny w naszej pasiece!</p>
                            <p><a href='https://pasiekapodgruszka.pl/products.php' class='btn'>Sprawdź ofertę</a></p>
                            <p>Pozdrawiamy,<br>Pasieka Pod Gruszką</p>";
                
                if (sendEmail($req['email'], $subject, $message)) {
                    $pdo->prepare("UPDATE availability_notifications SET sent_at = NOW() WHERE id = ?")->execute([$req['id']]);
                    $count++;
                }
            }
            // Opcjonalnie: można dodać info do $success, ale tutaj jest to trudne bo nadpisze.
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Produkty — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-honey-pot" style="color: #f39c12;"></i>
            <span class="nav-title">Produkty</span>
        </div>
        <div class="nav-right">
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- FORMULARZ -->
        <div class="settings-card">
            <h2>
                <i class="fa-solid <?php echo $editProduct ? 'fa-pen' : 'fa-plus'; ?>"></i>
                <?php echo $editProduct ? 'Edytuj produkt' : 'Dodaj nowy produkt'; ?>
            </h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editProduct ? 'edit' : 'add'; ?>">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-tag"></i> Nazwa produktu *</label>
                        <input type="text" name="name" required 
                               value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>"
                               placeholder="np. Miód Spadziowy">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-coins"></i> Cena (PLN) *</label>
                        <input type="number" name="price" step="0.01" min="0" required
                               value="<?php echo $editProduct['price'] ?? ''; ?>"
                               placeholder="45.00">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-bottle-droplet"></i> Pojemność słoika</label>
                        <input type="text" name="capacity"
                               value="<?php echo htmlspecialchars($editProduct['capacity'] ?? ''); ?>"
                               placeholder="np. 900ml, 400g, 500ml">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-lemon"></i> Smak</label>
                        <input type="text" name="taste" 
                               value="<?php echo htmlspecialchars($editProduct['taste'] ?? ''); ?>"
                               placeholder="np. Żywiczny, łagodny">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-align-left"></i> Opis</label>
                    <textarea name="description" rows="3" placeholder="Opis produktu..."><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-utensils"></i> Zastosowanie</label>
                    <input type="text" name="usage_text" 
                           value="<?php echo htmlspecialchars($editProduct['usage_text'] ?? ''); ?>"
                           placeholder="np. Do kawy, na chleb, prosto z łyżeczki">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-image"></i> Zdjęcie <?php echo $editProduct ? '(opcjonalne — zostawi obecne)' : ''; ?></label>
                        <input type="file" name="image" accept="image/*" class="file-input">
                        <?php if ($editProduct && !empty($editProduct['image_path'])): ?>
                            <small>Obecne: <?php echo htmlspecialchars(basename($editProduct['image_path'])); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-sort"></i> Kolejność</label>
                        <input type="number" name="sort_order" min="0" 
                               value="<?php echo $editProduct['sort_order'] ?? 0; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-boxes-stacked"></i> Stan magazynowy</label>
                        <input type="number" name="stock" min="-1"
                               value="<?php echo $editProduct['stock'] ?? -1; ?>">
                        <small>-1 = nieograniczony, 0 = niedostępny, &gt;0 = ilość</small>
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;padding-top:20px;">
                        <label class="toggle-label">
                            <input type="checkbox" name="is_active" value="1"
                                <?php echo (!$editProduct || ($editProduct['is_active'] ?? 1)) ? 'checked' : ''; ?>>
                            <span class="toggle-switch"></span>
                            <span>Aktywny (widoczny)</span>
                        </label>
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:10px;">
                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> <?php echo $editProduct ? 'Zapisz zmiany' : 'Dodaj produkt'; ?>
                    </button>
                    <?php if ($editProduct): ?>
                        <a href="products.php" class="btn-small btn-outline">Anuluj</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- LISTA -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-list"></i> Produkty (<?php echo count($products); ?>)</h2>
            <?php if (empty($products)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:30px;">Brak produktów.</p>
            <?php else: ?>
                <div class="crud-table-wrap">
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>Zdjęcie</th>
                                <th>Nazwa</th>
                                <th>Cena</th>
                                <?php if ($hasCapacity): ?><th>Pojemność</th><?php endif; ?>
                                <?php if ($hasStock): ?><th>Magazyn</th><?php endif; ?>
                                <th>Status</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr class="<?php echo ($p['is_active'] ?? 1) ? '' : 'row-inactive'; ?>">
                                <td data-label="Zdjęcie">
                                    <div class="thumb">
                                        <img src="/<?php echo htmlspecialchars($p['image_path']); ?>" alt=""
                                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23333%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2255%22 text-anchor=%22middle%22 fill=%22%23666%22 font-size=%2230%22>?</text></svg>'">
                                    </div>
                                </td>
                                <td data-label="Nazwa">
                                    <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                    <small style="display:block;color:var(--text-muted);"><?php echo htmlspecialchars($p['slug']); ?></small>
                                </td>
                                <td data-label="Cena"><strong><?php echo number_format($p['price'], 2); ?> zł</strong></td>
                                <?php if ($hasCapacity): ?>
                                    <td data-label="Pojemność"><?php echo htmlspecialchars($p['capacity'] ?? '—'); ?></td>
                                <?php endif; ?>
                                <?php if ($hasStock): ?>
                                    <td data-label="Magazyn">
                                        <form method="POST" class="stock-quick-form" style="display:flex;align-items:center;gap:5px;justify-content:flex-end;">
                                            <input type="hidden" name="action" value="quick_stock">
                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                            <button type="button" onclick="updateStockInp(this, -1)" style="width:24px;height:24px;border:1px solid var(--border);background:var(--bg-input);color:var(--text);border-radius:4px;cursor:pointer;">-</button>
                                            <input type="number" name="stock" value="<?php echo $p['stock']; ?>" style="width:60px;padding:2px 5px;text-align:center;border:1px solid var(--border);border-radius:4px;background:var(--bg-input);color:var(--text);">
                                            <button type="button" onclick="updateStockInp(this, 1)" style="width:24px;height:24px;border:1px solid var(--border);background:var(--bg-input);color:var(--text);border-radius:4px;cursor:pointer;">+</button>
                                            <button type="submit" title="Zapisz" style="width:24px;height:24px;border:none;background:var(--green);color:#fff;border-radius:4px;cursor:pointer;margin-left:5px;"><i class="fa-solid fa-check"></i></button>
                                        </form>
                                        <div style="margin-top:5px;font-size:0.75rem;">
                                            <?php echo stockLabel($p['stock'] ?? -1); ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                                <td data-label="Status">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="badge-btn <?php echo ($p['is_active'] ?? 1) ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo ($p['is_active'] ?? 1) ? '✅ Aktywny' : '❌ Ukryty'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td data-label="Akcje">
                                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                                        <a href="?edit=<?php echo $p['id']; ?>" class="btn-small btn-outline" title="Edytuj">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Usunąć produkt?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn-small btn-danger" title="Usuń">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
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
    <script>
        function updateStockInp(btn, delta) {
            const form = btn.closest('form');
            const input = form.querySelector('input[name="stock"]');
            let val = parseInt(input.value);
            if (isNaN(val)) val = 0;
            val += delta;
            // Allow -1 but not lower
            if (val < -1) val = -1;
            input.value = val;
        }
    </script>
</body>
</html>
