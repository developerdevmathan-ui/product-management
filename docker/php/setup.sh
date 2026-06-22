#!/usr/bin/env sh
set -eu

log() {
    printf '%s\n' "[setup] $*"
}

is_production() {
    [ "${APP_ENV:-local}" = "production" ]
}

create_writable_directories() {
    mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        public/build

    chown -R www-data:www-data storage bootstrap/cache public/build 2>/dev/null || true
    chmod -R ug+rwX storage bootstrap/cache public/build 2>/dev/null || true
}

ensure_environment_file() {
    if [ -f .env ]; then
        return
    fi

    if [ ! -f .env.example ]; then
        log "Missing .env and .env.example; setup cannot continue."
        exit 1
    fi

    log "Creating .env from .env.example"
    cp .env.example .env
}

env_value() {
    key="$1"

    if [ ! -f .env ]; then
        return
    fi

    grep -E "^${key}=" .env | tail -n 1 | cut -d '=' -f 2- | tr -d '"' || true
}

validate_production_environment() {
    if ! is_production; then
        return
    fi

    if [ "${APP_DEBUG:-false}" = "true" ]; then
        log "APP_DEBUG=true is not allowed when APP_ENV=production."
        exit 1
    fi

    if ! grep -Eq '^APP_KEY=base64:.+' .env && [ -z "${APP_KEY:-}" ]; then
        log "APP_KEY must be provided explicitly when APP_ENV=production."
        exit 1
    fi

    case "${APP_URL:-}" in
        https://*) ;;
        *)
            log "APP_URL must use https:// when APP_ENV=production."
            exit 1
            ;;
    esac

    if [ "${SESSION_SECURE_COOKIE:-false}" != "true" ]; then
        log "SESSION_SECURE_COOKIE=true is required when APP_ENV=production."
        exit 1
    fi

    if [ "${DOCKER_DB_PASSWORD:-}" = "secret" ] || [ "${DB_PASSWORD:-}" = "secret" ] \
        || [ "${DOCKER_DB_PASSWORD:-}" = "change_me_strong_db_password" ] \
        || [ "${DB_PASSWORD:-}" = "change_me_strong_db_password" ]; then
        log "Default database password is not allowed when APP_ENV=production."
        exit 1
    fi

    if [ "${DB_ROOT_PASSWORD:-}" = "root_secret" ] || [ "${DB_ROOT_PASSWORD:-}" = "change_me_strong_root_password" ]; then
        log "Default root database password is not allowed when APP_ENV=production."
        exit 1
    fi
}

ensure_application_key() {
    if grep -Eq '^APP_KEY=base64:.+' .env || [ -n "${APP_KEY:-}" ]; then
        return
    fi

    if is_production; then
        log "APP_KEY is missing and cannot be generated automatically in production."
        exit 1
    fi

    log "Generating Laravel application key"
    php artisan key:generate --force
}

ensure_php_dependencies() {
    if [ -f vendor/autoload.php ]; then
        return
    fi

    log "Installing Composer dependencies"
    composer install ${COMPOSER_INSTALL_FLAGS:---no-interaction --prefer-dist --optimize-autoloader}
}

ensure_node_dependencies() {
    if [ "${RUN_NODE_INSTALL:-true}" != "true" ]; then
        return
    fi

    if [ -d node_modules ] && [ -f node_modules/.package-lock.json ]; then
        return
    fi

    log "Installing Node dependencies"
    ${NPM_INSTALL_COMMAND:-npm install}
}

build_frontend_assets() {
    if [ "${RUN_FRONTEND_BUILD:-true}" != "true" ]; then
        return
    fi

    log "Building frontend assets"
    npm run build
}

wait_for_database() {
    if [ "${WAIT_FOR_DATABASE:-true}" != "true" ]; then
        return
    fi

    max_attempts="${DB_WAIT_ATTEMPTS:-60}"
    sleep_seconds="${DB_WAIT_SLEEP_SECONDS:-2}"
    attempt=1

    log "Waiting for MariaDB at ${DB_HOST:-mariadb}:${DB_PORT:-3306}"

    while [ "$attempt" -le "$max_attempts" ]; do
        if php -r '
            $host = getenv("DB_HOST") ?: "mariadb";
            $port = getenv("DB_PORT") ?: "3306";
            $database = getenv("DB_DATABASE") ?: "";
            $username = getenv("DB_USERNAME") ?: "";
            $password = getenv("DB_PASSWORD") ?: "";

            try {
                new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password, [
                    PDO::ATTR_TIMEOUT => 3,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                exit(0);
            } catch (Throwable $exception) {
                exit(1);
            }
        '; then
            log "MariaDB is ready"
            return
        fi

        attempt=$((attempt + 1))
        sleep "$sleep_seconds"
    done

    log "MariaDB was not ready after $max_attempts attempts"
    exit 1
}

run_migrations() {
    if [ "${RUN_MIGRATIONS:-true}" != "true" ]; then
        return
    fi

    log "Running database migrations"
    php artisan migrate --force
}

run_seeders() {
    seed_mode="${RUN_SEEDERS:-false}"

    if [ "$seed_mode" != "true" ]; then
        log "Skipping database seeders. Set RUN_SEEDERS=true to run them."
        return
    fi

    if is_production; then
        log "RUN_SEEDERS=true is not allowed when APP_ENV=production."
        exit 1
    fi

    if [ "${CONFIRM_RUN_SEEDERS:-no}" != "yes" ]; then
        log "RUN_SEEDERS=true requires CONFIRM_RUN_SEEDERS=yes."
        return
    fi

    log "Running database seeders"
    php artisan db:seed --force
}

create_storage_link() {
    if [ "${RUN_STORAGE_LINK:-true}" != "true" ]; then
        return
    fi

    log "Creating storage symlink"
    php artisan storage:link --force
}

warm_laravel_caches() {
    if [ "${RUN_OPTIMIZE:-false}" != "true" ]; then
        return
    fi

    log "Caching Laravel configuration, routes, and views"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
}

write_setup_marker() {
    date -u +"%Y-%m-%dT%H:%M:%SZ" > storage/framework/setup-complete
}

create_writable_directories
ensure_environment_file
validate_production_environment
ensure_php_dependencies
ensure_application_key
ensure_node_dependencies
build_frontend_assets
wait_for_database
run_migrations
run_seeders
create_storage_link
warm_laravel_caches
create_writable_directories
write_setup_marker

log "Application setup completed"
