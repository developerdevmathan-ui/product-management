# Deployment Automation Audit

Date: 2026-06-22  
Scope: `README.md`, `docker-compose.yml`, `docker/php/Dockerfile`, `docker/php/entrypoint.sh`, `scripts/deploy.sh`, Docker-related configuration

## Assignment Requirement

The project must support a fully automated fresh-machine setup:

```bash
git clone repository
docker compose up -d --build
```

The setup must not require manually running:

- `composer install`
- `npm install`
- `cp .env.example .env`
- `php artisan key:generate`
- `php artisan migrate`
- `php artisan db:seed`
- `php artisan storage:link`

## Executive Finding

The project is **partially automated**, but it does **not fully satisfy** the assignment requirement.

Docker Compose can build and start the infrastructure services, and the PHP image installs Composer dependencies during image build. However, a fresh clone followed only by `docker compose up -d --build` does not complete Laravel application initialization. The app key, database migrations, seed data, storage symlink, and frontend production assets are not fully automated by Compose or the container entrypoint.

## Assignment Compliance Score

**Deployment Automation Compliance: 45%**

| Requirement | Status | Evidence |
| --- | --- | --- |
| Docker Compose exists | Pass | `docker-compose.yml` defines app, nginx, MariaDB, Redis, queue, scheduler |
| PHP dependencies automated | Partial | `docker/php/Dockerfile` runs `composer install --no-dev --no-scripts` |
| Node dependencies automated | Fail | No `npm install`, `npm ci`, or Node build stage in Dockerfile/Compose |
| Frontend build automated | Fail | `public/build` is gitignored and no `npm run build` runs in Docker build |
| `.env` creation automated by Compose | Fail | `scripts/deploy.sh` copies `.env`, but `docker compose up -d --build` does not |
| `APP_KEY` generated automatically | Fail | Only `scripts/deploy.sh` runs `php artisan key:generate` |
| Database migrations automated | Fail | Only README/manual script runs `php artisan migrate`; Compose/entrypoint do not |
| Database seeding automated | Fail | No Docker startup path runs `php artisan db:seed` |
| Storage link automated | Fail | Only `scripts/deploy.sh` runs `php artisan storage:link` |
| Queue/scheduler services included | Pass | `queue` and `scheduler` services exist |
| Production-ready startup path | Partial | Docker services exist, but initialization is not idempotently automated |

## Current Problems

### 1. README Still Requires Manual Setup

**File:** `README.md`  
**Severity:** High

The README documents manual local setup:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

It also documents Docker startup separately and then tells the user to manually run:

```bash
docker compose exec app php artisan migrate
```

This does not meet the assignment requirement because the documented path still depends on manual commands after cloning.

### 2. Docker Entrypoint Does Not Initialize Laravel

**File:** `docker/php/entrypoint.sh`  
**Severity:** High

The entrypoint only creates writable directories and fixes permissions:

```sh
mkdir -p storage/app/public storage/framework/cache/data ...
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
exec "$@"
```

It does not:

- Create `.env`
- Generate `APP_KEY`
- Run migrations
- Run seeders
- Create `public/storage`
- Cache config/routes/views
- Build frontend assets

As a result, `docker compose up -d --build` starts containers but does not complete application setup.

### 3. Docker Compose Does Not Run A Setup Job

**File:** `docker-compose.yml`  
**Severity:** High

Compose defines app, nginx, database, Redis, queue, and scheduler services. It does not define an initialization service or startup command that runs Laravel setup tasks.

The app service command defaults to:

```yaml
CMD ["php-fpm"]
```

The queue and scheduler services start application workers, but there is no guarantee that migrations or seeders have run first.

### 4. Missing Application Key On Fresh Compose Startup

**Files:** `.env.example`, `docker-compose.yml`, `docker/php/entrypoint.sh`  
**Severity:** Critical

`.env.example` contains:

```env
APP_KEY=
```

`docker-compose.yml` does not provide `APP_KEY`, and the entrypoint does not generate one. Laravel web requests that need encrypted cookies/sessions can fail when no app key exists.

`scripts/deploy.sh` handles this, but the assignment requires `docker compose up -d --build` without manually running a deployment script.

### 5. Database Migrations Are Not Automated

**Files:** `docker-compose.yml`, `docker/php/entrypoint.sh`, `scripts/deploy.sh`  
**Severity:** Critical

`scripts/deploy.sh` runs:

```bash
docker compose exec app php artisan migrate --force
```

But `docker compose up -d --build` does not run migrations. On a fresh database, authenticated pages and product/admin modules will not have the required tables.

### 6. Database Seeders Are Not Automated

**Files:** `scripts/deploy.sh`, `database/seeders/DatabaseSeeder.php`  
**Severity:** Medium

The seeder creates local/testing admin and standard users only:

```php
if (! app()->environment(['local', 'testing'])) {
    return;
}
```

No Docker startup path runs:

```bash
php artisan db:seed
```

This means a fresh setup does not automatically create usable local admin credentials unless the user manually runs the seeder.

### 7. Frontend Assets Are Not Built In Docker

**Files:** `docker/php/Dockerfile`, `package.json`, `.gitignore`  
**Severity:** High

The project uses Vite:

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

But:

- `Dockerfile` does not install Node dependencies.
- `Dockerfile` does not run `npm run build`.
- `.gitignore` excludes `/public/build`.
- `git ls-files public/build` shows no tracked build artifacts.

On a fresh clone, the production Vite manifest will be missing unless assets are built manually.

### 8. Storage Symlink Is Not Created By Compose

**Files:** `docker/php/entrypoint.sh`, `scripts/deploy.sh`, `.gitignore`  
**Severity:** Medium

`scripts/deploy.sh` runs:

```bash
docker compose exec app php artisan storage:link
```

The entrypoint does not. `public/storage` is ignored by Git, so a fresh clone will not have the public storage symlink.

### 9. Deployment Script Exists But Is Not The Required Flow

**File:** `scripts/deploy.sh`  
**Severity:** Medium

The script automates several required steps:

- Copies `.env`
- Starts selected services
- Runs Composer install
- Generates app key
- Creates storage/cache directories
- Runs migrations
- Runs storage link
- Caches config/routes/views
- Starts queue and scheduler

However, it still does not run:

- `npm install` or `npm ci`
- `npm run build`
- `php artisan db:seed`

It also requires the user to manually run:

```bash
bash scripts/deploy.sh
```

That is outside the assignment's required one-command Compose flow.

### 10. Dockerfile Uses Composer Without Scripts

**File:** `docker/php/Dockerfile`  
**Severity:** Medium

The image build runs:

```dockerfile
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader
```

Using `--no-scripts` skips Laravel package discovery during build. This can be acceptable in a carefully controlled production build, but then the image should explicitly run required Laravel optimization/discovery commands later. The current Compose startup does not do that.

## Missing Automation

The current project does not automate the following through `docker compose up -d --build`:

1. `.env` creation or environment provisioning for all required Laravel values.
2. `APP_KEY` generation or injection.
3. Database migrations.
4. Database seeding.
5. Storage symlink creation.
6. Frontend dependency installation.
7. Frontend asset build.
8. One-time setup coordination before app, queue, and scheduler become active.
9. Production-safe cache warmup.
10. A documented fresh-machine one-command workflow.

## Manual Steps Remaining

Based on the actual current configuration, a fresh machine still requires one or more of these manual actions:

```bash
cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed
docker compose exec app php artisan storage:link
npm install
npm run build
```

Alternatively, the user can run:

```bash
bash scripts/deploy.sh
```

But that script still does not build frontend assets or seed the database, and it is not the assignment's required `docker compose up -d --build` workflow.

## What Currently Works

The project does have a useful Docker foundation:

- PHP 8.3 FPM image exists.
- Composer dependencies are installed during image build.
- nginx reverse proxy is configured.
- MariaDB and Redis services are configured with health checks.
- Queue and scheduler containers are present.
- Named volumes are used for MariaDB, Redis, vendor, storage, and bootstrap cache.
- `scripts/deploy.sh` provides a partial deployment workflow.
- `/up` health route is configured through Laravel bootstrap.

## Recommended Production Setup

### 1. Add A Dedicated Setup Service

Add a one-time `setup` service in `docker-compose.yml` that depends on healthy MariaDB and Redis, then runs an idempotent setup script:

```yaml
setup:
  build:
    context: .
    dockerfile: docker/php/Dockerfile
  command: /usr/local/bin/app-setup
  environment:
    APP_ENV: ${APP_ENV:-production}
    APP_KEY: ${APP_KEY}
    DB_CONNECTION: mariadb
    DB_HOST: mariadb
    DB_PORT: 3306
    DB_DATABASE: ${DOCKER_DB_DATABASE:-product_management}
    DB_USERNAME: ${DOCKER_DB_USERNAME:-product_management}
    DB_PASSWORD: ${DOCKER_DB_PASSWORD}
  depends_on:
    mariadb:
      condition: service_healthy
    redis:
      condition: service_healthy
```

Then make `app`, `queue`, and `scheduler` depend on successful setup completion.

### 2. Add An Idempotent Setup Script

Create a script such as `docker/php/setup.sh`:

```sh
#!/usr/bin/env sh
set -eu

php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force
fi
```

For production, seeders should only run behind an explicit flag.

### 3. Require APP_KEY In Production, Generate Only For Local

Production should not generate secrets implicitly. Use:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
```

For local Docker convenience, a dev-only entrypoint may generate `APP_KEY` if missing.

### 4. Build Frontend Assets Inside The Image

Use a multi-stage Dockerfile:

```dockerfile
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.3-fpm AS app
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
COPY . .
COPY --from=assets /app/public/build public/build
RUN php artisan package:discover --ansi
```

This removes the need for manual `npm install` and `npm run build`.

### 5. Avoid Bind Mounts In Production

The current Compose file mounts the source tree:

```yaml
volumes:
  - .:/var/www/html
```

That is useful for local development but not production. Production should run immutable images and mount only persistent storage where required.

Recommended split:

- `docker-compose.yml` for production-like immutable containers.
- `docker-compose.override.yml` for local bind mounts.
- Optional `docker-compose.dev.yml` for Vite hot reload.

### 6. Improve Health Checks

Current app health uses:

```yaml
php artisan about --only=environment
```

That confirms Laravel can execute, but it does not prove the web app is initialized. Add checks for:

- Valid `APP_KEY`
- Database connectivity
- Migration completion
- Cache directory writability

### 7. Update README To Match The Required Flow

The README should present the fresh-machine setup as:

```bash
git clone <repository>
cd product-management
docker compose up -d --build
```

Any optional commands should be clearly labeled as optional, not required for first boot.

## Final Assessment

The project currently has good Docker building blocks, but it is not yet a fully automated deployment setup under the assignment definition. The main blocker is that `docker compose up -d --build` starts containers without performing Laravel initialization tasks.

To become compliant, setup must be moved into Docker build/startup orchestration, preferably through a dedicated idempotent setup service and a production-grade multi-stage image that includes Composer dependencies and built Vite assets.
