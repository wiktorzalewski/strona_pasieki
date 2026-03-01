<?php
/**
 * export.php — Export danych do CSV
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('export');

$pdo = getDB();

// Obsługa pobierania CSV
if (isset($_GET['type']) && $pdo) {
    $type = $_GET['type'];
    $filename = '';
    $rows = [];
    $headers = [];

    try {
        switch ($type) {
            case 'products':
                requirePermission('products');
                $headers = ['ID', 'Nazwa', 'Slug', 'Opis', 'Cena', 'Stan', 'Kategoria', 'Dostępny', 'Data dodania'];
                $rows = $pdo->query("SELECT id, name, slug, description, price, stock, category, is_available, created_at FROM products ORDER BY id")->fetchAll(PDO::FETCH_NUM);
                $filename = 'produkty_' . date('Y-m-d') . '.csv';
                break;

            case 'kits':
                requirePermission('kits');
                $headers = ['ID', 'Nazwa', 'Slug', 'Opis', 'Cena', 'Stan', 'Dostępny', 'Data dodania'];
                $rows = $pdo->query("SELECT id, name, slug, description, price, stock, is_available, created_at FROM kits ORDER BY id")->fetchAll(PDO::FETCH_NUM);
                $filename = 'zestawy_' . date('Y-m-d') . '.csv';
                break;

            case 'recipes':
                requirePermission('recipes');
                $headers = ['ID', 'Tytuł', 'Slug', 'Opis', 'Czas przygotowania', 'Porcje', 'Data dodania'];
                $rows = $pdo->query("SELECT id, title, slug, description, prep_time, servings, created_at FROM recipes ORDER BY id")->fetchAll(PDO::FETCH_NUM);
                $filename = 'przepisy_' . date('Y-m-d') . '.csv';
                break;

            case 'newsletter':
                $headers = ['ID', 'Email', 'Aktywny', 'Data zapisu'];
                $rows = $pdo->query("SELECT id, email, is_active, created_at FROM newsletter_subscribers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_NUM);
                $filename = 'newsletter_' . date('Y-m-d') . '.csv';
                break;

            default:
                header('Location: export.php');
                exit();
        }

        // Wyślij CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        // BOM dla Excel (poprawne polskie znaki)
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);

        logActivity('Export CSV', "Typ: $type, Wierszy: " . count($rows));
        exit();

    } catch (Exception $e) {
        $exportError = 'Błąd eksportu: ' . $e->getMessage();
    }
}

// Policz rekordy do wyświetlenia
$counts = [];
if ($pdo) {
    foreach (['products', 'kits', 'recipes', 'newsletter_subscribers'] as $t) {
        try { $counts[$t] = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn(); }
        catch (Exception $e) { $counts[$t] = '?'; }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Export CSV — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-file-csv" style="color: #2ecc71;"></i>
            <span class="nav-title">Export do CSV</span>
        </div>
        <div class="nav-right">
            <span class="nav-user"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars(currentUsername()); ?></span>
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </nav>

    <main class="admin-container">
        <h2 style="margin-bottom:6px;"><i class="fa-solid fa-file-arrow-down"></i> Eksport danych</h2>
        <p style="color:var(--text-muted); margin-bottom:24px;">Pobierz dane jako plik CSV — otwieralny w Microsoft Excel i LibreOffice Calc.</p>

        <?php if (isset($exportError)): ?>
        <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($exportError); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">

            <?php if (hasPermission('products')): ?>
            <div class="settings-card" style="text-align:center; padding:28px;">
                <div style="font-size:2.5rem; color:#f39c12; margin-bottom:12px;">
                    <i class="fa-solid fa-honey-pot"></i>
                </div>
                <h3 style="margin:0 0 6px;">Produkty</h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:16px;">
                    <strong><?php echo $counts['products'] ?? '?'; ?></strong> rekordów
                </p>
                <a href="?type=products" class="btn-main" style="width:100%; box-sizing:border-box; display:block;">
                    <i class="fa-solid fa-download"></i> Pobierz CSV
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasPermission('kits')): ?>
            <div class="settings-card" style="text-align:center; padding:28px;">
                <div style="font-size:2.5rem; color:#e74c3c; margin-bottom:12px;">
                    <i class="fa-solid fa-gift"></i>
                </div>
                <h3 style="margin:0 0 6px;">Zestawy</h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:16px;">
                    <strong><?php echo $counts['kits'] ?? '?'; ?></strong> rekordów
                </p>
                <a href="?type=kits" class="btn-main" style="width:100%; box-sizing:border-box; display:block;">
                    <i class="fa-solid fa-download"></i> Pobierz CSV
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasPermission('recipes')): ?>
            <div class="settings-card" style="text-align:center; padding:28px;">
                <div style="font-size:2.5rem; color:#2ecc71; margin-bottom:12px;">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <h3 style="margin:0 0 6px;">Przepisy</h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:16px;">
                    <strong><?php echo $counts['recipes'] ?? '?'; ?></strong> rekordów
                </p>
                <a href="?type=recipes" class="btn-main" style="width:100%; box-sizing:border-box; display:block;">
                    <i class="fa-solid fa-download"></i> Pobierz CSV
                </a>
            </div>
            <?php endif; ?>

            <div class="settings-card" style="text-align:center; padding:28px;">
                <div style="font-size:2.5rem; color:#FF9800; margin-bottom:12px;">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <h3 style="margin:0 0 6px;">Subskrybenci</h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:16px;">
                    <strong><?php echo $counts['newsletter_subscribers'] ?? '?'; ?></strong> adresów email
                </p>
                <a href="?type=newsletter" class="btn-main" style="width:100%; box-sizing:border-box; display:block;">
                    <i class="fa-solid fa-download"></i> Pobierz CSV
                </a>
            </div>

        </div>

        <div class="settings-card" style="margin-top:20px; padding:16px 20px; background:rgba(52,152,219,0.07); border-color:rgba(52,152,219,0.2);">
            <p style="margin:0; font-size:0.9rem; color:var(--text-muted);">
                <i class="fa-solid fa-circle-info" style="color:#3498db;"></i>
                Pliki CSV używają separatora <strong>;</strong> (średnik) i kodowania <strong>UTF-8 z BOM</strong> — poprawnie wyświetlają polskie znaki w Microsoft Excel.
            </p>
        </div>
    </main>
</body>
</html>
