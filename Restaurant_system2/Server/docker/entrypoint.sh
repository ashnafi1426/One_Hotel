#!/bin/sh
set -e

# Default PORT if not provided by Render
PORT="${PORT:-10000}"
export PORT

# Force array cache on Render — avoids CACHE_STORE=database 500 errors when cache table missing
export CACHE_STORE=array

# Force correct Gmail SMTP settings — use smtps (port 465 SSL) which is valid for Symfony Mailer
# Symfony Mailer only accepts 'smtp' (port 587 STARTTLS) or 'smtps' (port 465 SSL)
export MAIL_SCHEME=smtps
export MAIL_PORT=465

echo "==> Configuring Nginx on port: ${PORT}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-available/default
ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

echo "==> Setting directory permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Running Laravel setup..."
php artisan storage:link --force || true

# Clear ALL caches before migration (ensure fresh state)
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "==> Applying database migrations..."
php artisan migrate --force 2>&1
MIGRATE_EXIT=$?
if [ $MIGRATE_EXIT -ne 0 ]; then
    echo "WARNING: Some migrations failed (exit code $MIGRATE_EXIT). Continuing..."
fi

# Re-cache config and views only
# NOTE: route:cache is INTENTIONALLY skipped — closure routes cannot be serialized
php artisan config:cache || true
php artisan view:cache || true

echo "==> Starting Supervisord (Nginx + PHP-FPM + Reverb + Queue Worker)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
