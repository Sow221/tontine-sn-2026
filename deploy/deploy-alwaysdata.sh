#!/bin/bash
# deploy-alwaysdata.sh — Déploiement TontineSN sur AlwaysData
set -euo pipefail

DATE=$(date '+%Y-%m-%d %H:%M:%S')
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"

echo "=== [${DATE}] Deploiement TontineSN (AlwaysData) ==="
echo "APP_DIR: ${APP_DIR}"

cd "${APP_DIR}"

# Always use .env.alwaysdata if present (written by GitHub Actions secret)
if [ -f .env.alwaysdata ]; then
    echo "=> .env.alwaysdata found, overwriting .env"
    cp .env.alwaysdata .env
    php artisan key:generate --force
elif [ ! -f .env ]; then
    echo "=> ERROR: .env missing and no .env.alwaysdata found. Create one from .env.example"
    exit 1
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

# Migrations + seed (idempotent — ne fait rien si admin existe déjà)
php artisan migrate --force
php artisan db:seed --force

# Permissions (ignorer les erreurs sur AlwaysData - hébergement mutualisé)
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Storage link
php artisan storage:link --force 2>/dev/null || true

# Mode normal
php artisan up

echo "=== [${DATE}] Deploiement termine avec succes ==="
