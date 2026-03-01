<?php
/**
 * hasla.php — Sejf Haseł (wersja standalone)
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('vault');

// Inicjalizuj sejf jeśli pusty
initializeVault();

$success = '';
$error = '';

// Obsługa zapisu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $vault = loadVault();
    
    if ($action === 'save_all') {
        foreach ($vault as $catIdx => &$category) {
            foreach ($category['entries'] as $entIdx => &$entry) {
                $prefix = "entry_{$catIdx}_{$entIdx}";
                $entry['name'] = $_POST[$prefix . '_name'] ?? $entry['name'];
                $entry['login'] = $_POST[$prefix . '_login'] ?? '';
                $entry['password'] = $_POST[$prefix . '_password'] ?? '';
                $entry['url'] = $_POST[$prefix . '_url'] ?? '';
                $entry['notes'] = $_POST[$prefix . '_notes'] ?? '';
            }
        }
        unset($category, $entry);
        if (saveVault($vault)) {
            $success = 'Wszystkie hasła zostały zapisane i zaszyfrowane.';
        } else {
            $error = 'Błąd zapisu! Sprawdź uprawnienia pliku hasla.env';
        }
    }
    
    if ($action === 'add_entry') {
        $catIdx = intval($_POST['category_index'] ?? 0);
        if (isset($vault[$catIdx])) {
            $vault[$catIdx]['entries'][] = [
                'name' => $_POST['new_name'] ?? 'Nowy wpis',
                'login' => '',
                'password' => '',
                'url' => '',
                'notes' => ''
            ];
            saveVault($vault);
            $success = 'Dodano nowy wpis.';
        }
    }
    
    if ($action === 'delete_entry') {
        $catIdx = intval($_POST['cat_idx'] ?? 0);
        $entIdx = intval($_POST['ent_idx'] ?? 0);
        if (isset($vault[$catIdx]['entries'][$entIdx])) {
            array_splice($vault[$catIdx]['entries'], $entIdx, 1);
            saveVault($vault);
            $success = 'Wpis usunięty.';
        }
    }
    
    if ($action === 'add_category') {
        $newCatName = trim($_POST['new_category_name'] ?? '');
        if ($newCatName) {
            $vault[] = [
                'category' => $newCatName,
                'icon' => 'fa-folder',
                'entries' => []
            ];
            saveVault($vault);
            $success = 'Dodano nową kategorię.';
        }
    }

    if ($action === 'delete_category') {
        $catIdx = intval($_POST['del_cat_idx'] ?? -1);
        if (isset($vault[$catIdx])) {
            array_splice($vault, $catIdx, 1);
            saveVault($vault);
            $success = 'Kategoria usunięta.';
        }
    }
}

$vault = loadVault();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Sejf Haseł — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-vault" style="color: #e74c3c;"></i>
            <span class="nav-title">Sejf Haseł</span>
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

        <div class="vault-header">
            <h1><i class="fa-solid fa-vault"></i> Sejf Haseł</h1>
            <p class="vault-subtitle"><i class="fa-solid fa-lock"></i> Dane zaszyfrowane AES-256-CBC</p>
        </div>

        <form method="POST" id="vault-form">
            <input type="hidden" name="action" value="save_all">

            <?php foreach ($vault as $catIdx => $category): ?>
                <div class="vault-category">
                    <div class="category-header">
                        <h2>
                            <i class="fa-solid <?php echo htmlspecialchars($category['icon']); ?>"></i>
                            <?php echo htmlspecialchars($category['category']); ?>
                        </h2>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <span class="entry-count"><?php echo count($category['entries']); ?> wpisów</span>
                            <button type="submit" name="action" value="delete_category" class="btn-delete-entry" title="Usuń kategorię"
                                    onclick="if(!confirm('Usunąć kategorię \'<?php echo htmlspecialchars($category['category']); ?>\' i wszystkie jej wpisy?')) return false; this.form.querySelector('[name=del_cat_idx]').value='<?php echo $catIdx; ?>';">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <?php if (empty($category['entries'])): ?>
                        <p class="empty-category">Brak wpisów w tej kategorii.</p>
                    <?php endif; ?>

                    <?php foreach ($category['entries'] as $entIdx => $entry): ?>
                        <?php $prefix = "entry_{$catIdx}_{$entIdx}"; ?>
                        <div class="vault-entry">
                            <div class="entry-header">
                                <input type="text" name="<?php echo $prefix; ?>_name" 
                                       value="<?php echo htmlspecialchars($entry['name']); ?>" 
                                       class="entry-name-input" placeholder="Nazwa">
                                <button type="submit" name="action" value="delete_entry" 
                                        class="btn-delete-entry"
                                        onclick="this.form.querySelector('[name=cat_idx]').value='<?php echo $catIdx; ?>'; this.form.querySelector('[name=ent_idx]').value='<?php echo $entIdx; ?>';"
                                        title="Usuń wpis">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <div class="entry-fields">
                                <div class="field-group">
                                    <label><i class="fa-solid fa-user"></i> Login</label>
                                    <div class="field-with-copy">
                                        <input type="text" name="<?php echo $prefix; ?>_login" 
                                               value="<?php echo htmlspecialchars($entry['login']); ?>" 
                                               placeholder="login / email" autocomplete="off">
                                        <button type="button" class="btn-copy" onclick="copyField(this)" title="Kopiuj">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="field-group">
                                    <label><i class="fa-solid fa-key"></i> Hasło</label>
                                    <div class="field-with-copy">
                                        <input type="password" name="<?php echo $prefix; ?>_password" 
                                               value="<?php echo htmlspecialchars($entry['password']); ?>" 
                                               placeholder="••••••••" autocomplete="new-password">
                                        <button type="button" class="btn-toggle-pass" onclick="togglePassword(this)" title="Pokaż/Ukryj">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn-copy" onclick="copyField(this)" title="Kopiuj">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="field-group">
                                    <label><i class="fa-solid fa-link"></i> URL</label>
                                    <div class="field-with-copy">
                                        <input type="url" name="<?php echo $prefix; ?>_url" 
                                               value="<?php echo htmlspecialchars($entry['url']); ?>" 
                                               placeholder="https://...">
                                        <?php if (!empty($entry['url'])): ?>
                                        <a href="<?php echo htmlspecialchars($entry['url']); ?>" target="_blank" class="btn-open-url" title="Otwórz">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="field-group full-width">
                                    <label><i class="fa-solid fa-note-sticky"></i> Notatki</label>
                                    <input type="text" name="<?php echo $prefix; ?>_notes" 
                                           value="<?php echo htmlspecialchars($entry['notes']); ?>" 
                                           placeholder="Dodatkowe informacje...">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="add-entry-row">
                        <input type="text" name="new_name" placeholder="Nazwa nowego wpisu..." class="add-entry-input">
                        <button type="submit" name="action" value="add_entry" class="btn-add-entry"
                                onclick="this.form.querySelector('[name=category_index]').value='<?php echo $catIdx; ?>';">
                            <i class="fa-solid fa-plus"></i> Dodaj
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>

            <input type="hidden" name="cat_idx" value="">
            <input type="hidden" name="ent_idx" value="">
            <input type="hidden" name="category_index" value="">
            <input type="hidden" name="del_cat_idx" value="">

            <div class="add-category-row">
                <input type="text" name="new_category_name" placeholder="Nazwa nowej kategorii..." class="add-category-input">
                <button type="submit" name="action" value="add_category" class="btn-add-category">
                    <i class="fa-solid fa-folder-plus"></i> Nowa Kategoria
                </button>
            </div>

            <div class="save-bar">
                <button type="submit" name="action" value="save_all" class="btn-save-all">
                    <i class="fa-solid fa-floppy-disk"></i> ZAPISZ WSZYSTKIE ZMIANY
                </button>
            </div>
        </form>
    </main>

    <script>
    function togglePassword(btn) {
        const input = btn.closest('.field-with-copy').querySelector('input');
        if (input.type === 'password') {
            input.type = 'text';
            btn.querySelector('i').className = 'fa-solid fa-eye-slash';
        } else {
            input.type = 'password';
            btn.querySelector('i').className = 'fa-solid fa-eye';
        }
    }

    function copyField(btn) {
        const input = btn.closest('.field-with-copy').querySelector('input');
        const originalType = input.type;
        input.type = 'text';
        navigator.clipboard.writeText(input.value).then(() => {
            const icon = btn.querySelector('i');
            icon.className = 'fa-solid fa-check';
            icon.style.color = '#2ecc71';
            setTimeout(() => {
                icon.className = 'fa-solid fa-copy';
                icon.style.color = '';
            }, 1500);
        });
        input.type = originalType;
    }
    </script>
</body>
</html>
