<?php
$pageTitle = 'Kontakt - Pasieka Pod Gruszką | Rybie';
$pageDesc = 'Skontaktuj się z nami! Adres: Rybie, ul. Mała 10. Telefon: 506 224 982. Zapraszamy po naturalny miód prosto z pasieki.';
$ogImage = 'https://pasiekapodgruszka.pl/assets/images/tlo_glowne.jpg';

include 'includes/header.php';

// === Obsługa formularza kontaktowego ===
$formSuccess = false;
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['contact_submit'])) {
    $name    = trim(strip_tags($_POST['name'] ?? ''));
    $email   = trim($_POST['email'] ?? '');
    $subject = trim(strip_tags($_POST['subject'] ?? ''));
    $message = trim(strip_tags($_POST['message'] ?? ''));

    if (empty($name) || empty($email) || empty($message)) {
        $formError = 'Wypełnij wszystkie wymagane pola.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Podaj poprawny adres email.';
    } else {
        // Zapis do bazy danych
        try {
            $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, ip_address) VALUES (?,?,?,?,?)")
                ->execute([$name, $email, $subject, $message, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception $e) {
            // Tabela może nie istnieć — ignoruj błąd DB
        }

        // Wyślij email powiadomienie
        $headers  = "From: kontakt@pasiekapodgruszka.pl\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $emailBody = "Nowa wiadomość z formularza kontaktowego:\n\n";
        $emailBody .= "Imię: $name\nEmail: $email\n";
        if ($subject) $emailBody .= "Temat: $subject\n";
        $emailBody .= "\nWiadomość:\n$message\n";
        @mail('kontakt@pasiekapodgruszka.pl', "Nowa wiadomość: $subject", $emailBody, $headers);

        $formSuccess = true;
    }
}
?>

    <main class="container">
        <h1 class="section-title">Znajdź nas</h1>

        <div class="contact-wrapper">

            <div class="card contact-card">
                <h3 style="color: var(--c-gold, #ffc107); font-family: var(--font-head); font-size: 1.8rem; margin-bottom: 30px;">
                    Skontaktuj się z nami
                </h3>

                <div style="margin-bottom: 25px;">
                    <p style="color: var(--c-text-muted, #888); font-size: 0.9rem;">Telefon:</p>
                    <p style="font-size: 1.4rem; font-weight: bold;">
                        <i class="fas fa-phone" style="color: var(--c-orange, #e67e22); margin-right: 10px;"></i>
                        <a href="tel:+48506224982" class="contact-link" style="color: #fff; text-decoration: none;">+48 506 224 982</a>
                    </p>
                </div>

                <div style="margin-bottom: 25px;">
                    <p style="color: var(--c-text-muted, #888); font-size: 0.9rem;">E-mail:</p>
                    <p style="font-size: 1.1rem; font-weight: bold;">
                        <i class="fas fa-envelope" style="color: var(--c-orange, #e67e22); margin-right: 10px;"></i>
                        <a href="mailto:kontakt@pasiekapodgruszka.pl" class="contact-link"
                            style="color: #fff; text-decoration: none;">kontakt@pasiekapodgruszka.pl</a>
                    </p>
                </div>

                <div style="margin-bottom: 25px;">
                    <p style="color: var(--c-text-muted, #888); font-size: 0.9rem;">Adres:</p>
                    <p style="font-size: 1.1rem; font-weight: bold;">
                        <i class="fas fa-map-marker-alt" style="color: var(--c-orange, #e67e22); margin-right: 10px;"></i>
                        Rybie, ul. Mała 10, 05-090
                    </p>
                </div>

                <div style="margin-bottom: 25px;">
                    <p style="color: var(--c-text-muted, #888); font-size: 0.9rem;"></p>
                    <p style="font-size: 1.1rem; font-weight: bold;">
                        <i class="fas fa-id-card" style="color: var(--c-orange, #e67e22); margin-right: 10px;"></i>
                        RHD, WNI 14213521
                    </p>
                </div>

                <hr style="border:0; border-top:1px solid rgba(255, 193, 7, 0.2); margin: 30px 0;">

                <!-- Formularz kontaktowy -->
                <?php if ($formSuccess): ?>
                <div style="background:rgba(46,204,113,0.12); border:1px solid rgba(46,204,113,0.3); border-radius:12px; padding:18px 20px; text-align:center; margin-bottom:20px;">
                    <i class="fas fa-check-circle" style="color:#2ecc71; font-size:1.5rem; margin-bottom:8px; display:block;"></i>
                    <strong style="color:#2ecc71;">Wiadomość wysłana!</strong>
                    <p style="color:#888; font-size:0.9rem; margin-top:6px;">Odpiszemy najszybciej jak to możliwe.</p>
                </div>
                <?php else: ?>
                <?php if ($formError): ?>
                <div style="background:rgba(231,76,60,0.12); border:1px solid rgba(231,76,60,0.3); border-radius:10px; padding:12px 16px; margin-bottom:16px; color:#e74c3c; font-size:0.9rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $formError; ?>
                </div>
                <?php endif; ?>
                <h4 style="margin-bottom:16px; color:var(--c-gold, #ffc107);">
                    <i class="fas fa-paper-plane" style="margin-right:8px;"></i>Napisz do nas
                </h4>
                <form method="POST" style="display:flex; flex-direction:column; gap:12px;">
                    <input type="hidden" name="contact_submit" value="1">
                    <input type="text" name="name" placeholder="Twoje imię *" required
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                        style="padding:11px 15px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff; font-size:0.95rem; outline:none;">
                    <input type="email" name="email" placeholder="Adres email *" required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        style="padding:11px 15px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff; font-size:0.95rem; outline:none;">
                    <input type="text" name="subject" placeholder="Temat (opcjonalnie)"
                        value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                        style="padding:11px 15px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff; font-size:0.95rem; outline:none;">
                    <textarea name="message" placeholder="Treść wiadomości *" required rows="4"
                        style="padding:11px 15px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff; font-size:0.95rem; outline:none; resize:vertical; font-family:inherit;"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    <button type="submit"
                        style="padding:12px; background:linear-gradient(135deg,#ffc107,#e6ac00); border:none; border-radius:10px; color:#000; font-weight:700; font-size:1rem; cursor:pointer; transition:transform 0.2s;"
                        onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                        <i class="fas fa-paper-plane"></i> Wyślij wiadomość
                    </button>
                </form>
                <?php endif; ?>

                <small class="text-muted" style="color: #666; display: block; text-align: center; line-height: 1.5; margin-top:20px;">
                    <i class="fas fa-clock"></i> Najlepiej dzwonić w godzinach 8:00 - 18:00.<br>
                    Jeśli nie odbieramy, pewnie jesteśmy przy ulach! 🐝
                </small>
            </div>

            <div class="map-responsive">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d756.3076524412897!2d20.939411934151728!3d52.15602424676124!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x471933048ecbdd71%3A0xe9b7f7180e1220ab!2sMiody%20z%20Pasieki%20Pod%20Gruszk%C4%85!5e0!3m2!1spl!2spl!4v1771713291024!5m2!1spl!2spl" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </main>

<?php include 'includes/footer.php'; ?>
