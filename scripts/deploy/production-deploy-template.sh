#!/usr/bin/env bash
set -euo pipefail

git status
git fetch origin
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan down
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
php artisan queue:restart

