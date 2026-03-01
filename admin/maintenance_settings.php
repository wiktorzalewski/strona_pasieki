<?php
/**
 * maintenance_settings.php — Zarządzanie przerwą techniczną
 */
require_once __DIR__ . '/helper.php';
requireLogin();
requirePermission('maintenance');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle') {
        $current = getSetting('maintenance_mode', '0');
        $new = ($current === '1') ? '0' : '1';
        if (setSetting('maintenance_mode', $new)) {
            logActivity('Przerwa techniczna', $new === '1' ? 'WŁĄCZONO' : 'WYŁĄCZONO');
            $success = $new === '1' ? 'Przerwa techniczna WŁĄCZONA.' : 'Przerwa techniczna WYŁĄCZONA. Strona działa normalnie.';
        } else {
            $error = 'Nie udało się zmienić ustawienia.';
        }
    }
    
    if ($action === 'save_settings') {
        $returnTime = trim($_POST['return_time'] ?? '~15 min');
        $customMsg = trim($_POST['custom_message'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        
        setSetting('maintenance_return_time', $returnTime);
        setSetting('maintenance_message', $customMsg);
        // Update the setting name to match the form field and the default value
        setSetting('maintenance_contact_email', $contactEmail);
        logActivity('Zmiana ustawień przerwy', "Czas: $returnTime");
        $success = 'Ustawienia przerwy technicznej zapisane.';
    }
    if ($action === 'save_schedule') {
        $scheduledAt = trim($_POST['scheduled_at'] ?? '');
        if ($scheduledAt) {
            // Upewnij się że data jest w przyszłości
            if (strtotime($scheduledAt) > time()) {
                setSetting('maintenance_scheduled_at', $scheduledAt);
                logActivity('Zaplanowano przerwę', "Data: $scheduledAt");
                $success = "Przerwa zaplanowana na: " . date('d.m.Y H:i', strtotime($scheduledAt));
            } else {
                $error = 'Data zaplanowanej przerwy musi być w przyszłości.';
            }
        } else {
            setSetting('maintenance_scheduled_at', '');
            logActivity('Anulowano zaplanowaną przerwę', '');
            $success = 'Zaplanowana przerwa została anulowana.';
        }
    }
}

$maintenanceOn = getSetting('maintenance_mode', '0') === '1';
$returnTime = getSetting('maintenance_return_time', '~15 min');
$customMessage = getSetting('maintenance_message', '');
$contactEmail = getSetting('maintenance_contact_email', 'kontakt@pasiekapodgruszka.pl');
$scheduledAt = getSetting('maintenance_scheduled_at', '');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Przerwa Techniczna — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
            <i class="fa-solid fa-screwdriver-wrench" style="color: #f39c12;"></i>
            <span class="nav-title">Przerwa Techniczna</span>
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

        <!-- WIELKI PRZYCISK TOGGLE -->
        <div class="maintenance-toggle-card <?php echo $maintenanceOn ? 'active' : ''; ?>">
            <div class="toggle-status">
                <div class="toggle-icon">
                    <i class="fa-solid <?php echo $maintenanceOn ? 'fa-triangle-exclamation' : 'fa-circle-check'; ?>"></i>
                </div>
                <h1><?php echo $maintenanceOn ? 'PRZERWA AKTYWNA' : 'STRONA DZIAŁA'; ?></h1>
                <p><?php echo $maintenanceOn 
                    ? 'Odwiedzający widzą stronę przerwy technicznej' 
                    : 'Strona jest dostępna dla wszystkich odwiedzających'; ?></p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="toggle">
                <button type="submit" class="btn-toggle <?php echo $maintenanceOn ? 'btn-toggle-off' : 'btn-toggle-on'; ?>">
                    <i class="fa-solid <?php echo $maintenanceOn ? 'fa-play' : 'fa-pause'; ?>"></i>
                    <?php echo $maintenanceOn ? 'WYŁĄCZ PRZERWĘ — Uruchom stronę' : 'WŁĄCZ PRZERWĘ TECHNICZNĄ'; ?>
                </button>
            </form>
        </div>

        <!-- USTAWIENIA PRZERWY -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-gear"></i> Ustawienia przerwy</h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_settings">
                
                <div class="form-group">
                    <label><i class="fa-solid fa-clock"></i> Szacowany czas powrotu</label>
                    <input type="text" name="return_time" value="<?php echo htmlspecialchars($returnTime); ?>" 
                           placeholder="np. ~15 min, 2 godziny, jutro rano">
                    <small>Ten tekst pojawi się na stronie przerwy technicznej</small>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-message"></i> Własna wiadomość (opcjonalnie)</label>
                    <textarea name="custom_message" rows="3" placeholder="np. Aktualizujemy system zamówień..."><?php echo htmlspecialchars($customMessage); ?></textarea>
                    <small>Dodatkowy tekst wyświetlany pod główną wiadomością</small>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-envelope"></i> Email kontaktowy</label>
                    <input type="email" name="maintenance_contact_email" id="maintenance_contact_email" class="form-input" 
                       value="<?php echo htmlspecialchars(getSetting('maintenance_contact_email', 'kontakt@pasiekapodgruszka.pl')); ?>">
                </div>

                <button type="submit" class="btn-save">
                    <i class="fa-solid fa-floppy-disk"></i> Zapisz ustawienia
                </button>
            </form>
        </div>

        <!-- ZAPLANOWANA PRZERWA -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-calendar-clock"></i> Zaplanowana Przerwa</h2>
            <p style="color:var(--text-muted); margin-bottom:14px;">
                Ustaw datę i godzinę — przerwa włączy się <strong>automatycznie</strong> bez Twojej ingerencji.
            </p>

            <?php if ($scheduledAt): ?>
            <div style="background:rgba(255,193,7,0.12); border:1px solid rgba(255,193,7,0.3); border-radius:8px; padding:12px 16px; margin-bottom:16px;">
                <i class="fa-solid fa-clock" style="color:#ffc107;"></i>
                <strong>Zaplanowana na:</strong> <?php echo date('d.m.Y H:i', strtotime($scheduledAt)); ?>
            </div>
            <?php endif; ?>

            <form method="POST" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                <input type="hidden" name="action" value="save_schedule">
                <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                    <label><i class="fa-solid fa-calendar"></i> Data i godzina przerwy</label>
                    <input type="datetime-local" name="scheduled_at"
                           value="<?php echo htmlspecialchars($scheduledAt); ?>"
                           min="<?php echo date('Y-m-d\TH:i'); ?>">
                    <small>Zostaw puste aby anulować zaplanowanie</small>
                </div>
                <button type="submit" class="btn-save" style="margin-bottom:22px;">
                    <i class="fa-solid fa-calendar-check"></i> Zaplanuj
                </button>
            </form>
        </div>

        <!-- PODGLĄD -->
        <div class="settings-card">
            <h2><i class="fa-solid fa-eye"></i> Podgląd strony przerwy</h2>
            <p style="color: var(--text-muted); margin-bottom: 15px;">Tak wygląda strona przerwy technicznej dla odwiedzających:</p>
            <a href="/maintenance.php" target="_blank" class="btn-small btn-outline">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Otwórz podgląd
            </a>
        </div>

    </main>
</body>
</html>
