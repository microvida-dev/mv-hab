# UX-05 — Pesquisa Universal e Centro de Comandos

## 1. Estado herdado da UX-01

A navegação por Workspaces permanece como fundação do Centro de Operações Municipal. A UX-05 reutiliza `WorkspaceService`, favoritos, recentes e breadcrumbs sem recriar a arquitetura.

## 2. Estado herdado da UX-02

O dashboard por perfil mantém KPIs, ações rápidas, alertas e filtros por RBAC. O bloco visual de pesquisa deixou de apresentar o estado "Preparado" e passa a encaminhar para pesquisa funcional.

## 3. Estado herdado da UX-03

O Case Workspace de candidatura continua preservado. Resultados de candidatura apontam preferencialmente para `backoffice.cases.applications.show`, quando a rota existe.

## 4. Estado herdado da UX-04

Os componentes visuais da pesquisa usam a base do Design System Municipal: `x-ui.card`, `x-ui.status-badge`, `x-ui.action-button`, `x-ui.empty-state` e foco visível.

## 5. Arquitetura da pesquisa universal

Foram criados:

- `UniversalSearchService`
- `SearchSourceRegistry`
- `SearchResultAuthorizationService`
- `SearchResultPresenter`
- contrato `SearchSource`

Cada source define termo mínimo, permissões, rota de destino, limite e payload minimizado.

## 6. Sources implementadas

Sources criadas:

- Workspaces
- Candidaturas
- Munícipes/Candidatos
- Concursos
- Programas
- Fogos
- Contratos
- Work Tasks
- Tickets
- Manutenção
- Vistorias
- Relatórios
- Comandos

As sources usam `select` explícito, limites por source e não usam `all()`.

## 7. Centro de comandos

O Centro de Comandos expõe apenas atalhos não destrutivos:

- abrir Painel Principal;
- abrir Workspaces autorizados;
- ver minhas tarefas;
- ver tarefas vencidas;
- abrir candidaturas;
- abrir concursos;
- abrir relatórios;
- abrir auditoria.

Comandos sem rota ou sem permissão são omitidos.

## 8. UI e Design System

Foram criados componentes Blade em `resources/views/components/search/` para pesquisa, resultados agrupados, item de resultado, command palette, dialog e empty state.

## 9. Favoritos/recentes avançados

A UX-05 valida que favoritos/recentes inválidos da UX-01 continuam a ser ignorados sem gerar erro. A extensão para favoritos de Case Workspace fica preparada pela estrutura existente, mas não foi aprofundada nesta sprint.

## 10. Integração com Case Workspace

A source de candidaturas usa o Case Workspace quando a rota `backoffice.cases.applications.show` existe. A pesquisa contextual local da UX-03 não foi substituída.

## 11. Segurança/RBAC

As rotas de pesquisa estão no grupo backoffice com autenticação, roles backoffice, utilizador ativo, MFA e logging. Candidatos não usam a pesquisa backoffice.

Cada resultado valida rota e permissão antes de ser apresentado.

## 12. RGPD/minimização

Os resultados não apresentam:

- NIF/document number;
- email;
- telefone;
- morada privada;
- documentos privados;
- paths internos;
- rendimentos;
- dados financeiros detalhados.

Tickets, manutenção e contratos mostram referências operacionais e estados, não descrições livres potencialmente sensíveis.

## 13. Performance

Foram aplicados:

- mínimo de 2 caracteres nas sources de dados;
- comandos disponíveis com termo vazio;
- limite por source;
- limite global;
- colunas explícitas;
- eager loading apenas quando necessário;
- fallback defensivo por source.

## 14. Acessibilidade

A UI inclui label, `aria-describedby`, dialog com `role="dialog"`, estados vazios e links focáveis.

## 15. Testes executados

Foram executados e gravados os comandos obrigatórios:

- `php artisan optimize:clear`
- `composer validate --strict`
- `./vendor/bin/pint --test`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UX`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UniversalSearch`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter CommandPalette`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Search`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Workspace`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Dashboard`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter CaseWorkspace`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security`
- `php artisan migrate:status`
- `php artisan route:list --except-vendor`
- `./vendor/bin/phpstan analyse --memory-limit=1G -v`
- `npm run build`
- `git diff --check`

## 16. Evidências

Artefactos gerados:

- `storage/qa/ux-05-composer.txt`
- `storage/qa/ux-05-optimize-clear.txt`
- `storage/qa/ux-05-ux-tests.txt`
- `storage/qa/ux-05-universal-search-tests.txt`
- `storage/qa/ux-05-command-palette-tests.txt`
- `storage/qa/ux-05-search-tests.txt`
- `storage/qa/ux-05-workspace-tests.txt`
- `storage/qa/ux-05-dashboard-tests.txt`
- `storage/qa/ux-05-case-workspace-tests.txt`
- `storage/qa/ux-05-security-tests.txt`
- `storage/qa/ux-05-rgpd-tests.txt`
- `storage/qa/ux-05-migrate-status.txt`
- `storage/qa/ux-05-route-list.txt`
- `storage/qa/ux-05-phpstan.txt`
- `storage/qa/ux-05-build.txt`
- `storage/qa/ux-05-pint.txt`
- `storage/qa/ux-05-diff-check.txt`
- `storage/qa/ux-05-phpunit.txt`

## 17. Riscos residuais

- A pesquisa usa queries SQL diretas e não motor dedicado. Isto é adequado ao âmbito da sprint, mas pode exigir indexação adicional se o volume crescer.
- A pesquisa de munícipes mostra nomes a utilizadores autorizados. Campos sensíveis complementares não são renderizados.
- Favoritos de Case Workspace ficam suportados pela estrutura atual quando registados por funcionalidades futuras.

## 18. Decisão final

PASS
