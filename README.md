# 🐝 Pasieka Pod Gruszką

Repozytorium zawierające kod źródłowy zaawansowanej strony internetowej i sklepu dla pasieki **Pasieka Pod Gruszką**. Projekt łączy nowoczesny wygląd front-endu z potężnym, dedykowanym panelem administracyjnym.

---

## 🚀 Ostatnia duża aktualizacja (Marzec 2026)
- **Nowy, bezpieczny Panel Administratora** z systemem ról, logowania i odzyskiwania haseł.
- **Integracja Google Reviews** - Automatyczne pobieranie i wyświetlanie opinii klientów.
- **System Newslettera** - Automatyczne powiadomienia i obsługa e-maili przez SMTP.
- **Powiadomienia o dostępności** - Powiadomienia dla klientów o ponownej dostępności produktów.
- **Tryb Konserwacji (Maintenance Mode)** - Elegancki, zautomatyzowany tryb przerwy technicznej na stronie.
- **Zaawansowane statystyki i analityka** wbudowane bezpośrednio w panel.

---

## 🛠 Technologie
- **Backend:** PHP 8+
- **Baza Danych:** MySQL (PDO)
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Dodatkowe API:** Google Places API (opinii)
- **Inne:** SMTP dla zautomatyzowanych wysyłek mailowych

---

## 📂 Struktura projektu
- 🛡️ `/admin` - Wszechstronny panel administracyjny do zarządzania stroną (produkty, zamówienia, statystyki, newsletter, powiadomienia, opinie Google, SEO, kopie zapasowe, Tryb Konserwacji).
- 🎨 `/assets` - Zasoby graficzne, arkusze stylów (CSS) i skrypty (JS).
- ⚙️ `/includes` - Współdzielone pliki konfiguracyjne oraz reużywalne moduły (np. połączenie z bazą danych, nagłówki/stopki).
- 🗄️ `/sql` - Schematy i narzędzia do zaawansowanej migracji bazy danych (w tym dane testowe, uprawnienia ról i schemat włączania nowych funkcji).
- 📄 `index.php`, `products.php`, itp. - Publiczne strony obsługujące ruch klientów (galeria, sklep, kontakt z Google Maps, blog).

---

## ⚙️ Konfiguracja (Deployment)
1. **Baza Danych:** Zaimportuj pliki z folderu `/sql` do swojej bazy MySQL (najpierw `schema.sql`, potem wybrane migracje, np. `migration_accounts.sql`, `05_admin_email_schema.sql`).
2. **Połączenie z bazą:** Zaktualizuj i uzupełnij hasła w pliku `includes/db.php` oraz `maintenance.php` lub w innych plikach korzystających z połączeń PDO (hasła w repozytorium na GitHubie zostały **usunięte i zastąpione przez proste placeholdery** dla bezpieczeństwa).
3. **Serwer SMTP:** Zaktualizuj poświadczenia SMTP do wysyłki maili w systemie w plikach takich jak `newsletter.php` i `debug_smtp.php`.
4. **Wymagania:** Upewnij się, że serwer PHP posiada zainstalowany i włączony moduł `pdo_mysql`.

---
```text
Folder PATH listing
C:.
|   .gitignore
|   404.php
|   apply_advanced_migrations.php
|   apply_analytics_migration.php
|   apply_google_reviews_migration.php
|   apply_migration.php
|   apply_new_features_migration.php
|   blog.php
|   check_db.php
|   contact.php
|   debug_smtp.php
|   diag.php
|   gallery.php
|   index.html
|   index.php
|   kits.php
|   linkmenu.html
|   maintenance.html
|   maintenance.php
|   my.php
|   newsletter.php
|   notify_request.php
|   optimize_images.php
|   products.php
|   przepisy.php
|   README.md
|   rescue.php
|   robots.txt
|   save_cookie_consent.php
|   sitemap.xml
|   tree.txt
|   
+---admin
|       accounts.php
|       activity_log.php
|       analytics.php
|       ... (szczegółowy kod źródłowy panelu - ponad 35 plików kontrolerów i widoków)
|       
+---assets
|   +---css
|   |       style.css
|   +---data
|   |       blog.json, produkty.json, przepisy.json
|   +---images
|   |       tlo_glowne.jpg, products (...), zsestawy (...), blog (...), gallery (...)
|   +---js
|   |       main.js, script.js
|   \---logo
|           favicon.ico, logo.png, full_logo.jpeg
+---includes
|       cookie_banner.php, db.php, footer.php, header.php
+---sql
|       02_update_schema.sql, schema.sql, migrations (...)
\---tech
        testy.html, cart.html
```
