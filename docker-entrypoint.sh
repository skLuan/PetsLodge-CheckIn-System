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

# 2. Run migrations (idempotent — Laravel tracks progress in the `migrations` table).
echo "Running database migrations..."
php artisan migrate --force

# 3. Seed ONLY on a fresh database (empty `users` table) to avoid duplicate-key
#    errors when the persistent volume already holds data.
USERS_COUNT=$(php -r "try { \$pdo = new PDO('${DB_CONNECTION}:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(); } catch (\Throwable \$e) { echo '0'; }")
if [ "${USERS_COUNT:-0}" -eq 0 ]; then
    echo "Fresh database detected — seeding..."
    php artisan db:seed --force
else
    echo "Database already seeded (users: ${USERS_COUNT}) — skipping seed."
fi

# 4. Ensure storage framework dirs exist. They are excluded by .dockerignore
#    (and empty dirs aren't copied into the image), which otherwise makes
#    `php artisan view:clear` fail with "View path not found".
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views

# 5. Clear caches (do NOT config:cache — compose injects env vars at runtime,
#    so we want Laravel to read them live).
echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 6. Ensure the public/storage symlink exists (PdfService serves printed PDFs from it).
if [ ! -L public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link
fi

echo "Docker setup complete!"

# Hand off to the container command (e.g. `php artisan serve`).
exec "$@"
