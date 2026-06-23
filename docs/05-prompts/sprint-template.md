# Sprint Template

## Nome da sprint

Sprint XX - titulo claro

## Objetivo

Descrever o resultado funcional e tecnico esperado.

## Escopo

- Item 1.
- Item 2.
- Item 3.

## Fora de escopo

- Item explicitamente excluido.

## Implementacao recomendada

- Models/migrations.
- Form Requests.
- Policies.
- Services.
- Controllers.
- Views.
- Jobs/events.
- Tests.

## Criterios de aceitacao

- [ ] Fluxo principal validado.
- [ ] Acesso autorizado e negado testado.
- [ ] Dados pessoais protegidos.
- [ ] Auditoria registada quando aplicavel.
- [ ] PHPStan, Pint, PHPUnit e build passam.

## Comandos

```bash
composer validate
php artisan optimize:clear
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
php artisan route:list --except-vendor
./vendor/bin/phpstan analyse --memory-limit=1G -v
npm run build
```

