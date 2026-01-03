#!/bin/sh
set -e

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Ensure storage directories exist
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Ensure SQLite database exists
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    chown www-data:www-data database/database.sqlite
fi

# Run migrations
php artisan migrate --force

# Cache config for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=========================================="
echo "  Homey Dashboard is starting..."
echo "  Access at: http://localhost:${PORT:-8080}"
echo "=========================================="

exec "$@"
