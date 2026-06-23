# Dashboard Operacional — Sprint 24

## Objetivo

O dashboard operacional agrega indicadores diários para equipas municipais: candidaturas, documentos pendentes, alertas, prazos, visitas e tickets. Não substitui decisão administrativa nem altera estados processuais.

## Implementação

- Rota: `backoffice.operational.dashboard`
- Controller: `App\Http\Controllers\Backoffice\OperationalDashboardController`
- Service: `App\Services\BackofficeDashboard\OperationalDashboardService`
- View: `resources/views/backoffice/dashboard/operational.blade.php`

## Segurança

- Requer autenticação backoffice.
- Candidatos não têm acesso.
- Os dados são agregados e não expõem documentos, paths internos ou identificadores sensíveis.

## Pendências

- Validar com utilizadores municipais quais indicadores devem ficar no primeiro ecrã.
- Avaliar cache por município/concurso quando o volume de candidaturas crescer.
