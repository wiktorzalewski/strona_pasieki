<?php
/**
 * recipes.php — Zarządzanie przepisami
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('recipes');

$pdo = getDB();
$success = '';
$error = '';


function makeSlug($text) {
    $pl = ['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ź'=>'z','ż'=>'z',
           'Ą'=>'a','Ć'=>'c','Ę'=>'e','Ł'=>'l','Ń'=>'n','Ó'=>'o','Ś'=>'s','Ź'=>'z','Ż'=>'z'];
    $text = strtr($text, $pl);
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($text))), '-');
}

// Konwertuj tekst linia-po-linii na JSON array
function linesToJson($text) {
    $lines = array_filter(array_map('trim', explode("\n", $text)));
    return json_encode(array_values($lines), JSON_UNESCAPED_UNICODE);
}

// Konwertuj JSON array na tekst linia-po-linii
function jsonToLines($json) {
    $arr = json_decode($json, true);
    if (!is_array($arr)) return $json;
    return implode("\n", $arr);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $slug = makeSlug($title);
        $short_desc = trim($_POST['short_desc'] ?? '');
        $prep_time = trim($_POST['prep_time'] ?? '30 min');
        $difficulty = trim($_POST['difficulty'] ?? 'Średni');
        $ingredients = linesToJson($_POST['ingredients'] ?? '');
        $steps = linesToJson($_POST['steps'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        $imagePath = '/assets/images/recipes/placeholder.jpg';
        $upload = handleImageUpload('image', 'assets/images/recipes');
        if ($upload && isset($upload['error'])) $error = $upload['error'];
        elseif ($upload && isset($upload['path'])) $imagePath = '/' . $upload['path'];
        
        if (!$error && $title) {
            try {
                $stmt = $pdo->prepare("INSERT INTO recipes (slug, title, short_desc, image_path, prep_time, difficulty, ingredients, steps, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$slug, $title, $short_desc, $imagePath, $prep_time, $difficulty, $ingredients, $steps, $is_active, $sort_order]);
                logActivity('Dodano przepis', "Tytuł: $title");
                $success = "Dodano przepis: $title";
            } catch (PDOException $e) { $error = 'Błąd: ' . $e->getMessage(); }
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = makeSlug($title);
        $short_desc = trim($_POST['short_desc'] ?? '');
        $prep_time = trim($_POST['prep_time'] ?? '30 min');
        $difficulty = trim($_POST['difficulty'] ?? 'Średni');
        $ingredients = linesToJson($_POST['ingredients'] ?? '');
        $steps = linesToJson($_POST['steps'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        $upload = handleImageUpload('image', 'assets/images/recipes');
        $imageSQL = ''; $params = [$slug, $title, $short_desc, $prep_time, $difficulty, $ingredients, $steps, $is_active, $sort_order];
        if ($upload && isset($upload['error'])) $error = $upload['error'];
        elseif ($upload && isset($upload['path'])) { $imageSQL = ', image_path=?'; $params[] = '/' . $upload['path']; }
        
        if (!$error && $id) {
            try {
                $params[] = $id;
                $pdo->prepare("UPDATE recipes SET slug=?, title=?, short_desc=?, prep_time=?, difficulty=?, ingredients=?, steps=?, is_active=?, sort_order=? $imageSQL WHERE id=?")->execute($params);
                logActivity('Edytowano przepis', "Tytuł: $title, ID: $id");
                $success = "Zaktualizowano: $title";
            } catch (PDOException $e) { $error = 'Błąd: ' . $e->getMessage(); }
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) { 
            $pdo->prepare("DELETE FROM recipes WHERE id=?")->execute([$id]); 
            logActivity('Usunięto przepis', "ID: $id");
            $success = 'Przepis usunięty.'; 
        }
    }
    
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) { $pdo->prepare("UPDATE recipes SET is_active = NOT is_active WHERE id=?")->execute([$id]); $success = 'Status zmieniony.'; }
    }
}

$recipes = $pdo ? $pdo->query("SELECT * FROM recipes ORDER BY sort_order, id")->fetchAll() : [];
$editRecipe = null;
if (isset($_GET['edit']) && $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id=?");
    $stmt->execute([intval($_GET['edit'])]);
    $editRecipe = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Przepisy — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-utensils" style="color: #2ecc71;"></i>
            <span class="nav-title">Przepisy</span>
        </div>
        <div class="nav-right">
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="settings-card">
            <h2><i class="fa-solid <?php echo $editRecipe ? 'fa-pen' : 'fa-plus'; ?>"></i> <?php echo $editRecipe ? 'Edytuj przepis' : 'Dodaj przepis'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editRecipe ? 'edit' : 'add'; ?>">
                <?php if ($editRecipe): ?><input type="hidden" name="id" value="<?php echo $editRecipe['id']; ?>"><?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-heading"></i> Tytuł *</label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($editRecipe['title'] ?? ''); ?>" placeholder="np. Tradycyjny Miodownik">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-align-left"></i> Krótki opis</label>
                    <textarea name="short_desc" rows="2" placeholder="Krótki opis przepisu..."><?php echo htmlspecialchars($editRecipe['short_desc'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-clock"></i> Czas przygotowania</label>
                        <input type="text" name="prep_time" value="<?php echo htmlspecialchars($editRecipe['prep_time'] ?? '30 min'); ?>" placeholder="np. 45 min">
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-gauge"></i> Trudność</label>
                        <select name="difficulty">
                            <?php foreach (['Łatwy', 'Średni', 'Trudny'] as $d): ?>
                                <option <?php echo (($editRecipe['difficulty'] ?? 'Średni') === $d) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-carrot"></i> Składniki (jeden na linię)</label>
                    <textarea name="ingredients" rows="6" placeholder="500g mąki&#10;200g masła&#10;3 łyżki miodu..."><?php echo htmlspecialchars(jsonToLines($editRecipe['ingredients'] ?? '[]')); ?></textarea>
                    <small>Wpisz każdy składnik w osobnej linii</small>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-list-ol"></i> Kroki (jeden na linię)</label>
                    <textarea name="steps" rows="6" placeholder="Zagnieć ciasto z mąki...&#10;Podziel ciasto na 3 części..."><?php echo htmlspecialchars(jsonToLines($editRecipe['steps'] ?? '[]')); ?></textarea>
                    <small>Wpisz każdy krok w osobnej linii — zostaną ponumerowane automatycznie</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fa-solid fa-image"></i> Zdjęcie</label>
                        <input type="file" name="image" accept="image/*" class="file-input">
                        <?php if ($editRecipe && !empty($editRecipe['image_path'])): ?>
                            <small>Obecne: <?php echo htmlspecialchars(basename($editRecipe['image_path'])); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-sort"></i> Kolejność</label>
                        <input type="number" name="sort_order" min="0" value="<?php echo $editRecipe['sort_order'] ?? 0; ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-top:10px;">
                    <label class="toggle-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo (!$editRecipe || ($editRecipe['is_active'] ?? 1)) ? 'checked' : ''; ?>>
                        <span class="toggle-switch"></span>
                        <span>Aktywny (widoczny na stronie)</span>
                    </label>
                </div>

                <div style="display:flex;gap:10px;margin-top:10px;">
                    <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> <?php echo $editRecipe ? 'Zapisz' : 'Dodaj'; ?></button>
                    <?php if ($editRecipe): ?><a href="recipes.php" class="btn-small btn-outline">Anuluj</a><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="settings-card">
            <h2><i class="fa-solid fa-list"></i> Przepisy (<?php echo count($recipes); ?>)</h2>
            <?php if (empty($recipes)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:30px;">Brak przepisów.</p>
            <?php else: ?>
                <div class="crud-table-wrap">
                    <table class="crud-table">
                        <thead><tr><th>Zdjęcie</th><th>Tytuł</th><th>Czas</th><th>Trudność</th><th>Status</th><th>Akcje</th></tr></thead>
                        <tbody>
                        <?php foreach ($recipes as $r): ?>
                            <tr class="<?php echo ($r['is_active'] ?? 1) ? '' : 'row-inactive'; ?>">
                                <td data-label="Zdjęcie"><div class="thumb"><img src="<?php echo htmlspecialchars($r['image_path']); ?>" alt="" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23333%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2255%22 text-anchor=%22middle%22 fill=%22%23666%22 font-size=%2230%22>?</text></svg>'"></div></td>
                                <td data-label="Tytuł">
                                    <strong><?php echo htmlspecialchars($r['title']); ?></strong>
                                    <small style="display:block;color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($r['short_desc']); ?></small>
                                </td>
                                <td data-label="Czas"><span class="badge badge-info"><?php echo htmlspecialchars($r['prep_time']); ?></span></td>
                                <td data-label="Trudność">
                                    <?php 
                                    $diffClass = ['Łatwy'=>'badge-success','Średni'=>'badge-warning','Trudny'=>'badge-danger'];
                                    $dc = $diffClass[$r['difficulty']] ?? 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $dc; ?>"><?php echo htmlspecialchars($r['difficulty']); ?></span>
                                </td>
                                <td data-label="Status">
                                    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                        <button type="submit" class="badge-btn <?php echo ($r['is_active'] ?? 1) ? 'badge-active' : 'badge-inactive'; ?>"><?php echo ($r['is_active'] ?? 1) ? '✅ Aktywny' : '❌ Ukryty'; ?></button>
                                    </form>
                                </td>
                                <td data-label="Akcje">
                                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                                        <a href="?edit=<?php echo $r['id']; ?>" class="btn-small btn-outline"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" onsubmit="return confirm('Usunąć przepis?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
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
