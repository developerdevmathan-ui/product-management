# Product Management

## Project Overview

Product Management is a Laravel 13 application using Laravel Breeze Blade authentication, MariaDB, Redis, Docker, queue processing, scheduler processing, and native role-based access control.

The application includes:

- Public welcome page
- Authenticated user dashboard
- Admin dashboard
- User profile management
- Admin user management
- Role management for Admin and Standard User accounts

Admin-only routes:

```text
/admin/dashboard
/admin/users
```

## Requirements

- PHP 8.3
- Laravel 13
- Composer
- Node.js and npm
- MariaDB
- Redis
- Docker

## Local Installation

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure MariaDB in `.env`:

```text
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=product_management
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations:

```bash
php artisan migrate
```

Start the local application:

```bash
php artisan serve
```

Start Vite:

```bash
npm run dev
```

## Authentication

Authentication is implemented with Laravel Breeze Blade.

Included authentication features:

- Login
- Logout
- Registration
- Password Reset
- Email Verification
- Remember Me
- Profile Management
- Password Update
- Account Deletion
- Roles

Roles:

- Admin
- Standard User

Authorization is implemented with:

- `users.role`
- `App\Enums\UserRole`
- `role:admin` middleware
- `UserPolicy`
- Blade `@can` checks

Security controls:

- CSRF protection
- Session regeneration after login
- Session invalidation after logout
- Login rate limiting
- Signed email verification URLs
- Strong password defaults
- Admin routes protected by `auth`, `verified`, and `role:admin`

## Docker Setup

Docker Compose includes:

- PHP 8.3 FPM app container
- nginx
- MariaDB
- Redis
- queue worker
- scheduler

Create the environment file:

```bash
cp .env.example .env
```

Optional Docker-specific values:

```text
DOCKER_APP_URL=http://localhost:8080
APP_PORT=8080
APP_BIND_HOST=127.0.0.1
DOCKER_DB_DATABASE=product_management
DOCKER_DB_USERNAME=product_management
DOCKER_DB_PASSWORD=secret
DB_ROOT_PASSWORD=root_secret
DB_BIND_HOST=127.0.0.1
DB_FORWARD_PORT=3307
DOCKER_REDIS_PASSWORD=null
REDIS_BIND_HOST=127.0.0.1
REDIS_FORWARD_PORT=6379
```

Start Docker:

```bash
docker compose up -d
```

Build and start Docker:

```bash
docker compose up -d --build
```

Run migrations inside Docker:

```bash
docker compose exec app php artisan migrate
```

Run the deployment automation script:

```bash
bash scripts/deploy.sh
```

Open the Docker app:

```text
http://localhost:8080
```

## Running Queue

Run the queue worker locally:

```bash
php artisan queue:work
```

Run the queue worker in Docker:

```bash
docker compose exec app php artisan queue:work
```

Docker also includes a dedicated queue container:

```bash
docker compose logs -f queue
```

## Running Scheduler

Run the scheduler locally:

```bash
php artisan schedule:work
```

Run the scheduler in Docker:

```bash
docker compose exec app php artisan schedule:work
```

Docker also includes a dedicated scheduler container:

```bash
docker compose logs -f scheduler
```

## Testing

The test suite uses a dedicated MariaDB testing database:

```text
product_management_testing
```

Create the testing database:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS product_management_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Run PHPUnit/Laravel tests:

```bash
php artisan test
```

## Pest Testing

Run all Pest tests:

```bash
./vendor/bin/pest
```

Run the focused authentication, authorization, and user-access Pest suite:

```bash
composer pest:auth
```

Run Pest in Docker:

```bash
docker compose exec app ./vendor/bin/pest
```

The tests use `RefreshDatabase`, so never point `phpunit.xml` at a development or production database.

## Admin User Creation

The local seeder creates default local/testing accounts only.

Run:

```bash
php artisan db:seed
```

Default local admin:

```text
Email: admin@example.com
Password: password
Role: admin
```

Default local standard user:

```text
Email: user@example.com
Password: password
Role: user
```

Create an admin user manually with Tinker:

```bash
php artisan tinker
```

```php
use App\Enums\UserRole;
use App\Models\User;

User::updateOrCreate(
    ['email' => 'admin@example.com'],
    [
        'name' => 'Admin User',
        'password' => 'ChangeMe123!',
        'email_verified_at' => now(),
        'role' => UserRole::Admin,
    ],
);
```

Create an admin user in Docker:

```bash
docker compose exec app php artisan tinker
```

Then run the same Tinker command above.
