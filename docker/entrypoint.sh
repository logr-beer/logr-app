#!/bin/sh
set -e

# Create storage symlink
php artisan storage:link --force 2>/dev/null || true

# Run migrations on startup
php artisan migrate --force

# Seed on first run (creates default admin user)
php artisan db:seed --force 2>/dev/null || true

# Start supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
