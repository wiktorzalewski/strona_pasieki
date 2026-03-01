# Pasieka Pod Gruszką

E-commerce platform and content management system built from scratch in PHP for a local apiary business. Includes a custom-built administration panel, automated newsletter system, Google Reviews integration, and a dedicated maintenance mode architecture.

## Tech Stack
- **Backend:** PHP 8.x
- **Database:** MySQL (PDO)
- **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6)
- **Integrations:** Google Places API, SMTP for automated mailing

## Core Features
- **Custom Admin Dashboard:** Comprehensive role-based access control (RBAC), activity logging, and CMS capabilities.
- **Product & Stock Management:** Tracks inventory, handles back-in-stock notifications, and manages image galleries.
- **Automated Newsletter & Mailing:** Integrated SMTP client with automated bulk mailing and subscriber management.
- **Google Reviews Sync:** Fetches, caches, and sanitizes Google Reviews via Places API to minimize latency on page load.
- **Maintenance Architecture:** Automated database-driven maintenance mode routing with scheduled downtime support.
- **Security:** CSRF tokens, secure password hashing (Bcrypt), parameterized PDO queries, and sanitized inputs.

## Directory Structure
```text
.
├── admin/          # Admin dashboard controllers, views, and routing
├── assets/         # Static assets (CSS, JS, optimized images, JSON endpoints)
├── includes/       # Shared logic, layout partials, and DB connection wrappers
├── sql/            # Database schemas, migrations, and seed data
└── setup/          # [WIP] Environment configurations
```

## Setup Instructions

1. **Database Initialization**
   - Create a new MySQL database.
   - Run `sql/schema.sql` to initialize tables.
   - Run necessary migrations (e.g., `sql/migration_accounts.sql`, `sql/05_admin_email_schema.sql`).

2. **Environment Configuration**
   - Rename/copy `includes/db.php.example` to `includes/db.php` (or modify `includes/db.php` directly if pulling from this repo).
   - Update `$host`, `$db_user`, `$db_pass`, and `$db_name` with your local database credentials.
   - For SMTP functionality, configure credentials in `newsletter.php` and `debug_smtp.php`.

3. **Web Server**
   - Point your local web server (Apache/Nginx) document root to the project directory.
   - Ensure `mod_rewrite` is enabled if setting up custom routing in the future.
   - Provide write permissions for any upload directories if applicable (`assets/images/gallery`).

## Recent Updates (March 2026)
- Implemented secure authentication flow with password recovery constraints.
- Added dynamic Google Reviews caching system.
- Refactored mailing logic to use a centralized SMTP service.
- Introduced automated maintenance mode scheduling.

## License
Proprietary / All Rights Reserved.
