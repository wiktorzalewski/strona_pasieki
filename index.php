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

<?php
// === OPINIE GOOGLE ===
$reviewsVisible = getSetting('google_reviews_visible', '0') ?? '0';
$reviewsLimit   = intval(getSetting('google_reviews_count', '5') ?: 5);
$overallRating  = getSetting('google_overall_rating', '5.0') ?: '5.0';
$totalRatings   = getSetting('google_total_ratings', '0') ?: '0';

if ($reviewsVisible === '1' && $pdo) {
    try {
        $gReviews = $pdo->query("SELECT * FROM google_reviews WHERE is_visible=1 ORDER BY review_time DESC LIMIT $reviewsLimit")->fetchAll();
    } catch (Exception $e) { $gReviews = []; }

    if (!empty($gReviews)):
?>
<section class="reviews-section">
    <div class="reviews-container">
        <div class="reviews-header">
            <div>
                <h2 class="section-title" style="text-align:left;margin-bottom:8px;">Co mówią klienci?</h2>
                <p style="color:var(--c-text-muted);font-size:0.95rem;">Opinie zweryfikowane przez Google</p>
            </div>
            <div class="reviews-overall">
                <div class="reviews-score"><?php echo htmlspecialchars($overallRating); ?></div>
                <div>
                    <div class="reviews-stars">
                        <?php for ($i=1;$i<=5;$i++): ?>
                        <i class="fa-<?php echo $i <= round(floatval($overallRating)) ? 'solid' : 'regular'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <div style="font-size:0.82rem;color:var(--c-text-muted);margin-top:3px;"><?php echo number_format(intval($totalRatings)); ?> opinii w Google</div>
                    <a href="https://www.google.com/maps/search/?api=1&query=Pasieka+Pod+Gruszką+Rybie" target="_blank" rel="noopener" class="reviews-google-link">
                        <i class="fa-brands fa-google"></i> Napisz opinię
                    </a>
                </div>
            </div>
        </div>

        <div class="reviews-grid">
        <?php foreach ($gReviews as $i => $rev): ?>
        <div class="review-card" style="animation-delay:<?php echo $i*0.1; ?>s">
            <div class="review-header">
                <img src="<?php echo htmlspecialchars($rev['reviewer_photo'] ?: 'https://ui-avatars.com/api/?name='.urlencode($rev['reviewer_name']).'&background=141414&color=E1B12C&size=48&bold=true'); ?>"
                     alt="<?php echo htmlspecialchars($rev['reviewer_name']); ?>"
                     class="review-avatar"
                     loading="lazy"
                     onerror="this.src='https://ui-avatars.com/api/?name=?&background=141414&color=E1B12C&size=48'">
                <div>
                    <div class="review-author"><?php echo htmlspecialchars($rev['reviewer_name']); ?></div>
                    <div class="review-date"><?php echo htmlspecialchars($rev['time_description']); ?></div>
                </div>
                <div class="review-google-badge">
                    <i class="fa-brands fa-google"></i>
                </div>
            </div>
            <div class="review-stars">
                <?php for ($s=1;$s<=5;$s++): ?>
                <i class="fa-solid fa-star<?php echo $s > $rev['rating']?' review-star-empty':''; ?>"></i>
                <?php endfor; ?>
            </div>
            <?php if ($rev['text']): ?>
            <p class="review-text">&ldquo;<?php echo htmlspecialchars($rev['text']); ?>&rdquo;</p>
            <?php else: ?>
            <p class="review-text" style="font-style:italic;color:var(--c-text-muted);">(Ocena bez komentarza)</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:30px;">
            <a href="https://www.google.com/maps/search/?api=1&query=Pasieka+Pod+Gruszką+Rybie" target="_blank" rel="noopener" class="btn-main">
                <i class="fa-brands fa-google"></i> Wszystkie opinie w Google
            </a>
        </div>
    </div>
</section>
<?php endif; } ?>

<?php include 'includes/footer.php'; ?>
