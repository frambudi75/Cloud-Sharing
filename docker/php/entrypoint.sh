#!/bin/sh
set -e

# Pastikan direktori storage dan uploads dimiliki oleh www-data
mkdir -p /var/www/storage/uploads
chown -R www-data:www-data /var/www/storage
chmod -R 775 /var/www/storage

exec "$@"
