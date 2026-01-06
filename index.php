<?php
$pageTitle = 'Pasieka Pod Gruszką - Prawdziwe Miody Naturalne';
$pageDesc = 'Odkryj smak natury w Pasiece Pod Gruszką! Oferujemy tradycyjne miody: lipowy, rzepakowy, spadziowy. Sprawdź naszą ofertę i odwiedź nas w Rybiu.';
$ogImage = 'https://pasiekapodgruszka.pl/assets/images/tlo_glowne.jpg';

// Styl specyficzny dla strony głównej (Hero Blur)
$extraCss = '
<style>
    .hero-header::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url(\'assets/images/tlo_glowne.jpg\');
        background-size: cover;
        background-position: center;
        filter: blur(1px);
        transform: scale(1.1);
        z-index: 0;
    }
</style>';

include 'includes/header.php';
?>

    <header class="hero-header">
        <div class="hero-content">
            <h1 class="section-title"
                style="margin-bottom: 10px; font-size: 4rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Złoto Natury
            </h1>
            <p
                style="font-size: 1.3rem; color: var(--c-gold); margin-bottom: 40px; text-shadow: 1px 1px 2px rgba(0,0,0,0.8); font-weight: bold;">
                Tradycyjna pasieka w nowoczesnym wydaniu.
            </p>
            <a href="products" class="btn-main" style="text-decoration: none;">WYBIERZ MIÓD</a>
        </div>
    </header>

    <main class="container">
        <h2 class="section-title">Dlaczego my?</h2>

        <div class="section-feature" style="text-align: center;">
            <div class="icon-feature-box">
                <i class="fa-solid fa-handshake-angle"></i>
            </div>
            <h3>Wsparcie zapylaczy</h3>
            <p>Kupując u nas, wspierasz lokalny ekosystem. Każdy słoik to pomoc w utrzymaniu populacji pszczół na
                Mazowszu.</p>
        </div>

        <div class="section-feature" style="text-align: center;">
            <div class="icon-feature-box">
                <i class="fa-solid fa-tree"></i>
            </div>
            <h3>Pasieki z dala od ludzi</h3>
            <p>Nasze ule stoją w lasach i na łąkach, z dala od smogu, hałasu i dróg szybkiego ruchu.</p>
        </div>

        <div class="section-feature" style="text-align: center;">
            <div class="icon-feature-box">
                <i class="fa-solid fa-leaf"></i>
            </div>
            <h3>Czysta natura</h3>
            <p>Zero syropu cukrowego, barwników czy aromatów. 100% czystego nektaru i spadzi od polskiej pszczoły.</p>
        </div>

        <div class="section-feature" style="text-align: center;">
            <div class="icon-feature-box">
                <i class="fa-solid fa-heart"></i>
            </div>
            <h3>Pasieka z pasji</h3>
            <p>Nasza pasieka powstała z prawdziwego zamiłowania. Dbamy o każdą pszczelą rodzinę jak o własną.</p>
        </div>
    </main>

<?php include 'includes/footer.php'; ?>
