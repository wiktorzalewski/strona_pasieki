# Pasieka Pod Gruszką

Repozytorium zawierające kod źródłowy strony internetowej **Pasieka Pod Gruszką**.

## Technologie
- PHP 8+
- MySQL (PDO)
- HTML5 / CSS3 / Vanilla JavaScript

## Struktura projektu
- `/admin` - Panel administracyjny do zarządzania stroną (zamówienia, statystyki, newsletter, powiadomienia)
- `/assets` - Zasoby graficzne, arkusze stylów (CSS) i skrypty (JS)
- `/includes` - Współdzielone pliki konfiguracyjne (np. połączenie z bazą danych)
- `/sql` - Schematy i migracje bazy danych
- `index.php`, `products.php`, itp. - Publiczne strony widoczne dla klientów

## Konfiguracja (Deployment)
1. Baza Danych: Zaimportuj pliki z folderu `/sql` do swojej bazy MySQL (najpierw `schema.sql`, potem migracje).
2. Połączenie z bazą: Zaktualizuj hasła w `includes/db.php` oraz `maintenance.php` lub w innych plikach konfiguracyjnych (hasła w tym repozytorium zostały ukryte).
3. Serwer SMTP: Zaktualizuj poświadczenia SMTP w `newsletter.php` i `debug_smtp.php`.
4. Upewnij się, że serwer obsługuje PHP oraz moduł `pdo_mysql`.
