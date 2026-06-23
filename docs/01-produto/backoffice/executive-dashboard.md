# Dashboard Executivo — Sprint 24

## Objetivo

O dashboard executivo apresenta uma visão sintética de execução municipal: volume de candidaturas, documentos pendentes, listas por validar, alertas críticos e atas por rever.

## Implementação

- Rota: `backoffice.operational.executive-dashboard`
- Controller: `App\Http\Controllers\Backoffice\ExecutiveDashboardController`
- Service: `App\Services\BackofficeDashboard\ExecutiveDashboardService`
- Snapshot persistente: `backoffice_dashboard_snapshots`

## Controlo

O dashboard usa permissões de relatórios e mantém dados agregados. Qualquer detalhe nominal deve continuar a passar por ecrãs autorizados e auditáveis.

## Pendências

- Confirmar KPIs oficiais do município antes de produção.
- Definir periodicidade de snapshots executivos.
