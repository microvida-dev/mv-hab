# QA-35 — Visitas e Atendimento ao Candidato

## 1. Sumário executivo

A QA-35 validou e reforçou o fluxo de visitas, reagendamentos, cancelamentos, falta de comparência, tickets de apoio, FAQ pública/contextual, integração com Work Tasks/SLA e proteção RGPD.

Decisão final: **PASS_WITH_ACCEPTED_RISKS**.

Riscos aceites:

- sem integração externa de calendário nesta sprint;
- sem envio externo real de notificações;
- sem votação pública útil/não útil persistida para FAQ, porque o modelo atual de FAQ contextual ainda não contém esses contadores;
- sem automatismos de publicação de FAQ a partir de tickets recorrentes, por regra de controlo editorial humano.

## 2. Dependências QA-30/QA-31/QA-32/QA-33/QA-34

Base confirmada por histórico Git:

- QA-30: roles, perfis, equipas, MFA e auditoria de acessos.
- QA-31: Work Tasks, SLA, histórico e dashboard operacional.
- QA-32: segurança/RGPD, MFA, sessões e auditoria sensível.
- QA-33: IA documental avançada sem decisão automática.
- QA-34: portal público avançado e oferta habitacional pública.

## 3. Estado inicial de visitas/atendimento/FAQ

| Área | Estado inicial | Ação QA-35 |
| --- | --- | --- |
| Visitas do candidato | Existiam `HousingVisit`, slots, disponibilidade, cancelamento e reagendamento. | Reforçada integração Work Tasks, motivo obrigatório e no-show. |
| Backoffice de visitas | Existia calendário/listagem e ações principais. | Rotas movidas para grupo backoffice institucional que inclui `support_agent`; adicionada ação no-show. |
| Tickets | Existiam `SupportTicket`, mensagens, anexos privados e policies. | Criadas categorias sensíveis e Work Tasks por competência. |
| FAQ | Existia FAQ contextual e FAQ pública estática. | FAQ pública passou a usar `ContextualFaq` publicado com fallback institucional. |
| Work Tasks | Motor QA-31 existia. | Visitas e tickets passaram a criar tarefas idempotentes. |
| Segurança/RGPD | Policies e storage privado já existiam. | Reforçada filtragem de tickets sensíveis por role. |

## 4. Visitas

Regras validadas:

- candidato solicita visita a fogo publicável;
- visita a fogo não publicável é bloqueada;
- visita duplicada ativa para o mesmo slot é bloqueada;
- reagendamento exige motivo;
- cancelamento exige motivo;
- conclusão exige nota interna;
- histórico é preservado em `housing_visit_status_histories`;
- auditoria é criada via `AuditLogger`/`AuditTrailService`.

Correções:

- `VisitBookingService` cria Work Task `visit_schedule`;
- `VisitReschedulingService` reutiliza a mesma origem `housing_visit:{id}` para não duplicar tarefa ativa;
- `RescheduleVisitRequest` exige `reason`.

## 5. Reagendamentos/cancelamentos/no-show

Reforços:

- reagendamento exige justificação;
- conclusão exige nota interna;
- recusa exige motivo;
- nova ação backoffice `backoffice.housing-visits.no-show`;
- falta de comparência grava `VisitStatus::Missed`, histórico, interação do candidato, auditoria e notificação interna.

Ficheiros principais:

- `app/Services/Visits/VisitBookingService.php`
- `app/Services/Visits/VisitReschedulingService.php`
- `app/Http/Controllers/Backoffice/HousingVisitController.php`
- `app/Http/Requests/CompleteVisitRequest.php`
- `app/Http/Requests/RejectVisitRequest.php`
- `app/Http/Requests/RescheduleVisitRequest.php`
- `resources/views/backoffice/housing-visits/show.blade.php`

## 6. Calendário interno

O calendário interno existente foi preservado:

- `VisitAvailability`;
- `VisitSlot`;
- `VisitCalendarService`;
- rotas `backoffice.visit-availabilities.*`;
- rotas `backoffice.visit-slots.*`.

Não foi criada integração Google/Outlook nem armazenamento de credenciais externas.

## 7. Tickets

Reforços:

- novas categorias: `legal`, `contract`, `payment`, `maintenance`;
- tickets gerais geram Work Task `support_ticket`;
- tickets RGPD geram `rgpd_request`;
- tickets financeiros geram `payment_review`;
- tickets contratuais geram `contract_review`;
- tickets jurídicos geram `complaint_review`;
- tickets de manutenção geram `maintenance_triage`;
- tickets de documentos/elegibilidade geram tarefas próprias.

Metadata enviada para Work Tasks:

- identificadores técnicos;
- categoria;
- origem `candidate_portal`;
- sem assunto, descrição ou dados pessoais desnecessários.

## 8. Mensagens/anexos/notas internas

Validações:

- candidato cria ticket e responde;
- técnico responde;
- notas internas ficam ocultas do candidato;
- anexos continuam privados e protegidos por policy;
- tickets fechados permanecem auditáveis através do histórico de mensagens/auditoria.

## 9. FAQ dinâmica

Reforços:

- `PublicFaqController` consulta `ContextualFaq` com `context_key = public`;
- pesquisa por texto;
- filtro técnico por categoria;
- entradas inativas ou de outro contexto não aparecem publicamente;
- fallback institucional preservado quando não existem FAQs dinâmicas;
- pesquisa sem resultados mostra estado controlado.

Não foi implementada publicação automática de sugestões de FAQ a partir de tickets.

## 10. Integração Work Tasks/SLA

Validações:

- visita solicitada cria `visit_schedule`;
- reagendamento reutiliza a tarefa ativa da visita;
- ticket geral cria `support_ticket`;
- ticket RGPD cria `rgpd_request`;
- ticket financeiro cria `payment_review`;
- tarefa fica atribuída à equipa competente quando existe membro ativo compatível;
- SLA é herdado de `WorkTaskSlaService`;
- metadata é minimizada.

## 11. Notificações

Preservado:

- notificações internas de visita solicitada, confirmada, reagendada, cancelada e concluída.

Adicionado:

- `visit_no_show` em `InteractionType`;
- `visit_no_show` em `OfficialNotificationType`;
- notificação interna de falta de comparência.

Sem envio externo real nesta sprint.

## 12. Dashboards

Correção implementada:

- `resources/views/backoffice/support-tickets/index.blade.php` deixou de tentar renderizar arrays de indicadores diretamente como texto;
- indicadores agregados em array são somados para apresentação compacta.

As listagens continuam paginadas.

## 13. Segurança/RGPD

Reforços:

- `SupportTicketPolicy` bloqueia categorias sensíveis a perfis incompatíveis;
- `SupportTicket::visibleToBackofficeUser()` filtra tickets sensíveis antes de renderizar a listagem;
- `support_agent` não consulta tickets financeiros/jurídicos;
- gestor financeiro consulta tickets financeiros;
- auditor consulta tickets sensíveis sem mutação;
- candidato só vê os seus recursos próprios;
- tickets/visitas não expõem documentos privados nem paths internos.

## 14. Policies/Gates

Policies analisadas/reforçadas:

- `HousingVisitPolicy`;
- `SupportTicketPolicy`;
- `SupportTicketMessagePolicy`;
- `SupportTicketAttachmentPolicy`;
- `ContextualFaqPolicy`;
- `WorkTaskPolicy`.

Rotas operacionais de visitas/tickets/FAQ foram movidas para o grupo backoffice institucional que inclui perfis QA-30, mantendo autorização fina nos controllers/policies.

## 15. Testes executados

Testes novos:

- `tests/Feature/QA35VisitsCandidateSupportTest.php`
- `tests/Feature/Backoffice/HousingVisitManagementTest.php`
- `tests/Feature/Candidate/SupportTicketFlowTest.php`
- `tests/Feature/Public/FaqPublicTest.php`
- `tests/Feature/Security/VisitsAndSupportAuthorizationTest.php`
- `tests/Feature/Workflows/VisitsSupportWorkTaskIntegrationTest.php`

Resultados:

| Comando | Resultado |
| --- | --- |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `./vendor/bin/pint --test` | PASS |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA35` | PASS, 3 testes, 24 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter HousingVisit` | PASS, 3 testes, 17 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter SupportTicket` | PASS, 1 teste, 8 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Faq` | PASS, 4 testes, 25 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security` | PASS, 40 testes, 243 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter WorkTask` | PASS, 22 testes, 76 asserções |
| `php artisan route:list --except-vendor` | PASS, 1119 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v` | PASS, 0 erros |
| `npm run build` | PASS |
| `git diff --check` | PASS |

## 16. Evidências

Artefactos locais:

- `storage/qa/qa-35-composer.txt`
- `storage/qa/qa-35-optimize-clear.txt`
- `storage/qa/qa-35-pint.txt`
- `storage/qa/qa-35-qa35-tests.txt`
- `storage/qa/qa-35-visits-tests.txt`
- `storage/qa/qa-35-support-tests.txt`
- `storage/qa/qa-35-faq-tests.txt`
- `storage/qa/qa-35-security-tests.txt`
- `storage/qa/qa-35-work-task-tests.txt`
- `storage/qa/qa-35-route-list.txt`
- `storage/qa/qa-35-phpstan.txt`
- `storage/qa/qa-35-build.txt`
- `storage/qa/qa-35-diff-check.txt`

## 17. Riscos residuais

| Risco | Mitigação |
| --- | --- |
| Sem integração externa de calendário. | Mantido fora de âmbito por regra QA-35; fluxo interno funciona. |
| Sem notificações externas reais. | Notificações internas preservadas; canais externos dependem de configuração futura explícita. |
| FAQ pública não tem feedback útil/não útil persistido. | Modelo atual não contém contadores; evolução futura deve adicionar migration própria. |
| Sugestões automáticas de FAQ a partir de tickets não publicam conteúdo. | Risco aceite; publicação automática seria insegura sem revisão humana. |

## 18. Decisão final

**PASS_WITH_ACCEPTED_RISKS**

A experiência de candidato fica validada para visitas, reagendamentos, cancelamentos, falta de comparência, tickets, FAQ dinâmica, Work Tasks, SLA, auditoria e ownership, sem alterar regras de elegibilidade, scoring, ranking, listas, contratos, rendas, pagamentos ou IA documental.
