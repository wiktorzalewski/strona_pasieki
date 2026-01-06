<?php
require_once __DIR__ . '/db.php';
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
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="keywords" content="miód, pasieka, pszczoły, miód naturalny, miód lipowy, miód rzepakowy, rybie, raszyn, warszawa, pasieka pod gruszką">
    <meta name="description" content="<?php echo htmlspecialchars($pageDesc); ?>">
    <meta name="robots" content="index, follow" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Wiktor Zalewski" />

    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="canonical" href="<?php echo htmlspecialchars($pageUrl); ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($pageUrl); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDesc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    <meta property="og:locale" content="pl_PL">
    <meta property="og:site_name" content="Pasieka Pod Gruszką">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($pageUrl); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($pageDesc); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/logo/favicon.ico">
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
