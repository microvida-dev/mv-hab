# Relatório PHPSTAN-16 — Residual Models, Seeders, Integrations & CI Quality Gate

## Resumo executivo

A PHPSTAN-16 foi concluída como sprint de remediação residual, sem alterações funcionais deliberadas.

O foco foi:

- models remanescentes com generics Eloquent em relações simples;
- factories e seeders demo;
- services periféricos de tenant portal, visitas, manutenção e notificações;
- controllers periféricos com IDs validados mas ainda ambíguos para PHPStan;
- quality gate progressivo para CI/CD.

## Métricas finais

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros PHPStan globais reportados pelo wrapper | 487 | 209 | -278 |
| Erros normalizados por assinatura | 469 | 198 | -271 |
| Ficheiros com erros normalizados | 181 | 86 | -95 |
| Erros removidos por assinatura exata | 0 | 271 | +271 |
| Erros novos por assinatura exata | 0 | 0 | 0 |
| `missingType.generics` | 234 | 25 | -209 |
| `missingType.iterableValue` | 69 | 56 | -13 |
| `argument.type` | 23 | 10 | -13 |
| `method.nonObject` | 26 | 20 | -6 |
| `property.nonObject` | 15 | 11 | -4 |
| `return.type` | 19 | 16 | -3 |
| `nullsafe.neverNull` | 19 | 18 | -1 |
| `notIdentical.alwaysTrue` | 13 | 8 | -5 |
| `identical.alwaysFalse` | 9 | 8 | -1 |
| `property.notFound` | 8 | 2 | -6 |

Resultado do comparador:

```text
previous_normalized_errors: 469
current_normalized_errors: 198
fixed: 271
new: 0
status: passed
```

## Ficheiros alterados

### Models

Foram adicionados generics Eloquent e PHPDocs de casts já existentes em models residuais, incluindo relações `BelongsTo`, `HasOne`, `HasMany` e `BelongsToMany`.

Exemplos de grupos afetados:

- candidaturas, listas e procedimentos;
- contratos, rendas e área do inquilino;
- visitas e manutenção;
- permissões e perfis;
- modelos operacionais auxiliares.

Relações polimórficas foram mantidas fora da remediação automática, salvo onde já estavam tratadas em sprints anteriores.

### Factories e seeders

- `database/factories/ContestFactory.php`
- `database/factories/ProgramFactory.php`
- `database/factories/DocumentFactory.php`
- `database/factories/ContextualFaqCategoryFactory.php`
- `database/factories/EligibilityCriterionFactory.php`
- `database/factories/EligibilityRuleSetFactory.php`
- `database/factories/CorrectionResponseFactory.php`
- `database/seeders/SystemAccessSeeder.php`
- `database/seeders/ConsentPurposeSeeder.php`
- `database/seeders/EligibilityBaseCriteriaSeeder.php`
- `database/seeders/ScoringBaseCriteriaSeeder.php`
- `database/seeders/SecurityAlertRuleSeeder.php`
- `database/seeders/NotificationTemplateSeeder.php`
- `database/seeders/RetentionPolicySeeder.php`
- `database/seeders/DemoAlcanenaAffordableRentSeeder.php`
- `database/seeders/DemoDataSeeder.php`

### Tenant, billing, visits e maintenance

- `app/Services/TenantPortal/TenantPortalAccessService.php`
- `app/Services/Visits/VisitBookingService.php`
- `app/Services/Maintenance/MaintenanceIndicatorService.php`
- models de suporte a contratos, visitas e manutenção.

Os erros financeiros sensíveis ainda remanescentes em `TenantBilling` foram adiados para sprint própria.

### Integrations e notifications

- `app/Services/Notifications/NotificationPreferenceService.php`

### Controllers periféricos

- `app/Http/Controllers/Backoffice/EligibilityCheckController.php`
- `app/Http/Controllers/Backoffice/Finance/RentReviewController.php`
- `app/Http/Controllers/Backoffice/Finance/TenantFinancialAccountController.php`
- `app/Http/Controllers/Backoffice/ProcedureTemplateController.php`
- `app/Http/Controllers/Backoffice/TenantCommunicationController.php`
- `app/Http/Controllers/Backoffice/TenantInvoiceController.php`
- `app/Http/Controllers/Backoffice/TenantPaymentController.php`

### Quality gate

- `docs/qa/phpstan-quality-gate.md`
- `scripts/phpstan-count-errors.php`
- `scripts/phpstan-baseline-compare.php`

## Artefactos gravados

- `storage/phpstan/phpstan-16-before.txt`
- `storage/phpstan/phpstan-16-after-models.txt`
- `storage/phpstan/phpstan-16-after-factories-seeders.txt`
- `storage/phpstan/phpstan-16-after-tenant-billing-visits-maintenance.txt`
- `storage/phpstan/phpstan-16-after-integrations.txt`
- `storage/phpstan/phpstan-16-after-controllers.txt`
- `storage/phpstan/phpstan-16-after-quality-gate.txt`
- `storage/phpstan/phpstan-16-final.txt`
- `storage/phpstan/phpstan-16-count-final.txt`
- `storage/phpstan/phpstan-16-baseline-compare-final.txt`
- `storage/phpstan/phpstan-16-directed-tests.txt`
- `storage/phpstan/phpstan-16-phpunit.txt`
- `storage/phpstan/phpstan-16-pint-final.txt`
- `storage/phpstan/phpstan-16-route-list.txt`
- `storage/phpstan/phpstan-16-optimize-clear-before.txt`
- `storage/phpstan/phpstan-16-optimize-clear-final.txt`

## Validação final

| Comando | Resultado |
| --- | --- |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK — 283 testes, 1775 asserções |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-16-final.txt` | Gerado — exit code 1 esperado por dívida residual |
| `php scripts/phpstan-count-errors.php storage/phpstan/phpstan-16-final.txt` | OK — 209 erros wrapper, 198 normalizados, 86 ficheiros |
| `php scripts/phpstan-baseline-compare.php storage/phpstan/phpstan-16-before.txt storage/phpstan/phpstan-16-final.txt` | OK — `new=0`, `fixed=271`, `status=passed` |
| `./vendor/bin/pint --test` | OK |
| `php artisan route:list --except-vendor` | OK — 1083 rotas |

Testes dirigidos:

```text
135 testes
1011 asserções
passed
```

## Correções adiadas

| Ficheiro / domínio | Motivo | Risco | Recomendação |
| --- | --- | --- | --- |
| `app/Services/TenantBilling/TenantChargeRunService.php` | Estados financeiros e warnings de execução requerem análise funcional | Médio | Tratar em sprint financeira dedicada |
| `app/Services/TenantBilling/TenantPaymentService.php` | Transação, estado de pagamento e retorno exigem validação de fluxo | Médio/Alto | Criar teste dirigido antes de alterar |
| Services residuais fora do âmbito | Erros de nullability e return types em domínios dispersos | Baixo/Médio | PHPSTAN-17 |
| Relações polimórficas residuais | Exigem leitura contextual | Médio | Corrigir manualmente por domínio |

## Bugs reais encontrados

| Código | Tipo | Descrição |
| --- | --- | --- |
| TS | Tipagem segura | Generics Eloquent em relações simples. |
| TS | Tipagem segura | Factories com Faker `array|string` substituído por strings inequívocas. |
| DT | Dívida técnica | Config de roles/permissões normalizada para arrays tipados. |
| RF | Risco funcional | Guard explícito em revisão manual de renda sem cálculo associado. |
| CI | Quality gate/CI | Scripts de contagem e comparação por assinatura sem linha. |

## Riscos residuais

- PHPStan global ainda não está verde: 209 erros wrapper / 198 assinaturas normalizadas.
- `php artisan test` continua não recomendado neste ambiente por limite operacional de memória; usar PHPUnit direto com `memory_limit=-1`.
- Não existe `.git` no diretório atual, pelo que não foi possível usar `git diff`/`git status`.
- Erros financeiros de Tenant Billing foram adiados deliberadamente para evitar alterações comportamentais sem testes específicos.
- Relações polimórficas residuais devem ser tratadas manualmente em PHPSTAN-17.

## Conclusão

A PHPSTAN-16 cumpre os critérios de fecho:

- PHPUnit direto OK;
- Pint OK;
- route list OK;
- PHPStan final gerado;
- redução líquida confirmada;
- `exact_new = 0`;
- quality gate documentado;
- scripts de apoio criados;
- sem baseline PHPStan;
- sem suppressions;
- sem alterações de dependências.

Recomendação: avançar para PHPSTAN-17 com foco nos erros finais residuais e enforcement CI mais estrito.
