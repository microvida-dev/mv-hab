# Relatório PHPSTAN-03 — Return Types, Nullability e Arrays

Data de execução: 2026-06-23  
Âmbito: remediação incremental de erros PHPStan de baixo risco, com foco em `missingType.iterableValue`, `return.type` simples e `nullsafe.neverNull`.

## 1. Resumo Executivo

O Sprint PHPSTAN-03 foi executado de forma incremental e conservadora.

Foram aplicados três lotes:

1. Form Requests: anotação de `rules()` e normalização de `authorize()` com retorno booleano explícito.
2. Reporting: anotação de arrays em serviços de dashboard/exportação e correção defensiva de retornos simples em exporters.
3. Factories: remoção de `?->` redundante antes de `??` em factories.

Não foram alteradas migrations, seeders, policies, regras de elegibilidade, pontuação, classificação, documentos privados, contratos, rendas, RGPD ou auditoria.

## 2. Métricas PHPStan

| Momento | Total de erros | Ficheiros afetados |
| --- | ---: | ---: |
| Baseline PHPSTAN-03 | 2828 | 529 |
| Após lote 1 — Form Requests | 2797 | 505 |
| Após lote 2 — Reporting | 2759 | 496 |
| Final após lote 3 — Factories | 2755 | 493 |

Redução final: 73 erros PHPStan.

## 3. Distribuição de Identificadores Alvo

| Identificador | Baseline | Final | Diferença |
| --- | ---: | ---: | ---: |
| `missingType.iterableValue` | 364 | 314 | -50 |
| `return.type` | 72 | 64 | -8 |
| `nullsafe.neverNull` | 84 | 80 | -4 |
| `missingType.return` | 13 | 13 | 0 |
| `missingType.parameter` | 9 | 9 | 0 |
| `argument.templateType` | 15 | 15 | 0 |
| `method.unresolvableReturnType` | 9 | 9 | 0 |

## 4. Lote 1 — Form Requests

Alterações aplicadas:

- Adicionados PHPDoc `@return array<string, mixed>` em métodos `rules()`.
- Normalizados `authorize()` que devolviam `bool|null` para retorno booleano explícito com `=== true`.

Impacto:

- Redução de 31 erros no total.
- Todos os erros alvo em `app/Http/Requests` foram removidos.

## 5. Lote 2 — Reporting

Alterações aplicadas:

- PHPDoc de arrays em serviços de dashboard, indicadores e exportação.
- Correção defensiva de `CsvReportExporter::render()` para lidar com falha de `fopen()`.
- Garantia de string em valores JSON exportados para CSV/HTML.
- Normalização defensiva de filtros lidos de `ReportRun`.

Notas:

- Foi evitada a correção genérica completa de `ReportFilterService` porque a anotação `Builder<TModel>` introduzia ruído novo de `literal-string` em consultas dinâmicas. Essa frente deve ficar para sprint própria de generics/query builders.

Impacto:

- Redução adicional de 38 erros.

## 6. Lote 3 — Factories

Alterações aplicadas:

- Substituição de `?->... ??` por `->... ??` em factories onde PHPStan assinalou `nullsafe.neverNull`.

Impacto:

- Redução adicional de 4 erros `nullsafe.neverNull`.

## 7. Ficheiros Alterados

### Form Requests

- `app/Http/Requests/ApproveListAutomationRunRequest.php`
- `app/Http/Requests/ConfirmTenantPaymentRequest.php`
- `app/Http/Requests/DownloadApplicationReportRequest.php`
- `app/Http/Requests/FilterExecutiveDashboardRequest.php`
- `app/Http/Requests/FilterLandlordDashboardRequest.php`
- `app/Http/Requests/GenerateApplicationReportRequest.php`
- `app/Http/Requests/GenerateDocumentDossierRequest.php`
- `app/Http/Requests/GenerateMaintenanceReportRequest.php`
- `app/Http/Requests/GenerateProcedureMinuteRequest.php`
- `app/Http/Requests/GenerateProcessConfirmationRequest.php`
- `app/Http/Requests/GenerateTenantInvoiceRequest.php`
- `app/Http/Requests/PublishProcedureTemplateRequest.php`
- `app/Http/Requests/RegisterTenantPaymentRequest.php`
- `app/Http/Requests/RenderProcedureTemplateRequest.php`
- `app/Http/Requests/ResolveInternalAlertRequest.php`
- `app/Http/Requests/ReviewListAutomationRunRequest.php`
- `app/Http/Requests/RunDefinitiveListAutomationRequest.php`
- `app/Http/Requests/RunProvisionalListAutomationRequest.php`
- `app/Http/Requests/RunTenantChargeRunRequest.php`
- `app/Http/Requests/SendProcessConfirmationRequest.php`
- `app/Http/Requests/StoreProcedureTemplateRequest.php`
- `app/Http/Requests/StoreTenantCommunicationMessageRequest.php`
- `app/Http/Requests/StoreTenantCommunicationRequest.php`
- `app/Http/Requests/UpdateDocumentDossierRequest.php`

### Reporting

- `app/Services/Reporting/DashboardService.php`
- `app/Services/Reporting/ExecutiveDashboardService.php`
- `app/Services/Reporting/OperationalDashboardService.php`
- `app/Services/Reporting/IndicatorCalculationService.php`
- `app/Services/Reporting/IndicatorRegistry.php`
- `app/Services/Reporting/ReportFilterService.php`
- `app/Services/Reporting/ReportAccessLogger.php`
- `app/Services/Reporting/ReportDefinitionService.php`
- `app/Services/Reporting/ReportExportService.php`
- `app/Services/Reporting/ReportQueryRegistry.php`
- `app/Services/Reporting/Exporters/CsvReportExporter.php`
- `app/Services/Reporting/Exporters/HtmlReportExporter.php`

### Factories

- `database/factories/ApplicationReportFactory.php`
- `database/factories/DocumentDossierFactory.php`
- `database/factories/HouseholdFactory.php`
- `database/factories/ProcessConfirmationFactory.php`

### Documentação

- `docs/qa/phpstan-03-return-types-nullability-remediation-report.md`

## 8. Ficheiros Gerados para Evidência

- `storage/phpstan/phpstan-03-before.txt`
- `storage/phpstan/phpstan-03-after-lote1-requests.txt`
- `storage/phpstan/phpstan-03-after-lote2-reporting-v4.txt`
- `storage/phpstan/phpstan-03-after-lote3-factories.txt`
- `storage/phpstan/phpstan-03-final.txt`

## 9. Erros Remanescentes

Top identificadores no resultado final:

| Identificador | Quantidade |
| --- | ---: |
| `missingType.generics` | 1093 |
| `missingType.iterableValue` | 314 |
| `property.notFound` | 284 |
| `argument.type` | 249 |
| `property.nonObject` | 123 |
| `method.nonObject` | 93 |
| `nullsafe.neverNull` | 80 |
| `return.type` | 64 |
| `deadCode.unreachable` | 51 |
| `notIdentical.alwaysTrue` | 46 |

Domínios com mais dívida:

| Domínio | Erros |
| --- | ---: |
| Models | 1082 |
| Services — Outros | 768 |
| Contracts/Finance | 177 |
| Reporting | 133 |
| Scoring | 104 |
| Allocation | 94 |
| Documents | 85 |
| Eligibility | 62 |
| Document Intelligence | 51 |
| Controllers | 46 |

## 10. Bugs Prováveis Não Corrigidos

Foram mantidos fora deste sprint por risco funcional ou por pertencerem a domínios protegidos:

- Comparações enum/string em `AdhesionRegistration`, `Application`, `ApplicationScore`, listas e policies.
- Chamadas a métodos em `string` onde o código parece esperar `Carbon`, por exemplo em datas de reclamações, visitas e documentos públicos.
- `DocumentDossierFactory` ainda referencia `DocumentDossierStatus::Generated`, constante não existente segundo PHPStan.
- Chamadas a scopes/métodos não resolvidos como `readyForContract()`, `eligibleForAllocation()` e `withTrashed()` em relações `HasMany`.
- Policies com relações inferidas como `Model|null`, com risco de falsos positivos e/ou necessidade de tipagem relacional.

Estes pontos devem ser tratados em sprint própria de lógica de domínio e relations/generics.

## 11. Comandos Executados

| Comando | Resultado |
| --- | --- |
| `git status --short` | Falhou: `fatal: not a git repository`. O projeto local não está num repositório Git. |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-before.txt` | Falhou com erros PHPStan esperados: 2828 |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-after-lote1-requests.txt` | Falhou com erros PHPStan esperados: 2797 |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-after-lote2-reporting-v4.txt` | Falhou com erros PHPStan esperados: 2759 |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-after-lote3-factories.txt` | Falhou com erros PHPStan esperados: 2755 |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-final.txt` | Falhou com erros PHPStan remanescentes: 2755 |
| `./vendor/bin/pint --test` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK: 280 testes, 1758 asserções |

## 12. Migrations, Dependências e Funcionalidade

- Migrations criadas: nenhuma.
- Dependências Composer/NPM instaladas: nenhuma.
- Configuração PHPStan alterada: não.
- Baseline PHPStan criado ou alterado: não.
- Regras de negócio alteradas: não.
- Funcionalidades críticas alteradas: não.

## 13. Riscos Residuais

- O PHPStan continua a falhar por dívida remanescente elevada.
- A maior parte dos erros restantes está em relations/generics de Models e Services, exigindo sprint dedicada.
- Existem erros que podem representar bugs reais em enum casts, datas, policies e scopes Eloquent.
- Reporting ainda contém erros residuais de relações/model properties e deve ser revisto numa fase posterior.

## 14. Recomendação

Avançar para `PHPSTAN-04` com foco em bugs reais, enum casts, datas, relations críticas e states impossíveis.

Antes disso, recomenda-se uma micro-sprint ou lote inicial para corrigir `DocumentDossierStatus::Generated`, scopes Eloquent não resolvidos e comparações enum/string em Models, porque são candidatos claros a erro real.
