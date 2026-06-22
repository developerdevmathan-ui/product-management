FROM node:22-bookworm-slim AS node

FROM php:8.3-fpm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libzip-dev \
        netcat-openbsd \
        supervisor \
        unzip \
        zip \
    && docker-php-ext-install \
        bcmath \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY docker/supervisor/supervisor.conf /etc/supervisor/conf.d/supervisor.conf
COPY docker/php/entrypoint.sh /usr/local/bin/docker-entrypoint
COPY docker/php/setup.sh /usr/local/bin/docker-setup

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload \
    --no-dev \
    --no-interaction \
    --optimize

RUN npm run build

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod +x /usr/local/bin/docker-entrypoint /usr/local/bin/docker-setup

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
