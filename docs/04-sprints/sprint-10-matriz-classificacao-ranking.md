# Sprint 10 — Matriz de Classificação e Ranking

## Prioridade de desenvolvimento

Esta sprint pertence à prioridade funcional:

```text
4. Classificação
```

A Sprint 10 implementa o sistema de classificação das candidaturas elegíveis, permitindo aplicar uma matriz de pontuação configurável por programa ou concurso, calcular pontuações, ordenar candidaturas e gerar rankings auditáveis.

Esta sprint deve transformar candidaturas elegíveis em resultados classificativos comparáveis, sem avançar ainda para listas provisórias, reclamações, audiência de interessados, listas definitivas ou atribuição.

---

# 1. Objetivo da Sprint

Implementar a Matriz de Classificação e Ranking da plataforma municipal de Arrendamento Acessível.

A plataforma deve permitir:

## Backoffice municipal

```text
Configurar matrizes de classificação por programa
Configurar matrizes de classificação por concurso
Criar critérios de pontuação
Criar regras de pontuação
Definir pesos
Definir pontuações máximas
Definir critérios automáticos
Definir critérios manuais
Definir regras de desempate
Executar classificação de candidaturas elegíveis
Reexecutar classificação quando autorizado
Consultar pontuação por candidatura
Consultar detalhe da pontuação por critério
Consultar ranking provisório interno
Gerar snapshots de ranking
Auditar execuções
Exportar resultados, se autorizado
```

## Júri / técnico autorizado

```text
Consultar candidaturas classificadas
Consultar detalhe de pontuação
Introduzir pontuação manual quando prevista
Validar critérios que exigem apreciação técnica
Consultar histórico de alterações
```

## Candidato

```text
Não vê ranking público nesta sprint
Não vê lista provisória nesta sprint
Pode ver apenas informação genérica de que a candidatura será classificada, se a candidatura já estiver submetida
A consulta de listas e resultados públicos fica para Sprint 11
```

## Sistema

```text
Calcula pontuações automaticamente quando possível
Regista pontuação total
Regista pontuação por critério
Regista critérios não aplicáveis
Regista critérios que exigem análise manual
Aplica regras de desempate
Gera ranking interno
Gera snapshot do ranking
Mantém histórico
Impede alterações não auditadas
Protege dados sensíveis
```

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 10.

Não avances para Sprint 11, Sprint 12, Sprint 13 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash
git branch --show-current
```

Não interromper a execução por causa da branch atual.

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
docs/backlog/sprint-7-motor-elegibilidade.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
docs/backlog/sprint-10-matriz-classificacao-ranking.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

Antes de implementar, identifica:

```text
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
Modelo User
Modelo Program
Modelo Contest
Modelo Application
Modelo ApplicationSnapshot
Modelo EligibilityCheck
Modelo EligibilityCheckResult
Modelo Household
Modelo HouseholdMember
Modelo IncomeRecord
Modelo CurrentHousingSituation
Modelo DocumentSubmission
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
ScoringRuleSet
ScoringCriterion
ScoringRule
ApplicationScore
ApplicationScoreDetail
RankingSnapshot
RankingEntry
ClassificationMatrix
ClassificationResult
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens ou APP_KEY.

---

# 3. Dependências funcionais

Esta sprint depende preferencialmente de:

```text
Sprint 7 — Motor de Elegibilidade
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento, se já existir
```

## Dependências mínimas obrigatórias

Para executar a Sprint 10 de forma completa devem existir:

```text
Application
Program
Contest
User
Sistema de permissões
Backoffice
```

## Dependência de elegibilidade

A classificação deve ser aplicada, por defeito, apenas a candidaturas elegíveis.

Se o motor de elegibilidade existir, usar:

```text
Último EligibilityCheck da candidatura
Resultado eligible
```

Se o motor de elegibilidade não existir, implementar a matriz e o ranking, mas impedir execução formal completa e documentar a pendência.

Não criar motor de elegibilidade simplificado dentro desta sprint.

## Dependência de candidatura

Se `Application` não existir, interrompe a implementação funcional e informa que a Sprint 10 depende da Sprint 8.

Não criar Application nesta sprint.

---

# 4. Âmbito incluído

Implementar:

```text
ScoringRuleSet
ScoringCriterion
ScoringRule
ScoringRun
ApplicationScore
ApplicationScoreDetail
RankingSnapshot
RankingEntry
TieBreakerRule

Enums de classificação
Services de classificação
Backoffice de matrizes
Backoffice de critérios
Backoffice de regras
Execução de classificação
Pontuação automática
Pontuação manual, quando aplicável
Regras de desempate
Ranking interno
Snapshots de ranking
Histórico de execuções
Auditoria, se existir
Policies
Form Requests
Rotas
Views/páginas
Seeders
Factories
Testes
Atualização documental
```

---

# 5. Fora de âmbito

Não implementar nesta sprint:

```text
Listas provisórias públicas
Publicação de resultados ao candidato
Reclamações
Audiência de interessados
Listas definitivas
Atribuição de habitações
Sorteio
Contrato
Cálculo de renda contratual
Pagamentos
Manutenção
Notificações reais por email/SMS
Integração com AT
Integração com Segurança Social
Integração com Autenticação.GOV
Assinatura digital
```

Podem ser criados pontos de integração para estas funcionalidades futuras, mas não implementar essas fases.

---

# 6. Separação conceptual obrigatória

Esta sprint deve separar claramente:

```text
Elegibilidade = verifica se a candidatura cumpre condições mínimas.
Classificação = atribui pontuação e ordena candidaturas elegíveis.
Atribuição = decide que habitação é atribuída a cada candidatura.
Lista provisória = publicação formal de resultados para efeitos administrativos.
```

Não misturar classificação com atribuição.

Não publicar listas nesta sprint.

Não criar decisões administrativas finais nesta sprint.

---

# 7. Conceito funcional

A classificação deve funcionar assim:

```text
Candidatura submetida
→ Candidatura elegível
→ Aplicação de matriz de classificação
→ Pontuação por critério
→ Pontuação total
→ Aplicação de desempates
→ Ranking interno
→ Snapshot de ranking
→ Preparação para listas provisórias na Sprint 11
```

A matriz de classificação deve ser configurável por:

```text
Programa
Concurso
```

Regras de precedência:

```text
Matriz específica de concurso prevalece sobre matriz do programa.
Apenas matrizes ativas podem ser usadas.
Matrizes arquivadas preservam histórico, mas não são usadas em novas execuções.
```

---

# 8. Estados de classificação

## Estados de ScoringRuleSet

```text
draft
active
archived
```

## Estados de ScoringRun

```text
draft
running
completed
failed
cancelled
locked
```

## Estados de ApplicationScore

```text
pending
calculated
requires_manual_review
manual_review_completed
excluded_from_scoring
locked
```

## Resultados por critério

```text
applied
not_applicable
requires_manual_review
missing_data
failed
manual
```

---

# 9. Modelo de dados

## 9.1 ScoringRuleSet

Criar entidade:

```text
ScoringRuleSet
```

Tabela:

```text
scoring_rule_sets
```

Campos mínimos:

```text
id
program_id
contest_id
name
description
status
is_default
starts_at
ends_at
created_by
updated_by
created_at
updated_at
deleted_at
```

Regras:

```text
Pode estar associado a programa.
Pode estar associado a concurso.
Se contest_id existir, prevalece sobre rule set do programa.
Apenas rule sets active são usados.
Não apagar rule set usado em classificação.
Preferir archived.
Usar soft deletes.
```

---

## 9.2 ScoringCriterion

Criar entidade:

```text
ScoringCriterion
```

Tabela:

```text
scoring_criteria
```

Campos mínimos:

```text
id
scoring_rule_set_id
code
name
description
category
target
calculation_type
operator
expected_value
minimum_value
maximum_value
points
max_points
weight
requires_manual_review
is_exclusionary
is_active
sort_order
success_message
failure_message
review_message
created_at
updated_at
deleted_at
```

## Categorias recomendadas

```text
household
income
housing_situation
residence
employment
age
disability
dependency
vulnerability
documents
eligibility
typology
manual_assessment
other
```

## Targets recomendados

```text
application
adhesion_registration
household
household_member
income_records
current_housing_situation
documents
eligibility_check
calculated_value
manual
```

## Tipos de cálculo

```text
fixed_points
boolean
range
threshold
proportional
weighted
manual
custom
```

## Operadores

```text
equals
not_equals
greater_than
greater_than_or_equal
less_than
less_than_or_equal
between
is_true
is_false
exists
not_exists
in
not_in
custom
```

## Regras

```text
code deve ser único dentro do scoring_rule_set_id.
Critérios inativos não são avaliados.
Critérios manuais devem ficar pendentes até avaliação técnica.
Critérios exclusionary podem excluir candidatura da classificação, mas não devem alterar elegibilidade automaticamente.
```

---

## 9.3 ScoringRule

Criar entidade:

```text
ScoringRule
```

Tabela:

```text
scoring_rules
```

## Objetivo

Permitir regras mais granulares dentro de um critério, sobretudo para escalões de pontuação.

Campos mínimos:

```text
id
scoring_criterion_id
label
description
operator
value
minimum_value
maximum_value
points
weight
sort_order
is_active
created_at
updated_at
deleted_at
```

## Exemplos

```text
Rendimento per capita inferior a 1 IAS → 20 pontos
Rendimento per capita entre 1 e 1,5 IAS → 15 pontos
Rendimento per capita entre 1,5 e 2 IAS → 10 pontos
Agregado com dependentes → 5 pontos
Situação habitacional precária → 15 pontos
Residência no município superior a 3 anos → 10 pontos
```

Não hardcodar estes exemplos em controllers.

Devem ser configuráveis.

---

## 9.4 TieBreakerRule

Criar entidade:

```text
TieBreakerRule
```

Tabela:

```text
tie_breaker_rules
```

Campos mínimos:

```text
id
scoring_rule_set_id
code
name
description
target
direction
priority_order
is_active
created_at
updated_at
deleted_at
```

## Valores de `direction`

```text
asc
desc
```

## Exemplos de desempate

```text
Maior pontuação em vulnerabilidade habitacional
Menor rendimento per capita
Maior número de dependentes
Maior tempo de residência no município
Data de submissão mais antiga
Idade mais elevada do requerente
```

Regras:

```text
Desempates são aplicados apenas quando há igualdade de pontuação total.
A ordem é definida por priority_order.
Critérios de desempate devem ser auditáveis.
```

---

## 9.5 ScoringRun

Criar entidade:

```text
ScoringRun
```

Tabela:

```text
scoring_runs
```

## Objetivo

Registar cada execução de classificação.

Campos mínimos:

```text
id
scoring_rule_set_id
program_id
contest_id
status
started_by
started_at
completed_at
failed_at
failure_reason
total_applications
scored_applications
manual_review_applications
excluded_applications
notes
created_at
updated_at
deleted_at
```

Regras:

```text
Cada execução deve ficar registada.
Uma execução deve poder falhar sem destruir resultados anteriores.
Execuções concluídas podem ser bloqueadas.
Não apagar execuções com resultados.
Usar soft deletes.
```

---

## 9.6 ApplicationScore

Criar entidade:

```text
ApplicationScore
```

Tabela:

```text
application_scores
```

Campos mínimos:

```text
id
scoring_run_id
application_id
scoring_rule_set_id
program_id
contest_id
user_id
status
total_score
automatic_score
manual_score
tie_breaker_values
rank_position
is_tied
requires_manual_review
excluded_from_ranking
exclusion_reason
calculated_at
calculated_by
locked_at
locked_by
created_at
updated_at
deleted_at
```

Regras:

```text
Cada candidatura deve ter no máximo um ApplicationScore por ScoringRun.
total_score = automatic_score + manual_score, salvo regra configurada diferente.
rank_position é calculado após aplicação de desempates.
tie_breaker_values pode ser JSON.
Usar soft deletes.
```

---

## 9.7 ApplicationScoreDetail

Criar entidade:

```text
ApplicationScoreDetail
```

Tabela:

```text
application_score_details
```

Campos mínimos:

```text
id
application_score_id
scoring_criterion_id
scoring_rule_id
code
name
category
result
points_awarded
max_points
weight
raw_value
normalized_value
message
technical_message
requires_manual_review
manual_points
manual_notes
reviewed_by
reviewed_at
created_at
updated_at
```

Regras:

```text
Cada critério avaliado gera detalhe.
raw_value deve evitar dados pessoais excessivos.
technical_message apenas backoffice.
manual_notes apenas backoffice.
```

---

## 9.8 RankingSnapshot

Criar entidade:

```text
RankingSnapshot
```

Tabela:

```text
ranking_snapshots
```

Campos mínimos:

```text
id
scoring_run_id
program_id
contest_id
snapshot_number
status
generated_by
generated_at
published_at
notes
created_at
updated_at
deleted_at
```

Estados recomendados:

```text
draft
internal
locked
archived
```

Nesta sprint, não usar `published` para publicação pública de listas.

Se existir campo `published_at`, manter nulo até Sprint 11.

---

## 9.9 RankingEntry

Criar entidade:

```text
RankingEntry
```

Tabela:

```text
ranking_entries
```

Campos mínimos:

```text
id
ranking_snapshot_id
application_score_id
application_id
rank_position
previous_rank_position
total_score
tie_breaker_values
is_tied
status
created_at
updated_at
```

Estados recomendados:

```text
ranked
tied
excluded
requires_manual_review
```

---

# 10. Enums recomendados

Criar, se a versão do PHP permitir:

```text
App\Enums\ScoringRuleSetStatus
App\Enums\ScoringCalculationType
App\Enums\ScoringOperator
App\Enums\ScoringRunStatus
App\Enums\ApplicationScoreStatus
App\Enums\ScoreCriterionResult
App\Enums\RankingSnapshotStatus
App\Enums\RankingEntryStatus
App\Enums\TieBreakerDirection
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 11. Relações obrigatórias

## Program

```text
hasMany ScoringRuleSet
hasMany ScoringRun
hasMany ApplicationScore
hasMany RankingSnapshot
```

## Contest

```text
hasMany ScoringRuleSet
hasMany ScoringRun
hasMany ApplicationScore
hasMany RankingSnapshot
```

## Application

```text
hasMany ApplicationScore
hasOne latestApplicationScore
```

## ScoringRuleSet

```text
belongsTo Program nullable
belongsTo Contest nullable
hasMany ScoringCriterion
hasMany TieBreakerRule
hasMany ScoringRun
belongsTo User as createdBy
belongsTo User as updatedBy
```

## ScoringCriterion

```text
belongsTo ScoringRuleSet
hasMany ScoringRule
hasMany ApplicationScoreDetail
```

## ScoringRule

```text
belongsTo ScoringCriterion
```

## TieBreakerRule

```text
belongsTo ScoringRuleSet
```

## ScoringRun

```text
belongsTo ScoringRuleSet
belongsTo Program nullable
belongsTo Contest nullable
belongsTo User as startedBy
hasMany ApplicationScore
hasMany RankingSnapshot
```

## ApplicationScore

```text
belongsTo ScoringRun
belongsTo Application
belongsTo ScoringRuleSet
belongsTo Program nullable
belongsTo Contest nullable
belongsTo User
belongsTo User as calculatedBy
belongsTo User as lockedBy
hasMany ApplicationScoreDetail
hasOne RankingEntry
```

## ApplicationScoreDetail

```text
belongsTo ApplicationScore
belongsTo ScoringCriterion
belongsTo ScoringRule nullable
belongsTo User as reviewedBy
```

## RankingSnapshot

```text
belongsTo ScoringRun
belongsTo Program nullable
belongsTo Contest nullable
belongsTo User as generatedBy
hasMany RankingEntry
```

## RankingEntry

```text
belongsTo RankingSnapshot
belongsTo ApplicationScore
belongsTo Application
```

---

# 12. Services obrigatórios

Criar:

```text
App\Services\Scoring\ScoringRuleSetResolver
App\Services\Scoring\ScoringEngine
App\Services\Scoring\ScoringCriterionEvaluator
App\Services\Scoring\ApplicationScoreService
App\Services\Scoring\ManualScoreService
App\Services\Scoring\RankingService
App\Services\Scoring\TieBreakerService
App\Services\Scoring\RankingSnapshotService
App\Services\Scoring\ScoringDataProvider
App\Services\Scoring\ScoringMessageService
```

---

## 12.1 ScoringRuleSetResolver

Responsável por:

```text
Resolver matriz aplicável a programa
Resolver matriz aplicável a concurso
Dar prioridade à matriz específica do concurso
Usar apenas matrizes ativas
Ignorar draft e archived
Gerar erro claro se não existir matriz aplicável
```

---

## 12.2 ScoringEngine

Responsável por:

```text
Receber contest_id ou program_id
Resolver matriz aplicável
Obter candidaturas elegíveis
Criar ScoringRun
Percorrer candidaturas
Avaliar critérios ativos
Criar ApplicationScore
Criar ApplicationScoreDetail
Calcular automatic_score
Identificar requires_manual_review
Aplicar desempates
Gerar RankingSnapshot interno
Registar totais da execução
Tratar falhas sem perder histórico anterior
```

A execução deve usar transações quando apropriado.

---

## 12.3 ScoringCriterionEvaluator

Responsável por avaliar critérios individuais.

Deve suportar:

```text
fixed_points
boolean
range
threshold
proportional
weighted
manual
custom
```

Critérios automáticos mínimos a suportar:

```text
household_size
number_of_dependents
number_of_minors
number_of_disabled_members
monthly_household_income
annual_household_income
monthly_income_per_capita
annual_income_per_capita
current_rent_burden
resides_in_municipality
works_in_municipality
years_residing_in_municipality
housing_status
housing_condition
risk_of_eviction
homelessness
temporary_accommodation
accessibility_needs
submitted_at
eligibility_result
```

Se faltar dado necessário, devolver:

```text
missing_data
```

Se o critério for manual, devolver:

```text
requires_manual_review
```

---

## 12.4 ApplicationScoreService

Responsável por:

```text
Criar pontuação da candidatura
Atualizar total_score
Recalcular candidatura específica
Bloquear pontuação
Consultar detalhe de pontuação
Impedir alteração indevida após lock
```

---

## 12.5 ManualScoreService

Responsável por:

```text
Listar critérios manuais pendentes
Permitir pontuação manual autorizada
Validar pontos máximos
Guardar reviewed_by
Guardar reviewed_at
Guardar manual_notes
Atualizar manual_score
Recalcular total_score
Auditar alteração, se auditoria existir
```

---

## 12.6 RankingService

Responsável por:

```text
Ordenar candidaturas por total_score
Aplicar desempates
Atribuir rank_position
Marcar empates
Excluir candidaturas marcadas como excluded_from_ranking
Gerar RankingEntry
```

Ordenação base:

```text
total_score desc
desempates configurados
application submitted_at asc, se configurado como fallback
```

---

## 12.7 TieBreakerService

Responsável por:

```text
Resolver valores de desempate
Aplicar regras por priority_order
Guardar tie_breaker_values
Identificar empates persistentes
```

---

## 12.8 RankingSnapshotService

Responsável por:

```text
Criar snapshot interno
Incrementar snapshot_number
Guardar ranking_entries
Bloquear snapshot quando necessário
Comparar snapshot anterior
Preparar dados para Sprint 11
```

---

## 12.9 ScoringDataProvider

Responsável por fornecer dados calculados:

```text
Número de membros do agregado
Número de adultos
Número de menores
Número de dependentes
Número de membros com deficiência
Rendimento mensal total
Rendimento anual total
Rendimento per capita
Taxa de esforço
Estado habitacional
Tempo de residência
Trabalho no município
Estado documental
Estado de elegibilidade
Data de submissão
```

Reutilizar services existentes sempre que possível.

Não duplicar lógica de rendimentos, documentos ou elegibilidade se já existir.

---

## 12.10 ScoringMessageService

Responsável por:

```text
Gerar mensagens simples para backoffice
Gerar mensagens técnicas
Evitar exposição de dados sensíveis
Usar mensagens configuradas nos critérios
Separar mensagens internas de mensagens futuras ao candidato
```

---

# 13. Backoffice

Criar área administrativa de classificação.

Controllers recomendados:

```text
App\Http\Controllers\Backoffice\ScoringRuleSetController
App\Http\Controllers\Backoffice\ScoringCriterionController
App\Http\Controllers\Backoffice\ScoringRuleController
App\Http\Controllers\Backoffice\TieBreakerRuleController
App\Http\Controllers\Backoffice\ScoringRunController
App\Http\Controllers\Backoffice\ApplicationScoreController
App\Http\Controllers\Backoffice\RankingSnapshotController
```

## ScoringRuleSetController

Ações:

```text
index
show
create
store
edit
update
activate
archive
duplicate
```

## ScoringCriterionController

Ações:

```text
index
create
store
edit
update
activate
inactivate
```

## ScoringRuleController

Ações:

```text
index
create
store
edit
update
destroy ou inactivate
```

## TieBreakerRuleController

Ações:

```text
index
create
store
edit
update
activate
inactivate
```

## ScoringRunController

Ações:

```text
index
show
create
store
run
cancel
lock
```

## ApplicationScoreController

Ações:

```text
index
show
manualReview
updateManualScore
lock
```

## RankingSnapshotController

Ações:

```text
index
show
lock
archive
export, se permitido
```

---

# 14. Área do candidato

Nesta sprint, não publicar ranking ao candidato.

Pode ser criada ou atualizada uma página simples na área da candidatura para indicar:

```text
A candidatura será classificada de acordo com os critérios definidos no aviso de concurso.
Os resultados provisórios serão disponibilizados em fase própria do procedimento.
```

Não mostrar:

```text
Pontuação total
Posição no ranking
Lista de outros candidatos
Critérios técnicos internos
```

A exposição de resultados ao candidato fica para Sprint 11.

---

# 15. Form Requests

Criar:

```text
StoreScoringRuleSetRequest
UpdateScoringRuleSetRequest
StoreScoringCriterionRequest
UpdateScoringCriterionRequest
StoreScoringRuleRequest
UpdateScoringRuleRequest
StoreTieBreakerRuleRequest
UpdateTieBreakerRuleRequest
RunScoringRequest
UpdateManualScoreRequest
LockScoringRunRequest
```

## StoreScoringRuleSetRequest

Validações mínimas:

```text
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
name required|string|max:255
description nullable|string|max:3000
status required|in:draft,active,archived
is_default boolean
starts_at nullable|date
ends_at nullable|date|after_or_equal:starts_at
```

Regra adicional:

```text
Deve existir program_id ou contest_id.
```

## StoreScoringCriterionRequest

```text
scoring_rule_set_id required|exists:scoring_rule_sets,id
code required|string|max:100
name required|string|max:255
description nullable|string|max:3000
category required|string|max:100
target required|string|max:100
calculation_type required|string|max:100
operator nullable|string|max:100
expected_value nullable
minimum_value nullable|numeric
maximum_value nullable|numeric
points nullable|numeric|min:0
max_points nullable|numeric|min:0
weight nullable|numeric|min:0
requires_manual_review boolean
is_exclusionary boolean
is_active boolean
sort_order nullable|integer|min:0
success_message nullable|string|max:1000
failure_message nullable|string|max:1000
review_message nullable|string|max:1000
```

## UpdateManualScoreRequest

```text
application_score_detail_id required|exists:application_score_details,id
manual_points required|numeric|min:0
manual_notes nullable|string|max:3000
```

Regra adicional:

```text
manual_points não pode exceder max_points do critério.
```

## RunScoringRequest

```text
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
scoring_rule_set_id nullable|exists:scoring_rule_sets,id
notes nullable|string|max:3000
```

Regra adicional:

```text
Deve existir contest_id ou program_id.
```

---

# 16. Policies

Criar:

```text
ScoringRuleSetPolicy
ScoringCriterionPolicy
ScoringRulePolicy
TieBreakerRulePolicy
ScoringRunPolicy
ApplicationScorePolicy
RankingSnapshotPolicy
RankingEntryPolicy
```

## Regras para admin

```text
Pode gerir matrizes
Pode gerir critérios
Pode gerir regras
Pode executar classificação
Pode consultar rankings internos
Pode bloquear execuções
Pode exportar se autorizado
```

## Regras para técnico municipal

```text
Pode consultar matrizes se autorizado
Pode executar classificação se autorizado
Pode consultar pontuações
Pode introduzir pontuação manual se autorizado
Pode consultar ranking interno
```

## Regras para júri

```text
Pode consultar pontuações se autorizado
Pode introduzir avaliação manual quando previsto
Não pode alterar configuração global sem permissão
Não pode publicar listas nesta sprint
```

## Regras para auditor

```text
Pode consultar histórico e resultados
Não pode alterar critérios
Não pode executar classificação
Não pode alterar pontuação
```

## Regras para candidato

```text
Não acede ao backoffice de classificação
Não vê ranking interno
Não vê pontuação nesta sprint
Não vê dados de outras candidaturas
```

---

# 17. Rotas

## Backoffice

Criar, preferencialmente:

```text
GET /backoffice/scoring/rule-sets
GET /backoffice/scoring/rule-sets/create
POST /backoffice/scoring/rule-sets
GET /backoffice/scoring/rule-sets/{scoringRuleSet}
GET /backoffice/scoring/rule-sets/{scoringRuleSet}/edit
PUT/PATCH /backoffice/scoring/rule-sets/{scoringRuleSet}
POST /backoffice/scoring/rule-sets/{scoringRuleSet}/activate
POST /backoffice/scoring/rule-sets/{scoringRuleSet}/archive
POST /backoffice/scoring/rule-sets/{scoringRuleSet}/duplicate

GET /backoffice/scoring/rule-sets/{scoringRuleSet}/criteria
GET /backoffice/scoring/rule-sets/{scoringRuleSet}/criteria/create
POST /backoffice/scoring/rule-sets/{scoringRuleSet}/criteria
GET /backoffice/scoring/criteria/{scoringCriterion}/edit
PUT/PATCH /backoffice/scoring/criteria/{scoringCriterion}
POST /backoffice/scoring/criteria/{scoringCriterion}/activate
POST /backoffice/scoring/criteria/{scoringCriterion}/inactivate

GET /backoffice/scoring/criteria/{scoringCriterion}/rules
GET /backoffice/scoring/criteria/{scoringCriterion}/rules/create
POST /backoffice/scoring/criteria/{scoringCriterion}/rules
GET /backoffice/scoring/rules/{scoringRule}/edit
PUT/PATCH /backoffice/scoring/rules/{scoringRule}
DELETE /backoffice/scoring/rules/{scoringRule}

GET /backoffice/scoring/rule-sets/{scoringRuleSet}/tie-breakers
GET /backoffice/scoring/rule-sets/{scoringRuleSet}/tie-breakers/create
POST /backoffice/scoring/rule-sets/{scoringRuleSet}/tie-breakers
GET /backoffice/scoring/tie-breakers/{tieBreakerRule}/edit
PUT/PATCH /backoffice/scoring/tie-breakers/{tieBreakerRule}
POST /backoffice/scoring/tie-breakers/{tieBreakerRule}/activate
POST /backoffice/scoring/tie-breakers/{tieBreakerRule}/inactivate

GET /backoffice/scoring/runs
GET /backoffice/scoring/runs/create
POST /backoffice/scoring/runs
GET /backoffice/scoring/runs/{scoringRun}
POST /backoffice/scoring/runs/{scoringRun}/run
POST /backoffice/scoring/runs/{scoringRun}/lock
POST /backoffice/scoring/runs/{scoringRun}/cancel

GET /backoffice/scoring/application-scores
GET /backoffice/scoring/application-scores/{applicationScore}
GET /backoffice/scoring/application-scores/{applicationScore}/manual-review
PUT/PATCH /backoffice/scoring/application-scores/{applicationScore}/manual-review
POST /backoffice/scoring/application-scores/{applicationScore}/lock

GET /backoffice/scoring/ranking-snapshots
GET /backoffice/scoring/ranking-snapshots/{rankingSnapshot}
POST /backoffice/scoring/ranking-snapshots/{rankingSnapshot}/lock
POST /backoffice/scoring/ranking-snapshots/{rankingSnapshot}/archive
```

Não criar rotas públicas de ranking nesta sprint.

---

# 18. Views / páginas

Se o projeto usa Blade, criar:

```text
resources/views/backoffice/scoring/rule-sets/index.blade.php
resources/views/backoffice/scoring/rule-sets/create.blade.php
resources/views/backoffice/scoring/rule-sets/edit.blade.php
resources/views/backoffice/scoring/rule-sets/show.blade.php

resources/views/backoffice/scoring/criteria/index.blade.php
resources/views/backoffice/scoring/criteria/create.blade.php
resources/views/backoffice/scoring/criteria/edit.blade.php

resources/views/backoffice/scoring/rules/index.blade.php
resources/views/backoffice/scoring/rules/create.blade.php
resources/views/backoffice/scoring/rules/edit.blade.php

resources/views/backoffice/scoring/tie-breakers/index.blade.php
resources/views/backoffice/scoring/tie-breakers/create.blade.php
resources/views/backoffice/scoring/tie-breakers/edit.blade.php

resources/views/backoffice/scoring/runs/index.blade.php
resources/views/backoffice/scoring/runs/create.blade.php
resources/views/backoffice/scoring/runs/show.blade.php

resources/views/backoffice/scoring/application-scores/index.blade.php
resources/views/backoffice/scoring/application-scores/show.blade.php
resources/views/backoffice/scoring/application-scores/manual-review.blade.php

resources/views/backoffice/scoring/ranking-snapshots/index.blade.php
resources/views/backoffice/scoring/ranking-snapshots/show.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 19. UX obrigatória no backoffice

## Matriz de classificação

A página de detalhe da matriz deve mostrar:

```text
Nome
Programa
Concurso
Estado
Período de vigência
Número de critérios
Número de regras de desempate
Últimas execuções
Ações disponíveis
```

## Critérios

Listagem deve mostrar:

```text
Código
Nome
Categoria
Tipo de cálculo
Pontos
Peso
Pontuação máxima
Requer análise manual
Estado
Ordem
Ações
```

## Execução de classificação

Antes de executar, mostrar resumo:

```text
Programa/concurso selecionado
Matriz aplicável
Número de candidaturas elegíveis
Número de candidaturas excluídas por falta de elegibilidade
Critérios automáticos
Critérios manuais
Regras de desempate
Aviso de auditoria
```

Copy obrigatório:

```text
A execução da classificação irá calcular pontuações com base nos dados existentes no sistema. Confirme que as candidaturas elegíveis e a matriz de classificação estão corretas antes de prosseguir.
```

## Detalhe de pontuação

Mostrar:

```text
Número da candidatura
Candidato
Concurso
Estado da candidatura
Estado de elegibilidade
Pontuação automática
Pontuação manual
Pontuação total
Posição no ranking
Detalhe por critério
Critérios pendentes de análise manual
Histórico
```

## Ranking interno

Mostrar:

```text
Posição
Número da candidatura
Candidato
Pontuação total
Desempates aplicados
Estado
Ações
```

Não mostrar ranking ao público nesta sprint.

---

# 20. Cálculo de pontuação

## Regras gerais

```text
Só candidaturas elegíveis entram na classificação, por defeito.
Critérios inativos são ignorados.
Critérios manuais ficam pendentes.
Critérios sem dados suficientes ficam missing_data.
Critérios exclusionary podem excluir do ranking.
Pontuação total deve ser recalculável.
Cada execução deve preservar os resultados.
```

## Fórmula base

```text
automatic_score = soma dos pontos automáticos aplicáveis
manual_score = soma dos pontos manuais aprovados
total_score = automatic_score + manual_score
```

Se houver `weight`, aplicar de forma documentada:

```text
points_awarded = base_points * weight
```

Evitar fórmulas implícitas sem documentação.

---

# 21. Regras de desempate

A ordenação principal é:

```text
total_score desc
```

Depois aplicar regras de desempate configuradas.

Se continuar empatado:

```text
Manter is_tied = true
Aplicar submitted_at asc como fallback apenas se configurado ou documentado
```

Não inventar desempates não configurados sem documentar.

---

# 22. Integração com elegibilidade

Se existir `EligibilityCheck`:

```text
Usar o último check válido da candidatura
Classificar apenas candidaturas com result = eligible
Mostrar candidaturas requires_review como pendentes ou excluídas conforme configuração
Não alterar resultado de elegibilidade
Não reimplementar motor de elegibilidade
```

Se não existir `EligibilityCheck`:

```text
Permitir configuração de matriz
Impedir execução formal completa
Documentar dependência da Sprint 7
```

---

# 23. Integração com candidaturas

Usar apenas candidaturas em estados adequados.

Estados recomendados para classificar:

```text
submitted
under_review
eligible
```

Não classificar:

```text
draft
withdrawn
cancelled
expired
excluded
```

Se os estados forem diferentes no projeto, mapear de forma documentada.

---

# 24. Auditoria

Se existir auditoria, auditar:

```text
Criação de matriz de classificação
Atualização de matriz
Ativação de matriz
Arquivo de matriz
Duplicação de matriz
Criação de critério
Atualização de critério
Ativação/inativação de critério
Criação de regra de pontuação
Atualização de regra de pontuação
Criação de regra de desempate
Atualização de regra de desempate
Execução de classificação
Reexecução de classificação
Pontuação manual
Bloqueio de pontuação
Geração de snapshot de ranking
Bloqueio de snapshot
Exportação de ranking, se existir
```

Não criar auditoria paralela.

Se auditoria não existir, documentar pendência.

---

# 25. RGPD e segurança

Regras obrigatórias:

```text
Classificação contém dados sensíveis.
Ranking interno não é público.
Candidato não consulta pontuação nesta sprint.
Candidato não consulta posição no ranking nesta sprint.
Backoffice exige permissões.
Júri só vê dados conforme autorização.
Auditor não altera dados.
Não expor ranking em rotas públicas.
Não expor dados pessoais em exportações sem permissão.
Não guardar dados sensíveis excessivos em raw_value.
Não guardar documentos nos snapshots de ranking.
Não guardar paths internos.
Não permitir mass assignment de pontuação.
Não permitir alteração direta de total_score fora dos services.
Não permitir edição de pontuações bloqueadas.
```

---

# 26. Seeders e factories

Criar factories:

```text
ScoringRuleSetFactory
ScoringCriterionFactory
ScoringRuleFactory
TieBreakerRuleFactory
ScoringRunFactory
ApplicationScoreFactory
ApplicationScoreDetailFactory
RankingSnapshotFactory
RankingEntryFactory
```

Criar seeders:

```text
ScoringBaseCriteriaSeeder
ScoringDemoRuleSetSeeder
```

Critérios demo permitidos:

```text
Rendimento per capita
Número de dependentes
Situação habitacional precária
Residência no município
Trabalho no município
Membro com deficiência/incapacidade
Risco de despejo declarado
Situação de sem-abrigo
Critério manual de apreciação técnica
```

Usar apenas dados fictícios.

Não usar dados pessoais reais.

---

# 27. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text
guest_cannot_access_backoffice_scoring
candidate_cannot_access_backoffice_scoring
admin_can_access_scoring_rule_sets
technician_can_access_scoring_if_authorized
jury_member_can_view_scores_if_authorized
auditor_can_view_scores_without_editing
candidate_cannot_view_internal_ranking
```

## Matriz

```text
admin_can_create_scoring_rule_set
admin_can_update_scoring_rule_set
admin_can_activate_scoring_rule_set
admin_can_archive_scoring_rule_set
scoring_rule_set_requires_program_or_contest
contest_scoring_rule_set_takes_precedence_over_program_rule_set
draft_scoring_rule_set_is_not_used
archived_scoring_rule_set_is_not_used
```

## Critérios e regras

```text
admin_can_create_scoring_criterion
admin_can_update_scoring_criterion
criterion_code_must_be_unique_within_rule_set
inactive_criterion_is_not_evaluated
admin_can_create_scoring_rule_for_criterion
scoring_rule_points_must_be_non_negative
manual_criterion_requires_manual_review
```

## Execução

```text
scoring_run_can_be_created_for_contest
scoring_run_uses_active_rule_set
scoring_run_scores_only_eligible_applications_when_eligibility_exists
scoring_run_excludes_draft_applications
scoring_run_creates_application_score_for_each_scored_application
scoring_run_creates_score_details
scoring_run_calculates_total_score
scoring_run_marks_manual_criteria_as_requires_manual_review
scoring_run_creates_ranking_snapshot
```

## Pontuação

```text
fixed_points_criterion_awards_points
boolean_criterion_awards_points_when_true
range_criterion_awards_points_inside_range
threshold_criterion_awards_points_when_condition_matches
missing_data_criterion_creates_missing_data_result
manual_score_cannot_exceed_max_points
manual_score_updates_total_score
locked_score_cannot_be_edited
```

## Ranking e desempates

```text
ranking_orders_by_total_score_desc
tie_breaker_rule_is_applied_when_scores_are_equal
ranking_marks_persistent_ties
ranking_snapshot_preserves_positions
ranking_entry_stores_tie_breaker_values
```

## Segurança

```text
candidate_cannot_mass_assign_total_score
candidate_cannot_create_scoring_run
candidate_cannot_update_manual_score
ranking_is_not_publicly_accessible
technical_messages_are_not_visible_to_candidate
```

## Auditoria, se existir

```text
creating_scoring_rule_set_generates_audit_log
running_scoring_generates_audit_log
updating_manual_score_generates_audit_log
locking_ranking_snapshot_generates_audit_log
```

Se alguma dependência não existir, documentar teste como pendente em vez de criar teste quebrado.

---

# 28. Comandos de validação

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

# 29. Atualização documental obrigatória

Atualizar, se existirem:

```text
docs/backlog/sprint-10-matriz-classificacao-ranking.md
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
Pendências para Sprint 11 e Sprint 12
```

---

# 30. Critérios de aceitação

A Sprint 10 está concluída quando:

```text
Existem matrizes de classificação configuráveis
Matrizes podem ser associadas a programa
Matrizes podem ser associadas a concurso
Matriz de concurso prevalece sobre matriz de programa
Apenas matrizes ativas são usadas
Existem critérios de pontuação configuráveis
Existem regras de pontuação configuráveis
Existem regras de desempate configuráveis
O sistema executa classificação de candidaturas elegíveis
O sistema exclui candidaturas não elegíveis quando existe elegibilidade
O sistema cria ScoringRun
O sistema cria ApplicationScore
O sistema cria ApplicationScoreDetail
O sistema calcula pontuação automática
O sistema permite pontuação manual quando autorizada
O sistema impede pontuação manual acima do máximo
O sistema calcula pontuação total
O sistema aplica regras de desempate
O sistema gera ranking interno
O sistema gera RankingSnapshot
O sistema preserva histórico de execuções
O sistema bloqueia edição de pontuações bloqueadas
O candidato não vê ranking interno
O candidato não vê pontuação nesta sprint
Backoffice exige permissões
Auditoria é usada se existir
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foram implementadas listas provisórias
Não foram implementadas reclamações
Não foi implementada atribuição
Não foi implementado contrato
```

---

# 31. Resposta final esperada do Codex

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
19. Confirmação de que não foram implementadas funcionalidades fora de âmbito
20. Recomendação objetiva para avançar ou não para Sprint 11
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 32. Definition of Done

A Sprint 10 só está concluída quando a plataforma tiver uma matriz de classificação configurável, executável, auditável e capaz de produzir um ranking interno de candidaturas elegíveis, preservando histórico, pontuação por critério e regras de desempate.

O resultado deve permitir que a Sprint 11 use o ranking interno para gerar listas provisórias, reclamações, audiência de interessados e listas definitivas.

---

# 33. Execução realizada em 11/06/2026

## Implementado

- Matriz de classificação configurável por programa ou concurso.
- Critérios de pontuação configuráveis, incluindo critérios automáticos, manuais, exclusionary, pesos e limites.
- Regras de pontuação por critério.
- Regras de desempate por prioridade.
- Execução interna de classificação (`ScoringRun`) para candidaturas admitidas administrativamente.
- Filtro conservador pelo último `EligibilityCheck` com resultado `eligible`.
- Pontuação automática, avaliação manual autorizada e bloqueio de pontuação.
- Ranking interno com `rank_position`, identificação de empates e `RankingSnapshot`.
- Backoffice Blade/Tailwind para matrizes, critérios, regras, desempates, execuções, pontuações e snapshots.
- Mensagem genérica na área da candidatura do candidato, sem pontuação ou ranking.
- Auditoria integrada com `AuditLogger`.
- Factories, seeders demo fictícios e testes de feature.

## Tabelas criadas

- `scoring_rule_sets`
- `scoring_criteria`
- `scoring_rules`
- `tie_breaker_rules`
- `scoring_runs`
- `application_scores`
- `application_score_details`
- `ranking_snapshots`
- `ranking_entries`

## Models criados ou alterados

- Criados: `ScoringRuleSet`, `ScoringCriterion`, `ScoringRule`, `TieBreakerRule`, `ScoringRun`, `ApplicationScore`, `ApplicationScoreDetail`, `RankingSnapshot`, `RankingEntry`.
- Alterados: `Program`, `Contest`, `Application`.

## Enums criados

- `ScoringRuleSetStatus`
- `ScoringCalculationType`
- `ScoringOperator`
- `ScoringRunStatus`
- `ApplicationScoreStatus`
- `ScoreCriterionResult`
- `RankingSnapshotStatus`
- `RankingEntryStatus`
- `TieBreakerDirection`

## Controllers criados

- `Backoffice\ScoringRuleSetController`
- `Backoffice\ScoringCriterionController`
- `Backoffice\ScoringRuleController`
- `Backoffice\TieBreakerRuleController`
- `Backoffice\ScoringRunController`
- `Backoffice\ApplicationScoreController`
- `Backoffice\RankingSnapshotController`

## Requests criados

- `StoreScoringRuleSetRequest`, `UpdateScoringRuleSetRequest`
- `StoreScoringCriterionRequest`, `UpdateScoringCriterionRequest`
- `StoreScoringRuleRequest`, `UpdateScoringRuleRequest`
- `StoreTieBreakerRuleRequest`, `UpdateTieBreakerRuleRequest`
- `RunScoringRequest`, `UpdateManualScoreRequest`, `LockScoringRunRequest`

## Policies criadas

- `ScoringRuleSetPolicy`
- `ScoringCriterionPolicy`
- `ScoringRulePolicy`
- `TieBreakerRulePolicy`
- `ScoringRunPolicy`
- `ApplicationScorePolicy`
- `RankingSnapshotPolicy`
- `RankingEntryPolicy`

## Services criados

- `ScoringRuleSetResolver`
- `ScoringEngine`
- `ScoringCriterionEvaluator`
- `ApplicationScoreService`
- `ManualScoreService`
- `RankingService`
- `TieBreakerService`
- `RankingSnapshotService`
- `ScoringDataProvider`
- `ScoringMessageService`

## Rotas criadas

Todas as rotas foram criadas sob `/backoffice/scoring`, sem rotas públicas de ranking:

- matrizes;
- critérios;
- regras;
- desempates;
- execuções;
- pontuações;
- snapshots internos.

## Seeders e factories

- Factories criadas para todas as entidades de scoring/ranking.
- Seeders criados: `ScoringBaseCriteriaSeeder`, `ScoringDemoRuleSetSeeder`.
- `DatabaseSeeder` atualizado para incluir os seeders de scoring.
- Critérios seed/demo são fictícios e não têm valor jurídico.

## Testes

- Criado `tests/Feature/Sprint10ScoringRankingTest.php`.
- Cobertura: acesso, criação/ativação/arquivo de matriz, precedência concurso/programa, execução, filtro por admissão/elegibilidade, pontuação manual, bloqueio, ranking, desempate e ausência de ranking público.

## Comandos executados

- `php artisan migrate` — passou.
- `php artisan db:seed --class=SystemAccessSeeder` — passou.
- `php artisan route:list` — passou, 299 rotas.
- `php artisan test` — passou com 106 testes/597 asserções.
- `npm run build` — passou.
- `ls vendor/bin | rg 'phpstan|psalm'` — não encontrou PHPStan/Psalm.
- `./vendor/bin/pint` — passou e formatou ficheiros.
- `php artisan test` após Pint — passou com 106 testes/597 asserções.

## Problemas encontrados

- O ficheiro documental esperado `docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md` não existe; existe a variante `docs/backlog/sprint-6-gestao-documental-avancada.md`.
- `phpstan` e `psalm` não estão instalados em `vendor/bin`.
- Critérios demo são apenas operacionais/fictícios e exigem validação jurídica antes de produção.

## Pendências para Sprint 11

- Criar listas provisórias a partir de `RankingSnapshot`.
- Definir identificador público/pseudonimizado.
- Validar campos publicáveis, formato de lista, prazo de reclamação e audiência.
- Garantir que publicação cria snapshot/lista imutável e auditável.
- Expor resultados ao candidato apenas no fluxo próprio de listas/reclamações.

## Pendências para Sprint 12

- Usar ranking definitivo apenas após Sprint 11.
- Definir compatibilidade entre agregado, tipologia e habitação.
- Implementar atribuição/sorteio sem alterar ranking histórico.

## Fora de âmbito confirmado

- Não foram implementadas listas provisórias.
- Não foram implementadas reclamações ou audiência.
- Não foi implementada lista definitiva.
- Não foi implementada atribuição.
- Não foram implementados contratos, pagamentos, manutenção ou notificações reais.
- Não foram criadas integrações externas.

Fim da Sprint 10.
