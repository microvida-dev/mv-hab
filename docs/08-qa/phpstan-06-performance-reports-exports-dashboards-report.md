# Relatório PHPSTAN-06 — Performance, Reports, Exports & Dashboards Hardening

Data de execução: 2026-06-23  
Objetivo: reduzir dívida PHPStan nos módulos de reporting, exports, dashboards e relatórios operacionais, sem alterar permissões, policies, migrations, seeders ou regras funcionais de domínio.

## 1. Resumo Executivo

A sprint foi executada com foco em tipagem segura, guardas locais e previsibilidade dos payloads de relatórios/exportações.

Foram corrigidos erros em:

- serviços de indicadores de reporting;
- filtros e query builders de reporting;
- execução, replay, exportação e download de relatórios;
- exportadores CSV/HTML e masking de dados sensíveis;
- dashboards executivos/operacionais;
- relatórios operacionais de candidatura.

Não foram alteradas regras de candidatura, elegibilidade, pontuação, classificação, atribuição, contratos, rendas, RGPD subject rights, MFA ou policies.

## 2. PHPStan Antes/Depois

| Momento | Total de erros | Ficheiros afetados |
| --- | ---: | ---: |
| Antes | 2706 | 480 |
| Depois | 2513 | 452 |

Redução líquida: 193 erros.  
Novos erros introduzidos: 0, comparando por ficheiro, identificador e mensagem.

## 3. Distribuição dos Erros Removidos

| Identificador | Removidos |
| --- | ---: |
| `missingType.iterableValue` | 98 |
| `missingType.generics` | 31 |
| `property.notFound` | 14 |
| `argument.type` | 12 |
| `nullCoalesce.offset` | 7 |
| `property.nonObject` | 4 |
| `method.nonObject` | 3 |
| `match.alwaysFalse` | 3 |
| `return.type` | 3 |
| `argument.unresolvableType` | 1 |
| `assign.propertyType` | 1 |
| `instanceof.alwaysFalse` | 1 |
| `method.unresolvableReturnType` | 1 |
| `nullsafe.neverNull` | 1 |

## 4. Ficheiros Alterados

- `app/Http/Controllers/Backoffice/ExecutiveDashboardController.php`
- `app/Http/Controllers/Backoffice/OperationalDashboardController.php`
- `app/Models/ApplicationReport.php`
- `app/Models/DashboardDefinition.php`
- `app/Models/DashboardWidget.php`
- `app/Models/ReportExport.php`
- `app/Models/ReportFilterPreset.php`
- `app/Models/ReportRun.php`
- `app/Services/OperationalReports/ApplicationReportExportService.php`
- `app/Services/OperationalReports/ApplicationReportPayloadBuilder.php`
- `app/Services/Reporting/Indicators/AllocationIndicatorsService.php`
- `app/Services/Reporting/Indicators/ApplicationIndicatorsService.php`
- `app/Services/Reporting/Indicators/CommunicationIndicatorsService.php`
- `app/Services/Reporting/Indicators/ComplaintIndicatorsService.php`
- `app/Services/Reporting/Indicators/DocumentIndicatorsService.php`
- `app/Services/Reporting/Indicators/FinanceIndicatorsService.php`
- `app/Services/Reporting/Indicators/HousingIndicatorsService.php`
- `app/Services/Reporting/Indicators/MaintenanceIndicatorsService.php`
- `app/Services/Reporting/ReportDownloadService.php`
- `app/Services/Reporting/ReportExportService.php`
- `app/Services/Reporting/ReportFilterService.php`
- `app/Services/Reporting/ReportQueryService.php`
- `app/Services/Reporting/ReportRunService.php`
- `app/Services/Reporting/SensitiveDataMaskingService.php`

## 5. Correções Executadas

| Área | Correção |
| --- | --- |
| Indicadores | Tipagem de filtros `array<string, mixed>`, builders `Builder<Model>` e mapas de retorno. |
| Query service | Tipagem de linhas exportáveis como `array<int, array<string, mixed>>`. |
| Report filters | Generics seguros nos builders aplicados a datas, candidaturas e contratos. |
| Report run | Validação local da definição, filtros e scope antes de replay. |
| Report download | Guardas para export sem run/definition, filtros normalizados e download auditado preservado. |
| Report export | Confirmação sensível passa a respeitar `sensitivity_level` nullable sem erro. |
| Dashboard | Autenticação resolvida uma vez antes de bloquear candidatos. |
| Operational reports | Payload de relatório de candidatura passa a ler relações e atributos com guardas locais, evitando dependência de PHPDoc global em modelos core. |
| Application report export | Payload JSON passa por secções tipadas antes de aceder a offsets. |
| Masking | Entrada e saída tipadas como linhas associativas. |

## 6. Riscos de Performance Identificados

| Área | Risco | Decisão |
| --- | --- | --- |
| Dashboards executivos | Indicadores agregados recalculados em cada request | Documentar para futura cache/materialização; não implementado nesta sprint. |
| Exports CSV/HTML | Export ainda carrega linhas em memória antes de escrever ficheiro | Manter por compatibilidade; propor chunking/queue em sprint própria quando houver datasets reais. |
| Relatórios operacionais de candidatura | `loadMissing()` carrega várias relações profundas | Mantido por ser relatório unitário por candidatura; monitorizar com dados reais. |
| Audit/report logs | Filtros por data/tipo/utilizador dependem de índices existentes | Não foram criadas migrations; rever índices em sprint de performance DB. |
| Finance reports | Agregações por contrato/renda podem crescer | Recomendar índices/materialização futura; sem alteração funcional nesta sprint. |

## 7. Riscos de Segurança/RGPD Identificados

- Downloads de report export continuam auditados em `report_access_logs`, `audit_logs`, `access_logs` e `sensitive_data_access_logs`.
- Não houve alargamento de permissões.
- Não houve alteração em policies.
- Exports mantêm fallback documentado: `xlsx -> csv` e `pdf -> html`.
- CSV mantém proteção contra formula injection no exporter.
- Relatórios de candidatura continuam a mascarar nome do candidato quando o actor não tem `reports.view_sensitive`.

## 8. Testes Criados ou Atualizados

Não foram criados testes novos. Foram reutilizados e reforçados por execução os testes já existentes de reporting/dashboard e relatório operacional:

- `tests/Feature/Sprint17ReportingDashboardTest.php`
- `tests/Feature/Sprint24BackofficeOperationalTest.php`

## 9. Comandos Executados

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-06-before.txt` | Falhou com 2706 erros esperados |
| `./vendor/bin/pint --test` | OK no baseline |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK no baseline: 283 testes, 1775 asserções |
| `./vendor/bin/phpstan analyse app/Services/Reporting ...` | OK após correções nos serviços/modelos de reporting relevantes |
| `./vendor/bin/phpstan analyse app/Services/OperationalReports ...` | OK após correções no relatório operacional de candidatura |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter 'Reporting\|Dashboard\|Report'` | OK: 19 testes, 134 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter 'application_report_and_document_dossier_are_generated_in_private_storage'` | OK: 1 teste, 11 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter 'csv_export_is_private_formula_safe_and_download_is_logged\|sensitive_financial_report_is_blocked_for_technician_and_requires_confirmation\|xlsx_and_pdf_requests_use_documented_safe_fallbacks'` | OK: 3 testes, 19 asserções |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-06-final-v2.txt` | Falhou com 2513 erros remanescentes |
| `./vendor/bin/pint --test` | OK final |
| `php artisan route:list --except-vendor` | OK: 1083 rotas |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK final: 283 testes, 1775 asserções |

## 10. Migrations, Seeders e Dependências

- Migrations criadas: nenhuma.
- Seeders alterados: nenhum.
- Dependências Composer/NPM adicionadas: nenhuma.
- Frontend alterado: não.
- Policies alteradas: nenhuma.

## 11. Riscos Residuais

- PHPStan global mantém 2513 erros legados.
- Persistem erros de generics em modelos de reports/auditoria não tocados.
- Persistem erros em allocation reports, property inspection reports, document dossier exports e dashboards de outros domínios.
- `ProcessDashboardController` e serviços processuais continuam com erros fora do foco desta sprint.
- Exports grandes ainda devem ser migrados futuramente para chunking/queue/materialização.

## 12. Recomendação para PHPSTAN-07

Avançar para `PHPSTAN-07 — Document Intelligence, Jobs, Queues & Integrations Hardening`, mantendo as mesmas regras:

- não introduzir suppressions;
- não criar baseline;
- corrigir apenas erros com impacto claro;
- usar testes dirigidos para jobs, queues, retries, logs e privacidade documental;
- não criar chamadas externas obrigatórias.
