# Relatório de Qualidade — Sprint 26

## Resultado

Sprint 26 implementada com foco em área do inquilino e gestão pós-atribuição.

## Implementado

- `TenantProfile` e `TenantContractAccess`.
- `TenantInvoice`, `TenantPayment`, `TenantChargeRun` e itens.
- Comunicações internas do inquilino.
- Dashboard operacional pós-atribuição.
- Reutilização de contratos, manutenção e vistorias existentes.
- Teste `tests/Feature/Sprint26TenantPostAwardTest.php`.

## Comandos executados durante implementação

- `php artisan migrate`: executado com sucesso.
- `php artisan test --filter=Sprint26TenantPostAwardTest`: 5 testes, 31 asserções, sucesso.
- `php artisan route:list`: sucesso, 1067 rotas.
- `php artisan test`: executou 206 testes e 1342 asserções como passados, mas o processo terminou com erro de memória PHP a 128 MB durante bootstrap de rotas; não foi considerado sucesso por exit code 2.
- `php -d memory_limit=-1 ./vendor/bin/phpunit`: sucesso, 215 testes e 1394 asserções.
- `npm run build`: sucesso.
- `./vendor/bin/pint`: formatou `app/Services/TenantBilling/TenantInvoiceService.php` e `routes/web.php`.
- `./vendor/bin/pint --test`: sucesso.

## PHPStan

Não executado nesta sprint por instrução explícita do utilizador: “ignore o phpstan”.

## Riscos

- Validação municipal final necessária para textos financeiros e regras de cobrança.
- Integração bancária real continua fora de âmbito.
- Relatórios de manutenção ainda são operacionais e não documentos oficiais.
