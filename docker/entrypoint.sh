#!/bin/sh
set -e

# Set up persistent data volume
# /data is a single mounted volume for all persistent state
mkdir -p /data/database /data/storage
chown -R www-data:www-data /data

# Symlink persistent files into the app
# Only symlink the SQLite file (not the whole database/ dir, which contains migrations/seeders)
if [ ! -f /data/database/database.sqlite ]; then
    touch /data/database/database.sqlite
    chown www-data:www-data /data/database/database.sqlite
fi
ln -sf /data/database/database.sqlite /var/www/html/database/database.sqlite

# Symlink storage/app to persistent volume
rm -rf /var/www/html/storage/app
ln -sf /data/storage /var/www/html/storage/app

# Set Laravel defaults for Docker (not user-configurable)
export APP_NAME=Logr
export APP_ENV=production
export APP_DEBUG=false
export APP_URL=${APP_URL:-http://localhost:8337}
export DB_CONNECTION=sqlite
export DB_DATABASE=/var/www/html/database/database.sqlite
export QUEUE_CONNECTION=database
export CACHE_STORE=database
export SESSION_DRIVER=database

# Generate APP_KEY on first run, persist to data volume
if [ ! -f /data/.app_key ]; then
    echo "base64:$(openssl rand -base64 32)" > /data/.app_key
    echo "Generated new APP_KEY"
fi
export APP_KEY=$(cat /data/.app_key)

# Build .env from environment variables so Laravel (php-fpm) can read them.
env | grep -E '^(APP_|DB_|QUEUE_|CACHE_|SESSION_)' | grep -v '=$' > .env 2>/dev/null || true

# Create storage symlink
php artisan storage:link --force 2>/dev/null || true

# Run migrations on startup
php artisan migrate --force

# Seed on first run (creates default venue)
php artisan db:seed --force 2>/dev/null || true

# Start supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
