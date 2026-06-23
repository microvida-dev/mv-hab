#!/usr/bin/env bash
set -euo pipefail

php artisan optimize:clear
php artisan route:list --except-vendor
php artisan queue:restart

echo "Validar manualmente: login, dashboard, concurso publico, candidatura, documentos privados, backoffice e logs."

