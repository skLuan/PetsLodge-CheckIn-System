#!/bin/bash
set -e

echo "Starting PetsLodge Docker Setup..."

# Wait for MySQL to be ready (simple wait)
echo "Waiting for MySQL to be ready..."
for i in {1..30}; do
    if mysqladmin ping -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; then
        echo "MySQL is ready!"
        break
    fi
    echo "Waiting for MySQL... ($i/30)"
    sleep 2
done

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

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
