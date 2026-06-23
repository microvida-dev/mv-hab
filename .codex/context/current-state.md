# Current State

Esta pasta foi criada a partir de `crm-habitacao-publica 6.zip` e reorganizada como `MV-HAB`.

## Limpeza aplicada

- Removido `vendor/`.
- Removido `node_modules/`.
- Removido `.env`.
- Removido `.phpunit.result.cache`.
- Removida base local `database/database.sqlite`.
- Excluidos artefactos de runtime em `storage/framework/views`, logs, pail, phpstan e build publico.
- Fontes anexadas copiadas para `docs/00-fontes`.
- Documentacao reorganizada em pastas numeradas.

## Proxima validacao recomendada

```bash
cd /workspace/MV-HAB
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan optimize:clear
composer validate
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
php artisan route:list --except-vendor
./vendor/bin/phpstan analyse --memory-limit=1G -v
npm run build
```

