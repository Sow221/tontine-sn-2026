#!/bin/bash
set -euo pipefail

DATE=$(date '+%Y-%m-%d %H:%M:%S')
echo "=== [$DATE] Deploiement TontineSN ==="

php artisan down --retry=60

git fetch --all
git reset --hard origin/main

composer install --no-dev --optimize-autoloader --no-interaction

npm ci --production 2>/dev/null || npm ci
npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan migrate --force

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

php artisan storage:link --force 2>/dev/null || true

php artisan up

sudo systemctl reload php8.2-fpm 2>/dev/null || sudo systemctl restart php8.2-fpm
sudo supervisorctl restart tontine-queue-worker:* 2>/dev/null || echo "  (queue worker ok)"

echo "=== OK ==="
