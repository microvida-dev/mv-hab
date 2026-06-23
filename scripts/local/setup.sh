#!/usr/bin/env bash
set -euo pipefail

composer install
npm ci

if [ ! -f .env ]; then
    cp .env.example .env
fi

php artisan key:generate
php artisan optimize:clear

echo "Configure a base de dados no .env e execute: php artisan migrate --seed"

