server {
    listen 80;
    server_name space.wikzal.pl www.space.wikzal.pl;
    root /var/www/space.wikzal.pl;
    index index.php index.html;

    # --- 1. BEZPIECZEŃSTWO ---
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    # --- 2. LOGI ---
    access_log /var/log/nginx/space.wikzal.pl_access.log;
    error_log /var/log/nginx/space.wikzal.pl_error.log;

    # --- 3. MAINTENANCE (z Bazy Danych) ---
    # Obsługiwane w includes/db.php

    # --- 4. GŁÓWNA LOKALIZACJA (PRETTY URLs) ---
    location / {
        try_files $uri $uri/ @extensionless-php;
    }

    # Obsługa linków bez .php (np. /products -> /products.php)
    location @extensionless-php {
        rewrite ^(.*)$ $1.php last;
    }

    # --- 5. OBSŁUGA PHP 8.4 ---
    location ~ \.php$ {
        # TO JEST KLUCZ DO DZIAŁAJĄCEGO 404:
        try_files $uri =404; 
        
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_intercept_errors on;
    }

    # --- 6. CUSTOM 404 ---
    error_page 404 /404.php;
    location = /404.php {
        internal;
    }

    # --- 7. Cache statycznych plików ---
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Blokuj ukryte pliki
    location ~ /\. {
        deny all;
    }
}