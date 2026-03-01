<div id="cookie-banner" class="cookie-banner">
    <div class="cookie-content">
        <p>
            <i class="fas fa-cookie-bite" style="color: var(--c-gold, #ffc107);"></i>
            Ta strona używa plików cookies, aby zapewnić najlepszą jakość korzystania z naszej witryny.
            Klikając "Akceptuję", wyrażasz zgodę na używanie wszystkich plików cookies.
        </p>
        <button id="accept-cookies" class="btn-main btn-sm">AKCEPTUJĘ</button>
    </div>
</div>

<style>
    .cookie-banner {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: rgba(10, 10, 10, 0.95);
        border-top: 1px solid var(--c-gold, #ffc107);
        padding: 15px 20px;
        z-index: 10000;
        box-shadow: 0 -5px 15px rgba(0,0,0,0.5);
        transform: translateY(100%);
        transition: transform 0.5s ease-out;
        display: none; /* Początkowo ukryty, JS go pokaże */
    }

    .cookie-banner.visible {
        transform: translateY(0);
        display: block;
    }

    .cookie-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        color: #ddd;
        font-size: 0.9rem;
    }

    .cookie-content p {
        margin: 0;
    }

    .btn-sm {
        padding: 8px 20px;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .cookie-banner {
            padding: 20px;
            bottom: 0 !important;
        }
        
        .cookie-content {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        
        .btn-sm {
            width: 100%;
            padding: 12px 0;
            font-size: 1rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('cookie-banner');
        const acceptBtn = document.getElementById('accept-cookies');
        
        // Sprawdź czy zgoda już jest
        if (!localStorage.getItem('cookie_consent')) {
            // Pokaż banner z opóźnieniem dla lepszego efektu
            setTimeout(() => {
                banner.style.display = 'block';
                // Wymuszenie reflow dla animacji
                banner.offsetHeight; 
                banner.classList.add('visible');
            }, 1000);
        }

        acceptBtn.addEventListener('click', function() {
            // Ukryj banner
            banner.classList.remove('visible');
            setTimeout(() => {
                banner.style.display = 'none';
            }, 500);

            // Zapisz w LocalStorage
            localStorage.setItem('cookie_consent', 'true');

            // Wyślij dane do logowania
            fetch('save_cookie_consent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'accept_cookies' })
            }).then(response => {
                console.log('Cookie consent logged');
            }).catch(error => {
                console.error('Error logging cookie consent:', error);
            });
        });
    });
</script>
