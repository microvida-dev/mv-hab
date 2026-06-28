# UX/UI-08 — CRM Analytics & Executive Dashboards

## Resumo da sprint

A UX/UI-08 introduz uma camada analítica municipal read-only para a MV HAB, integrada no backoffice de relatórios existente. A nova experiência apresenta KPIs executivos, tendências, distribuições, funil operacional, indicadores de SLA, carga operacional e tabelas analíticas com componentes acessíveis e responsivos.

A sprint não altera regras de negócio, cálculos oficiais, elegibilidade, scoring, ranking, listas, contratos, rendas, workflows, policies, RBAC ou RGPD.

## Dashboards criados/reforçados

- Criado atalho `/backoffice/analytics`.
- Criada rota equivalente `/backoffice/reports/analytics`.
- Reforçado o workspace Gestão com o item `Centro analítico`.
- Reforçado o Painel executivo existente com resumo, KPIs, tendências e funil.
- Mantidas as rotas antigas de relatórios, dashboards, execuções e exportações.

## Services criados

- `DashboardAnalyticsService`
- `ExecutiveDashboardService`
- `ProfileAnalyticsService`
- `MetricAggregationService`
- `ChartDatasetService`
- `TrendAnalysisService`
- `FunnelAnalysisService`
- `OperationalStatisticsService`
- `TerritorialDistributionService`
- `SlaAnalyticsService`
- `WorkloadAnalyticsService`
- `MunicipalInsightsService`

Todos os serviços são read-only e usam agregações, limites e guards de schema quando aplicável.

## Componentes criados

Em `resources/views/components/analytics/`:

- `kpi-card`
- `trend-card`
- `line-chart`
- `bar-chart`
- `donut-chart`
- `funnel-chart`
- `progress-gauge`
- `metric-comparison`
- `status-distribution`
- `sla-summary`
- `workload-summary`
- `territorial-summary`
- `executive-card`
- `analytics-empty-state`
- `analytics-table`

Os componentes reutilizam o Design System Municipal da UX-04 e usam linguagem municipal em português.

## Métricas disponíveis

- Candidaturas recebidas.
- Candidaturas por estado.
- Evolução mensal de candidaturas.
- Evolução mensal de tarefas.
- Documentos por validar.
- Tickets por estado.
- Concursos ativos.
- Contratos ativos, quando autorizado.
- Rendas em aberto, apenas quando autorizado.
- Pedidos RGPD agregados, quando autorizado.
- Funil operacional municipal.
- SLA por buckets.
- Carga operacional por responsável/equipa.
- Distribuição territorial por freguesia.
- Candidaturas agregadas por concurso.
- Resumo operacional por domínio.

## Métricas omitidas por RGPD

- Métricas nominais de cidadãos.
- NIF, moradas completas, email pessoal e telefone pessoal.
- Documentos privados.
- Caminhos internos de storage.
- Rendimentos individuais.
- Dados financeiros detalhados sem permissão.
- Cohorts pequenos com risco de identificação direta.

## Decisões de performance

- Não foi adicionado motor externo de BI ou pesquisa.
- Não foi adicionada dependência JavaScript pesada para gráficos.
- Os gráficos usam Blade/CSS/SVG simples e tabelas alternativas.
- As queries usam agregações SQL, `limit`, `selectRaw` controlado e guards de schema.
- Não há `Model::all()` na camada criada.
- A carga operacional usa agregados por técnico/equipa, não listas nominais de processos.

## Decisões de acessibilidade

- Gráficos têm `role="img"` ou `progressbar` quando aplicável.
- Cada gráfico inclui alternativa textual em tabela.
- Estados não dependem apenas de cor.
- Filtros têm labels e preservam query string.
- Empty states são explícitos.
- Layout usa grelhas responsivas.

## Testes executados

Evidências a guardar em `storage/qa/`:

- `ux-08-composer.txt`
- `ux-08-optimize-clear.txt`
- `ux-08-pint.txt`
- `ux-08-phpunit.txt`
- `ux-08-ux-tests.txt`
- `ux-08-dashboard-tests.txt`
- `ux-08-analytics-tests.txt`
- `ux-08-executive-tests.txt`
- `ux-08-metric-tests.txt`
- `ux-08-funnel-tests.txt`
- `ux-08-sla-tests.txt`
- `ux-08-workload-tests.txt`
- `ux-08-performance-tests.txt`
- `ux-08-security-tests.txt`
- `ux-08-rgpd-tests.txt`
- `ux-08-accessibility-tests.txt`
- `ux-08-migrate-status.txt`
- `ux-08-route-list.txt`
- `ux-08-phpstan.txt`
- `ux-08-build.txt`
- `ux-08-diff-check.txt`

## Evidências

- `composer validate --strict`: PASS.
- `php artisan optimize:clear`: PASS.
- `./vendor/bin/pint --test`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml`: PASS, 600 testes, 3582 assertions.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UX`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Dashboard`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Analytics`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Executive`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Metric`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Funnel`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Sla`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Workload`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Accessibility`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Performance`: PASS.
- `php artisan migrate:status`: PASS.
- `php artisan route:list --except-vendor`: PASS.
- `./vendor/bin/phpstan analyse --memory-limit=1G -v`: PASS.
- `npm run build`: PASS.
- `git diff --check`: PASS.

Não foram criadas migrations nesta sprint.
- Restantes quality gates serão registados em `storage/qa`.

## Riscos conhecidos

- O painel executivo legado continua dependente dos seeders/definições de dashboard existentes.
- A medição de performance inclui queries de navegação/layout herdadas das UX anteriores; a UX-08 não introduz queries por item em Blade.
- O funil operacional é visual e usa contagens disponíveis por tabela; fases sem tabela ou sem dados aparecem com zero.
- Não foi adicionada biblioteca de gráficos externa; gráficos avançados podem evoluir numa sprint futura mantendo acessibilidade.

## Recomendações para UX/UI-09

- Evoluir filtros analíticos por perfil sem duplicar reporting.
- Melhorar tradução de estados técnicos nas tabelas analíticas.
- Adicionar drill-down autorizado a partir de gráficos, mantendo minimização RGPD.
- Medir query count isolado do layout global para separar custo de navegação e custo analítico.

## Decisão final

PASS

Os quality gates obrigatórios passaram e a sprint manteve o âmbito read-only, sem alterações a regras de negócio, workflows, policies ou dados administrativos.
