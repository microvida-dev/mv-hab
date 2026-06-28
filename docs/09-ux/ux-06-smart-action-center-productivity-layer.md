# UX-06 — Smart Action Center & Productivity Layer

## 1. Estado herdado UX-01

A base de navegação por Workspaces foi preservada. A UX-06 reutiliza o `WorkspaceService`, `WorkspaceResolver`, `BreadcrumbService`, `FavoritesService`, `RecentItemsService` e os componentes de navegação já existentes.

Alteração incremental:

- adicionada a entrada contextual `Produtividade` no workspace `Gestão`;
- mantidos `Workspaces`, favoritos, recentes e breadcrumbs;
- nenhuma rota existente foi removida.

## 2. Estado herdado UX-02

O Dashboard Inteligente por Perfil foi preservado. A UX-06 adiciona blocos compactos de produtividade ao `/dashboard` apenas para perfis backoffice autorizados:

- próximo processo sugerido;
- resumo de inbox municipal;
- centro de trabalho compacto.

O candidato continua a ser redirecionado para o dashboard de candidato e não recebe widgets de backoffice.

## 3. Estado herdado UX-03

O Case Workspace não foi alterado. A recomendação de próximo processo aponta para destinos autorizados existentes, com foco em Work Tasks. Não executa ações nem altera estado.

## 4. Estado herdado UX-04

A UX-06 reutiliza o Design System Municipal:

- `x-ui.card`;
- `x-ui.status-badge`;
- `x-ui.action-button`;
- `x-ui.empty-state`;
- `x-ui.section-header`;
- tokens e classes de foco existentes.

Não foi introduzida framework visual nova.

## 5. Estado herdado UX-05

A Pesquisa Universal e o Centro de Comandos foram preservados. Foi adicionado o comando autorizado `Abrir produtividade`, condicionado por `work_tasks.view` e rota existente.

## 6. Smart Action Center

Implementado em:

- `app/Services/Productivity/SmartActionCenterService.php`;
- `resources/views/components/productivity/action-center.blade.php`.

Secções suportadas:

- Hoje;
- Em atraso;
- A vencer;
- Esta semana;
- Concluído hoje;
- Sem responsável;
- Bloqueados.

As secções são derivadas de Work Tasks visíveis pelo utilizador, através de `WorkTaskDashboardService::visibleQuery()`. Não há criação de estados nem mutação de processos.

## 7. My Work

Implementado em:

- `app/Services/Productivity/MyWorkService.php`;
- `resources/views/components/productivity/my-work.blade.php`.

Grupos:

- Atribuído a mim;
- Fila da minha equipa;
- Pendências operacionais.

Ordenação: vencidas, prioridade, prazo.

## 8. Municipal Inbox

Implementado em:

- `app/Services/Productivity/MunicipalInboxService.php`;
- `app/Services/Productivity/OperationalNotificationService.php`;
- `resources/views/components/productivity/inbox.blade.php`.

Fonte: `OfficialNotification` existente.

Categorias:

- Operacional;
- Sistema;
- Segurança;
- RGPD.

O corpo e assunto da notificação não são renderizados na camada de produtividade.

## 9. Smart Queue

Implementado em:

- `app/Services/Productivity/SmartQueueService.php`;
- `resources/views/components/productivity/smart-queue.blade.php`.

Filas:

- Urgente;
- Hoje;
- Esta semana;
- Sem responsável;
- Bloqueados;
- Em atraso.

Os critérios estão documentados no próprio payload de cada fila e usam apenas campos existentes.

## 10. Workload

Implementado em:

- `app/Services/Productivity/WorkloadService.php`;
- `resources/views/components/productivity/workload.blade.php`.

Mostra apenas agregados por técnico/equipa:

- total de itens;
- vencidas;
- a vencer;
- carga relativa.

Não mostra dados pessoais de candidatos, documentos, NIF, moradas, rendimentos ou dados financeiros.

## 11. Deadline/SLA Indicators

Implementado em:

- `app/Services/Productivity/DeadlineIndicatorService.php`;
- `resources/views/components/productivity/deadline-indicator.blade.php`.

Estados apresentados:

- Dentro do prazo;
- A vencer;
- Em atraso;
- Sem prazo;
- Concluído.

A lógica apenas apresenta o estado visual com base em `due_at`, `completed_at` e `status` existentes.

## 12. Next Case

Implementado em:

- `app/Services/Productivity/NextCaseService.php`;
- `resources/views/components/productivity/next-case.blade.php`.

Critérios:

- estado vencido;
- prioridade;
- atribuição ao utilizador;
- prazo.

A recomendação é apenas sugestiva e não altera workflows, decisões ou estados.

## 13. Batch Toolbar

Implementado em:

- `resources/views/components/productivity/batch-toolbar.blade.php`.

Funcionalidade atual:

- seleção visual;
- contagem selecionada;
- botões preparados;
- ações críticas/destrutivas indisponíveis.

Não existem ações de apagar, aprovar, rejeitar, publicar, alterar scoring, ranking, contratos ou pagamentos.

## 14. Integração com Design System

Criados componentes em `resources/views/components/productivity/`.

Página dedicada:

- `app/Http/Controllers/Backoffice/ProductivityController.php`;
- `resources/views/productivity/index.blade.php`;
- rota `backoffice.productivity.index`.

Integração compacta no dashboard principal:

- `app/Http/Controllers/DashboardController.php`;
- `resources/views/dashboard.blade.php`.

## 15. Segurança/RBAC

Autorização central:

- `app/Services/Productivity/ProductivityAuthorizationService.php`.

Regras:

- candidato não recebe produtividade backoffice;
- auditor tem camada apenas de consulta;
- rotas inexistentes são ignoradas;
- dados são filtrados antes de renderizar;
- componentes Blade recebem payload minimizado.

## 16. RGPD

Minimização aplicada:

- Work Tasks apresentam tipo, número, estado, prioridade, equipa e prazo;
- Inbox não apresenta assunto/corpo;
- Workload apresenta apenas contagens;
- metadata de tarefas não é renderizada;
- documentos privados e paths internos não são renderizados.

Testes cobrem NIF, email, telefone, morada e paths privados em metadata.

## 17. Performance

Medidas aplicadas:

- queries baseadas em `visibleQuery()` existente;
- limites por secção/fila;
- `select` explícito para Work Tasks;
- eager loading herdado de `WorkTaskDashboardService`;
- sem `Model::all()`;
- sem queries em views.

Não foram criadas migrations.

## 18. Acessibilidade

Smoke coverage:

- `aria-live` no Centro de Trabalho e Inbox;
- foco visível;
- headings;
- botões com texto claro;
- estados vazios.

## 19. Testes executados

Evidências guardadas em `storage/qa`:

- `ux-06-composer.txt` — PASS;
- `ux-06-optimize-clear.txt` — PASS;
- `ux-06-pint.txt` — PASS;
- `ux-06-phpunit.txt` — PASS, 560 testes, 3427 assertions;
- `ux-06-ux-tests.txt` — PASS, 72 testes;
- `ux-06-productivity-tests.txt` — PASS, 12 testes;
- `ux-06-smart-action-tests.txt` — PASS;
- `ux-06-inbox-tests.txt` — PASS;
- `ux-06-queue-tests.txt` — PASS;
- `ux-06-workload-tests.txt` — PASS;
- `ux-06-security-tests.txt` — PASS;
- `ux-06-rgpd-tests.txt` — PASS;
- `ux-06-accessibility-tests.txt` — PASS;
- `ux-06-migrate-status.txt` — PASS;
- `ux-06-route-list.txt` — PASS;
- `ux-06-phpstan.txt` — PASS, 0 erros;
- `ux-06-build.txt` — PASS;
- `ux-06-diff-check.txt` — PASS.

## 20. Evidências

Rotas relevantes:

- `GET /dashboard`;
- `GET /backoffice/productivity`;
- `GET /backoffice/search`;
- `GET /backoffice/search/commands`;
- `GET /backoffice/work-tasks/*`.

Migrations relevantes confirmadas:

- `2026_06_11_030000_create_administrative_workflow_tables`;
- `2026_06_24_000031_create_work_task_workflow_tables`;
- `2026_06_27_000033_create_navigation_personalization_tables`.

## 21. Riscos residuais

- A UX-06 usa Work Tasks e notificações existentes como fontes principais. Outros domínios aparecem quando já estão refletidos nesses mecanismos operacionais.
- A batch toolbar é deliberadamente visual nesta versão para evitar mutações indevidas.
- Não foi criada integração externa de calendário, pesquisa externa ou IA.
- A recomendação de próximo processo é heurística simples e não substitui julgamento humano.

## 22. Decisão final

PASS

A UX-06 fecha a camada atual de produtividade sem alterar regras de negócio, workflows administrativos, RBAC, RGPD, auditoria, Work Tasks ou SLA.
