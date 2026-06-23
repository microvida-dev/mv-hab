# Deploy Producao

## Principios

- Nunca executar `migrate:fresh` em producao.
- Fazer backup antes de migrations.
- Usar `APP_ENV=production` e `APP_DEBUG=false`.
- Garantir permissoes de `storage` e `bootstrap/cache`.
- Nao enviar `.env`, dumps, logs ou documentos privados para Git.

## Comandos base

```bash
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
```

## Pos-deploy

```bash
php artisan route:list --except-vendor
php artisan queue:restart
```

Validar login, dashboard, concurso publico, candidatura, documentos privados, backoffice e logs.

