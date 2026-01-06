<?php
require_once 'includes/db.php';

$pageTitle = 'O nas - Pasja i Tradycja | Pasieka Pod Gruszką';
$pageDesc = 'Poznaj historię Pasieki Pod Gruszką. Dowiedz się, kim jesteśmy i jak z pasją dbamy o nasze pszczoły.';
$ogImage = 'https://pasiekapodgruszka.pl/assets/images/tlo_glowne.jpg';

// Pobierz sekcje "O nas" z bazy
try {
    $stmt = $pdo->query("SELECT * FROM about_sections WHERE is_active = 1 ORDER BY sort_order ASC");
    $sections = $stmt->fetchAll();
} catch (PDOException $e) {
    $sections = [];
}

$extraCss = '
    <style>
        .about-section {
            display: flex;
            align-items: center;
            gap: 40px;
            margin-bottom: 80px;
        }

        .about-section.reverse {
            flex-direction: row-reverse;
        }

        .about-img {
            flex: 1;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
        }

        .about-img img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s;
        }

        .about-img:hover img {
            transform: scale(1.05);
        }

        .about-text {
            flex: 1;
        }

        .about-text h3 {
            color: var(--c-gold, #ffc107);
            font-size: 2rem;
            margin-bottom: 20px;
            font-family: \'Playfair Display\', serif;
        }

        .about-text p {
            line-height: 1.8;
            color: #ccc;
            margin-bottom: 15px;
            font-size: 1.05rem;
        }

        @media (max-width: 900px) {

            .about-section,
            .about-section.reverse {
                flex-direction: column;
                gap: 20px;
            }

            .about-img {
                width: 100%;
            }
        }
    </style>';

include 'includes/header.php';
?>

    <main class="container">
        <h1 class="section-title">Poznaj naszą historię</h1>

        <?php foreach ($sections as $section): ?>
            <div class="about-section <?php echo ($section['image_position'] === 'right') ? 'reverse' : ''; ?>">
                <div class="about-img">
                    <img src="<?php echo htmlspecialchars($section['image_path']); ?>" alt="<?php echo htmlspecialchars($section['title']); ?>"
                        onerror="this.src='https://placehold.co/600x400?text=Nasza+Pasieka'">
                </div>
                <div class="about-text">
                    <h3><?php echo htmlspecialchars($section['title']); ?></h3>
                    <?php echo $section['content']; ?>
                    
                    <?php if (!empty($section['button_text']) && !empty($section['button_link'])): ?>
                        <div style="margin-top: 20px;">
                            <a href="<?php echo htmlspecialchars($section['button_link']); ?>" class="btn-main"><?php echo htmlspecialchars($section['button_text']); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </main>

<?php include 'includes/footer.php'; ?>
