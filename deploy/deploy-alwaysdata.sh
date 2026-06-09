#!/bin/bash
# deploy-alwaysdata.sh — Déploiement TontineSN sur AlwaysData
set -euo pipefail

DATE=$(date '+%Y-%m-%d %H:%M:%S')
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"

echo "=== [${DATE}] Deploiement TontineSN (AlwaysData) ==="
echo "APP_DIR: ${APP_DIR}"

cd "${APP_DIR}"

# Auto-create .env from .env.production if missing
if [ ! -f .env ] && [ -f .env.production ]; then
    echo "=> .env not found, copying from .env.production"
    cp .env.production .env
    php artisan key:generate --force
fi

# Mode maintenance
php artisan down --retry=60

# Pull
git fetch --all
git reset --hard origin/main

# Composer
composer install --no-dev --optimize-autoloader --no-interaction

# Frontend
if command -v npm &> /dev/null; then
    npm ci --production 2>/dev/null || npm ci
    npm run build
fi

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
