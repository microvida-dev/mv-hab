#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="${1:-$(pwd)}"
SEEDER="database/seeders/Production/AlcanenaProductionSeeder.php"
TEST="tests/Feature/Seeders/AlcanenaProductionSeederTest.php"

cd "$ROOT"

test -f artisan || { echo "ERRO: executar na raiz do projeto MV-HAB." >&2; exit 1; }
test -f "$SEEDER" || { echo "ERRO: ficheiro em falta: $SEEDER" >&2; exit 1; }
test -f "$TEST" || { echo "ERRO: ficheiro em falta: $TEST" >&2; exit 1; }

php -l "$SEEDER"
php -l "$TEST"

php artisan test "$TEST"

if [[ "${RUN_PRODUCTION_SEEDER:-0}" == "1" ]]; then
    php artisan db:seed \
        --class='Database\\Seeders\\Production\\AlcanenaProductionSeeder' \
        --force
else
    cat <<'EOF'
Validação concluída.
A execução real ficou bloqueada por defeito.

Para executar explicitamente:

RUN_PRODUCTION_SEEDER=1 \
  bash scripts/production/install-alcanena-production-seeder.sh
EOF
fi
