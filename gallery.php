<?php
require_once 'includes/db.php';

$pageTitle = 'Galeria Zdjęć - Życie Pasieki | Pasieka Pod Gruszką';
$pageDesc = 'Zobacz zdjęcia z naszej pasieki. Pszczoły przy pracy, miodobranie i piękno natury.';
$ogImage = 'https://pasiekapodgruszka.pl/assets/images/gallery/1.jpg';

// Pobierz liste zdjec z bazy
try {
    $stmt = $pdo->query("SELECT * FROM gallery_images ORDER BY sort_order ASC, id DESC");
    $imagesRaw = $stmt->fetchAll();
    
    $jsImages = [];
    foreach ($imagesRaw as $img) {
        $jsImages[] = [
            'src' => 'assets/images/gallery/' . $img['file_name'],
            'alt' => $img['title'] ?? 'Zdjęcie z pasieki'
        ];
    }
} catch (PDOException $e) {
    $jsImages = [];
}

// Przekazanie danych do JS
if (!empty($jsImages)) {
    $extraScripts = '<script>window.galleryImages = ' . json_encode($jsImages) . ';</script>';
}

include 'includes/header.php';
?>

    <main class="container">
        <h1 class="section-title">Galeria Pasieki</h1>
        <p style="text-align:center; color:#888; margin-bottom: 30px;">
            Zobacz jak wygląda życie w naszej pasiece. Kliknij zdjęcie, aby je powiększyć.
        </p>

        <div id="gallery-grid" class="gallery-container">
            <!-- JS will populate this -->
        </div>

    </main>

    <div id="lightbox" class="lightbox">
        <span class="lb-close" onclick="closeLightbox()">&times;</span>

        <button class="lb-btn lb-prev" onclick="changeImage(-1)">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="lightbox-content">
            <img src="" alt="Powiększenie" id="lb-image" class="lightbox-img">
        </div>

        <button class="lb-btn lb-next" onclick="changeImage(1)">
            <i class="fa-solid fa-chevron-right"></i>
        </button>

        <div id="lb-counter" class="lb-counter">1 / 10</div>
    </div>

<?php include 'includes/footer.php'; ?>
