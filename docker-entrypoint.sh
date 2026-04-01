#!/bin/sh
set -e

echo "Starting PetsLodge Docker Setup..."

# Wait for MySQL to be ready (simple wait)
echo "Waiting for MySQL to be ready..."
i=0
while [ $i -lt 30 ]; do
    i=$((i + 1))
    if php artisan db:monitor --databases=mysql 2>/dev/null; then
        echo "MySQL is ready!"
        break
    fi
    echo "Waiting for MySQL... ($i/30)"
    sleep 2
done

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Seed database (skip if already seeded)
echo "Seeding database..."
php artisan db:seed --force 2>/dev/null || echo "Seeding skipped (already seeded)."

# Clear and rebuild caches
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Create storage links if needed
if [ ! -L public/storage ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

echo "Docker setup complete!"

# Execute the container command (php artisan serve)
exec "$@"
