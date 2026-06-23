# MASTER PROMPT — EXECUÇÃO DA SPRINT 12: ATRIBUIÇÃO DE HABITAÇÕES

Atua como arquiteto sénior Laravel, tech lead e product engineer.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 12 — Atribuição de Habitações
```

Esta sprint pertence à fase de afetação final de habitações da plataforma municipal de Arrendamento Acessível.

A Sprint 12 deve ser executada apenas depois da Sprint 11 estar funcionalmente validada, porque a atribuição deve incidir sobre candidaturas constantes de listas definitivas publicadas, bloqueadas ou formalmente aprovadas.

---

# 1. Regra principal

Executa apenas a Sprint 12.

Não avances para Sprint 13, Sprint 14, Sprint 15 ou qualquer sprint futura sem validação explícita.

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
docs/backlog/sprint-12-atribuicao-habitacoes.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 12.

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
docs/backlog/sprint-12-atribuicao-habitacoes.md

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
Sistema de listas definitivas, se existir

Modelo User
Modelo Program
Modelo Contest
Modelo Application
Modelo AdministrativeProcess
Modelo ScoringRun
Modelo ApplicationScore
Modelo RankingSnapshot
Modelo RankingEntry
Modelo ProvisionalList
Modelo ProvisionalListEntry
Modelo DefinitiveList
Modelo DefinitiveListEntry
Modelo HousingUnit
Modelo DocumentSubmission
Modelo OfficialNotification, se existir
Modelo AuditLog, se existir
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
ContestHousingUnit
TypologyAdequacyRule
HousingPreference
AllocationRuleSet
AllocationRun
Allocation
AllocationOffer
LotteryRun
LotteryParticipant
LotteryDrawResult
ReserveList
ReserveListEntry
AllocationReport
AllocationNotification
HousingAllocation
CandidateOffer
AllocationDecision
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não apagar listas, candidaturas, rankings ou atribuições existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY ou credenciais.

---

# 5. Dependências obrigatórias

Esta sprint depende obrigatoriamente de:

```text
Sprint 8 — Candidaturas e Submissão Formal
Sprint 10 — Matriz de Classificação e Ranking
Sprint 11 — Listas Provisórias, Reclamações e Audiência
```

Depende preferencialmente de:

```text
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 6 — Gestão Documental Avançada
Sistema de notificações internas
Sistema de auditoria
```

## Dependência da Sprint 8

Se `Application` não existir, interrompe a implementação funcional e informa:

```text
A Sprint 12 depende da Sprint 8 — Candidaturas e Submissão Formal.
```

Não criar `Application` nesta sprint.

## Dependência da Sprint 10

Se `ApplicationScore`, `RankingEntry` ou equivalente de ranking não existir, interrompe a implementação funcional e informa:

```text
A Sprint 12 depende da Sprint 10 — Matriz de Classificação e Ranking.
```

Não criar matriz de classificação nesta sprint.

Não recalcular pontuação nesta sprint.

## Dependência da Sprint 11

Se `DefinitiveList` ou `DefinitiveListEntry` não existir, interrompe a implementação funcional e informa:

```text
A Sprint 12 depende da Sprint 11 — Listas Provisórias, Reclamações e Audiência.
```

Não criar listas definitivas simplificadas dentro desta sprint.

A atribuição deve usar apenas listas definitivas:

```text
published
locked
approved
```

conforme estados realmente existentes no projeto.

Preferir listas definitivas bloqueadas.

---

# 6. Validação jurídica obrigatória

Esta sprint tem impacto administrativo direto.

Não implementar atribuição irreversível sem estado de confirmação.

Não hardcodar regras de tipologia, sorteio, prazos de aceitação ou consequências da recusa sem validação jurídica.

Não gerar contrato automaticamente.

Não marcar habitação como definitivamente contratada nesta sprint.

Regras obrigatórias:

```text
Regras de tipologia devem ser configuráveis.
Regras de sorteio devem ser auditáveis.
Critérios de desempate por sorteio devem ser registados.
Prazos de aceitação devem ser configuráveis.
Recusas devem ficar registadas.
Desistências devem ficar registadas.
Substituições por suplentes devem ficar registadas.
Todas as decisões de atribuição devem identificar quem executou e quando.
Todos os sorteios devem preservar participantes, seed, algoritmo, ordem e resultado.
Relatórios ou atas devem ser gerados e preservados.
Notificações devem ficar registadas.
```

Textos oficiais devem ser tratados como minutas configuráveis e sujeitos a validação jurídica.

---

# 7. Objetivo da implementação

Implementar o módulo de atribuição de habitações.

A plataforma deve permitir que o Município:

```text
Associe habitações disponíveis a um concurso
Defina regras de tipologia adequada
Registe preferências de habitação dos candidatos, quando aplicável
Execute atribuição por classificação/ranking
Execute atribuição por sorteio quando aplicável
Registe sorteios de forma auditável
Gere propostas/ofertas de atribuição
Notifique candidatos da atribuição
Permita aceitação ou recusa pelo candidato dentro de prazo
Registe desistências
Avance automaticamente para suplentes quando houver recusa/desistência/expiração
Mantenha lista de reserva/suplentes
Bloqueie temporariamente habitações atribuídas
Liberte habitações em caso de recusa/desistência
Gere ata/relatório de atribuição
Prepare candidaturas atribuídas para contrato na Sprint 13
```

---

# 8. Âmbito incluído

Implementar:

```text
Relação entre concurso e habitações disponíveis
Estados de disponibilidade da habitação no contexto do concurso
Regras de tipologia adequada
Preferências de habitação pelo candidato
Motor de atribuição por ranking/classificação
Motor de sorteio auditável, quando aplicável
Execuções de atribuição
Propostas/ofertas de atribuição
Aceitação pelo candidato
Recusa pelo candidato
Desistência
Avanço automático para suplente
Lista de reserva/suplentes
Bloqueio temporário de habitação atribuída
Libertação de habitação em caso de recusa/desistência
Notificações internas de atribuição
Relatório/ata de atribuição
Preparação para contrato
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
Contrato de arrendamento
Assinatura de contrato
Contrato-promessa
Cálculo contratual final da renda
Caução
Pagamentos
Recibos
Gestão de incumprimento
Gestão pós-contrato
Manutenção
Vistorias pós-atribuição
Renovação contratual
Revisão de renda
Integrações com Autoridade Tributária
Integrações com Segurança Social
Integração com Autenticação.GOV
Assinatura digital
Envio real de SMS, salvo módulo seguro existente
Envio real de email, salvo módulo seguro existente
```

Podem ser criados pontos de integração para a Sprint 13, mas não implementar contrato.

---

# 10. Fluxo funcional obrigatório

O fluxo da Sprint 12 deve ser:

```text
Lista definitiva publicada/bloqueada
→ Habitações disponíveis associadas ao concurso
→ Regras de tipologia configuradas
→ Preferências recolhidas, se aplicável
→ Execução de atribuição
→ Atribuição por ranking ou sorteio
→ Geração de proposta/oferta de habitação
→ Notificação ao candidato
→ Aceitação ou recusa dentro do prazo
→ Se aceitar: preparar para contrato
→ Se recusar/desistir/expirar: chamar suplente
→ Atualizar lista de reserva
→ Gerar relatório/ata de atribuição
```

A Sprint 12 não celebra contrato.

A Sprint 12 apenas cria a decisão de afetação e prepara a fase contratual.

---

# 11. Modos de atribuição

Implementar suporte para os seguintes modos:

```text
ranking
lottery
ranking_then_lottery
preference_based
manual_with_justification
```

## ranking

Atribuição respeita a ordem da lista definitiva/ranking.

## lottery

Atribuição ocorre por sorteio auditável entre candidatos elegíveis.

## ranking_then_lottery

Ranking define prioridade principal e sorteio resolve empate ou grupo equivalente.

## preference_based

Ranking é combinado com preferências de habitação do candidato.

## manual_with_justification

Atribuição manual por técnico autorizado, sempre com fundamentação obrigatória.

---

# 12. Estados obrigatórios

## ContestHousingUnitStatus

```text
available
reserved
allocated
accepted
refused
withdrawn
unavailable
removed
```

## AllocationRuleSetStatus

```text
draft
active
archived
```

## AllocationRunStatus

```text
draft
ready
running
completed
failed
cancelled
locked
```

## AllocationMethod

```text
ranking
lottery
ranking_then_lottery
preference_based
manual_with_justification
```

## AllocationStatus

```text
proposed
offered
accepted
refused
expired
withdrawn
cancelled
superseded
ready_for_contract
```

## AllocationOfferStatus

```text
draft
issued
pending_response
accepted
refused
expired
withdrawn
cancelled
superseded
```

## LotteryRunStatus

```text
draft
ready
running
completed
failed
cancelled
locked
```

## LotteryResultType

```text
selected
reserve
not_selected
excluded
```

## ReserveListStatus

```text
draft
active
locked
archived
cancelled
```

## ReserveListEntryStatus

```text
waiting
called
offered
accepted
refused
expired
withdrawn
removed
```

## AllocationReportStatus

```text
draft
generated
approved
published
cancelled
archived
```

Se o projeto usar enums PHP, criar enums.

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 13. Modelo de dados a implementar

## 13.1 ContestHousingUnit

Criar entidade:

```text
ContestHousingUnit
```

Tabela:

```text
contest_housing_units
```

Objetivo:

```text
Associar habitações disponíveis a um concurso/programa e controlar o estado da habitação no âmbito desse procedimento.
```

Campos mínimos:

```text
id
program_id
contest_id
housing_unit_id

status
availability_starts_at
availability_ends_at

typology
bedrooms
max_occupants
min_occupants
accessible
reserved_for_special_condition

monthly_rent
estimated_expenses
notes
internal_notes

created_by
updated_by

created_at
updated_at
deleted_at
```

Regras:

```text
housing_unit_id obrigatório.
contest_id ou program_id obrigatório.
Uma habitação não deve estar disponível simultaneamente em dois concursos incompatíveis.
status deve controlar disponibilidade no concurso.
Não alterar HousingUnit global de forma irreversível sem confirmação.
Usar soft deletes.
```

Se já existir `HousingUnit`, reutilizar.

Não criar modelo paralelo de habitação sem necessidade.

---

## 13.2 TypologyAdequacyRule

Criar entidade:

```text
TypologyAdequacyRule
```

Tabela:

```text
typology_adequacy_rules
```

Objetivo:

```text
Definir regras configuráveis de adequação entre composição do agregado e tipologia da habitação.
```

Campos mínimos:

```text
id
program_id
contest_id

name
description
is_active

min_household_members
max_household_members
min_adults
max_adults
min_children
max_children
min_bedrooms
max_bedrooms
typology

requires_accessibility
special_condition_key
priority_order

created_at
updated_at
deleted_at
```

Regras:

```text
Regras por concurso prevalecem sobre regras por programa.
Critérios devem ser configuráveis.
Se não houver regra aplicável, devolver requires_manual_review em vez de atribuição automática.
Usar soft deletes.
```

---

## 13.3 HousingPreference

Criar entidade:

```text
HousingPreference
```

Tabela:

```text
housing_preferences
```

Objetivo:

```text
Guardar preferências de habitação indicadas pelo candidato, quando o concurso permitir.
```

Campos mínimos:

```text
id
application_id
user_id
contest_id
contest_housing_unit_id
housing_unit_id

preference_order
notes
submitted_at

created_at
updated_at
deleted_at
```

Regras:

```text
preference_order deve ser único por candidatura.
Candidato só pode escolher habitações disponíveis no concurso.
Preferências só são editáveis até ao prazo definido.
Se o concurso não permitir preferências, ocultar funcionalidade.
```

Se a Sprint 8 já tiver `ApplicationPreference`, avaliar reaproveitamento ou migração, evitando duplicação.

---

## 13.4 AllocationRuleSet

Criar entidade:

```text
AllocationRuleSet
```

Tabela:

```text
allocation_rule_sets
```

Objetivo:

```text
Configurar regras de atribuição por programa/concurso.
```

Campos mínimos:

```text
id
program_id
contest_id

name
description
status
allocation_method

allow_preferences
allow_lottery
allow_manual_override
requires_acceptance
acceptance_deadline_days
auto_call_next_on_refusal
auto_call_next_on_expiry
max_refusals_allowed

created_by
updated_by

created_at
updated_at
deleted_at
```

Regras:

```text
Apenas rule sets active devem ser usados.
Rule set de concurso prevalece sobre rule set de programa.
Prazos devem ser configuráveis.
Não apagar rule set usado numa execução.
Usar soft deletes.
```

---

## 13.5 AllocationRun

Criar entidade:

```text
AllocationRun
```

Tabela:

```text
allocation_runs
```

Objetivo:

```text
Registar cada execução de atribuição.
```

Campos mínimos:

```text
id
allocation_rule_set_id
program_id
contest_id
definitive_list_id

status
allocation_method

started_by
started_at
completed_at
failed_at
failure_reason
locked_at
locked_by

total_housing_units
total_candidates
total_allocations
total_reserve_entries
total_refusals

notes
internal_notes

created_at
updated_at
deleted_at
```

Regras:

```text
Cada execução deve ficar registada.
Uma execução falhada não deve apagar histórico anterior.
Execução concluída pode ser bloqueada.
Não apagar execução com atribuições.
Usar soft deletes.
```

---

## 13.6 Allocation

Criar entidade:

```text
Allocation
```

Tabela:

```text
allocations
```

Objetivo:

```text
Representar a afetação de uma habitação a uma candidatura/candidato.
```

Campos mínimos:

```text
id
allocation_run_id
allocation_rule_set_id
program_id
contest_id
definitive_list_id
definitive_list_entry_id

application_id
user_id
contest_housing_unit_id
housing_unit_id

allocation_method
status

rank_position
reserve_position
preference_order

allocated_by
allocated_at

offered_at
acceptance_deadline_at
accepted_at
refused_at
expired_at
withdrawn_at
cancelled_at
ready_for_contract_at

refusal_reason
withdrawal_reason
cancellation_reason
manual_justification

superseded_by_allocation_id

created_at
updated_at
deleted_at
```

Regras:

```text
Cada habitação só pode ter uma allocation ativa.
Cada candidatura só pode ter uma allocation ativa por concurso.
Mudanças de estado devem passar por service.
Recusa/desistência deve preservar histórico.
Não apagar allocations com decisão.
```

---

## 13.7 AllocationOffer

Criar entidade:

```text
AllocationOffer
```

Tabela:

```text
allocation_offers
```

Objetivo:

```text
Registar a oferta formal de habitação ao candidato.
```

Campos mínimos:

```text
id
allocation_id
application_id
user_id
contest_housing_unit_id
housing_unit_id

offer_number
status
message
instructions

issued_by
issued_at
response_deadline_at

accepted_at
refused_at
expired_at
withdrawn_at
cancelled_at

candidate_response
candidate_notes
refusal_reason

created_at
updated_at
deleted_at
```

Regras:

```text
offer_number obrigatório e único.
Oferta deve ter prazo quando requires_acceptance=true.
Aceitação/recusa deve ser feita pelo candidato autenticado.
Oferta expirada pode chamar suplente se configurado.
```

---

## 13.8 LotteryRun

Criar entidade:

```text
LotteryRun
```

Tabela:

```text
lottery_runs
```

Objetivo:

```text
Registar sorteio auditável, quando aplicável.
```

Campos mínimos:

```text
id
allocation_run_id
program_id
contest_id
definitive_list_id

status
lottery_method
seed
seed_source
algorithm
participants_count
drawn_count

started_by
started_at
completed_at
failed_at
failure_reason
locked_at
locked_by

audit_hash
audit_payload

created_at
updated_at
deleted_at
```

Regras:

```text
Sorteio deve ser reproduzível ou auditável.
Guardar seed ou fonte de seed.
Guardar algoritmo.
Guardar payload auditável.
Guardar hash do payload.
Não permitir alteração após locked.
```

---

## 13.9 LotteryParticipant

Criar entidade:

```text
LotteryParticipant
```

Tabela:

```text
lottery_participants
```

Campos mínimos:

```text
id
lottery_run_id
application_id
user_id
definitive_list_entry_id

participant_number
rank_position
weight
is_eligible
exclusion_reason

created_at
updated_at
```

Regras:

```text
participant_number não deve expor dados pessoais.
Candidatos excluídos não devem entrar no sorteio.
Peso deve ser documentado se usado.
```

---

## 13.10 LotteryDrawResult

Criar entidade:

```text
LotteryDrawResult
```

Tabela:

```text
lottery_draw_results
```

Campos mínimos:

```text
id
lottery_run_id
lottery_participant_id
application_id
user_id

draw_order
result_type
selected
assigned_contest_housing_unit_id
assigned_housing_unit_id

random_value
audit_data

created_at
updated_at
```

Regras:

```text
draw_order obrigatório.
Resultados devem ser imutáveis após bloqueio.
audit_data deve evitar dados pessoais excessivos.
```

---

## 13.11 ReserveList

Criar entidade:

```text
ReserveList
```

Tabela:

```text
reserve_lists
```

Objetivo:

```text
Guardar lista de suplentes para substituição em caso de recusa, expiração ou desistência.
```

Campos mínimos:

```text
id
allocation_run_id
program_id
contest_id
definitive_list_id

status
generated_by
generated_at
locked_at
locked_by
notes

created_at
updated_at
deleted_at
```

Estados:

```text
draft
active
locked
archived
cancelled
```

---

## 13.12 ReserveListEntry

Criar entidade:

```text
ReserveListEntry
```

Tabela:

```text
reserve_list_entries
```

Campos mínimos:

```text
id
reserve_list_id
allocation_run_id
application_id
user_id
definitive_list_entry_id

reserve_position
status
called_at
offered_at
accepted_at
refused_at
expired_at
removed_at

linked_allocation_id
replacement_for_allocation_id

created_at
updated_at
deleted_at
```

Regras:

```text
Ordem de suplentes deve respeitar ranking ou sorteio.
Ao chamar suplente, registar called_at.
Não apagar entrada usada.
```

---

## 13.13 AllocationReport

Criar entidade:

```text
AllocationReport
```

Tabela:

```text
allocation_reports
```

Objetivo:

```text
Guardar ata/relatório da atribuição.
```

Campos mínimos:

```text
id
allocation_run_id
program_id
contest_id
definitive_list_id

report_number
title
status
summary
method_description
legal_basis
results_summary
exceptions_summary
refusals_summary
reserve_summary

generated_by
generated_at
approved_by
approved_at
published_at

file_path
file_disk

created_at
updated_at
deleted_at
```

Estados:

```text
draft
generated
approved
published
cancelled
archived
```

Regras:

```text
Relatório deve poder existir em HTML mesmo sem PDF.
Se for gerado PDF, guardar em storage privado.
Não publicar dados sensíveis sem anonimização.
```

---

## 13.14 AllocationNotification

Criar entidade apenas se `OfficialNotification` não for suficiente:

```text
AllocationNotification
```

Tabela:

```text
allocation_notifications
```

Campos mínimos:

```text
id
allocation_id
allocation_offer_id
user_id
application_id

notification_type
status
channel
subject
body

created_by
created_at
sent_at
read_at
failed_at
failure_reason

updated_at
deleted_at
```

Se `OfficialNotification` existir, preferir reutilizar `OfficialNotification` e não criar tabela nova.

---

# 14. Enums a criar

Criar, se a versão do PHP permitir:

```text
App\Enums\ContestHousingUnitStatus
App\Enums\AllocationRuleSetStatus
App\Enums\AllocationMethod
App\Enums\AllocationRunStatus
App\Enums\AllocationStatus
App\Enums\AllocationOfferStatus
App\Enums\LotteryRunStatus
App\Enums\LotteryResultType
App\Enums\ReserveListStatus
App\Enums\ReserveListEntryStatus
App\Enums\AllocationReportStatus
App\Enums\AllocationNotificationType
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 15. Relações obrigatórias

## Program

```text
hasMany ContestHousingUnit
hasMany AllocationRuleSet
hasMany AllocationRun
hasMany Allocation
hasMany ReserveList
hasMany AllocationReport
```

## Contest

```text
hasMany ContestHousingUnit
hasMany AllocationRuleSet
hasMany AllocationRun
hasMany Allocation
hasMany ReserveList
hasMany AllocationReport
```

## HousingUnit

Adicionar:

```text
hasMany ContestHousingUnit
hasMany Allocation
hasMany AllocationOffer
```

## ContestHousingUnit

```text
belongsTo Program nullable
belongsTo Contest nullable
belongsTo HousingUnit
hasMany HousingPreference
hasMany Allocation
hasMany AllocationOffer
```

## Application

Adicionar:

```text
hasMany HousingPreference
hasMany Allocation
hasMany AllocationOffer
hasMany LotteryParticipant
hasMany LotteryDrawResult
hasMany ReserveListEntry
hasOne activeAllocation
hasOne acceptedAllocation
```

## DefinitiveList

Adicionar:

```text
hasMany AllocationRun
hasMany Allocation
hasMany ReserveList
```

## DefinitiveListEntry

Adicionar:

```text
hasMany Allocation
hasMany ReserveListEntry
```

## AllocationRuleSet

```text
belongsTo Program nullable
belongsTo Contest nullable
belongsTo User as createdBy
belongsTo User as updatedBy
hasMany AllocationRun
```

## AllocationRun

```text
belongsTo AllocationRuleSet
belongsTo Program nullable
belongsTo Contest nullable
belongsTo DefinitiveList
belongsTo User as startedBy
belongsTo User as lockedBy nullable
hasMany Allocation
hasOne LotteryRun
hasOne ReserveList
hasMany AllocationReport
```

## Allocation

```text
belongsTo AllocationRun
belongsTo AllocationRuleSet
belongsTo Program nullable
belongsTo Contest nullable
belongsTo DefinitiveList
belongsTo DefinitiveListEntry nullable
belongsTo Application
belongsTo User as candidate
belongsTo ContestHousingUnit
belongsTo HousingUnit
belongsTo User as allocatedBy nullable
belongsTo Allocation as supersededByAllocation nullable
hasMany AllocationOffer
hasOne activeOffer
```

## AllocationOffer

```text
belongsTo Allocation
belongsTo Application
belongsTo User as candidate
belongsTo ContestHousingUnit
belongsTo HousingUnit
belongsTo User as issuedBy
```

## LotteryRun

```text
belongsTo AllocationRun
belongsTo Program nullable
belongsTo Contest nullable
belongsTo DefinitiveList
belongsTo User as startedBy
belongsTo User as lockedBy nullable
hasMany LotteryParticipant
hasMany LotteryDrawResult
```

## LotteryParticipant

```text
belongsTo LotteryRun
belongsTo Application
belongsTo User as candidate
belongsTo DefinitiveListEntry nullable
hasMany LotteryDrawResult
```

## LotteryDrawResult

```text
belongsTo LotteryRun
belongsTo LotteryParticipant
belongsTo Application
belongsTo User as candidate
belongsTo ContestHousingUnit as assignedContestHousingUnit nullable
belongsTo HousingUnit as assignedHousingUnit nullable
```

## ReserveList

```text
belongsTo AllocationRun
belongsTo Program nullable
belongsTo Contest nullable
belongsTo DefinitiveList
belongsTo User as generatedBy
belongsTo User as lockedBy nullable
hasMany ReserveListEntry
```

## ReserveListEntry

```text
belongsTo ReserveList
belongsTo AllocationRun
belongsTo Application
belongsTo User as candidate
belongsTo DefinitiveListEntry nullable
belongsTo Allocation as linkedAllocation nullable
belongsTo Allocation as replacementForAllocation nullable
```

## AllocationReport

```text
belongsTo AllocationRun
belongsTo Program nullable
belongsTo Contest nullable
belongsTo DefinitiveList
belongsTo User as generatedBy
belongsTo User as approvedBy nullable
```

---

# 16. Services obrigatórios

Criar:

```text
App\Services\Allocation\ContestHousingUnitService
App\Services\Allocation\TypologyAdequacyService
App\Services\Allocation\HousingPreferenceService
App\Services\Allocation\AllocationRuleSetResolver
App\Services\Allocation\AllocationEngine
App\Services\Allocation\RankingAllocationService
App\Services\Allocation\PreferenceAllocationService
App\Services\Allocation\LotteryService
App\Services\Allocation\LotteryAuditService
App\Services\Allocation\AllocationOfferService
App\Services\Allocation\AllocationResponseService
App\Services\Allocation\ReserveListService
App\Services\Allocation\ReplacementService
App\Services\Allocation\AllocationReportService
App\Services\Allocation\AllocationNotificationService
App\Services\Allocation\ContractReadinessService
```

---

## ContestHousingUnitService

Responsável por:

```text
Associar habitações ao concurso
Remover habitações do concurso quando permitido
Marcar habitação como disponível
Marcar habitação como reservada
Marcar habitação como atribuída
Libertar habitação em caso de recusa/desistência
Verificar conflitos com outros concursos
Verificar disponibilidade
```

---

## TypologyAdequacyService

Responsável por:

```text
Resolver regras de tipologia por concurso/programa
Calcular composição do agregado
Avaliar número de membros
Avaliar número de adultos
Avaliar número de menores
Avaliar necessidades de acessibilidade
Avaliar tipologia da habitação
Devolver adequate, inadequate, requires_manual_review ou not_applicable
```

Não hardcodar regras de tipologia em controllers.

---

## HousingPreferenceService

Responsável por:

```text
Listar habitações disponíveis para preferência
Validar preferências do candidato
Guardar preferências ordenadas
Impedir duplicação de ordem
Impedir escolha de habitação fora do concurso
Bloquear edição após prazo ou após execução de atribuição
```

---

## AllocationRuleSetResolver

Responsável por:

```text
Resolver regras de atribuição por concurso
Resolver regras de atribuição por programa
Dar prioridade a regra específica de concurso
Usar apenas rule sets ativos
Impedir execução sem regra aplicável
```

---

## AllocationEngine

Responsável por:

```text
Receber definitive_list_id
Resolver rule set aplicável
Validar lista definitiva pronta
Validar habitações disponíveis
Criar AllocationRun
Executar método de atribuição configurado
Criar Allocations
Criar AllocationOffers
Criar ReserveList
Gerar relatório preliminar
Registar totais
Tratar falhas sem apagar histórico
```

A execução deve usar transações onde fizer sentido.

Não deve gerar contrato.

---

## RankingAllocationService

Responsável por:

```text
Percorrer DefinitiveListEntry por rank_position
Encontrar habitação adequada
Respeitar tipologia
Respeitar disponibilidade
Criar allocation proposta
Gerar suplentes para candidatos sem habitação
```

---

## PreferenceAllocationService

Responsável por:

```text
Considerar preferências ordenadas do candidato
Conciliar ranking com preferências
Atribuir primeira habitação adequada disponível
Passar para preferência seguinte se indisponível
Aplicar fallback para habitação adequada sem preferência, se configurado
```

---

## LotteryService

Responsável por:

```text
Criar LotteryRun
Criar participantes
Validar elegibilidade dos participantes
Executar sorteio
Gerar resultados
Atribuir ordem de seleção
Associar habitações quando aplicável
Criar reserva/suplentes por ordem sorteada
Bloquear resultado do sorteio
```

---

## LotteryAuditService

Responsável por:

```text
Gerar seed
Guardar seed_source
Guardar algoritmo
Guardar payload auditável
Gerar audit_hash
Permitir reprodução ou verificação do sorteio
Impedir alteração após lock
```

Regras obrigatórias:

```text
Sorteio deve ser auditável.
Sorteio deve preservar participantes.
Sorteio deve preservar ordem.
Sorteio deve preservar algoritmo.
Sorteio deve preservar seed ou fonte de seed.
```

---

## AllocationOfferService

Responsável por:

```text
Criar oferta de atribuição
Gerar número de oferta
Definir prazo de resposta
Emitir oferta
Registar notificação interna
Marcar oferta como expirada
Cancelar oferta
Substituir oferta quando necessário
```

---

## AllocationResponseService

Responsável por:

```text
Permitir aceitação pelo candidato
Permitir recusa pelo candidato
Validar prazo de resposta
Registar motivo de recusa
Atualizar Allocation
Atualizar AllocationOffer
Marcar habitação como accepted quando aceite
Preparar candidatura para contrato
Disparar substituição quando recusada/expirada
```

---

## ReserveListService

Responsável por:

```text
Criar lista de reserva
Criar entradas por ranking ou sorteio
Consultar próximo suplente
Marcar suplente como chamado
Marcar suplente como oferecido
Atualizar status de suplente
Bloquear lista quando necessário
```

---

## ReplacementService

Responsável por:

```text
Identificar recusa/desistência/expiração
Libertar habitação
Selecionar próximo suplente adequado
Criar nova allocation para suplente
Criar nova offer
Registar vínculo à allocation substituída
Auditar substituição
```

Se não houver suplente adequado, manter habitação disponível e documentar.

---

## AllocationReportService

Responsável por:

```text
Gerar relatório/ata de atribuição
Listar habitações consideradas
Listar candidatos considerados
Listar atribuições realizadas
Listar recusas/desistências
Listar suplentes
Listar sorteio e hash, se aplicável
Listar exceções e decisões manuais
Gerar versão HTML
Gerar PDF apenas se infraestrutura existir
Guardar relatório em storage privado, se ficheiro existir
```

---

## AllocationNotificationService

Responsável por:

```text
Criar notificação de atribuição
Criar notificação de prazo de resposta
Criar notificação de aceitação
Criar notificação de recusa
Criar notificação de chamada de suplente
Usar OfficialNotification se existir
Não enviar email/SMS real sem integração segura
```

---

## ContractReadinessService

Responsável por:

```text
Identificar allocations aceites
Verificar se candidatura está ready_for_contract
Verificar se habitação está aceite
Verificar se não há recusa/desistência
Disponibilizar scope para Sprint 13
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
Backoffice\ContestHousingUnitController
Backoffice\TypologyAdequacyRuleController
Backoffice\AllocationRuleSetController
Backoffice\AllocationRunController
Backoffice\AllocationController
Backoffice\AllocationOfferController
Backoffice\LotteryRunController
Backoffice\ReserveListController
Backoffice\AllocationReportController
```

## Área do candidato

Namespace recomendado:

```text
App\Http\Controllers\Candidate
```

Controllers:

```text
Candidate\HousingPreferenceController
Candidate\AllocationController
Candidate\AllocationOfferController
Candidate\AllocationResponseController
```

---

# 18. Form Requests obrigatórios

Criar:

```text
StoreContestHousingUnitRequest
UpdateContestHousingUnitRequest

StoreTypologyAdequacyRuleRequest
UpdateTypologyAdequacyRuleRequest

StoreHousingPreferenceRequest
UpdateHousingPreferenceRequest

StoreAllocationRuleSetRequest
UpdateAllocationRuleSetRequest

RunAllocationRequest
CreateManualAllocationRequest
CancelAllocationRequest

IssueAllocationOfferRequest
AcceptAllocationOfferRequest
RefuseAllocationOfferRequest
WithdrawAllocationRequest

RunLotteryRequest
LockLotteryRunRequest

CallNextReserveCandidateRequest
GenerateAllocationReportRequest
ApproveAllocationReportRequest
```

## StoreContestHousingUnitRequest

```text
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
housing_unit_id required|exists:housing_units,id
status required|string|max:100
availability_starts_at nullable|date
availability_ends_at nullable|date|after_or_equal:availability_starts_at
typology nullable|string|max:100
bedrooms nullable|integer|min:0|max:20
max_occupants nullable|integer|min:1|max:50
min_occupants nullable|integer|min:1|max:50
accessible boolean
reserved_for_special_condition nullable|string|max:255
monthly_rent nullable|numeric|min:0
estimated_expenses nullable|numeric|min:0
notes nullable|string|max:3000
internal_notes nullable|string|max:3000
```

Regra adicional:

```text
Deve existir program_id ou contest_id.
```

## StoreTypologyAdequacyRuleRequest

```text
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
name required|string|max:255
description nullable|string|max:3000
is_active boolean
min_household_members nullable|integer|min:1|max:50
max_household_members nullable|integer|min:1|max:50
min_adults nullable|integer|min:0|max:50
max_adults nullable|integer|min:0|max:50
min_children nullable|integer|min:0|max:50
max_children nullable|integer|min:0|max:50
min_bedrooms nullable|integer|min:0|max:20
max_bedrooms nullable|integer|min:0|max:20
typology nullable|string|max:100
requires_accessibility boolean
special_condition_key nullable|string|max:255
priority_order nullable|integer|min:0
```

## UpdateHousingPreferenceRequest

```text
preferences required|array|min:1
preferences.*.contest_housing_unit_id required|exists:contest_housing_units,id
preferences.*.preference_order required|integer|min:1
preferences.*.notes nullable|string|max:1000
```

Regras adicionais:

```text
Cada contest_housing_unit_id deve pertencer ao concurso da candidatura.
preference_order deve ser único.
Candidato só pode alterar preferências próprias.
```

## StoreAllocationRuleSetRequest

```text
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
name required|string|max:255
description nullable|string|max:3000
status required|string|max:100
allocation_method required|string|max:100
allow_preferences boolean
allow_lottery boolean
allow_manual_override boolean
requires_acceptance boolean
acceptance_deadline_days required|integer|min:1|max:120
auto_call_next_on_refusal boolean
auto_call_next_on_expiry boolean
max_refusals_allowed nullable|integer|min:0|max:20
```

Regra adicional:

```text
Deve existir program_id ou contest_id.
```

## RunAllocationRequest

```text
definitive_list_id required|exists:definitive_lists,id
allocation_rule_set_id nullable|exists:allocation_rule_sets,id
allocation_method nullable|string|max:100
notes nullable|string|max:3000
```

## AcceptAllocationOfferRequest

```text
allocation_offer_id required|exists:allocation_offers,id
candidate_response nullable|string|max:3000
confirm_acceptance accepted
```

## RefuseAllocationOfferRequest

```text
allocation_offer_id required|exists:allocation_offers,id
refusal_reason required|string|min:5|max:3000
confirm_refusal accepted
```

## RunLotteryRequest

```text
allocation_run_id required|exists:allocation_runs,id
seed nullable|string|max:255
seed_source nullable|string|max:255
algorithm nullable|string|max:255
notes nullable|string|max:3000
```

---

# 19. Policies obrigatórias

Criar:

```text
ContestHousingUnitPolicy
TypologyAdequacyRulePolicy
HousingPreferencePolicy
AllocationRuleSetPolicy
AllocationRunPolicy
AllocationPolicy
AllocationOfferPolicy
LotteryRunPolicy
LotteryParticipantPolicy
LotteryDrawResultPolicy
ReserveListPolicy
ReserveListEntryPolicy
AllocationReportPolicy
```

## Regras para candidato

```text
Candidato só vê as suas próprias preferências.
Candidato só edita preferências da sua própria candidatura e dentro do prazo.
Candidato só vê as suas próprias ofertas de atribuição.
Candidato só aceita ou recusa oferta própria.
Candidato não vê ofertas de outros candidatos.
Candidato não vê lista interna de suplentes, salvo informação própria.
Candidato não executa atribuição.
Candidato não executa sorteio.
Candidato não altera habitações.
Candidato não acede ao backoffice.
```

## Regras para técnico municipal

```text
Pode consultar habitações do concurso conforme permissão.
Pode preparar regras de atribuição se autorizado.
Pode executar atribuição se autorizado.
Pode consultar resultados de atribuição.
Pode emitir ofertas se autorizado.
Pode chamar suplentes se autorizado.
Pode gerar relatório se autorizado.
Não aprova relatório se política exigir nível superior.
```

## Regras para júri

```text
Pode consultar execução de atribuição conforme autorização.
Pode consultar sorteio auditável se autorizado.
Pode consultar relatório.
Não altera regras sem permissão.
```

## Regras para admin

```text
Pode gerir habitações do concurso.
Pode gerir regras de tipologia.
Pode gerir regras de atribuição.
Pode executar atribuição.
Pode executar sorteio.
Pode bloquear execução.
Pode aprovar relatório.
```

## Regras para auditor

```text
Pode consultar atribuições, sorteios, suplentes e relatórios.
Pode consultar payload auditável do sorteio.
Não pode alterar atribuições.
Não pode executar sorteio.
Não pode aceitar/recusar em nome do candidato.
```

---

# 20. Rotas obrigatórias

## Backoffice

Criar, preferencialmente:

```text
GET /backoffice/allocation/contest-housing-units
GET /backoffice/allocation/contest-housing-units/create
POST /backoffice/allocation/contest-housing-units
GET /backoffice/allocation/contest-housing-units/{contestHousingUnit}
GET /backoffice/allocation/contest-housing-units/{contestHousingUnit}/edit
PUT/PATCH /backoffice/allocation/contest-housing-units/{contestHousingUnit}
POST /backoffice/allocation/contest-housing-units/{contestHousingUnit}/available
POST /backoffice/allocation/contest-housing-units/{contestHousingUnit}/unavailable
DELETE /backoffice/allocation/contest-housing-units/{contestHousingUnit}

GET /backoffice/allocation/typology-rules
GET /backoffice/allocation/typology-rules/create
POST /backoffice/allocation/typology-rules
GET /backoffice/allocation/typology-rules/{typologyAdequacyRule}/edit
PUT/PATCH /backoffice/allocation/typology-rules/{typologyAdequacyRule}
POST /backoffice/allocation/typology-rules/{typologyAdequacyRule}/activate
POST /backoffice/allocation/typology-rules/{typologyAdequacyRule}/deactivate

GET /backoffice/allocation/rule-sets
GET /backoffice/allocation/rule-sets/create
POST /backoffice/allocation/rule-sets
GET /backoffice/allocation/rule-sets/{allocationRuleSet}
GET /backoffice/allocation/rule-sets/{allocationRuleSet}/edit
PUT/PATCH /backoffice/allocation/rule-sets/{allocationRuleSet}
POST /backoffice/allocation/rule-sets/{allocationRuleSet}/activate
POST /backoffice/allocation/rule-sets/{allocationRuleSet}/archive
POST /backoffice/allocation/rule-sets/{allocationRuleSet}/duplicate

GET /backoffice/allocation/runs
GET /backoffice/allocation/runs/create
POST /backoffice/allocation/runs
GET /backoffice/allocation/runs/{allocationRun}
POST /backoffice/allocation/runs/{allocationRun}/run
POST /backoffice/allocation/runs/{allocationRun}/cancel
POST /backoffice/allocation/runs/{allocationRun}/lock

GET /backoffice/allocation/allocations
GET /backoffice/allocation/allocations/{allocation}
GET /backoffice/allocation/allocations/manual-create
POST /backoffice/allocation/allocations/manual
POST /backoffice/allocation/allocations/{allocation}/cancel
POST /backoffice/allocation/allocations/{allocation}/ready-for-contract

GET /backoffice/allocation/offers
GET /backoffice/allocation/offers/{allocationOffer}
POST /backoffice/allocation/offers/{allocationOffer}/issue
POST /backoffice/allocation/offers/{allocationOffer}/mark-expired
POST /backoffice/allocation/offers/{allocationOffer}/cancel

GET /backoffice/allocation/lotteries
GET /backoffice/allocation/lotteries/create
POST /backoffice/allocation/lotteries
GET /backoffice/allocation/lotteries/{lotteryRun}
POST /backoffice/allocation/lotteries/{lotteryRun}/run
POST /backoffice/allocation/lotteries/{lotteryRun}/lock
GET /backoffice/allocation/lotteries/{lotteryRun}/audit

GET /backoffice/allocation/reserve-lists
GET /backoffice/allocation/reserve-lists/{reserveList}
POST /backoffice/allocation/reserve-lists/{reserveList}/call-next
POST /backoffice/allocation/reserve-lists/{reserveList}/lock
POST /backoffice/allocation/reserve-lists/{reserveList}/archive

GET /backoffice/allocation/reports
GET /backoffice/allocation/reports/{allocationReport}
POST /backoffice/allocation/runs/{allocationRun}/reports/generate
POST /backoffice/allocation/reports/{allocationReport}/approve
GET /backoffice/allocation/reports/{allocationReport}/download
```

## Área do candidato

Criar, preferencialmente:

```text
GET /area-candidato/preferencias-habitacao
GET /area-candidato/preferencias-habitacao/{application}/editar
PUT/PATCH /area-candidato/preferencias-habitacao/{application}
POST /area-candidato/preferencias-habitacao/{application}/submeter

GET /area-candidato/atribuicoes
GET /area-candidato/atribuicoes/{allocation}

GET /area-candidato/ofertas-atribuicao
GET /area-candidato/ofertas-atribuicao/{allocationOffer}
POST /area-candidato/ofertas-atribuicao/{allocationOffer}/aceitar
POST /area-candidato/ofertas-atribuicao/{allocationOffer}/recusar
POST /area-candidato/atribuicoes/{allocation}/desistir
```

---

# 21. Views / páginas obrigatórias

Se o projeto usa Blade, criar:

## Backoffice

```text
resources/views/backoffice/allocation/contest-housing-units/index.blade.php
resources/views/backoffice/allocation/contest-housing-units/create.blade.php
resources/views/backoffice/allocation/contest-housing-units/edit.blade.php
resources/views/backoffice/allocation/contest-housing-units/show.blade.php

resources/views/backoffice/allocation/typology-rules/index.blade.php
resources/views/backoffice/allocation/typology-rules/create.blade.php
resources/views/backoffice/allocation/typology-rules/edit.blade.php

resources/views/backoffice/allocation/rule-sets/index.blade.php
resources/views/backoffice/allocation/rule-sets/create.blade.php
resources/views/backoffice/allocation/rule-sets/edit.blade.php
resources/views/backoffice/allocation/rule-sets/show.blade.php

resources/views/backoffice/allocation/runs/index.blade.php
resources/views/backoffice/allocation/runs/create.blade.php
resources/views/backoffice/allocation/runs/show.blade.php

resources/views/backoffice/allocation/allocations/index.blade.php
resources/views/backoffice/allocation/allocations/show.blade.php
resources/views/backoffice/allocation/allocations/manual-create.blade.php

resources/views/backoffice/allocation/offers/index.blade.php
resources/views/backoffice/allocation/offers/show.blade.php

resources/views/backoffice/allocation/lotteries/index.blade.php
resources/views/backoffice/allocation/lotteries/create.blade.php
resources/views/backoffice/allocation/lotteries/show.blade.php
resources/views/backoffice/allocation/lotteries/audit.blade.php

resources/views/backoffice/allocation/reserve-lists/index.blade.php
resources/views/backoffice/allocation/reserve-lists/show.blade.php

resources/views/backoffice/allocation/reports/index.blade.php
resources/views/backoffice/allocation/reports/show.blade.php
```

## Área do candidato

```text
resources/views/candidate/housing-preferences/index.blade.php
resources/views/candidate/housing-preferences/edit.blade.php

resources/views/candidate/allocations/index.blade.php
resources/views/candidate/allocations/show.blade.php

resources/views/candidate/allocation-offers/index.blade.php
resources/views/candidate/allocation-offers/show.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 22. UX obrigatória no backoffice

## Habitações do concurso

Mostrar:

```text
Concurso
Habitação
Morada/localização resumida
Tipologia
N.º de quartos
Capacidade mínima/máxima
Acessibilidade
Renda estimada
Estado no concurso
Ações
```

## Regras de atribuição

Mostrar:

```text
Nome
Programa
Concurso
Método de atribuição
Preferências permitidas
Sorteio permitido
Prazo de aceitação
Chamada automática de suplentes
Estado
Ações
```

## Execução de atribuição

Antes de executar, mostrar resumo:

```text
Lista definitiva usada
Número de candidatos rankeados
Número de habitações disponíveis
Método de atribuição
Regras de tipologia
Preferências consideradas
Sorteio aplicável
Número estimado de suplentes
Aviso de auditoria
```

Copy obrigatório:

```text
A execução da atribuição irá afetar habitações a candidatos com base na lista definitiva e nas regras configuradas. Confirme que a lista definitiva, as habitações disponíveis e as regras de tipologia estão corretas antes de prosseguir.
```

## Sorteio

A página de sorteio deve mostrar:

```text
Método
Participantes
Seed
Fonte da seed
Algoritmo
Data/hora de execução
Executado por
Resultados
Hash de auditoria
Payload auditável
Estado
```

## Relatório de atribuição

Mostrar:

```text
Número do relatório
Concurso
Lista definitiva
Execução de atribuição
Método
Habitações consideradas
Atribuições realizadas
Recusas
Desistências
Suplentes
Sorteios, se existirem
Exceções
Aprovação
Download, se existir
```

---

# 23. UX obrigatória para candidato

## Preferências de habitação

Mostrar:

```text
Concurso
Habitações disponíveis
Tipologia
Localização resumida
Renda estimada, se publicável
Acessibilidade
Ordem de preferência
Notas
Prazo para submissão
```

Copy obrigatório:

```text
Indique a sua ordem de preferência entre as habitações disponíveis, quando aplicável. A atribuição final dependerá das regras do concurso, da classificação e da adequação da tipologia ao seu agregado.
```

## Oferta de atribuição

Mostrar:

```text
Habitação atribuída
Tipologia
Localização resumida
Renda estimada, se aplicável
Prazo para resposta
Instruções
Botão aceitar
Botão recusar
Aviso sobre consequências da recusa
```

Copy obrigatório:

```text
Foi-lhe proposta a atribuição de uma habitação. Deve aceitar ou recusar dentro do prazo indicado. A falta de resposta poderá ser considerada como recusa ou desistência, conforme as regras do procedimento.
```

## Aceitação

Antes de aceitar, mostrar confirmação:

```text
Confirmo que pretendo aceitar a habitação proposta e que compreendo que a fase seguinte corresponde à preparação do contrato de arrendamento.
```

## Recusa

Antes de recusar, mostrar confirmação:

```text
Confirmo que pretendo recusar a habitação proposta e que compreendo que esta recusa poderá determinar a chamada do candidato suplente, conforme as regras do procedimento.
```

---

# 24. Regras de atribuição por ranking

A atribuição por ranking deve:

```text
Usar apenas entradas da lista definitiva prontas para atribuição
Ordenar por rank_position asc
Avaliar tipologia adequada
Avaliar disponibilidade da habitação
Considerar preferências se configurado
Atribuir primeira habitação adequada disponível
Criar reserve list para candidatos sem habitação
Registar candidatos sem habitação disponível adequada
```

Não ignorar tipologia.

Não atribuir habitação indisponível.

Não atribuir a candidato excluído.

---

# 25. Regras de sorteio auditável

Quando aplicável, o sorteio deve:

```text
Criar lista de participantes
Atribuir participant_number sem dados pessoais
Guardar seed
Guardar seed_source
Guardar algoritmo
Guardar data/hora
Guardar executante
Guardar ordem sorteada
Guardar audit_hash
Guardar audit_payload
Bloquear resultado após conclusão
Permitir consulta por auditor autorizado
```

Não usar sorteio não registado.

Não permitir alteração manual silenciosa do resultado.

Se houver intervenção manual posterior, criar justificação e audit log.

---

# 26. Regras de aceitação, recusa e suplentes

## Aceitação

Ao aceitar:

```text
AllocationOffer.status = accepted
Allocation.status = accepted
ContestHousingUnit.status = accepted
accepted_at preenchido
ready_for_contract_at preenchido
Notificação/registo interno criado
```

## Recusa

Ao recusar:

```text
AllocationOffer.status = refused
Allocation.status = refused
ContestHousingUnit.status volta a available ou reserved conforme regra
refusal_reason obrigatório
Chamar suplente se auto_call_next_on_refusal=true
```

## Expiração

Ao expirar prazo:

```text
AllocationOffer.status = expired
Allocation.status = expired
Chamar suplente se auto_call_next_on_expiry=true
```

## Desistência

Ao desistir:

```text
Allocation.status = withdrawn
withdrawal_reason obrigatório
Habitação pode voltar a available
Chamar suplente se configurado
```

## Substituição

Ao chamar suplente:

```text
ReserveListEntry.status = called
Nova Allocation criada
Nova AllocationOffer criada
replacement_for_allocation_id preenchido
Histórico preservado
```

---

# 27. Integração com listas definitivas

A Sprint 12 deve consumir dados da Sprint 11.

Regras:

```text
Usar apenas DefinitiveList published, locked ou approved conforme configuração.
Preferir listas locked.
Usar apenas DefinitiveListEntry ranked/admitted.
Ignorar entradas excluded/removed/cancelled.
Não alterar lista definitiva.
Não alterar ranking original.
Guardar referência à definitive_list_entry_id.
```

---

# 28. Integração com contrato — Sprint 13

A Sprint 12 deve preparar a Sprint 13.

Criar ou preparar scopes:

```text
Allocation::readyForContract()
Application::readyForContract()
HousingUnit::allocatedForContract()
```

Uma allocation está pronta para contrato quando:

```text
status = accepted ou ready_for_contract
Oferta aceite
Habitação associada
Candidatura válida
Candidato não desistiu
Habitação não foi substituída por outra allocation
```

Não criar contrato nesta sprint.

---

# 29. Integração com notificações

Se existir `OfficialNotification`, usar esse modelo para:

```text
allocation_offer_issued
allocation_offer_accepted
allocation_offer_refused
allocation_offer_expired
reserve_candidate_called
allocation_ready_for_contract
```

Se não existir:

```text
Criar AllocationNotification ou registo interno equivalente.
Não enviar email/SMS real.
Documentar pendência.
```

Não marcar notificação como `sent` sem envio real.

---

# 30. Auditoria

Se existir auditoria, auditar:

```text
Associação de habitação ao concurso
Alteração de estado de habitação no concurso
Criação de regra de tipologia
Criação de regra de atribuição
Execução de atribuição
Criação de allocation
Criação de offer
Emissão de offer
Aceitação pelo candidato
Recusa pelo candidato
Expiração de offer
Desistência
Chamada de suplente
Execução de sorteio
Bloqueio de sorteio
Geração de relatório
Aprovação de relatório
Manual override de atribuição
```

Não criar auditoria paralela se já existir sistema de auditoria.

Se não existir auditoria, documentar pendência.

Não guardar dados sensíveis excessivos nos logs.

---

# 31. RGPD e segurança

Regras obrigatórias:

```text
Atribuições contêm dados sensíveis.
Candidato só vê as suas próprias ofertas e atribuições.
Candidato não vê atribuições de outros candidatos.
Candidato não vê lista interna de suplentes, salvo informação própria.
Backoffice exige permissões.
Júri só acede conforme autorização.
Auditor não altera dados.
Sorteios auditáveis não devem expor dados pessoais desnecessários.
Relatórios públicos devem ser anonimizados se forem publicados.
Não expor morada completa publicamente sem regra clara.
Não expor dados pessoais em URLs públicas.
Não guardar documentos nas tabelas de atribuição.
Não guardar paths internos.
Não permitir mass assignment de status.
Não permitir aceitar/recusar oferta de outro candidato.
Não permitir atribuir habitação já ativa noutra allocation.
```

---

# 32. Seeders e factories

Criar factories:

```text
ContestHousingUnitFactory
TypologyAdequacyRuleFactory
HousingPreferenceFactory
AllocationRuleSetFactory
AllocationRunFactory
AllocationFactory
AllocationOfferFactory
LotteryRunFactory
LotteryParticipantFactory
LotteryDrawResultFactory
ReserveListFactory
ReserveListEntryFactory
AllocationReportFactory
AllocationNotificationFactory, se existir
```

Criar seeders opcionais:

```text
AllocationRuleSetSeeder
TypologyAdequacyRuleSeeder
DemoContestHousingUnitSeeder
DemoAllocationSeeder
```

Usar apenas dados fictícios.

Não usar dados pessoais reais.

---

# 33. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text
guest_cannot_access_candidate_allocations
candidate_can_access_own_allocation_offer
candidate_cannot_access_other_candidate_allocation_offer
candidate_cannot_accept_other_candidate_offer
candidate_cannot_refuse_other_candidate_offer
candidate_cannot_access_backoffice_allocation
technician_can_access_backoffice_allocation_if_authorized
auditor_can_view_lottery_audit_without_editing
```

## Habitações do concurso

```text
admin_can_attach_housing_unit_to_contest
contest_housing_unit_requires_housing_unit
contest_housing_unit_requires_program_or_contest
housing_unit_cannot_be_attached_to_conflicting_active_contest
contest_housing_unit_can_be_marked_available
contest_housing_unit_can_be_marked_unavailable
```

## Tipologia

```text
typology_rule_can_be_created_for_contest
typology_rule_can_be_created_for_program
contest_typology_rule_takes_precedence_over_program_rule
typology_service_returns_adequate_for_matching_household
typology_service_returns_inadequate_for_mismatched_household
typology_service_returns_requires_manual_review_when_data_missing
```

## Preferências

```text
candidate_can_submit_housing_preferences_for_own_application
candidate_cannot_submit_preferences_for_other_application
preference_order_must_be_unique
candidate_cannot_choose_housing_unit_outside_contest
preferences_are_locked_after_allocation_run
```

## Atribuição por ranking

```text
allocation_run_can_be_created_from_locked_definitive_list
allocation_run_requires_available_housing_units
ranking_allocation_respects_rank_position
ranking_allocation_respects_typology
ranking_allocation_does_not_allocate_unavailable_housing_unit
ranking_allocation_creates_allocation_records
ranking_allocation_creates_offers_when_requires_acceptance
ranking_allocation_creates_reserve_list
```

## Sorteio auditável

```text
lottery_run_can_be_created_when_rule_allows_lottery
lottery_run_creates_participants
lottery_run_generates_draw_results
lottery_run_stores_seed
lottery_run_stores_algorithm
lottery_run_stores_audit_hash
locked_lottery_run_cannot_be_modified
auditor_can_view_lottery_audit_payload
```

## Aceitação e recusa

```text
candidate_can_accept_own_offer_within_deadline
accepted_offer_marks_allocation_accepted
accepted_offer_marks_allocation_ready_for_contract
candidate_can_refuse_own_offer_with_reason
refused_offer_marks_allocation_refused
refusal_releases_housing_unit_when_configured
refusal_calls_next_reserve_candidate_when_configured
expired_offer_calls_next_reserve_candidate_when_configured
```

## Suplentes

```text
reserve_list_is_created_from_unallocated_candidates
reserve_list_order_respects_ranking_or_lottery
next_reserve_candidate_can_be_called
calling_reserve_candidate_creates_new_allocation
replacement_allocation_references_previous_allocation
```

## Relatório

```text
allocation_report_can_be_generated
allocation_report_contains_allocation_summary
allocation_report_contains_refusals_summary
allocation_report_contains_reserve_summary
allocation_report_contains_lottery_audit_when_applicable
allocation_report_can_be_approved
```

## Integração com contrato

```text
ready_for_contract_scope_returns_accepted_allocations
refused_allocations_are_not_ready_for_contract
withdrawn_allocations_are_not_ready_for_contract
superseded_allocations_are_not_ready_for_contract
```

## Segurança

```text
candidate_cannot_mass_assign_allocation_status
candidate_cannot_mass_assign_offer_status
candidate_cannot_create_manual_allocation
candidate_cannot_run_lottery
candidate_cannot_call_reserve_candidate
allocation_cannot_assign_same_housing_unit_twice
allocation_cannot_assign_same_candidate_twice_in_same_contest
```

## Auditoria, se existir

```text
running_allocation_generates_audit_log
running_lottery_generates_audit_log
accepting_offer_generates_audit_log
refusing_offer_generates_audit_log
calling_reserve_candidate_generates_audit_log
generating_allocation_report_generates_audit_log
```

Se alguma dependência não existir, documentar teste como pendente em vez de criar teste quebrado.

---

# 34. Comandos de validação

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

# 35. Atualização documental obrigatória

No final, atualizar, se existirem:

```text
docs/backlog/sprint-12-atribuicao-habitacoes.md
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
Pendências para Sprint 13
Validações jurídicas pendentes
Regras de tipologia implementadas
Regras de sorteio implementadas
Limitações de notificações
```

---

# 36. Critérios de aceitação

A Sprint 12 está concluída quando:

```text
O Município consegue associar habitações a um concurso
O Município consegue configurar regras de tipologia adequada
O candidato consegue indicar preferências de habitação quando aplicável
O sistema consegue executar atribuição por ranking
O sistema respeita ranking e tipologia
O sistema não atribui habitação indisponível
O sistema não atribui habitação a candidato excluído
O sistema consegue executar sorteio quando aplicável
O sorteio guarda participantes, seed, algoritmo, ordem e hash de auditoria
O sorteio é consultável por auditor autorizado
O sistema cria ofertas de atribuição
O candidato consegue aceitar dentro do prazo
O candidato consegue recusar dentro do prazo
A recusa exige motivo
A recusa liberta a habitação quando configurado
A recusa chama suplente quando configurado
A expiração chama suplente quando configurado
A lista de reserva/suplentes é gerada
A substituição pelo candidato seguinte é registada
A aceitação prepara candidatura para contrato
O sistema gera relatório/ata de atribuição
O relatório contém método, resultados, recusas, suplentes e sorteios
O candidato não vê dados de outros candidatos
Backoffice exige permissões
Auditoria é usada se existir
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foi implementado contrato
Não foi implementado pagamento
Não foi implementada manutenção
```

---

# 37. Resposta final obrigatória

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
20. Regras de tipologia implementadas
21. Regras de sorteio implementadas
22. Limitações de notificações
23. Confirmação de que não foram implementadas funcionalidades fora de âmbito
24. Recomendação objetiva para avançar ou não para Sprint 13
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 38. Execução imediata

Executa agora apenas:

```text
Sprint 12 — Atribuição de Habitações
```

Usa como referência principal:

```text
docs/backlog/sprint-12-atribuicao-habitacoes.md
```

Fim da master prompt da Sprint 12.
