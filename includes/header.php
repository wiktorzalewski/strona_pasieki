<?php
require_once __DIR__ . '/db.php';

// === TRACKING ODWIEDZIN ===
// Nie śledź adminów, botów ani AJAX-a
$isAdmin = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false;
$isBot = preg_match('/bot|crawler|spider|curl|wget/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
$isXhr = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
if (!$isAdmin && !$isBot && !$isXhr && $pdo) {
    try {
        $page = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        $page = $page ?: 'index';
        $page = substr($page, 0, 100);
        $pdo->prepare("INSERT INTO page_views (page, views, date) VALUES (?, 1, CURDATE())
                        ON DUPLICATE KEY UPDATE views = views + 1")
            ->execute([$page]);
    } catch (Exception $e) {
        // cicha awaria
    }
}
// ========================

// === REDIRECT MANAGER ===
if (!$isAdmin && !$isBot && !$isXhr && $pdo) {
    try {
        $_reqPath = '/' . ltrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        $stmt = $pdo->prepare("SELECT id, to_url, redirect_code FROM redirects WHERE from_path=? AND is_active=1 LIMIT 1");
        $stmt->execute([$_reqPath]);
        $_redirect = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($_redirect) {
            $pdo->prepare("UPDATE redirects SET hits=hits+1 WHERE id=?")->execute([$_redirect['id']]);
            header('Location: ' . $_redirect['to_url'], true, $_redirect['redirect_code']);
            exit();
        }
        unset($_reqPath, $stmt, $_redirect);
    } catch (Exception $e) {
        // cicha awaria
    }
}
// =======================

// Domyślne wartości zmiennych, jeśli nie zostały ustawione na podstronie
if (!isset($pageTitle)) $pageTitle = 'Pasieka Pod Gruszką';
if (!isset($pageDesc)) $pageDesc = 'Odkryj smak natury w Pasiece Pod Gruszką! Oferujemy tradycyjne miody: lipowy, rzepakowy, spadziowy.';
if (!isset($ogImage)) $ogImage = 'https://pasiekapodgruszka.pl/assets/images/tlo_glowne.jpg';
if (!isset($pageUrl)) $pageUrl = 'https://pasiekapodgruszka.pl/' . basename($_SERVER['PHP_SELF']);

// Funkcja pomocnicza do klasy 'active' w menu
function isActive($pageName) {
    if (basename($_SERVER['PHP_SELF']) == $pageName) {
        return 'active';
    }
    return '';
}

// Breadcrumb — dynamiczne generowanie
$breadcrumbName = 'Start';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$breadcrumbMap = [
    'index'    => 'Start',
    'products' => 'Sklep z Miodem',
    'kits'     => 'Zestawy Prezentowe',
    'rescue'   => 'Pogotowie Rójkowe',
    'przepisy' => 'Przepisy z Miodem',
    'gallery'  => 'Galeria Zdjęć',
    'blog'     => 'Blog Pszczelarski',
    'my'       => 'O nas',
    'contact'  => 'Kontakt',
];
if (isset($breadcrumbMap[$currentPage])) {
    $breadcrumbName = $breadcrumbMap[$currentPage];
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="keywords" content="miód, pasieka, pszczoły, miód naturalny, miód lipowy, miód rzepakowy, miód spadziowy, miód wielokwiatowy, rybie, raszyn, warszawa, mazowieckie, pasieka pod gruszką, naturalny miód pszczeli, pogotowie rójkowe">
    <meta name="description" content="<?php echo htmlspecialchars($pageDesc); ?>">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Wiktor Zalewski" />
    <meta name="theme-color" content="#1a1a1a">
    <meta name="copyright" content="© 2025 Pasieka Pod Gruszką">
    <meta name="geo.region" content="PL-14">
    <meta name="geo.placename" content="Rybie, Raszyn">
    <meta name="geo.position" content="52.150000;20.930000">
    <meta name="ICBM" content="52.150000, 20.930000">
    <meta name="format-detection" content="telephone=no">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="canonical" href="<?php echo htmlspecialchars($pageUrl); ?>">
    <link rel="apple-touch-icon" href="/assets/logo/logo.png">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($pageUrl); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDesc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="pl_PL">
    <meta property="og:site_name" content="Pasieka Pod Gruszką">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($pageUrl); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($pageDesc); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">

    <!-- JSON-LD: LocalBusiness (Google Knowledge Panel) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "@id": "https://pasiekapodgruszka.pl/#business",
        "name": "Pasieka Pod Gruszką",
        "description": "Tradycyjna pasieka oferująca naturalne miody pszczele: lipowy, rzepakowy, spadziowy i wielokwiatowy. Pogotowie rójkowe na terenie powiatu.",
        "url": "https://pasiekapodgruszka.pl",
        "telephone": "+48506224982",
        "email": "kontakt@wikzal.pl",
        "image": "https://pasiekapodgruszka.pl/assets/logo/logo.png",
        "logo": {
            "@type": "ImageObject",
            "url": "https://pasiekapodgruszka.pl/assets/logo/logo.png"
        },
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "ul. Mała 10",
            "addressLocality": "Rybie",
            "postalCode": "05-090",
            "addressRegion": "Mazowieckie",
            "addressCountry": "PL"
        },
        "geo": {
            "@type": "GeoCoordinates",
            "latitude": 52.150000,
            "longitude": 20.930000
        },
        "priceRange": "$$",
        "currenciesAccepted": "PLN",
        "paymentAccepted": "Gotówka, Przelew",
        "openingHoursSpecification": {
            "@type": "OpeningHoursSpecification",
            "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
            "opens": "08:00",
            "closes": "18:00"
        },
        "sameAs": []
    }
    </script>

    <!-- JSON-LD: WebSite (Sitelinks Search Box) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "@id": "https://pasiekapodgruszka.pl/#website",
        "name": "Pasieka Pod Gruszką",
        "url": "https://pasiekapodgruszka.pl",
        "inLanguage": "pl-PL",
        "publisher": {
            "@id": "https://pasiekapodgruszka.pl/#business"
        }
    }
    </script>

    <!-- JSON-LD: BreadcrumbList -->
    <?php if ($currentPage !== 'index'): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Start",
                "item": "https://pasiekapodgruszka.pl/"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "<?php echo htmlspecialchars($breadcrumbName); ?>",
                "item": "<?php echo htmlspecialchars($pageUrl); ?>"
            }
        ]
    }
    </script>
    <?php endif; ?>

    <?php if (isset($extraJsonLd)) echo $extraJsonLd; ?>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="/assets/logo/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/logo/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>

<body>
    <div id="transition-overlay"></div>

    <nav>
        <div class="nav-container">
            <a href="/" class="logo">
                <img src="/assets/logo/logo.png" alt="Logo">Pasieka Pod Gruszką
            </a>

            <ul class="menu desktop-links">
                <li><a href="/" class="<?php echo isActive('index.php'); ?>"><i class="fa-solid fa-house"></i> Start</a></li>
                <li><a href="products" class="<?php echo isActive('products.php'); ?>"><i class="fa-solid fa-store"></i> Sklep</a></li>
                <li><a href="rescue" style="color:var(--c-orange, #e67e22);" class="<?php echo isActive('rescue.php'); ?>"><i class="fa-solid fa-truck-medical"></i> Pogotowie</a></li>
                <li><a href="contact" class="<?php echo isActive('contact.php'); ?>"><i class="fa-solid fa-envelope"></i> Kontakt</a></li>
            </ul>

            <button id="menu-toggle" class="hamburger-btn">
                <i class="fa-solid fa-bars"></i> <span class="btn-text">MENU</span>
            </button>
        </div>
    </nav>

    <div id="side-menu-overlay" class="menu-overlay"></div>
    <div id="side-menu" class="side-menu-panel">
        <div class="side-menu-header">
            <span class="side-logo"><i class="fa-solid fa-bars"></i> Nawigacja</span>
            <span id="menu-close" class="close-btn">&times;</span>
        </div>

        <ul class="side-menu-list">
            <li><a href="/"><i class="fa-solid fa-house"></i> Start</a></li>
            <li><a href="products"><i class="fa-solid fa-jar"></i> Sklep z miodami</a></li>
            <li><a href="kits"><i class="fa-solid fa-gift"></i> Zestawy Prezentowe</a></li>
            <li><a href="rescue"><i class="fa-solid fa-truck-medical"></i> Pogotowie Rójkowe</a></li>
            <li><a href="gallery"><i class="fa-solid fa-images"></i> Galeria Zdjęć</a></li>
            <li><a href="blog"><i class="fa-solid fa-newspaper"></i> Blog Pszczelarski</a></li>
            <li><a href="przepisy"><i class="fa-solid fa-utensils"></i> Przepisy Cioci Agnieszki</a></li>
            <li><a href="my"><i class="fa-solid fa-user-group"></i> O nas</a></li>
            <li><a href="contact"><i class="fa-solid fa-address-book"></i> Kontakt</a></li>
            <li><a href="linkmenu.html"><i class="fa-solid fa-link"></i> Wszystkie Linki</a></li>
        </ul>
    </div>
