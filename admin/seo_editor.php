<?php
/**
 * seo_editor.php — Masowy edytor meta-tagów SEO
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('products'); // Or a specific SEO permission if added

$pdo = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (isset($_POST['seo_save'])) {
        try {
            $pdo->beginTransaction();
            
            // Produkty
            if (isset($_POST['seo_products'])) {
                foreach ($_POST['seo_products'] as $id => $data) {
                    $stmt = $pdo->prepare("UPDATE products SET meta_title = ?, meta_description = ? WHERE id = ?");
                    $stmt->execute([$data['title'], $data['desc'], $id]);
                }
            }
            
            // Zestawy
            if (isset($_POST['seo_kits'])) {
                foreach ($_POST['seo_kits'] as $id => $data) {
                    $stmt = $pdo->prepare("UPDATE kits SET meta_title = ?, meta_description = ? WHERE id = ?");
                    $stmt->execute([$data['title'], $data['desc'], $id]);
                }
            }
            
            // Przepisy
            if (isset($_POST['seo_recipes'])) {
                foreach ($_POST['seo_recipes'] as $id => $data) {
                    $stmt = $pdo->prepare("UPDATE recipes SET meta_title = ?, meta_description = ? WHERE id = ?");
                    $stmt->execute([$data['title'], $data['desc'], $id]);
                }
            }
            
            $pdo->commit();
            logActivity('Zaktualizowano dane SEO', 'Masowa edycja meta-tagów');
            $success = 'Zmiany SEO zostały zapisane pomyślnie.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Błąd podczas zapisywania: ' . $e->getMessage();
        }
    }
}

// Pobierz dane
$products = $pdo->query("SELECT id, name, meta_title, meta_description FROM products ORDER BY name ASC")->fetchAll();
$kits = $pdo->query("SELECT id, name, meta_title, meta_description FROM kits ORDER BY name ASC")->fetchAll();
$recipes = $pdo->query("SELECT id, title as name, meta_title, meta_description FROM recipes ORDER BY title ASC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edytor SEO — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .seo-row { margin-bottom: 20px; padding: 15px; background: var(--bg-input); border-radius: 8px; border-left: 4px solid var(--blue); }
        .seo-row h4 { margin-bottom: 10px; color: var(--gold); }
        .seo-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        .tab-btn { padding: 8px 15px; cursor: pointer; border-radius: 5px; background: var(--bg-card); border: 1px solid var(--border); color: var(--text-muted); }
        .tab-btn.active { background: var(--blue); color: white; border-color: var(--blue); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-magnifying-glass" style="color:var(--blue);"></i>
            <span class="nav-title">Edytor SEO</span>
        </div>
        <div class="nav-right">
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="settings-card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h2><i class="fa-solid fa-tags"></i> Meta-tagi i nagłówki</h2>
                    <button type="submit" name="seo_save" class="btn-main"><i class="fa-solid fa-floppy-disk"></i> Zapisz Wszystkie Zmiany</button>
                </div>
                
                <div class="tabs" style="margin-top:20px;">
                    <div class="tab-btn active" data-tab="products">Produkty</div>
                    <div class="tab-btn" data-tab="kits">Zestawy</div>
                    <div class="tab-btn" data-tab="recipes">Przepisy</div>
                </div>

                <div id="products" class="tab-content active">
                    <h3>Produkty (<?php echo count($products); ?>)</h3>
                    <?php foreach ($products as $p): ?>
                        <div class="seo-row">
                            <h4><?php echo htmlspecialchars($p['name']); ?></h4>
                            <div class="seo-inputs">
                                <div class="form-group">
                                    <label>Meta Title</label>
                                    <input type="text" name="seo_products[<?php echo $p['id']; ?>][title]" value="<?php echo htmlspecialchars($p['meta_title'] ?? ''); ?>" placeholder="Domyślna nazwa: <?php echo htmlspecialchars($p['name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Meta Description</label>
                                    <textarea name="seo_products[<?php echo $p['id']; ?>][desc]" rows="2"><?php echo htmlspecialchars($p['meta_description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="kits" class="tab-content">
                    <h3>Zestawy (<?php echo count($kits); ?>)</h3>
                    <?php foreach ($kits as $k): ?>
                        <div class="seo-row">
                            <h4><?php echo htmlspecialchars($k['name']); ?></h4>
                            <div class="seo-inputs">
                                <div class="form-group">
                                    <label>Meta Title</label>
                                    <input type="text" name="seo_kits[<?php echo $k['id']; ?>][title]" value="<?php echo htmlspecialchars($k['meta_title'] ?? ''); ?>" placeholder="Domyślna nazwa: <?php echo htmlspecialchars($k['name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Meta Description</label>
                                    <textarea name="seo_kits[<?php echo $k['id']; ?>][desc]" rows="2"><?php echo htmlspecialchars($k['meta_description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="recipes" class="tab-content">
                    <h3>Przepisy (<?php echo count($recipes); ?>)</h3>
                    <?php foreach ($recipes as $re): ?>
                        <div class="seo-row">
                            <h4><?php echo htmlspecialchars($re['name']); ?></h4>
                            <div class="seo-inputs">
                                <div class="form-group">
                                    <label>Meta Title</label>
                                    <input type="text" name="seo_recipes[<?php echo $re['id']; ?>][title]" value="<?php echo htmlspecialchars($re['meta_title'] ?? ''); ?>" placeholder="Domyślna nazwa: <?php echo htmlspecialchars($re['name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Meta Description</label>
                                    <textarea name="seo_recipes[<?php echo $re['id']; ?>][desc]" rows="2"><?php echo htmlspecialchars($re['meta_description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" name="seo_save" class="btn-main" style="width:100%; margin-top:20px;"><i class="fa-solid fa-floppy-disk"></i> Zapisz Wszystkie Zmiany</button>
            </div>
        </form>
    </main>

    <script>
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.onclick = () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
            };
        });
    </script>
</body>
</html>
