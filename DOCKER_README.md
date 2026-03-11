# Docker Setup for PetsLodge Check-In System

This guide will help you containerize and run the PetsLodge Check-In System using Docker.

## Prerequisites

- [Docker](https://www.docker.com/get-started) (version 20.10+)
- [Docker Compose](https://docs.docker.com/compose/install/) (version 2.0+)

## Quick Start

### 1. Setup Environment Variables

Copy the example environment file:

```bash
cp .env.docker .env
```

Generate a new application key:

```bash
docker-compose run --rm artisan key:generate
```

### 2. Build and Start Containers

Build the Docker images:

```bash
docker-compose build
```

Start all services:

```bash
docker-compose up -d
```

### 3. Verify Services are Running

Check the status of containers:

```bash
docker-compose ps
```

### 4. Run Database Migrations

Wait for MySQL to be ready, then run migrations:

```bash
docker-compose run --rm artisan migrate
```

### 5. Access the Application

Open your browser and navigate to:

```
http://localhost:8080
```

## Services Overview

The Docker setup includes the following services:

| Service | Port | Description |
|---------|------|-------------|
| Nginx | 8080 | Web server |
| PHP-FPM | 9000 | PHP application |
| MySQL | 3306 | Database |
| Redis | 6379 | Cache & Session |

## Common Commands

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f nginx
docker-compose logs -f app
docker-compose logs -f mysql
```

### Stop Services

```bash
docker-compose down
```

### Restart Services

```bash
docker-compose restart
```

### Rebuild Services

```bash
docker-compose up -d --build
```

### Run Laravel Commands

```bash
# Run migrations
docker-compose run --rm artisan migrate

# Seed database
docker-compose run --rm artisan db:seed

# Clear caches
docker-compose run --rm artisan cache:clear
docker-compose run --rm artisan config:clear
docker-compose run --rm artisan route:clear
docker-compose run --rm artisan view:clear

# Create storage link
docker-compose run --rm artisan storage:link
```

### Access Container Shell

```bash
# PHP container
docker-compose exec app sh

# MySQL container
docker-compose exec mysql sh
```

### Database Access

Connect to MySQL from outside Docker:

```bash
docker-compose exec mysql mysql -u petslodge -p petslodge
```

Password: `petslodge_secret`

## Development vs Production

### Development Mode

For development, you may want to:

1. Enable debug mode in `.env`:
   ```
   APP_DEBUG=true
   ```

2. Use volume mounts for real-time code changes:
   The docker-compose.yml already mounts the current directory, so code changes will be reflected immediately.

### Production Mode

For production deployment:

1. Set `APP_DEBUG=false` in `.env`
2. Generate optimized autoloader: `composer install --optimize-autoloader --no-dev`
3. Build assets: `npm run build`
4. Run migrations: `docker-compose run --rm artisan migrate --force`
5. Clear and rebuild caches:
   ```bash
   docker-compose run --rm artisan config:cache
   docker-compose run --rm artisan route:cache
   docker-compose run --rm artisan view:cache
   ```

## Troubleshooting

### Container Won't Start

Check logs for errors:
```bash
docker-compose logs app
```

### Database Connection Issues

Ensure MySQL is fully started:
```bash
docker-compose ps
docker-compose logs mysql
```

### Permission Issues

Fix permissions:
```bash
docker-compose exec app chown -R www-data:www-data /var/www
```

### Port Already in Use

If port 8080 is in use, modify the port in `docker-compose.yml`:
```yaml
nginx:
  ports:
    - "8081:80"  # Change to different port
```

## File Structure

```
.
├── Dockerfile              # PHP-FPM application image
├── Dockerfile.nginx       # Nginx web server image
├── docker-compose.yml     # Service orchestration
├── docker-entrypoint.sh   # Application startup script
├── .env.docker            # Example environment configuration
└── docker/
    ├── php/
    │   ├── local.ini      # PHP configuration
    │   └── php-fpm-healthcheck
    └── nginx/
        ├── nginx.conf    # Nginx main configuration
        └── default.conf  # Site configuration
```

## Stopping and Cleaning Up

To stop all services:

```bash
docker-compose down
```

To remove all data (including database):

```bash
docker-compose down -v
```

To remove all images:

```bash
docker-compose down --rmi all
```
