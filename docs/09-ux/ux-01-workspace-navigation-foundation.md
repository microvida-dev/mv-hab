# UX-01 — Arquitetura de Navegação por Workspaces

## Sumário executivo

A UX-01 substitui o ponto de entrada administrativo baseado em side navigation global por um Painel Principal organizado por workspaces municipais. O menu lateral continua disponível apenas no contexto de um workspace ou módulo interno, preservando rotas, controllers, policies, middleware, permissões, auditoria e regras de negócio.

Decisão: `PASS`.

## Guardrails aplicados

- Não foram alteradas regras de elegibilidade, scoring, ranking, listas, contratos, rendas, documentos, IA documental, RGPD ou Work Tasks.
- Não foram removidas rotas, controllers, policies, middleware ou permissões.
- Workspaces, favoritos e recentes respeitam RBAC através da matriz de permissões/roles e policies existentes.
- A persistência nova guarda apenas metadados mínimos de navegação por utilizador.
- Não há exposição de documentos privados, paths internos, NIF, rendimentos ou dados pessoais adicionais.

## Ficheiros analisados

- `AGENTS.md`
- `docs/README.md`
- `docs/02-arquitetura/domain-boundaries.md`
- `docs/08-qa/enterprise-quality-gate.md`
- `docs/08-qa/query-and-export-guardrails.md`
- `docs/08-qa/critical-flows-test-map.md`
- `docs/08-qa/pre-release-checklist.md`
- `docs/09-seguranca-rgpd/security-rgpd-guardrails.md`
- `docs/08-qa/qa-30-user-role-competency-management-report.md`
- `docs/08-qa/qa-31-work-task-competency-workflow-report.md`
- `docs/08-qa/qa-32-security-rgpd-hardening-report.md`
- `docs/08-qa/qa-33-advanced-document-ai-report.md`
- `docs/08-qa/qa-34-advanced-public-portal-report.md`
- `docs/08-qa/qa-35-visits-candidate-support-report.md`
- `docs/08-qa/qa-36-production-municipal-final-report.md`
- `docs/08-qa/phase-1-hardening-before-municipal-presentation-report.md`
- `docs/08-qa/phase-2-controlled-municipal-staging-readiness-report.md`
- `docs/08-qa/phase-3-real-users-pilot-readiness-report.md`
- `docs/11-operacoes/out-of-scope-integrations.md`
- `docs/11-operacoes/municipal-rbac-team-matrix.md`
- `docs/11-operacoes/municipal-access-review-checklist.md`
- `docs/11-operacoes/reporting-export-guardrails.md`
- `docs/11-operacoes/security-rgpd-operational-checklist.md`
- `routes/web.php`
- `app/Http/Controllers/DashboardController.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/views/dashboard.blade.php`
- `app/Services/CandidateExperience/CandidateNavigationService.php`

## Arquitetura criada

Serviços:

- `app/Services/Navigation/WorkspaceService.php`
- `app/Services/Navigation/WorkspaceResolver.php`
- `app/Services/Navigation/BreadcrumbService.php`
- `app/Services/Navigation/FavoritesService.php`
- `app/Services/Navigation/RecentItemsService.php`

Controllers e request:

- `app/Http/Controllers/Navigation/WorkspaceController.php`
- `app/Http/Controllers/Navigation/FavoriteController.php`
- `app/Http/Requests/Navigation/StoreNavigationFavoriteRequest.php`

Persistência:

- `navigation_favorites`
- `navigation_recent_items`

Componentes Blade:

- `resources/views/components/navigation/workspace-card.blade.php`
- `resources/views/components/navigation/workspace-grid.blade.php`
- `resources/views/components/navigation/context-sidebar.blade.php`
- `resources/views/components/navigation/breadcrumbs.blade.php`
- `resources/views/components/navigation/favorites.blade.php`
- `resources/views/components/navigation/recent-items.blade.php`

Views:

- `resources/views/dashboard.blade.php`
- `resources/views/navigation/workspace.blade.php`

## Workspaces

| Workspace | Âmbito | Controlo de acesso |
| --- | --- | --- |
| Atendimento | Munícipes, agregados, simulador, candidaturas, documentos, visitas, tickets e FAQ | Permissões existentes de atendimento, candidaturas, documentos, visitas e suporte |
| Concursos | Programas, concursos, documentos, elegibilidade, pontuação, listas, sorteios e publicações | Permissões existentes de programas, concursos, documentos, elegibilidade, scoring, public lists e allocations |
| Património | Fogos, portal público, contratos, rendas, manutenção e vistorias | Permissões existentes de housing units, settings, contracts, finance, payments, maintenance e inspections |
| Gestão | Work Tasks, relatórios, KPIs, auditoria, RGPD, IA documental e comunicações | Permissões existentes de work_tasks, reports, audit_logs, privacy, documents e notifications |
| Administração | Utilizadores, roles, equipas, auditoria de acessos, segurança, MFA e retenção | Permissões existentes de users, roles, teams, access_audit, privacy e roles administrator/auditor |

## Comportamento UX

- `/dashboard` passa a ser o Painel Principal por workspaces e não carrega side navigation.
- `/workspaces/{workspace}` abre o workspace e carrega apenas menu contextual desse workspace.
- Breadcrumbs globais aparecem em páginas internas com contexto resolvido.
- Favoritos podem fixar workspaces autorizados.
- Recentes registam visitas a workspaces.
- Pesquisa global fica preparada como infraestrutura visual e de agrupamento, sem pesquisa completa nesta sprint.

## Segurança e RGPD

- Favoritos e recentes são filtrados no momento de leitura; se o utilizador perder permissão, o item deixa de ser apresentado.
- A nova persistência não guarda dados pessoais de cidadãos/candidatos/inquilinos, documentos, paths privados ou valores financeiros.
- Rotas de workspace e favoritos usam autenticação, role backoffice, active backoffice, MFA backoffice e log backoffice.
- Candidatos continuam redirecionados para a experiência sequencial própria e não acedem a workspaces municipais.

## Testes criados

- `tests/Feature/UX/WorkspaceDashboardTest.php`
- `tests/Feature/UX/WorkspaceAuthorizationTest.php`
- `tests/Feature/UX/BreadcrumbTest.php`
- `tests/Feature/UX/FavoritesTest.php`
- `tests/Feature/UX/RecentItemsTest.php`

Cobertura:

- dashboard principal por workspaces;
- candidato redirecionado para área própria;
- workspaces visíveis apenas conforme permissões;
- bloqueio de candidato em workspace backoffice;
- menu contextual por workspace;
- breadcrumbs;
- favoritos persistidos/removidos;
- recentes persistidos e atualizados.

## Comandos executados

- `pwd`
- `git status --short --branch`
- `git remote -v`
- `git branch --show-current`
- `git log --oneline -10`
- `php artisan route:list --except-vendor`
- `php artisan route:list --json`
- `php -l app/Services/Navigation/WorkspaceService.php`
- `php -l app/Services/Navigation/WorkspaceResolver.php`
- `php -l app/Services/Navigation/BreadcrumbService.php`
- `php -l app/Services/Navigation/FavoritesService.php`
- `php -l app/Services/Navigation/RecentItemsService.php`
- `php -l app/Http/Controllers/Navigation/WorkspaceController.php`
- `php -l app/Http/Controllers/Navigation/FavoriteController.php`
- `php -l app/Http/Requests/Navigation/StoreNavigationFavoriteRequest.php`
- `php -l app/Http/Controllers/DashboardController.php`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UX`
- `./vendor/bin/phpstan analyse app/Services/Navigation app/Http/Controllers/Navigation app/Http/Requests/Navigation app/Http/Controllers/DashboardController.php --memory-limit=1G -v`
- `php artisan optimize:clear`
- `composer validate --strict`
- `./vendor/bin/pint --test`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml`
- `php artisan route:list --except-vendor`
- `./vendor/bin/phpstan analyse --memory-limit=1G -v`
- `npm run build`
- `git diff --check`

## Resultados já obtidos

- Testes UX: `PASS` — 11 testes, 47 asserções.
- PHPStan dirigido aos novos ficheiros: `PASS` — 0 erros.
- `php artisan optimize:clear`: `PASS`
- `composer validate --strict`: `PASS`
- `./vendor/bin/pint --test`: `PASS`
- PHPUnit completo: `PASS` — 477 testes, 3050 asserções.
- `php artisan route:list --except-vendor`: `PASS`
- PHPStan completo: `PASS` — 0 erros.
- `npm run build`: `PASS`
- `git diff --check`: `PASS`

## Riscos residuais

- O bloco legado em `resources/views/layouts/navigation.blade.php` ainda existe como fallback histórico, mas é substituído em runtime pelo `WorkspaceService` para utilizadores backoffice. Deve ser removido numa iteração de limpeza depois de validar visualmente todas as rotas antigas.
- A pesquisa universal ainda não executa consultas reais; apenas prepara a infraestrutura UX.
- Favoritos nesta fase fixam workspaces; favoritos de páginas/recurso ficam preparados no modelo, mas sem UI completa.
- A validação visual em browser ainda deve confirmar responsividade e contraste em ecrãs reais.

## Critérios de aceitação

| Critério | Estado |
| --- | --- |
| Side navigation deixa de ser navegação principal | Implementado |
| Painel Principal por workspaces | Implementado |
| Navegação contextual por workspace | Implementado |
| Breadcrumbs | Implementado |
| Favoritos | Implementado para workspaces |
| Recentes | Implementado para workspaces |
| RBAC íntegro | Coberto por testes UX |
| Rotas existentes preservadas | Implementado |
| PHPUnit completo | PASS |
| Pint | PASS |
| PHPStan completo | PASS |
| Build | PASS |
| route:list | PASS |

## Decisão final

`PASS`

A fundação de workspaces está implementada, os testes UX dedicados passam e os quality gates obrigatórios foram executados com sucesso.
