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

# ---- Database bootstrap: first-time only ----
# Migrations + seeders run ONCE, when the database is empty. On every later boot
# we do NOT re-seed (so restarts / `docker compose up --build` never wipe or
# duplicate your data) and we only apply migrations that are genuinely new.
#   - `migrate:status` fails when the migrations table is absent => fresh DB.
#   - Set FORCE_SEED=true to deliberately re-run the seeders.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    if php artisan migrate:status >/dev/null 2>&1; then
        # Database was already initialized on a previous boot. Always apply any
        # pending migrations: `migrate` is idempotent (it only runs migrations
        # that have not run yet), so every deploy reliably picks up new
        # migrations — no fragile "is anything pending?" text-parsing check.
        echo "Applying pending migrations (if any)…"
        php artisan migrate --force

        if [ "${FORCE_SEED:-false}" = "true" ]; then
            echo "FORCE_SEED=true — re-seeding on request…"
            php artisan db:seed --force || echo "Seeding failed."
        else
            echo "Skipping seeders (already seeded on first boot)."
        fi
    else
        # Fresh database → one-time full setup.
        echo "Fresh database detected — running first-time migrations + seeders…"
        php artisan migrate --force
        if [ "${RUN_SEEDERS:-true}" = "true" ]; then
            php artisan db:seed --force || echo "Seeding failed."
        fi
    fi
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
