    <div id="newsletter-box" class="newsletter-box hidden">
        <span id="newsletter-close" class="newsletter-close">×</span>
        <h4 style="color:var(--c-gold);">Słodkie Nowości!</h4>
        <p style="font-size:0.9rem; margin:10px 0;">Zapisz się, aby otrzymać kod na darmową dostawę.</p>
        <input type="email" placeholder="Twój email"
            style="width:100%; padding:10px; border:1px solid var(--c-gold); background:transparent; color:#fff;">
        <button class="btn-main" style="width:100%; margin-top:10px; padding:10px;">ZAPISZ SIĘ</button>
    </div>

    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <div class="logo">
                    <img src="/assets/logo/logo.png" style="height:30px; margin-right:10px;">Pasieka Pod Gruszką
                </div>
                <p style="color:#888; font-size:0.9rem; margin-top: 15px;">
                    Naturalne miody z pasją.
                </p>
            </div>

            <div class="footer-col">
                <h4 style="color:#fff; margin-bottom:15px;">Odbiory Osobiste</h4>
                <p style="color:#888; font-size:0.9rem;">
                    <i class="fas fa-map-marker-alt" style="color:var(--c-gold); margin-right:5px;"></i> Rybie, ul. Mała
                    10, 05-090
                </p>
                <p style="color:var(--c-gold); font-weight:bold; margin: 10px 0;">
                    <i class="fas fa-phone" style="margin-right:5px;"></i> Tel: +48 506 224 982
                </p>
                <small style="color:#666;">Prosimy dzwonić przed przyjazdem.</small>
            </div>

            <div class="footer-col">
                <h4 style="color:#fff; margin-bottom:15px;">Linki</h4>
                <ul style="list-style:none;">
                    <li style="margin-bottom:5px;">
                        <a href="products" style="color:#888;"><i class="fas fa-chevron-right"
                                style="font-size:0.8rem; margin-right:5px;"></i> Sklep</a>
                    </li>
                    <li style="margin-bottom:5px;">
                        <a href="rescue" style="color:#888;"><i class="fas fa-chevron-right"
                                style="font-size:0.8rem; margin-right:5px;"></i> Pogotowie Rójkowe</a>
                    </li>
                    <li style="margin-bottom:5px;">
                        <a href="przepisy" style="color:#888;"><i class="fas fa-chevron-right"
                                style="font-size:0.8rem; margin-right:5px;"></i> Przepisy</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom"
            style="text-align:center; padding:20px; border-top:1px solid #222; font-size:0.85rem; color:#666;">
            © 2025 Pasieka Pod Gruszką. Projektant & programista strony:
            <a href="https://github.com/wiktorzalewski" target="_blank" class="developer-signature"
                style="color:#fff; text-decoration:none; font-weight:bold; margin-left:5px;">Wiktor Zalewski</a>
            <p> Błędy strony należy zgłaszać za pomocą wiadomości email na wiktorzalewski50@gmail.com </p>

            <div style="margin-top: 20px;">
                <a href="https://miody.wikzal.pl/phpmyadmin/" class="admin-panel-link" target="_blank">
                    <i class="fas fa-tools"></i> Panel Admina
                </a>
            </div>
        </div>
    </footer>

    <?php if (isset($extraScripts)) echo $extraScripts; ?>
    <script src="/assets/js/main.js?v=2"></script>
</body>

</html>
