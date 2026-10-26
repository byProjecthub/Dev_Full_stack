FROM php:8.3-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j2 pdo_mysql mysqli
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Set working directory
WORKDIR /app

# Install the locked dependencies; the checked-in vendor tree is excluded.
COPY composer.json composer.lock ./
ARG COMPOSER_PREFER_INSTALL=dist
# Cloud environments can supply their trusted proxy CA without baking it in.
RUN --mount=type=secret,id=cloud_ca \
    if [ -f /run/secrets/cloud_ca ]; then export COMPOSER_CAFILE=/run/secrets/cloud_ca GIT_SSL_CAINFO=/run/secrets/cloud_ca; fi; \
    composer install --no-dev --prefer-install="$COMPOSER_PREFER_INSTALL" --no-interaction --no-progress

COPY . /app/
RUN mkdir -p logs cache uploads

# Local development port; PORT can override the server's listening port.
EXPOSE 8080

# Start PHP built-in server
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t . router.php"]
