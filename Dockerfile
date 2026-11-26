FROM php:8.4-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libonig-dev libzip-dev libcurl4-openssl-dev postgresql-client ca-certificates unzip \
    && docker-php-ext-install pdo_pgsql pgsql mbstring zip curl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
ARG COMPOSER_PREFER_INSTALL=dist
RUN --mount=type=secret,id=cloud_ca \
    if [ -f /run/secrets/cloud_ca ]; then export COMPOSER_CAFILE=/run/secrets/cloud_ca GIT_SSL_CAINFO=/run/secrets/cloud_ca; fi; \
    composer install --no-dev --prefer-install="$COMPOSER_PREFER_INSTALL" --no-interaction --no-progress
COPY . /var/www/html/
COPY deploy/apache.conf /etc/apache2/conf-available/vueports.conf
COPY deploy/php.ini /usr/local/etc/php/conf.d/vueports.ini
RUN a2enconf vueports \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && mkdir -p /tmp/vueports/cache /tmp/vueports/logs /tmp/vueports/uploads /var/www/html/uploads \
    && chown -R www-data:www-data /tmp/vueports /var/www/html/uploads
ENV APP_ENV=production SESSION_DRIVER=database VUEPORTS_RUNTIME_DIR=/tmp/vueports
EXPOSE 80
HEALTHCHECK --interval=5s --timeout=5s --start-period=15s --retries=12 \
    CMD php -r '$h=json_decode(file_get_contents("http://127.0.0.1/monitoring/health-check.php"),true); exit(($h["status"]??"")==="healthy"?0:1);'
CMD ["apache2-foreground"]
