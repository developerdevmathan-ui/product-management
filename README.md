# Product Management

Product Management is a Laravel 13 application with Breeze Blade authentication, MariaDB, Redis, Docker, queue processing, scheduler processing, native role-based access control, and a production-oriented product inventory module.

## Features

- Product CRUD, details, search, filtering, sorting, and pagination
- SKU generation in `PRD-000001` format
- Quantity-driven stock status
- Admin and standard user roles
- Admin dashboard and user role management
- Form Request validation
- Service and repository layers
- Rich text sanitization
- Pest feature coverage
- Dockerized PHP-FPM, nginx, MariaDB, Redis, queue, and scheduler

## One-Command Docker Setup

After cloning the repository, run:

```bash
docker compose up -d --build
```

The Docker setup automatically:

- Creates `.env` from `.env.example` when missing
- Generates `APP_KEY` for local development when missing
- Installs Composer dependencies when the `vendor` volume is empty
- Installs Node dependencies when the `node_modules` volume is empty
- Builds frontend assets
- Waits for MariaDB readiness
- Runs `php artisan migrate --force`
- Skips database seeders unless explicitly enabled
- Runs `php artisan storage:link --force`
- Starts PHP-FPM, nginx, queue worker, and scheduler

Open:

```text
http://localhost:8080
```

Default local seeded accounts:

```text
Admin: admin@example.com / password
User:  user@example.com / password
```

Seeders are opt-in during Docker setup. To run them, choose yes by setting both variables before starting Compose:

```powershell
$env:RUN_SEEDERS="true"
$env:CONFIRM_RUN_SEEDERS="yes"
docker compose up -d --build
```

Or place this in `.env`:

```env
RUN_SEEDERS=true
CONFIRM_RUN_SEEDERS=yes
```

To skip seeders, leave the defaults:

```env
RUN_SEEDERS=false
CONFIRM_RUN_SEEDERS=no
```

CKEditor uses GPL mode by default:

```env
VITE_CKEDITOR_LICENSE_KEY=GPL
```

The CKEditor powered-by label is part of GPL mode. To remove it, provide a valid CKEditor commercial license key in `.env`, then rebuild the frontend assets.

## Docker Services

- `setup`: one-shot application bootstrap
- `app`: PHP-FPM
- `nginx`: HTTP ingress
- `mariadb`: database
- `redis`: cache/session/queue backend
- `queue`: Redis queue worker
- `scheduler`: Laravel scheduler

Useful commands:

```bash
docker compose ps
docker compose logs -f app
docker compose logs -f setup
docker compose logs -f queue
docker compose logs -f scheduler
docker compose down
```

To rebuild from scratch, including named volumes:

```bash
docker compose down -v
docker compose up -d --build
```

## Production Configuration

Production must provide real secrets and secure settings through environment variables or a secret manager.

Required production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-production-key
APP_URL=https://your-domain.example
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
DOCKER_DB_PASSWORD=strong-random-password
DB_ROOT_PASSWORD=strong-random-root-password
RUN_SEEDERS=false
RUN_OPTIMIZE=true
```

The Docker setup fails fast in production when:

- `APP_DEBUG=true`
- `APP_KEY` is missing
- `APP_URL` is not HTTPS
- `SESSION_SECURE_COOKIE=true` is missing
- default database credentials are used

## Local Non-Docker Development

Docker is the recommended setup path. For local host-based development:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

## Testing

Run tests locally:

```bash
php artisan test
```

Run tests in Docker:

```bash
docker compose exec app php artisan test
```

Run formatting:

```bash
vendor/bin/pint --test
```

Run security audits:

```bash
composer audit --locked
npm audit
```

## CI/CD

GitHub Actions runs:

- Composer install
- npm install
- Vite build
- Pint
- raw SQL scan
- raw Blade output scan
- Composer audit
- npm audit
- Laravel tests with MariaDB and Redis services
- Docker Compose config validation
- Docker image build

Workflow file:

```text
.github/workflows/ci.yml
```

## Security Notes

- Keep `.env` files out of Git.
- Rotate any secret that has ever been shared or committed.
- Use HTTPS in production.
- Keep `APP_DEBUG=false` outside local development.
- Keep `SESSION_ENCRYPT=true`.
- Keep `SESSION_SECURE_COOKIE=true` for HTTPS deployments.
- Do not enable production seeders unless they are explicitly safe and reviewed.
