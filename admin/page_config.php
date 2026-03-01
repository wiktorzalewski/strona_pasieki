<?php
/**
 * page_config.php — Zarządzanie treścią strony
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('maintenance'); // Reuse maintenance perm or add new

$pdo = getDB();
$success = '';
$error = '';

$configKeys = [
    'General' => [
        'site_title' => ['label' => 'Tytuł strony (Browser Table)', 'type' => 'text'],
        'contact_email' => ['label' => 'Email kontaktowy', 'type' => 'email'],
        'contact_phone' => ['label' => 'Telefon kontaktowy', 'type' => 'text'],
        'contact_address' => ['label' => 'Adres fizyczny', 'type' => 'text'],
    ],
    'Strona Główna' => [
        'hero_title' => ['label' => 'Tytuł główny (Hero)', 'type' => 'text'],
        'hero_subtitle' => ['label' => 'Podtytuł (Hero)', 'type' => 'textarea'],
        'about_title' => ['label' => 'Tytuł sekcji "O nas"', 'type' => 'text'],
        'about_text' => ['label' => 'Treść sekcji "O nas"', 'type' => 'textarea'],
    ],
    'Stopka' => [
        'footer_text' => ['label' => 'Tekst w stopce', 'type' => 'text'],
        'footer_copyright' => ['label' => 'Copyright (np. © 2024 Pasieka)', 'type' => 'text'],
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    try {
        foreach ($_POST['config'] as $key => $value) {
            setSetting($key, $value);
        }
        logActivity('Zmiana konfiguracji treści', 'Zaktualizowano ustawienia strony');
        $success = 'Konfiguracja została zapisana.';
    } catch (Exception $e) {
        $error = 'Błąd: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfiguracja Strony — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-gears" style="color:#e67e22;"></i>
            <span class="nav-title">Konfiguracja Treści</span>
        </div>
        <div class="nav-right">
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <?php foreach ($configKeys as $category => $items): ?>
                <div class="settings-card">
                    <h2><i class="fa-solid fa-folder-open"></i> <?php echo $category; ?></h2>
                    <?php foreach ($items as $key => $info): ?>
                        <div class="form-group" style="margin-top:15px;">
                            <label><?php echo $info['label']; ?></label>
                            <?php if ($info['type'] === 'textarea'): ?>
                                <textarea name="config[<?php echo $key; ?>]" rows="4"><?php echo htmlspecialchars(getSetting($key)); ?></textarea>
                            <?php else: ?>
                                <input type="<?php echo $info['type']; ?>" name="config[<?php echo $key; ?>]" value="<?php echo htmlspecialchars(getSetting($key)); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div style="margin-top:20px; margin-bottom:50px;">
                <button type="submit" class="btn-save" style="width:100%; padding:15px; font-size:1.1rem;">
                    <i class="fa-solid fa-floppy-disk"></i> Zapisz całą konfigurację
                </button>
            </div>
        </form>
    </main>
</body>
</html>
