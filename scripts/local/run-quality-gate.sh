#!/usr/bin/env bash
set -euo pipefail

composer validate
php artisan optimize:clear
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
php artisan route:list --except-vendor
./vendor/bin/phpstan analyse --memory-limit=1G -v
npm run build

