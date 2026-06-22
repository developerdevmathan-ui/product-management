# Docker Production Review

Date: 2026-06-22  
Scope: `docker-compose.yml`, `docker/php/Dockerfile`, `docker/php/entrypoint.sh`, `docker/nginx/default.conf`, `scripts/deploy.sh`

## Executive Finding

`docker compose up -d --build` is **not currently sufficient** to fully start the application in a production-ready way.

The implementation is moving toward one-command automation, but the current Dockerfile fails during image build at the frontend dependency step:

```text
RUN npm ci
Error: Cannot find module '../lib/cli.js'
Require stack:
- /usr/local/bin/npm
```

Because the image does not build successfully, the full stack cannot reliably start from a fresh clone with only:

```bash
docker compose up -d --build
```

## Production Readiness Score

**Production Readiness Score: 58%**

| Area | Score | Assessment |
| --- | ---: | --- |
| Container startup sequence | 75% | app waits for DB and queue/scheduler depend on app health, but setup is embedded in app startup. |
| Database wait strategy | 80% | PDO retry loop is reasonable and configurable. |
| Environment handling | 55% | `.env` auto-copy helps local setup, but production secrets should be injected, not generated. |
| APP_KEY generation | 60% | Automated for local convenience, risky for production if secrets are not externally managed. |
| Composer automation | 75% | Image build installs Composer deps and entrypoint can recover missing `vendor`. |
| Frontend build process | 20% | Current build fails due broken Node/npm copy. |
| Migration automation | 70% | Automated, but app startup should not be the long-term production migration runner. |
| Seeder automation | 45% | Seeders run by default; unsafe for production unless explicitly enabled. |
| Queue automation | 80% | Dedicated queue service exists and waits for app health. |
| Scheduler automation | 80% | Dedicated scheduler service exists and waits for app health. |
| Health checks | 65% | Useful basic checks, but do not verify migrations/assets/web readiness deeply. |
| Restart policies | 80% | `restart: unless-stopped` is present on services. |

## Determination

### Is `docker compose up -d --build` sufficient?

**No.**

Reasons:

1. The Docker image currently fails to build at `npm ci`.
2. Production secrets are not handled in a production-safe way.
3. Seeders run by default.
4. Build-time assets are masked by bind mounts and named volumes.
5. Application initialization is coupled to the PHP-FPM app container startup.

## Risks

### 1. Docker Image Build Failure

**Files:** `docker/php/Dockerfile`  
**Risk Level:** Critical

The Dockerfile copies Node, npm, and npx from a Node image into the PHP image:

```dockerfile
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/bin/npm /usr/local/bin/npm
COPY --from=node /usr/local/bin/npx /usr/local/bin/npx
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
```

During verification, the build failed at:

```dockerfile
RUN npm ci
```

with:

```text
Error: Cannot find module '../lib/cli.js'
```

**Impact**  
The stack cannot be built from a fresh clone, so the assignment command fails before containers can start.

**Recommended Fix**  
Use a proper multi-stage frontend build instead of copying npm into the PHP runtime image:

```dockerfile
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.3-fpm AS app
WORKDIR /var/www/html
COPY . .
COPY --from=assets /app/public/build public/build
```

If runtime `npm install` is still required for local bind-mounted development, install Node using the OS package manager or keep Node in a separate builder/dev service.

### 2. App Container Performs Too Many Startup Responsibilities

**Files:** `docker/php/entrypoint.sh`, `docker-compose.yml`  
**Risk Level:** High

The app entrypoint performs:

- `.env` creation
- Composer install
- APP_KEY generation
- Node install
- Frontend build
- Database wait
- Migrations
- Seeders
- Storage link
- PHP-FPM startup

**Impact**  
PHP-FPM startup becomes slow and fragile. If migration, npm, Composer, or asset build fails, the web container never starts. This is workable for local automation but not ideal for production.

**Recommended Fix**  
Move one-time setup into a dedicated `setup` service or deployment job. Then make app, queue, and scheduler depend on successful setup completion.

### 3. Bind Mount Masks Built Image Contents

**File:** `docker-compose.yml`  
**Risk Level:** High

The app service mounts:

```yaml
volumes:
  - .:/var/www/html
  - vendor-data:/var/www/html/vendor
  - node-modules-data:/var/www/html/node_modules
  - public-build-data:/var/www/html/public/build
```

**Impact**  
Anything built into the image at `/var/www/html` can be hidden by the bind mount. The named volumes also hide image-built `vendor`, `node_modules`, and `public/build` on first run, forcing runtime installation/build again.

**Recommended Fix**  
Split local development and production Compose files:

- Development: bind mount source and allow runtime install/build.
- Production: no source bind mount; run immutable images with built `vendor` and `public/build`.

### 4. Seeders Run By Default

**File:** `docker-compose.yml`, `docker/php/entrypoint.sh`  
**Risk Level:** High

Current default:

```yaml
RUN_SEEDERS: ${RUN_SEEDERS:-true}
```

The entrypoint runs:

```sh
php artisan db:seed --force
```

**Impact**  
In production, seeders should not run unless explicitly enabled. Even though the current `DatabaseSeeder` returns outside local/testing, future seeders could accidentally modify production data.

**Recommended Fix**  
Default `RUN_SEEDERS=false` and enable it only in a local override file:

```yaml
RUN_SEEDERS: ${RUN_SEEDERS:-false}
```

### 5. APP_KEY Generated At Runtime

**File:** `docker/php/entrypoint.sh`  
**Risk Level:** Medium

The entrypoint generates the key if missing:

```sh
php artisan key:generate --force
```

**Impact**  
This is convenient locally, but production secrets should be provided through a secret manager or environment variable. If a production container starts without persisted `.env` or external `APP_KEY`, encrypted cookies, sessions, and data can become invalid after regeneration.

**Recommended Fix**  
For production:

- Require `APP_KEY` to be supplied.
- Fail fast if `APP_ENV=production` and `APP_KEY` is missing.
- Generate only in local/development mode.

### 6. Composer Install At Runtime

**File:** `docker/php/entrypoint.sh`  
**Risk Level:** Medium

The entrypoint runs Composer if `vendor/autoload.php` is missing.

**Impact**  
Runtime dependency installation requires network access, increases startup time, and makes production startup non-deterministic.

**Recommended Fix**  
For production, install Composer dependencies at build time only. Keep runtime Composer install only for local development.

### 7. Frontend Build At Runtime

**File:** `docker/php/entrypoint.sh`  
**Risk Level:** Medium

The entrypoint runs:

```sh
npm install
npm run build
```

**Impact**  
Runtime asset builds require network access and CPU at startup. Production images should contain prebuilt assets.

**Recommended Fix**  
Build assets in a Node builder stage and copy `public/build` into the PHP/nginx image.

### 8. Health Check Does Not Prove Full Readiness

**File:** `docker-compose.yml`  
**Risk Level:** Medium

Current app health check:

```yaml
test: ["CMD-SHELL", "test -f .env && test -f vendor/autoload.php && php artisan about --only=environment >/dev/null || exit 1"]
```

**Impact**  
This proves Laravel can boot, but it does not verify:

- Database migrations are complete
- Vite manifest exists
- Storage symlink exists
- nginx can reach PHP-FPM
- Queue backend is usable

**Recommended Fix**  
Add an application readiness command or endpoint that checks critical dependencies:

- `APP_KEY` present
- DB connection works
- `migrations` table exists
- `public/build/manifest.json` exists
- `public/storage` symlink exists

### 9. Queue And Scheduler Share App Image But Skip Setup

**File:** `docker-compose.yml`  
**Risk Level:** Low

Queue and scheduler use:

```yaml
RUN_AUTOMATED_SETUP: "false"
depends_on:
  app:
    condition: service_healthy
```

**Impact**  
This is mostly correct. The risk is that app health may pass before every operational dependency is truly ready unless the health check is strengthened.

**Recommended Fix**  
Keep queue/scheduler setup disabled, but make them depend on a dedicated `setup` service or stronger app health.

## Missing Features

1. Successful frontend build inside Docker.
2. Production-safe multi-stage image.
3. Dedicated setup/migration service.
4. Production/development Compose separation.
5. Secret management strategy.
6. Fail-fast production APP_KEY validation.
7. Strong readiness check.
8. Optional, explicit seeding.
9. Immutable production containers without source bind mounts.
10. Clear README instructions for local vs production Docker.

## Security Issues

### Runtime Secret Generation

Generating `APP_KEY` at runtime is acceptable for local development but not production. Production should inject `APP_KEY` using environment variables, Docker secrets, or a managed secret store.

### Default Local Credentials

`RUN_SEEDERS=true` can create default users in local/testing. This should never become a production default.

### Runtime Package Installation

Runtime Composer/npm installation increases supply-chain risk and requires outbound network access from production containers.

### Bind Mounted Application Source

Bind mounting the whole source tree into the container is a development convenience. Production containers should run immutable images and only mount specific persistent storage.

### APP_DEBUG Default

Compose defaults to:

```yaml
APP_DEBUG: ${APP_DEBUG:-true}
```

Production must default to `false`.

## Production-Ready Target Design

Recommended container roles:

```text
mariadb       Database
redis         Cache/session/queue backend
setup         One-time init: migrate, optional seed, storage link, cache warmup
app           PHP-FPM only
nginx         HTTP ingress
queue         Queue worker only
scheduler     Scheduler only
```

Recommended startup flow:

```text
mariadb healthy
redis healthy
setup completes successfully
app starts
nginx starts after app healthy
queue starts after setup/app healthy
scheduler starts after setup/app healthy
```

Recommended production defaults:

```env
APP_ENV=production
APP_DEBUG=false
RUN_SEEDERS=false
RUN_FRONTEND_BUILD=false
RUN_AUTOMATED_SETUP=false
RUN_OPTIMIZE=true
```

For local development, use an override file to enable:

```env
RUN_AUTOMATED_SETUP=true
RUN_SEEDERS=true
RUN_FRONTEND_BUILD=true
```

## Final Assessment

The Docker environment has useful automation pieces, especially the idempotent entrypoint logic and service health dependencies. However, it is not production-ready yet because the image currently fails to build, setup is coupled to PHP-FPM startup, seeders run by default, and production secrets/assets are not handled with immutable-build discipline.

For local assignment automation, fix the Node/npm build failure first. For production readiness, move initialization into a setup job, build assets in a Node stage, inject secrets externally, disable seeders by default, and remove source bind mounts from the production Compose file.
