#!/bin/sh
set -e

# Pastikan direktori storage, uploads, dan avatars dimiliki oleh www-data
mkdir -p /var/www/storage/uploads /var/www/storage/avatars
chown -R www-data:www-data /var/www/storage
chmod -R 775 /var/www/storage

exec "$@"
