# Sprint 9 — Workflow Administrativo e Aperfeiçoamento

## Prioridade de desenvolvimento

Esta sprint pertence à fase operacional posterior à submissão formal de candidatura.

A Sprint 9 implementa o workflow administrativo necessário para que as candidaturas submetidas possam ser recebidas, triadas, analisadas, aperfeiçoadas e admitidas ou não admitidas para classificação.

Esta sprint deve ser executada depois da Sprint 8 e antes da Sprint 10.

A ordem operacional recomendada é:

```text id="zktb5m"
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 10 — Matriz de Classificação e Ranking
Sprint 11 — Listas Provisórias, Reclamações e Audiência
Sprint 12 — Atribuição
```

---

# 1. Objetivo da Sprint

Implementar o workflow administrativo de análise inicial das candidaturas submetidas.

A plataforma deve permitir que os serviços municipais:

```text id="u1argj"
Recebam candidaturas submetidas
Criem processo administrativo associado à candidatura
Atribuam técnico responsável
Façam triagem inicial
Consultem dados, documentos, elegibilidade e snapshots
Registem análise administrativa
Registem análise documental
Registem análise de requisitos
Peçam aperfeiçoamento ao candidato
Definam prazo para resposta
Permitam resposta do candidato
Recebam documentos substituídos ou complementares
Registem resposta ao aperfeiçoamento
Reavaliem candidatura após resposta
Registem decisão de admissão para classificação
Registem decisão de não admissão, quando aplicável
Guardem histórico completo de estados
Criem timeline processual
Registem fundamentos, notas internas e decisões
Auditem ações críticas
Preparem candidaturas para a Sprint 10
```

A Sprint 9 deve criar o estado final necessário para que a Sprint 10 classifique apenas candidaturas administrativamente preparadas.

Estado recomendado:

```text id="qgoj0u"
admitted_for_scoring
```

ou equivalente:

```text id="dojjvp"
eligible_for_classification
```

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 9.

Não avances para Sprint 10, Sprint 11, Sprint 12 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash id="mbqjuj"
git branch --show-current
```

Não interromper a execução por causa da branch atual.

Antes de alterar código, lê, se existirem:

```text id="jqxg1u"
docs/architecture/technical-architecture.md
docs/architecture/data-model-overview.md
docs/product/product-vision.md
docs/product/functional-requirements.md
docs/product/user-roles.md
docs/product/process-workflows.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/backlog/roadmap.md

docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
docs/backlog/sprint-7-motor-elegibilidade.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
docs/backlog/sprint-10-matriz-classificacao-ranking.md

docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

Antes de implementar, identifica:

```text id="6mkll0"
Versão do Laravel
Versão do PHP
Sistema de autenticação
Sistema de roles/permissões
Stack frontend
Models existentes
Migrations existentes
Controllers existentes
Requests existentes
Policies existentes
Services existentes
Views/componentes existentes
Tests existentes
Sistema de auditoria, se existir
Sistema de notificações, se existir
Modelo User
Modelo Program
Modelo Contest
Modelo Application
Modelo ApplicationStatusHistory
Modelo ApplicationSnapshot
Modelo ApplicationDocument
Modelo DocumentSubmission
Modelo DocumentReview
Modelo EligibilityCheck, se existir
Modelo EligibilityCheckResult, se existir
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text id="79wxra"
AdministrativeProcess
ApplicationWorkflow
ApplicationReview
CorrectionRequest
CorrectionResponse
ProcessTask
ProcessTimeline
AdmissionDecision
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens ou APP_KEY.

---

# 3. Dependências funcionais

Esta sprint depende obrigatoriamente da Sprint 8.

Se `Application` não existir, interrompe a implementação funcional e informa:

```text id="us84wf"
A Sprint 9 depende da Sprint 8 — Candidaturas e Submissão Formal.
```

Esta sprint depende preferencialmente de:

```text id="9glzc3"
Sprint 6 — Gestão Documental Avançada
Sprint 7 — Motor de Elegibilidade
Sprint 8 — Candidaturas e Submissão Formal
```

## Se Sprint 6 não existir

Implementar o workflow administrativo sem validação documental avançada, mas documentar pendência.

Não criar sistema documental paralelo simplificado.

## Se Sprint 7 não existir

Implementar o workflow administrativo e permitir análise manual dos requisitos, mas documentar que a avaliação de elegibilidade automática fica pendente.

Não criar motor de elegibilidade simplificado dentro da Sprint 9.

## Se Sprint 8 existir

Integrar obrigatoriamente com candidaturas submetidas.

A Sprint 9 deve atuar apenas sobre candidaturas em estado adequado, normalmente:

```text id="fjmoni"
submitted
under_review
correction_submitted
```

ou equivalentes existentes no projeto.

---

# 4. Validação jurídica antes de automatismos

Esta sprint deve ser construída com cautela jurídica.

Não implementar decisões automáticas irreversíveis.

Não criar exclusões automáticas definitivas.

Não hardcodar prazos legais sem validação jurídica.

Qualquer prazo, estado, fundamento ou mensagem com impacto processual deve ser configurável ou documentado como pendente de validação jurídica.

Regras importantes:

```text id="4p6ezg"
Pedidos de aperfeiçoamento devem ficar registados.
Prazos devem ficar registados.
Respostas do candidato devem ficar registadas.
Motivos de não admissão devem ser fundamentados.
Alterações de estado devem gerar histórico.
Decisões devem identificar quem decidiu e quando.
Notas internas não devem ser visíveis ao candidato.
Comunicações formais reais ficam para sprint própria, salvo registo interno.
```

Esta sprint pode gerar registos de comunicação e textos-base, mas não deve implementar envio real de email/SMS se esse módulo ainda não existir.

---

# 5. Âmbito incluído

Implementar:

```text id="dsjbft"
Processo administrativo da candidatura
Estados administrativos
Histórico de estado administrativo
Triagem inicial
Atribuição de técnico responsável
Análise administrativa
Análise documental
Análise de requisitos
Pedido de aperfeiçoamento
Itens de aperfeiçoamento
Prazo de resposta
Resposta ao aperfeiçoamento pelo candidato
Associação de documentos corrigidos
Registo de correções
Reanálise após resposta
Decisão de admissão para classificação
Decisão de não admissão
Fundamentação da decisão
Timeline processual
Notas internas
Tarefas administrativas
Configuração simples de prazos
Backoffice administrativo
Área do candidato para acompanhar pedidos de aperfeiçoamento
Policies
Form Requests
Services
Rotas
Views/páginas
Seeders/factories
Testes
Atualização documental
```

---

# 6. Fora de âmbito

Não implementar nesta sprint:

```text id="5g5dxf"
Matriz de classificação
Pontuação
Ranking
Listas provisórias públicas
Reclamações
Audiência de interessados
Listas definitivas
Atribuição de habitações
Sorteio
Contrato
Cálculo de renda contratual
Pagamentos
Manutenção
Notificações reais por SMS
Notificações reais por email, salvo se já existir módulo e for seguro
Integração com AT
Integração com Segurança Social
Integração com Autenticação.GOV
Assinatura digital
OCR
```

Podem ser criados pontos de integração para estas funcionalidades futuras, mas não implementar essas fases.

---

# 7. Conceito funcional

O fluxo administrativo deve funcionar assim:

```text id="oi6m8h"
Candidatura submetida
→ Processo administrativo criado
→ Receção administrativa
→ Triagem inicial
→ Análise documental/requisitos
→ Pedido de aperfeiçoamento, se necessário
→ Resposta do candidato
→ Reanálise
→ Decisão de admissão ou não admissão para classificação
→ Candidaturas admitidas seguem para Sprint 10
```

A Sprint 9 não publica listas.

A Sprint 9 não ordena candidaturas.

A Sprint 9 não atribui habitações.

A Sprint 9 apenas determina se a candidatura pode ou não seguir para a classificação.

---

# 8. Estados administrativos recomendados

Criar estados formais para o processo administrativo.

## AdministrativeProcessStatus

```text id="ad9n8q"
submitted
received
assigned
preliminary_review
document_review
eligibility_review
requires_correction
awaiting_candidate_response
correction_submitted
correction_overdue
correction_under_review
admitted_for_scoring
not_admitted
withdrawn
cancelled
archived
```

## Significado dos estados

### submitted

Candidatura foi submetida pelo candidato.

### received

Candidatura foi recebida administrativamente.

### assigned

Processo foi atribuído a técnico responsável.

### preliminary_review

Processo em triagem inicial.

### document_review

Documentos em análise.

### eligibility_review

Requisitos de acesso/elegibilidade em análise.

### requires_correction

Foi identificado que o processo exige aperfeiçoamento.

### awaiting_candidate_response

Pedido de aperfeiçoamento foi emitido e aguarda resposta.

### correction_submitted

Candidato respondeu ao pedido de aperfeiçoamento.

### correction_overdue

Prazo de resposta terminou sem resposta válida.

### correction_under_review

Resposta ao aperfeiçoamento em análise.

### admitted_for_scoring

Candidatura admitida para classificação.

### not_admitted

Candidatura não admitida para classificação.

### withdrawn

Candidato desistiu da candidatura.

### cancelled

Processo cancelado administrativamente.

### archived

Processo arquivado.

---

# 9. Estados do pedido de aperfeiçoamento

## CorrectionRequestStatus

```text id="78178s"
draft
issued
open
partially_responded
responded
overdue
under_review
accepted
rejected
closed
cancelled
```

## Estados dos itens de aperfeiçoamento

```text id="tyk17b"
pending
responded
accepted
rejected
waived
cancelled
```

---

# 10. Modelo de dados

## 10.1 AdministrativeProcess

Criar entidade:

```text id="6twy58"
AdministrativeProcess
```

Tabela:

```text id="urg9ou"
administrative_processes
```

Campos mínimos:

```text id="5r7sqz"
id
process_number
application_id
program_id
contest_id
user_id

status
assigned_to
assigned_at

received_at
preliminary_review_started_at
document_review_started_at
eligibility_review_started_at

admitted_for_scoring_at
not_admitted_at
withdrawn_at
cancelled_at
archived_at

current_correction_request_id

summary
internal_notes
legal_basis
decision_summary

created_by
updated_by

created_at
updated_at
deleted_at
```

Regras:

```text id="pzc83q"
application_id obrigatório e único.
process_number obrigatório e único.
status obrigatório.
assigned_to nullable.
current_correction_request_id nullable.
Usar soft deletes.
Não permitir mass assignment de status, process_number ou user_id.
Mudanças de estado devem passar por service.
```

---

## 10.2 AdministrativeProcessStatusHistory

Criar entidade:

```text id="uyow65"
AdministrativeProcessStatusHistory
```

Tabela:

```text id="7lzzq0"
administrative_process_status_histories
```

Campos mínimos:

```text id="689v5c"
id
administrative_process_id
from_status
to_status
changed_by
reason
created_at
```

Regras:

```text id="4ww0pz"
Criar registo sempre que o status muda.
reason é opcional, mas recomendado para decisões.
Não guardar dados sensíveis excessivos em reason.
```

---

## 10.3 ApplicationReview

Criar entidade:

```text id="mdtrz2"
ApplicationReview
```

Tabela:

```text id="zv0plu"
application_reviews
```

Objetivo:

```text id="xdbrvg"
Registar ciclos de análise técnica/administrativa da candidatura.
```

Campos mínimos:

```text id="h6rlgk"
id
administrative_process_id
application_id
review_type
status
reviewed_by
started_at
completed_at
result
summary
internal_notes
created_at
updated_at
deleted_at
```

Valores recomendados para `review_type`:

```text id="w5427u"
preliminary
documental
eligibility
correction_response
admission
```

Valores recomendados para `status`:

```text id="t3gtmi"
draft
in_progress
completed
cancelled
```

Valores recomendados para `result`:

```text id="g3iino"
passed
failed
requires_correction
requires_manual_review
insufficient_data
not_applicable
```

---

## 10.4 ApplicationReviewItem

Criar entidade:

```text id="n5dh1q"
ApplicationReviewItem
```

Tabela:

```text id="x9celc"
application_review_items
```

Objetivo:

```text id="gi0gt4"
Registar cada ponto analisado dentro de uma análise.
```

Campos mínimos:

```text id="l2hg6k"
id
application_review_id
code
name
category
target_type
target_id
result
message
technical_message
requires_correction
correction_reason
created_at
updated_at
```

Categorias recomendadas:

```text id="7ot1o7"
application_data
documents
eligibility
household
income
housing_situation
identity
deadline
manual
other
```

Resultados recomendados:

```text id="k7w5r3"
passed
failed
requires_correction
requires_manual_review
insufficient_data
not_applicable
```

---

## 10.5 CorrectionRequest

Criar entidade:

```text id="xq5dx6"
CorrectionRequest
```

Tabela:

```text id="c83n0r"
correction_requests
```

Campos mínimos:

```text id="crlkup"
id
administrative_process_id
application_id
user_id

request_number
status
subject
message
legal_basis
instructions

issued_by
issued_at
response_deadline_at
responded_at
closed_at
cancelled_at

candidate_visible
internal_notes

created_at
updated_at
deleted_at
```

Regras:

```text id="llnbxt"
request_number obrigatório e único.
status obrigatório.
response_deadline_at deve ser configurável.
candidate_visible define se aparece ao candidato.
Não enviar automaticamente por email/SMS nesta sprint.
```

---

## 10.6 CorrectionRequestItem

Criar entidade:

```text id="w3e7w3"
CorrectionRequestItem
```

Tabela:

```text id="03yarg"
correction_request_items
```

Campos mínimos:

```text id="hs748u"
id
correction_request_id
target_type
target_id
issue_type
title
description
required_action
status
is_required
document_type_id
required_document_id
sort_order
created_at
updated_at
deleted_at
```

Valores recomendados para `issue_type`:

```text id="24wss1"
missing_document
rejected_document
expired_document
missing_data
inconsistent_data
unclear_information
eligibility_issue
manual_review
other
```

Valores recomendados para `required_action`:

```text id="3k1l0s"
upload_document
replace_document
update_data
provide_explanation
confirm_information
contact_services
other
```

Regras:

```text id="5e714g"
Pode estar associado a documento, campo, agregado, rendimento, situação habitacional ou candidatura.
Itens obrigatórios devem ser respondidos antes de fechar o pedido.
```

---

## 10.7 CorrectionResponse

Criar entidade:

```text id="mk7zsn"
CorrectionResponse
```

Tabela:

```text id="elgu5c"
correction_responses
```

Campos mínimos:

```text id="6cz01p"
id
correction_request_id
correction_request_item_id
application_id
user_id

response_text
document_submission_id
submitted_at
status
reviewed_by
reviewed_at
review_result
review_notes

created_at
updated_at
deleted_at
```

Valores recomendados para `status`:

```text id="jaql6i"
draft
submitted
under_review
accepted
rejected
cancelled
```

Valores recomendados para `review_result`:

```text id="6ir1rl"
accepted
rejected
requires_more_information
not_applicable
```

Regras:

```text id="gdkhwp"
Candidato só responde aos seus próprios pedidos.
Resposta pode ter texto, documento associado ou ambos.
Documentos devem usar o sistema documental da Sprint 6, se existir.
Não guardar ficheiros diretamente nesta tabela.
```

---

## 10.8 AdministrativeDecision

Criar entidade:

```text id="6x6orp"
AdministrativeDecision
```

Tabela:

```text id="9ync75"
administrative_decisions
```

Objetivo:

```text id="tzy08n"
Registar decisão administrativa de admissão ou não admissão para classificação.
```

Campos mínimos:

```text id="4dt5n7"
id
administrative_process_id
application_id
decision_type
decision_result
status
summary
legal_basis
grounds
decided_by
decided_at
approved_by
approved_at
candidate_visible
created_at
updated_at
deleted_at
```

Valores recomendados para `decision_type`:

```text id="7iyann"
admission_for_scoring
non_admission
correction_outcome
administrative_closure
```

Valores recomendados para `decision_result`:

```text id="zo38tr"
admitted_for_scoring
not_admitted
requires_correction
closed
cancelled
```

Valores recomendados para `status`:

```text id="y6l5ve"
draft
proposed
approved
cancelled
```

Regras:

```text id="emj7gj"
Decisão pode ser proposta por técnico.
Decisão pode exigir aprovação, se a política assim definir.
Não transformar decisão em lista pública nesta sprint.
```

---

## 10.9 AdministrativeTask

Criar entidade:

```text id="s4l67a"
AdministrativeTask
```

Tabela:

```text id="ahm9lq"
administrative_tasks
```

Campos mínimos:

```text id="moawxu"
id
administrative_process_id
application_id
title
description
status
priority
assigned_to
due_at
completed_at
created_by
created_at
updated_at
deleted_at
```

Estados recomendados:

```text id="y6vds1"
open
in_progress
completed
cancelled
overdue
```

Prioridades recomendadas:

```text id="kpp3o5"
low
normal
high
urgent
```

---

## 10.10 AdministrativeProcessNote

Criar entidade:

```text id="dshhn9"
AdministrativeProcessNote
```

Tabela:

```text id="9l505p"
administrative_process_notes
```

Campos mínimos:

```text id="mzlq89"
id
administrative_process_id
application_id
user_id
visibility
note_type
body
created_at
updated_at
deleted_at
```

Valores de `visibility`:

```text id="7nd39p"
internal
candidate_visible
audit_only
```

Regras:

```text id="j6csig"
Por defeito, notas devem ser internal.
Notas internas nunca aparecem ao candidato.
candidate_visible deve ser usado com cuidado.
```

---

## 10.11 AdministrativeWorkflowConfig

Criar entidade opcional, mas recomendada:

```text id="c127rg"
AdministrativeWorkflowConfig
```

Tabela:

```text id="4t25x6"
administrative_workflow_configs
```

Objetivo:

```text id="qh3s95"
Configurar prazos e regras simples por programa/concurso sem hardcoding.
```

Campos mínimos:

```text id="wa2o1v"
id
program_id
contest_id
name
is_active
default_correction_deadline_days
allow_deadline_extension
max_deadline_extensions
auto_mark_overdue
requires_decision_approval
created_at
updated_at
deleted_at
```

Regras:

```text id="6n3aij"
Configuração por concurso prevalece sobre configuração por programa.
Se não existir configuração, usar valores conservadores e documentados.
Prazos devem ser validados juridicamente antes de produção.
```

---

# 11. Enums recomendados

Criar, se a versão do PHP permitir:

```text id="r6m7od"
App\Enums\AdministrativeProcessStatus
App\Enums\ApplicationReviewType
App\Enums\ApplicationReviewStatus
App\Enums\ApplicationReviewResult
App\Enums\CorrectionRequestStatus
App\Enums\CorrectionRequestItemStatus
App\Enums\CorrectionIssueType
App\Enums\CorrectionRequiredAction
App\Enums\CorrectionResponseStatus
App\Enums\CorrectionResponseReviewResult
App\Enums\AdministrativeDecisionType
App\Enums\AdministrativeDecisionResult
App\Enums\AdministrativeDecisionStatus
App\Enums\AdministrativeTaskStatus
App\Enums\AdministrativeTaskPriority
App\Enums\AdministrativeNoteVisibility
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 12. Relações obrigatórias

## Application

Adicionar:

```text id="f8ewon"
hasOne AdministrativeProcess
hasMany ApplicationReview
hasMany CorrectionRequest
hasMany CorrectionResponse
hasMany AdministrativeDecision
hasMany AdministrativeTask
hasMany AdministrativeProcessNote
```

## User

Adicionar conforme necessário:

```text id="if9xqg"
hasMany assignedAdministrativeProcesses
hasMany administrativeProcessesCreated
hasMany correctionRequestsIssued
hasMany correctionResponses
hasMany administrativeDecisions
hasMany administrativeTasks
hasMany administrativeProcessNotes
```

## AdministrativeProcess

```text id="jbdsk9"
belongsTo Application
belongsTo Program nullable
belongsTo Contest nullable
belongsTo User as candidate
belongsTo User as assignedTo
belongsTo User as createdBy
belongsTo User as updatedBy
belongsTo CorrectionRequest as currentCorrectionRequest nullable
hasMany AdministrativeProcessStatusHistory
hasMany ApplicationReview
hasMany CorrectionRequest
hasMany CorrectionResponse
hasMany AdministrativeDecision
hasMany AdministrativeTask
hasMany AdministrativeProcessNote
```

## CorrectionRequest

```text id="156say"
belongsTo AdministrativeProcess
belongsTo Application
belongsTo User as candidate
belongsTo User as issuedBy
hasMany CorrectionRequestItem
hasMany CorrectionResponse
```

## CorrectionRequestItem

```text id="zzm3vu"
belongsTo CorrectionRequest
belongsTo DocumentType nullable
belongsTo RequiredDocument nullable
hasMany CorrectionResponse
```

## CorrectionResponse

```text id="zkv4p8"
belongsTo CorrectionRequest
belongsTo CorrectionRequestItem
belongsTo Application
belongsTo User as candidate
belongsTo DocumentSubmission nullable
belongsTo User as reviewedBy
```

## AdministrativeDecision

```text id="jbaqjv"
belongsTo AdministrativeProcess
belongsTo Application
belongsTo User as decidedBy
belongsTo User as approvedBy nullable
```

## AdministrativeTask

```text id="74mg42"
belongsTo AdministrativeProcess
belongsTo Application
belongsTo User as assignedTo
belongsTo User as createdBy
```

## AdministrativeProcessNote

```text id="txr51f"
belongsTo AdministrativeProcess
belongsTo Application
belongsTo User
```

---

# 13. Services obrigatórios

Criar:

```text id="hrw8ay"
App\Services\Administrative\AdministrativeProcessService
App\Services\Administrative\ApplicationIntakeService
App\Services\Administrative\ApplicationReviewService
App\Services\Administrative\CorrectionRequestService
App\Services\Administrative\CorrectionResponseService
App\Services\Administrative\AdministrativeDecisionService
App\Services\Administrative\AdministrativeWorkflowTransitionService
App\Services\Administrative\AdministrativeDeadlineService
App\Services\Administrative\AdministrativeTaskService
App\Services\Administrative\AdministrativeTimelineService
App\Services\Administrative\AdministrativeWorkflowConfigResolver
```

---

## 13.1 AdministrativeProcessService

Responsável por:

```text id="iddh80"
Criar processo para candidatura submetida
Gerar número de processo
Obter processo por candidatura
Atribuir técnico responsável
Atualizar estado via transition service
Consultar resumo processual
Bloquear ações quando processo está encerrado
```

---

## 13.2 ApplicationIntakeService

Responsável por:

```text id="wel98z"
Identificar candidaturas submetidas sem processo
Criar processo administrativo
Marcar candidatura como recebida administrativamente
Criar status history
Criar tarefa inicial de triagem
```

---

## 13.3 ApplicationReviewService

Responsável por:

```text id="n4761o"
Criar análise preliminar
Criar análise documental
Criar análise de elegibilidade/requisitos
Criar análise de resposta ao aperfeiçoamento
Criar review items
Fechar análise com resultado
Identificar necessidade de aperfeiçoamento
Preparar proposta de admissão ou não admissão
```

---

## 13.4 CorrectionRequestService

Responsável por:

```text id="lywqfl"
Criar pedido de aperfeiçoamento
Criar itens de aperfeiçoamento
Gerar número do pedido
Definir prazo de resposta
Emitir pedido
Cancelar pedido
Fechar pedido
Marcar como overdue
Listar pedidos ativos
Validar se pode existir novo pedido
```

Regras:

```text id="xqvsze"
Não permitir múltiplos pedidos abertos simultâneos para o mesmo processo, salvo decisão explícita.
Não permitir pedido de aperfeiçoamento em processo encerrado.
Não permitir emissão sem itens obrigatórios ou mensagem.
```

---

## 13.5 CorrectionResponseService

Responsável por:

```text id="7qm8b0"
Permitir resposta do candidato
Validar prazo
Associar documento substituído ou complementar
Guardar texto de resposta
Submeter resposta
Marcar item como responded
Marcar pedido como responded quando todos os itens obrigatórios tiverem resposta
Permitir análise técnica da resposta
Aceitar ou rejeitar respostas
```

---

## 13.6 AdministrativeDecisionService

Responsável por:

```text id="nt8tdd"
Criar proposta de admissão para classificação
Criar proposta de não admissão
Registar fundamentos
Aprovar decisão, se workflow exigir
Aplicar decisão ao processo
Atualizar estado para admitted_for_scoring ou not_admitted
Registar histórico
Auditar decisão, se auditoria existir
```

Não publicar decisão como lista.

Não notificar formalmente por email/SMS nesta sprint, salvo se o módulo já existir e estiver seguro.

---

## 13.7 AdministrativeWorkflowTransitionService

Responsável por:

```text id="m0nj1r"
Controlar transições de estado permitidas
Impedir saltos inválidos
Criar status history
Executar side effects controlados
Garantir que estados finais bloqueiam alterações indevidas
```

## Transições mínimas permitidas

```text id="d0c42w"
submitted → received
received → assigned
assigned → preliminary_review
preliminary_review → document_review
document_review → eligibility_review
eligibility_review → requires_correction
requires_correction → awaiting_candidate_response
awaiting_candidate_response → correction_submitted
awaiting_candidate_response → correction_overdue
correction_submitted → correction_under_review
correction_under_review → eligibility_review
eligibility_review → admitted_for_scoring
eligibility_review → not_admitted
preliminary_review → not_admitted
admitted_for_scoring → archived
not_admitted → archived
```

Permitir `withdrawn` e `cancelled` apenas por ações específicas autorizadas.

---

## 13.8 AdministrativeDeadlineService

Responsável por:

```text id="rw9ryc"
Calcular prazo de resposta ao aperfeiçoamento
Verificar pedidos vencidos
Marcar correction_overdue quando aplicável
Criar alertas internos ou tarefas
Respeitar configuração por programa/concurso
```

Não criar cron/job obrigatório se a arquitetura não estiver preparada.

Pode criar command Artisan opcional:

```text id="fw1dwe"
php artisan administrative:mark-overdue-corrections
```

se fizer sentido.

---

## 13.9 AdministrativeTaskService

Responsável por:

```text id="ze9uuv"
Criar tarefas administrativas
Atribuir tarefas
Marcar tarefa como concluída
Marcar tarefa como vencida
Listar tarefas abertas do técnico
```

---

## 13.10 AdministrativeTimelineService

Responsável por:

```text id="62btdf"
Gerar timeline processual
Agregar estados
Agregar reviews
Agregar pedidos de aperfeiçoamento
Agregar respostas
Agregar decisões
Agregar notas visíveis, se existirem
Filtrar informação para candidato ou backoffice
```

---

## 13.11 AdministrativeWorkflowConfigResolver

Responsável por:

```text id="pc0m3m"
Resolver configuração por concurso
Resolver configuração por programa
Aplicar fallback documentado
Evitar hardcoding de prazos
```

---

# 14. Controllers

Criar controllers conforme a arquitetura existente.

## Backoffice

Namespace recomendado:

```text id="z3wrgc"
App\Http\Controllers\Backoffice
```

Controllers:

```text id="uag0at"
Backoffice\AdministrativeProcessController
Backoffice\ApplicationIntakeController
Backoffice\ApplicationReviewController
Backoffice\CorrectionRequestController
Backoffice\CorrectionResponseReviewController
Backoffice\AdministrativeDecisionController
Backoffice\AdministrativeTaskController
Backoffice\AdministrativeProcessNoteController
Backoffice\AdministrativeWorkflowConfigController
```

### AdministrativeProcessController

Ações:

```text id="1tg8yc"
index
show
assign
startPreliminaryReview
startDocumentReview
startEligibilityReview
timeline
```

### ApplicationIntakeController

Ações:

```text id="yr2d56"
index
createProcess
createProcessesBatch
```

### ApplicationReviewController

Ações:

```text id="ejlb5c"
create
store
show
complete
```

### CorrectionRequestController

Ações:

```text id="5uygnx"
index
show
create
store
edit
update
issue
cancel
close
markOverdue
```

### CorrectionResponseReviewController

Ações:

```text id="5281q4"
show
accept
reject
requestMoreInformation
```

### AdministrativeDecisionController

Ações:

```text id="2u5r48"
createAdmission
storeAdmission
createNonAdmission
storeNonAdmission
show
approve
cancel
```

### AdministrativeTaskController

Ações:

```text id="z9r3t3"
index
store
update
complete
cancel
```

### AdministrativeProcessNoteController

Ações:

```text id="c3j3r9"
store
update
destroy
```

### AdministrativeWorkflowConfigController

Ações:

```text id="j9te3y"
index
create
store
edit
update
activate
deactivate
```

---

## Área do candidato

Namespace recomendado:

```text id="s1teml"
App\Http\Controllers\Candidate
```

Controllers:

```text id="h4yd19"
Candidate\ApplicationProcessController
Candidate\CorrectionRequestController
Candidate\CorrectionResponseController
```

### Candidate\ApplicationProcessController

Ações:

```text id="jyr7fq"
index
show
timeline
```

### Candidate\CorrectionRequestController

Ações:

```text id="zuipm7"
index
show
```

### Candidate\CorrectionResponseController

Ações:

```text id="ip38rg"
create
store
edit
update
submit
```

---

# 15. Form Requests

Criar:

```text id="4pxgp1"
AssignAdministrativeProcessRequest
StoreApplicationReviewRequest
CompleteApplicationReviewRequest
StoreCorrectionRequestRequest
UpdateCorrectionRequestRequest
IssueCorrectionRequestRequest
StoreCorrectionResponseRequest
SubmitCorrectionResponseRequest
ReviewCorrectionResponseRequest
StoreAdministrativeDecisionRequest
ApproveAdministrativeDecisionRequest
StoreAdministrativeTaskRequest
UpdateAdministrativeTaskRequest
StoreAdministrativeProcessNoteRequest
StoreAdministrativeWorkflowConfigRequest
UpdateAdministrativeWorkflowConfigRequest
```

## StoreCorrectionRequestRequest

Validações mínimas:

```text id="tkc352"
administrative_process_id required|exists:administrative_processes,id
subject required|string|max:255
message required|string|max:5000
legal_basis nullable|string|max:3000
instructions nullable|string|max:5000
response_deadline_at nullable|date|after:now
items required|array|min:1
items.*.title required|string|max:255
items.*.description required|string|max:3000
items.*.issue_type required|string|max:100
items.*.required_action required|string|max:100
items.*.is_required boolean
items.*.document_type_id nullable|exists:document_types,id
items.*.required_document_id nullable|exists:required_documents,id
```

## StoreCorrectionResponseRequest

```text id="aemr6v"
correction_request_item_id required|exists:correction_request_items,id
response_text nullable|string|max:5000
document_submission_id nullable|exists:document_submissions,id
```

Regra adicional:

```text id="hrqea5"
response_text ou document_submission_id deve estar preenchido.
```

## StoreAdministrativeDecisionRequest

```text id="28pprc"
administrative_process_id required|exists:administrative_processes,id
decision_type required|string|max:100
decision_result required|string|max:100
summary required|string|max:3000
legal_basis nullable|string|max:3000
grounds required|string|max:5000
candidate_visible boolean
```

## StoreAdministrativeWorkflowConfigRequest

```text id="hhrbc4"
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
name required|string|max:255
is_active boolean
default_correction_deadline_days required|integer|min:1|max:120
allow_deadline_extension boolean
max_deadline_extensions nullable|integer|min:0|max:10
auto_mark_overdue boolean
requires_decision_approval boolean
```

Regra adicional:

```text id="5fe9y1"
Deve existir program_id ou contest_id.
```

---

# 16. Policies

Criar:

```text id="0ob1s0"
AdministrativeProcessPolicy
ApplicationReviewPolicy
CorrectionRequestPolicy
CorrectionResponsePolicy
AdministrativeDecisionPolicy
AdministrativeTaskPolicy
AdministrativeProcessNotePolicy
AdministrativeWorkflowConfigPolicy
```

## Regras para candidato

```text id="fq2r9x"
Candidato só vê processos da sua própria candidatura.
Candidato só vê pedidos de aperfeiçoamento candidate_visible.
Candidato só responde aos seus próprios pedidos.
Candidato só responde dentro do prazo, salvo regra configurada.
Candidato não vê notas internas.
Candidato não vê technical_message.
Candidato não altera estados administrativos.
Candidato não cria decisões.
Candidato não acede ao backoffice.
```

## Regras para técnico municipal

```text id="0l5q1n"
Pode consultar processos conforme permissão.
Pode ser atribuído a processos.
Pode criar análises.
Pode criar pedidos de aperfeiçoamento.
Pode analisar respostas.
Pode propor admissão ou não admissão.
Pode criar notas internas.
Não pode aprovar decisão se política exigir aprovação superior.
```

## Regras para júri

```text id="mdjtv7"
Pode consultar processos admitidos ou em análise, conforme permissão.
Pode consultar histórico.
Não pode alterar dados sem permissão explícita.
```

## Regras para admin

```text id="dufpuy"
Pode gerir workflow.
Pode configurar prazos.
Pode atribuir processos.
Pode aprovar decisões se permitido.
```

## Regras para auditor

```text id="yg5s7r"
Pode consultar histórico e timeline.
Não pode alterar processos.
Não pode emitir pedidos.
Não pode aprovar decisões.
```

---

# 17. Rotas

## Backoffice

Criar, preferencialmente:

```text id="5q285n"
GET /backoffice/administrative-processes
GET /backoffice/administrative-processes/{administrativeProcess}
POST /backoffice/administrative-processes/{administrativeProcess}/assign
POST /backoffice/administrative-processes/{administrativeProcess}/start-preliminary-review
POST /backoffice/administrative-processes/{administrativeProcess}/start-document-review
POST /backoffice/administrative-processes/{administrativeProcess}/start-eligibility-review
GET /backoffice/administrative-processes/{administrativeProcess}/timeline

GET /backoffice/application-intake
POST /backoffice/application-intake/{application}/create-process
POST /backoffice/application-intake/create-processes-batch

GET /backoffice/administrative-processes/{administrativeProcess}/reviews/create
POST /backoffice/administrative-processes/{administrativeProcess}/reviews
GET /backoffice/application-reviews/{applicationReview}
POST /backoffice/application-reviews/{applicationReview}/complete

GET /backoffice/administrative-processes/{administrativeProcess}/correction-requests
GET /backoffice/administrative-processes/{administrativeProcess}/correction-requests/create
POST /backoffice/administrative-processes/{administrativeProcess}/correction-requests
GET /backoffice/correction-requests/{correctionRequest}
GET /backoffice/correction-requests/{correctionRequest}/edit
PUT/PATCH /backoffice/correction-requests/{correctionRequest}
POST /backoffice/correction-requests/{correctionRequest}/issue
POST /backoffice/correction-requests/{correctionRequest}/cancel
POST /backoffice/correction-requests/{correctionRequest}/close
POST /backoffice/correction-requests/{correctionRequest}/mark-overdue

GET /backoffice/correction-responses/{correctionResponse}
POST /backoffice/correction-responses/{correctionResponse}/accept
POST /backoffice/correction-responses/{correctionResponse}/reject
POST /backoffice/correction-responses/{correctionResponse}/request-more-information

GET /backoffice/administrative-processes/{administrativeProcess}/decisions/create-admission
POST /backoffice/administrative-processes/{administrativeProcess}/decisions/admission
GET /backoffice/administrative-processes/{administrativeProcess}/decisions/create-non-admission
POST /backoffice/administrative-processes/{administrativeProcess}/decisions/non-admission
GET /backoffice/administrative-decisions/{administrativeDecision}
POST /backoffice/administrative-decisions/{administrativeDecision}/approve
POST /backoffice/administrative-decisions/{administrativeDecision}/cancel

GET /backoffice/administrative-tasks
POST /backoffice/administrative-processes/{administrativeProcess}/tasks
PUT/PATCH /backoffice/administrative-tasks/{administrativeTask}
POST /backoffice/administrative-tasks/{administrativeTask}/complete
POST /backoffice/administrative-tasks/{administrativeTask}/cancel

POST /backoffice/administrative-processes/{administrativeProcess}/notes
PUT/PATCH /backoffice/administrative-notes/{administrativeProcessNote}
DELETE /backoffice/administrative-notes/{administrativeProcessNote}

GET /backoffice/administrative-workflow-configs
GET /backoffice/administrative-workflow-configs/create
POST /backoffice/administrative-workflow-configs
GET /backoffice/administrative-workflow-configs/{administrativeWorkflowConfig}/edit
PUT/PATCH /backoffice/administrative-workflow-configs/{administrativeWorkflowConfig}
POST /backoffice/administrative-workflow-configs/{administrativeWorkflowConfig}/activate
POST /backoffice/administrative-workflow-configs/{administrativeWorkflowConfig}/deactivate
```

## Área do candidato

Criar, preferencialmente:

```text id="h4iptj"
GET /area-candidato/processos
GET /area-candidato/processos/{administrativeProcess}
GET /area-candidato/processos/{administrativeProcess}/timeline

GET /area-candidato/pedidos-aperfeicoamento
GET /area-candidato/pedidos-aperfeicoamento/{correctionRequest}
GET /area-candidato/pedidos-aperfeicoamento/{correctionRequest}/responder
POST /area-candidato/pedidos-aperfeicoamento/{correctionRequest}/respostas
GET /area-candidato/respostas-aperfeicoamento/{correctionResponse}/editar
PUT/PATCH /area-candidato/respostas-aperfeicoamento/{correctionResponse}
POST /area-candidato/pedidos-aperfeicoamento/{correctionRequest}/submeter
```

---

# 18. Views / páginas

Se o projeto usa Blade, criar:

## Backoffice

```text id="wka5jy"
resources/views/backoffice/administrative-processes/index.blade.php
resources/views/backoffice/administrative-processes/show.blade.php
resources/views/backoffice/administrative-processes/timeline.blade.php

resources/views/backoffice/application-intake/index.blade.php

resources/views/backoffice/application-reviews/create.blade.php
resources/views/backoffice/application-reviews/show.blade.php

resources/views/backoffice/correction-requests/index.blade.php
resources/views/backoffice/correction-requests/create.blade.php
resources/views/backoffice/correction-requests/edit.blade.php
resources/views/backoffice/correction-requests/show.blade.php

resources/views/backoffice/correction-responses/show.blade.php

resources/views/backoffice/administrative-decisions/create-admission.blade.php
resources/views/backoffice/administrative-decisions/create-non-admission.blade.php
resources/views/backoffice/administrative-decisions/show.blade.php

resources/views/backoffice/administrative-tasks/index.blade.php

resources/views/backoffice/administrative-workflow-configs/index.blade.php
resources/views/backoffice/administrative-workflow-configs/create.blade.php
resources/views/backoffice/administrative-workflow-configs/edit.blade.php
```

## Área do candidato

```text id="9v5v14"
resources/views/candidate/processes/index.blade.php
resources/views/candidate/processes/show.blade.php
resources/views/candidate/processes/timeline.blade.php

resources/views/candidate/correction-requests/index.blade.php
resources/views/candidate/correction-requests/show.blade.php
resources/views/candidate/correction-requests/respond.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 19. UX obrigatória no backoffice

## Lista de processos administrativos

Mostrar:

```text id="csbod5"
Número do processo
Número da candidatura
Candidato
Concurso
Programa
Estado administrativo
Técnico responsável
Data de submissão
Data de receção
Prazo ativo, se existir
Ações
```

Filtros recomendados:

```text id="wagx2z"
Estado
Concurso
Programa
Técnico responsável
Pedidos de aperfeiçoamento em aberto
Prazos vencidos
Pesquisa por número de candidatura/processo
```

## Detalhe do processo

Mostrar:

```text id="9eqvd4"
Dados principais
Estado atual
Timeline
Candidatura
Registo de adesão
Agregado
Rendimentos
Situação habitacional
Documentos
Elegibilidade, se existir
Pedidos de aperfeiçoamento
Respostas
Análises
Decisões
Tarefas
Notas internas
Ações administrativas disponíveis
```

## Timeline

A timeline deve incluir:

```text id="mdycuy"
Submissão da candidatura
Criação do processo
Mudanças de estado
Atribuição de técnico
Início de análises
Pedidos de aperfeiçoamento
Respostas do candidato
Validações/rejeições
Decisões
Arquivamento/cancelamento
```

## Pedido de aperfeiçoamento

A página deve permitir criar itens como:

```text id="4dryvp"
Documento em falta
Documento rejeitado
Dado em falta
Dado inconsistente
Esclarecimento necessário
Resposta obrigatória
```

Antes de emitir, mostrar confirmação:

```text id="3zlhwf"
Ao emitir este pedido, o candidato passará a poder responder através da sua área pessoal. Confirme que os itens, instruções e prazo estão corretos.
```

---

# 20. UX obrigatória para candidato

## Lista de pedidos de aperfeiçoamento

Mostrar:

```text id="wp8z4h"
Número do pedido
Candidatura
Estado
Data de emissão
Prazo de resposta
Itens pendentes
Ações
```

## Detalhe do pedido

Mostrar:

```text id="tm4qtr"
Assunto
Mensagem
Instruções
Prazo
Itens solicitados
Estado de cada item
Formulário de resposta
Documentos associados
Histórico de respostas
```

## Copy obrigatório

```text id="8did5w"
Os serviços municipais solicitaram informação adicional ou correção de elementos da sua candidatura. Responda dentro do prazo indicado para que a análise possa prosseguir.
```

## Prazo vencido

```text id="vrdaga"
O prazo de resposta a este pedido encontra-se vencido. Poderá contactar os serviços municipais para esclarecimentos.
```

## Após submissão da resposta

```text id="7k671q"
A sua resposta foi submetida com sucesso e ficará disponível para análise pelos serviços municipais.
```

---

# 21. Integração com documentos

Se Sprint 6 existir:

```text id="y5q4j7"
Pedidos de aperfeiçoamento podem solicitar documentos.
Itens podem apontar para document_type_id ou required_document_id.
Candidato deve usar DocumentUploadService para submeter/substituir documentos.
CorrectionResponse deve guardar document_submission_id.
Documentos substituídos devem manter versionamento.
Backoffice deve ver estado documental atualizado.
```

Não guardar ficheiros diretamente no módulo de aperfeiçoamento.

Se Sprint 6 não existir, documentar pendência.

---

# 22. Integração com elegibilidade

Se Sprint 7 existir:

```text id="k7bp8o"
Backoffice deve conseguir ver último EligibilityCheck da candidatura.
Após resposta ao aperfeiçoamento, permitir reexecutar ou assinalar necessidade de reavaliação.
Não alterar resultado de elegibilidade diretamente fora do motor.
```

Se Sprint 7 não existir:

```text id="0n9ud9"
Permitir análise manual de requisitos.
Documentar pendência de integração com motor de elegibilidade.
```

---

# 23. Integração com classificação

A Sprint 9 deve preparar a Sprint 10.

Apenas candidaturas com processo em estado:

```text id="a8tmg0"
admitted_for_scoring
```

devem ser elegíveis para classificação.

Adicionar método/escopo:

```text id="5tmw3b"
AdministrativeProcess::admittedForScoring()
Application::admittedForScoring()
```

ou equivalente.

Não implementar ranking nesta sprint.

Não implementar pontuação nesta sprint.

---

# 24. Auditoria

Se existir auditoria, auditar:

```text id="flzajd"
Criação de processo administrativo
Atribuição de técnico
Mudança de estado administrativo
Criação de análise
Conclusão de análise
Criação de pedido de aperfeiçoamento
Emissão de pedido de aperfeiçoamento
Cancelamento de pedido de aperfeiçoamento
Resposta do candidato
Análise da resposta
Criação de decisão administrativa
Aprovação de decisão administrativa
Admissão para classificação
Não admissão
Criação/alteração de notas internas
```

Não criar auditoria paralela.

Se auditoria não existir, documentar pendência.

Não guardar dados sensíveis excessivos nos logs.

---

# 25. RGPD e segurança

Regras obrigatórias:

```text id="56qzbb"
Processos administrativos contêm dados sensíveis.
Candidato só vê os seus próprios processos.
Candidato só vê pedidos candidate_visible.
Candidato não vê notas internas.
Candidato não vê mensagens técnicas.
Candidato não vê dados de outros candidatos.
Backoffice exige permissões.
Júri só acede conforme permissão.
Auditor não altera dados.
Não expor dados em rotas públicas.
Não expor fundamentos internos ao candidato sem candidate_visible.
Não guardar documentos em tabelas de resposta.
Não guardar paths internos.
Não enviar dados sensíveis por email/SMS nesta sprint.
Não permitir mass assignment de status.
Não permitir alteração direta de decisões fora dos services.
Não permitir apagar histórico de estados.
```

---

# 26. Seeders e factories

Criar factories:

```text id="d9acai"
AdministrativeProcessFactory
AdministrativeProcessStatusHistoryFactory
ApplicationReviewFactory
ApplicationReviewItemFactory
CorrectionRequestFactory
CorrectionRequestItemFactory
CorrectionResponseFactory
AdministrativeDecisionFactory
AdministrativeTaskFactory
AdministrativeProcessNoteFactory
AdministrativeWorkflowConfigFactory
```

Criar seeders opcionais:

```text id="s6j54o"
AdministrativeWorkflowConfigSeeder
AdministrativeDemoProcessSeeder
```

Dados demo permitidos:

```text id="f4nxjv"
Processo Administrativo Demo
Pedido de Aperfeiçoamento Demo
Tarefa Administrativa Demo
```

Usar apenas dados fictícios.

Não usar dados pessoais reais.

---

# 27. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text id="sn6lrs"
guest_cannot_access_backoffice_administrative_processes
candidate_cannot_access_backoffice_administrative_processes
technician_can_access_assigned_administrative_process
admin_can_access_all_administrative_processes
candidate_can_access_own_process
candidate_cannot_access_other_candidate_process
candidate_cannot_see_internal_notes
candidate_cannot_see_non_visible_correction_request
```

## Criação de processo

```text id="88rg6e"
administrative_process_can_be_created_for_submitted_application
administrative_process_requires_application
application_can_have_only_one_administrative_process
process_number_is_unique
process_initial_status_is_received_or_submitted
creating_process_creates_status_history
```

## Atribuição e estados

```text id="i9gatd"
technician_can_be_assigned_to_process
assigning_process_updates_assigned_at
valid_status_transition_is_allowed
invalid_status_transition_is_blocked
status_transition_creates_history
final_status_blocks_invalid_changes
```

## Análises

```text id="q6cfo0"
technician_can_create_preliminary_review
technician_can_create_document_review
technician_can_create_eligibility_review
review_can_have_items
review_completion_sets_result
review_requiring_correction_can_generate_correction_request
```

## Pedido de aperfeiçoamento

```text id="9my1s5"
technician_can_create_correction_request
correction_request_requires_at_least_one_item
correction_request_can_be_issued
issued_correction_request_has_deadline
candidate_can_see_visible_issued_correction_request
candidate_cannot_see_draft_correction_request
candidate_cannot_see_other_candidate_correction_request
overdue_correction_request_can_be_marked_overdue
```

## Resposta do candidato

```text id="yo5nsy"
candidate_can_submit_response_to_own_correction_request
candidate_cannot_submit_response_to_other_candidate_request
response_requires_text_or_document
response_can_link_document_submission
response_marks_item_as_responded
all_required_items_responded_marks_request_as_responded
candidate_cannot_respond_after_deadline_when_policy_blocks_it
```

## Análise da resposta

```text id="7al5u3"
technician_can_accept_correction_response
technician_can_reject_correction_response
accepting_required_items_allows_correction_request_to_close
rejected_response_can_keep_request_open_or_require_more_information
```

## Decisão administrativa

```text id="kt4c4v"
technician_can_create_admission_decision_proposal
technician_can_create_non_admission_decision_proposal
decision_requires_grounds
decision_can_be_approved_when_policy_allows
approved_admission_sets_process_admitted_for_scoring
approved_non_admission_sets_process_not_admitted
decision_creates_status_history
```

## Integração com classificação

```text id="tneexg"
application_admitted_for_scoring_scope_returns_only_admitted_processes
application_not_admitted_is_not_admitted_for_scoring
draft_or_submitted_without_process_is_not_admitted_for_scoring
```

## Segurança

```text id="xmgfog"
candidate_cannot_mass_assign_process_status
candidate_cannot_mass_assign_decision_result
candidate_cannot_create_administrative_decision
candidate_cannot_create_internal_note
internal_notes_are_not_visible_to_candidate
technical_messages_are_not_visible_to_candidate
```

## Auditoria, se existir

```text id="5ykjhg"
creating_process_generates_audit_log
issuing_correction_request_generates_audit_log
submitting_correction_response_generates_audit_log
approving_administrative_decision_generates_audit_log
```

Se alguma dependência não existir, documentar teste como pendente em vez de criar teste quebrado.

---

# 28. Comandos de validação

No final, executar:

```bash id="099kd9"
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash id="o0il55"
php artisan migrate
```

Se o projeto usar frontend build:

```bash id="minhrm"
npm run build
```

Se o projeto usar Pint:

```bash id="rwxxs0"
./vendor/bin/pint
```

Se existir PHPStan/Psalm:

```bash id="hbv165"
./vendor/bin/phpstan analyse
```

Se algum comando falhar, documentar:

```text id="v0b4ce"
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
```

Não afirmar que comandos passaram se não foram executados.

---

# 29. Atualização documental obrigatória

Atualizar, se existirem:

```text id="37prl9"
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
docs/backlog/roadmap.md
docs/product/functional-requirements.md
docs/product/process-workflows.md
docs/architecture/data-model-overview.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Documentar:

```text id="b5193v"
O que foi implementado
Tabelas criadas
Models criados
Enums criados
Controllers criados
Requests criados
Policies criadas
Services criados
Views criadas
Rotas criadas
Seeders/factories criados
Testes criados
Comandos executados
Resultado dos comandos
Problemas encontrados
Pendências para Sprint 10 e Sprint 11
Validações jurídicas pendentes
```

---

# 30. Critérios de aceitação

A Sprint 9 está concluída quando:

```text id="u31rtn"
Existe processo administrativo associado à candidatura submetida
Cada candidatura tem no máximo um processo administrativo ativo
O processo administrativo tem número único
O processo administrativo tem estado formal
Mudanças de estado criam histórico
Backoffice consegue listar processos
Backoffice consegue consultar detalhe do processo
Backoffice consegue atribuir técnico responsável
Backoffice consegue iniciar triagem/análise
Backoffice consegue criar análises com itens
Backoffice consegue criar pedido de aperfeiçoamento
Pedido de aperfeiçoamento tem prazo
Pedido de aperfeiçoamento tem itens
Candidato vê pedidos emitidos e visíveis
Candidato consegue responder a pedidos próprios
Candidato consegue associar documento à resposta quando Sprint 6 existe
Backoffice consegue analisar resposta
Backoffice consegue aceitar ou rejeitar resposta
Backoffice consegue propor admissão para classificação
Backoffice consegue propor não admissão
Decisão exige fundamentação
Decisão aprovada atualiza estado do processo
Candidaturas admitidas ficam disponíveis para Sprint 10
Candidaturas não admitidas não seguem para classificação
Notas internas não aparecem ao candidato
Candidato não vê processos de outros candidatos
Backoffice exige permissões
Auditoria é usada se existir
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foi implementada classificação
Não foi implementado ranking
Não foram implementadas listas provisórias
Não foi implementada atribuição
```

---

# 31. Resposta final esperada do Codex

No final da execução, responder com:

```text id="mzs2y6"
1. Sprint executada
2. Resumo do que foi implementado
3. Ficheiros criados
4. Ficheiros alterados
5. Migrations criadas
6. Models criados ou alterados
7. Enums criados
8. Controllers criados ou alterados
9. Form Requests criados
10. Policies criadas
11. Services criados
12. Views/páginas criadas ou alteradas
13. Rotas criadas
14. Seeders/factories criados ou alterados
15. Testes criados ou alterados
16. Resultado dos comandos executados
17. Problemas encontrados
18. Pendências
19. Validações jurídicas pendentes
20. Confirmação de que não foram implementadas funcionalidades fora de âmbito
21. Recomendação objetiva para avançar ou não para Sprint 10
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 32. Definition of Done

A Sprint 9 só está concluída quando a plataforma permitir gerir administrativamente uma candidatura submetida, pedir aperfeiçoamento, receber resposta do candidato, registar análise e decidir se a candidatura fica admitida ou não admitida para classificação.

O resultado deve permitir que a Sprint 10 classifique apenas candidaturas formalmente preparadas e admitidas para classificação.

Fim da Sprint 9.

---

## Execução técnica em 11/06/2026

### Implementado

- Migration `2026_06_11_030000_create_administrative_workflow_tables.php`.
- Enums formais para estados de processo, análises, aperfeiçoamento, respostas, decisões, tarefas e notas.
- Models e relações para `AdministrativeProcess`, histórico de estado, reviews, pedidos/itens/respostas de aperfeiçoamento, decisões, tarefas, notas e configurações.
- Services de processo, intake, transições, prazos, análises, aperfeiçoamento, respostas, decisões, tarefas, notas, timeline e resolução de configuração.
- Policies e Form Requests para proteger backoffice, candidato e campos críticos.
- Rotas de backoffice e área do candidato para Sprint 9.
- Views Blade para receção, processos, timeline, análises, pedidos, respostas, decisões, tarefas, configurações e acompanhamento pelo candidato.
- Factories das entidades administrativas e seeders opcionais de configuração/demo.
- Teste `Sprint9AdministrativeWorkflowTest` com fluxo principal e proteção de acesso.

### Ficheiros criados

- `app/Enums/AdministrativeProcessStatus.php` e enums relacionados da Sprint 9.
- `app/Models/AdministrativeProcess.php` e models administrativos relacionados.
- `app/Services/Administrative/*`.
- Controllers backoffice e candidate da Sprint 9.
- Requests e policies da Sprint 9.
- Views em `resources/views/backoffice/*` e `resources/views/candidate/*`.
- Factories e seeders administrativos.
- `tests/Feature/Sprint9AdministrativeWorkflowTest.php`.

### Ficheiros alterados

- `routes/web.php`.
- `config/mvhab.php`.
- `app/Models/Application.php`.
- `app/Models/User.php`.
- `app/Models/Program.php`.
- `app/Models/Contest.php`.
- `resources/views/layouts/navigation.blade.php`.
- Documentação de roadmap, requisitos, workflows, modelo de dados, segurança, RGPD e QA.

### Comandos e resultados registados

- `php artisan route:list`: executou com sucesso antes dos testes, com 254 rotas.
- `php artisan migrate`: primeira tentativa falhou por nome automático de constraint demasiado longo no MySQL.
- `php artisan tinker --execute='Schema::dropIfExists(...)'`: usado apenas para remover tabelas novas parcialmente criadas pela migration falhada.
- `php artisan migrate`: segunda tentativa executou com sucesso.
- `php artisan test tests/Feature/Sprint9AdministrativeWorkflowTest.php`: passou com 6 testes e 32 asserções.
- `php artisan test`: passou com 99 testes e 557 asserções.
- `npm run build`: passou com Vite.
- `./vendor/bin/pint`: executou e formatou ficheiros da Sprint 9.
- `ls vendor/bin | rg 'phpstan|psalm'`: não encontrou PHPStan nem Psalm instalados.

### Pendências para Sprint 10

- Usar exclusivamente `Application::admittedForScoring()` ou `AdministrativeProcess::admittedForScoring()` como universo de classificação.
- Não classificar candidaturas em `submitted`, `received`, `requires_correction`, `awaiting_candidate_response`, `not_admitted` ou sem processo administrativo.
- Validar juridicamente critérios, pesos, desempates e snapshots de classificação antes de implementar ranking.

### Validações jurídicas pendentes

- Prazo padrão de aperfeiçoamento e bloqueio após vencimento.
- Textos-base de pedidos, instruções, fundamentos e visibilidade ao candidato.
- Necessidade de aprovação superior de decisões por programa/concurso.
- Prova de comunicação formal, a tratar na Sprint 16.
