<?php
/**
 * google_reviews.php — Zarządzanie Opiniami Google Places
 * Pobiera opinie przez Google Places API i cachuje w bazie danych.
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('google_reviews');
logAdminSession();

$pdo = getDB();
$success = '';
$error = '';

function fetchGoogleReviews($apiKey, $placeId, $pdo) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=" . urlencode($placeId)
         . "&fields=name,rating,user_ratings_total,reviews"
         . "&language=pl"
         . "&key=" . urlencode($apiKey);

    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) return ['error' => 'Nie można połączyć z Google API.'];

    $data = json_decode($response, true);
    if (!isset($data['result'])) {
        $msg = $data['error_message'] ?? 'Nieznany błąd API. Sprawdź klucz i Place ID.';
        return ['error' => $msg . ' Status: ' . ($data['status'] ?? '?')];
    }

    $result = $data['result'];
    $reviews = $result['reviews'] ?? [];

    // Wyczyść stare opinie
    $pdo->exec("DELETE FROM google_reviews");

    // Zapisz nowe
    $stmt = $pdo->prepare("INSERT INTO google_reviews (reviewer_name, reviewer_photo, rating, text, time_description, review_time) VALUES (?,?,?,?,?,?)");
    foreach ($reviews as $rev) {
        $stmt->execute([
            $rev['author_name'] ?? 'Anonim',
            $rev['profile_photo_url'] ?? '',
            $rev['rating'] ?? 5,
            $rev['text'] ?? '',
            $rev['relative_time_description'] ?? '',
            $rev['time'] ?? 0,
        ]);
    }

    // Zapisz ogólną ocenę i nazwę
    setSetting('google_overall_rating', number_format($result['rating'] ?? 5, 1));
    setSetting('google_total_ratings', $result['user_ratings_total'] ?? 0);
    setSetting('google_place_name', $result['name'] ?? 'Pasieka Pod Gruszką');
    setSetting('google_reviews_last_sync', date('Y-m-d H:i:s'));

    return ['count' => count($reviews), 'rating' => $result['rating'] ?? 5];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        setSetting('google_places_api_key', trim($_POST['api_key'] ?? ''));
        setSetting('google_place_id', trim($_POST['place_id'] ?? ''));
        setSetting('google_reviews_count', max(1, min(5, intval($_POST['count'] ?? 5))));
        setSetting('google_reviews_visible', !empty($_POST['visible']) ? '1' : '0');
        logActivity('Google Reviews', 'Zapisano ustawienia');
        $success = 'Ustawienia zapisane.';
    }

    if ($action === 'sync') {
        $apiKey  = getSetting('google_places_api_key');
        $placeId = getSetting('google_place_id');
        if (empty($apiKey) || empty($placeId)) {
            $error = 'Uzupełnij klucz API i Place ID przed synchronizacją.';
        } else {
            $result = fetchGoogleReviews($apiKey, $placeId, $pdo);
            if (isset($result['error'])) {
                $error = 'Błąd: ' . $result['error'];
            } else {
                logActivity('Google Reviews', "Zsynchronizowano {$result['count']} opinii");
                $success = "Zsynchronizowano {$result['count']} opinii (śr. ocena: {$result['rating']}/5).";
            }
        }
    }

    if ($action === 'toggle_review') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE google_reviews SET is_visible=1-is_visible WHERE id=?")->execute([$id]);
    }

    if ($action === 'delete_review') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM google_reviews WHERE id=?")->execute([$id]);
    }
}

// Wczytaj dane
$reviews = [];
$apiKey = getSetting('google_places_api_key');
$placeId = getSetting('google_place_id');
$reviewsCount = getSetting('google_reviews_count', '5');
$reviewsVisible = getSetting('google_reviews_visible', '1');
$overallRating = getSetting('google_overall_rating', '5.0');
$totalRatings = getSetting('google_total_ratings', '0');
$lastSync = getSetting('google_reviews_last_sync');

try {
    $reviews = $pdo ? $pdo->query("SELECT * FROM google_reviews ORDER BY review_time DESC")->fetchAll() : [];
} catch (Exception $e) { $error = 'Tabela nie istnieje. Uruchom migrację: apply_google_reviews_migration.php'; }

function stars($n) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) $out .= '<i class="fa-solid fa-star" style="color:' . ($i <= $n ? '#ffc107' : '#333') . ';"></i>';
    return $out;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Opinie Google — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="admin-nav">
    <div class="nav-left">
        <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
        <i class="fa-brands fa-google" style="color:#4285F4;"></i>
        <span class="nav-title">Opinie Google</span>
    </div>
    <div class="nav-right">
        <span class="nav-user"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars(currentUsername()); ?></span>
        <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</nav>

<main class="admin-container">
    <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Jak znaleźć Place ID -->
    <div class="alert alert-info" style="margin-bottom:20px;">
        <i class="fa-brands fa-google"></i>
        <strong>Jak znaleźć Place ID?</strong>
        Wejdź na:
        <a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder" target="_blank" style="color:#4285F4;">Place ID Finder</a>
        lub wyszukaj firmę na Google Maps i skopiuj ID z URL.
        &nbsp;|&nbsp;
        Klucz API: <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color:#4285F4;">Google Cloud Console</a>
        (wymagane: Places API)
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

        <!-- Ustawienia -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-gear"></i> Konfiguracja API</h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_settings">
                <div class="form-group">
                    <label><i class="fa-solid fa-key"></i> Klucz Google Places API</label>
                    <input type="text" name="api_key" value="<?php echo htmlspecialchars($apiKey); ?>" placeholder="AIzaSy...">
                </div>
                <div class="form-group">
                    <label><i class="fa-brands fa-google"></i> Place ID firmy</label>
                    <input type="text" name="place_id" value="<?php echo htmlspecialchars($placeId); ?>" placeholder="ChIJ...">
                </div>
                <div class="form-row" style="gap:12px;">
                    <div class="form-group" style="margin:0;flex:1;">
                        <label><i class="fa-solid fa-list-ol"></i> Ile opinii na stronie</label>
                        <select name="count">
                            <?php foreach ([3,4,5] as $n): ?>
                            <option value="<?php echo $n; ?>" <?php echo $reviewsCount == $n ? 'selected':'' ; ?>><?php echo $n; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;flex:1;">
                        <label><i class="fa-solid fa-eye"></i> Widoczność</label>
                        <label class="maintenance-toggle" style="margin-top:8px;">
                            <input type="checkbox" name="visible" <?php echo $reviewsVisible==='1'?'checked':''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Zapisz ustawienia</button>
            </form>
        </div>

        <!-- Statystyki i Sync -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-star"></i> Status</h2>
            <div style="text-align:center;padding:20px 0;">
                <div style="font-size:4rem;font-weight:900;color:var(--gold);line-height:1;"><?php echo $overallRating; ?></div>
                <div style="margin:10px 0;font-size:1.4rem;"><?php echo stars(round(floatval($overallRating))); ?></div>
                <div style="color:var(--text-muted);font-size:0.9rem;"><?php echo number_format(intval($totalRatings)); ?> opinii w Google</div>
                <div style="color:var(--text-muted);font-size:0.8rem;margin-top:6px;">
                    <?php echo $lastSync ? 'Ostatnia sync: ' . date('d.m.Y H:i', strtotime($lastSync)) : 'Brak synchronizacji'; ?>
                </div>
            </div>
            <hr style="border:0;border-top:1px solid var(--border);margin:15px 0;">
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <form method="POST" style="flex:1;">
                    <input type="hidden" name="action" value="sync">
                    <button class="btn-save" style="width:100%;" <?php echo (empty($apiKey)||empty($placeId))?'disabled title="Uzupełnij ustawienia API"':''; ?>>
                        <i class="fa-solid fa-rotate"></i> Synchronizuj z Google
                    </button>
                </form>
            </div>
            <?php if (empty($apiKey) || empty($placeId)): ?>
            <p style="color:#f39c12;font-size:0.82rem;margin-top:10px;"><i class="fa-solid fa-triangle-exclamation"></i> Uzupełnij klucz API i Place ID po lewej.</p>
            <?php endif; ?>
            <div style="margin-top:16px;padding:12px;background:rgba(255,255,255,0.03);border-radius:10px;font-size:0.82rem;color:var(--text-muted);line-height:1.6;">
                <i class="fa-solid fa-circle-info"></i>
                Google Places API zwraca <strong>do 5 opinii</strong> za darmo (najistotniejsze algorytmicznie).<br>
                Koszt: $17 za 1000 zapytań. Sync ręczny — nie automatyczny.
            </div>
        </div>
    </div>

    <!-- Lista opinii z cache -->
    <div class="settings-card" style="padding:0;overflow:hidden;">
        <div style="padding:20px 24px 12px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;">
            <h3 style="margin:0;"><i class="fa-solid fa-comments"></i> Opinie w bazie (<?php echo count($reviews); ?>)</h3>
            <span style="font-size:0.82rem;color:var(--text-muted);">Widoczne na stronie: <?php echo count(array_filter($reviews, fn($r)=>$r['is_visible'])); ?></span>
        </div>
        <?php if (empty($reviews)): ?>
        <p style="text-align:center;padding:40px;color:var(--text-muted);">Brak opinii. Kliknij "Synchronizuj z Google" aby pobrać.</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:0;">
        <?php foreach ($reviews as $r): ?>
        <div style="padding:16px 24px;border-bottom:1px solid rgba(255,255,255,0.04);display:flex;align-items:flex-start;gap:14px;<?php echo !$r['is_visible']?'opacity:0.4;':''; ?>">
            <img src="<?php echo htmlspecialchars($r['reviewer_photo'] ?: 'https://ui-avatars.com/api/?name='.urlencode($r['reviewer_name']).'&background=1a1a1a&color=ffc107&size=40'); ?>"
                 width="40" height="40" style="border-radius:50%;flex-shrink:0;" loading="lazy" onerror="this.src='https://ui-avatars.com/api/?name=?&background=1a1a1a&color=ffc107&size=40'">
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                    <strong><?php echo htmlspecialchars($r['reviewer_name']); ?></strong>
                    <span><?php echo stars($r['rating']); ?></span>
                    <span style="color:var(--text-muted);font-size:0.8rem;"><?php echo htmlspecialchars($r['time_description']); ?></span>
                </div>
                <p style="font-size:0.88rem;color:var(--text-muted);line-height:1.5;"><?php echo nl2br(htmlspecialchars(mb_substr($r['text'],0,200))); ?><?php echo mb_strlen($r['text'])>200?'…':''; ?></p>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;">
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="toggle_review">
                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                    <button class="btn-small <?php echo $r['is_visible']?'btn-outline':'btn-success'; ?>" type="submit" title="<?php echo $r['is_visible']?'Ukryj':'Pokaż'; ?>">
                        <i class="fa-solid <?php echo $r['is_visible']?'fa-eye-slash':'fa-eye'; ?>"></i>
                    </button>
                </form>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="delete_review">
                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                    <button class="btn-small btn-danger" type="submit" onclick="return confirm('Usunąć tę opinię z bazy?')"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
