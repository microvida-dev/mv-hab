# UX-04 — Design System Municipal e Normalização Visual

## 1. Estado herdado da UX-01

A UX-01 introduziu o Painel Principal por Workspaces, favoritos, recentes, breadcrumbs e navegação contextual. A UX-04 preserva essa arquitetura e normaliza visualmente os componentes em `resources/views/components/navigation/`.

Não foram alterados `WorkspaceService`, `WorkspaceResolver`, `FavoritesService`, `RecentItemsService`, rotas, policies ou permissões.

## 2. Estado herdado da UX-02

A UX-02 introduziu dashboards inteligentes por perfil, KPIs, ações rápidas, widgets e alertas por RBAC. A UX-04 preserva os services de dashboard e normaliza apenas os componentes Blade em `resources/views/components/dashboard/` e `resources/views/dashboard.blade.php`.

## 3. Estado herdado da UX-03

A UX-03 introduziu o Case Workspace de candidatura, timeline, checklist, progresso visual, próxima ação e tabs contextuais. A UX-04 preserva `CaseWorkspaceService` e normaliza os componentes Blade em `resources/views/components/cases/`.

## 4. Tokens definidos/reforçados

Tokens reforçados em `tailwind.config.js`:

| Família | Alteração |
| --- | --- |
| `civic` | adicionados `200` e `800` para hover, foco e contraste coerente |
| `ink` | adicionados `200`, `300`, `400`, `600`, `800`, `950` para texto, borders e estados |

Classes base adicionadas em `resources/css/app.css`:

- `mv-page-shell`;
- `mv-card`;
- `mv-card-muted`;
- `mv-card-interactive`;
- `mv-section-title`;
- `mv-section-description`;
- `mv-focus-ring`;
- `mv-badge` e estados;
- `mv-tab` e `mv-tab-active`;
- `mv-data-label`;
- `mv-data-value`.

## 5. Componentes base

Criados componentes reutilizáveis em `resources/views/components/ui/`:

- `page-header`;
- `section-header`;
- `card`;
- `status-badge`;
- `action-button`;
- `empty-state`;
- `data-list`;
- `metric-card`;
- `alert-panel`;
- `tabs`;
- `focus-ring`.

Os componentes são passivos: renderizam payload já filtrado pelos services e não consultam dados sensíveis.

## 6. Normalização dos componentes de Workspaces

Componentes normalizados:

- `workspace-card`;
- `workspace-grid`;
- `favorites`;
- `recent-items`;
- `breadcrumbs`.

Melhorias:

- cartões com `mv-card-interactive`;
- estados vazios com `x-ui.empty-state`;
- foco visível em links;
- breadcrumbs com superfície consistente;
- favoritos/recentes mantêm validação de rota antes de renderizar.

## 7. Normalização dos componentes de Dashboard

Componentes normalizados:

- `profile-dashboard`;
- `kpi-card`;
- `widget-panel`;
- `quick-action`;
- `deadline-alert`;
- `empty-state`.

Melhorias:

- KPIs usam `x-ui.metric-card`;
- badges de tons usam `x-ui.status-badge`;
- ações e alertas têm foco visível;
- blocos principais usam `mv-card`;
- dashboard usa `x-ui.page-header` e `mv-page-shell`.

## 8. Normalização dos componentes de Case Workspace

Componentes normalizados:

- `case-header`;
- `case-tabs`;
- `case-sidebar`;
- `process-timeline`;
- `process-checklist`;
- `process-progress`;
- `next-action`;
- `contextual-search`.

Melhorias:

- texto visível “Case Workspace” substituído por “Espaço de Trabalho do Processo”;
- tabs passam por `x-ui.tabs` com `role="tablist"` e `role="tab"`;
- checklist/progresso usam `x-ui.status-badge` com labels textuais;
- timeline usa estado vazio comum;
- resumo usa `x-ui.data-list`;
- sidebar preserva links autorizados e foco visível.

## 9. Terminologia portuguesa

Labels principais validados em português:

- Painel Principal;
- Portal Público;
- Indicadores do perfil;
- Ações rápidas;
- Alertas e prazos;
- Favoritos;
- Recentes;
- Espaço de Trabalho do Processo;
- Próxima ação;
- Checklist processual;
- Timeline;
- Painel do processo.

Não foram renomeadas roles técnicas, permissões ou nomes internos.

## 10. Acessibilidade

Cobertura de smoke tests:

- labels em pesquisa global e pesquisa contextual;
- `aria-describedby` na pesquisa global;
- tabs com `role="tablist"` e `role="tab"`;
- `aria-selected` nos tabs;
- foco visível por classes `focus-visible:ring-2`;
- headings `h1`/`h2` presentes;
- estados não dependem apenas de cor, porque os badges têm label textual.

## 11. Mobile/tablet

Padrões preservados/reforçados:

- `mv-page-shell` centraliza largura e spacing;
- grids responsivas existentes mantidas;
- tabs continuam em `overflow-x-auto`;
- Case Workspace mantém layout `xl:grid-cols` com fallback vertical;
- botões mantêm `min-h-10`;
- cards e KPIs mantêm spacing consistente.

## 12. Segurança/RGPD

Validações:

- componentes visuais não consultam dados por si;
- favoritos/recentes continuam a ignorar rotas inexistentes;
- empty states não revelam recursos não autorizados;
- timeline continua a mascarar identificadores e paths privados;
- dashboard continua a apresentar contagens agregadas;
- candidato continua fora do dashboard municipal.

Não foram alteradas policies, permissions, middleware, Work Tasks, auditoria, RGPD ou regras de negócio.

## 13. Testes executados

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | PASS |
| `composer validate --strict` | PASS |
| `./vendor/bin/pint --test` | PASS |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | PASS — 522 testes, 3273 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UX` | PASS — 48 testes, 243 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter DesignSystem` | PASS — 4 testes, 20 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Accessibility` | PASS — 9 testes, 54 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Workspace` | PASS — 24 testes, 122 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Dashboard` | PASS — 42 testes, 255 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter CaseWorkspace` | PASS — 10 testes, 54 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd` | PASS — 27 testes, 172 assertions |
| `php artisan migrate:status` | PASS |
| `php artisan route:list --except-vendor` | PASS |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v` | PASS — 0 erros |
| `npm run build` | PASS |
| `git diff --check` | PASS |

## 14. Evidências

Evidências locais:

- `storage/qa/ux-04-composer.txt`;
- `storage/qa/ux-04-optimize-clear.txt`;
- `storage/qa/ux-04-pint.txt`;
- `storage/qa/ux-04-phpunit.txt`;
- `storage/qa/ux-04-ux-tests.txt`;
- `storage/qa/ux-04-design-system-tests.txt`;
- `storage/qa/ux-04-accessibility-tests.txt`;
- `storage/qa/ux-04-workspace-tests.txt`;
- `storage/qa/ux-04-dashboard-tests.txt`;
- `storage/qa/ux-04-case-workspace-tests.txt`;
- `storage/qa/ux-04-rgpd-tests.txt`;
- `storage/qa/ux-04-migrate-status.txt`;
- `storage/qa/ux-04-route-list.txt`;
- `storage/qa/ux-04-phpstan.txt`;
- `storage/qa/ux-04-build.txt`;
- `storage/qa/ux-04-diff-check.txt`.

## 15. Riscos residuais

- Alguns módulos legados fora dos ecrãs principais ainda usam markup próprio e devem ser normalizados progressivamente.
- A validação WCAG é smoke automatizado; contraste e navegação por teclado devem ser revistos visualmente no ambiente de demonstração.
- Alguns labels técnicos históricos como “Work Tasks” permanecem onde já existiam; a sprint não renomeia entidades técnicas nem permissões.
- Não foi introduzido motor de tokens em PHP; a fonte visual primária continua Tailwind/CSS.

## 16. Decisão final

PASS.

A UX-04 estabelece uma base visual municipal coerente, reforça componentes reutilizáveis, preserva UX-01/UX-02/UX-03, mantém RBAC/RGPD e passa os quality gates obrigatórios.
