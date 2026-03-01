<?php
// Wyłącz wyświetlanie błędów, aby nie psuły JSONa (ważne!)
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// --- KONFIGURACJA SMTP (UZUPEŁNIJ DANE) ---
$smtpConfig = [
    'host' => 'poczta2686594.home.pl',   // Usunięto spację
    'port' => 587,               // 587 dla TLS, 465 dla SSL
    'username' => 'newsletter@pasiekapodgruszka.pl',
    'password' => 'YOUR_SMTP_PASSWORD', // <--- Wpisz hasło
    'from' => 'newsletter@pasiekapodgruszka.pl',
    'fromName' => 'Pasieka Pod Gruszką'
];

// Sprawdź czy request jest typu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Pobierz dane
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data && isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
} elseif (isset($data['email'])) {
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
} else {
    $email = '';
}

// Walidacja
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy adres email.']);
    exit;
}

// Treść wiadomości HTML (PREMIUM DESIGN)
$subject = '🐝 Witaj w rodzinie Pasieki Pod Gruszką!';
$logoUrl = 'https://pasiekapodgruszka.pl/assets/logo/logo.png'; 

$messageHtml = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Witaj w newsletterze!</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f7f7f7; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .email-wrapper { width: 100%; background-color: #f7f7f7; padding: 40px 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .email-header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 3px solid #f1c40f; }
        .email-header img { max-width: 180px; height: auto; }
        .email-body { padding: 40px 30px; color: #333333; line-height: 1.8; }
        .h1-title { font-size: 24px; font-weight: 700; color: #2c3e50; margin-bottom: 20px; text-align: center; }
        .text-p { font-size: 16px; margin-bottom: 20px; color: #555555; }
        .discount-box { background: linear-gradient(135deg, #fffcf5 0%, #fff7d6 100%); border: 2px dashed #f1c40f; border-radius: 10px; padding: 25px; text-align: center; margin: 30px 0; }
        .discount-label { font-size: 14px; font-weight: 600; color: #7f8c8d; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; display: block; }
        .discount-code { font-size: 32px; font-weight: 800; color: #d35400; margin: 0; display: block; letter-spacing: 2px; }
        .cta-button { display: inline-block; background-color: #d35400; color: #ffffff; text-decoration: none; padding: 15px 35px; border-radius: 50px; font-weight: bold; font-size: 16px; margin-top: 10px; transition: background-color 0.3s; }
        .email-footer { background-color: #2c3e50; padding: 30px; text-align: center; color: #ecf0f1; font-size: 13px; }
        .footer-link { color: #bdc3c7; text-decoration: none; margin: 0 10px; }
        .social-icons { margin-top: 20px; }
        @media only screen and (max-width: 600px) {
            .email-body { padding: 30px 20px; }
            .h1-title { font-size: 20px; }
            .discount-code { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <img src="' . $logoUrl . '" alt="Pasieka Pod Gruszką">
            </div>
            
            <!-- Body -->
            <div class="email-body">
                <h1 class="h1-title">Witaj w naszej miodowej rodzinie! 🐝</h1>
                
                <p class="text-p">Dzień dobry!</p>
                <p class="text-p">Dziękujemy, że do nas dołączyłeś. Od teraz jesteś częścią społeczności, która ceni **naturę, tradycję i prawdziwy smak**.</p>
                <p class="text-p">Będziemy dzielić się z Tobą historiami z pasieki, informacjami o nowych zbiorach (rzepak, lipa, spadź) oraz unikalnymi przepisami.</p>



                <div style="text-align: center;">
                    <a href="https://pasiekapodgruszka.pl/products.html" class="cta-button">PRZEJDŹ DO SKLEPU</a>
                </div>
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <p>Pasieka Pod Gruszką</p>
                <p>Rybie, ul. Mała 10, 05-090</p>
                <p style="opacity: 0.7;">Wiadomość wysłana automatycznie. Nie odpowiadaj na nią.</p>
                <div style="margin-top: 20px; border-top: 1px solid #34495e; padding-top: 20px;">
                    <a href="https://pasiekapodgruszka.pl" class="footer-link">Strona Główna</a> | 
                    <a href="https://pasiekapodgruszka.pl/contact.html" class="footer-link">Kontakt</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
';

// --- ZAPIS DO BAZY DANYCH ---
require_once __DIR__ . '/includes/db.php';

try {
    // Używamy INSERT IGNORE aby nie wywalać błędu jak mail już jest
    $stmt = $pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
    $stmt->execute([$email]);
} catch (Exception $e) {
    // Ignorujemy błędy bazy przy zapisie newslettera, żeby ch chociaż mail wyszedł, 
    // albo logujemy cicho. Użytkownik i tak dostanie maila.
}

// --- WYSYŁKA ---

try {
    $result = sendSmtpMail($email, $subject, $messageHtml, $smtpConfig);
    if ($result === true) {
        echo json_encode(['success' => true, 'message' => 'Dziękujemy! Sprawdź swoją skrzynkę mailową.']);
    } else {
        throw new Exception($result);
    }
} catch (Exception $e) {
    // Logowanie błędu do pliku (jeśli masz uprawnienia)
    // @file_put_contents('email_error.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
    
    // Zwracamy błąd JSON
    echo json_encode(['success' => false, 'message' => 'Nie udało się wysłać wiadomości: ' . $e->getMessage()]);
}

// --- FUNKCJA SMTP (Mini Class) ---
function sendSmtpMail($to, $subject, $htmlContent, $config) {
    $host = trim($config['host']); // Zabezpieczenie przed spacjami
    $port = $config['port'];
    $username = $config['username'];
    $password = $config['password'];
    $from = $config['from'];
    $fromName = $config['fromName'];

    try {
        $socket = fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) throw new Exception("Nie można połączyć z serwerem SMTP: $errstr ($errno)");

        // Helper do czytania odpowiedzi
        $read = function() use ($socket) {
            $response = '';
            while ($str = fgets($socket, 515)) {
                $response .= $str;
                if (substr($str, 3, 1) == ' ') break;
            }
            return $response;
        };

        // Helper do wysyłania komend
        $send = function($cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $read(); // Powitanie serwera

        $send("EHLO " . $_SERVER['SERVER_NAME']);
        $read();

        // Start TLS jeśli port 587 (dla home.pl zazwyczaj tak)
        if ($port == 587) {
            $send("STARTTLS");
            $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send("EHLO " . $_SERVER['SERVER_NAME']);
            $read();
        }

        // Autoryzacja
        $send("AUTH LOGIN");
        $read();
        $send(base64_encode($username));
        $read();
        $send(base64_encode($password));
        $res = $read();
        if (strpos($res, '235') === false) throw new Exception("Błąd autoryzacji SMTP (błędne hasło?).");

        // Dane maila
        $send("MAIL FROM: <$from>");
        $read();
        $send("RCPT TO: <$to>");
        $read();
        $send("DATA");
        $read();

        // Nagłówki i treść
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <$from>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        $fullData = $headers . "\r\n" . $htmlContent . "\r\n.";
        $send($fullData);
        $res = $read();
        
        if (strpos($res, '250') === false) throw new Exception("Błąd wysyłania treści.");

        $send("QUIT");
        fclose($socket);

        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
?>
