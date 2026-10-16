# syntax=docker/dockerfile:1

# ---------- Base : FrankenPHP (Caddy + PHP 8.3) ----------
FROM dunglas/frankenphp:1-php8.3-alpine AS base

RUN install-php-extensions intl

WORKDIR /app

# ---------- Dépendances Composer (sans dev) ----------
FROM base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative

# ---------- Image finale ----------
FROM base AS final

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    SERVER_NAME=:8080 \
    TZ=Europe/Paris

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

COPY --from=vendor /app /app

# Cache Symfony préchauffé à la construction (le secret n'est qu'un placeholder de build)
RUN APP_SECRET=build-only php bin/console cache:warmup --env=prod \
    && mkdir -p /var/lib/crip \
    && setcap -r /usr/local/bin/frankenphp \
    && chown -R www-data:www-data /app/var /var/lib/crip /config/caddy /data/caddy

USER www-data
EXPOSE 8080
VOLUME /var/lib/crip

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD wget -q --spider http://127.0.0.1:8080/merci || exit 1

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
