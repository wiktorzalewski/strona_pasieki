<?php
$pageTitle = 'Blog Pszczelarski - Wieści z Pasieki | Pasieka Pod Gruszką';
$pageDesc = 'Aktualności z życia pasieki. Relacje z miodobrań, ciekawostki o pszczołach. Śledź nas na Facebooku!';
$ogImage = 'https://pasiekapodgruszka.pl/assets/images/tlo_glowne.jpg';

$extraCss = '
    <style>
        .fb-wrapper {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            max-width: 550px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .fb-iframe {
            border: none;
            overflow: hidden;
            width: 100%;
            height: 800px;
        }

        .fb-alt-link {
            text-align: center;
            margin-top: 30px;
        }

        @media (max-width: 600px) {
            .fb-wrapper {
                padding: 10px;
            }

            .fb-iframe {
                height: 600px;
            }
        }
    </style>';

include 'includes/header.php';
?>

    <main class="container">
        <h1 class="section-title">Wieści z Pasieki</h1>
        <p style="text-align:center; color:#888; margin-bottom: 40px;">
            Bądź na bieżąco! Zobacz co słychać u naszych pszczół na Facebooku.
        </p>

        <div class="fb-wrapper">
            <iframe
                src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2Ffacebook&tabs=timeline&width=500&height=800&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true&appId"
                class="fb-iframe" scrolling="no" frameborder="0" allowfullscreen="true"
                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
            </iframe>
        </div>

        <div class="fb-alt-link">
            <p style="color:#aaa; margin-bottom:15px;">Nie widzisz postów powyżej?</p>
            <a href="https://www.facebook.com/" target="_blank" class="btn-main">
                <i class="fab fa-facebook"></i> PRZEJDŹ DO FACEBOOKA
            </a>
        </div>

    </main>

<?php include 'includes/footer.php'; ?>
