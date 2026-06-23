# MASTER PROMPT — EXECUÇÃO DA SPRINT 10: MATRIZ DE CLASSIFICAÇÃO E RANKING

Atua como arquiteto sénior Laravel, tech lead e product engineer.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 10 — Matriz de Classificação e Ranking
```

Esta sprint pertence à prioridade funcional:

```text
4. Classificação
```

A Sprint 10 deve ser executada apenas depois da Sprint 9 estar funcionalmente validada, porque a classificação só deve incidir sobre candidaturas administrativamente admitidas para classificação.

---

# 1. Regra principal

Executa apenas a Sprint 10.

Não avances para Sprint 11, Sprint 12, Sprint 13 ou qualquer sprint futura sem validação explícita.

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
docs/backlog/sprint-10-matriz-classificacao-ranking.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 10.

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
Sistema documental, se existir
Sistema de elegibilidade, se existir
Sistema de workflow administrativo, se existir
Modelo User
Modelo Program
Modelo Contest
Modelo Application
Modelo AdministrativeProcess
Modelo AdministrativeDecision
Modelo EligibilityCheck
Modelo EligibilityCheckResult
Modelo ApplicationSnapshot
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
TieBreakerRule
ScoringRun
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

Não introduzir passwords reais, tokens, APP_KEY ou credenciais.

---

# 5. Dependências obrigatórias

Esta sprint depende funcionalmente de:

```text
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
```

Depende preferencialmente de:

```text
Sprint 7 — Motor de Elegibilidade
```

## Dependência da Sprint 8

Se `Application` não existir, interrompe a implementação funcional e informa:

```text
A Sprint 10 depende da Sprint 8 — Candidaturas e Submissão Formal.
```

Não criar `Application` nesta sprint.

## Dependência da Sprint 9

Se `AdministrativeProcess` não existir, não executar classificação formal.

A Sprint 10 deve classificar apenas candidaturas cujo processo administrativo esteja em estado:

```text
admitted_for_scoring
```

ou equivalente documentado:

```text
eligible_for_classification
```

Se a Sprint 9 ainda não existir, podes implementar a configuração da matriz, critérios e serviços base, mas deves impedir a execução formal da classificação e documentar a pendência.

Não criar workflow administrativo simplificado dentro desta sprint.

## Dependência da Sprint 7

Se `EligibilityCheck` existir, a classificação deve considerar apenas candidaturas com último resultado elegível, salvo regra administrativa diferente.

Se `EligibilityCheck` não existir, usar o estado administrativo `admitted_for_scoring` como critério mínimo, documentando que a validação automática de elegibilidade está pendente.

Não criar motor de elegibilidade simplificado dentro desta sprint.

---

# 6. Objetivo da implementação

Implementar a Matriz de Classificação e Ranking interno da plataforma municipal de Arrendamento Acessível.

A plataforma deve permitir:

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
Executar classificação apenas sobre candidaturas admitidas para classificação
Registar execução da classificação
Calcular pontuação automática
Permitir pontuação manual autorizada
Registar detalhe da pontuação por critério
Calcular pontuação total
Aplicar desempates
Gerar ranking interno
Gerar snapshot interno de ranking
Preservar histórico de execuções
Impedir alterações não auditadas
Proteger dados sensíveis
Preparar Sprint 11
```

---

# 7. Separação conceptual obrigatória

Separar claramente:

```text
Elegibilidade = verifica se a candidatura cumpre condições mínimas.
Workflow administrativo = admite ou não admite a candidatura para classificação.
Classificação = atribui pontuação e ordena candidaturas admitidas.
Lista provisória = publicação formal de resultados.
Atribuição = decisão sobre habitação atribuída.
```

Nesta sprint, não implementar:

```text
Publicação de listas
Reclamações
Audiência de interessados
Listas definitivas
Atribuição
Contrato
```

A Sprint 10 deve produzir ranking interno, não lista pública.

---

# 8. Âmbito incluído

Implementar:

```text
ScoringRuleSet
ScoringCriterion
ScoringRule
TieBreakerRule
ScoringRun
ApplicationScore
ApplicationScoreDetail
RankingSnapshot
RankingEntry

Enums de classificação
Services de classificação
Backoffice de matrizes
Backoffice de critérios
Backoffice de regras
Backoffice de desempates
Execução de classificação
Pontuação automática
Pontuação manual autorizada
Ranking interno
Snapshots de ranking
Histórico de execuções
Policies
Form Requests
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
Notificações reais por SMS
Notificações reais por email, salvo se já existir módulo seguro
Integração com Autoridade Tributária
Integração com Segurança Social
Integração com Autenticação.GOV
Assinatura digital
OCR
```

Podem ser criados pontos de integração para funcionalidades futuras, mas não implementar essas fases.

---

# 10. Fluxo funcional obrigatório

O fluxo da Sprint 10 deve ser:

```text
Candidatura submetida
→ Processo administrativo admitido para classificação
→ Matriz ativa resolvida por concurso/programa
→ Execução de classificação criada
→ Critérios ativos avaliados
→ Pontuação por critério registada
→ Pontuação total calculada
→ Critérios manuais assinalados
→ Regras de desempate aplicadas
→ Ranking interno gerado
→ Snapshot de ranking criado
→ Resultado preparado para Sprint 11
```

Não classificar candidaturas em estados:

```text
draft
withdrawn
cancelled
expired
not_admitted
archived
```

Não classificar candidaturas sem processo administrativo admitido para classificação.

---

# 11. Estados obrigatórios

## ScoringRuleSetStatus

```text
draft
active
archived
```

## ScoringRunStatus

```text
draft
running
completed
failed
cancelled
locked
```

## ApplicationScoreStatus

```text
pending
calculated
requires_manual_review
manual_review_completed
excluded_from_scoring
locked
```

## ScoreCriterionResult

```text
applied
not_applicable
requires_manual_review
missing_data
failed
manual
```

## RankingSnapshotStatus

```text
draft
internal
locked
archived
```

## RankingEntryStatus

```text
ranked
tied
excluded
requires_manual_review
```

---

# 12. Modelo de dados a implementar

## 12.1 ScoringRuleSet

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

## 12.2 ScoringCriterion

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

Categorias recomendadas:

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

Tipos de cálculo:

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

Regras:

```text
code deve ser único dentro do scoring_rule_set_id.
Critérios inativos não são avaliados.
Critérios manuais devem ficar pendentes até avaliação técnica.
Critérios exclusionary podem excluir candidatura da classificação, mas não devem alterar elegibilidade automaticamente.
```

---

## 12.3 ScoringRule

Criar entidade:

```text
ScoringRule
```

Tabela:

```text
scoring_rules
```

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

Objetivo:

```text
Permitir escalões e regras internas de pontuação dentro de cada critério.
```

---

## 12.4 TieBreakerRule

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

Valores de `direction`:

```text
asc
desc
```

Regras:

```text
Desempates são aplicados apenas quando há igualdade de pontuação total.
A ordem é definida por priority_order.
Critérios de desempate devem ser auditáveis.
```

---

## 12.5 ScoringRun

Criar entidade:

```text
ScoringRun
```

Tabela:

```text
scoring_runs
```

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

## 12.6 ApplicationScore

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
total_score = automatic_score + manual_score, salvo regra documentada diferente.
rank_position é calculado após desempates.
tie_breaker_values pode ser JSON.
Usar soft deletes.
```

---

## 12.7 ApplicationScoreDetail

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

## 12.8 RankingSnapshot

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

Regras:

```text
Nesta sprint, published_at deve permanecer nulo.
Não publicar ranking.
Não expor ao candidato.
```

---

## 12.9 RankingEntry

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

---

# 13. Enums a criar

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

# 14. Relações obrigatórias

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
scope admittedForScoring, usando AdministrativeProcess
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

# 15. Services obrigatórios

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

## ScoringRuleSetResolver

Responsável por:

```text
Resolver matriz aplicável a programa
Resolver matriz aplicável a concurso
Dar prioridade à matriz específica do concurso
Usar apenas matrizes ativas
Ignorar draft e archived
Gerar erro claro se não existir matriz aplicável
```

## ScoringEngine

Responsável por:

```text
Receber contest_id ou program_id
Resolver matriz aplicável
Obter candidaturas admitidas para classificação
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

## ScoringCriterionEvaluator

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

## ApplicationScoreService

Responsável por:

```text
Criar pontuação da candidatura
Atualizar total_score
Recalcular candidatura específica
Bloquear pontuação
Consultar detalhe de pontuação
Impedir alteração indevida após lock
```

## ManualScoreService

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

## RankingService

Responsável por:

```text
Ordenar candidaturas por total_score
Aplicar desempates
Atribuir rank_position
Marcar empates
Excluir candidaturas marcadas como excluded_from_ranking
Gerar RankingEntry
```

## TieBreakerService

Responsável por:

```text
Resolver valores de desempate
Aplicar regras por priority_order
Guardar tie_breaker_values
Identificar empates persistentes
```

## RankingSnapshotService

Responsável por:

```text
Criar snapshot interno
Incrementar snapshot_number
Guardar ranking_entries
Bloquear snapshot quando necessário
Comparar snapshot anterior
Preparar dados para Sprint 11
```

## ScoringDataProvider

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
Estado administrativo
```

Reutilizar services existentes sempre que possível.

Não duplicar lógica de rendimentos, documentos, elegibilidade ou workflow administrativo se já existir.

## ScoringMessageService

Responsável por:

```text
Gerar mensagens simples para backoffice
Gerar mensagens técnicas
Evitar exposição de dados sensíveis
Usar mensagens configuradas nos critérios
Separar mensagens internas de mensagens futuras ao candidato
```

---

# 16. Controllers obrigatórios

Criar em namespace:

```text
App\Http\Controllers\Backoffice
```

Controllers:

```text
Backoffice\ScoringRuleSetController
Backoffice\ScoringCriterionController
Backoffice\ScoringRuleController
Backoffice\TieBreakerRuleController
Backoffice\ScoringRunController
Backoffice\ApplicationScoreController
Backoffice\RankingSnapshotController
```

Não criar controller público de ranking.

Não criar páginas públicas de resultados.

---

# 17. Form Requests obrigatórios

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

# 18. Policies obrigatórias

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

# 19. Rotas obrigatórias

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

# 20. Views / páginas obrigatórias

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

# 21. UX obrigatória no backoffice

## Matriz de classificação

A página de detalhe deve mostrar:

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
Número de candidaturas admitidas para classificação
Número de candidaturas excluídas
Critérios automáticos
Critérios manuais
Regras de desempate
Aviso de auditoria
```

Copy obrigatório:

```text
A execução da classificação irá calcular pontuações com base nos dados existentes no sistema. Confirme que as candidaturas admitidas para classificação e a matriz de classificação estão corretas antes de prosseguir.
```

## Detalhe de pontuação

Mostrar:

```text
Número da candidatura
Candidato
Concurso
Estado da candidatura
Estado administrativo
Estado de elegibilidade, se existir
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

# 22. Cálculo de pontuação

## Regras gerais

```text
Só candidaturas admitidas para classificação entram na execução.
Se EligibilityCheck existir, por defeito só entram candidaturas elegíveis.
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

# 23. Regras de desempate

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

# 24. Integração com workflow administrativo

A Sprint 10 deve usar o estado final da Sprint 9.

Apenas classificar candidaturas com processo administrativo em:

```text
admitted_for_scoring
```

Implementar ou reutilizar:

```text
AdministrativeProcess::admittedForScoring()
Application::admittedForScoring()
```

Não alterar estados administrativos nesta sprint.

Não criar decisões administrativas nesta sprint.

---

# 25. Integração com elegibilidade

Se existir `EligibilityCheck`:

```text
Usar o último check válido da candidatura
Classificar apenas candidaturas com result = eligible, salvo configuração documentada
Mostrar candidaturas requires_review como pendentes ou excluídas conforme configuração
Não alterar resultado de elegibilidade
Não reimplementar motor de elegibilidade
```

Se não existir `EligibilityCheck`:

```text
Permitir configuração da matriz
Executar apenas se a regra de negócio permitir usar admitted_for_scoring como filtro suficiente
Documentar dependência da Sprint 7
```

---

# 26. Integração com candidaturas

Usar candidaturas em estados compatíveis.

Estados recomendados para classificar:

```text
submitted
under_review
eligible
admitted_for_scoring
```

Não classificar:

```text
draft
withdrawn
cancelled
expired
excluded
not_admitted
```

Se os estados forem diferentes no projeto, mapear e documentar.

---

# 27. Auditoria

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

# 28. RGPD e segurança

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

# 29. Seeders e factories

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

# 30. Testes obrigatórios

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
scoring_run_scores_only_applications_admitted_for_scoring
scoring_run_scores_only_eligible_applications_when_eligibility_exists
scoring_run_excludes_draft_applications
scoring_run_excludes_not_admitted_applications
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

# 33. Critérios de aceitação

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
O sistema executa classificação apenas de candidaturas admitidas para classificação
O sistema exclui candidaturas não admitidas
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
19. Confirmação de que não foram implementadas funcionalidades fora de âmbito
20. Recomendação objetiva para avançar ou não para Sprint 11
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 35. Execução imediata

Executa agora apenas:

```text
Sprint 10 — Matriz de Classificação e Ranking
```

Usa como referência principal:

```text
docs/backlog/sprint-10-matriz-classificacao-ranking.md
```

Fim da master prompt da Sprint 10.
