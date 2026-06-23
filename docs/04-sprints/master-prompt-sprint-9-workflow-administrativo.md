# MASTER PROMPT — EXECUÇÃO DA SPRINT 9: WORKFLOW ADMINISTRATIVO E APERFEIÇOAMENTO

Atua como arquiteto sénior Laravel, tech lead e product engineer.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
```

Esta sprint deve ser executada depois da Sprint 8 e antes da Sprint 10.

A Sprint 9 cria a camada administrativa necessária para que candidaturas submetidas possam ser recebidas, analisadas, aperfeiçoadas, reavaliadas e admitidas ou não admitidas para classificação.

---

# 1. Regra principal

Executa apenas a Sprint 9.

Não avances para Sprint 10, Sprint 11, Sprint 12 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash
git branch --show-current
```

Não interromper a execução por causa da branch atual.

---

# 2. Ficheiro principal da sprint

Usa como referência principal:

```text
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 9.

Não improvisar uma implementação sem o ficheiro de sprint.

---

# 3. Documentação obrigatória a ler antes de implementar

Antes de alterar código, lê, se existirem:

```text
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

---

# 4. Inspeção inicial obrigatória

Antes de implementar, inspeciona o repositório e identifica:

```text
Versão do Laravel
Versão do PHP
Sistema de autenticação
Sistema de roles/permissões
Stack frontend
Estrutura de rotas
Controllers existentes
Models existentes
Migrations existentes
Seeders existentes
Factories existentes
Policies existentes
Services existentes
Views/componentes existentes
Tests existentes
Sistema de auditoria, se existir
Sistema de notificações, se existir
Sistema documental, se existir
Sistema de elegibilidade, se existir
Modelo User
Modelo Program
Modelo Contest
Modelo Application
Modelo ApplicationStatusHistory
Modelo ApplicationSnapshot
Modelo ApplicationDocument
Modelo DocumentSubmission
Modelo DocumentReview
Modelo EligibilityCheck
Modelo EligibilityCheckResult
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
AdministrativeProcess
ApplicationWorkflow
ApplicationReview
ApplicationReviewItem
CorrectionRequest
CorrectionRequestItem
CorrectionResponse
AdministrativeDecision
AdministrativeTask
AdministrativeProcessNote
AdministrativeWorkflowConfig
ProcessTimeline
AdmissionDecision
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY ou credenciais.

---

# 5. Dependências obrigatórias

Esta sprint depende obrigatoriamente da Sprint 8.

Se `Application` não existir, interrompe a implementação funcional e informa:

```text
A Sprint 9 depende da Sprint 8 — Candidaturas e Submissão Formal.
```

Esta sprint depende preferencialmente de:

```text
Sprint 6 — Gestão Documental Avançada
Sprint 7 — Motor de Elegibilidade
Sprint 8 — Candidaturas e Submissão Formal
```

## Se Sprint 6 não existir

Implementar o workflow administrativo sem validação documental avançada, mas documentar pendência.

Não criar sistema documental paralelo simplificado.

## Se Sprint 7 não existir

Implementar o workflow administrativo e permitir análise manual dos requisitos, mas documentar que a avaliação automática de elegibilidade fica pendente.

Não criar motor de elegibilidade simplificado dentro desta sprint.

## Se Sprint 8 existir

Integrar obrigatoriamente com candidaturas submetidas.

A Sprint 9 deve atuar apenas sobre candidaturas em estados adequados, normalmente:

```text
submitted
under_review
correction_submitted
```

ou equivalentes existentes no projeto.

---

# 6. Validação jurídica obrigatória

Esta sprint deve ser implementada com cautela jurídica.

Não implementar decisões automáticas irreversíveis.

Não criar exclusões automáticas definitivas.

Não hardcodar prazos legais sem validação jurídica.

Qualquer prazo, estado, fundamento, mensagem processual ou regra com impacto administrativo deve ser:

```text
Configurável;
Ou documentado como pendente de validação jurídica;
Ou implementado de forma conservadora e reversível.
```

Regras obrigatórias:

```text
Pedidos de aperfeiçoamento devem ficar registados.
Prazos devem ficar registados.
Respostas do candidato devem ficar registadas.
Motivos de não admissão devem ser fundamentados.
Alterações de estado devem gerar histórico.
Decisões devem identificar quem decidiu e quando.
Notas internas não devem ser visíveis ao candidato.
Comunicações formais reais ficam para sprint própria, salvo se já existir módulo seguro.
```

Esta sprint pode gerar registos de comunicação e textos-base, mas não deve implementar envio real de email/SMS se esse módulo ainda não existir.

---

# 7. Objetivo da implementação

Implementar o workflow administrativo de análise inicial das candidaturas submetidas.

A plataforma deve permitir que os serviços municipais:

```text
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

A Sprint 9 deve criar o estado final que permitirá à Sprint 10 classificar apenas candidaturas administrativamente preparadas.

Estado recomendado:

```text
admitted_for_scoring
```

ou equivalente:

```text
eligible_for_classification
```

---

# 8. Âmbito incluído

Implementar:

```text
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

# 9. Fora de âmbito

Não implementar nesta sprint:

```text
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
Notificações reais por email, salvo se já existir módulo seguro
Integração com Autoridade Tributária
Integração com Segurança Social
Integração com Autenticação.GOV
Assinatura digital
OCR
```

Podem ser criados pontos de integração para estas funcionalidades futuras, mas não implementar essas fases.

---

# 10. Fluxo funcional obrigatório

O fluxo administrativo deve funcionar assim:

```text
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

# 11. Estados administrativos

Criar estados formais para o processo administrativo.

## AdministrativeProcessStatus

```text
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

# 12. Estados do pedido de aperfeiçoamento

## CorrectionRequestStatus

```text
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

## CorrectionRequestItemStatus

```text
pending
responded
accepted
rejected
waived
cancelled
```

## CorrectionResponseStatus

```text
draft
submitted
under_review
accepted
rejected
cancelled
```

---

# 13. Modelo de dados a implementar

## 13.1 AdministrativeProcess

Criar entidade:

```text
AdministrativeProcess
```

Tabela:

```text
administrative_processes
```

Campos mínimos:

```text
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

```text
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

## 13.2 AdministrativeProcessStatusHistory

Criar entidade:

```text
AdministrativeProcessStatusHistory
```

Tabela:

```text
administrative_process_status_histories
```

Campos mínimos:

```text
id
administrative_process_id
from_status
to_status
changed_by
reason
created_at
```

Regras:

```text
Criar registo sempre que o status muda.
reason é opcional, mas recomendado para decisões.
Não guardar dados sensíveis excessivos em reason.
```

---

## 13.3 ApplicationReview

Criar entidade:

```text
ApplicationReview
```

Tabela:

```text
application_reviews
```

Objetivo:

```text
Registar ciclos de análise técnica/administrativa da candidatura.
```

Campos mínimos:

```text
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

```text
preliminary
documental
eligibility
correction_response
admission
```

Valores recomendados para `status`:

```text
draft
in_progress
completed
cancelled
```

Valores recomendados para `result`:

```text
passed
failed
requires_correction
requires_manual_review
insufficient_data
not_applicable
```

---

## 13.4 ApplicationReviewItem

Criar entidade:

```text
ApplicationReviewItem
```

Tabela:

```text
application_review_items
```

Campos mínimos:

```text
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

```text
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

```text
passed
failed
requires_correction
requires_manual_review
insufficient_data
not_applicable
```

---

## 13.5 CorrectionRequest

Criar entidade:

```text
CorrectionRequest
```

Tabela:

```text
correction_requests
```

Campos mínimos:

```text
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

```text
request_number obrigatório e único.
status obrigatório.
response_deadline_at configurável.
candidate_visible define se aparece ao candidato.
Não enviar automaticamente por email/SMS nesta sprint.
```

---

## 13.6 CorrectionRequestItem

Criar entidade:

```text
CorrectionRequestItem
```

Tabela:

```text
correction_request_items
```

Campos mínimos:

```text
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

```text
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

```text
upload_document
replace_document
update_data
provide_explanation
confirm_information
contact_services
other
```

Regras:

```text
Pode estar associado a documento, campo, agregado, rendimento, situação habitacional ou candidatura.
Itens obrigatórios devem ser respondidos antes de fechar o pedido.
```

---

## 13.7 CorrectionResponse

Criar entidade:

```text
CorrectionResponse
```

Tabela:

```text
correction_responses
```

Campos mínimos:

```text
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

Valores recomendados para `review_result`:

```text
accepted
rejected
requires_more_information
not_applicable
```

Regras:

```text
Candidato só responde aos seus próprios pedidos.
Resposta pode ter texto, documento associado ou ambos.
Documentos devem usar o sistema documental da Sprint 6, se existir.
Não guardar ficheiros diretamente nesta tabela.
```

---

## 13.8 AdministrativeDecision

Criar entidade:

```text
AdministrativeDecision
```

Tabela:

```text
administrative_decisions
```

Objetivo:

```text
Registar decisão administrativa de admissão ou não admissão para classificação.
```

Campos mínimos:

```text
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

```text
admission_for_scoring
non_admission
correction_outcome
administrative_closure
```

Valores recomendados para `decision_result`:

```text
admitted_for_scoring
not_admitted
requires_correction
closed
cancelled
```

Valores recomendados para `status`:

```text
draft
proposed
approved
cancelled
```

Regras:

```text
Decisão pode ser proposta por técnico.
Decisão pode exigir aprovação, se a política assim definir.
Não transformar decisão em lista pública nesta sprint.
```

---

## 13.9 AdministrativeTask

Criar entidade:

```text
AdministrativeTask
```

Tabela:

```text
administrative_tasks
```

Campos mínimos:

```text
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

```text
open
in_progress
completed
cancelled
overdue
```

Prioridades recomendadas:

```text
low
normal
high
urgent
```

---

## 13.10 AdministrativeProcessNote

Criar entidade:

```text
AdministrativeProcessNote
```

Tabela:

```text
administrative_process_notes
```

Campos mínimos:

```text
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

```text
internal
candidate_visible
audit_only
```

Regras:

```text
Por defeito, notas devem ser internal.
Notas internas nunca aparecem ao candidato.
candidate_visible deve ser usado com cuidado.
```

---

## 13.11 AdministrativeWorkflowConfig

Criar entidade:

```text
AdministrativeWorkflowConfig
```

Tabela:

```text
administrative_workflow_configs
```

Objetivo:

```text
Configurar prazos e regras simples por programa/concurso sem hardcoding.
```

Campos mínimos:

```text
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

```text
Configuração por concurso prevalece sobre configuração por programa.
Se não existir configuração, usar valores conservadores e documentados.
Prazos devem ser validados juridicamente antes de produção.
```

---

# 14. Enums a criar

Criar, se a versão do PHP permitir:

```text
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

# 15. Relações obrigatórias

## Application

Adicionar:

```text
hasOne AdministrativeProcess
hasMany ApplicationReview
hasMany CorrectionRequest
hasMany CorrectionResponse
hasMany AdministrativeDecision
hasMany AdministrativeTask
hasMany AdministrativeProcessNote
```

## AdministrativeProcess

```text
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

```text
belongsTo AdministrativeProcess
belongsTo Application
belongsTo User as candidate
belongsTo User as issuedBy
hasMany CorrectionRequestItem
hasMany CorrectionResponse
```

## CorrectionRequestItem

```text
belongsTo CorrectionRequest
belongsTo DocumentType nullable
belongsTo RequiredDocument nullable
hasMany CorrectionResponse
```

## CorrectionResponse

```text
belongsTo CorrectionRequest
belongsTo CorrectionRequestItem
belongsTo Application
belongsTo User as candidate
belongsTo DocumentSubmission nullable
belongsTo User as reviewedBy
```

## AdministrativeDecision

```text
belongsTo AdministrativeProcess
belongsTo Application
belongsTo User as decidedBy
belongsTo User as approvedBy nullable
```

## AdministrativeTask

```text
belongsTo AdministrativeProcess
belongsTo Application
belongsTo User as assignedTo
belongsTo User as createdBy
```

## AdministrativeProcessNote

```text
belongsTo AdministrativeProcess
belongsTo Application
belongsTo User
```

---

# 16. Services obrigatórios

Criar:

```text
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

## AdministrativeProcessService

Responsável por:

```text
Criar processo para candidatura submetida
Gerar número de processo
Obter processo por candidatura
Atribuir técnico responsável
Atualizar estado via transition service
Consultar resumo processual
Bloquear ações quando processo está encerrado
```

---

## ApplicationIntakeService

Responsável por:

```text
Identificar candidaturas submetidas sem processo
Criar processo administrativo
Marcar candidatura como recebida administrativamente
Criar status history
Criar tarefa inicial de triagem
```

---

## ApplicationReviewService

Responsável por:

```text
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

## CorrectionRequestService

Responsável por:

```text
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

```text
Não permitir múltiplos pedidos abertos simultâneos para o mesmo processo, salvo decisão explícita.
Não permitir pedido de aperfeiçoamento em processo encerrado.
Não permitir emissão sem itens obrigatórios ou mensagem.
```

---

## CorrectionResponseService

Responsável por:

```text
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

## AdministrativeDecisionService

Responsável por:

```text
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

## AdministrativeWorkflowTransitionService

Responsável por:

```text
Controlar transições de estado permitidas
Impedir saltos inválidos
Criar status history
Executar side effects controlados
Garantir que estados finais bloqueiam alterações indevidas
```

## Transições mínimas permitidas

```text
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

## AdministrativeDeadlineService

Responsável por:

```text
Calcular prazo de resposta ao aperfeiçoamento
Verificar pedidos vencidos
Marcar correction_overdue quando aplicável
Criar alertas internos ou tarefas
Respeitar configuração por programa/concurso
```

Não criar cron/job obrigatório se a arquitetura não estiver preparada.

Pode criar command Artisan opcional:

```bash
php artisan administrative:mark-overdue-corrections
```

se fizer sentido.

---

## AdministrativeTaskService

Responsável por:

```text
Criar tarefas administrativas
Atribuir tarefas
Marcar tarefa como concluída
Marcar tarefa como vencida
Listar tarefas abertas do técnico
```

---

## AdministrativeTimelineService

Responsável por:

```text
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

## AdministrativeWorkflowConfigResolver

Responsável por:

```text
Resolver configuração por concurso
Resolver configuração por programa
Aplicar fallback documentado
Evitar hardcoding de prazos
```

---

# 17. Controllers obrigatórios

## Backoffice

Criar em namespace:

```text
App\Http\Controllers\Backoffice
```

Controllers:

```text
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

## Área do candidato

Criar em namespace:

```text
App\Http\Controllers\Candidate
```

Controllers:

```text
Candidate\ApplicationProcessController
Candidate\CorrectionRequestController
Candidate\CorrectionResponseController
```

---

# 18. Form Requests obrigatórios

Criar:

```text
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

```text
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

```text
correction_request_item_id required|exists:correction_request_items,id
response_text nullable|string|max:5000
document_submission_id nullable|exists:document_submissions,id
```

Regra adicional:

```text
response_text ou document_submission_id deve estar preenchido.
```

## StoreAdministrativeDecisionRequest

```text
administrative_process_id required|exists:administrative_processes,id
decision_type required|string|max:100
decision_result required|string|max:100
summary required|string|max:3000
legal_basis nullable|string|max:3000
grounds required|string|max:5000
candidate_visible boolean
```

## StoreAdministrativeWorkflowConfigRequest

```text
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

```text
Deve existir program_id ou contest_id.
```

---

# 19. Policies obrigatórias

Criar:

```text
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

```text
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

```text
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

```text
Pode consultar processos admitidos ou em análise, conforme permissão.
Pode consultar histórico.
Não pode alterar dados sem permissão explícita.
```

## Regras para admin

```text
Pode gerir workflow.
Pode configurar prazos.
Pode atribuir processos.
Pode aprovar decisões se permitido.
```

## Regras para auditor

```text
Pode consultar histórico e timeline.
Não pode alterar processos.
Não pode emitir pedidos.
Não pode aprovar decisões.
```

---

# 20. Rotas obrigatórias

## Backoffice

Criar, preferencialmente:

```text
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

```text
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

# 21. Views / páginas obrigatórias

Se o projeto usa Blade, criar:

## Backoffice

```text
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

```text
resources/views/candidate/processes/index.blade.php
resources/views/candidate/processes/show.blade.php
resources/views/candidate/processes/timeline.blade.php

resources/views/candidate/correction-requests/index.blade.php
resources/views/candidate/correction-requests/show.blade.php
resources/views/candidate/correction-requests/respond.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 22. UX obrigatória no backoffice

## Lista de processos administrativos

Mostrar:

```text
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

```text
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

```text
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

```text
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

Antes de emitir, mostrar confirmação:

```text
Ao emitir este pedido, o candidato passará a poder responder através da sua área pessoal. Confirme que os itens, instruções e prazo estão corretos.
```

---

# 23. UX obrigatória para candidato

## Lista de pedidos de aperfeiçoamento

Mostrar:

```text
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

```text
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

```text
Os serviços municipais solicitaram informação adicional ou correção de elementos da sua candidatura. Responda dentro do prazo indicado para que a análise possa prosseguir.
```

## Prazo vencido

```text
O prazo de resposta a este pedido encontra-se vencido. Poderá contactar os serviços municipais para esclarecimentos.
```

## Após submissão da resposta

```text
A sua resposta foi submetida com sucesso e ficará disponível para análise pelos serviços municipais.
```

---

# 24. Integração com documentos

Se Sprint 6 existir:

```text
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

# 25. Integração com elegibilidade

Se Sprint 7 existir:

```text
Backoffice deve conseguir ver último EligibilityCheck da candidatura.
Após resposta ao aperfeiçoamento, permitir reexecutar ou assinalar necessidade de reavaliação.
Não alterar resultado de elegibilidade diretamente fora do motor.
```

Se Sprint 7 não existir:

```text
Permitir análise manual de requisitos.
Documentar pendência de integração com motor de elegibilidade.
```

---

# 26. Integração com classificação

A Sprint 9 deve preparar a Sprint 10.

Apenas candidaturas com processo em estado:

```text
admitted_for_scoring
```

devem estar disponíveis para classificação.

Adicionar método/escopo:

```text
AdministrativeProcess::admittedForScoring()
Application::admittedForScoring()
```

ou equivalente.

Não implementar ranking nesta sprint.

Não implementar pontuação nesta sprint.

---

# 27. Auditoria

Se existir auditoria, auditar:

```text
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

# 28. RGPD e segurança

Regras obrigatórias:

```text
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

# 29. Seeders e factories

Criar factories:

```text
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

```text
AdministrativeWorkflowConfigSeeder
AdministrativeDemoProcessSeeder
```

Dados demo permitidos:

```text
Processo Administrativo Demo
Pedido de Aperfeiçoamento Demo
Tarefa Administrativa Demo
```

Usar apenas dados fictícios.

Não usar dados pessoais reais.

---

# 30. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text
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

```text
administrative_process_can_be_created_for_submitted_application
administrative_process_requires_application
application_can_have_only_one_administrative_process
process_number_is_unique
process_initial_status_is_received_or_submitted
creating_process_creates_status_history
```

## Atribuição e estados

```text
technician_can_be_assigned_to_process
assigning_process_updates_assigned_at
valid_status_transition_is_allowed
invalid_status_transition_is_blocked
status_transition_creates_history
final_status_blocks_invalid_changes
```

## Análises

```text
technician_can_create_preliminary_review
technician_can_create_document_review
technician_can_create_eligibility_review
review_can_have_items
review_completion_sets_result
review_requiring_correction_can_generate_correction_request
```

## Pedido de aperfeiçoamento

```text
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

```text
candidate_can_submit_response_to_own_correction_request
candidate_cannot_submit_response_to_other_candidate_request
response_requires_text_or_document
response_can_link_document_submission
response_marks_item_as_responded
all_required_items_responded_marks_request_as_responded
candidate_cannot_respond_after_deadline_when_policy_blocks_it
```

## Análise da resposta

```text
technician_can_accept_correction_response
technician_can_reject_correction_response
accepting_required_items_allows_correction_request_to_close
rejected_response_can_keep_request_open_or_require_more_information
```

## Decisão administrativa

```text
technician_can_create_admission_decision_proposal
technician_can_create_non_admission_decision_proposal
decision_requires_grounds
decision_can_be_approved_when_policy_allows
approved_admission_sets_process_admitted_for_scoring
approved_non_admission_sets_process_not_admitted
decision_creates_status_history
```

## Integração com classificação

```text
application_admitted_for_scoring_scope_returns_only_admitted_processes
application_not_admitted_is_not_admitted_for_scoring
draft_or_submitted_without_process_is_not_admitted_for_scoring
```

## Segurança

```text
candidate_cannot_mass_assign_process_status
candidate_cannot_mass_assign_decision_result
candidate_cannot_create_administrative_decision
candidate_cannot_create_internal_note
internal_notes_are_not_visible_to_candidate
technical_messages_are_not_visible_to_candidate
```

## Auditoria, se existir

```text
creating_process_generates_audit_log
issuing_correction_request_generates_audit_log
submitting_correction_response_generates_audit_log
approving_administrative_decision_generates_audit_log
```

Se alguma dependência não existir, documentar teste como pendente em vez de criar teste quebrado.

---

# 31. Comandos de validação

No final, executar:

```bash
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash
php artisan migrate
```

Se o projeto usar frontend build:

```bash
npm run build
```

Se o projeto usar Pint:

```bash
./vendor/bin/pint
```

Se existir PHPStan/Psalm:

```bash
./vendor/bin/phpstan analyse
```

Se algum comando falhar, documentar:

```text
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
```

Não afirmar que comandos passaram se não foram executados.

---

# 32. Atualização documental obrigatória

No final, atualizar, se existirem:

```text
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

```text
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

# 33. Critérios de aceitação

A Sprint 9 está concluída quando:

```text
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

# 34. Resposta final obrigatória

No final da execução, responder com:

```text
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

# 35. Execução imediata

Executa agora apenas:

```text
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
```

Usa como referência principal:

```text
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
```

Fim da master prompt da Sprint 9.
