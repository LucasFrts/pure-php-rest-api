#!/bin/sh
set -e

# Bind mounts from the host override image permissions; ensure php-fpm (www-data)
# can always write logs regardless of the host UID/GID.
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage
chmod -R ug+rwX /var/www/html/storage

exec docker-php-entrypoint "$@"
