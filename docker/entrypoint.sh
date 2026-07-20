#!/bin/sh
set -e

cd /var/www/html

# Ensure an app key exists.
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    [ -f .env ] || cp .env.example .env
    php artisan key:generate --force
fi

# Wait for the database to accept connections.
echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
until php -r "exit(@fsockopen(getenv('DB_HOST'), (int)getenv('DB_PORT')) ? 0 : 1);" 2>/dev/null; do
    sleep 2
done
echo "Database is up."

# Storage symlink for public media.
php artisan storage:link 2>/dev/null || true

if [ "${RUN_MIGRATIONS}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_SEEDERS}" = "true" ]; then
    # Seeders are idempotent (updateOrCreate / firstOrCreate) so this is safe.
    php artisan db:seed --force || echo "Seeding skipped or already applied."
fi

# Synthesize demo streaming media so seeded assets (whose real files are not
# present in a fresh container) remain playable in the Studio and public API.
php artisan demo:audio 2>/dev/null || echo "Demo audio generation skipped."

# Cache framework config/routes/views for production.
if [ "${APP_ENV}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
