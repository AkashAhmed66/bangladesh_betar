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

# Search (M06): ensure the Elasticsearch indices exist and are populated. Safe
# to run on every boot — it only imports indices that are still empty (first
# boot / after data loss); ongoing sync is handled by the Scout observers and a
# nightly reconcile. Never blocks boot if Elasticsearch is unavailable.
if [ "${RUN_SEARCH_SETUP:-true}" = "true" ] && [ "${SCOUT_DRIVER:-}" = "elastic" ]; then
    # ES starts alongside the app (service_started, not health-gated), so give it
    # a bounded moment to accept connections before the first-boot import. We
    # proceed regardless after the timeout — search has a SQL fallback.
    echo "Waiting for Elasticsearch at ${ELASTIC_HOST} (up to 60s)…"
    i=0
    until curl -fsS "${ELASTIC_HOST}/_cluster/health" >/dev/null 2>&1 || [ "$i" -ge 30 ]; do
        i=$((i + 1)); sleep 2
    done
    echo "Setting up search indices…"
    php artisan search:index --if-empty || echo "Search index setup skipped (Elasticsearch unavailable)."
fi

# Cache framework config/routes/views for production.
if [ "${APP_ENV}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Everything above ran as root, so any files/directories artisan created
# (storage dirs, caches, demo audio, upload folders) are root-owned — which
# would make PHP-FPM (www-data) fail with UnableToCreateDirectory on upload.
# Normalize ownership as the last boot step so the web user can always write.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

exec "$@"
