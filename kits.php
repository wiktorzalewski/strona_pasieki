<?php
require_once 'includes/db.php';

$pageTitle = 'Zestawy Prezentowe - Miód na Prezent';
$pageDesc = 'Eleganckie zestawy prezentowe z miodem. Idealne na święta, urodziny i dla firm. Spraw słodką niespodziankę bliskim!';
$ogImage = 'https://pasiekapodgruszka.pl/assets/images/zestawy/trio.jpg';

// Pobierz zestawy z bazy
try {
    $stmt = $pdo->query("SELECT * FROM kits WHERE is_active = 1 ORDER BY id ASC");
    $kits = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Błąd pobierania zestawów: " . $e->getMessage());
}

// Generowanie danych JS do zmiennej $extraScripts
$jsData = "window.kitsDatabase = {};\n";
foreach ($kits as $kit) {
    $jsData .= "window.kitsDatabase['" . $kit['slug'] . "'] = {\n";
    $jsData .= "    title: '" . htmlspecialchars($kit['name']) . "',\n";
    $jsData .= "    price: '" . htmlspecialchars($kit['price_label']) . "',\n";
    $jsData .= "    img: '" . htmlspecialchars($kit['image_path']) . "',\n";
    $jsData .= "    desc: `" . addslashes($kit['description']) . "`\n";
    $jsData .= "};\n";
}

$extraScripts = '<script>' . $jsData . '</script>';

include 'includes/header.php';
?>

    <main class="container">
        <h1 class="section-title">Zestawy Prezentowe</h1>
        <p style="text-align:center; color:#888; margin-bottom: 40px;">Podaruj bliskim słodką chwilę. Idealne na święta,
            urodziny i dla firm.</p>

        <div class="product-grid">
            <?php foreach ($kits as $kit): ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($kit['image_path']); ?>" 
                         class="product-img"
                         onerror="this.src='https://placehold.co/600x600?text=Zestaw'">
                    <h3><?php echo htmlspecialchars($kit['name']); ?></h3>
                    
                    <p style="color:#aaa;">
                        <?php echo htmlspecialchars(substr(strip_tags($kit['description']), 0, 50)) . '...'; ?>
                    </p>
                    
                    <span style="display:block; margin:15px 0; font-size:1.2rem; font-weight:bold;">
                        <?php echo htmlspecialchars($kit['price_label']); ?>
                    </span>
                    
                    <button class="btn-main" onclick="openKitDetails('<?php echo $kit['slug']; ?>')">ZOBACZ SZCZEGÓŁY</button>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="kit-modal" class="custom-modal-overlay">
        <div class="custom-modal-box">
            <button class="close-custom-modal" onclick="closeKitModal()">×</button>
            <div class="modal-split">
                <div class="modal-left">
                    <img src="" id="modal-img" class="modal-product-image">
                </div>
                <div class="modal-right">
                    <h2 id="modal-title" class="modal-title"></h2>
                    <span id="modal-price"
                        style="font-size:1.5rem; font-weight:bold; display:block; margin-bottom:20px;"></span>
                    <div id="modal-desc" style="color:#ddd; line-height:1.6; margin-bottom:30px;"></div>

                    <button class="btn-main" style="width:100%;" onclick="window.location.href='contact'">
                        <i class="fa-solid fa-envelope"></i> ZAMÓW ZESTAW
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
