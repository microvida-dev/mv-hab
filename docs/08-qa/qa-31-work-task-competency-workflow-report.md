# QA-31 — Gestão de Tarefas e Workflow por Competência

## 1. Sumário executivo

QA-31 implementou e validou uma caixa de trabalho municipal para tarefas operacionais por competência, com SLA, histórico, auditoria, dashboard e proteção de autorização.

Decisão final: **PASS_WITH_ACCEPTED_RISKS**.

Risco aceite: os pontos de criação idempotente estão implementados em `WorkTaskCreationService`, mas a ligação automática a todos os eventos de domínio existentes ficou preparada e documentada, sem listeners profundos, para não alterar fluxos críticos já validados nas QA-22 a QA-30.

## 2. Dependência da QA-30

A implementação usa a base QA-30:

- roles institucionais preservadas;
- `MunicipalTeam`;
- `municipal_team_user`;
- permissões RBAC em `config/mvhab.php`;
- middlewares `active.backoffice`, `mfa.backoffice` e `log.backoffice`;
- auditoria em `AuditTrailService`.

Não foram removidas roles, permissões ou policies existentes.

## 3. Modelo de tarefas

Foram criados:

- `app/Models/WorkTask.php`;
- `app/Models/WorkTaskHistory.php`;
- `app/Models/WorkTaskSlaPolicy.php`;
- migration `2026_06_24_000031_create_work_task_workflow_tables.php`;
- factories de suporte.

Campos principais validados:

- tipo;
- origem;
- entidade relacionada por morph;
- prioridade;
- estado;
- equipa municipal;
- responsável;
- prazo;
- datas de atribuição, conclusão e cancelamento;
- motivos de reatribuição/cancelamento;
- metadata minimizada;
- criador/atualizador.

Histórico: `work_task_histories` é imutável por model events de update/delete.

## 4. Tipos e estados

Tipos mínimos implementados:

- `document_review`;
- `eligibility_review`;
- `scoring_review`;
- `complaint_review`;
- `hearing_review`;
- `contract_review`;
- `rent_review`;
- `payment_review`;
- `maintenance_triage`;
- `inspection_schedule`;
- `visit_schedule`;
- `support_ticket`;
- `rgpd_request`;
- `audit_review`.

Estados mínimos implementados:

- `pending`;
- `assigned`;
- `in_analysis`;
- `waiting_candidate`;
- `waiting_internal`;
- `waiting_external`;
- `completed`;
- `cancelled`;
- `overdue`.

## 5. Motor de atribuição por competência

Serviço: `app/Services/Workflows/WorkTaskAssignmentService.php`.

Matriz implementada:

| Tipo | Equipa | Perfis |
| --- | --- | --- |
| `document_review` | Gabinete Técnico | `municipal_technician` |
| `complaint_review` | Gabinete Jurídico | `legal_manager`, `jury` |
| `hearing_review` | Gabinete Jurídico | `legal_manager` |
| `contract_review` | Gabinete Jurídico, Gabinete de Habitação | `legal_manager`, `housing_manager` |
| `rent_review`, `payment_review` | Gabinete Financeiro | `financial_manager` |
| `maintenance_triage` | Manutenção | `maintenance_manager` |
| `inspection_schedule` | Vistorias | `inspection_manager` |
| `visit_schedule` | Atendimento, Gabinete de Habitação | `support_agent`, `housing_manager` |
| `support_ticket` | Atendimento | `support_agent` |
| `rgpd_request`, `audit_review` | Auditoria | `auditor`, `administrator` |

Validações:

- não atribui a utilizador inativo;
- não atribui a equipa inativa;
- não atribui a perfil incompatível;
- se não existir utilizador compatível, a tarefa fica na fila da equipa;
- se não existir equipa ativa, a tarefa fica pendente sem equipa.

## 6. Caixa de trabalho

Rotas criadas:

- `/backoffice/work-tasks`;
- `/backoffice/work-tasks/my`;
- `/backoffice/work-tasks/team`;
- `/backoffice/work-tasks/overdue`;
- `/backoffice/work-tasks/dashboard`;
- `/backoffice/work-tasks/{workTask}`;
- ações de claim, reassign e status.

Controllers:

- `WorkTaskController`;
- `WorkTaskDashboardController`.

Views:

- `resources/views/backoffice/work-tasks/index.blade.php`;
- `resources/views/backoffice/work-tasks/show.blade.php`;
- `resources/views/backoffice/work-tasks/dashboard.blade.php`.

## 7. SLA

Serviço: `WorkTaskSlaService`.

SLA por omissão:

- 5 dias úteis: revisão documental, apoio, manutenção e revisões técnicas;
- 10 dias úteis: reclamações, audiência, contratos, rendas, pagamentos, vistorias e visitas;
- 15 dias úteis: RGPD e auditoria.

Job idempotente:

- `app/Jobs/MarkOverdueWorkTasksJob.php`.

Validações:

- cálculo de `due_at` por dias úteis;
- marcação de vencidas;
- tarefas concluídas não regressam a vencidas;
- evento interno de prazo próximo preparado.

## 8. Alertas

Eventos adicionados com payload mínimo:

- `WorkTaskCreated`;
- `WorkTaskAssigned`;
- `WorkTaskDueSoon`;
- `WorkTaskOverdue`;
- `WorkTaskReassigned`;
- `WorkTaskCompleted`;
- `WorkTaskCancelled`.

Não foram adicionadas integrações externas. Email e canais externos ficaram fora do âmbito por regra da sprint.

## 9. Reatribuição e escalação

Reatribuição implementada em service:

- exige justificação;
- cria histórico;
- cria auditoria;
- bloqueia utilizador incompatível;
- bloqueia equipa inativa;
- permite devolver à fila da equipa.

Escalação operacional fica suportada por reatribuição para responsável/equipa competente; não foi criado fluxo hierárquico adicional.

## 10. Dashboard

Serviço: `WorkTaskDashboardService`.

Indicadores:

- contagens por estado;
- vencidas;
- a vencer;
- concluídas nos últimos 30 dias;
- taxa de cumprimento SLA;
- carga por equipa;
- carga por utilizador.

Implementação usa agregações SQL e paginação nas listagens.

## 11. Auditoria

Eventos auditados:

- `work_task_created`;
- `work_task_assigned`;
- `work_task_claimed`;
- `work_task_started`;
- `work_task_waiting`;
- `work_task_reassigned`;
- `work_task_completed`;
- `work_task_cancelled`;
- `work_task_overdue`.

Cada evento regista:

- actor;
- tarefa;
- equipa anterior/nova;
- utilizador anterior/novo;
- estado anterior/novo;
- justificação/nota;
- timestamp.

Dados privados e documentos não são copiados para tarefas.

## 12. Segurança/RGPD

Controlo implementado:

- `WorkTaskPolicy`;
- `WorkTaskAssignmentPolicy`;
- `WorkTaskDashboardPolicy`;
- novas permissões `work_tasks.*`;
- routes protegidas por autenticação, role, utilizador ativo, MFA e logging backoffice;
- visibilidade por utilizador/equipa salvo perfis superiores e auditoria;
- candidato sem acesso a rotas de tarefas;
- auditor consulta sem mutação;
- `support_agent` bloqueado em tarefas jurídicas/financeiras incompatíveis.

Minimização:

- metadata filtra chaves sensíveis;
- ficha de tarefa mostra apenas tipo/id da entidade relacionada;
- documentos privados não são expostos.

## 13. Testes executados

Testes novos:

- `tests/Feature/QA31WorkTaskCompetencyWorkflowTest.php`;
- `tests/Feature/Backoffice/WorkTaskInboxTest.php`;
- `tests/Feature/Backoffice/WorkTaskAssignmentTest.php`;
- `tests/Feature/Backoffice/WorkTaskSlaTest.php`;
- `tests/Feature/Backoffice/WorkTaskDashboardTest.php`;
- `tests/Feature/Security/WorkTaskAuthorizationTest.php`;
- `tests/Unit/Workflows/WorkTaskAssignmentServiceTest.php`;
- `tests/Unit/Workflows/WorkTaskSlaServiceTest.php`.

Resultados:

- `QA31`: PASS, 2 testes, 16 assertions;
- `WorkTask`: PASS, 19 testes, 56 assertions;
- `Workflow`: PASS, 15 testes, 89 assertions;
- `Security`: PASS, 25 testes, 150 assertions;
- PHPUnit completo: PASS, 331 testes, 2027 assertions.

## 14. Evidências

Evidências locais:

- `storage/qa/qa-31-composer.txt`;
- `storage/qa/qa-31-optimize-clear.txt`;
- `storage/qa/qa-31-pint.txt`;
- `storage/qa/qa-31-qa31-tests.txt`;
- `storage/qa/qa-31-work-task-tests.txt`;
- `storage/qa/qa-31-assignment-tests.txt`;
- `storage/qa/qa-31-sla-tests.txt`;
- `storage/qa/qa-31-dashboard-tests.txt`;
- `storage/qa/qa-31-workflow-tests.txt`;
- `storage/qa/qa-31-security-tests.txt`;
- `storage/qa/qa-31-phpunit-full.txt`;
- `storage/qa/qa-31-route-list.txt`;
- `storage/qa/qa-31-phpstan.txt`;
- `storage/qa/qa-31-build.txt`;
- `storage/qa/qa-31-diff-check.txt`.

## 15. Riscos residuais

- Listeners automáticos para todos os eventos de domínio devem ser ligados faseadamente em sprint própria, com testes por fluxo de origem.
- Notificações in-app foram preparadas por eventos, mas sem UI de notificações específica para tarefas.
- Dashboard mostra ids internos de equipa/utilizador nas agregações para evitar expor dados pessoais; pode evoluir para labels autorizadas.
- O dashboard global legacy continua fora do âmbito de novos perfis institucionais, mantendo a decisão QA-30 de não abrir rotas antigas.

## 16. Critérios de aceitação

Cumprido:

- tarefas criadas por ponto de integração seguro;
- criação idempotente;
- encaminhamento por competência;
- equipas e perfis QA-30 respeitados;
- caixa de trabalho por utilizador/equipa;
- SLA calculado e monitorizado;
- tarefas vencidas identificadas;
- reatribuições exigem justificação;
- histórico preservado;
- auditoria criada;
- dashboard operacional existente;
- dados privados minimizados;
- PHPUnit passou;
- Pint passou;
- PHPStan passou com 0 erros;
- build passou;
- route:list passou;
- git diff --check passou.

## 17. Decisão final

**PASS_WITH_ACCEPTED_RISKS**

QA-31 fica apta para staging/demo sobre a base QA-30, com módulo operacional de tarefas por competência implementado, testado e auditável. A ativação automática massiva por eventos de domínio deve ser feita incrementalmente para não alterar fluxos críticos já estabilizados.
