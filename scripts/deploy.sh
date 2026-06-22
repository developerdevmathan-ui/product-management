#!/usr/bin/env bash
set -euo pipefail

if [ ! -f .env ]; then
    cp .env.example .env
fi

APP_ENV_VALUE="$(grep -E '^APP_ENV=' .env | tail -n 1 | cut -d '=' -f 2- | tr -d '"')"
APP_DEBUG_VALUE="$(grep -E '^APP_DEBUG=' .env | tail -n 1 | cut -d '=' -f 2- | tr -d '"')"
APP_URL_VALUE="$(grep -E '^APP_URL=' .env | tail -n 1 | cut -d '=' -f 2- | tr -d '"')"
SESSION_SECURE_COOKIE_VALUE="$(grep -E '^SESSION_SECURE_COOKIE=' .env | tail -n 1 | cut -d '=' -f 2- | tr -d '"')"
DOCKER_DB_PASSWORD_VALUE="$(grep -E '^DOCKER_DB_PASSWORD=' .env | tail -n 1 | cut -d '=' -f 2- | tr -d '"')"
DB_ROOT_PASSWORD_VALUE="$(grep -E '^DB_ROOT_PASSWORD=' .env | tail -n 1 | cut -d '=' -f 2- | tr -d '"')"

if [ "${APP_ENV_VALUE}" = "production" ]; then
    if [ "${APP_DEBUG_VALUE}" = "true" ]; then
        echo "APP_DEBUG=true is not allowed in production." >&2
        exit 1
    fi

    case "${APP_URL_VALUE}" in
        https://*) ;;
        *)
            echo "APP_URL must use https:// in production." >&2
            exit 1
            ;;
    esac

    if [ "${SESSION_SECURE_COOKIE_VALUE}" != "true" ]; then
        echo "SESSION_SECURE_COOKIE=true is required in production." >&2
        exit 1
    fi

    if [ "${DOCKER_DB_PASSWORD_VALUE}" = "secret" ] \
        || [ "${DOCKER_DB_PASSWORD_VALUE}" = "change_me_strong_db_password" ] \
        || [ "${DB_ROOT_PASSWORD_VALUE}" = "root_secret" ] \
        || [ "${DB_ROOT_PASSWORD_VALUE}" = "change_me_strong_root_password" ]; then
        echo "Default Docker database credentials are not allowed in production." >&2
        exit 1
    fi
fi

docker compose up -d --build

docker compose ps
