# MASTER PROMPT — EXECUÇÃO DA SPRINT 11: LISTAS PROVISÓRIAS, RECLAMAÇÕES E AUDIÊNCIA DE INTERESSADOS

Atua como arquiteto sénior Laravel, tech lead e product engineer.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 11 — Listas Provisórias, Reclamações e Audiência de Interessados
```

Esta sprint pertence à fase formal de decisão administrativa da plataforma municipal de Arrendamento Acessível.

A Sprint 11 deve transformar o ranking interno produzido pela Sprint 10 em listas administrativas publicáveis, com controlo de anonimização, período de reclamações, análise de reclamações, audiência de interessados quando aplicável e geração de lista definitiva com histórico completo.

---

# 1. Regra principal

Executa apenas a Sprint 11.

Não avances para Sprint 12, Sprint 13, Sprint 14 ou qualquer sprint futura sem validação explícita.

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
docs/backlog/sprint-11-listas-provisorias-reclamacoes-audiencia.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 11.

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
docs/backlog/sprint-11-listas-provisorias-reclamacoes-audiencia.md

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
Sistema de workflow administrativo, se existir
Sistema de classificação/ranking, se existir

Modelo User
Modelo Program
Modelo Contest
Modelo Application
Modelo AdministrativeProcess
Modelo AdministrativeDecision
Modelo ScoringRun
Modelo ApplicationScore
Modelo ApplicationScoreDetail
Modelo RankingSnapshot
Modelo RankingEntry
Modelo DocumentSubmission
Modelo AuditLog, se existir
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
ProvisionalList
ProvisionalListEntry
DefinitiveList
DefinitiveListEntry
PublishedList
Publication
Complaint
ComplaintAttachment
ComplaintReview
ComplaintDecision
AdditionalInformationRequest
AdditionalInformationResponse
Hearing
HearingSubmission
OfficialNotification
ListVersion
ListPublication
ListChangeLog
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não apagar listas já existentes.

Não apagar reclamações já existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY ou credenciais.

---

# 5. Dependências obrigatórias

Esta sprint depende obrigatoriamente de:

```text
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 10 — Matriz de Classificação e Ranking
```

## Dependência da Sprint 8

Se `Application` não existir, interrompe a implementação funcional e informa:

```text
A Sprint 11 depende da Sprint 8 — Candidaturas e Submissão Formal.
```

Não criar `Application` nesta sprint.

## Dependência da Sprint 9

Se `AdministrativeProcess` não existir, interrompe a implementação funcional e informa:

```text
A Sprint 11 depende da Sprint 9 — Workflow Administrativo e Aperfeiçoamento.
```

Não criar workflow administrativo simplificado nesta sprint.

## Dependência da Sprint 10

Se `RankingSnapshot`, `RankingEntry` ou `ApplicationScore` não existirem, interrompe a implementação funcional e informa:

```text
A Sprint 11 depende da Sprint 10 — Matriz de Classificação e Ranking.
```

Não criar matriz de classificação.

Não criar ranking simplificado.

A Sprint 11 deve usar apenas ranking interno já gerado pela Sprint 10.

---

# 6. Validação jurídica obrigatória

Esta sprint tem impacto administrativo e deve ser implementada com cautela jurídica.

Não hardcodar prazos legais sem validação jurídica.

Não publicar listas sem etapa de aprovação.

Não publicar listas sem aplicação de anonimização adequada.

Não criar atos administrativos irreversíveis sem confirmação.

Não enviar notificações reais por email/SMS sem módulo seguro e validação explícita.

Qualquer prazo, fundamento, minuta, mensagem oficial, regra de publicitação ou regra de audiência deve ser:

```text
Configurável;
Ou documentado como pendente de validação jurídica;
Ou implementado de forma conservadora e reversível.
```

Regras obrigatórias:

```text
Prazos de reclamação devem ser configuráveis.
Prazos de audiência devem ser configuráveis.
Motivos de exclusão devem ser fundamentados.
Reclamações devem ficar registadas.
Decisões de reclamação devem ser fundamentadas.
Audiências devem ficar registadas quando aplicáveis.
Pronúncias devem ficar registadas.
Listas provisórias e definitivas devem ser versionadas.
Alterações entre lista provisória e definitiva devem ser registadas.
Publicações devem ser auditáveis.
Notificações devem ficar registadas.
Dados pessoais devem ser minimizados.
Anonimização deve ser configurável.
```

Textos oficiais devem ser tratados como minutas configuráveis e sujeitos a validação jurídica.

---

# 7. Objetivo da implementação

Implementar a fase formal de decisão administrativa anterior à atribuição.

A plataforma deve permitir que o Município:

```text
Gere listas provisórias a partir de ranking interno
Separe candidaturas admitidas, ordenadas e excluídas
Registe motivos de exclusão
Aprove internamente listas provisórias
Publique listas provisórias de forma controlada
Anonimize parcialmente dados quando necessário
Abra período de reclamações
Permita reclamação pelo candidato
Permita anexar documentos ou fundamentos à reclamação
Permita análise de reclamações no backoffice
Permita solicitar informação complementar
Permita resposta do candidato a pedidos complementares
Permita decisão fundamentada da reclamação
Permita audiência de interessados quando aplicável
Permita submissão de pronúncia em audiência
Permita análise e decisão da pronúncia
Gere listas definitivas
Compare lista provisória e lista definitiva
Registe histórico de alterações
Registe notificações oficiais internas
Prepare a Sprint 12 para atribuição
```

---

# 8. Âmbito incluído

Implementar:

```text
Módulo de listas provisórias
Módulo de listas definitivas
Entradas de lista
Versões de lista
Publicação controlada
Anonimização parcial
Períodos de reclamação
Submissão de reclamação pelo candidato
Anexos/documentos da reclamação, se sistema documental existir
Análise de reclamações no backoffice
Pedidos de informação complementar
Resposta do candidato a pedidos complementares
Decisão de reclamação
Audiência de interessados, quando aplicável
Submissão de pronúncia
Análise da pronúncia
Notificações oficiais internas
Histórico de alterações entre listas
Preparação para Sprint 12
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
Atribuição final de habitação
Sorteio final
Afetação de fogo
Aceitação ou recusa de habitação
Contrato de arrendamento
Contrato-promessa
Cálculo contratual final da renda
Pagamentos
Cauções
Assinatura digital
Manutenção
Gestão pós-contrato
Integrações com Autoridade Tributária
Integrações com Segurança Social
Integração com Autenticação.GOV
OCR
Envio real de SMS, salvo módulo seguro existente
Envio real de email, salvo módulo seguro existente
```

Podem ser criados pontos de integração para estas funcionalidades futuras, mas não implementar a Sprint 12.

---

# 10. Fluxo funcional obrigatório

O fluxo da Sprint 11 deve ser:

```text
Ranking interno concluído e, preferencialmente, bloqueado
→ Geração de lista provisória
→ Revisão administrativa da lista provisória
→ Aprovação interna da lista provisória
→ Publicação controlada
→ Notificação dos candidatos
→ Abertura do período de reclamações
→ Submissão de reclamações
→ Análise das reclamações
→ Pedidos complementares, se necessário
→ Decisão das reclamações
→ Audiência de interessados, se aplicável
→ Submissão de pronúncias
→ Análise das pronúncias
→ Geração de lista definitiva
→ Aprovação interna da lista definitiva
→ Publicação controlada da lista definitiva
→ Notificação dos candidatos
→ Preparação para atribuição
```

A Sprint 11 não atribui habitação.

A Sprint 11 não celebra contrato.

A Sprint 11 não calcula pagamentos.

A Sprint 11 consolida o resultado administrativo final do concurso.

---

# 11. Estados obrigatórios

## ProvisionalListStatus

```text
draft
under_review
approved
published
complaint_period_open
complaint_period_closed
superseded
cancelled
archived
```

## DefinitiveListStatus

```text
draft
under_review
approved
published
locked
cancelled
archived
```

## ListEntryStatus

```text
admitted
excluded
ranked
pending_review
changed_after_complaint
removed
cancelled
```

## ComplaintStatus

```text
draft
submitted
received
under_review
requires_additional_information
awaiting_candidate_response
additional_information_submitted
accepted
partially_accepted
rejected
withdrawn
cancelled
closed
```

## ComplaintDecisionStatus

```text
draft
proposed
approved
notified
cancelled
```

## HearingStatus

```text
not_required
draft
issued
open
submitted
under_review
completed
cancelled
closed
```

## OfficialNotificationStatus

```text
draft
queued
sent
delivered
read
failed
cancelled
```

Se o projeto não tiver envio real de notificações, criar apenas registos internos e não marcar `sent` sem envio efetivo.

---

# 12. Modelo de dados a implementar

## 12.1 ProvisionalList

Criar entidade:

```text
ProvisionalList
```

Tabela:

```text
provisional_lists
```

Campos mínimos:

```text
id
program_id
contest_id
ranking_snapshot_id
scoring_run_id

list_number
title
description
status
version_number

generated_by
generated_at
reviewed_by
reviewed_at
approved_by
approved_at

published_by
published_at
publication_starts_at
publication_ends_at

complaint_period_starts_at
complaint_period_ends_at

anonymization_mode
public_visibility
internal_notes
legal_basis

created_at
updated_at
deleted_at
```

Regras:

```text
ranking_snapshot_id obrigatório.
contest_id ou program_id obrigatório.
list_number obrigatório e único.
Não publicar sem aprovação.
Não alterar diretamente lista publicada.
Criar nova versão ou marcar superseded quando necessário.
Usar soft deletes.
```

---

## 12.2 ProvisionalListEntry

Criar entidade:

```text
ProvisionalListEntry
```

Tabela:

```text
provisional_list_entries
```

Campos mínimos:

```text
id
provisional_list_id
application_id
application_score_id
ranking_entry_id
user_id

entry_type
status
rank_position
total_score

public_identifier
candidate_name_masked
application_number_masked

exclusion_reason
exclusion_legal_basis
decision_summary

metadata
created_at
updated_at
deleted_at
```

Valores de `entry_type`:

```text
admitted
excluded
ranked
```

Regras:

```text
Entradas de admitidos/rankeados devem poder conter rank_position.
Entradas de excluídos devem conter motivo e fundamento.
public_identifier não deve expor NIF, email, telefone ou dados sensíveis.
metadata deve evitar dados pessoais excessivos.
```

---

## 12.3 DefinitiveList

Criar entidade:

```text
DefinitiveList
```

Tabela:

```text
definitive_lists
```

Campos mínimos:

```text
id
program_id
contest_id
provisional_list_id
ranking_snapshot_id
scoring_run_id

list_number
title
description
status
version_number

generated_by
generated_at
reviewed_by
reviewed_at
approved_by
approved_at

published_by
published_at
publication_starts_at
publication_ends_at

anonymization_mode
public_visibility
internal_notes
legal_basis

created_at
updated_at
deleted_at
```

Regras:

```text
Deve estar ligada a uma lista provisória.
Não gerar lista definitiva enquanto houver reclamações abertas, salvo decisão administrativa expressa e documentada.
Não publicar sem aprovação.
Lista definitiva publicada deve ficar bloqueada para edição direta.
```

---

## 12.4 DefinitiveListEntry

Criar entidade:

```text
DefinitiveListEntry
```

Tabela:

```text
definitive_list_entries
```

Campos mínimos:

```text
id
definitive_list_id
provisional_list_entry_id
application_id
application_score_id
ranking_entry_id
user_id

entry_type
status
rank_position
previous_rank_position
total_score
previous_total_score

public_identifier
candidate_name_masked
application_number_masked

exclusion_reason
exclusion_legal_basis
decision_summary
change_reason
changed_after_complaint

metadata
created_at
updated_at
deleted_at
```

Regras:

```text
Deve preservar referência à entrada provisória quando existir.
Deve identificar alterações face à lista provisória.
Não guardar dados pessoais excessivos em metadata.
```

---

## 12.5 ListPublication

Criar entidade:

```text
ListPublication
```

Tabela:

```text
list_publications
```

Campos mínimos:

```text
id
publishable_type
publishable_id

publication_type
status
channel
title
summary
public_url
internal_url

published_by
published_at
unpublished_by
unpublished_at

visibility_starts_at
visibility_ends_at
anonymization_mode

created_at
updated_at
deleted_at
```

Valores de `publication_type`:

```text
provisional_list
definitive_list
hearing_notice
complaint_decision_notice
other
```

Valores de `channel`:

```text
public_portal
candidate_area
backoffice
municipal_website
notice_board
email
sms
postal
other
```

Regras:

```text
Esta sprint pode criar registo de publicação sem envio real.
public_url só deve existir se houver rota pública controlada.
Publicações públicas devem respeitar anonimização.
```

---

## 12.6 Complaint

Criar entidade:

```text
Complaint
```

Tabela:

```text
complaints
```

Campos mínimos:

```text
id
provisional_list_id
provisional_list_entry_id
application_id
user_id

complaint_number
status
subject
grounds
requested_outcome

submitted_at
received_at
review_started_at
review_completed_at

assigned_to
assigned_at

requires_additional_information
additional_information_requested_at
additional_information_deadline_at

withdrawn_at
cancelled_at
closed_at

candidate_visible
internal_notes

created_at
updated_at
deleted_at
```

Regras:

```text
Um candidato só pode reclamar sobre a sua candidatura/lista.
complaint_number obrigatório e único.
Reclamações fora do prazo devem ser bloqueadas ou registadas como extemporâneas conforme configuração.
Não apagar reclamações submetidas.
Usar soft deletes.
```

---

## 12.7 ComplaintAttachment

Criar entidade:

```text
ComplaintAttachment
```

Tabela:

```text
complaint_attachments
```

Campos mínimos:

```text
id
complaint_id
document_submission_id
uploaded_by
description
created_at
updated_at
deleted_at
```

Regras:

```text
Se Sprint 6 existir, usar DocumentSubmission.
Não guardar ficheiros diretamente nesta tabela.
Se Sprint 6 não existir, documentar pendência e não criar storage paralelo.
```

---

## 12.8 ComplaintReview

Criar entidade:

```text
ComplaintReview
```

Tabela:

```text
complaint_reviews
```

Campos mínimos:

```text
id
complaint_id
reviewed_by
status
result
summary
technical_notes
started_at
completed_at
created_at
updated_at
deleted_at
```

Valores de `result`:

```text
accepted
partially_accepted
rejected
requires_additional_information
not_admissible
withdrawn
```

---

## 12.9 ComplaintDecision

Criar entidade:

```text
ComplaintDecision
```

Tabela:

```text
complaint_decisions
```

Campos mínimos:

```text
id
complaint_id
application_id
provisional_list_id

decision_number
status
decision_result
summary
grounds
legal_basis
effects_on_ranking
effects_on_exclusion
requires_list_update

proposed_by
proposed_at
approved_by
approved_at
notified_at

candidate_visible
created_at
updated_at
deleted_at
```

Valores de `decision_result`:

```text
accepted
partially_accepted
rejected
not_admissible
withdrawn
cancelled
```

Regras:

```text
Decisão deve ter fundamentação.
Decisão aprovada deve poder impactar lista definitiva.
Não alterar diretamente lista provisória publicada.
Efeitos devem ser aplicados na geração da lista definitiva.
```

---

## 12.10 AdditionalInformationRequest

Criar entidade:

```text
AdditionalInformationRequest
```

Tabela:

```text
additional_information_requests
```

Campos mínimos:

```text
id
complaint_id
application_id
user_id

request_number
status
subject
message
instructions
deadline_at

issued_by
issued_at
responded_at
closed_at
cancelled_at

internal_notes

created_at
updated_at
deleted_at
```

Estados recomendados:

```text
draft
issued
open
responded
overdue
under_review
closed
cancelled
```

---

## 12.11 AdditionalInformationResponse

Criar entidade:

```text
AdditionalInformationResponse
```

Tabela:

```text
additional_information_responses
```

Campos mínimos:

```text
id
additional_information_request_id
complaint_id
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

Estados recomendados:

```text
draft
submitted
under_review
accepted
rejected
cancelled
```

---

## 12.12 Hearing

Criar entidade:

```text
Hearing
```

Tabela:

```text
hearings
```

Campos mínimos:

```text
id
provisional_list_id
definitive_list_id
application_id
user_id

hearing_number
status
hearing_type
subject
message
legal_basis
grounds
deadline_at

issued_by
issued_at
submitted_at
reviewed_by
reviewed_at
closed_at

candidate_visible
internal_notes

created_at
updated_at
deleted_at
```

Valores de `hearing_type`:

```text
intention_to_exclude
intention_to_change_ranking
intention_to_reject_complaint
other
```

Regras:

```text
Audiência deve ser criada quando houver intenção de decisão desfavorável, se aplicável.
Prazo deve ser configurável.
Candidato só vê audiências candidate_visible.
```

---

## 12.13 HearingSubmission

Criar entidade:

```text
HearingSubmission
```

Tabela:

```text
hearing_submissions
```

Campos mínimos:

```text
id
hearing_id
application_id
user_id

submission_text
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

Estados recomendados:

```text
draft
submitted
under_review
accepted
rejected
cancelled
```

---

## 12.14 OfficialNotification

Criar entidade:

```text
OfficialNotification
```

Tabela:

```text
official_notifications
```

Campos mínimos:

```text
id
user_id
application_id
notifiable_type
notifiable_id

notification_type
status
channel
subject
body
sent_at
delivered_at
read_at
failed_at
failure_reason

created_by
created_at
updated_at
deleted_at
```

Tipos recomendados:

```text
provisional_list_published
complaint_received
additional_information_requested
complaint_decided
hearing_issued
hearing_submission_received
definitive_list_published
other
```

Regras:

```text
Se não existir sistema de envio real, criar apenas registo interno.
Não marcar sent sem envio efetivo.
Não enviar SMS/email real sem integração segura.
```

---

## 12.15 ListChangeLog

Criar entidade:

```text
ListChangeLog
```

Tabela:

```text
list_change_logs
```

Campos mínimos:

```text
id
provisional_list_id
definitive_list_id
application_id
user_id

change_type
from_value
to_value
reason
source_type
source_id
changed_by
created_at
```

Valores de `change_type`:

```text
rank_changed
score_changed
status_changed
exclusion_removed
exclusion_added
entry_added
entry_removed
complaint_effect
hearing_effect
manual_correction
other
```

Regras:

```text
Toda alteração relevante entre lista provisória e definitiva deve ser registada.
Não guardar dados pessoais excessivos em from_value/to_value.
```

---

# 13. Enums a criar

Criar, se a versão do PHP permitir:

```text
App\Enums\ProvisionalListStatus
App\Enums\DefinitiveListStatus
App\Enums\ListEntryStatus
App\Enums\ListEntryType
App\Enums\ListPublicationStatus
App\Enums\ListPublicationChannel
App\Enums\ListPublicationType
App\Enums\AnonymizationMode
App\Enums\ComplaintStatus
App\Enums\ComplaintReviewResult
App\Enums\ComplaintDecisionStatus
App\Enums\ComplaintDecisionResult
App\Enums\AdditionalInformationRequestStatus
App\Enums\AdditionalInformationResponseStatus
App\Enums\HearingStatus
App\Enums\HearingType
App\Enums\HearingSubmissionStatus
App\Enums\OfficialNotificationStatus
App\Enums\OfficialNotificationType
App\Enums\OfficialNotificationChannel
App\Enums\ListChangeType
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 14. Anonimização e publicação

## Modos de anonimização

Criar modos configuráveis:

```text
none
partial_name
application_number_only
public_identifier_only
masked_application_number
fully_anonymized
```

## Regras obrigatórias

```text
Não publicar NIF.
Não publicar email.
Não publicar telefone.
Não publicar morada completa.
Não publicar data de nascimento completa.
Não publicar documentos.
Não publicar composição detalhada do agregado em lista pública.
Não publicar rendimentos detalhados em lista pública.
Não publicar notas internas.
Não publicar mensagens técnicas.
Motivos de exclusão devem ser publicáveis apenas de forma compatível com RGPD.
```

## Identificador público recomendado

Usar identificador público sem dados pessoais:

```text
Número de candidatura mascarado
Código público gerado
Referência interna sem dados pessoais
Hash curto não derivado diretamente de NIF/email
```

Exemplos permitidos:

```text
MVHAB-2026-000123
CAND-000123
```

Exemplos proibidos:

```text
NIF
Email
Telefone
Nome completo sem validação
Morada
```

---

# 15. Relações obrigatórias

## Program

```text
hasMany ProvisionalList
hasMany DefinitiveList
hasMany ListPublication
```

## Contest

```text
hasMany ProvisionalList
hasMany DefinitiveList
hasMany ListPublication
```

## Application

Adicionar:

```text
hasMany ProvisionalListEntry
hasMany DefinitiveListEntry
hasMany Complaint
hasMany ComplaintDecision
hasMany Hearing
hasMany HearingSubmission
hasMany OfficialNotification
```

## User

Adicionar conforme necessário:

```text
hasMany Complaints
hasMany Hearings
hasMany OfficialNotifications
```

## ProvisionalList

```text
belongsTo Program nullable
belongsTo Contest nullable
belongsTo RankingSnapshot
belongsTo ScoringRun
belongsTo User as generatedBy
belongsTo User as reviewedBy nullable
belongsTo User as approvedBy nullable
belongsTo User as publishedBy nullable
hasMany ProvisionalListEntry
hasMany Complaint
hasMany Hearing
morphMany ListPublication as publishable
hasOne DefinitiveList
```

## ProvisionalListEntry

```text
belongsTo ProvisionalList
belongsTo Application
belongsTo ApplicationScore nullable
belongsTo RankingEntry nullable
belongsTo User as candidate
hasMany Complaint
hasOne DefinitiveListEntry
```

## DefinitiveList

```text
belongsTo ProvisionalList
belongsTo Program nullable
belongsTo Contest nullable
belongsTo RankingSnapshot nullable
belongsTo ScoringRun nullable
belongsTo User as generatedBy
belongsTo User as reviewedBy nullable
belongsTo User as approvedBy nullable
belongsTo User as publishedBy nullable
hasMany DefinitiveListEntry
morphMany ListPublication as publishable
hasMany ListChangeLog
```

## DefinitiveListEntry

```text
belongsTo DefinitiveList
belongsTo ProvisionalListEntry nullable
belongsTo Application
belongsTo ApplicationScore nullable
belongsTo RankingEntry nullable
belongsTo User as candidate
```

## Complaint

```text
belongsTo ProvisionalList
belongsTo ProvisionalListEntry nullable
belongsTo Application
belongsTo User as candidate
belongsTo User as assignedTo nullable
hasMany ComplaintAttachment
hasMany ComplaintReview
hasOne ComplaintDecision
hasMany AdditionalInformationRequest
```

## ComplaintDecision

```text
belongsTo Complaint
belongsTo Application
belongsTo ProvisionalList
belongsTo User as proposedBy
belongsTo User as approvedBy nullable
```

## AdditionalInformationRequest

```text
belongsTo Complaint
belongsTo Application
belongsTo User as candidate
belongsTo User as issuedBy
hasMany AdditionalInformationResponse
```

## Hearing

```text
belongsTo ProvisionalList nullable
belongsTo DefinitiveList nullable
belongsTo Application
belongsTo User as candidate
belongsTo User as issuedBy
belongsTo User as reviewedBy nullable
hasMany HearingSubmission
```

## OfficialNotification

```text
belongsTo User
belongsTo Application nullable
morphTo notifiable
belongsTo User as createdBy nullable
```

## ListChangeLog

```text
belongsTo ProvisionalList nullable
belongsTo DefinitiveList nullable
belongsTo Application
belongsTo User as candidate
belongsTo User as changedBy nullable
morphTo source nullable
```

---

# 16. Services obrigatórios

Criar:

```text
App\Services\Lists\ProvisionalListService
App\Services\Lists\DefinitiveListService
App\Services\Lists\ListEntryBuilderService
App\Services\Lists\ListPublicationService
App\Services\Lists\ListAnonymizationService
App\Services\Lists\ListVersionService
App\Services\Lists\ListChangeLogService

App\Services\Complaints\ComplaintService
App\Services\Complaints\ComplaintReviewService
App\Services\Complaints\ComplaintDecisionService
App\Services\Complaints\AdditionalInformationService

App\Services\Hearings\HearingService
App\Services\Hearings\HearingSubmissionService

App\Services\Notifications\OfficialNotificationService
App\Services\AdministrativeDecision\FinalDecisionReadinessService
```

---

## ProvisionalListService

Responsável por:

```text
Gerar lista provisória a partir de RankingSnapshot
Separar admitidos/rankeados
Separar excluídos, quando existirem fundamentos
Criar ProvisionalList
Criar ProvisionalListEntry
Gerar número de lista
Definir estado inicial
Enviar para revisão
Aprovar lista provisória
Abrir período de reclamação
Fechar período de reclamação
Cancelar lista quando permitido
Arquivar lista quando permitido
```

Regras:

```text
Não gerar lista a partir de ranking não concluído.
Preferir ranking bloqueado.
Não publicar sem aprovação.
Não alterar diretamente lista publicada.
```

---

## DefinitiveListService

Responsável por:

```text
Verificar se período de reclamações terminou
Verificar se todas as reclamações foram decididas
Verificar se audiências aplicáveis foram concluídas
Gerar lista definitiva
Aplicar efeitos das decisões de reclamação
Aplicar efeitos da audiência
Criar DefinitiveListEntry
Registar alterações face à lista provisória
Aprovar lista definitiva
Publicar lista definitiva
Bloquear lista definitiva publicada
Preparar candidaturas para atribuição
```

Não fazer atribuição nesta sprint.

---

## ListEntryBuilderService

Responsável por:

```text
Converter RankingEntry em entrada de lista
Converter ApplicationScore em entrada de lista
Criar entrada de admitido
Criar entrada de excluído
Aplicar public_identifier
Aplicar masking
Guardar pontuação total apenas se permitido
Guardar rank_position
Guardar motivo de exclusão
```

---

## ListPublicationService

Responsável por:

```text
Preparar publicação
Validar aprovação prévia
Aplicar anonimização
Criar ListPublication
Publicar em área pública se rota existir
Publicar em área do candidato se aplicável
Controlar janela temporal de visibilidade
Retirar publicação quando permitido
Registar publicação
Criar notificações internas associadas
```

Se não existir mecanismo público adequado, criar apenas publicação controlada em backoffice e área autenticada, documentando pendência.

---

## ListAnonymizationService

Responsável por:

```text
Gerar identificador público
Mascarar número de candidatura
Mascarar nome
Ocultar dados sensíveis
Aplicar modo de anonimização
Gerar payload público seguro
Gerar payload interno completo
```

---

## ComplaintService

Responsável por:

```text
Abrir reclamação
Criar número de reclamação
Validar prazo de reclamação
Guardar fundamentos
Permitir rascunho
Submeter reclamação
Receber reclamação
Atribuir técnico
Retirar reclamação, se permitido
Cancelar reclamação, se permitido
Fechar reclamação
```

Regras:

```text
Candidato só reclama da sua própria entrada/lista.
Não permitir reclamações fora do período, salvo configuração expressa.
Não apagar reclamação submetida.
```

---

## ComplaintReviewService

Responsável por:

```text
Iniciar análise
Registar análise
Avaliar fundamentos
Consultar documentos
Consultar candidatura
Consultar pontuação
Consultar histórico administrativo
Solicitar informação complementar
Concluir análise
Preparar proposta de decisão
```

---

## ComplaintDecisionService

Responsável por:

```text
Criar decisão de reclamação
Definir resultado
Fundamentar decisão
Indicar efeitos no ranking
Indicar efeitos na exclusão
Indicar se exige atualização da lista definitiva
Submeter decisão para aprovação
Aprovar decisão
Registar notificação
Criar ListChangeLog futuro
```

---

## AdditionalInformationService

Responsável por:

```text
Criar pedido de informação complementar
Definir prazo
Emitir pedido
Receber resposta do candidato
Associar documentos
Analisar resposta
Fechar pedido
Marcar como vencido
```

---

## HearingService

Responsável por:

```text
Criar audiência de interessados quando aplicável
Gerar número de audiência
Definir prazo
Emitir audiência
Permitir pronúncia do candidato
Analisar pronúncia
Concluir audiência
Registar efeitos
```

Audiência deve ser configurável e juridicamente validável.

---

## HearingSubmissionService

Responsável por:

```text
Criar rascunho de pronúncia
Submeter pronúncia
Associar documento, se aplicável
Bloquear submissões fora do prazo, salvo configuração expressa
Permitir análise técnica da pronúncia
Registar resultado da análise
```

---

## OfficialNotificationService

Responsável por:

```text
Criar notificação oficial interna
Gerar assunto e corpo a partir de templates
Associar notificação a lista, reclamação ou audiência
Registar canal previsto
Registar envio apenas se o sistema de envio existir
Registar leitura se existir mecanismo de leitura
```

Não enviar email/SMS real sem integração segura.

---

## FinalDecisionReadinessService

Responsável por:

```text
Verificar se lista definitiva está publicada
Verificar se lista definitiva está bloqueada
Verificar se não há reclamações pendentes
Verificar se não há audiências pendentes
Verificar se candidaturas admitidas estão preparadas para Sprint 12
Disponibilizar scope para atribuição
```

---

# 17. Controllers obrigatórios

Criar controllers conforme a arquitetura existente.

## Backoffice

Namespace recomendado:

```text
App\Http\Controllers\Backoffice
```

Controllers:

```text
Backoffice\ProvisionalListController
Backoffice\DefinitiveListController
Backoffice\ListPublicationController
Backoffice\ComplaintController
Backoffice\ComplaintReviewController
Backoffice\ComplaintDecisionController
Backoffice\AdditionalInformationRequestController
Backoffice\HearingController
Backoffice\HearingSubmissionReviewController
Backoffice\OfficialNotificationController
```

## Área do candidato

Namespace recomendado:

```text
App\Http\Controllers\Candidate
```

Controllers:

```text
Candidate\PublishedListController
Candidate\ComplaintController
Candidate\AdditionalInformationResponseController
Candidate\HearingController
Candidate\HearingSubmissionController
Candidate\OfficialNotificationController
```

## Área pública

Criar apenas se a arquitetura permitir publicação pública segura.

Namespace recomendado:

```text
App\Http\Controllers\Public
```

Controller:

```text
Public\PublishedResultListController
```

A área pública deve usar payload anonimizado.

Nunca expor dados completos publicamente.

---

# 18. Form Requests obrigatórios

Criar:

```text
GenerateProvisionalListRequest
ApproveProvisionalListRequest
PublishProvisionalListRequest
OpenComplaintPeriodRequest
CloseComplaintPeriodRequest

GenerateDefinitiveListRequest
ApproveDefinitiveListRequest
PublishDefinitiveListRequest
LockDefinitiveListRequest

StoreComplaintRequest
UpdateComplaintRequest
SubmitComplaintRequest
AssignComplaintRequest

StoreComplaintDecisionRequest
ApproveComplaintDecisionRequest

StoreAdditionalInformationRequestRequest
SubmitAdditionalInformationResponseRequest
ReviewAdditionalInformationResponseRequest

StoreHearingRequest
IssueHearingRequest
SubmitHearingSubmissionRequest
ReviewHearingSubmissionRequest

StoreOfficialNotificationRequest
PublishListRequest
```

## GenerateProvisionalListRequest

```text
ranking_snapshot_id required|exists:ranking_snapshots,id
title required|string|max:255
description nullable|string|max:3000
publication_starts_at nullable|date
publication_ends_at nullable|date|after_or_equal:publication_starts_at
complaint_period_starts_at nullable|date
complaint_period_ends_at nullable|date|after_or_equal:complaint_period_starts_at
anonymization_mode required|string|max:100
public_visibility required|boolean
legal_basis nullable|string|max:3000
internal_notes nullable|string|max:3000
```

## StoreComplaintRequest

```text
provisional_list_id required|exists:provisional_lists,id
provisional_list_entry_id nullable|exists:provisional_list_entries,id
application_id required|exists:applications,id
subject required|string|max:255
grounds required|string|min:10|max:10000
requested_outcome nullable|string|max:5000
attachments nullable|array
attachments.*.document_submission_id nullable|exists:document_submissions,id
attachments.*.description nullable|string|max:1000
```

Regras adicionais:

```text
A candidatura deve pertencer ao candidato autenticado.
A lista deve estar em período de reclamação aberto.
```

## StoreComplaintDecisionRequest

```text
complaint_id required|exists:complaints,id
decision_result required|string|max:100
summary required|string|max:3000
grounds required|string|min:10|max:10000
legal_basis nullable|string|max:3000
effects_on_ranking nullable|string|max:5000
effects_on_exclusion nullable|string|max:5000
requires_list_update boolean
candidate_visible boolean
```

## StoreAdditionalInformationRequestRequest

```text
complaint_id required|exists:complaints,id
subject required|string|max:255
message required|string|max:5000
instructions nullable|string|max:5000
deadline_at required|date|after:now
internal_notes nullable|string|max:3000
```

## SubmitAdditionalInformationResponseRequest

```text
additional_information_request_id required|exists:additional_information_requests,id
response_text nullable|string|max:10000
document_submission_id nullable|exists:document_submissions,id
```

Regra adicional:

```text
response_text ou document_submission_id deve estar preenchido.
```

## StoreHearingRequest

```text
application_id required|exists:applications,id
provisional_list_id nullable|exists:provisional_lists,id
definitive_list_id nullable|exists:definitive_lists,id
hearing_type required|string|max:100
subject required|string|max:255
message required|string|max:5000
legal_basis nullable|string|max:3000
grounds required|string|max:10000
deadline_at required|date|after:now
candidate_visible boolean
internal_notes nullable|string|max:3000
```

## SubmitHearingSubmissionRequest

```text
hearing_id required|exists:hearings,id
submission_text required|string|min:10|max:10000
document_submission_id nullable|exists:document_submissions,id
```

---

# 19. Policies obrigatórias

Criar:

```text
ProvisionalListPolicy
ProvisionalListEntryPolicy
DefinitiveListPolicy
DefinitiveListEntryPolicy
ListPublicationPolicy
ComplaintPolicy
ComplaintReviewPolicy
ComplaintDecisionPolicy
AdditionalInformationRequestPolicy
AdditionalInformationResponsePolicy
HearingPolicy
HearingSubmissionPolicy
OfficialNotificationPolicy
ListChangeLogPolicy
```

## Regras para candidato

```text
Candidato só vê listas publicadas ou listas disponíveis na sua área.
Candidato só vê a sua própria entrada detalhada.
Candidato só reclama sobre a sua própria candidatura.
Candidato só vê as suas próprias reclamações.
Candidato só responde aos seus próprios pedidos complementares.
Candidato só vê as suas próprias audiências.
Candidato só submete pronúncia sobre audiência própria.
Candidato não vê notas internas.
Candidato não vê mensagens técnicas.
Candidato não acede ao backoffice.
Candidato não vê ranking interno completo, salvo publicação pública anonimizada.
```

## Regras para técnico municipal

```text
Pode consultar listas internas conforme permissão.
Pode gerar lista provisória se autorizado.
Pode analisar reclamações.
Pode solicitar informação complementar.
Pode propor decisões.
Pode preparar audiência.
Não publica listas sem permissão específica.
Não aprova decisão se política exigir aprovação superior.
```

## Regras para júri

```text
Pode consultar listas e reclamações conforme autorização.
Pode emitir parecer ou decisão se configurado.
Não altera dados sem permissão explícita.
```

## Regras para admin

```text
Pode gerir listas.
Pode aprovar publicações.
Pode publicar listas.
Pode gerir prazos.
Pode aprovar decisões.
Pode consultar histórico.
```

## Regras para auditor

```text
Pode consultar histórico, publicações, reclamações e decisões.
Não pode alterar listas.
Não pode decidir reclamações.
Não pode publicar listas.
```

## Regras para público

```text
Só acede a listas publicadas publicamente.
Só vê dados anonimizados.
Não vê detalhe individual sensível.
Não vê documentos.
Não vê fundamentos internos.
```

---

# 20. Rotas obrigatórias

## Backoffice

Criar, preferencialmente:

```text
GET /backoffice/lists/provisional
GET /backoffice/lists/provisional/create
POST /backoffice/lists/provisional
GET /backoffice/lists/provisional/{provisionalList}
POST /backoffice/lists/provisional/{provisionalList}/review
POST /backoffice/lists/provisional/{provisionalList}/approve
POST /backoffice/lists/provisional/{provisionalList}/publish
POST /backoffice/lists/provisional/{provisionalList}/open-complaint-period
POST /backoffice/lists/provisional/{provisionalList}/close-complaint-period
POST /backoffice/lists/provisional/{provisionalList}/cancel
POST /backoffice/lists/provisional/{provisionalList}/archive

GET /backoffice/lists/definitive
GET /backoffice/lists/definitive/create
POST /backoffice/lists/definitive
GET /backoffice/lists/definitive/{definitiveList}
POST /backoffice/lists/definitive/{definitiveList}/review
POST /backoffice/lists/definitive/{definitiveList}/approve
POST /backoffice/lists/definitive/{definitiveList}/publish
POST /backoffice/lists/definitive/{definitiveList}/lock
POST /backoffice/lists/definitive/{definitiveList}/archive

GET /backoffice/complaints
GET /backoffice/complaints/{complaint}
POST /backoffice/complaints/{complaint}/assign
POST /backoffice/complaints/{complaint}/mark-received
POST /backoffice/complaints/{complaint}/start-review
POST /backoffice/complaints/{complaint}/close

GET /backoffice/complaints/{complaint}/decisions/create
POST /backoffice/complaints/{complaint}/decisions
GET /backoffice/complaint-decisions/{complaintDecision}
POST /backoffice/complaint-decisions/{complaintDecision}/approve
POST /backoffice/complaint-decisions/{complaintDecision}/cancel

GET /backoffice/complaints/{complaint}/additional-information/create
POST /backoffice/complaints/{complaint}/additional-information
GET /backoffice/additional-information-requests/{additionalInformationRequest}
POST /backoffice/additional-information-requests/{additionalInformationRequest}/close
POST /backoffice/additional-information-requests/{additionalInformationRequest}/mark-overdue

GET /backoffice/hearings
GET /backoffice/hearings/create
POST /backoffice/hearings
GET /backoffice/hearings/{hearing}
POST /backoffice/hearings/{hearing}/issue
POST /backoffice/hearings/{hearing}/close
POST /backoffice/hearings/{hearing}/cancel

GET /backoffice/hearing-submissions/{hearingSubmission}
POST /backoffice/hearing-submissions/{hearingSubmission}/accept
POST /backoffice/hearing-submissions/{hearingSubmission}/reject

GET /backoffice/official-notifications
GET /backoffice/official-notifications/{officialNotification}
POST /backoffice/official-notifications
POST /backoffice/official-notifications/{officialNotification}/mark-sent
POST /backoffice/official-notifications/{officialNotification}/mark-failed
```

## Área do candidato

Criar, preferencialmente:

```text
GET /area-candidato/resultados
GET /area-candidato/resultados/{provisionalList}

GET /area-candidato/reclamacoes
GET /area-candidato/reclamacoes/criar
POST /area-candidato/reclamacoes
GET /area-candidato/reclamacoes/{complaint}
GET /area-candidato/reclamacoes/{complaint}/editar
PUT/PATCH /area-candidato/reclamacoes/{complaint}
POST /area-candidato/reclamacoes/{complaint}/submeter
POST /area-candidato/reclamacoes/{complaint}/desistir

GET /area-candidato/pedidos-informacao-complementar/{additionalInformationRequest}
GET /area-candidato/pedidos-informacao-complementar/{additionalInformationRequest}/responder
POST /area-candidato/pedidos-informacao-complementar/{additionalInformationRequest}/responder

GET /area-candidato/audiencias
GET /area-candidato/audiencias/{hearing}
GET /area-candidato/audiencias/{hearing}/pronunciar
POST /area-candidato/audiencias/{hearing}/pronunciar

GET /area-candidato/notificacoes-oficiais
GET /area-candidato/notificacoes-oficiais/{officialNotification}
POST /area-candidato/notificacoes-oficiais/{officialNotification}/marcar-lida
```

## Área pública

Criar apenas se houver publicação pública segura:

```text
GET /resultados
GET /resultados/{listPublication}
```

A área pública deve devolver apenas payload anonimizado.

---

# 21. Views / páginas obrigatórias

Se o projeto usa Blade, criar:

## Backoffice

```text
resources/views/backoffice/lists/provisional/index.blade.php
resources/views/backoffice/lists/provisional/create.blade.php
resources/views/backoffice/lists/provisional/show.blade.php

resources/views/backoffice/lists/definitive/index.blade.php
resources/views/backoffice/lists/definitive/create.blade.php
resources/views/backoffice/lists/definitive/show.blade.php

resources/views/backoffice/complaints/index.blade.php
resources/views/backoffice/complaints/show.blade.php

resources/views/backoffice/complaint-decisions/create.blade.php
resources/views/backoffice/complaint-decisions/show.blade.php

resources/views/backoffice/additional-information-requests/create.blade.php
resources/views/backoffice/additional-information-requests/show.blade.php

resources/views/backoffice/hearings/index.blade.php
resources/views/backoffice/hearings/create.blade.php
resources/views/backoffice/hearings/show.blade.php

resources/views/backoffice/official-notifications/index.blade.php
resources/views/backoffice/official-notifications/show.blade.php
```

## Área do candidato

```text
resources/views/candidate/results/index.blade.php
resources/views/candidate/results/show.blade.php

resources/views/candidate/complaints/index.blade.php
resources/views/candidate/complaints/create.blade.php
resources/views/candidate/complaints/edit.blade.php
resources/views/candidate/complaints/show.blade.php

resources/views/candidate/additional-information/show.blade.php
resources/views/candidate/additional-information/respond.blade.php

resources/views/candidate/hearings/index.blade.php
resources/views/candidate/hearings/show.blade.php
resources/views/candidate/hearings/submit.blade.php

resources/views/candidate/official-notifications/index.blade.php
resources/views/candidate/official-notifications/show.blade.php
```

## Área pública, se aplicável

```text
resources/views/public/results/index.blade.php
resources/views/public/results/show.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 22. UX obrigatória no backoffice

## Lista provisória

A página de detalhe da lista provisória deve mostrar:

```text
Número da lista
Programa
Concurso
Ranking de origem
Estado
Versão
Data de geração
Data de aprovação
Data de publicação
Período de reclamação
Modo de anonimização
Número de admitidos
Número de excluídos
Número de entradas ordenadas
Ações disponíveis
```

## Entradas da lista

Mostrar:

```text
Posição
Número público
Número da candidatura
Candidato, apenas backoffice
Estado
Pontuação total, se permitido
Motivo de exclusão, quando aplicável
Fundamento legal, quando aplicável
Alterações
Ações
```

## Publicação

Antes de publicar, mostrar confirmação:

```text
Ao publicar esta lista, os candidatos poderão consultar os resultados e, se aplicável, apresentar reclamação dentro do prazo configurado. Confirme que a lista foi revista, aprovada e que a anonimização está correta.
```

## Reclamações

A lista de reclamações deve mostrar:

```text
Número da reclamação
Candidato
Candidatura
Lista provisória
Estado
Data de submissão
Técnico responsável
Prazo ativo, se existir
Resultado da decisão
Ações
```

## Decisão da reclamação

A página de decisão deve exigir:

```text
Resultado
Resumo
Fundamentos
Base legal, se aplicável
Efeitos na classificação
Efeitos na exclusão
Indicação se altera a lista definitiva
Visibilidade para candidato
```

## Lista definitiva

A página da lista definitiva deve mostrar:

```text
Número da lista definitiva
Lista provisória de origem
Estado
Versão
Total de entradas
Alterações face à lista provisória
Reclamações consideradas
Audiências consideradas
Data de aprovação
Data de publicação
Ações disponíveis
```

---

# 23. UX obrigatória para candidato

## Resultados

O candidato deve ver:

```text
Concurso
Estado da lista
Número público da candidatura
Estado da sua candidatura
Posição, se legalmente permitido
Motivo de exclusão, quando aplicável
Prazo de reclamação
Botão para apresentar reclamação, se aplicável
Histórico de notificações
```

## Reclamação

Copy obrigatório:

```text
Pode apresentar reclamação relativamente ao resultado provisório da sua candidatura dentro do prazo indicado. A reclamação deve indicar os fundamentos e, se aplicável, juntar documentos ou informação complementar.
```

Campos mínimos:

```text
Assunto
Fundamentos
Resultado pretendido
Documentos associados, se aplicável
Confirmação de veracidade
Botão guardar rascunho
Botão submeter reclamação
```

## Após submissão da reclamação

Mensagem obrigatória:

```text
A sua reclamação foi submetida com sucesso e ficará disponível para análise pelos serviços municipais.
```

## Pedido de informação complementar

Mensagem obrigatória:

```text
Os serviços municipais solicitaram informação complementar para análise da sua reclamação. Responda dentro do prazo indicado.
```

## Audiência de interessados

Mensagem obrigatória:

```text
Foi-lhe concedida audiência de interessados para se pronunciar sobre os elementos indicados. A sua pronúncia deve ser submetida dentro do prazo definido.
```

## Lista definitiva

Mensagem obrigatória:

```text
A lista definitiva encontra-se disponível. Esta lista resulta da análise das candidaturas, reclamações e demais atos procedimentais aplicáveis.
```

---

# 24. Integração com ranking

A Sprint 11 deve consumir os dados da Sprint 10.

Regras:

```text
Gerar lista provisória apenas a partir de RankingSnapshot completed/internal/locked.
Preservar rank_position.
Preservar total_score internamente.
Permitir ocultar pontuação publicamente.
Permitir mostrar apenas identificador público.
Não recalcular pontuação nesta sprint.
Não alterar ApplicationScore diretamente.
Não alterar RankingSnapshot diretamente.
```

Se for necessário ajustar resultado por decisão de reclamação:

```text
Não alterar ranking original.
Registar efeitos em ComplaintDecision.
Aplicar efeitos na DefinitiveList.
Criar ListChangeLog.
```

---

# 25. Integração com workflow administrativo

Apenas candidaturas com processo em estado adequado devem integrar listas.

Estados recomendados:

```text
admitted_for_scoring
not_admitted
```

Candidaturas `admitted_for_scoring` podem entrar como admitidas/rankeadas.

Candidaturas `not_admitted` podem entrar como excluídas, se o procedimento exigir publicitação de excluídos.

Não incluir:

```text
draft
withdrawn
cancelled
archived
```

Se os estados forem diferentes no projeto, mapear e documentar.

---

# 26. Integração com documentos

Se Sprint 6 existir:

```text
Reclamações podem associar DocumentSubmission.
Pedidos de informação complementar podem associar DocumentSubmission.
Pronúncia em audiência pode associar DocumentSubmission.
Não guardar ficheiros diretamente nas tabelas de reclamações/audiências.
Usar storage privado.
Usar policies documentais existentes.
```

Se Sprint 6 não existir, documentar pendência.

---

# 27. Integração com notificações

Se existir sistema de notificações:

```text
Criar notificação ao publicar lista provisória
Criar notificação ao receber reclamação
Criar notificação ao solicitar informação complementar
Criar notificação ao decidir reclamação
Criar notificação ao emitir audiência
Criar notificação ao publicar lista definitiva
```

Se não existir sistema de envio:

```text
Criar OfficialNotification como registo interno.
Mostrar na área do candidato.
Não enviar email/SMS real.
Documentar pendência.
```

---

# 28. Integração com Sprint 12

A Sprint 11 deve expor candidaturas aptas para atribuição.

Criar ou preparar scopes:

```text
DefinitiveList::published()
DefinitiveList::locked()
DefinitiveListEntry::ranked()
DefinitiveListEntry::eligibleForAllocation()
Application::readyForAllocation()
```

Uma candidatura só deve estar pronta para Sprint 12 quando:

```text
Pertence a lista definitiva publicada ou bloqueada
Está admitida/rankeada
Não está excluída
Não tem reclamação pendente
Não tem audiência pendente
Não está withdrawn/cancelled
```

Não atribuir habitação nesta sprint.

---

# 29. Auditoria

Se existir auditoria, auditar:

```text
Geração de lista provisória
Aprovação de lista provisória
Publicação de lista provisória
Abertura de período de reclamação
Fecho de período de reclamação
Submissão de reclamação
Receção de reclamação
Atribuição de reclamação
Pedido de informação complementar
Resposta a pedido complementar
Decisão de reclamação
Aprovação de decisão de reclamação
Criação de audiência
Emissão de audiência
Submissão de pronúncia
Decisão/fecho de audiência
Geração de lista definitiva
Aprovação de lista definitiva
Publicação de lista definitiva
Bloqueio de lista definitiva
Alterações entre lista provisória e definitiva
Criação de notificação oficial
```

Não criar auditoria paralela se já existir sistema de auditoria.

Se não existir auditoria, documentar pendência.

Não guardar dados sensíveis excessivos nos logs.

---

# 30. RGPD e segurança

Regras obrigatórias:

```text
Listas e reclamações contêm dados sensíveis.
Listas públicas devem ser anonimizadas.
Candidato só vê os seus próprios detalhes.
Público só vê dados anonimizados.
Backoffice exige permissões.
Júri só acede conforme autorização.
Auditor não altera dados.
Não expor documentos publicamente.
Não expor NIF, email, telefone ou morada.
Não expor composição detalhada do agregado em listas públicas.
Não expor rendimentos detalhados em listas públicas.
Não guardar paths internos.
Não permitir mass assignment de status.
Não permitir edição direta de listas publicadas.
Não permitir apagar reclamações submetidas.
Não permitir apagar decisões aprovadas.
Não enviar dados sensíveis por email/SMS nesta sprint sem módulo seguro.
```

---

# 31. Seeders e factories

Criar factories:

```text
ProvisionalListFactory
ProvisionalListEntryFactory
DefinitiveListFactory
DefinitiveListEntryFactory
ListPublicationFactory
ComplaintFactory
ComplaintAttachmentFactory
ComplaintReviewFactory
ComplaintDecisionFactory
AdditionalInformationRequestFactory
AdditionalInformationResponseFactory
HearingFactory
HearingSubmissionFactory
OfficialNotificationFactory
ListChangeLogFactory
```

Criar seeders opcionais:

```text
ListWorkflowConfigSeeder
DemoProvisionalListSeeder
DemoComplaintSeeder
DemoHearingSeeder
```

Usar apenas dados fictícios.

Não usar dados pessoais reais.

---

# 32. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text
guest_cannot_access_candidate_results
candidate_can_access_own_result
candidate_cannot_access_other_candidate_result
public_can_only_access_anonymized_published_lists
candidate_cannot_access_backoffice_lists
technician_can_access_backoffice_lists_if_authorized
auditor_can_view_lists_without_editing
candidate_cannot_view_internal_notes
candidate_cannot_view_technical_messages
```

## Lista provisória

```text
admin_can_generate_provisional_list_from_ranking_snapshot
provisional_list_requires_ranking_snapshot
provisional_list_cannot_be_generated_from_unfinished_ranking
provisional_list_creates_entries_from_ranking_entries
provisional_list_contains_ranked_admitted_candidates
provisional_list_contains_excluded_candidates_when_configured
provisional_list_cannot_be_published_without_approval
approved_provisional_list_can_be_published
published_provisional_list_creates_publication_record
published_provisional_list_applies_anonymization
```

## Anonimização

```text
public_list_does_not_expose_nif
public_list_does_not_expose_email
public_list_does_not_expose_phone
public_list_does_not_expose_full_address
public_list_uses_public_identifier
partial_name_anonymization_masks_candidate_name
application_number_only_mode_hides_candidate_name
```

## Reclamações

```text
candidate_can_create_complaint_for_own_application_during_complaint_period
candidate_cannot_create_complaint_outside_complaint_period_when_blocked
candidate_cannot_complain_about_other_candidate_application
complaint_requires_grounds
complaint_can_be_saved_as_draft
complaint_can_be_submitted
submitted_complaint_gets_unique_number
submitted_complaint_cannot_be_deleted_by_candidate
technician_can_mark_complaint_received
technician_can_start_complaint_review
```

## Informação complementar

```text
technician_can_request_additional_information_for_complaint
additional_information_request_has_deadline
candidate_can_respond_to_own_additional_information_request
candidate_cannot_respond_to_other_candidate_request
response_requires_text_or_document
overdue_additional_information_request_can_be_marked_overdue
```

## Decisão de reclamação

```text
technician_can_create_complaint_decision
complaint_decision_requires_grounds
complaint_decision_can_be_approved_if_authorized
accepted_complaint_can_mark_list_update_required
rejected_complaint_does_not_change_ranking_by_default
complaint_decision_creates_notification_record
candidate_can_view_visible_complaint_decision
candidate_cannot_view_internal_decision_notes
```

## Audiência de interessados

```text
technician_can_create_hearing
hearing_requires_deadline
issued_hearing_is_visible_to_candidate_when_candidate_visible
candidate_can_submit_hearing_submission
candidate_cannot_submit_hearing_for_other_candidate
hearing_submission_requires_text
technician_can_review_hearing_submission
closed_hearing_blocks_new_submissions
```

## Lista definitiva

```text
definitive_list_cannot_be_generated_while_complaints_are_pending
definitive_list_can_be_generated_after_all_complaints_are_decided
definitive_list_applies_accepted_complaint_effects
definitive_list_entries_reference_provisional_entries
definitive_list_records_rank_changes
definitive_list_records_status_changes
definitive_list_cannot_be_published_without_approval
published_definitive_list_can_be_locked
locked_definitive_list_cannot_be_edited
```

## Integração com Sprint 12

```text
ready_for_allocation_scope_returns_only_ranked_entries_from_locked_definitive_list
excluded_entries_are_not_ready_for_allocation
entries_with_pending_complaints_are_not_ready_for_allocation
entries_with_pending_hearings_are_not_ready_for_allocation
```

## Segurança

```text
candidate_cannot_mass_assign_complaint_status
candidate_cannot_mass_assign_list_entry_status
candidate_cannot_publish_list
candidate_cannot_approve_complaint_decision
public_routes_do_not_expose_internal_notes
public_routes_do_not_expose_documents
```

## Auditoria, se existir

```text
generating_provisional_list_generates_audit_log
publishing_provisional_list_generates_audit_log
submitting_complaint_generates_audit_log
approving_complaint_decision_generates_audit_log
issuing_hearing_generates_audit_log
publishing_definitive_list_generates_audit_log
```

Se alguma dependência não existir, documentar teste como pendente em vez de criar teste quebrado.

---

# 33. Comandos de validação

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

# 34. Atualização documental obrigatória

No final, atualizar, se existirem:

```text
docs/backlog/sprint-11-listas-provisorias-reclamacoes-audiencia.md
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
Pendências para Sprint 12
Validações jurídicas pendentes
Regras de anonimização aplicadas
Limitações de notificação oficial
```

---

# 35. Critérios de aceitação

A Sprint 11 está concluída quando:

```text
O Município consegue gerar lista provisória a partir de ranking interno
A lista provisória separa admitidos/rankeados e excluídos
Motivos de exclusão são registados
A lista provisória exige aprovação antes de publicação
A lista provisória pode ser publicada de forma controlada
A publicação aplica anonimização configurável
O candidato consegue consultar o seu resultado, quando publicado
O período de reclamação pode ser aberto e fechado
O candidato consegue apresentar reclamação dentro do prazo
O candidato não consegue reclamar sobre candidatura alheia
O técnico consegue analisar reclamação
O técnico consegue pedir informação complementar
O candidato consegue responder a pedido complementar
O técnico consegue decidir reclamação
A decisão de reclamação é fundamentada
A audiência de interessados pode ser criada quando aplicável
O candidato consegue submeter pronúncia em audiência
O técnico consegue analisar pronúncia
A lista definitiva pode ser gerada após tratamento das reclamações/audiências
A lista definitiva preserva histórico da lista provisória
Alterações entre lista provisória e definitiva são registadas
A lista definitiva exige aprovação antes de publicação
A lista definitiva pode ser publicada de forma controlada
A lista definitiva pode ser bloqueada
Candidaturas da lista definitiva ficam preparadas para Sprint 12
O público não vê dados pessoais sensíveis
O candidato não vê dados de outros candidatos fora do que for publicado e anonimizado
Backoffice exige permissões
Auditoria é usada se existir
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foi implementada atribuição
Não foi implementado contrato
Não foi implementado pagamento
```

---

# 36. Resposta final obrigatória

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
20. Regras de anonimização implementadas
21. Limitações de notificações oficiais
22. Confirmação de que não foram implementadas funcionalidades fora de âmbito
23. Recomendação objetiva para avançar ou não para Sprint 12
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 37. Execução imediata

Executa agora apenas:

```text
Sprint 11 — Listas Provisórias, Reclamações e Audiência de Interessados
```

Usa como referência principal:

```text
docs/backlog/sprint-11-listas-provisorias-reclamacoes-audiencia.md
```

Fim da master prompt da Sprint 11.
