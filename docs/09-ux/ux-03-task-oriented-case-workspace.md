# UX-03 — Experiência Processual e Case Workspace

## 1. Estado herdado da UX-01

A UX-01 introduziu o Painel Principal por Workspaces, favoritos, recentes, breadcrumbs e navegação contextual. A UX-03 preserva essa fundação e reutiliza `WorkspaceResolver` e `BreadcrumbService` para enquadrar o novo Case Workspace dentro do workspace Atendimento.

A migration de personalização de navegação está aplicada:

| Migration | Estado |
| --- | --- |
| `2026_06_27_000033_create_navigation_personalization_tables` | Ran |

## 2. Estado herdado da UX-02

A UX-02 introduziu dashboards inteligentes por perfil, KPIs, ações rápidas, alertas e filtragem por RBAC. A UX-03 não recria esses serviços. O dashboard continua funcional e passa a poder encaminhar o técnico para o contexto processual de uma candidatura.

## 3. Objetivo da experiência processual

O objetivo implementado foi reduzir a navegação por módulos e permitir que o técnico municipal trabalhe uma candidatura como processo/caso, com resumo, timeline, checklist, progresso, próxima ação e painéis contextuais no mesmo ecrã.

## 4. Case Workspace implementado

Foi criada a fundação reutilizável em `app/Services/Cases/`:

| Serviço | Função |
| --- | --- |
| `CaseWorkspaceService` | Orquestra o workspace do processo. |
| `CaseWorkspaceResolver` | Define tipos suportados e extensibilidade para casos futuros. |
| `CaseSummaryService` | Compõe metadados seguros do caso. |
| `ProcessTimelineService` | Agrega timeline processual, Work Tasks e auditoria autorizada. |
| `NextActionResolver` | Resolve próxima ação operacional, sem alterar dados. |
| `ProcessChecklistService` | Deriva checklist processual dos dados existentes. |
| `ProcessProgressService` | Calcula progresso visual por fases. |
| `ContextualCaseSearchService` | Pesquisa local no contexto carregado. |
| `CaseAuthorizationService` | Centraliza filtragem de permissões/rotas. |

Também foram criados componentes Blade reutilizáveis em `resources/views/components/cases/`.

## 5. Caso Candidatura

Implementação completa:

- rota `GET /backoffice/cases/applications/{application}`;
- controller fino `Backoffice\CaseWorkspaceController`;
- view `resources/views/cases/application/show.blade.php`;
- ação “Abrir processo” na listagem de candidaturas;
- tabs autorizados para Resumo, Timeline, Documentos, Elegibilidade, Pontuação, Listas, Comunicações, Tarefas, Visitas, RGPD e Auditoria.

Os tipos `contract`, `maintenance_request`, `inspection`, `complaint`, `support_ticket` e `contest` ficaram preparados no resolver como suporte estrutural, sem ecrãs profundos nesta sprint.

## 6. Timeline

A timeline da candidatura reutiliza `ProcessTimelineBuilder` e agrega, quando autorizado:

- eventos processuais existentes;
- Work Tasks relacionadas;
- auditoria relacionada.

Dados sensíveis são minimizados. Identificadores numéricos longos e paths privados são mascarados antes de renderizar.

## 7. Checklist

A checklist é derivada dos dados existentes e não grava novas decisões. Estados usados:

- `completed`;
- `pending`;
- `warning`;
- `not_applicable`.

Itens implementados: dados do candidato, agregado, rendimentos, documentos obrigatórios, elegibilidade, pontuação, visitas, audiência, reclamações, lista e contrato.

## 8. Próxima ação

A próxima ação é sugestiva e operacional. Não executa decisões automáticas nem altera estado. O botão principal só aparece quando:

- a rota existe;
- o utilizador tem permissão;
- o perfil não é auditor;
- a ação faz sentido face aos dados existentes.

## 9. Progresso visual

Foi criado progresso visual para candidatura:

Recebida → Documentação → Elegibilidade → Pontuação → Lista Provisória → Audiência/Reclamações → Lista Definitiva → Atribuição → Contrato → Inquilino.

O progresso é apenas leitura e não altera workflows.

## 10. Pesquisa contextual

Foi criada pesquisa local no contexto carregado do processo. Nesta versão pesquisa apenas timeline, checklist e tabs autorizados. Não há indexação externa nem motor universal profundo.

## 11. Integração com Workspaces/Dashboard

- `WorkspaceResolver` reconhece rotas `backoffice.cases.applications.*` como Atendimento.
- `BreadcrumbService` gera breadcrumb para Case Workspace.
- Workspaces, dashboard, favoritos e recentes continuam sem alteração funcional.

## 12. Segurança/RBAC

Controlo aplicado:

- candidato não acede ao Case Workspace de backoffice;
- support_agent sem permissão não abre candidatura como caso;
- auditor tem leitura sem ação mutável;
- tabs, links rápidos e próxima ação são filtrados por permissão e existência de rota;
- nenhuma policy existente foi enfraquecida.

## 13. RGPD/minimização

O Case Workspace não expõe:

- NIF;
- morada privada;
- documentos privados;
- paths internos;
- dados financeiros indevidos;
- notas internas sensíveis fora de tabs autorizados.

Links documentais continuam a usar rotas protegidas existentes.

## 14. Performance

Medidas aplicadas:

- `loadMissing` para relações necessárias da candidatura;
- timeline limitada;
- Work Tasks e auditoria limitadas;
- sem `all()` em tabelas operacionais;
- views sem queries complexas;
- filtering de tabs/links antes de renderizar.

## 15. Testes executados

| Comando | Resultado |
| --- | --- |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `./vendor/bin/pint --test` | PASS |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | PASS — 509 testes, 3199 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UX` | PASS — 35 testes, 169 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter CaseWorkspace` | PASS — 9 testes, 46 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter ApplicationCase` | PASS — 1 teste, 9 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Timeline` | PASS — 4 testes, 17 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Checklist` | PASS — 13 testes, 92 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter NextAction` | PASS — 4 testes, 10 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security` | PASS — 56 testes, 350 assertions |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd` | PASS — 25 testes, 163 assertions |
| `php artisan migrate:status` | PASS |
| `php artisan route:list --except-vendor` | PASS |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v` | PASS — 0 erros |
| `npm run build` | PASS |
| `git diff --check` | PASS |

## 16. Evidências

Evidências locais geradas:

- `storage/qa/ux-03-composer.txt`
- `storage/qa/ux-03-optimize-clear.txt`
- `storage/qa/ux-03-pint.txt`
- `storage/qa/ux-03-phpunit.txt`
- `storage/qa/ux-03-ux-tests.txt`
- `storage/qa/ux-03-case-workspace-tests.txt`
- `storage/qa/ux-03-application-case-tests.txt`
- `storage/qa/ux-03-timeline-tests.txt`
- `storage/qa/ux-03-checklist-tests.txt`
- `storage/qa/ux-03-next-action-tests.txt`
- `storage/qa/ux-03-security-tests.txt`
- `storage/qa/ux-03-rgpd-tests.txt`
- `storage/qa/ux-03-migrate-status.txt`
- `storage/qa/ux-03-route-list.txt`
- `storage/qa/ux-03-phpstan.txt`
- `storage/qa/ux-03-build.txt`
- `storage/qa/ux-03-diff-check.txt`

## 17. Riscos residuais

- Os restantes tipos de caso ficaram estruturados no resolver, mas não têm ecrãs profundos nesta sprint.
- A pesquisa contextual é local e limitada ao conteúdo já carregado.
- A timeline depende da qualidade dos eventos já registados pelos módulos existentes.
- A integração de favoritos/recentes para casos específicos fica preparada pela arquitetura, mas sem persistência adicional dedicada a recursos de caso.

## 18. Decisão final

PASS.

A UX-03 cumpre os critérios definidos para Case Workspace de candidatura, mantém UX-01/UX-02, preserva RBAC/RGPD, não altera regras de negócio e passa os quality gates exigidos.
