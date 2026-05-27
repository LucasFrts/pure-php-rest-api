FROM php:8.4-fpm

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
    libonig-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql mbstring \
    && echo "display_errors=Off\nlog_errors=On\nerror_reporting=E_ALL" > /usr/local/etc/php/conf.d/api-errors.ini

WORKDIR /var/www/html

COPY . .

RUN composer install --optimize-autoloader \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data storage

COPY docker/app/entrypoint.sh /usr/local/bin/docker-app-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-app-entrypoint.sh

ENTRYPOINT ["docker-app-entrypoint.sh"]
CMD ["php-fpm"]

EXPOSE 9000
