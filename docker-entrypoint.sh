#!/bin/sh
set -e

echo "Starting PetsLodge Docker Setup..."

# Defaults (docker-compose injects the real DB_* values at runtime)
: "${DB_CONNECTION:=mysql}"
: "${DB_HOST:=mysql}"
: "${DB_PORT:=3306}"
: "${DB_DATABASE:=petslodge}"
: "${DB_USERNAME:=petslodge}"
: "${DB_PASSWORD:=petslodge_secret}"

# Exactly ONE container may own the shared state: migrations, seeding, cache
# clearing and the storage symlink. The app and queue containers run this same
# script over the same bind mount, so every unguarded step below is either a
# race or a destructive act on data the other container is actively using.
#
# SKIP_MIGRATIONS is the previous name for this switch; accept it so an older
# docker-compose.yml still behaves correctly.
SKIP_SHARED_SETUP="${SKIP_SHARED_SETUP:-${SKIP_MIGRATIONS:-false}}"

# 1. Wait for MySQL using a reliable PDO probe (pdo_mysql ships in the image,
#    so no mysql-client is needed).
echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."
i=0
MAX_ATTEMPTS=60
until php -r "new PDO('${DB_CONNECTION}:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -ge "$MAX_ATTEMPTS" ]; then
        echo "ERROR: MySQL not reachable at ${DB_HOST}:${DB_PORT} after ${MAX_ATTEMPTS} attempts." >&2
        exit 1
    fi
    echo "  Waiting for MySQL... (${i}/${MAX_ATTEMPTS})"
    sleep 2
done
echo "MySQL is ready!"

# 2. Ensure storage framework dirs exist. They are excluded by .dockerignore
#    (and empty dirs aren't copied into the image), which otherwise makes
#    `php artisan view:clear` fail with "View path not found".
#
#    MUST stay ahead of the cache-clearing step below, and stays UNGUARDED:
#    `mkdir -p` is idempotent and safe for both containers to race, and the
#    queue worker needs these directories for its own cache/session writes.
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views

if [ "${SKIP_SHARED_SETUP}" = "true" ]; then
    # Secondary container (the queue worker). Everything below belongs to the
    # app container; touching any of it from here breaks the app container.
    echo "SKIP_SHARED_SETUP=true — leaving migrations, seeding, cache clearing"
    echo "and the storage symlink to the app container."
else
    # 3. Run migrations (idempotent — Laravel tracks progress in `migrations`).
    echo "Running database migrations..."
    php artisan migrate --force

    # 4. Seed ONLY on a fresh database (empty `users` table) to avoid
    #    duplicate-key errors when the persistent volume already holds data.
    USERS_COUNT=$(php -r "try { \$pdo = new PDO('${DB_CONNECTION}:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(); } catch (\Throwable \$e) { echo '0'; }")
    if [ "${USERS_COUNT:-0}" -eq 0 ]; then
        echo "Fresh database detected — seeding..."
        php artisan db:seed --force
    else
        echo "Database already seeded (users: ${USERS_COUNT}) — skipping seed."
    fi

    # 5. Clear caches (do NOT config:cache — compose injects env vars at
    #    runtime, so we want Laravel to read them live).
    #
    #    GUARDED ON PURPOSE. `view:clear` empties storage/framework/views, which
    #    lives on the shared bind mount. Run from the queue container it wipes
    #    the compiled Blade views the app container is serving out of, and every
    #    page then has to recompile across the slow mount. `--max-time=3600`
    #    makes the worker exit hourly and `restart: unless-stopped` brings it
    #    straight back, so unguarded this fires again every single hour.
    echo "Clearing caches..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    # 6. Ensure the public/storage symlink exists AND points somewhere real.
    #    `-L` only asks "is this a symlink?" — a DANGLING symlink passes that
    #    test, so the old guard happily left a link pointing at a path from a
    #    previous mount layout (/mnt/host/...) in place. Every drop-in print
    #    then died with "Unable to create a directory at
    #    /var/www/public/storage". `-e` follows the link, so it is false for a
    #    dangling one; -d confirms we landed on a directory. Remove first —
    #    storage:link cannot overwrite a path that already exists as a link.
    #
    #    Also guarded: two containers racing here means the loser gets a
    #    non-zero exit from storage:link, and `set -e` kills the container.
    if [ ! -e public/storage ] || [ ! -d public/storage ]; then
        echo "Creating storage symlink (missing or dangling)..."
        rm -f public/storage
        php artisan storage:link
    fi
fi

echo "Docker setup complete!"

# Hand off to the container command (e.g. `php artisan serve`).
exec "$@"
