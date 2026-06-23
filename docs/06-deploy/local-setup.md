# Deploy Local

## Instalar dependencias

```bash
cd /workspace/MV-HAB
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

## Configurar base de dados

Atualizar `.env` com MySQL/MariaDB local ou SQLite de teste.

```bash
php artisan migrate --seed
```

## Arrancar aplicacao

```bash
npm run dev
php artisan serve
```

## Validar qualidade

```bash
composer validate
php artisan optimize:clear
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
php artisan route:list --except-vendor
./vendor/bin/phpstan analyse --memory-limit=1G -v
npm run build
```

