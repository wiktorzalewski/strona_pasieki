<?php
require_once 'helper.php';
requireLogin();

// Obsługa akcji (Wysyłka / Usuwanie)
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDB();

    // Usuwanie subskrybenta
    if (isset($_POST['delete_email'])) {
        $id = intval($_POST['delete_id']);
        $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Usunięto subskrybenta.";
        $msgType = 'success';
    }

    // Wysyłka masowa
    if (isset($_POST['send_newsletter'])) {
        $subject = trim($_POST['subject']);
        $body = trim($_POST['body']); // HTML body

        if (empty($subject) || empty($body)) {
            $msg = "Wpisz temat i treść wiadomości.";
            $msgType = 'error';
        } else {
            // Pobierz wszystkich aktywnych
            $stmt = $pdo->query("SELECT email FROM newsletter_subscribers WHERE is_active = 1");
            $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($emails) > 0) {
                // Konfiguracja SMTP (skopiowana z newsletter.php - warto by to wydzielić do configa)
                $smtpConfig = [
                    'host' => 'poczta2686594.home.pl',
                    'port' => 587,
                    'username' => 'newsletter@pasiekapodgruszka.pl',
                    'password' => 'YOUR_SMTP_PASSWORD',
                    'from' => 'newsletter@pasiekapodgruszka.pl',
                    'fromName' => 'Pasieka Pod Gruszką'
                ];

                // Import funkcji pomocniczej SMTP (jeśli nie jest w helperze, wklejamy ją tutaj jako mini-klasę lub include)
                // Najlepiej byłoby zrobić require '../newsletter.php' ale tam jest logika obsługi requestu, więc skopiujemy funkcję sendSmtpMail
                // Zdefiniujmy ją poniżej, poza if-em głównym, lub dołączmy.
                // Szybki fix: definicja funkcji na dole pliku.

                $sentCount = 0;
                $errors = [];

                foreach ($emails as $email) {
                    // Opóźnienie, żeby nie zablokowali nam konta (np. 1 sekunda)
                    sleep(1); 
                    
                    // Doklej stopkę rezygnacji (opcjonalne, ale dobre praktyki)
                    // $bodyWithFooter = $body . "<br><hr><small>Otrzymałeś tę wiadomość, bo zapisałeś się na newsletter Pasieki.</small>";
                    
                     // Prosty template HTML
                    $htmlMessage = '
                    <!DOCTYPE html>
                    <html>
                    <head><style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333;}</style></head>
                    <body>
                        <div style="max-width:600px;margin:0 auto;padding:20px;">
                            <div style="text-align:center;margin-bottom:20px;">
                                <img src="https://pasiekapodgruszka.pl/assets/logo/logo.png" alt="Logo" style="height:50px;">
                            </div>
                            <div style="background:#fff;padding:20px;border-radius:5px;border:1px solid #eee;">
                                ' . $body . '
                            </div>
                            <div style="text-align:center;margin-top:20px;font-size:12px;color:#999;">
                                Pasieka Pod Gruszką<br>
                                Rybie, ul. Mała 10
                            </div>
                        </div>
                    </body>
                    </html>';

                    $res = sendSmtpMailLocal($email, $subject, $htmlMessage, $smtpConfig);
                    if ($res === true) {
                        $sentCount++;
                    } else {
                        $errors[] = "$email: $res";
                    }
                }

                $msg = "Wysłano do $sentCount osób.";
                if (count($errors) > 0) {
                    $msg .= " Błędy (" . count($errors) . "): " . implode(", ", array_slice($errors, 0, 3)) . "...";
                    $msgType = 'warning';
                } else {
                    $msgType = 'success';
                }
                logActivity('Wysłano newsletter', "Temat: $subject, Do osób: $sentCount");

            } else {
                $msg = "Brak subskrybentów w bazie.";
                $msgType = 'warning';
            }
        }
    }
}

// Pobieranie listy
$pdo = getDB();
$users = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Newsletter — Panel Admina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <!-- Quill.js CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-left">
            <a href="dashboard.php" style="color:#fff;text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Powrót</a>
        </div>
        <div class="nav-right">
             <span class="nav-user"><i class="fa-solid fa-envelope"></i> Newsletter</span>
        </div>
    </nav>

    <main class="admin-container">
        <div class="settings-card">
            <div style="display:grid; grid-template-columns: 1fr 300px; gap: 20px;">
                <!-- Formularz wysyłki -->
                <div class="newsletter-form-section">
                    <h3><i class="fa-solid fa-paper-plane"></i> Wyślij wiadomość do wszystkich</h3>
                    <form method="post" onsubmit="return confirm('Czy na pewno chcesz wysłać tę wiadomość do WSZYSTKICH subskrybentów?');">
                        <div class="form-group">
                            <label>Temat wiadomości:</label>
                            <input type="text" name="subject" required placeholder="np. Nowości w naszej pasiece">
                        </div>
                        
                        <div class="form-group">
                            <label>Treść wiadomości:</label>
                            <div id="editor-container" style="height: 300px; background: var(--bg-input); color: var(--text); border: 1px solid var(--border); border-radius: 5px;"></div>
                            <input type="hidden" name="body" id="body-input">
                        </div>

                        <button type="submit" name="send_newsletter" class="btn-main">
                            <i class="fa-solid fa-paper-plane"></i> WYŚLIJ NEWSLETTER
                        </button>
                    </form>
                </div>

                <!-- Statystyki -->
                <div class="newsletter-stats-section" style="text-align:center; padding:20px; background:var(--bg-input); border-radius:var(--radius); border:1px solid var(--border); display:flex; flex-direction:column; justify-content:center;">
                    <h3>Subskrybenci</h3>
                    <div style="font-size:4rem; font-weight:700; color:var(--gold); margin:10px 0;">
                        <?php echo count($users); ?>
                    </div>
                    <p style="color:var(--text-muted);">Zapisanych osób</p>
                </div>
            </div>
        </div>

        <div class="settings-card" style="margin-top:30px;">
            <h2><i class="fa-solid fa-list"></i> Lista Subskrybentów</h2>
            <div class="crud-table-wrap">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Data zapisu</th>
                            <th>Opcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td data-label="ID"><?php echo $u['id']; ?></td>
                            <td data-label="Email"><strong><?php echo htmlspecialchars($u['email']); ?></strong></td>
                            <td data-label="Data zapisu"><?php echo date('d.m.Y H:i', strtotime($u['created_at'])); ?></td>
                            <td data-label="Akcje">
                                <form method="post" style="display:inline; justify-content:flex-end;" onsubmit="return confirm('Usunąć ten email?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" name="delete_email" class="btn-small btn-danger">
                                        <i class="fa-solid fa-trash"></i> Usuń
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
    <!-- Quill.js JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });

        // Form submission
        document.querySelector('form').onsubmit = function() {
            var body = document.querySelector('#body-input');
            body.value = quill.root.innerHTML;
            
            if (quill.getText().trim().length === 0 && quill.root.innerHTML.indexOf('<img') === -1) {
                alert('Treść wiadomości nie może być pusta.');
                return false;
            }
            
            return confirm('Czy na pewno chcesz wysłać tę wiadomość do WSZYSTKICH subskrybentów?');
        };
    </script>
</body>
</html>

<?php
// Duplikat funkcji SMTP dla Admina (lokalna wersja)
function sendSmtpMailLocal($to, $subject, $htmlContent, $config) {
    $host = trim($config['host']);
    $port = $config['port'];
    $username = $config['username'];
    $password = $config['password'];
    $from = $config['from'];
    $fromName = $config['fromName'];

    try {
        $socket = fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) throw new Exception("$errstr ($errno)");

        $read = function() use ($socket) {
            $response = '';
            while ($str = fgets($socket, 515)) {
                $response .= $str;
                if (substr($str, 3, 1) == ' ') break;
            }
            return $response;
        };

        $send = function($cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $read(); 
        $send("EHLO " . $_SERVER['SERVER_NAME']); $read();

        if ($port == 587) {
            $send("STARTTLS"); $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send("EHLO " . $_SERVER['SERVER_NAME']); $read();
        }

        $send("AUTH LOGIN"); $read();
        $send(base64_encode($username)); $read();
        $send(base64_encode($password)); 
        if (strpos($read(), '235') === false) throw new Exception("Auth failed");

        $send("MAIL FROM: <$from>"); $read();
        $send("RCPT TO: <$to>"); $read();
        $send("DATA"); $read();

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <$from>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        $send($headers . "\r\n" . $htmlContent . "\r\n.");
        if (strpos($read(), '250') === false) throw new Exception("Send failed");

        $send("QUIT");
        fclose($socket);
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
?>
