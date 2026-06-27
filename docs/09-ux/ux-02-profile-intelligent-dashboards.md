# UX-02 — Dashboards Inteligentes por Perfil

## Sumário executivo

A UX-02 evolui o Painel Principal criado na UX-01 para um dashboard contextual por perfil, role, equipa e permissões. A navegação por Workspaces é preservada e enriquecida com saudação, indicadores agregados, foco operacional, ações rápidas, alertas de prazo, favoritos e recentes filtrados por RBAC.

Decisão: `PASS`. A implementação não altera regras de negócio, policies, permissões, workflows administrativos ou URLs da UX-01.

## Estado herdado da UX-01

- `/dashboard` já era o ponto de entrada principal do Centro de Operações Municipal da Habitação.
- Workspaces existentes preservados: Atendimento, Concursos, Património, Gestão e Administração.
- Pesquisa universal continua preparada, sem motor de pesquisa profundo nesta sprint.
- Favoritos e recentes continuam a usar `FavoritesService` e `RecentItemsService`.
- Breadcrumbs e menus contextuais da UX-01 não foram alterados.

## Validação de migrations de navegação

Comando obrigatório executado antes da implementação:

- `php artisan migrate:status | grep -i navigation || true`

Resultado observado:

- `2026_06_27_000033_create_navigation_personalization_tables .. [2] Ran`

Não foram criadas tabelas novas para a UX-02. O dashboard usa tabelas existentes de forma defensiva via `Schema::hasTable()` e não consulta tabelas de personalização sem migration versionada.

## Estrutura de dashboard por perfil

Serviços criados:

- `app/Services/Dashboard/ProfileDashboardService.php`
- `app/Services/Dashboard/DashboardWidgetRegistry.php`
- `app/Services/Dashboard/DashboardMetricService.php`
- `app/Services/Dashboard/DashboardQuickActionService.php`
- `app/Services/Dashboard/DashboardDeadlineService.php`
- `app/Services/Dashboard/DashboardAuthorizationService.php`

O `DashboardController` mantém-se fino: obtém o utilizador autenticado, bloqueia utilizadores desativados, redireciona candidatos para a experiência própria e delega a composição do payload no `ProfileDashboardService`.

## Widgets implementados

Perfis cobertos:

- `administrator`
- `municipal_technician`
- `jury`
- `legal_manager`
- `financial_manager`
- `housing_manager`
- `maintenance_manager`
- `inspection_manager`
- `support_agent`
- `auditor`
- `candidate`, mantendo redireção para dashboard próprio

Widgets/painéis de foco:

- Administração e segurança
- Operação transversal
- Revisão técnica
- Classificação e listas
- Validação jurídica
- Controlo financeiro
- Gestão habitacional
- Manutenção
- Vistorias
- Atendimento
- Auditoria em leitura

## KPIs implementados

KPIs autorizados e agregados:

- utilizadores ativos;
- equipas ativas;
- alertas de segurança;
- tarefas atribuídas;
- tarefas da equipa;
- tarefas vencidas;
- candidaturas pendentes;
- documentos pendentes;
- reclamações pendentes;
- contratos pendentes;
- rendas pendentes;
- pagamentos por validar;
- tickets abertos;
- visitas agendadas;
- fogos disponíveis;
- manutenção urgente;
- vistorias agendadas;
- eventos de auditoria;
- pedidos RGPD.

Cada KPI valida rota, permissão e/ou role antes de ser apresentado.

## Ações rápidas por perfil

As ações rápidas foram movidas para `DashboardQuickActionService` e são filtradas por autorização:

- Técnico: Rever documentos, Abrir candidaturas, Ver tarefas, Ver concursos.
- Júri: Classificar processos, Ver listas, Ver reclamações.
- Financeiro: Ver rendas, Ver pagamentos, Ver contratos.
- Manutenção: Pedidos urgentes, Ver vistorias, Tarefas vencidas.
- Auditor: Ver auditoria, Acessos sensíveis, Relatórios.

Rotas inexistentes ou sem permissão são ocultadas automaticamente.

## Alertas e prazos

Foi criado um bloco compacto de alertas com:

- tarefas vencidas;
- tarefas a vencer;
- documentos pendentes;
- reclamações pendentes;
- contratos pendentes;
- pedidos RGPD;
- alertas de segurança.

Os alertas usam contagens agregadas e links apenas quando autorizados.

## Segurança e RBAC

- Workspaces continuam filtrados pelo `WorkspaceService`.
- KPIs, ações rápidas e alertas validam `Route::has()`, permissões e roles.
- Auditor recebe apenas ações de leitura.
- Candidato não recebe widgets de backoffice e continua redirecionado para `candidate.dashboard`.
- Utilizador desativado recebe `403`.
- Favoritos/recentes inválidos ou sem autorização são ignorados.

## RGPD e minimização

- O dashboard não expõe NIF, contactos, moradas, documentos privados, paths internos, notas internas ou dados financeiros detalhados.
- Tickets, documentos, rendas e auditoria são apresentados apenas como contagens agregadas.
- Não foram criados logs, exports ou snapshots com dados pessoais.
- Os testes RGPD usam dados sintéticos e verificam que descrições sensíveis não são renderizadas.

## Performance

- KPIs usam contagens agregadas via query builder.
- Work Tasks são filtradas por utilizador/equipa antes da contagem.
- Não foi introduzido `all()` em tabelas operacionais.
- Favoritos e recentes mantêm limites existentes.
- O payload é composto uma vez no service e reutilizado pela view.

## Testes criados

- `tests/Feature/UX/ProfileDashboardTest.php`
- `tests/Feature/UX/ProfileDashboardAuthorizationTest.php`
- `tests/Feature/UX/ProfileDashboardWidgetsTest.php`
- `tests/Feature/UX/ProfileDashboardQuickActionsTest.php`
- `tests/Feature/UX/ProfileDashboardSessionSafetyTest.php`
- `tests/Feature/UX/ProfileDashboardRgpdTest.php`
- `tests/Unit/Dashboard/ProfileDashboardServiceTest.php`
- `tests/Unit/Dashboard/DashboardWidgetRegistryTest.php`
- `tests/Unit/Dashboard/DashboardMetricServiceTest.php`

Cobertura:

- dashboards por perfil;
- autorização de widgets/KPIs/ações;
- candidato fora do backoffice;
- auditor apenas leitura;
- favoritos/recentes inválidos;
- utilizador desativado;
- minimização RGPD;
- métricas de Work Tasks por utilizador/equipa/SLA.

## Evidências

Evidências locais criadas:

- `storage/qa/ux-02-composer.txt`
- `storage/qa/ux-02-optimize-clear.txt`
- `storage/qa/ux-02-phpunit.txt`
- `storage/qa/ux-02-ux-tests.txt`
- `storage/qa/ux-02-dashboard-tests.txt`
- `storage/qa/ux-02-profile-dashboard-tests.txt`
- `storage/qa/ux-02-navigation-tests.txt`
- `storage/qa/ux-02-security-tests.txt`
- `storage/qa/ux-02-rgpd-tests.txt`
- `storage/qa/ux-02-migrate-status.txt`
- `storage/qa/ux-02-phpstan.txt`
- `storage/qa/ux-02-route-list.txt`
- `storage/qa/ux-02-build.txt`
- `storage/qa/ux-02-pint.txt`
- `storage/qa/ux-02-diff-check.txt`

## Comandos executados

Executados durante implementação:

- `pwd`
- `git status --short --branch`
- `git remote -v`
- `git branch --show-current`
- `git log -1 --oneline`
- `php artisan migrate:status | grep -i navigation || true`
- `php artisan migrate:status | grep -i dashboard || true`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter ProfileDashboard`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UX`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Dashboard`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Navigation`
- `./vendor/bin/pint --test`
- `./vendor/bin/phpstan analyse --memory-limit=1G -v`
- `php artisan optimize:clear > storage/qa/ux-02-optimize-clear.txt 2>&1`
- `composer validate --strict > storage/qa/ux-02-composer.txt 2>&1`
- `./vendor/bin/pint --test > storage/qa/ux-02-pint.txt 2>&1`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UX > storage/qa/ux-02-ux-tests.txt 2>&1`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Dashboard > storage/qa/ux-02-dashboard-tests.txt 2>&1`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter ProfileDashboard > storage/qa/ux-02-profile-dashboard-tests.txt 2>&1`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Navigation > storage/qa/ux-02-navigation-tests.txt 2>&1`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security > storage/qa/ux-02-security-tests.txt 2>&1`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd > storage/qa/ux-02-rgpd-tests.txt 2>&1`
- `php artisan migrate:status > storage/qa/ux-02-migrate-status.txt 2>&1`
- `php artisan route:list --except-vendor > storage/qa/ux-02-route-list.txt 2>&1`
- `./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/qa/ux-02-phpstan.txt 2>&1`
- `npm run build > storage/qa/ux-02-build.txt 2>&1`
- `git diff --check > storage/qa/ux-02-diff-check.txt 2>&1`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml > storage/qa/ux-02-phpunit.txt 2>&1`

Os comandos finais obrigatórios ficam registados em `storage/qa/ux-02-*.txt`.

## Resultados finais dos gates

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | PASS |
| `composer validate --strict` | PASS |
| `./vendor/bin/pint --test` | PASS |
| PHPUnit completo | PASS — 491 testes, 3125 asserções |
| PHPUnit `--filter UX` | PASS — 22 testes, 111 asserções |
| PHPUnit `--filter Dashboard` | PASS — 37 testes, 226 asserções |
| PHPUnit `--filter ProfileDashboard` | PASS — 12 testes, 70 asserções |
| PHPUnit `--filter Navigation` | PASS — 9 testes, 71 asserções |
| PHPUnit `--filter Security` | PASS — 56 testes, 350 asserções |
| PHPUnit `--filter Rgpd` | PASS — 24 testes, 157 asserções |
| `php artisan migrate:status` | PASS — migration `navigation_personalization` aplicada |
| `php artisan route:list --except-vendor` | PASS — 1122 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v` | PASS — 0 erros |
| `npm run build` | PASS |
| `git diff --check` | PASS |

## Riscos residuais

- O motor universal de pesquisa continua preparado, mas não funcionalmente profundo; isto é deliberado no âmbito da UX-02.
- Alguns KPIs podem apresentar zero em ambientes sem dados operacionais, mantendo estado vazio/controlado.
- Rotas não existentes são ocultadas, o que reduz risco operacional, mas pode tornar algumas ações invisíveis até o módulo correspondente estar disponível.
- A validação visual final em browser ainda deve ser feita no ambiente de demonstração municipal.

## Decisão final

`PASS`

Os gates obrigatórios passaram, o dashboard varia por perfil, os widgets respeitam RBAC, candidatos continuam fora do backoffice, auditor mantém leitura controlada, favoritos/recentes da UX-01 continuam filtrados e não foram expostos dados sensíveis.
