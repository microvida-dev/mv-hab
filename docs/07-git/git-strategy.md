# Git Strategy

## Branches

| Branch | Uso |
| --- | --- |
| `main` | Codigo estavel e pronto para deploy. |
| `develop` | Integracao opcional antes de release. |
| `feature/*` | Novas funcionalidades. |
| `fix/*` | Correcoes pequenas e regressivas. |
| `hotfix/*` | Correcao urgente sobre producao. |
| `release/*` | Preparacao de entrega municipal ou beta. |

## Commits

Usar mensagens claras:

```bash
feat: add public housing filters
fix: protect document download policy
test: cover eligibility scoring tie break
docs: reorganize deploy documentation
```

## Antes de merge

```bash
git status
composer validate
php artisan optimize:clear
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
php artisan route:list --except-vendor
./vendor/bin/phpstan analyse --memory-limit=1G -v
npm run build
```

