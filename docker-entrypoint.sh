#!/bin/sh
set -e

# Jalankan optimasi Laravel dan migrasi database sebelum melayani request
echo "Running database migrations..."
php artisan migrate --force

echo "Caching configuration and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Lanjutkan ke perintah utama (CMD)
exec "$@"
