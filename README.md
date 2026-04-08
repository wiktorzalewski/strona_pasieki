# Pasieka Pod Gruszką

Platforma e-commerce oraz system CMS stworzony od podstaw w PHP dla lokalnej pasieki. Projekt zawiera autorski panel administracyjny, zautomatyzowany system newslettera, integrację z opiniami Google oraz dedykowaną architekturę trybu konserwacji (maintenance mode).

## Tech Stack

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-323330?logo=javascript&logoColor=F7DF1E&style=for-the-badge)

## Core Features
- **Autorski panel administratora:** Rozbudowana kontrola dostępu oparta na rolach (RBAC), logowanie aktywności oraz funkcjonalności CMS.
- **Zarządzanie produktami i stanami magazynowymi:** Śledzenie dostępności asortymentu, obsługa powiadomień o powrocie produktu na stan oraz zarządzanie galeriami zdjęć.
- **Zautomatyzowany Newsletter:** Zintegrowany klient SMTP do automatycznej wysyłki mailingu oraz zarządzania subskrybentami.
- **Synchronizacja opinii Google:** Pobieranie, cachowanie i sanityzacja opinii z Google Places API, aby zminimalizować opóźnienia przy ładowaniu strony (latency).
- **Architektura Maintenance Mode:** Automatyczny, sterowany z bazy danych tryb przerwy technicznej z obsługą planowanych okien konserwacyjnych.
- **Bezpieczeństwo:** Ochrona przed CSRF, bezpieczne hashowanie haseł (Bcrypt), parametryzowane zapytania PDO oraz sanityzacja danych wejściowych.

## Directory Structure
```text
.
├── admin/          # Kontrolery, widoki oraz routing panelu administratora
├── assets/         # Zasoby statyczne (CSS, JS, zoptymalizowane grafiki, endpointy JSON)
├── includes/       # Współdzielona logika biznesowa, szablony i wrapper połączenia z bazą
├── sql/            # Schematy bazy danych, migracje i początkowe dane (seed data)
└── setup/          # [WIP] Konfiguracja środowiska uruchomieniowego
```

## Setup Instructions

1. **Inicjalizacja Bazy Danych**
   - Utwórz nową bazę danych MySQL.
   - Uruchom `sql/schema.sql`, aby utworzyć tabele.
   - Wykonaj niezbędne migracje (np. `sql/migration_accounts.sql`, `sql/05_admin_email_schema.sql`).

2. **Konfiguracja Środowiska**
   - Skopiuj `includes/db.php.example` jako `includes/db.php` (lub bezpośrednio zmodyfikuj `includes/db.php`, jeśli pobierasz kod z repozytorium).
   - Zaktualizuj zmienne `$host`, `$db_user`, `$db_pass` oraz `$db_name` własnymi danymi dostępowymi do lokalnej bazy danych.
   - W przypadku funkcjonalności SMTP, skonfiguruj odpowiednie poświadczenia w plikach `newsletter.php` i `debug_smtp.php`.

3. **Serwer WWW**
   - Wskaż katalog projektu jako "document root" w lokalnym serwerze webowym (Apache/Nginx).
   - Upewnij się, że moduł `mod_rewrite` jest włączony w celu obsługi autorskiego routingu.
   - Nadaj odpowiednie uprawnienia zapisu dla katalogów, do których zapisywane są przesyłane pliki (np. `assets/images/gallery`).

## Recent Updates (Marzec 2026)
- Wdrożono bezpieczny przepływ uwierzytelniania z ograniczeniami dla odzyskiwania hasła.
- Dodano dynamiczny system cachowania opinii Google.
- Zmieniono logikę wysyłania e-maili z wykorzystaniem scentralizowanej usługi SMTP.
- Wprowadzono automatyczne harmonogramowanie trybu konserwacji (maintenance mode).

## Licencja
**Wszelkie prawa zastrzeżone (All Rights Reserved).**  
Kod jest udostępniony w celach demonstracyjnych i edukacyjnych (Open Source w zakresie wglądu). Zabrania się kopiowania, modyfikacji oraz redystrybucji kodu bez wyraźnej zgody autora (Wiktor Zalewski).

