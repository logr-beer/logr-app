#!/bin/sh
set -e

# Auto-generate APP_KEY on first boot if not set
if [ -z "$APP_KEY" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "No APP_KEY set, generating one..."
    php artisan key:generate --force
fi

# Create storage symlink
php artisan storage:link --force 2>/dev/null || true

# Run migrations on startup
php artisan migrate --force

# Seed on first run (creates default venue)
php artisan db:seed --force 2>/dev/null || true

# Start supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
