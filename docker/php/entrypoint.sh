#!/usr/bin/env sh
set -eu

log() {
    printf '%s\n' "[entrypoint] $*"
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

create_writable_directories

if [ "${APP_ENV:-local}" = "production" ] && [ "${APP_DEBUG:-false}" = "true" ]; then
    log "APP_DEBUG=true is not allowed when APP_ENV=production."
    exit 1
fi

exec "$@"
