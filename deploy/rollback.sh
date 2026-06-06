#!/usr/bin/env bash
set -euo pipefail

DEPLOY_PATH=/var/www/fuelHunter
COMMIT_SHA="${1:?Usage: rollback.sh <commit-sha>}"

echo "==> Rolling back to $COMMIT_SHA"
cd "$DEPLOY_PATH"
git fetch origin
git reset --hard "$COMMIT_SHA"

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Installing JS dependencies and building assets"
npm ci
npm run build

echo "==> Clearing and rebuilding Laravel caches"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Restarting queue worker"
sudo supervisorctl restart fuelHunter-queue:*

echo "==> Rollback to $COMMIT_SHA complete."
