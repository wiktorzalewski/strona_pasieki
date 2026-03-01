<?php
require_once 'helper.php';
requireLogin();
requireOwner(); // Tylko główny admin widzi debug

$action = $_GET['action'] ?? '';
$result = '';

// Test SMTP
if ($action === 'test_smtp') {
    $to = 'wiktorzalewski50@gmail.com'; // Admin email
    $subject = 'Test SMTP z Panelu Admina';
    $message = '<p>Jeśli to czytasz, SMTP działa poprawnie!</p>';
    
    if (sendEmail($to, $subject, $message)) {
        $result = '<div class="alert alert-success">Wysłano testowy email na wiktorzalewski50@gmail.com. Sprawdź skrzynkę (i spam).</div>';
    } else {
        $result = '<div class="alert alert-error">Błąd wysyłania. Sprawdź logi lub credentials.</div>';
    }
}

// Stats
$stats = getServerStats();
$dbCounts = getDbCounts();

// DNS Check
$dnsRecords = [];
try {
    $dnsRecords = dns_get_record('pasiekapodgruszka.pl', DNS_TXT);
} catch (Exception $e) {
    $dnsRecords = [];
}


?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Debug Tool — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" style="color:#fff;text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Powrót</a>
        </div>
        <div class="nav-right">
             <span class="nav-user"><i class="fa-solid fa-bug"></i> Debug Mode</span>
        </div>
    </nav>

    <main class="admin-container">
        <h1>Centrum Diagnostyczne</h1>
        
        <?php echo $result; ?>

        <div class="dashboard-grid">
            <!-- PHP & Server -->
            <div class="dash-card">
                <h3><i class="fa-brands fa-php"></i> Serwer</h3>
                <ul style="list-style:none;padding:0;margin-top:10px;">
                    <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                    <li><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></li>
                    <li><strong>IP Serwera:</strong> <?php echo $_SERVER['SERVER_ADDR']; ?></li>
                    <li><strong>Twój IP:</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?></li>
                </ul>
            </div>

            <!-- Database -->
            <div class="dash-card">
                <h3><i class="fa-solid fa-database"></i> Baza Danych</h3>
                <?php if(getDB()): ?>
                    <div style="color:green;font-weight:bold;"><i class="fa-solid fa-check"></i> Połączono</div>
                <?php else: ?>
                    <div style="color:red;font-weight:bold;"><i class="fa-solid fa-times"></i> Błąd połączenia</div>
                <?php endif; ?>
                
                <hr>
                <small>Rekordy:</small>
                <ul style="list-style:none;padding:0;">
                    <?php foreach($dbCounts as $tbl => $cnt): ?>
                        <li><?php echo $tbl; ?>: <strong><?php echo $cnt; ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- SMTP Test -->
            <div class="dash-card">
                <h3><i class="fa-solid fa-paper-plane"></i> Test SMTP</h3>
                <p>Host: <strong>poczta2686594.home.pl</strong></p>
                <p>User: <strong>newsletter@pasiekapodgruszka.pl</strong></p>
                <a href="?action=test_smtp" class="btn-main" style="display:block;text-align:center;margin-top:15px;">
                    Wyślij Testowy Email
                </a>
            </div>
            
            <!-- Session -->
            <div class="dash-card">
                <h3><i class="fa-solid fa-user-clock"></i> Sesja</h3>
                <pre style="background:#f4f4f4;padding:10px;font-size:10px;overflow:auto;"><?php print_r($_SESSION); ?></pre>
            </div>

            <!-- DNS Info -->
            <div class="dash-card" style="grid-column: span 2;">
                <h3><i class="fa-solid fa-network-wired"></i> Rekordy DNS (TXT/SPF)</h3>
                <p><small>Tutaj sprawdzisz, czy Twój rekord SPF jest już widoczny dla świata.</small></p>
                <?php if (empty($dnsRecords)): ?>
                    <div class="alert alert-error">Nie znaleziono rekordów TXT lub błąd pobierania.</div>
                <?php else: ?>
                    <ul style="list-style:none;padding:0;font-family:monospace;font-size:12px;">
                    <?php foreach ($dnsRecords as $r): ?>
                        <li style="border-bottom:1px solid #eee;padding:5px;">
                            <?php echo htmlspecialchars($r['txt']); ?>
                            <?php if (strpos($r['txt'], 'v=spf1') !== false): ?>
                                <strong style="color:green;">(To jest Twój SPF!)</strong>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </main>
</body>
</html>
