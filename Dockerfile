# syntax=docker/dockerfile:1
# ==========================================================================
# Nutritrace / Farm to Fork — image de l'application monolithique Laravel
# Utilisée par : docker-compose (profil "app"), la CI (test de fumée) et Render.
# ==========================================================================

# ---------- Étape 1 : dépendances PHP (sans outils de dev) ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
        --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------- Étape 2 : image d'exécution PHP 8.3 + Apache ----------
FROM php:8.3-apache

# Extensions nécessaires : MySQL, images (photos, PDF dompdf), zip, intl, opcache
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev libicu-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl bcmath exif opcache \
 && a2enmod rewrite headers \
 && apt-get purge -y --auto-remove \
 && rm -rf /var/lib/apt/lists/*

# Configuration PHP de production + limites d'upload (photos de 2 Mo max côté application)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && { \
      echo 'upload_max_filesize=4M'; \
      echo 'post_max_size=8M'; \
      echo 'memory_limit=256M'; \
      echo 'expose_php=Off'; \
      echo 'opcache.enable=1'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.memory_consumption=128'; \
    } > "$PHP_INI_DIR/conf.d/nutritrace.ini"

# Apache sert le dossier public/ de Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
 && echo 'ServerName localhost' >> /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /app /var/www/html

# Script de démarrage : port dynamique (Render), migrations, caches
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint \
 && chmod +x /usr/local/bin/entrypoint \
 && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public/harvests bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=80

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1:" . getenv("PORT") . "/up") === false ? 1 : 0);'

ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
