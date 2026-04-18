#!/usr/bin/env bash
set -euo pipefail

DEPLOY_PATH=/var/www/fuelHunter

echo "==> Pulling latest code"
cd "$DEPLOY_PATH"
git pull origin main

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Installing JS dependencies and building assets"
npm ci
npm run build

echo "==> Caching Laravel config / routes / views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Running migrations"
php artisan migrate --force

echo "==> Generating sitemap"
php artisan sitemap:generate

echo "==> Restarting queue worker"
sudo supervisorctl restart fuelHunter-queue:*

echo "==> Done."
