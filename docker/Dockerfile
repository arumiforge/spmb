# syntax=docker/dockerfile:1
#
# SPMB images (D-066…D-069). Build from the repository root:
#
#   docker build -f docker/Dockerfile --target app -t spmb-app:<tag> .
#   docker build -f docker/Dockerfile --target web -t spmb-web:<tag> .
#
# On the server `spmb deploy` builds both from `git archive <commit>`, so the image always
# matches one exact commit. Targets:
#   app  PHP-FPM 8.5 with the application, vendor/ (no dev packages) and built assets; runs as www-data
#   web  Nginx with public/ only (static files + build/); passes PHP requests to `app:9000`
# Never put secrets, a proxy or a CA bundle in this file: the app's .env is mounted at run time.

ARG PHP_IMAGE=php:8.5-fpm
ARG NODE_IMAGE=node:24-bookworm-slim
ARG NGINX_IMAGE=nginx:1.28-alpine
ARG COMPOSER_IMAGE=composer:2

# --- Frontend assets (Tailwind scans the PHP views, so they are copied in) -----------------
FROM ${NODE_IMAGE} AS assets
WORKDIR /src
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources/ resources/
COPY app/ app/
RUN npm run build

FROM ${COMPOSER_IMAGE} AS composer

# --- PHP base: extensions and production settings -------------------------------------------
FROM ${PHP_IMAGE} AS php-base
# Build the extensions, then keep only the shared libraries they link to (docker-library pattern).
RUN set -eux; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype-dev; \
    docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype; \
    docker-php-ext-install -j"$(nproc)" intl mysqli gd zip exif pcntl; \
    apt-mark auto '.*' > /dev/null; \
    [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark > /dev/null; \
    find /usr/local -type f -executable -exec ldd '{}' ';' 2>/dev/null \
        | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); print so }' \
        | sort -u | xargs -r dpkg-query --search 2>/dev/null \
        | awk 'sub(":$", "", $1) { print $1 }' | sort -u | xargs -r apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*; \
    for ext in intl mysqli gd zip exif pcntl; do php -m | grep -qix "$ext"; done; \
    php -r 'exit(function_exists("imagewebp") && function_exists("imagejpeg") && function_exists("imagettftext") ? 0 : 1);'
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/spmb.ini "$PHP_INI_DIR/conf.d/90-spmb.ini"
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-spmb.conf
WORKDIR /var/www/html

# --- Composer dependencies (no dev packages) ------------------------------------------------
FROM php-base AS vendor
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-progress --prefer-dist
COPY app/ app/
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && composer check-platform-reqs --no-dev

# --- app: PHP-FPM ---------------------------------------------------------------------------
FROM php-base AS app
# Code is owned by root and read-only for PHP; only writable/ belongs to www-data.
COPY app/ app/
COPY public/ public/
COPY spark ./
COPY clients/ clients/
COPY --from=vendor /var/www/html/vendor/ vendor/
COPY --from=assets /src/public/build/ public/build/
# writable/ holds only the index.html guards (.dockerignore); the named volume mounted here keeps
# logs, cache, sessions and uploads. Docker copies this skeleton into a new, empty volume.
COPY --chown=www-data:www-data writable/ writable/
RUN chown root:root /var/www/html && chmod 0755 /var/www/html
USER www-data
EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD php -r 'exit(@fsockopen("127.0.0.1", 9000) ? 0 : 1);'
CMD ["php-fpm", "--nodaemonize"]

# --- web: Nginx with public/ only -----------------------------------------------------------
FROM ${NGINX_IMAGE} AS web
RUN rm -f /etc/nginx/conf.d/default.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/spmb.conf /etc/nginx/conf.d/spmb.conf
COPY public/ /var/www/html/public/
COPY --from=assets /src/public/build/ /var/www/html/public/build/
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1/healthz || exit 1
