#!/bin/bash
# deploy-alwaysdata.sh — Déploiement TontineSN sur AlwaysData
set -euo pipefail

DATE=$(date '+%Y-%m-%d %H:%M:%S')
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"

echo "=== [${DATE}] Deploiement TontineSN (AlwaysData) ==="
echo "APP_DIR: ${APP_DIR}"

cd "${APP_DIR}"

# Auto-create .env from .env.alwaysdata if missing
if [ ! -f .env ]; then
    if [ -f .env.alwaysdata ]; then
        echo "=> .env not found, copying from .env.alwaysdata"
        cp .env.alwaysdata .env
        php artisan key:generate --force
    else
        echo "=> ERROR: .env missing and no .env.alwaysdata found. Create one from .env.example"
        exit 1
    fi
fi

# Mode maintenance
php artisan down --retry=60

# Pull
git fetch --all
git reset --hard origin/main

# Composer
composer install --no-dev --optimize-autoloader --no-interaction

# Frontend — les assets sont pré-buildés dans public/build/ (commit git)
# AlwaysData a une mémoire trop limitée (~100MB) pour npm build
# On ne fait rien ici, le git pull a déjà récupéré les derniers assets

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Migrations
php artisan migrate --force

# Permissions
chmod -R 775 storage bootstrap/cache

# Storage link
php artisan storage:link --force 2>/dev/null || true

# Mode normal
php artisan up

echo "=== OK ==="
