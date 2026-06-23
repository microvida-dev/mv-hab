# Sprint 23 — Acompanhamento Processual Avançado

## Prioridade de desenvolvimento

Esta sprint pertence à fase de transparência processual, melhoria da experiência do candidato e consolidação da rastreabilidade administrativa.

A Sprint 23 deve permitir que o candidato acompanhe permanentemente o estado do seu processo, compreenda a etapa em que se encontra, consulte o histórico cronológico, responda a pedidos de aperfeiçoamento, submeta documentação adicional, exerça direitos em audiência prévia, acompanhe notificações e reutilize dados em futuras candidaturas.

Esta sprint deve preservar e integrar os módulos existentes de:

```text id="154sxk"
Registo de adesão
Simulador avançado
Candidaturas
Gestão documental
Workflow administrativo
Pedidos de aperfeiçoamento
Notificações
Auditoria/RGPD
Helpdesk/tickets
FAQ contextual
Reutilização de dados
```

Os manuais e FAQ analisados demonstram a importância do acompanhamento contínuo pelo candidato, da clareza dos estados, da submissão assistida de documentos e da reutilização de dados em futuras candidaturas.

---

# 1. Objetivo da Sprint

Dar transparência total ao estado do processo.

Implementar:

```text id="fchzy3"
Timeline completa do processo
Estados detalhados
Histórico cronológico
Área de notificações
Recursos em audiência prévia
Submissão de documentação adicional
Pedidos de aperfeiçoamento
Desistência controlada
Reutilização automática dos dados em futuras candidaturas
```

A plataforma deve permitir que o candidato:

```text id="84b2ny"
Veja uma timeline completa do processo
Compreenda o estado atual da candidatura
Consulte histórico cronológico de eventos
Consulte notificações e prazos
Responda a audiência prévia ou recursos, quando aplicável
Submeta documentação adicional solicitada
Responda a pedidos de aperfeiçoamento
Desista da candidatura de forma controlada
Veja consequências da desistência antes de confirmar
Reutilize dados validados em futuras candidaturas
Acompanhe interações, visitas, tickets e documentos no mesmo histórico
```

A plataforma deve permitir que o Município:

```text id="2kwfly"
Configure estados públicos do processo
Mapeie estados internos para estados compreensíveis pelo candidato
Registe eventos processuais relevantes
Controle prazos de resposta
Consulte histórico integral do processo
Analise respostas em audiência prévia
Analise documentação adicional
Controle desistências
Audite cada ação crítica
Reutilize dados já validados quando permitido
Reduza pedidos repetidos de informação
```

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 23.

Não avances para Sprint 24 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash id="h94aqc"
git branch --show-current
```

Não interromper execução por causa da branch atual.

Antes de alterar código, lê, se existirem:

```text id="7xbs2w"
docs/architecture/technical-architecture.md
docs/architecture/data-model-overview.md
docs/product/product-vision.md
docs/product/functional-requirements.md
docs/product/user-roles.md
docs/product/process-workflows.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/backlog/roadmap.md

docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/sprint-5-agregado-rendimentos-situacao-habitacional.md
docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
docs/backlog/sprint-11-listas-provisorias-reclamacoes-audiencia.md
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
docs/backlog/sprint-18-rgpd-seguranca-auditoria-avancada.md
docs/backlog/sprint-19-testes-integrados-qualidade.md
docs/backlog/sprint-21-simulador-avancado-registo-inteligente.md
docs/backlog/sprint-22-candidaturas-visitas-apoio-candidato.md
docs/backlog/sprint-23-acompanhamento-processual-avancado.md

docs/candidate-experience/support-tickets.md
docs/candidate-experience/contextual-faq.md
docs/candidate-experience/simulation-application-inconsistencies.md
docs/qa/test-coverage-matrix.md
docs/qa/quality-gates.md
docs/qa/regression-test-plan.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

Não alterar `.env`.

Não introduzir credenciais.

Não usar dados pessoais reais.

Não criar integrações externas obrigatórias.

Não substituir o workflow administrativo existente; esta sprint deve criar camada de acompanhamento e transparência sobre o workflow existente.

---

# 3. PHPStan obrigatório antes de publicar — contexto com 2471 erros legados

O projeto tem atualmente:

```text id="4fdu8s"
2471 erros PHPStan legados
```

A Sprint 23 não tem como objetivo corrigir todos os erros legados.

A Sprint 23 tem como objetivo obrigatório:

```text id="c9i0ny"
Não aumentar o número de erros PHPStan.
Não introduzir novos erros PHPStan nos ficheiros criados ou alterados.
Identificar claramente erros legados versus erros introduzidos pela sprint.
Executar PHPStan antes da implementação e antes da publicação.
Corrigir todos os erros PHPStan diretamente causados pela Sprint 23.
```

## 3.1 Verificação PHPStan inicial

Antes de criar ou alterar ficheiros, executar, se PHPStan existir:

```bash id="xujntl"
mkdir -p storage/phpstan

php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint23-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint23-before.txt || true
```

Se existir `phpstan.neon`, usar:

```bash id="5y7fom"
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint23-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint23-before.txt || true
```

Se existir script no `composer.json`, usar também o comando do projeto, por exemplo:

```bash id="5jhnjp"
composer phpstan
```

Registar no relatório final:

```text id="3vbphp"
PHPStan inicial executado: sim/não
Total de erros legados conhecido: 2471
Ficheiro de output inicial criado
Comando usado
Falhou por memória: sim/não
Falhou por configuração: sim/não
```

Se PHPStan não existir, documentar:

```text id="7zagv7"
PHPStan/Larastan não está instalado/configurado. Não foi possível executar análise estática.
```

## 3.2 Estratégia para não misturar erros legados

Durante a implementação:

```text id="7pu8d0"
Não corrigir erros PHPStan fora do âmbito da Sprint 23, salvo se bloquearem diretamente a sprint.
Não alterar ficheiros apenas para reduzir ruído PHPStan legado.
Não criar baseline artificial sem autorização.
Não esconder erros novos com ignoreErrors genéricos.
Não adicionar @phpstan-ignore sem justificação objetiva.
Não reduzir o nível do PHPStan.
Não remover paths analisados.
Não alterar configuração PHPStan para ocultar problemas.
```

## 3.3 Verificação PHPStan antes de publicação

Antes de considerar a Sprint 23 pronta para publicação, executar:

```bash id="4ay95b"
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint23-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint23-after.txt || true
```

Com config, se existir:

```bash id="gohv75"
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint23-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint23-after.txt || true
```

Depois, identificar erros nos ficheiros criados ou alterados nesta sprint.

Se existirem erros PHPStan em ficheiros da Sprint 23:

```text id="pa2zjv"
Corrigir antes de concluir.
Não publicar como concluído enquanto houver erro novo causado pela Sprint 23.
```

Se existirem apenas os 2471 erros legados:

```text id="ylz21q"
Documentar que o passivo PHPStan legado permanece.
Confirmar que a Sprint 23 não adicionou erros novos nos ficheiros alterados.
```

Se a contagem aumentar:

```text id="k3z9js"
Identificar ficheiros novos/alterados.
Corrigir erros introduzidos.
Reexecutar PHPStan.
Documentar diferença.
```

---

# Estado de Execucao — 2026-06-19

## Implementado

- acompanhamento processual do candidato;
- timeline cronologica com eventos publicos e internos;
- snapshot de estado publico da candidatura;
- centro de notificacoes do candidato sobre `official_notifications`;
- submissao de pronuncia em audiencia previa com compatibilidade com o fluxo legado;
- submissao de documentacao adicional;
- resposta a pedidos de aperfeicoamento;
- desistência controlada;
- reutilizacao de dados para candidaturas futuras;
- area backoffice para timeline, estado publico, documentos adicionais, desistencias, reutilizacao e audiencias;
- factories, seeder demo, policies, Form Requests, services, views, routes e testes da sprint.

## Ficheiros documentais criados

- `docs/candidate-experience/process-tracking.md`
- `docs/candidate-experience/process-timeline.md`
- `docs/candidate-experience/notification-center.md`
- `docs/candidate-experience/preliminary-hearing-and-appeals.md`
- `docs/candidate-experience/additional-documents-and-corrections.md`
- `docs/candidate-experience/controlled-withdrawal.md`
- `docs/candidate-experience/future-application-data-reuse.md`
- `docs/qa/sprint-23-quality-report.md`

## Validação executada

- `composer validate`: OK;
- `php artisan route:list`: OK, 952 rotas;
- `php artisan migrate --pretend`: OK;
- `php artisan migrate`: OK;
- `php artisan test --filter=Sprint23ProcessTrackingTest`: OK, 6 testes e 24 assercoes;
- `php artisan test`: OK, 202 testes e 1282 assercoes;
- `npm run build`: OK;
- `./vendor/bin/pint`: OK apos formatacao automatica inicial;
- `./vendor/bin/pint --test`: OK;
- PHPStan executado antes e depois, mas terminou com codigo 1 sem output e com ficheiros de relatorio vazios.

## PHPStan

O passivo legado indicado para a sprint e de 2471 erros. Nesta execucao, PHPStan 2.2.2 esta instalado, mas os comandos `analyse` e `diagnose` terminaram com codigo 1 sem produzir stdout, stderr ou relatorios com conteudo.

Nao foram alterados `phpstan.neon`, nivel, baseline, paths analisados ou `ignoreErrors`.

## Riscos e pendencias

- corrigir a anomalia operacional do PHPStan antes de usar a contagem como gate conclusivo;
- validar juridicamente textos e efeitos de desistência, audiência e reutilizacao;
- rever estados publicos por municipio/concurso;
- executar teste de carga sobre timelines e notificacoes com volume realista;
- manter integracoes externas fora do ambito ate existirem condicoes municipais.

## 3.4 Resultado PHPStan obrigatório no relatório final

A resposta final deve incluir:

```text id="do1tw2"
Estado PHPStan inicial
Estado PHPStan antes de publicação
Contagem legada assumida: 2471
Novos erros introduzidos pela Sprint 23: sim/não
Erros PHPStan em ficheiros criados/alterados: sim/não
Correções PHPStan aplicadas
Bloqueia publicação: sim/não
```

---

# 4. Inspeção inicial obrigatória

Antes de implementar, identificar:

```text id="6ojj67"
Versão do Laravel
Versão do PHP
Stack frontend real
Sistema de autenticação
Sistema de área pessoal do candidato
Sistema de roles/permissões
Sistema de policies
Sistema de Form Requests
Sistema de notificações
Sistema de auditoria
Sistema de RGPD/consentimentos
Sistema de candidaturas
Sistema de workflow administrativo
Sistema de audiência prévia/reclamações/recursos
Sistema de pedidos de aperfeiçoamento
Sistema documental
Sistema de upload documental adicional
Sistema de desistência, se existir
Sistema de reutilização de dados
Sistema de tickets/visitas/interações
Sistema de FAQ contextual
Sistema de timeline/histórico, se existir
Sistema de testes
Configuração PHPStan/Larastan
Configuração Pint
Configuração PHPUnit/Pest
Comandos disponíveis em composer.json
Comandos disponíveis em package.json
```

Inspecionar os modelos existentes:

```text id="qgmqqr"
User
Citizen/Candidate, se existir
AdhesionRegistration
Household
HouseholdMember
IncomeRecord
CurrentHousingSituation
Application
ApplicationStatusHistory
ApplicationSnapshot
ApplicationPrefill
ApplicationWithdrawal
ApplicationAppeal
ApplicationComplaint
CorrectionRequest
CorrectionRequestResponse
DocumentSubmission
DocumentVersion
RequiredDocument
OfficialNotification
NotificationRead
CommunicationLog
SupportTicket
SupportTicketMessage
HousingVisit
CandidateInteraction
SimulationSession
ApplicationSimulationInconsistency
AuditEvent
SensitiveDataAccessLog
DataSubjectRequest
CandidateDataReuseProfile
RegistrationRenewal
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text id="v8xp85"
ProcessTimeline
ProcessTimelineEvent
ApplicationTimelineEvent
ApplicationPublicStatus
CandidateNotificationCenter
PreliminaryHearingResponse
AdditionalDocumentSubmission
CorrectionRequestResponse
ControlledWithdrawal
FutureApplicationDataReuse
CandidateProcessDashboard
```

reaproveitar ou adaptar com compatibilidade.

---

# 5. Dependências funcionais

Esta sprint depende preferencialmente de:

```text id="egupun"
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 11 — Listas Provisórias, Reclamações e Audiência
Sprint 16 — Notificações, Comunicações e Modelos Documentais
Sprint 18 — RGPD, Segurança e Auditoria Avançada
Sprint 21 — Simulador Avançado e Registo Inteligente
Sprint 22 — Candidaturas, Visitas e Apoio ao Candidato
```

Dependências mínimas:

```text id="tkffmj"
User
Application
ApplicationStatusHistory ou equivalente
DocumentSubmission ou equivalente
OfficialNotification ou equivalente
Área do candidato
Backoffice
```

Se a Sprint 9 existir:

```text id="0dtj2p"
Integrar pedidos de aperfeiçoamento existentes.
Não criar fluxo paralelo incompatível.
```

Se a Sprint 11 existir:

```text id="75ll9w"
Integrar audiência prévia, reclamações, recursos e listas.
Não duplicar modelos de reclamação se já existirem.
```

Se a Sprint 16 existir:

```text id="dz02pp"
Usar notificações existentes no centro de notificações.
```

Se a Sprint 21 existir:

```text id="n9z6zw"
Usar CandidateDataReuseProfile, ApplicationPrefill e RegistrationRenewal quando existirem.
```

Se a Sprint 22 existir:

```text id="fl5f36"
Integrar visitas, tickets, FAQ contextual e interações na timeline.
```

Se algum módulo não existir:

```text id="xa9o69"
Implementar camada tolerante a dependências parciais.
Documentar limitação.
Não inventar decisão administrativa inexistente.
Não criar workflow paralelo definitivo sem necessidade.
```

---

# 6. Validação funcional, administrativa e RGPD

Regras obrigatórias:

```text id="pj5y6v"
Candidato só vê processos próprios.
Candidato só vê notificações próprias.
Candidato só responde a pedidos dirigidos ao seu processo.
Candidato só submete documentação adicional no contexto permitido.
Candidato só desiste de candidatura própria e desistível.
Candidato deve confirmar desistência de forma explícita.
Desistência deve indicar consequências.
Histórico processual deve ser imutável ou append-only.
Eventos internos não destinados ao candidato não devem aparecer publicamente.
Timeline do candidato deve usar linguagem clara.
Timeline interna pode ter detalhe técnico superior.
A audiência prévia deve respeitar prazos e estados.
Documentação adicional deve ir para storage privado.
Reutilização de dados deve exigir confirmação quando os dados forem sensíveis ou desatualizados.
```

Copy obrigatório na timeline:

```text id="pkrlrm"
Esta timeline apresenta o histórico do seu processo com base nos atos registados na plataforma. Algumas etapas podem depender de validação documental, análise técnica ou decisão dos serviços municipais.
```

Copy obrigatório na desistência:

```text id="80abfv"
A desistência da candidatura pode impedir a continuação deste processo. Antes de confirmar, verifique as consequências aplicáveis ao concurso e confirme que pretende desistir.
```

Copy obrigatório na reutilização de dados:

```text id="jjou7s"
Os dados reutilizados devem ser revistos e confirmados antes de nova candidatura. Dados desatualizados, incompletos ou alterados podem afetar a elegibilidade, a pontuação ou a análise do processo.
```

---

# 7. Âmbito incluído

Implementar:

```text id="ko7nz0"
Timeline completa do processo
Estados detalhados
Mapeamento de estados internos para estados públicos
Histórico cronológico do processo
Área de notificações do candidato
Leitura/arquivo de notificações
Recursos/respostas em audiência prévia
Submissão de documentação adicional
Pedidos de aperfeiçoamento integrados
Resposta a pedidos de aperfeiçoamento
Desistência controlada
Reutilização automática dos dados em futuras candidaturas
Confirmação de dados reutilizados
Indicadores de dados reutilizados/desatualizados
Backoffice para consultar timeline
Backoffice para configurar estados públicos, se necessário
Backoffice para analisar respostas de audiência
Backoffice para analisar documentação adicional
Services
Controllers
Form Requests
Policies
Views/páginas
Rotas
Factories
Seeders
Testes
Documentação
PHPStan antes/depois
```

---

# 8. Fora de âmbito

Não implementar nesta sprint:

```text id="6qshik"
Novo motor de classificação
Novo motor de elegibilidade
Novo motor documental
Nova assinatura digital
Novo pagamento digital
Nova integração externa
Notificações SMS reais
Notificações e-mail reais sem configuração existente
Chat em tempo real
Reescrita completa do workflow administrativo
Nova área do inquilino
Gestão contratual
Atribuição de habitação
Sorteios
Geração jurídica final de atas
```

Esta sprint cria transparência e interação processual, não decisão administrativa final.

---

# 9. Fluxos funcionais obrigatórios

## 9.1 Consulta da timeline pelo candidato

```text id="ijy3g1"
Candidato entra na área pessoal
→ Acede ao processo/candidatura
→ Sistema carrega timeline pública
→ Sistema mostra estado atual
→ Sistema mostra próximos passos
→ Sistema mostra eventos cronológicos
→ Sistema mostra prazos ativos
→ Sistema mostra notificações relevantes
→ Sistema mostra ações disponíveis
```

## 9.2 Pedido de aperfeiçoamento

```text id="rjz1rq"
Técnico cria pedido de aperfeiçoamento ou usa pedido existente
→ Sistema gera evento de timeline
→ Sistema notifica candidato
→ Candidato consulta pedido
→ Candidato submete resposta e/ou documentos
→ Sistema regista resposta
→ Sistema atualiza estado
→ Técnico analisa
→ Sistema regista resultado na timeline
```

## 9.3 Audiência prévia / recurso

```text id="ujcm5f"
Sistema ou técnico identifica fase de audiência prévia
→ Candidato recebe notificação
→ Candidato consulta fundamento/motivo
→ Candidato submete pronúncia/recurso dentro do prazo
→ Pode anexar documentos adicionais
→ Sistema regista submissão
→ Técnico analisa
→ Decisão/resposta fica no histórico
```

## 9.4 Documentação adicional

```text id="p1xmkq"
Candidato recebe pedido ou tem ação disponível
→ Seleciona tipo documental
→ Faz upload de documento
→ Sistema valida ficheiro
→ Sistema guarda em storage privado
→ Sistema cria evento cronológico
→ Técnico consulta documentação adicional
```

## 9.5 Desistência controlada

```text id="ok0f4m"
Candidato consulta candidatura
→ Seleciona desistir
→ Sistema mostra consequências
→ Candidato confirma explicitamente
→ Sistema valida se estado permite desistência
→ Sistema regista desistência
→ Sistema bloqueia ações incompatíveis
→ Sistema cria evento de timeline
→ Sistema notifica backoffice
```

## 9.6 Reutilização de dados em futura candidatura

```text id="81x9tk"
Candidato inicia nova candidatura
→ Sistema identifica dados reutilizáveis
→ Sistema mostra origem dos dados
→ Sistema indica dados validados/desatualizados
→ Candidato confirma reutilização
→ Sistema pré-preenche dados permitidos
→ Sistema cria snapshot de origem
→ Candidato revê antes de submeter
```

---

# 10. Estados e tipos recomendados

## PublicProcessStatus

```text id="qlgsi4"
draft
registration_required
simulation_required
ready_to_apply
submitted
received
under_review
awaiting_documents
awaiting_correction
correction_submitted
awaiting_preliminary_hearing
preliminary_hearing_submitted
admitted
not_admitted
scoring
ranked
provisional_list_published
complaint_period
complaint_submitted
complaint_under_review
definitive_list_published
allocated
not_allocated
contract_pending
completed
withdrawn
cancelled
archived
```

## TimelineEventType

```text id="qrfrr7"
application_created
application_submitted
status_changed
document_uploaded
document_validated
document_rejected
additional_document_requested
additional_document_submitted
correction_requested
correction_submitted
notification_sent
notification_read
preliminary_hearing_opened
preliminary_hearing_submitted
complaint_submitted
complaint_decided
visit_scheduled
visit_completed
ticket_created
ticket_resolved
inconsistency_detected
withdrawal_requested
application_withdrawn
data_reused
application_prefilled
manual_note
system_event
```

## TimelineEventVisibility

```text id="ss9asb"
candidate_visible
backoffice_only
auditor_visible
system_only
```

## ProcessActionType

```text id="7k0ka5"
submit_documents
respond_correction
submit_preliminary_hearing
submit_complaint
schedule_visit
open_ticket
withdraw_application
reuse_data
confirm_data
view_notification
```

## ProcessActionStatus

```text id="1scowm"
available
completed
blocked
expired
not_applicable
pending_review
```

## NotificationCenterStatus

```text id="2s9v66"
unread
read
archived
expired
action_required
```

## AdditionalDocumentStatus

```text id="yltfj0"
draft
submitted
under_review
accepted
rejected
requires_replacement
cancelled
```

## PreliminaryHearingStatus

```text id="fdu43l"
not_applicable
open
submitted
under_review
accepted
rejected
partially_accepted
closed
expired
```

## ControlledWithdrawalStatus

```text id="2t5h61"
draft
pending_confirmation
confirmed
rejected
cancelled
completed
```

## DataReuseStatus

```text id="5wyvpi"
available
requires_confirmation
confirmed
applied
outdated
expired
blocked
```

---

# 11. Modelo de dados

## 11.1 ProcessTimelineEvent

Criar entidade:

```text id="u2cbf9"
ProcessTimelineEvent
```

Tabela:

```text id="vvrcxl"
process_timeline_events
```

Campos mínimos:

```text id="my1nmm"
id
event_number

user_id nullable
application_id nullable
adhesion_registration_id nullable
contest_id nullable
housing_unit_id nullable

event_type
visibility
public_status nullable
title
description
occurred_at
due_at nullable

related_type nullable
related_id nullable

metadata
created_by nullable
created_at
updated_at
```

Regras:

```text id="ssgv2q"
Eventos devem ser append-only sempre que possível.
Eventos candidate_visible aparecem ao candidato.
Eventos backoffice_only não aparecem ao candidato.
Não guardar dados sensíveis desnecessários em metadata.
```

## 11.2 ApplicationPublicStatusSnapshot

Criar entidade:

```text id="lbiaxq"
ApplicationPublicStatusSnapshot
```

Tabela:

```text id="kci4r9"
application_public_status_snapshots
```

Campos:

```text id="46c0qe"
id
application_id
public_status
internal_status
title
description
next_step
action_required
action_due_at nullable
progress_percentage nullable
is_terminal
created_at
updated_at
```

Objetivo:

```text id="hqcvk7"
Guardar estado público compreensível da candidatura sem expor detalhe interno indevido.
```

## 11.3 CandidateNotification

Criar se não existir centro compatível:

```text id="vzx6ud"
CandidateNotification
```

Tabela:

```text id="37axz2"
candidate_notifications
```

Campos:

```text id="7r5x6y"
id
notification_number
user_id
application_id nullable
contest_id nullable

type
status
title
message
action_label nullable
action_url nullable
read_at nullable
archived_at nullable
expires_at nullable

related_type nullable
related_id nullable

created_at
updated_at
deleted_at
```

Se já existir `OfficialNotification`, `Notification` ou `CommunicationLog`, reaproveitar e criar apenas camada de apresentação.

## 11.4 PreliminaryHearingSubmission

Criar ou adaptar entidade existente:

```text id="0g8g5y"
PreliminaryHearingSubmission
```

Tabela:

```text id="jyl1tw"
preliminary_hearing_submissions
```

Campos:

```text id="sfrp95"
id
submission_number
application_id
user_id
contest_id nullable

status
subject
body
submitted_at
reviewed_at nullable
reviewed_by nullable
decision nullable
decision_notes nullable

created_at
updated_at
deleted_at
```

Regras:

```text id="w294f8"
Submissão só permitida em janela/estado aplicável.
Deve aceitar anexos/documentos adicionais.
Deve criar evento de timeline.
```

## 11.5 AdditionalDocumentRequest

Criar ou adaptar se já existir pedido de aperfeiçoamento/documento:

```text id="we1arw"
AdditionalDocumentRequest
```

Tabela:

```text id="kj35j4"
additional_document_requests
```

Campos:

```text id="wld70m"
id
request_number
application_id
user_id
requested_by
document_type_id nullable

status
title
description
due_at nullable
submitted_at nullable
reviewed_at nullable
reviewed_by nullable
decision nullable
decision_notes nullable

created_at
updated_at
deleted_at
```

## 11.6 AdditionalDocumentSubmission

Criar ou adaptar:

```text id="ny02fp"
AdditionalDocumentSubmission
```

Tabela:

```text id="sc0ws2"
additional_document_submissions
```

Campos:

```text id="67rwb1"
id
additional_document_request_id nullable
application_id
user_id
document_submission_id nullable

status
title
description
submitted_at
reviewed_at nullable
reviewed_by nullable
review_notes nullable

created_at
updated_at
deleted_at
```

Regras:

```text id="jz28fi"
Ficheiros devem usar storage privado.
Pode integrar com DocumentSubmission existente.
```

## 11.7 ControlledWithdrawal

Criar entidade:

```text id="1j2dsi"
ControlledWithdrawal
```

Tabela:

```text id="mfi225"
controlled_withdrawals
```

Campos:

```text id="orh0b3"
id
withdrawal_number
application_id
user_id

status
reason
consequence_acknowledged
confirmed_at nullable
completed_at nullable
cancelled_at nullable
processed_by nullable
notes nullable

created_at
updated_at
deleted_at
```

Regras:

```text id="ia9op0"
Desistência exige confirmação explícita.
Não apagar candidatura.
Alterar estado via service.
Criar evento de timeline.
Auditar ação.
```

## 11.8 FutureApplicationDataReuse

Criar ou adaptar com CandidateDataReuseProfile/ApplicationPrefill se existir:

```text id="ifw509"
FutureApplicationDataReuse
```

Tabela:

```text id="2o8y8a"
future_application_data_reuses
```

Campos:

```text id="gmx3hn"
id
user_id
source_application_id nullable
target_application_id nullable
candidate_data_reuse_profile_id nullable

status
reused_sections
excluded_sections
warnings
confirmed_at nullable
applied_at nullable

created_at
updated_at
deleted_at
```

Secções possíveis:

```text id="m660n4"
personal_data
household
income
housing_situation
preferences
documents_summary
simulation_snapshot
```

Regras:

```text id="wboavk"
Não copiar documentos automaticamente sem validação.
Não copiar dados expirados sem alerta.
Não aplicar sem confirmação.
```

## 11.9 ProcessAction

Criar entidade opcional se útil:

```text id="cbd89s"
ProcessAction
```

Tabela:

```text id="2bgttr"
process_actions
```

Campos:

```text id="ub4gur"
id
application_id
user_id
action_type
status
title
description
action_url nullable
due_at nullable
completed_at nullable
blocked_reason nullable
metadata
created_at
updated_at
```

Objetivo:

```text id="5ekvt9"
Mostrar ao candidato ações pendentes, disponíveis ou expiradas.
```

---

# 12. Índices e performance

Adicionar índices seguros:

```text id="p8edcb"
process_timeline_events.event_number unique
process_timeline_events.user_id
process_timeline_events.application_id
process_timeline_events.event_type
process_timeline_events.visibility
process_timeline_events.occurred_at

application_public_status_snapshots.application_id
application_public_status_snapshots.public_status
application_public_status_snapshots.action_due_at

candidate_notifications.notification_number unique
candidate_notifications.user_id
candidate_notifications.application_id
candidate_notifications.status
candidate_notifications.expires_at

preliminary_hearing_submissions.submission_number unique
preliminary_hearing_submissions.application_id
preliminary_hearing_submissions.user_id
preliminary_hearing_submissions.status

additional_document_requests.request_number unique
additional_document_requests.application_id
additional_document_requests.user_id
additional_document_requests.status
additional_document_requests.due_at

additional_document_submissions.application_id
additional_document_submissions.user_id
additional_document_submissions.status

controlled_withdrawals.withdrawal_number unique
controlled_withdrawals.application_id
controlled_withdrawals.user_id
controlled_withdrawals.status

future_application_data_reuses.user_id
future_application_data_reuses.source_application_id
future_application_data_reuses.target_application_id
future_application_data_reuses.status

process_actions.application_id
process_actions.user_id
process_actions.action_type
process_actions.status
process_actions.due_at
```

Migrations devem ser reversíveis.

Não adicionar índices duplicados.

---

# 13. Services obrigatórios

Criar namespaces:

```text id="94c9lx"
App\Services\ProcessTracking
App\Services\CandidateNotifications
App\Services\ApplicationActions
App\Services\DataReuse
```

Criar services:

```text id="d5aqi2"
App\Services\ProcessTracking\ProcessTimelineService
App\Services\ProcessTracking\ProcessTimelineBuilder
App\Services\ProcessTracking\ApplicationPublicStatusService
App\Services\ProcessTracking\ProcessActionResolver
App\Services\ProcessTracking\ProcessHistoryFormatter

App\Services\CandidateNotifications\CandidateNotificationCenterService
App\Services\CandidateNotifications\CandidateNotificationReadService

App\Services\ApplicationActions\PreliminaryHearingSubmissionService
App\Services\ApplicationActions\AdditionalDocumentRequestService
App\Services\ApplicationActions\AdditionalDocumentSubmissionService
App\Services\ApplicationActions\CorrectionRequestResponseService
App\Services\ApplicationActions\ControlledWithdrawalService

App\Services\DataReuse\FutureApplicationDataReuseService
App\Services\DataReuse\DataReuseEligibilityService
App\Services\DataReuse\DataReuseSnapshotService
```

## 13.1 ProcessTimelineService

Responsável por:

```text id="pl61kd"
Criar eventos de timeline
Listar eventos por candidatura
Filtrar por visibilidade
Criar eventos a partir de status histories
Criar eventos a partir de documentos
Criar eventos a partir de notificações
Criar eventos a partir de tickets/visitas
Garantir ordenação cronológica
```

## 13.2 ProcessTimelineBuilder

Responsável por:

```text id="iej91n"
Construir timeline agregada para candidato
Construir timeline interna para backoffice
Combinar eventos nativos e eventos derivados
Gerar grupos por data
Gerar labels públicos
Ocultar eventos internos ao candidato
```

## 13.3 ApplicationPublicStatusService

Responsável por:

```text id="203qrx"
Mapear estado interno para estado público
Gerar descrição compreensível
Gerar próximo passo
Gerar prazo de ação
Gerar percentagem aproximada de progresso
Criar snapshot de estado público
```

## 13.4 ProcessActionResolver

Responsável por:

```text id="f8e0fc"
Determinar ações disponíveis para candidato
Determinar ações bloqueadas
Determinar ações expiradas
Gerar links de ação
Gerar motivos de bloqueio
```

## 13.5 CandidateNotificationCenterService

Responsável por:

```text id="zrhdnd"
Listar notificações do candidato
Filtrar por estado
Marcar como lida
Arquivar
Resolver ações associadas
Integrar com OfficialNotification se existir
```

## 13.6 PreliminaryHearingSubmissionService

Responsável por:

```text id="hvfzul"
Validar se audiência está aberta
Criar submissão
Guardar texto do candidato
Associar anexos/documentos
Criar timeline event
Notificar backoffice
Auditar ação
```

## 13.7 AdditionalDocumentSubmissionService

Responsável por:

```text id="0a8e7v"
Validar pedido/documento
Guardar documento em storage privado
Criar ou associar DocumentSubmission
Atualizar estado
Criar timeline event
Notificar backoffice
Auditar ação
```

## 13.8 CorrectionRequestResponseService

Responsável por:

```text id="as2i6y"
Responder a pedido de aperfeiçoamento existente
Validar prazo
Guardar resposta
Associar documentos
Criar timeline event
Atualizar estado do pedido
Notificar técnico
```

## 13.9 ControlledWithdrawalService

Responsável por:

```text id="4jd69x"
Validar se candidatura permite desistência
Criar pedido de desistência
Exigir confirmação
Aplicar desistência transacionalmente
Atualizar estado da candidatura
Criar timeline event
Criar auditoria
Notificar backoffice
```

## 13.10 FutureApplicationDataReuseService

Responsável por:

```text id="1fwgke"
Identificar dados reutilizáveis
Validar se dados estão atuais
Comparar com dados existentes
Gerar warnings
Criar reutilização
Aplicar dados confirmados
Criar snapshot de origem
Criar timeline event
```

---

# 14. Controllers

Criar ou completar:

```text id="8xd5u8"
App\Http\Controllers\Candidate\ProcessDashboardController
App\Http\Controllers\Candidate\ProcessTimelineController
App\Http\Controllers\Candidate\CandidateNotificationCenterController
App\Http\Controllers\Candidate\PreliminaryHearingSubmissionController
App\Http\Controllers\Candidate\AdditionalDocumentSubmissionController
App\Http\Controllers\Candidate\CorrectionRequestResponseController
App\Http\Controllers\Candidate\ControlledWithdrawalController
App\Http\Controllers\Candidate\FutureApplicationDataReuseController

App\Http\Controllers\Backoffice\ProcessTimelineController
App\Http\Controllers\Backoffice\ApplicationPublicStatusController
App\Http\Controllers\Backoffice\PreliminaryHearingSubmissionController
App\Http\Controllers\Backoffice\AdditionalDocumentRequestController
App\Http\Controllers\Backoffice\AdditionalDocumentSubmissionController
App\Http\Controllers\Backoffice\ControlledWithdrawalController
App\Http\Controllers\Backoffice\FutureApplicationDataReuseController
```

Controllers devem ser magros.

Toda lógica de decisão deve ficar em Services.

---

# 15. Form Requests

Criar:

```text id="yt5ids"
StorePreliminaryHearingSubmissionRequest
UpdatePreliminaryHearingSubmissionRequest
StoreAdditionalDocumentRequestRequest
StoreAdditionalDocumentSubmissionRequest
RespondCorrectionRequestRequest
StoreControlledWithdrawalRequest
ConfirmControlledWithdrawalRequest
CancelControlledWithdrawalRequest
StoreFutureApplicationDataReuseRequest
ConfirmFutureApplicationDataReuseRequest
MarkCandidateNotificationReadRequest
ArchiveCandidateNotificationRequest
ResolveProcessActionRequest
```

## StorePreliminaryHearingSubmissionRequest

```php id="3bnf3q"
'application_id' => ['required', 'exists:applications,id'],
'subject' => ['required', 'string', 'max:180'],
'body' => ['required', 'string', 'min:10', 'max:10000'],
'attachments' => ['nullable', 'array'],
'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
```

## StoreAdditionalDocumentSubmissionRequest

```php id="h67rz1"
'application_id' => ['required', 'exists:applications,id'],
'additional_document_request_id' => ['nullable', 'exists:additional_document_requests,id'],
'title' => ['required', 'string', 'max:180'],
'description' => ['nullable', 'string', 'max:2000'],
'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
```

## RespondCorrectionRequestRequest

```php id="tmybmn"
'correction_request_id' => ['required', 'integer'],
'message' => ['required', 'string', 'min:10', 'max:10000'],
'attachments' => ['nullable', 'array'],
'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
```

## StoreControlledWithdrawalRequest

```php id="m5cyfk"
'application_id' => ['required', 'exists:applications,id'],
'reason' => ['required', 'string', 'min:10', 'max:3000'],
'consequence_acknowledged' => ['accepted'],
```

## ConfirmControlledWithdrawalRequest

```php id="bzpd4s"
'confirm_withdrawal' => ['accepted'],
```

## ConfirmFutureApplicationDataReuseRequest

```php id="nbvt6u"
'target_application_id' => ['required', 'exists:applications,id'],
'sections' => ['required', 'array', 'min:1'],
'sections.*' => ['string', 'max:100'],
'confirm_review_required' => ['accepted'],
```

---

# 16. Policies

Criar ou completar:

```text id="lw7t05"
ProcessTimelineEventPolicy
ApplicationPublicStatusSnapshotPolicy
CandidateNotificationPolicy
PreliminaryHearingSubmissionPolicy
AdditionalDocumentRequestPolicy
AdditionalDocumentSubmissionPolicy
ControlledWithdrawalPolicy
FutureApplicationDataReusePolicy
ProcessActionPolicy
```

Regras:

```text id="f4f423"
Guest não acede a timeline processual.
Candidato só vê processos próprios.
Candidato só vê eventos candidate_visible próprios.
Candidato só vê notificações próprias.
Candidato só submete audiência em candidatura própria e estado permitido.
Candidato só submete documentação adicional em candidatura própria.
Candidato só responde a pedido de aperfeiçoamento próprio.
Candidato só desiste de candidatura própria e desistível.
Candidato só reutiliza dados próprios.
Técnico vê processos conforme permissões.
Auditor consulta histórico sem alterar.
Admin pode configurar mapeamento de estados.
```

---

# 17. Rotas da área do candidato

Adicionar, adaptando à estrutura real:

```php id="5v71hb"
Route::middleware(['auth'])->prefix('area-candidato')->name('candidate.')->group(function (): void {
    Route::get('/processos', [ProcessDashboardController::class, 'index'])->name('processes.index');
    Route::get('/processos/{application}', [ProcessDashboardController::class, 'show'])->name('processes.show');

    Route::get('/processos/{application}/timeline', [ProcessTimelineController::class, 'show'])->name('processes.timeline');

    Route::get('/notificacoes', [CandidateNotificationCenterController::class, 'index'])->name('notifications.index');
    Route::post('/notificacoes/{candidateNotification}/ler', [CandidateNotificationCenterController::class, 'markRead'])->name('notifications.read');
    Route::post('/notificacoes/{candidateNotification}/arquivar', [CandidateNotificationCenterController::class, 'archive'])->name('notifications.archive');

    Route::get('/processos/{application}/audiencia-previa/criar', [PreliminaryHearingSubmissionController::class, 'create'])->name('preliminary-hearings.create');
    Route::post('/processos/{application}/audiencia-previa', [PreliminaryHearingSubmissionController::class, 'store'])->name('preliminary-hearings.store');
    Route::get('/audiencia-previa/{preliminaryHearingSubmission}', [PreliminaryHearingSubmissionController::class, 'show'])->name('preliminary-hearings.show');

    Route::get('/processos/{application}/documentos-adicionais/criar', [AdditionalDocumentSubmissionController::class, 'create'])->name('additional-documents.create');
    Route::post('/processos/{application}/documentos-adicionais', [AdditionalDocumentSubmissionController::class, 'store'])->name('additional-documents.store');

    Route::get('/processos/{application}/aperfeicoamentos/{correctionRequest}/responder', [CorrectionRequestResponseController::class, 'create'])->name('correction-requests.respond.create');
    Route::post('/processos/{application}/aperfeicoamentos/{correctionRequest}/responder', [CorrectionRequestResponseController::class, 'store'])->name('correction-requests.respond.store');

    Route::get('/processos/{application}/desistir', [ControlledWithdrawalController::class, 'create'])->name('withdrawals.create');
    Route::post('/processos/{application}/desistir', [ControlledWithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::post('/desistencias/{controlledWithdrawal}/confirmar', [ControlledWithdrawalController::class, 'confirm'])->name('withdrawals.confirm');
    Route::post('/desistencias/{controlledWithdrawal}/cancelar', [ControlledWithdrawalController::class, 'cancel'])->name('withdrawals.cancel');

    Route::get('/reutilizacao-dados', [FutureApplicationDataReuseController::class, 'index'])->name('data-reuse.index');
    Route::post('/reutilizacao-dados', [FutureApplicationDataReuseController::class, 'store'])->name('data-reuse.store');
    Route::post('/reutilizacao-dados/{futureApplicationDataReuse}/confirmar', [FutureApplicationDataReuseController::class, 'confirm'])->name('data-reuse.confirm');
});
```

Todas as rotas devem usar policies.

---

# 18. Rotas de backoffice

Adicionar, adaptando à estrutura real:

```php id="0fwxhr"
Route::middleware(['auth'])->prefix('backoffice')->name('backoffice.')->group(function (): void {
    Route::get('/processos/{application}/timeline', [ProcessTimelineController::class, 'show'])->name('processes.timeline');
    Route::get('/processos/{application}/public-status', [ApplicationPublicStatusController::class, 'show'])->name('processes.public-status.show');
    Route::put('/processos/{application}/public-status', [ApplicationPublicStatusController::class, 'update'])->name('processes.public-status.update');

    Route::get('/audiencias-previas', [PreliminaryHearingSubmissionController::class, 'index'])->name('preliminary-hearings.index');
    Route::get('/audiencias-previas/{preliminaryHearingSubmission}', [PreliminaryHearingSubmissionController::class, 'show'])->name('preliminary-hearings.show');
    Route::post('/audiencias-previas/{preliminaryHearingSubmission}/decidir', [PreliminaryHearingSubmissionController::class, 'decide'])->name('preliminary-hearings.decide');

    Route::get('/documentos-adicionais/pedidos', [AdditionalDocumentRequestController::class, 'index'])->name('additional-document-requests.index');
    Route::post('/processos/{application}/documentos-adicionais/pedidos', [AdditionalDocumentRequestController::class, 'store'])->name('additional-document-requests.store');

    Route::get('/documentos-adicionais/submissoes', [AdditionalDocumentSubmissionController::class, 'index'])->name('additional-document-submissions.index');
    Route::get('/documentos-adicionais/submissoes/{additionalDocumentSubmission}', [AdditionalDocumentSubmissionController::class, 'show'])->name('additional-document-submissions.show');
    Route::post('/documentos-adicionais/submissoes/{additionalDocumentSubmission}/decidir', [AdditionalDocumentSubmissionController::class, 'decide'])->name('additional-document-submissions.decide');

    Route::get('/desistencias', [ControlledWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/desistencias/{controlledWithdrawal}', [ControlledWithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::post('/desistencias/{controlledWithdrawal}/processar', [ControlledWithdrawalController::class, 'process'])->name('withdrawals.process');

    Route::get('/reutilizacao-dados', [FutureApplicationDataReuseController::class, 'index'])->name('data-reuse.index');
});
```

Todas as rotas devem respeitar middleware, policies e convenções existentes.

---

# 19. Views / páginas

Se o projeto usa Blade, criar:

```text id="kn3y6v"
resources/views/candidate/processes/index.blade.php
resources/views/candidate/processes/show.blade.php
resources/views/candidate/processes/timeline.blade.php

resources/views/candidate/notifications/index.blade.php

resources/views/candidate/preliminary-hearings/create.blade.php
resources/views/candidate/preliminary-hearings/show.blade.php

resources/views/candidate/additional-documents/create.blade.php

resources/views/candidate/correction-requests/respond.blade.php

resources/views/candidate/withdrawals/create.blade.php
resources/views/candidate/withdrawals/show.blade.php

resources/views/candidate/data-reuse/index.blade.php
resources/views/candidate/data-reuse/confirm.blade.php

resources/views/backoffice/processes/timeline.blade.php
resources/views/backoffice/processes/public-status.blade.php

resources/views/backoffice/preliminary-hearings/index.blade.php
resources/views/backoffice/preliminary-hearings/show.blade.php

resources/views/backoffice/additional-document-requests/index.blade.php
resources/views/backoffice/additional-document-submissions/index.blade.php
resources/views/backoffice/additional-document-submissions/show.blade.php

resources/views/backoffice/withdrawals/index.blade.php
resources/views/backoffice/withdrawals/show.blade.php

resources/views/backoffice/data-reuse/index.blade.php
```

Se o projeto usa Inertia/React/Vue, criar equivalentes.

Não mudar stack frontend.

---

# 20. UX obrigatória

## 20.1 Dashboard processual do candidato

Mostrar:

```text id="igp1ux"
Estado atual do processo
Descrição clara do estado
Próximo passo
Prazo ativo, se existir
Ações disponíveis
Notificações recentes
Documentos pendentes
Pedidos de aperfeiçoamento
Audiência prévia, se aplicável
Tickets e visitas relacionados, se existirem
Timeline resumida
```

## 20.2 Timeline completa

Mostrar:

```text id="0b39jv"
Eventos por ordem cronológica
Data e hora
Título
Descrição
Tipo de evento
Estado público
Documento/ação associada, se aplicável
Prazo associado, se aplicável
Ação disponível, se aplicável
```

A timeline deve ter:

```text id="gyn87n"
Versão resumida
Versão detalhada
Filtros por tipo de evento
Separação visual por fases
Estado atual destacado
```

## 20.3 Área de notificações

Mostrar:

```text id="c3m874"
Notificações não lidas
Notificações lidas
Notificações com ação obrigatória
Notificações expiradas
Data
Assunto
Mensagem
Link para ação
Marcar como lida
Arquivar
```

## 20.4 Audiência prévia / recurso

Mostrar:

```text id="fzqm0q"
Contexto da audiência
Prazo
Motivo/fundamento
Campo para resposta
Upload de anexos
Confirmação antes de submeter
Histórico da submissão
Estado de análise
```

## 20.5 Documentação adicional

Mostrar:

```text id="7m58fw"
Pedido associado
Tipo documental
Descrição
Prazo
Upload
Estado da submissão
Resultado da análise
Motivo de rejeição, se aplicável
```

## 20.6 Desistência controlada

Mostrar:

```text id="mz4o0q"
Aviso de consequências
Estado atual da candidatura
Campo de motivo
Checkbox de confirmação
Resumo antes de confirmar
Botão de cancelar
Botão de confirmar desistência
```

## 20.7 Reutilização de dados

Mostrar:

```text id="5h52ye"
Dados disponíveis para reutilização
Origem dos dados
Data da última confirmação
Dados desatualizados
Secções a reutilizar
Secções excluídas
Warnings
Confirmação antes de aplicar
```

---

# 21. Regras de timeline

Eventos devem incluir, quando aplicável:

```text id="tciqar"
Criação da candidatura
Submissão
Alteração de estado
Receção pela Câmara
Atribuição a técnico
Pedido de aperfeiçoamento
Resposta a aperfeiçoamento
Upload documental
Validação/rejeição documental
Notificação enviada
Notificação lida
Audiência prévia aberta
Resposta em audiência
Reclamação/recurso submetido
Lista provisória publicada
Lista definitiva publicada
Atribuição
Visita agendada/concluída
Ticket criado/resolvido
Inconsistência detetada
Desistência
Reutilização de dados
```

Eventos candidate_visible devem usar linguagem cidadã.

Eventos backoffice_only podem conter detalhe técnico.

Não mostrar notas internas ao candidato.

---

# 22. Regras de estados públicos

O estado público deve:

```text id="pdan39"
Ser compreensível para o candidato.
Não expor detalhe interno indevido.
Mapear estados internos complexos para mensagens claras.
Indicar próximo passo.
Indicar se há ação obrigatória.
Indicar prazo.
Indicar se o processo está concluído, cancelado ou desistido.
```

Exemplos de textos:

```text id="f68ftq"
Candidatura em preparação
Candidatura submetida
Em análise pelos serviços municipais
A aguardar documentos
A aguardar resposta do candidato
Resposta recebida
Em audiência prévia
Pronúncia submetida
Admitida para classificação
Não admitida
Em fase de classificação
Lista provisória publicada
Período de reclamação aberto
Reclamação em análise
Lista definitiva publicada
Habitação atribuída
Processo concluído
Candidatura desistida
```

---

# 23. Regras de audiência prévia e recursos

Regras obrigatórias:

```text id="ngkcd0"
Submissão só disponível em estado e prazo aplicáveis.
Candidato só submete uma resposta ativa por fase, salvo regra diferente.
Resposta deve ficar associada à candidatura.
Anexos devem ficar privados.
Backoffice deve conseguir consultar e decidir.
Decisão deve gerar timeline event.
Prazos expirados bloqueiam submissão, salvo permissão administrativa.
```

Não implementar decisão automática.

---

# 24. Regras de documentação adicional

Regras obrigatórias:

```text id="s40qw9"
Submissão deve estar associada a candidatura.
Submissão pode estar associada a pedido específico.
Ficheiros devem ser validados.
Ficheiros devem ficar em storage privado.
Download deve passar por controller autorizado existente.
Estado deve aparecer na timeline.
Rejeição deve ter motivo.
```

Não expor documentos por URL público.

---

# 25. Regras de pedidos de aperfeiçoamento

Se já existir módulo de aperfeiçoamento, integrar.

Regras:

```text id="whfchr"
Pedido de aperfeiçoamento aparece na timeline.
Pedido gera ação pendente.
Candidato pode responder dentro do prazo.
Resposta pode incluir mensagem e documentos.
Técnico vê resposta no backoffice.
Estado muda para resposta submetida ou equivalente.
Histórico é preservado.
```

Não criar segundo fluxo incompatível se já existir `CorrectionRequest`.

---

# 26. Regras de desistência controlada

Regras obrigatórias:

```text id="dg8jsf"
Só o titular/candidato autorizado pode desistir.
Só estados desistíveis permitem desistência.
Desistência exige motivo.
Desistência exige confirmação explícita.
Desistência não apaga candidatura.
Desistência altera estado por service transacional.
Desistência cria evento de timeline.
Desistência cria auditoria.
Desistência bloqueia ações futuras incompatíveis.
```

Estados normalmente não desistíveis:

```text id="tijkz9"
completed
contract_signed
archived
cancelled
withdrawn
```

Adaptar aos estados reais do projeto.

---

# 27. Regras de reutilização automática de dados

Regras obrigatórias:

```text id="fv56t3"
Reutilização só com dados do próprio candidato.
Reutilização deve mostrar origem.
Reutilização exige confirmação.
Dados validados podem ser sugeridos automaticamente.
Dados expirados devem gerar warning.
Documentos não devem ser copiados automaticamente como válidos sem regra expressa.
Snapshots devem preservar origem.
Candidatura submetida não deve ser sobrescrita.
```

Reutilizar preferencialmente:

```text id="fsum1j"
Dados pessoais
Agregado familiar
Rendimentos
Situação habitacional
Preferências
Dados de contacto
Resumo documental
Última simulação relevante
```

---

# 28. Notificações

Se Sprint 16 existir, emitir notificações para:

```text id="muyvov"
Novo pedido de aperfeiçoamento
Pedido de aperfeiçoamento perto do prazo
Resposta de aperfeiçoamento recebida
Audiência prévia aberta
Audiência prévia perto do prazo
Resposta de audiência submetida
Documento adicional solicitado
Documento adicional analisado
Desistência registada
Dados reutilizados em nova candidatura
Estado do processo alterado
```

Não enviar e-mail/SMS real sem configuração segura.

Se notificações não existirem, criar eventos internos ou documentar pendência.

---

# 29. Auditoria e RGPD

Auditar, se existir auditoria:

```text id="eu0fxm"
Consulta de processo sensível
Submissão de audiência prévia
Submissão de documento adicional
Resposta a pedido de aperfeiçoamento
Desistência iniciada
Desistência confirmada
Reutilização de dados
Alteração de estado público
Criação de evento manual de timeline
Marcação de notificação como lida
```

RGPD:

```text id="0dtcvl"
Não expor processos de terceiros.
Não expor documentos privados.
Não guardar dados sensíveis em logs técnicos.
Não mostrar notas internas ao candidato.
Não reutilizar dados sem transparência.
Não copiar documentos privados sem regra.
```

---

# 30. Backoffice — indicadores mínimos

Criar indicadores simples:

```text id="q2yz48"
Processos com ação pendente do candidato
Processos com prazo a expirar
Pedidos de aperfeiçoamento em aberto
Respostas de aperfeiçoamento recebidas
Audiências prévias abertas
Audiências prévias respondidas
Documentos adicionais submetidos
Desistências confirmadas
Processos com dados reutilizados
Notificações não lidas pelo candidato
```

Pode ser página simples sem BI avançado.

---

# 31. Factories e seeders

Criar factories:

```text id="i6bvm4"
ProcessTimelineEventFactory
ApplicationPublicStatusSnapshotFactory
CandidateNotificationFactory
PreliminaryHearingSubmissionFactory
AdditionalDocumentRequestFactory
AdditionalDocumentSubmissionFactory
ControlledWithdrawalFactory
FutureApplicationDataReuseFactory
ProcessActionFactory
```

Criar seeder opcional:

```text id="0cpx7d"
Database\Seeders\ProcessTrackingDemoSeeder
```

Dados fictícios:

```text id="xfp7xl"
Candidatura submetida
Candidatura em análise
Pedido de aperfeiçoamento
Resposta a aperfeiçoamento
Audiência prévia aberta
Documento adicional submetido
Desistência controlada
Reutilização de dados em nova candidatura
Timeline completa com eventos
Notificações lidas e não lidas
```

Não usar dados reais.

---

# 32. Testes obrigatórios

Criar ou completar testes.

## 32.1 Timeline e estados

```text id="e01o67"
tests/Feature/Candidate/ProcessTimelineTest.php
tests/Feature/Candidate/ProcessDashboardTest.php
tests/Feature/Backoffice/ProcessTimelineManagementTest.php
tests/Unit/ProcessTracking/ProcessTimelineBuilderTest.php
tests/Unit/ProcessTracking/ApplicationPublicStatusServiceTest.php
```

Cobrir:

```text id="pjed0n"
Candidato vê timeline própria
Candidato não vê timeline de terceiro
Timeline ordena eventos cronologicamente
Eventos internos não aparecem ao candidato
Backoffice vê timeline completa conforme permissão
Estado público é mapeado corretamente
Próximo passo é apresentado
Ações pendentes aparecem no dashboard
```

## 32.2 Notificações

```text id="4o3hbn"
tests/Feature/Candidate/CandidateNotificationCenterTest.php
tests/Unit/CandidateNotifications/CandidateNotificationCenterServiceTest.php
```

Cobrir:

```text id="gq20y0"
Candidato vê notificações próprias
Candidato não vê notificações de terceiro
Candidato marca notificação como lida
Candidato arquiva notificação
Notificação com ação mostra link
Notificação expirada não permite ação
```

## 32.3 Audiência prévia / recursos

```text id="4iw77q"
tests/Feature/Candidate/PreliminaryHearingSubmissionTest.php
tests/Feature/Backoffice/PreliminaryHearingReviewTest.php
tests/Unit/ApplicationActions/PreliminaryHearingSubmissionServiceTest.php
```

Cobrir:

```text id="h1g2v1"
Candidato submete resposta em audiência aberta
Candidato não submete fora do prazo
Candidato não submete em processo de terceiro
Anexo fica privado
Backoffice consulta resposta
Backoffice decide resposta
Submissão cria timeline event
```

## 32.4 Documentação adicional e aperfeiçoamentos

```text id="dj5pth"
tests/Feature/Candidate/AdditionalDocumentSubmissionTest.php
tests/Feature/Candidate/CorrectionRequestResponseTest.php
tests/Feature/Backoffice/AdditionalDocumentReviewTest.php
tests/Unit/ApplicationActions/AdditionalDocumentSubmissionServiceTest.php
tests/Unit/ApplicationActions/CorrectionRequestResponseServiceTest.php
```

Cobrir:

```text id="l4joz6"
Candidato submete documento adicional
Documento inválido é rejeitado
Documento fica em storage privado
Candidato responde a pedido de aperfeiçoamento
Candidato não responde a pedido de terceiro
Backoffice analisa submissão
Rejeição exige motivo
Timeline é atualizada
```

## 32.5 Desistência controlada

```text id="8v2doi"
tests/Feature/Candidate/ControlledWithdrawalTest.php
tests/Feature/Backoffice/ControlledWithdrawalManagementTest.php
tests/Unit/ApplicationActions/ControlledWithdrawalServiceTest.php
```

Cobrir:

```text id="3n6w3s"
Candidato inicia desistência
Desistência exige confirmação explícita
Desistência exige motivo
Candidato não desiste de candidatura de terceiro
Candidatura em estado não desistível bloqueia desistência
Desistência atualiza estado
Desistência cria timeline event
Desistência cria auditoria se existir
```

## 32.6 Reutilização de dados

```text id="zsqr7d"
tests/Feature/Candidate/FutureApplicationDataReuseTest.php
tests/Unit/DataReuse/FutureApplicationDataReuseServiceTest.php
tests/Unit/DataReuse/DataReuseEligibilityServiceTest.php
```

Cobrir:

```text id="qbuhua"
Sistema identifica dados reutilizáveis
Candidato confirma reutilização
Dados expirados geram warning
Documentos não são copiados automaticamente como válidos
Dados são aplicados em candidatura rascunho
Candidatura submetida não é sobrescrita
Candidato não reutiliza dados de terceiro
Timeline regista reutilização
```

## 32.7 Segurança/RGPD

```text id="x8ekfx"
tests/Feature/Security/ProcessTrackingPrivacyTest.php
```

Cobrir:

```text id="b670ap"
Guest não acede a processos
Candidato não vê processo de terceiro
Candidato não vê eventos internos
Candidato não vê documentos de terceiro
Mass assignment de public_status é bloqueado
Mass assignment de user_id é bloqueado
Notas internas não aparecem ao candidato
```

---

# 33. PHPStan específico da Sprint 23

Após implementar testes e código:

```bash id="eaygl1"
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint23-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint23-after.txt || true
```

Verificar especialmente ficheiros novos:

```text id="4ls27b"
app/Models/ProcessTimelineEvent.php
app/Models/ApplicationPublicStatusSnapshot.php
app/Models/CandidateNotification.php
app/Models/PreliminaryHearingSubmission.php
app/Models/AdditionalDocumentRequest.php
app/Models/AdditionalDocumentSubmission.php
app/Models/ControlledWithdrawal.php
app/Models/FutureApplicationDataReuse.php
app/Models/ProcessAction.php
app/Services/ProcessTracking/*
app/Services/CandidateNotifications/*
app/Services/ApplicationActions/*
app/Services/DataReuse/*
app/Http/Controllers/Candidate/*
app/Http/Controllers/Backoffice/*
app/Http/Requests/*
tests/Feature/*
tests/Unit/*
```

Corrigir:

```text id="avjfeb"
missingType.generics
missingType.iterableValue
argument.type
return.type
property.notFound
method.notFound
enum/value type mismatch
invalid relation generics
```

Em relações Eloquent, usar PHPDoc generics corretos:

```php id="3p1eq0"
/** @return BelongsTo<Application, ProcessTimelineEvent> */
public function application(): BelongsTo
{
    return $this->belongsTo(Application::class);
}
```

Em arrays estruturados, usar PHPDoc:

```php id="sf6sfr"
/** @return array{title: string, description: string, action_required: bool, due_at?: string|null} */
```

Não adicionar `mixed` sem necessidade.

---

# 34. Comandos obrigatórios finais

Executar:

```bash id="epx5uv"
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash id="2hwpvl"
php artisan migrate
```

Se o projeto usar frontend build:

```bash id="st62p8"
npm run build
```

Se o projeto usar Pint:

```bash id="v11q23"
./vendor/bin/pint
```

Se existir PHPStan:

```bash id="pyyobj"
php -d memory_limit=1G ./vendor/bin/phpstan analyse
```

Se existir Psalm:

```bash id="1znbje"
./vendor/bin/psalm
```

Se algum comando falhar, documentar:

```text id="686bfd"
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
Bloqueia publicação: sim/não
```

Não afirmar que comandos passaram se não foram executados.

Não ocultar erros.

---

# 35. Documentação obrigatória

Criar ou atualizar:

```text id="2qfsei"
docs/backlog/sprint-23-acompanhamento-processual-avancado.md
docs/candidate-experience/process-tracking.md
docs/candidate-experience/process-timeline.md
docs/candidate-experience/notification-center.md
docs/candidate-experience/preliminary-hearing-and-appeals.md
docs/candidate-experience/additional-documents-and-corrections.md
docs/candidate-experience/controlled-withdrawal.md
docs/candidate-experience/future-application-data-reuse.md
docs/qa/test-coverage-matrix.md
docs/qa/sprint-23-quality-report.md
docs/backlog/roadmap.md
```

## docs/candidate-experience/process-tracking.md

Incluir:

```text id="t82n2h"
Objetivo
Dashboard processual
Estados públicos
Próximos passos
Ações disponíveis
Permissões
Limitações
```

## docs/candidate-experience/process-timeline.md

Incluir:

```text id="4hakmr"
Tipos de eventos
Visibilidades
Eventos públicos
Eventos internos
Ordenação
Integrações com documentos, notificações, visitas e tickets
```

## docs/candidate-experience/notification-center.md

Incluir:

```text id="slcou6"
Objetivo
Estados
Ações
Leitura
Arquivo
Expiração
Integração com notificações oficiais
```

## docs/candidate-experience/preliminary-hearing-and-appeals.md

Incluir:

```text id="1w89fb"
Fluxo
Prazos
Submissão
Anexos
Análise backoffice
Limitações
```

## docs/candidate-experience/additional-documents-and-corrections.md

Incluir:

```text id="xjgghl"
Pedidos de aperfeiçoamento
Documentos adicionais
Estados
Storage privado
Análise técnica
Motivos de rejeição
```

## docs/candidate-experience/controlled-withdrawal.md

Incluir:

```text id="b17r6s"
Fluxo de desistência
Estados desistíveis
Confirmação
Consequências
Auditoria
Limitações
```

## docs/candidate-experience/future-application-data-reuse.md

Incluir:

```text id="w7haza"
Dados reutilizáveis
Dados excluídos
Confirmação
Snapshots
Dados expirados
Aplicação em candidatura futura
```

## docs/qa/sprint-23-quality-report.md

Incluir:

```text id="d7t5lb"
PHPStan inicial
PHPStan final
Erros legados assumidos: 2471
Erros novos introduzidos: sim/não
Testes executados
Funcionalidades concluídas
Funcionalidades pendentes
Riscos RGPD
Riscos funcionais
Riscos de publicação
```

---

# 36. Critérios de aceitação

A Sprint 23 está concluída quando:

```text id="akq2ew"
Existe timeline completa do processo.
Candidato vê apenas timeline própria.
Timeline mostra histórico cronológico.
Timeline distingue eventos públicos e internos.
Estados públicos são claros.
Dashboard processual mostra estado atual e próximo passo.
Área de notificações existe.
Candidato consegue marcar notificações como lidas.
Candidato consegue arquivar notificações.
Recursos/audiência prévia podem ser submetidos quando aplicável.
Submissão fora do prazo é bloqueada ou documentada conforme regra.
Documentação adicional pode ser submetida.
Documentação adicional fica em storage privado.
Pedidos de aperfeiçoamento aparecem na timeline.
Candidato consegue responder a pedido de aperfeiçoamento.
Desistência controlada existe.
Desistência exige confirmação explícita.
Desistência cria evento e auditoria se existir.
Dados podem ser reutilizados em futura candidatura.
Reutilização exige confirmação.
Dados expirados geram aviso.
Documentos não são copiados automaticamente como válidos sem regra expressa.
Backoffice consegue consultar timeline completa conforme permissão.
Notificações são emitidas se módulo existir.
Auditoria é criada se módulo existir.
Dados pessoais não são expostos indevidamente.
PHPStan foi executado antes da implementação, se disponível.
PHPStan foi executado antes da publicação, se disponível.
Foram considerados os 2471 erros legados.
Sprint 23 não introduz erros PHPStan novos nos ficheiros alterados.
php artisan route:list executa sem erro.
php artisan test executa sem erro ou falhas são documentadas.
npm run build executa sem erro ou falhas são documentadas.
./vendor/bin/pint executa sem erro ou alterações são documentadas.
Documentação foi criada/atualizada.
Não foram usadas credenciais.
Não foram usados dados pessoais reais.
Não foram implementadas funcionalidades fora de âmbito.
```

---

# 37. Resposta final esperada do Codex

No final da execução, responder com:

```text id="49qh7f"
1. Sprint executada
2. Resumo do trabalho realizado
3. Estado PHPStan inicial
4. Estado PHPStan antes de publicação
5. Erros PHPStan legados considerados: 2471
6. Novos erros PHPStan introduzidos pela Sprint 23: sim/não
7. Models criados ou alterados
8. Migrations criadas
9. Services criados ou alterados
10. Controllers criados ou alterados
11. Form Requests criados ou alterados
12. Policies criadas ou alteradas
13. Rotas da área do candidato criadas ou alteradas
14. Rotas de backoffice criadas ou alteradas
15. Views/components criados ou alterados
16. Estado da timeline processual
17. Estado dos estados públicos detalhados
18. Estado do histórico cronológico
19. Estado da área de notificações
20. Estado dos recursos/audiência prévia
21. Estado da documentação adicional
22. Estado dos pedidos de aperfeiçoamento
23. Estado da desistência controlada
24. Estado da reutilização automática de dados
25. Estado das notificações/auditoria
26. Testes criados ou alterados
27. Resultado de php artisan route:list
28. Resultado de php artisan test
29. Resultado de php artisan migrate, se aplicável
30. Resultado de npm run build, se aplicável
31. Resultado de ./vendor/bin/pint, se aplicável
32. Resultado de PHPStan/Psalm, se aplicável
33. Riscos ainda existentes
34. Pendências técnicas
35. Confirmação de que não foram usados dados pessoais reais
36. Confirmação de que não foram usadas credenciais
37. Confirmação de que não foram implementadas funcionalidades fora de âmbito
38. Recomendação objetiva para avançar ou não para Sprint 24
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 38. Definition of Done

A Sprint 23 só está concluída quando existir acompanhamento processual avançado com timeline completa, estados detalhados, histórico cronológico, área de notificações, audiência prévia/recursos, documentação adicional, pedidos de aperfeiçoamento, desistência controlada e reutilização automática de dados em futuras candidaturas, com permissões, RGPD, auditoria, testes e validação PHPStan sem aumento do passivo legado de 2471 erros.

Fim da Sprint 23.
