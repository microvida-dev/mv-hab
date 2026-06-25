# QA-45 Dashboards, KPIs and Municipal Reports Report

## Sumario executivo

QA-45 reforcou a readiness de dashboards, KPIs e relatorios municipais para piloto real controlado. A plataforma ja tinha modulo de Reporting com dashboards, definicoes de relatorios, exports privados, auditoria e mitigacao de formula injection. Foi acrescentado um agregador municipal de KPIs para catalogo transversal e testes focados em autorizacao, minimizacao e performance.

## Ficheiros analisados

- `app/Services/Reporting/*`
- `app/Services/Reporting/Exporters/CsvReportExporter.php`
- `app/Services/Reports/MunicipalKpiService.php`
- `routes/web.php`
- `database/seeders/ReportDefinitionSeeder.php`
- `database/seeders/IndicatorDefinitionSeeder.php`
- `tests/Feature/Sprint17ReportingDashboardTest.php`

## Alteracoes

- criado `App\Services\Reports\MunicipalKpiService`;
- criado catalogo operacional em `docs/11-operacoes/municipal-dashboard-kpi-catalog.md`;
- criado guardrail de exports em `docs/11-operacoes/reporting-export-guardrails.md`;
- criados testes QA45/Reports.

## Validacoes

- dashboards municipais exigem backoffice autenticado;
- candidato/inquilino nao acedem a relatorios municipais;
- auditor tem leitura controlada;
- KPIs sao agregados;
- exports ficam em storage privado;
- downloads sao auditados;
- CSV formula injection e neutralizada.

## Riscos residuais

- validacao de carga com volume real deve ser feita em staging municipal;
- exports nominais devem continuar bloqueados sem permissao especifica;
- relatorios novos devem seguir o registry permitido.

## Decisao

`PASS_WITH_ACCEPTED_RISKS`
