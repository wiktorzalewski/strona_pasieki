<?php
require_once 'includes/db.php';

// Konfiguracja nagłówka
$pageTitle = 'Sklep z Miodem - Pasieka Pod Gruszką';
$pageDesc = 'Wybierz swój ulubiony miód! W ofercie miód lipowy, rzepakowy, spadziowy. Naturalne produkty pszczele prosto z pasieki.';
$ogImage = 'https://pasiekapodgruszka.pl/assets/images/tlo_glowne.jpg';

// Pobierz produkty z bazy
try {
    $stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY sort_order ASC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Błąd pobierania produktów: " . $e->getMessage());
}

// JSON-LD: Product schema dla każdego produktu
$productSchemas = [];
foreach ($products as $product) {
    $productSchemas[] = [
        '@type' => 'Product',
        'name' => $product['name'],
        'image' => 'https://pasiekapodgruszka.pl/' . $product['image_path'],
        'description' => strip_tags($product['description']),
        'brand' => [
            '@type' => 'Brand',
            'name' => 'Pasieka Pod Gruszką'
        ],
        'offers' => [
            '@type' => 'Offer',
            'price' => number_format($product['price'], 2, '.', ''),
            'priceCurrency' => 'PLN',
            'availability' => (isset($product['stock']) && $product['stock'] == 0) ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
            'seller' => [
                '@type' => 'Organization',
                'name' => 'Pasieka Pod Gruszką'
            ]
        ]
    ];
}
$extraJsonLd = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Miody naturalne',
    'itemListElement' => array_map(function($p, $i) {
        return [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => $p
        ];
    }, $productSchemas, array_keys($productSchemas))
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

include 'includes/header.php';
?>

    <main class="container">
        <h1 class="section-title" style="margin-bottom: 5px;">Wybierz miód idealny dla siebie</h1>

        <h5
            style="text-align: center; color: #888; font-weight: normal; margin-top: 0; margin-bottom: 40px; font-size: 1rem;">
            Kliknij w przycisk pod miodem, aby zobaczyć jego właściwości i znaleźć idealny dla siebie.
        </h5>

        <div class="product-grid">
            <?php foreach ($products as $product): 
                $capacity = $product['capacity'] ?? '900ml';
                $stock = $product['stock'] ?? -1;
                $isOutOfStock = ($stock == 0);
            ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-img"
                         onerror="this.src='https://placehold.co/800x600?text=Brak+Zdjecia'">

                    <?php if ($isOutOfStock): ?>
                        <div style="background:#e74c3c; color:#fff; text-align:center; padding:8px 0; font-weight:700; font-size:0.85rem; letter-spacing:0.5px; text-transform:uppercase;">
                            <i class="fa-solid fa-times-circle"></i> Niedostępny
                        </div>
                    <?php endif; ?>

                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    
                    <p style="color:var(--c-text-muted, #aaa);">
                        <?php 
                        if (preg_match('/<span class="highlight-text">(.*?)<\/span>/', $product['description'], $matches)) {
                            echo $matches[1];
                        } else {
                            echo "Naturalny miód pszczeli.";
                        }
                        ?>
                    </p>

                    <span style="display:block; margin:15px 0; font-size:1.2rem; font-weight:bold;">
                        <?php echo number_format($product['price'], 2); ?> PLN 
                        <small style="font-size:0.8rem; font-weight:normal;">/ <?php echo htmlspecialchars($capacity); ?></small>
                    </span>
                    
                    <button class="btn-main" onclick="openHoneyDetails('<?php echo $product['slug']; ?>')">ZOBACZ SZCZEGÓŁY</button>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="honey-details-modal" class="custom-modal-overlay">
        <div class="custom-modal-box">
            <button class="close-custom-modal" onclick="closeHoneyModal()">×</button>

            <div class="modal-split">
                <div class="modal-left">
                    <img src="" alt="Podgląd miodu" id="modal-img-target" class="modal-product-image">
                </div>

                <div class="modal-right">
                    <h2 id="modal-title-target" class="modal-title">Nazwa Produktu</h2>
                    <span id="modal-price-target" class="modal-price-tag">0.00 PLN</span>

                    <div class="attribute-grid">
                        <div class="attr-item">
                            <h4><i class="fas fa-utensils attr-icon"></i> Dominujący Smak</h4>
                            <p id="modal-taste-target">...</p>
                        </div>
                        <div class="attr-item">
                            <h4><i class="fas fa-lightbulb attr-icon"></i> Najlepsze do</h4>
                            <p id="modal-usage-target">...</p>
                        </div>
                    </div>

                    <div id="modal-desc-target" style="font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">
                    </div>

                    <div
                        style="background:rgba(255,255,255,0.05); padding:15px; border-radius:10px; margin-bottom: 20px; border-left: 3px solid #ffc107;">
                        <small style="color:#ddd;"><i class="fas fa-info-circle"></i> Słoik szklany, pojemność <span id="modal-capacity-target">900ml</span>.</small>
                    </div>
                    <div id="modal-stock-info" style="margin-bottom:15px;"></div>

                    <!-- Sekcja powiadomień (ukryta domyślnie) -->
                    <div id="modal-notify-section" style="display:none; margin-bottom:20px; background:rgba(255,255,255,0.05); padding:15px; border-radius:10px; border:1px solid #333;">
                        <h4 style="margin-top:0; font-size:1rem; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-bell" style="color:var(--gold);"></i> Powiadom o dostępności
                        </h4>
                        <p style="font-size:0.85rem; color:#aaa; margin:5px 0 10px;">Zostaw email - damy znać, gdy miód wróci!</p>
                        <div style="display:flex; gap:8px;">
                            <input type="email" id="modal-notify-email" placeholder="Twój email..." 
                                style="flex:1; padding:10px; border-radius:5px; border:1px solid #444; background:#222; color:#fff;">
                            <button id="modal-notify-btn" class="btn-main" style="padding:0 20px;">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </div>
                        <p id="modal-notify-msg" style="margin-top:10px; font-size:0.85rem; display:none;"></p>
                    </div>

                    <div id="modal-purchase-controls">
                        <div class="qty-control" style="margin-bottom: 20px;">
                            <button class="qty-btn" onclick="changeHoneyQty(-1)">-</button>
                            <span id="modal-qty-target"
                                style="font-size:1.2rem; font-weight:bold; min-width:40px; text-align:center;">1</span>
                            <button class="qty-btn" onclick="changeHoneyQty(1)">+</button>
                        </div>

                        <button class="btn-main" style="width:100%;" onclick="window.location.href='contact'">
                            <i class="fa-solid fa-circle-question"></i> JAK KUPIĆ?
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GENEROWANIE DANYCH JS Z BAZY -->
    <script>
        window.honeyDatabase = {};
        <?php foreach ($products as $product): ?>
        window.honeyDatabase['<?php echo $product['slug']; ?>'] = {
            id: <?php echo $product['id']; ?>,
            title: '<?php echo htmlspecialchars($product['name']); ?>',
            price: '<?php echo number_format($product['price'], 2); ?> PLN',
            img: '<?php echo htmlspecialchars($product['image_path']); ?>',
            taste: '<?php echo htmlspecialchars($product['taste']); ?>',
            usage: '<?php echo htmlspecialchars($product['usage_text']); ?>',
            description: `<?php echo addslashes($product['description']); ?>`,
            capacity: '<?php echo htmlspecialchars($product['capacity'] ?? '900ml'); ?>',
            stock: <?php echo intval($product['stock'] ?? -1); ?>
        };
        <?php endforeach; ?>
    </script>

<?php include 'includes/footer.php'; ?>
