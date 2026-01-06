<?php
$pageTitle = 'Kontakt - Pasieka Pod Gruszką | Rybie';
$pageDesc = 'Skontaktuj się z nami! Adres: Rybie, ul. Mała 10. Telefon: 506 224 982. Zapraszamy po naturalny miód prosto z pasieki.';
$ogImage = 'https://pasiekapodgruszka.pl/assets/images/tlo_glowne.jpg';

include 'includes/header.php';
?>

    <main class="container">
        <h1 class="section-title">Znajdź nas</h1>

        <div class="contact-wrapper">

            <div class="card contact-card">
                <h3
                    style="color: var(--c-gold, #ffc107); font-family: var(--font-head); font-size: 1.8rem; margin-bottom: 30px;">
                    Skontaktuj się z nami
                </h3>

                <div style="margin-bottom: 25px;">
                    <p style="color: var(--c-text-muted, #888); font-size: 0.9rem;">Telefon:</p>
                    <p style="font-size: 1.4rem; font-weight: bold;">
                        <i class="fas fa-phone" style="color: var(--c-orange, #e67e22); margin-right: 10px;"></i>
                        <a href="tel:+48506224982" class="contact-link" style="color: #fff; text-decoration: none;">+48
                            506 224 982</a>
                    </p>
                </div>

                <div style="margin-bottom: 25px;">
                    <p style="color: var(--c-text-muted, #888); font-size: 0.9rem;">E-mail:</p>
                    <p style="font-size: 1.1rem; font-weight: bold;">
                        <i class="fas fa-envelope" style="color: var(--c-orange, #e67e22); margin-right: 10px;"></i>
                        <a href="mailto:kontakt@wikzal.pl" class="contact-link"
                            style="color: #fff; text-decoration: none;">kontakt@wikzal.pl</a>
                    </p>
                </div>

                <div style="margin-bottom: 25px;">
                    <p style="color: var(--c-text-muted, #888); font-size: 0.9rem;">Adres:</p>
                    <p style="font-size: 1.1rem; font-weight: bold;">
                        <i class="fas fa-map-marker-alt"
                            style="color: var(--c-orange, #e67e22); margin-right: 10px;"></i>
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

                <small class="text-muted" style="color: #666; display: block; text-align: center; line-height: 1.5;">
                    <i class="fas fa-clock"></i> Najlepiej dzwonić w godzinach 8:00 - 18:00.<br>
                    Jeśli nie odbieramy, pewnie jesteśmy przy ulach! 🐝
                </small>
            </div>

            <div class="map-responsive">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2448.169123456789!2d20.930000!3d52.150000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4719331899999999%3A0x0!2sul.+Ma%C5%82a+10%2C+05-090+Rybie!5e0!3m2!1spl!2spl!4v1700000000000!5m2!1spl!2spl"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </main>

<?php include 'includes/footer.php'; ?>
