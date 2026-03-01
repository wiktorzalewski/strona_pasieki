<?php
/**
 * dashboard.php — Rozbudowany Dashboard z uprawnieniami
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/helper.php';
requireLogin();

$maintenanceOn = getSetting('maintenance_mode', '0') === '1';
$maintenanceTime = getSetting('maintenance_return_time', '~15 min');
$stats = getServerStats();
$dbCounts = getDbCounts();
$vaultEntries = 0;
$vault = loadVault();
foreach ($vault as $cat) {
    $vaultEntries += count($cat['entries']);
}

// Liczba kont (tylko dla ownera)
$userCount = 0;
if (isOwner()) {
    $pdo = getDB();
    if ($pdo) {
        try { $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); } catch (Exception $e) { $userCount = '?'; }
    }
}

// Bell: nieprzeczytane wiadomości
$unreadMessages = hasPermission('messages') ? getUnreadMessagesCount() : 0;

// Zaloguj/aktualizuj sesję admina
logAdminSession();

// Rola do wyświetlenia
$roleLabels = ['owner' => 'Właściciel', 'admin' => 'Admin', 'editor' => 'Edytor'];
$roleLabel = $roleLabels[$_SESSION['user_role'] ?? 'editor'] ?? 'Użytkownik';
$roleIcon = isOwner() ? 'fa-crown' : (($_SESSION['user_role'] ?? '') === 'admin' ? 'fa-user-shield' : 'fa-user-pen');
$roleColor = isOwner() ? '#ffc107' : (($_SESSION['user_role'] ?? '') === 'admin' ? '#3498db' : '#2ecc71');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <i class="fa-solid fa-shield-halved" style="color: #ffc107;"></i>
            <span class="nav-title">Panel Admina</span>
        </div>
        <div class="nav-right">
            <?php if ($unreadMessages > 0): ?>
            <a href="messages.php" style="position:relative;color:var(--text-muted);text-decoration:none;padding:6px;border-radius:8px;transition:color 0.3s;" title="<?php echo $unreadMessages; ?> nieprzeczytanych wiadomości">
                <i class="fa-solid fa-bell" style="font-size:1.1rem;"></i>
                <span style="position:absolute;top:-4px;right:-4px;background:#e74c3c;color:#fff;border-radius:50%;width:18px;height:18px;font-size:0.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;"><?php echo min($unreadMessages, 99); ?></span>
            </a>
            <?php endif; ?>
            <span class="nav-user"><i class="fa-solid <?php echo $roleIcon; ?>" style="color:<?php echo $roleColor; ?>;"></i> <?php echo htmlspecialchars(currentUsername()); ?></span>
            <a href="logout.php" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj</a>
        </div>
    </nav>

    <main class="admin-container">
        <div class="dashboard-header">
            <h1><i class="fa-solid fa-gauge-high"></i> Dashboard</h1>
            <p class="dash-subtitle">Witaj, <strong><?php echo htmlspecialchars(currentUsername()); ?></strong>! Rola: <span style="color:<?php echo $roleColor; ?>;font-weight:600;"><?php echo $roleLabel; ?></span></p>
        </div>

        <!-- STATUS MAINTENANCE -->
        <?php if (hasPermission('maintenance')): ?>
        <div class="maintenance-status <?php echo $maintenanceOn ? 'maintenance-on' : 'maintenance-off'; ?>">
            <div class="maintenance-status-left">
                <i class="fa-solid <?php echo $maintenanceOn ? 'fa-triangle-exclamation' : 'fa-circle-check'; ?>"></i>
                <div>
                    <strong><?php echo $maintenanceOn ? 'PRZERWA TECHNICZNA AKTYWNA' : 'Strona działa normalnie'; ?></strong>
                    <?php if ($maintenanceOn): ?>
                        <small>Powrót: <?php echo htmlspecialchars($maintenanceTime); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <a href="maintenance_settings.php" class="btn-small <?php echo $maintenanceOn ? 'btn-danger' : 'btn-success'; ?>">
                <i class="fa-solid fa-wrench"></i> Zarządzaj
            </a>
        </div>
        <?php endif; ?>

        <!-- ZARZĄDZANIE TREŚCIĄ -->
        <?php if (hasPermission('products') || hasPermission('kits') || hasPermission('recipes') || hasPermission('gallery')): ?>
        <h2 class="section-title"><i class="fa-solid fa-pen-ruler"></i> Zarządzanie treścią</h2>
        <div class="dashboard-grid">
            <?php if (hasPermission('products')): ?>
            <a href="products.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fa-solid fa-honey-pot"></i>
                </div>
                <h3>Produkty</h3>
                <p><?php echo $dbCounts['products'] ?? '?'; ?> produktów</p>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('kits')): ?>
            <a href="kits.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fa-solid fa-gift"></i>
                </div>
                <h3>Zestawy</h3>
                <p><?php echo $dbCounts['kits'] ?? '?'; ?> zestawów</p>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('recipes')): ?>
            <a href="recipes.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <h3>Przepisy</h3>
                <p><?php echo $dbCounts['recipes'] ?? '?'; ?> przepisów</p>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('gallery')): ?>
            <a href="gallery.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fa-solid fa-images"></i>
                </div>
                <h3>Galeria</h3>
                <p><?php echo $dbCounts['gallery_images'] ?? '?'; ?> zdjęć</p>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- NARZĘDZIA -->
        <h2 class="section-title"><i class="fa-solid fa-toolbox"></i> Narzędzia</h2>
        <div class="dashboard-grid">
            <?php if (hasPermission('vault')): ?>
            <a href="hasla.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #8e44ad, #9b59b6);">
                    <i class="fa-solid fa-vault"></i>
                </div>
                <h3>Sejf Haseł</h3>
                <p><?php echo $vaultEntries; ?> zapisanych wpisów</p>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('maintenance')): ?>
            <a href="maintenance_settings.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, <?php echo $maintenanceOn ? '#e74c3c, #c0392b' : '#2ecc71, #27ae60'; ?>);">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <h3>Przerwa Techniczna</h3>
                <p><?php echo $maintenanceOn ? 'AKTYWNA — kliknij aby wyłączyć' : 'Wyłączona'; ?></p>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('server_info')): ?>
            <a href="server_info.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fa-solid fa-server"></i>
                </div>
                <h3>Info o Serwerze</h3>
                <p>PHP <?php echo $stats['php_version'] ?? '?'; ?></p>
            </a>
            <a href="debug.php" class="dash-card">
                 <div class="dash-card-icon" style="background: linear-gradient(135deg, #34495e, #2c3e50);">
                    <i class="fa-solid fa-bug"></i>
                </div>
                <h3>Debug Tool</h3>
                <p>Test SMTP & DB</p>
            </a>
            <?php endif; ?>

            <a href="backups.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fa-solid fa-database"></i>
                </div>
                <h3>Kopie Zapasowe</h3>
                <p>Baza i pliki</p>
            </a>
            <a href="page_config.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #e67e22, #d35400);">
                    <i class="fa-solid fa-gears"></i>
                </div>
                <h3>Treść Strony</h3>
                <p>Ustawienia dynamiczne</p>
            </a>
            <a href="seo_editor.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h3>Edytor SEO</h3>
                <p>Meta tytuły i opisy</p>
            </a>
            <a href="activity_log.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #7f8c8d, #34495e);">
                    <i class="fa-solid fa-list-ol"></i>
                </div>
                <h3>Logi Aktywności</h3>
                <p>Historia zmian</p>
            </a>
            <?php if (hasPermission('analytics')): ?>
            <a href="analytics.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #3498db, #1abc9c);">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3>Analityka</h3>
                <p>Odwiedziny strony</p>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('export')): ?>
            <a href="export.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                    <i class="fa-solid fa-file-csv"></i>
                </div>
                <h3>Export CSV</h3>
                <p>Pobierz dane</p>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('messages')): ?>
            <a href="messages.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <h3>Wiadomości</h3>
                <p><?php echo $unreadMessages > 0 ? "<span style='color:#e74c3c;font-weight:700;'>$unreadMessages nowych</span>" : 'Brak nowych'; ?></p>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('discounts')): ?>
            <a href="discounts.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #e67e22, #d35400);">
                    <i class="fa-solid fa-tag"></i>
                </div>
                <h3>Kody Rabatowe</h3>
                <p>Generator kuponów</p>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('redirects')): ?>
            <a href="redirects.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <i class="fa-solid fa-route"></i>
                </div>
                <h3>Redirect Manager</h3>
                <p>Przekierowania 301/302</p>
            </a>
            <?php endif; ?>
            <?php if (isOwner()): ?>
            <a href="sessions.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fa-solid fa-desktop"></i>
                </div>
                <h3>Sesje Adminów</h3>
                <p>Aktywne sesje</p>
            </a>
            <?php endif; ?>
            <?php if (isOwner()): ?>
            <a href="accounts.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #e91e63, #c2185b);">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <h3>Menażer Kont</h3>
                <p><?php echo $userCount; ?> kont</p>
            </a>
            <?php endif; ?>

            <a href="newsletter.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #FF9800, #F57C00);">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <h3>Newsletter</h3>
                <p>Wyślij wiadomość</p>
            </a>
            <?php if (hasPermission('google_reviews')): ?>
            <a href="google_reviews.php" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #4285F4, #0F9D58);">
                    <i class="fa-brands fa-google"></i>
                </div>
                <h3>Opinie Google</h3>
                <p>Zarządzaj recenzjami</p>
            </a>
            <?php endif; ?>

        </div>

        <!-- PRZYDATNE LINKI -->
        <h2 class="section-title"><i class="fa-solid fa-link"></i> Przydatne Łącza</h2>
        <div class="dashboard-grid">
            <a href="https://pasiekapodgruszka.pl" target="_blank" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fa-solid fa-globe"></i>
                </div>
                <h3>Strona Główna</h3>
                <p>Otwórz witrynę</p>
            </a>

            <?php if (isOwner()): ?>
            <a href="https://search.google.com/search-console" target="_blank" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #4285F4, #3367D6);">
                    <i class="fa-brands fa-google"></i>
                </div>
                <h3>Google Search Console</h3>
                <p>Statystyki SEO</p>
            </a>

            <a href="https://www.google.com/maps/search/?api=1&query=Pasieka+Pod+Gruszką+Rybie+Mała+10" target="_blank" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <h3>Mapy Google</h3>
                <p>Wizytówka Pasieki</p>
            </a>

            <a href="https://dash.cloudflare.com/" target="_blank" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #F38020, #FAAE40);">
                    <i class="fa-solid fa-cloud"></i>
                </div>
                <h3>Cloudflare</h3>
                <p>DNS i Ochrona</p>
            </a>

            <a href="https://pasiekapodgruszka.pl/phpmyadmin/" target="_blank" class="dash-card">
                <div class="dash-card-icon" style="background: linear-gradient(135deg, #6c5ce7, #a29bfe);">
                    <i class="fa-solid fa-database"></i>
                </div>
                <h3>phpMyAdmin</h3>
                <p>Zarządzanie Bazą</p>
            </a>
            <?php endif; ?>
        </div>

        <!-- STATYSTYKI SZYBKIE -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-honey-pot" style="color: #f39c12;"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $dbCounts['products'] ?? '?'; ?></span>
                    <span class="stat-label">Produkty</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-gift" style="color: #e74c3c;"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $dbCounts['kits'] ?? '?'; ?></span>
                    <span class="stat-label">Zestawy</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-utensils" style="color: #2ecc71;"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $dbCounts['recipes'] ?? '?'; ?></span>
                    <span class="stat-label">Przepisy</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-image" style="color: #3498db;"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $dbCounts['gallery_images'] ?? '?'; ?></span>
                    <span class="stat-label">Zdjęcia</span>
                </div>
            </div>
        </div>

        <!-- DYSK / SERWER -->
        <?php if (isset($stats['disk_percent'])): ?>
        <div class="server-bar">
            <div class="server-bar-item">
                <span><i class="fa-solid fa-hard-drive"></i> Dysk: <?php echo $stats['disk_used']; ?> / <?php echo $stats['disk_total']; ?></span>
                <div class="mini-progress">
                    <div class="mini-progress-fill <?php echo $stats['disk_percent'] > 80 ? 'danger' : ''; ?>" style="width: <?php echo $stats['disk_percent']; ?>%"></div>
                </div>
                <span class="stat-percent"><?php echo $stats['disk_percent']; ?>%</span>
            </div>
            <?php if (isset($stats['uptime'])): ?>
            <div class="server-bar-item">
                <span><i class="fa-solid fa-clock"></i> Uptime: <?php echo $stats['uptime']; ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($stats['load'])): ?>
            <div class="server-bar-item">
                <span><i class="fa-solid fa-microchip"></i> Load: <?php echo $stats['load']; ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="dash-info-bar">
            <div class="info-item">
                <i class="fa-solid fa-clock"></i>
                <span>Zalogowano: <?php echo date('H:i, d.m.Y', $_SESSION['login_time'] ?? time()); ?></span>
            </div>
            <div class="info-item">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Sesja wygaśnie po 30 min nieaktywności</span>
            </div>
            </div>
        </div>
    </main>

    <!-- SZYBKIE NOTATKI -->
    <div id="quick-notes-widget" style="position:fixed;bottom:20px;right:20px;z-index:999;width:320px;">
        <div id="notes-header" style="background:linear-gradient(135deg,#ffc107,#e6ac00);color:#000;padding:10px 16px;border-radius:12px 12px 0 0;font-weight:700;font-size:0.9rem;cursor:pointer;display:flex;align-items:center;justify-content:space-between;user-select:none;" onclick="toggleNotes()">
            <span><i class="fa-solid fa-note-sticky"></i> Szybkie Notatki</span>
            <div style="display:flex;gap:8px;align-items:center;">
                <span id="notes-saved-at" style="font-size:0.72rem;font-weight:400;opacity:0.7;"></span>
                <i class="fa-solid fa-chevron-up" id="notes-chevron"></i>
            </div>
        </div>
        <div id="notes-body" style="background:var(--bg-card);border:1px solid var(--border);border-top:none;border-radius:0 0 12px 12px;display:none;">
            <textarea id="notes-textarea"
                placeholder="Twoje prywatne notatki — zapisywane automatycznie..."
                style="width:100%;height:200px;padding:14px;background:#1e1e1e;border:none;color:var(--text);font-family:'Inter',sans-serif;font-size:0.88rem;resize:vertical;border-radius:0 0 12px 12px;outline:none;line-height:1.6;"></textarea>
        </div>
    </div>

</body>

<script>
// === Quick Notes ===
let notesSaveTimer = null;
const notesWidget = document.getElementById('quick-notes-widget');
const notesBody = document.getElementById('notes-body');
const notesTA = document.getElementById('notes-textarea');
const notesChevron = document.getElementById('notes-chevron');
const notesSavedAt = document.getElementById('notes-saved-at');

// Wczytaj notatki
fetch('notes_api.php').then(r=>r.json()).then(data => {
    if (data.ok && data.content) {
        notesTA.value = data.content;
    }
});

function toggleNotes() {
    const isHidden = notesBody.style.display === 'none';
    notesBody.style.display = isHidden ? 'block' : 'none';
    notesChevron.className = isHidden ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-up';
    if (isHidden) notesTA.focus();
}

// Autosave z debounce 1.5s
notesTA.addEventListener('input', function() {
    clearTimeout(notesSaveTimer);
    notesSavedAt.textContent = 'Zapisywanie...';
    notesSaveTimer = setTimeout(() => saveNotes(), 1500);
});

function saveNotes() {
    fetch('notes_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ content: notesTA.value })
    }).then(r=>r.json()).then(data => {
        notesSavedAt.textContent = data.ok ? 'Zapisano ' + data.saved_at : 'Błąd!';
        if (data.ok) setTimeout(() => notesSavedAt.textContent = '', 3000);
    });
}

// === Bell animation ===
document.querySelectorAll('.fa-bell').forEach(bell => {
    bell.style.animation = 'bell-ring 1s ease 2s 2';
});

// === Session Countdown Timer (30 min) ===
(function() {
    const SESSION_TIMEOUT_MS = 30 * 60 * 1000; // 30 minut
    const WARN_BEFORE_MS    = 2 * 60 * 1000;   // ostrzeżenie 2 min przed
    let warnTimeout, logoutTimeout;

    // Toast HTML
    const toast = document.createElement('div');
    toast.id = 'session-toast';
    toast.innerHTML = `
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <i class="fa-solid fa-clock" style="font-size:1.4rem;color:#ffc107;flex-shrink:0;margin-top:2px;"></i>
            <div style="flex:1;">
                <div style="font-weight:700;font-size:0.95rem;margin-bottom:4px;">Sesja wygaśnie za <span id="sess-countdown">2:00</span></div>
                <div style="font-size:0.82rem;color:#aaa;margin-bottom:12px;">Po 30 minutach nieaktywności zostaniesz wylogowany.</div>
                <div style="display:flex;gap:8px;">
                    <button id="sess-extend" style="padding:7px 16px;background:linear-gradient(135deg,#ffc107,#e6ac00);border:none;border-radius:8px;color:#000;font-weight:700;font-size:0.85rem;cursor:pointer;">Przedłuż sesję</button>
                    <button onclick="window.location='logout.php'" style="padding:7px 14px;background:rgba(231,76,60,0.15);border:1px solid rgba(231,76,60,0.4);border-radius:8px;color:#e74c3c;font-size:0.85rem;cursor:pointer;font-weight:600;">Wyloguj teraz</button>
                </div>
            </div>
        </div>`;
    toast.style.cssText = `
        position:fixed;bottom:90px;right:20px;z-index:9999;
        background:var(--bg-card);border:1px solid rgba(255,193,7,0.4);
        border-left:3px solid #ffc107;border-radius:14px;
        padding:18px;width:310px;box-shadow:0 8px 30px rgba(0,0,0,0.5);
        display:none;animation:slide-in-right 0.4s ease;`;
    document.body.appendChild(toast);

    // Odliczanie w toaście
    let countdownInterval;
    function startCountdown(ms) {
        let remaining = Math.floor(ms / 1000);
        const el = document.getElementById('sess-countdown');
        countdownInterval = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                window.location = 'logout.php?expired=1';
                return;
            }
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            if (el) el.textContent = m + ':' + String(s).padStart(2,'0');
        }, 1000);
    }

    function showWarning() {
        toast.style.display = 'block';
        startCountdown(WARN_BEFORE_MS);
    }

    function resetTimer() {
        clearTimeout(warnTimeout);
        clearTimeout(logoutTimeout);
        clearInterval(countdownInterval);
        toast.style.display = 'none';
        warnTimeout  = setTimeout(showWarning, SESSION_TIMEOUT_MS - WARN_BEFORE_MS);
        logoutTimeout = setTimeout(() => { window.location = 'logout.php?expired=1'; }, SESSION_TIMEOUT_MS);
    }

    // Przedłużenie sesji — ping przez AJAX
    document.getElementById('sess-extend')?.addEventListener('click', function() {
        fetch('dashboard.php', { method: 'HEAD' }).catch(()=>{});
        resetTimer();
    });

    // Reset timera przy aktywności użytkownika
    ['click','keydown','mousemove','touchstart','scroll'].forEach(evt => {
        document.addEventListener(evt, resetTimer, { passive: true });
    });

    resetTimer(); // Start
})();
</script>

<style>
@keyframes bell-ring {
    0%,100% { transform: rotate(0); }
    20% { transform: rotate(-20deg); }
    40% { transform: rotate(20deg); }
    60% { transform: rotate(-15deg); }
    80% { transform: rotate(15deg); }
}
</style>
</html>
