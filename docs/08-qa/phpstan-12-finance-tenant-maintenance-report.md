# Relatório PHPSTAN-12 — Finance, Tenant Area & Maintenance Hardening

Data: 2026-06-23  
Âmbito: contratos, finanças, faturação/cobrança do inquilino, manutenção e vistorias.

## Resumo executivo

A sprint reduziu o PHPStan de 1147 para 872 erros globais.

Resultado:

- Redução total: 275 erros.
- Critério mínimo: cumprido (`-150`).
- Critério esperado: cumprido (`-250`).
- Erros novos exatos face à baseline da sprint: 0.
- Baseline, `ignoreErrors` e `phpstan-ignore`: não alterados.
- Regras de cálculo de renda, contratos ativos, histórico financeiro, auditoria, RGPD e notificações legais: preservadas.

O PHPStan continua a falhar por dívida técnica remanescente, mas a sprint não introduziu erros novos face à baseline local.

## Métricas PHPStan

| Artefacto | Erros | Ficheiros |
| --- | ---: | ---: |
| `storage/phpstan/phpstan-12-before.txt` | 1147 | 307 |
| `storage/phpstan/phpstan-12-after-contracts.txt` | 1000 | 291 |
| `storage/phpstan/phpstan-12-after-finance.txt` | 962 | 287 |
| `storage/phpstan/phpstan-12-after-tenant.txt` | 929 | 279 |
| `storage/phpstan/phpstan-12-after-maintenance.txt` | 905 | 273 |
| `storage/phpstan/phpstan-12-final.txt` | 872 | 267 |

Comparação final:

- `exact_new`: 0.
- `exact_removed`: 275.

## Alterações realizadas

### Modelos Eloquent

Foram adicionados PHPDocs de generics Eloquent e, quando existia factory, `@use HasFactory<...>`.

Ficheiros:

- `app/Models/TenantFinancialAccount.php`
- `app/Models/MaintenanceRequest.php`
- `app/Models/RentCalculation.php`
- `app/Models/PropertyInspection.php`
- `app/Models/RentRuleSet.php`
- `app/Models/ContractTemplate.php`
- `app/Models/ContractClause.php`
- `app/Models/MaintenanceIntervention.php`
- `app/Models/RentInstallment.php`
- `app/Models/LeasePayment.php`
- `app/Models/RentReview.php`
- `app/Models/TenantInvoice.php`
- `app/Models/TenantPayment.php`
- `app/Models/ContractDeposit.php`
- `app/Models/InspectionChecklistTemplate.php`
- `app/Models/InspectionChecklistTemplateItem.php`
- `app/Models/TenantChargeRunItem.php`
- `app/Models/TenantContractAccess.php`
- `app/Models/MaintenanceCost.php`
- `app/Models/RentSchedule.php`
- `app/Models/TenantCommunication.php`
- `app/Models/PaymentReceipt.php`

### Services

Foram adicionados guards explícitos para relações obrigatórias antes de chamadas tipadas, normalização de enums/datas e PHPDocs de payloads.

Ficheiros:

- `app/Services/Contracts/ContractDepositService.php`
- `app/Services/Finance/ArrearDetectionService.php`
- `app/Services/Finance/LeasePaymentService.php`
- `app/Services/Finance/PaymentAllocationService.php`
- `app/Services/Finance/PaymentImportService.php`
- `app/Services/Finance/RentReviewService.php`
- `app/Services/Inspections/PropertyInspectionService.php`
- `app/Services/Inspections/PropertyInspectionReportService.php`
- `app/Services/Maintenance/MaintenanceAssignmentService.php`
- `app/Services/Maintenance/MaintenanceCostService.php`
- `app/Services/Maintenance/MaintenanceInterventionService.php`
- `app/Services/Maintenance/MaintenanceRequestService.php`
- `app/Services/Maintenance/MaintenanceStatusService.php`
- `app/Services/TenantBilling/TenantInvoiceService.php`

### Controllers

Foram removidos guards tautológicos expostos pelo PHPStan.

Ficheiros:

- `app/Http/Controllers/Backoffice/RentCalculationController.php`
- `app/Http/Controllers/Backoffice/RentRuleSetController.php`

## Validação executada

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/pint --test` | OK |
| `php artisan test` | Falhou por limite de memória PHP de 128MB no processo filho, após 164 testes passados |
| `php -d memory_limit=-1 ./vendor/bin/phpunit` | OK: 283 testes, 1775 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Contract` | OK: 11 testes, 84 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Finance` | OK: 4 testes, 36 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Rent` | OK: 16 testes, 167 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Tenant` | OK: 7 testes, 55 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Maintenance` | OK: 5 testes, 50 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Inspection` | OK: 5 testes, 50 asserções |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` | Falha esperada por dívida legada: 872 erros remanescentes |

Artefactos:

- `storage/phpstan/phpstan-12-before.txt`
- `storage/phpstan/phpstan-12-after-contracts.txt`
- `storage/phpstan/phpstan-12-after-finance.txt`
- `storage/phpstan/phpstan-12-after-tenant.txt`
- `storage/phpstan/phpstan-12-after-maintenance.txt`
- `storage/phpstan/phpstan-12-final.txt`
- `storage/phpstan/phpstan-12-artisan-test.txt`
- `storage/phpstan/phpstan-12-phpunit-memory-unlimited.txt`
- `storage/phpstan/phpstan-12-directed-tests.txt`

## Riscos controlados

- Não foram alteradas migrations, seeders, dependências, `.env`, baseline ou configuração PHPStan.
- Não foram alteradas fórmulas de renda, estados de contrato, regras de cobrança ou regras de manutenção.
- Os novos guards tornam relações obrigatórias explícitas antes de chamadas a services tipados.
- Downloads de recibos já tinham validação de `storage_path`/`storage_disk`; não foi necessária alteração.

## Riscos residuais

- Persistem 872 erros PHPStan legados em 267 ficheiros.
- `php artisan test` mantém falha operacional por memória PHP 128MB no processo filho; a validação funcional passou com PHPUnit direto e `memory_limit=-1`.
- Ainda há dívida em `FinanceNotificationService`, `TenantPortalAccessService`, seeders demo, charge runs, tenant communications e services de contratos/cobrança.

## Recomendação

Avançar para PHPSTAN-13 — Public Portal, Reports & Integrations.

Antes ou durante a próxima sprint, recomenda-se ajustar o comando local de testes para usar memória suficiente ou executar PHPUnit direto com `php -d memory_limit=-1 ./vendor/bin/phpunit`, porque `php artisan test` continua limitado a 128MB neste ambiente.
