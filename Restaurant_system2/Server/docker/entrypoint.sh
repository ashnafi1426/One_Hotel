#!/bin/sh
set -e

# Default PORT if not provided by Render
PORT="${PORT:-10000}"
export PORT

echo "==> Configuring Nginx on port: ${PORT}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-available/default
ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

echo "==> Setting directory permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Running Laravel setup..."
php artisan storage:link --force || true

# Optimize cache
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "==> Applying database migrations..."
php artisan migrate --force || echo "Migration skipped or failed, continuing..."

echo "==> Starting Supervisord (Nginx + PHP-FPM + Reverb + Queue Worker)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
