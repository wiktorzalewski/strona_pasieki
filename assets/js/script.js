document.addEventListener('DOMContentLoaded', () => {
    console.log('SCRIPT.JS (NOWY PLIK) załadowano poprawnie! Wersja FINALNA.');
    initNewsletter();
    initSideMenu();
    if (document.getElementById('gallery-grid')) initGallery();
});

/* --- BAZA DANYCH MIODÓW (Obsługa PHP + Fallback dla HTML) --- */
if (typeof window.honeyDatabase === 'undefined') {
    window.honeyDatabase = {
        'spadziowy': {
            title: 'Miód Spadziowy',
            price: '55.00 PLN',
            img: 'assets/images/products/spadziowy.jpg',
            taste: 'Żywiczny, łagodny, mało słodki.',
            usage: 'Do kawy (nie zakwasza), na chleb, prosto z łyżeczki.',
            description: `
                <span class="highlight-text">Królewski, wytrawny smak prosto z lasu.</span>
                <p>Miód spadziowy, nazywany często "królewskim", powstaje ze spadzi drzew iglastych. Charakteryzuje się ciemną barwą i gęstą konsystencją.</p>
                <ul class="honey-features">
                    <li>Działa silnie przeciwzapalnie i wykrztuśnie.</li>
                    <li>Niezastąpiony w stanach wyczerpania organizmu.</li>
                    <li>Zawiera 9 razy więcej biopierwiastków niż inne miody.</li>
                </ul>`
        },
        'lipowy': {
            title: 'Miód Lipowy',
            price: '45.00 PLN',
            img: 'assets/images/products/lipowy.jpg',
            taste: 'Ostry, wyrazisty, aromat kwiatów lipy.',
            usage: 'Herbata z cytryną, syropy domowe, na przeziębienia.',
            description: `
                <span class="highlight-text">Złoty lek na przeziębienia.</span>
                <p>Jeden z najbardziej aromatycznych miodów polskich. W smaku lekko ostry, a czasem gorzkawy, co jest gwarancją jego naturalnego pochodzenia.</p>
                <ul class="honey-features">
                    <li>Skuteczny w walce z grypą i przeziębieniem.</li>
                    <li>Działa napotnie i obniża gorączkę.</li>
                    <li>Łagodzi stres i ułatwia zasypianie.</li>
                </ul>`
        },
        'rzepakowy': {
            title: 'Miód Rzepakowy',
            price: '40.00 PLN',
            img: 'assets/images/products/rzepakowy.jpg',
            taste: 'Bardzo słodki, łagodny, kremowy.',
            usage: 'Słodzenie twarogu, napoje izotoniczne, wypieki.',
            description: `
                <span class="highlight-text">Kremowy, łagodny i energetyczny.</span>
                <p>Miód wiosenny o bardzo szybkiej krystalizacji, dlatego sprzedajemy go w postaci kremowanej. Idealny dla dzieci ze względu na łagodny smak.</p>
                <ul class="honey-features">
                    <li>Wspomaga serce i układ krążenia.</li>
                    <li>Przyspiesza regenerację po wysiłku fizycznym.</li>
                    <li>Doskonale oczyszcza wątrobę (detoks).</li>
                </ul>`
        }
    };
}

/* --- LOGIKA MODALI MIODÓW --- */
window.openHoneyDetails = function (productId) {
    const modal = document.getElementById('honey-details-modal');
    const data = window.honeyDatabase[productId];

    if (!data) return;

    const titleEl = document.getElementById('modal-title-target');
    const priceEl = document.getElementById('modal-price-target');
    const descEl = document.getElementById('modal-desc-target');
    const tasteEl = document.getElementById('modal-taste-target');
    const usageEl = document.getElementById('modal-usage-target');
    const qtyEl = document.getElementById('modal-qty-target');
    const imgEl = document.getElementById('modal-img-target');
    const capacityEl = document.getElementById('modal-capacity-target');


    if (titleEl) titleEl.innerText = data.title;
    if (priceEl) priceEl.innerText = data.price;
    if (descEl) descEl.innerHTML = data.description;
    if (tasteEl) tasteEl.innerText = data.taste;
    if (usageEl) usageEl.innerText = data.usage;
    if (qtyEl) qtyEl.innerText = "1";
    if (capacityEl) capacityEl.innerText = data.capacity || '900ml';

    // Stock info
    const stockEl = document.getElementById('modal-stock-info');
    const notifySection = document.getElementById('modal-notify-section');
    const purchaseControls = document.getElementById('modal-purchase-controls');

    const stock = data.stock;

    // Reset msg
    const msgEl = document.getElementById('modal-notify-msg');
    if (msgEl) { msgEl.style.display = 'none'; msgEl.innerText = ''; msgEl.className = ''; }

    if (stock < 0) {
        stockEl.innerHTML = '<small style="color:#2ecc71;"><i class="fa-solid fa-check-circle"></i> Produkt dostępny</small>';
        if (notifySection) notifySection.style.display = 'none';
        if (purchaseControls) purchaseControls.style.display = 'block';
    } else if (stock === 0) {
        stockEl.innerHTML = '<small style="color:#e74c3c;font-weight:bold;"><i class="fa-solid fa-times-circle"></i> Produkt chwilowo niedostępny</small>';
        if (notifySection) notifySection.style.display = 'block';
        if (purchaseControls) purchaseControls.style.display = 'none';
    } else if (stock <= 10) {
        stockEl.innerHTML = '<small style="color:#f39c12;"><i class="fa-solid fa-exclamation-triangle"></i> Ostatnie ' + stock + ' szt. — zamów szybko!</small>';
        if (notifySection) notifySection.style.display = 'none';
        if (purchaseControls) purchaseControls.style.display = 'block';
    } else {
        stockEl.innerHTML = '<small style="color:#2ecc71;"><i class="fa-solid fa-check-circle"></i> Dostępne: ' + stock + ' szt.</small>';
        if (notifySection) notifySection.style.display = 'none';
        if (purchaseControls) purchaseControls.style.display = 'block';
    }


    if (imgEl) {
        imgEl.src = data.img;
        imgEl.onerror = function () { this.src = 'https://placehold.co/800x600?text=Brak+Zdjecia'; };
    }

    // Zapisz slug w atrybucie modala, żeby powiadomienia wiedziały jaki to produkt
    if (modal) {
        modal.dataset.productId = productId; // productId tutaj to slug przekazany w argumencie funkcji

        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('active');
        }, 10);
    }
};

/* --- POWIADOMIENIA O DOSTĘPNOŚCI --- */
function initNotifyForm() {
    const btn = document.getElementById('modal-notify-btn');
    const emailInp = document.getElementById('modal-notify-email');
    const msgEl = document.getElementById('modal-notify-msg');
    const modal = document.getElementById('honey-details-modal');

    if (btn && emailInp && modal) {
        btn.addEventListener('click', () => {
            const email = emailInp.value.trim();
            const productId = modal.dataset.productId; // To jest slug

            // Pobieramy ID numeryczne z obiektu JS
            const productData = window.honeyDatabase[productId];
            const dbId = productData ? productData.id : 0;

            if (!validateEmail(email)) {
                showNotifyMsg('Podaj poprawny email.', 'error');
                return;
            }
            if (!dbId) {
                showNotifyMsg('Błąd produktu (brak ID).', 'error');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            fetch('notify_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: dbId, email: email })
            })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
                    showNotifyMsg(data.message, data.success ? 'success' : 'error');
                    if (data.success) emailInp.value = '';
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
                    showNotifyMsg('Błąd połączenia.', 'error');
                });
        });
    }

    function showNotifyMsg(text, type) {
        if (!msgEl) return;
        msgEl.innerText = text;
        msgEl.style.display = 'block';
        msgEl.style.color = type === 'success' ? '#2ecc71' : '#e74c3c';
    }
}

// Wywołaj inicjalizację
document.addEventListener('DOMContentLoaded', () => {
    initNotifyForm();
});

window.closeHoneyModal = function () {
    const modal = document.getElementById('honey-details-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
};

window.changeHoneyQty = function (delta) {
    const qtySpan = document.getElementById('modal-qty-target');
    if (qtySpan) {
        let val = parseInt(qtySpan.innerText) + delta;
        if (val < 1) val = 1;
        qtySpan.innerText = val;
    }
};

/* --- KITS LOGIC (Zestawy) --- */
if (typeof window.kitsDatabase === 'undefined') {
    window.kitsDatabase = {
        'trio': {
            title: 'Zestaw Trio Smaków',
            price: '45.00 PLN',
            img: 'assets/images/zestawy/trio.jpg',
            desc: 'Idealny na drobny upominek. Zawiera 3 słoiczki po 130g: Miód Lipowy, Spadziowy i Rzepakowy. Zapakowane w ekologiczne pudełko z okienkiem i wełną drzewną.'
        },
        'swiateczny': {
            title: 'Zestaw Świąteczny',
            price: '95.00 PLN',
            img: 'assets/images/zestawy/duz.jpg',
            desc: 'Bogaty zestaw pod choinkę. W środku: 2x500ml miodu (do wyboru), drewniana pałeczka do miodu, świeczka z wosku pszczelego. Całość przewiązana jutowym sznurkiem z ozdobną zawieszką.'
        },
        'firmowy': {
            title: 'Zestaw dla Firm',
            price: 'Wycena Indywidualna',
            img: 'assets/images/zestawy/firmowy.jpg',
            desc: 'Szukasz prezentów dla pracowników lub kontrahentów? Przygotujemy zestawy dopasowane do Twojego budżetu. Możliwość dodania logo firmy na etykiecie lub bileciku. Skontaktuj się z nami!'
        }
    };
}

window.openKitDetails = function (id) {
    const data = window.kitsDatabase[id];
    if (!data) return;

    const titleEl = document.getElementById('modal-title');
    const priceEl = document.getElementById('modal-price');
    const descEl = document.getElementById('modal-desc');
    const imgEl = document.getElementById('modal-img');
    const modal = document.getElementById('kit-modal');

    if (titleEl) titleEl.innerText = data.title;
    if (priceEl) priceEl.innerText = data.price;
    if (descEl) descEl.innerText = data.desc;

    if (imgEl) {
        imgEl.src = data.img;
        imgEl.onerror = function () { this.src = 'https://placehold.co/600x600?text=Zestaw'; };
    }

    if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
    }
};

window.closeKitModal = function () {
    const modal = document.getElementById('kit-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.style.display = 'none', 300);
    }
};

/* --- GALLERY LOGIC (Galeria) --- */
function initGallery() {
    // Sprawdź czy mamy dane z bazy (window.galleryImages)
    const sourceImages = window.galleryImages || [];
    const isDynamic = sourceImages.length > 0;

    // Jeśli brak danych z bazy, użyj domyślnych ustawień (fallback)
    const staticTotal = 25;
    const effectiveTotal = isDynamic ? sourceImages.length : staticTotal;

    const imagePath = 'assets/images/gallery/';
    const imageExtension = '.jpg';
    let currentImageIndex = 1;

    // Pagination settings
    const itemsPerPage = 8;
    let itemsLoaded = 0;

    const galleryGrid = document.getElementById('gallery-grid');

    if (!galleryGrid) return;

    // Funkcja ładująca partię zdjęć
    function loadNextBatch() {
        const start = itemsLoaded;
        const end = Math.min(itemsLoaded + itemsPerPage, effectiveTotal);

        for (let i = start; i < end; i++) {
            // Logic for indexing:
            // If dynamic: i is index (0-based) in sourceImages
            // If static: we used 1-based index loop. Let's adapt.

            const displayIndex = i + 1; // 1-based for counter/ID

            const item = document.createElement('div');
            item.className = 'gallery-item';
            // Animation class for fade-in effect
            item.style.animation = `fadeInUp 0.5s ease backwards ${(i - start) * 0.1}s`;
            item.onclick = () => openLightbox(displayIndex);

            const img = document.createElement('img');

            if (isDynamic) {
                img.src = sourceImages[i].src;
                img.alt = sourceImages[i].alt;
            } else {
                img.src = `${imagePath}${displayIndex}${imageExtension}`;
                img.alt = `Zdjęcie z pasieki nr ${displayIndex}`;
            }

            img.loading = "lazy";

            // Ukryj jeśli brak zdjęcia
            img.onerror = function () {
                this.parentElement.style.display = 'none';
            };

            const overlay = document.createElement('div');
            overlay.className = 'gallery-overlay';
            overlay.innerHTML = '<i class="fas fa-search-plus"></i>';

            item.appendChild(img);
            item.appendChild(overlay);
            galleryGrid.appendChild(item);
        }

        itemsLoaded = end;

        // Manage Load More button logic
        updateLoadMoreButton();
    }

    // Function to create/update Load More button
    function updateLoadMoreButton() {
        let loadMoreBtn = document.getElementById('gallery-load-more');

        // Remove existing button if it exists to re-append at the bottom or hide
        if (loadMoreBtn) {
            loadMoreBtn.remove();
        }

        if (itemsLoaded < effectiveTotal) {
            loadMoreBtn = document.createElement('button');
            loadMoreBtn.id = 'gallery-load-more';
            loadMoreBtn.className = 'btn-main';
            loadMoreBtn.innerText = 'ZOBACZ WIĘCEJ';
            loadMoreBtn.style.margin = '30px auto';
            loadMoreBtn.style.display = 'block';
            loadMoreBtn.onclick = loadNextBatch;

            // Insert after the grid
            galleryGrid.parentNode.insertBefore(loadMoreBtn, galleryGrid.nextSibling);
        }
    }

    // Initial load
    loadNextBatch();

    window.openLightbox = function (index) {
        // index argument is expected to be 1-based logic from render loop
        const lightbox = document.getElementById('lightbox');
        currentImageIndex = index;
        updateLightboxImage();

        if (lightbox) {
            lightbox.style.display = 'flex';
            setTimeout(() => {
                lightbox.classList.add('active');
            }, 10);
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeLightbox = function () {
        const lightbox = document.getElementById('lightbox');
        if (lightbox) {
            lightbox.classList.remove('active');
            setTimeout(() => {
                lightbox.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
    };

    window.changeImage = function (direction) {
        currentImageIndex += direction;
        if (currentImageIndex > effectiveTotal) currentImageIndex = 1;
        else if (currentImageIndex < 1) currentImageIndex = effectiveTotal;
        updateLightboxImage();
    };

    function updateLightboxImage() {
        const lbImg = document.getElementById('lb-image');
        const counter = document.getElementById('lb-counter');

        if (lbImg) {
            lbImg.style.opacity = 0;
            setTimeout(() => {
                if (isDynamic) {
                    // currentImageIndex is 1-based, array is 0-based
                    const imgData = sourceImages[currentImageIndex - 1];
                    if (imgData) lbImg.src = imgData.src;
                } else {
                    lbImg.src = `${imagePath}${currentImageIndex}${imageExtension}`;
                }

                if (counter) counter.innerText = `${currentImageIndex} / ${effectiveTotal}`;
                lbImg.style.opacity = 1;
            }, 150);
        }
    }

    // Keyboard events
    window.addEventListener('keydown', (e) => {
        const lightbox = document.getElementById('lightbox');
        if (lightbox && lightbox.style.display === 'flex') {
            if (e.key === 'Escape') window.closeLightbox();
            if (e.key === 'ArrowRight') window.changeImage(1);
            if (e.key === 'ArrowLeft') window.changeImage(-1);
        }
    });

    // Click outside to close
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target.id === 'lightbox') {
                window.closeLightbox();
            }
        });
    }
}

/* --- FUNKCJE POMOCNICZE (Newsletter, Menu) --- */
function initNewsletter() {
    console.log('Inicjalizacja newslettera...');
    const box = document.getElementById('newsletter-box');
    const closeBtn = document.getElementById('newsletter-close');
    const input = box ? box.querySelector('input[type="email"]') : null;
    const submitBtn = box ? box.querySelector('button') : null;

    if (!box) {
        console.log('Nie znaleziono boxa newslettera.');
        return;
    }

    // Sprawdź czy element istnieje
    if (!sessionStorage.getItem('newsletter_closed')) {
        setTimeout(() => {
            box.classList.remove('hidden');
            console.log('Newsletter pokazany.');
        }, 3000);

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                closeNewsletter(box);
            });
        }

        if (submitBtn && input) {
            console.log('Przycisk i input znalezione. Dodawanie listenera.');
            submitBtn.addEventListener('click', () => {
                const email = input.value.trim();
                console.log('Kliknięto zapisz. Email:', email);

                if (!validateEmail(email)) {
                    alert('Proszę wpisać poprawny adres e-mail.');
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerText = 'Wysyłanie...';

                fetch('newsletter.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: email })
                })
                    .then(response => {
                        console.log('Odpowiedź serwera:', response);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Dane z serwera:', data);
                        if (data.success) {
                            alert(data.message);
                            closeNewsletter(box);
                        } else {
                            alert('Błąd: ' + data.message);
                            submitBtn.disabled = false;
                            submitBtn.innerText = 'ZAPISZ SIĘ';
                        }
                    })
                    .catch(error => {
                        console.error('Błąd fetch:', error);
                        alert('Wystąpił błąd podczas zapisywania. Spróbuj ponownie.');
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'ZAPISZ SIĘ';
                    });
            });
        } else {
            console.error('Brak inputa lub przycisku w newsletterze.');
        }
    } else {
        console.log('Newsletter zablokowany przez sessionStorage.');
    }
}

function closeNewsletter(box) {
    if (box) {
        box.classList.add('hidden');
        sessionStorage.setItem('newsletter_closed', 'true');
    }
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function initSideMenu() {
    const menuBtn = document.getElementById('menu-toggle');
    const closeBtn = document.getElementById('menu-close');
    const sideMenu = document.getElementById('side-menu');
    const overlay = document.getElementById('side-menu-overlay');

    function toggleMenu() {
        if (sideMenu) sideMenu.classList.toggle('active');
        if (overlay) overlay.classList.toggle('active');
    }

    if (menuBtn) menuBtn.addEventListener('click', toggleMenu);
    if (closeBtn) closeBtn.addEventListener('click', toggleMenu);
    if (overlay) overlay.addEventListener('click', toggleMenu);
}

// Global click handler to close modals when clicking outside
window.onclick = function (e) {
    const honeyModal = document.getElementById('honey-details-modal');
    if (honeyModal && e.target === honeyModal) {
        window.closeHoneyModal();
    }

    const kitModal = document.getElementById('kit-modal');
    if (kitModal && e.target === kitModal) {
        window.closeKitModal();
    }
};
