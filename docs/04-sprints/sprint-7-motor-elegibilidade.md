# Sprint 7 — Motor de Elegibilidade

## Prioridade de desenvolvimento

Esta sprint pertence à prioridade funcional:

```text
2. Elegibilidade
```

A Sprint 7 implementa o motor de elegibilidade da plataforma municipal de Arrendamento Acessível.

O objetivo é permitir que a plataforma avalie, de forma estruturada, auditável e explicável, se um candidato reúne as condições mínimas para poder avançar num determinado programa ou concurso.

Esta sprint deve transformar os dados recolhidos nas sprints anteriores em verificações objetivas de acesso.

---

# Dependências funcionais

Esta sprint depende preferencialmente de:

```text
Sprint 3 — Portal Público e Programas
Sprint 4 — Registo de Adesão e Área Pessoal
Sprint 5 — Agregado Familiar, Rendimentos e Situação Habitacional
Sprint 6 — Gestão Documental Avançada
```

Esta sprint prepara ou integra com:

```text
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 10 — Matriz de Classificação e Ranking
Sprint 11 — Listas Provisórias, Reclamações e Audiência
```

Se a Sprint 8 ainda não estiver implementada, esta sprint deve criar o motor de elegibilidade e permitir pré-avaliações com base no Registo de Adesão, Agregado, Rendimentos, Situação Habitacional e Documentos.

Se a Sprint 8 já estiver implementada, esta sprint deve integrar a elegibilidade diretamente com as candidaturas formais.

---

# Objetivo da Sprint

Implementar um motor de elegibilidade configurável, transparente e auditável, capaz de:

- Definir regras de elegibilidade por programa;
- Definir regras de elegibilidade por concurso;
- Executar pré-verificação para candidatos;
- Executar verificação formal para candidaturas;
- Avaliar requisitos mínimos;
- Avaliar impedimentos;
- Avaliar limites de rendimento;
- Avaliar composição do agregado;
- Avaliar adequação tipológica;
- Avaliar residência ou atividade profissional no município;
- Avaliar situação habitacional;
- Avaliar documentação mínima;
- Registar resultado global;
- Registar resultado por critério;
- Explicar os motivos de elegibilidade ou não elegibilidade;
- Guardar histórico de verificações;
- Permitir reavaliação quando os dados mudam;
- Disponibilizar resultado ao candidato em linguagem simples;
- Disponibilizar detalhe técnico no backoffice;
- Preparar integração com classificação e ranking.

Esta sprint não deve implementar classificação, pontuação competitiva, ranking, listas, atribuição ou contrato.

---

# Instrução operacional para Codex

Executa apenas esta Sprint 7.

Não avances para Sprint 8, Sprint 9, Sprint 10, Sprint 11 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash
git branch --show-current
```

Antes de alterar código, lê primeiro, se existirem:

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

docs/backlog/sprint-3-portal-publico-programas.md
docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/sprint-5-agregado-rendimentos-situacao-habitacional.md
docs/backlog/sprint-6-gestao-documental-avancada.md
docs/backlog/sprint-7-motor-elegibilidade.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md

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
Services existentes
Policies existentes
Controllers existentes
Views existentes
Tests existentes
Program
Contest
AdhesionRegistration
Household
HouseholdMember
IncomeRecord
IncomeSource
CurrentHousingSituation
DocumentSubmission
DocumentChecklistService, se existir
Application, se já existir
Auditoria, se existir
```

Não duplicar entidades existentes.

Se já existir algo semelhante a `ProgramRule`, `EligibilityRule`, `EligibilityCheck` ou `ApplicationEligibility`, reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

---

# Âmbito desta Sprint

## Incluído

Implementar:

```text
Motor de elegibilidade configurável
Regras de elegibilidade por programa
Regras de elegibilidade por concurso
Critérios de elegibilidade
Execução de pré-check do candidato
Execução de check formal da candidatura, se Application existir
Resultado global de elegibilidade
Resultado detalhado por critério
Motivos de falha
Motivos de alerta
Histórico de verificações
Snapshot dos dados avaliados
Backoffice de configuração das regras
Backoffice de consulta dos resultados
Área do candidato com resultado simplificado
Integração com documentos obrigatórios
Integração com rendimentos e agregado
Integração com situação habitacional
Services de avaliação
Policies
Form Requests
Seeders de critérios base
Factories
Testes mínimos
Atualização documental
```

## Fora de âmbito

Não implementar nesta sprint:

```text
Classificação competitiva
Pontuação social
Ranking
Ordenação de candidatos
Listas provisórias
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
Validação automática de documentos oficiais
OCR
Assinatura digital
```

Podem ser criados pontos de integração para estas funcionalidades futuras, mas não as implementar.

---

# Conceito funcional

A elegibilidade é a verificação das condições mínimas para que um candidato ou candidatura possa prosseguir num determinado programa ou concurso.

A elegibilidade responde à pergunta:

```text
O candidato cumpre as condições mínimas para ser admitido?
```

A classificação responde a outra pergunta, fora desta sprint:

```text
Entre os candidatos elegíveis, qual é a prioridade relativa?
```

Nesta sprint, o sistema deve separar claramente:

```text
Elegibilidade = admissibilidade / cumprimento mínimo
Classificação = pontuação / ordenação futura
```

Não misturar elegibilidade com ranking.

---

# Tipos de avaliação

Implementar dois modos de avaliação.

## 1. Pré-verificação do candidato

A pré-verificação pode ser executada antes da candidatura formal.

Baseia-se em:

```text
Registo de Adesão
Agregado
Rendimentos
Situação habitacional
Documentos submetidos
Programa
Concurso, se selecionado
```

Resultado apresentado ao candidato como orientação.

Copy obrigatório:

```text
Esta verificação é indicativa e baseia-se nos dados atualmente declarados. A decisão final depende da análise dos serviços municipais e das regras do programa ou concurso.
```

## 2. Verificação formal da candidatura

Se a entidade `Application` já existir, permitir verificar a elegibilidade da candidatura formal.

Baseia-se nos dados associados à candidatura e, se existirem, nos snapshots criados no momento da submissão.

Resultado apresentado no backoffice como avaliação técnica inicial.

Não transformar automaticamente o resultado em decisão final irreversível sem workflow administrativo futuro.

---

# Resultado global de elegibilidade

O motor deve devolver um dos seguintes resultados:

```text
eligible
ineligible
requires_review
insufficient_data
not_applicable
```

## eligible

Todos os critérios obrigatórios foram cumpridos.

## ineligible

Um ou mais critérios obrigatórios falharam.

## requires_review

Existem dados que exigem validação técnica ou decisão administrativa.

## insufficient_data

Faltam dados essenciais para avaliar.

## not_applicable

A regra não se aplica ao contexto avaliado.

---

# Tipos de critérios de elegibilidade

Criar suporte para os seguintes critérios:

```text
Idade mínima
Residência no município
Trabalho no município
Nacionalidade ou título de residência
Registo de adesão finalizado
Agregado familiar mínimo
Rendimentos declarados
Limite mínimo de rendimento
Limite máximo de rendimento
Taxa de esforço
Situação habitacional declarada
Não titularidade de imóvel habitacional adequado
Não usufruto de apoios incompatíveis
Não existência de dívida ou impedimento municipal, se configurado
Documentos obrigatórios submetidos
Documentos obrigatórios validados, se configurado
Adequação tipológica
Condição especial do agregado
Prazo de candidatura aberto
Candidatura duplicada
```

Nem todos têm de estar ativos por defeito.

Devem ser configuráveis por programa ou concurso.

---

# Modelo de dados

## 1. EligibilityRuleSet

Criar entidade:

```text
EligibilityRuleSet
```

Tabela:

```text
eligibility_rule_sets
```

## Objetivo

Agrupar regras de elegibilidade aplicáveis a um programa ou concurso.

## Campos mínimos

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

## Estados permitidos

```text
draft
active
archived
```

## Regras

- Pode existir regra global por programa.
- Pode existir regra específica por concurso.
- Regra de concurso prevalece sobre regra de programa quando aplicável.
- Apenas rule sets ativos devem ser usados para avaliação.
- `contest_id` opcional.
- `program_id` obrigatório se `contest_id` não existir.
- Usar soft deletes.

---

## 2. EligibilityCriterion

Criar entidade:

```text
EligibilityCriterion
```

Tabela:

```text
eligibility_criteria
```

## Objetivo

Definir cada critério de elegibilidade configurável.

## Campos mínimos

```text
id
eligibility_rule_set_id
code
name
description
category
target
operator
expected_value
minimum_value
maximum_value
unit
is_mandatory
requires_manual_review
failure_message
success_message
review_message
sort_order
is_active
created_at
updated_at
deleted_at
```

## Valores recomendados para `category`

```text
identity
residence
household
income
housing
documents
application
legal_impediments
typology
special_condition
other
```

## Valores recomendados para `target`

```text
adhesion_registration
household
household_member
income_records
current_housing_situation
documents
application
contest
program
calculated_value
manual
```

## Valores recomendados para `operator`

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
all_required_documents_submitted
all_required_documents_validated
custom
```

## Regras

- `code` deve ser único dentro do mesmo `eligibility_rule_set_id`.
- Critérios inativos não devem ser avaliados.
- Critérios obrigatórios que falham podem tornar o resultado global `ineligible`.
- Critérios com `requires_manual_review = true` podem tornar o resultado global `requires_review`.
- Não colocar lógica complexa no controller.

---

## 3. EligibilityCheck

Criar entidade:

```text
EligibilityCheck
```

Tabela:

```text
eligibility_checks
```

## Objetivo

Registar cada execução do motor de elegibilidade.

## Campos mínimos

```text
id
eligibility_rule_set_id
program_id
contest_id
application_id
adhesion_registration_id
user_id

check_type
status
result
summary
missing_data
warnings

executed_by
executed_at

created_at
updated_at
deleted_at
```

## Valores recomendados para `check_type`

```text
candidate_pre_check
application_formal_check
backoffice_manual_check
system_recheck
```

## Valores recomendados para `status`

```text
draft
completed
failed
cancelled
```

## Valores recomendados para `result`

```text
eligible
ineligible
requires_review
insufficient_data
not_applicable
```

## Regras

- Cada verificação deve guardar contexto.
- `application_id` pode ser nullable se for pré-check.
- `adhesion_registration_id` obrigatório quando existe registo.
- `missing_data` pode ser JSON.
- `warnings` pode ser JSON.
- Usar soft deletes.

---

## 4. EligibilityCheckResult

Criar entidade:

```text
EligibilityCheckResult
```

Tabela:

```text
eligibility_check_results
```

## Objetivo

Registar o resultado individual de cada critério avaliado.

## Campos mínimos

```text
id
eligibility_check_id
eligibility_criterion_id

code
name
category

result
actual_value
expected_value
operator
message
technical_message
requires_manual_review

created_at
updated_at
```

## Valores recomendados para `result`

```text
passed
failed
requires_review
insufficient_data
not_applicable
```

## Regras

- Cada critério avaliado gera um resultado.
- Guardar valor esperado e valor real sempre que seguro.
- Não guardar dados pessoais excessivos em `actual_value`.
- `technical_message` visível apenas no backoffice.
- `message` pode ser visível ao candidato, se apropriado.

---

## 5. EligibilitySnapshot

Criar entidade:

```text
EligibilitySnapshot
```

Tabela:

```text
eligibility_snapshots
```

## Objetivo

Guardar a fotografia dos dados usados na avaliação para garantir rastreabilidade.

## Campos mínimos

```text
id
eligibility_check_id
snapshot_type
data
created_at
updated_at
```

## Valores de `snapshot_type`

```text
adhesion_registration
household
household_members
income_records
current_housing_situation
documents
application
calculated_values
```

## Regras

- Guardar apenas dados necessários para justificar a avaliação.
- Evitar duplicação excessiva de dados sensíveis.
- Não guardar ficheiros.
- Não guardar paths internos.
- Proteger acesso por policy.

---

# Enums recomendados

Criar, se a versão do PHP permitir:

```text
App\Enums\EligibilityRuleSetStatus
App\Enums\EligibilityCheckType
App\Enums\EligibilityCheckStatus
App\Enums\EligibilityResult
App\Enums\EligibilityCriterionResult
App\Enums\EligibilityCriterionCategory
App\Enums\EligibilityOperator
```

## EligibilityRuleSetStatus

```text
draft
active
archived
```

## EligibilityCheckType

```text
candidate_pre_check
application_formal_check
backoffice_manual_check
system_recheck
```

## EligibilityCheckStatus

```text
draft
completed
failed
cancelled
```

## EligibilityResult

```text
eligible
ineligible
requires_review
insufficient_data
not_applicable
```

## EligibilityCriterionResult

```text
passed
failed
requires_review
insufficient_data
not_applicable
```

## EligibilityOperator

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
all_required_documents_submitted
all_required_documents_validated
custom
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# Relações

## Program

Adicionar:

```text
Program hasMany EligibilityRuleSet
Program hasMany EligibilityCheck
```

## Contest

Adicionar:

```text
Contest hasMany EligibilityRuleSet
Contest hasMany EligibilityCheck
```

## User

Adicionar:

```text
User hasMany EligibilityCheck
```

## AdhesionRegistration

Adicionar:

```text
AdhesionRegistration hasMany EligibilityCheck
```

## Application

Se existir:

```text
Application hasMany EligibilityCheck
Application hasOne latestEligibilityCheck
```

## EligibilityRuleSet

```text
EligibilityRuleSet belongsTo Program nullable
EligibilityRuleSet belongsTo Contest nullable
EligibilityRuleSet hasMany EligibilityCriterion
EligibilityRuleSet hasMany EligibilityCheck
EligibilityRuleSet belongsTo User as createdBy
EligibilityRuleSet belongsTo User as updatedBy
```

## EligibilityCriterion

```text
EligibilityCriterion belongsTo EligibilityRuleSet
EligibilityCriterion hasMany EligibilityCheckResult
```

## EligibilityCheck

```text
EligibilityCheck belongsTo EligibilityRuleSet
EligibilityCheck belongsTo Program nullable
EligibilityCheck belongsTo Contest nullable
EligibilityCheck belongsTo Application nullable
EligibilityCheck belongsTo AdhesionRegistration nullable
EligibilityCheck belongsTo User nullable
EligibilityCheck belongsTo User as executedBy
EligibilityCheck hasMany EligibilityCheckResult
EligibilityCheck hasMany EligibilitySnapshot
```

## EligibilityCheckResult

```text
EligibilityCheckResult belongsTo EligibilityCheck
EligibilityCheckResult belongsTo EligibilityCriterion
```

## EligibilitySnapshot

```text
EligibilitySnapshot belongsTo EligibilityCheck
```

---

# Critérios base recomendados

Criar seeders para critérios base reutilizáveis.

## Seeder recomendado

```text
EligibilityBaseCriteriaSeeder
```

Criar critérios base, mas não assumir todos como ativos em todos os concursos.

Critérios sugeridos:

```text
registration_is_registered
Registo de Adesão finalizado

candidate_is_adult
Candidato maior de idade

contest_is_open
Concurso aberto

has_household
Agregado familiar preenchido

has_applicant_member
Existe membro requerente

has_income_information
Rendimentos declarados ou ausência de rendimentos justificada

income_above_minimum
Rendimento acima do mínimo definido

income_below_maximum
Rendimento abaixo do máximo definido

has_current_housing_situation
Situação habitacional preenchida

resides_in_municipality
Residência no município

works_in_municipality
Trabalho no município

has_required_documents_submitted
Documentos obrigatórios submetidos

has_required_documents_validated
Documentos obrigatórios validados

no_duplicate_active_application
Sem candidatura ativa duplicada

typology_is_adequate
Tipologia adequada ao agregado

no_declared_property_impediment
Sem impedimento por propriedade habitacional declarada

no_incompatible_housing_support
Sem apoio habitacional incompatível declarado

requires_manual_review_for_special_conditions
Condições especiais exigem análise manual
```

---

# Configuração de regras por programa/concurso

## Backoffice

Criar área de configuração para:

```text
Eligibility Rule Sets
Eligibility Criteria
```

## Funcionalidades mínimas

Admin ou técnico autorizado deve poder:

```text
Criar conjunto de regras
Associar conjunto a programa
Associar conjunto a concurso
Ativar conjunto de regras
Arquivar conjunto de regras
Criar critério
Editar critério
Ativar/inativar critério
Definir se critério é obrigatório
Definir operador
Definir valores mínimos/máximos
Definir mensagens para candidato e técnico
Ordenar critérios
```

## Restrições

- Não permitir apagar rule set que já tenha verificações associadas.
- Permitir arquivar em vez de apagar.
- Não permitir alterar drasticamente critérios já usados sem documentar impacto.
- Se necessário, duplicar rule set para nova versão.

## Versionamento simples

Nesta sprint, não é obrigatório criar versionamento complexo.

Mas deve ser possível:

```text
Arquivar rule set antigo
Criar novo rule set ativo
Preservar checks antigos com rule set antigo
```

---

# Services

Criar services para evitar lógica pesada nos controllers.

## Services recomendados

```text
App\Services\Eligibility\EligibilityRuleSetResolver
App\Services\Eligibility\EligibilityEngine
App\Services\Eligibility\EligibilityCriteriaEvaluator
App\Services\Eligibility\EligibilityCheckService
App\Services\Eligibility\EligibilitySnapshotService
App\Services\Eligibility\EligibilityResultAggregator
App\Services\Eligibility\EligibilityDataProvider
App\Services\Eligibility\EligibilityMessageService
```

---

## EligibilityRuleSetResolver

Responsável por:

```text
Resolver o rule set aplicável a um programa
Resolver o rule set aplicável a um concurso
Dar prioridade a rule set específico do concurso
Usar rule set ativo
Ignorar rule sets draft ou archived
Lançar erro claro se não existir rule set aplicável
```

---

## EligibilityEngine

Responsável por:

```text
Executar avaliação completa
Receber contexto de avaliação
Percorrer critérios ativos
Chamar evaluator por critério
Criar EligibilityCheck
Criar EligibilityCheckResult
Criar snapshots
Agregação do resultado global
Guardar missing_data e warnings
Devolver DTO/array de resultado
```

Não colocar lógica diretamente em controllers.

---

## EligibilityCriteriaEvaluator

Responsável por avaliar critérios individuais.

Deve suportar, no mínimo:

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
all_required_documents_submitted
all_required_documents_validated
custom
```

## Avaliações específicas mínimas

Implementar avaliadores para:

```text
registration_is_registered
candidate_is_adult
contest_is_open
has_household
has_applicant_member
has_income_information
income_above_minimum
income_below_maximum
has_current_housing_situation
resides_in_municipality
works_in_municipality
has_required_documents_submitted
has_required_documents_validated
no_duplicate_active_application
```

Avaliadores complexos como tipologia adequada podem ser implementados de forma inicial e conservadora, ou marcados como `requires_review` se os dados necessários não existirem.

---

## EligibilityCheckService

Responsável por:

```text
Criar pré-check para candidato
Criar check formal para candidatura
Reexecutar check
Obter último check
Listar histórico
Garantir permissões de execução
```

---

## EligibilitySnapshotService

Responsável por:

```text
Capturar dados mínimos avaliados
Capturar dados de agregado
Capturar rendimentos agregados
Capturar situação habitacional
Capturar estado documental
Capturar candidatura, se existir
Guardar JSON seguro
```

---

## EligibilityResultAggregator

Responsável por calcular resultado global.

## Regras de agregação

```text
Se qualquer critério obrigatório falhar → ineligible
Se existir critério obrigatório com insufficient_data → insufficient_data
Se existir critério requires_review e nenhum obrigatório falhou → requires_review
Se todos os critérios obrigatórios passaram → eligible
Se nenhum critério aplicável existir → not_applicable
```

Caso haja mistura entre `failed` e `requires_review`, prevalece:

```text
ineligible
```

Caso haja mistura entre `insufficient_data` e `requires_review`, prevalece:

```text
insufficient_data
```

---

## EligibilityDataProvider

Responsável por fornecer dados calculados:

```text
Idade do candidato
Número de membros do agregado
Número de adultos
Número de menores
Número de dependentes
Rendimento mensal total
Rendimento anual total
Rendimento per capita
Taxa de esforço atual
Estado da situação habitacional
Documentos obrigatórios em falta
Documentos rejeitados
Documentos submetidos
Documentos validados
Existência de candidatura ativa duplicada
```

Não duplicar cálculos existentes na Sprint 5.

Reutilizar `IncomeService`, `RegistrationProgressService` ou equivalentes quando existirem.

---

## EligibilityMessageService

Responsável por:

```text
Gerar mensagens simples para candidato
Gerar mensagens técnicas para backoffice
Evitar exposição de dados sensíveis
Usar failure_message/success_message/review_message configurados
```

---

# Controllers

Criar controllers conforme a arquitetura existente.

## Backoffice

Namespace recomendado:

```text
App\Http\Controllers\Backoffice
```

Controllers:

```text
Backoffice\EligibilityRuleSetController
Backoffice\EligibilityCriterionController
Backoffice\EligibilityCheckController
```

### EligibilityRuleSetController

Ações:

```text
index
show
create
store
edit
update
archive
activate
duplicate
```

### EligibilityCriterionController

Ações:

```text
index
create
store
edit
update
destroy ou archive/inactivate
```

Preferir inativar em vez de apagar.

### EligibilityCheckController

Ações:

```text
index
show
runForApplication, se Application existir
rerun
```

---

## Área do candidato

Namespace recomendado:

```text
App\Http\Controllers\Candidate
```

Controller:

```text
Candidate\EligibilityController
```

Ações:

```text
index
preCheck
show
history
```

## Responsabilidades

### index

Mostrar explicação da elegibilidade e último resultado.

### preCheck

Executar pré-verificação para programa ou concurso selecionado.

### show

Mostrar detalhe simplificado do resultado.

### history

Mostrar histórico de verificações do próprio candidato.

---

# Form Requests

Criar Form Requests:

```text
StoreEligibilityRuleSetRequest
UpdateEligibilityRuleSetRequest
StoreEligibilityCriterionRequest
UpdateEligibilityCriterionRequest
RunCandidatePreCheckRequest
RunApplicationEligibilityCheckRequest
```

## StoreEligibilityRuleSetRequest

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

## StoreEligibilityCriterionRequest

Validações mínimas:

```text
eligibility_rule_set_id required|exists:eligibility_rule_sets,id
code required|string|max:100
name required|string|max:255
description nullable|string|max:3000
category required|string|max:100
target required|string|max:100
operator required|string|max:100
expected_value nullable
minimum_value nullable|numeric
maximum_value nullable|numeric
unit nullable|string|max:50
is_mandatory boolean
requires_manual_review boolean
failure_message nullable|string|max:1000
success_message nullable|string|max:1000
review_message nullable|string|max:1000
sort_order nullable|integer|min:0
is_active boolean
```

## RunCandidatePreCheckRequest

```text
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
```

Regra:

```text
Deve existir program_id ou contest_id.
```

## RunApplicationEligibilityCheckRequest

```text
application_id required|exists:applications,id
```

Se `applications` não existir, não criar esta request ou criá-la apenas quando a dependência existir.

---

# Policies e autorização

Criar policies:

```text
EligibilityRuleSetPolicy
EligibilityCriterionPolicy
EligibilityCheckPolicy
EligibilityCheckResultPolicy
EligibilitySnapshotPolicy
```

## Regras para candidato

- Candidato pode executar pré-check apenas sobre os seus próprios dados.
- Candidato pode consultar apenas os seus próprios checks.
- Candidato não pode ver mensagens técnicas internas.
- Candidato não pode alterar rule sets.
- Candidato não pode alterar critérios.
- Candidato não pode ver checks de outros candidatos.
- Candidato não pode executar check formal de candidatura de outro utilizador.

## Regras para técnico municipal

- Pode consultar rule sets e critérios.
- Pode criar/editar rule sets se tiver permissão.
- Pode executar check formal de candidatura, se Application existir.
- Pode consultar resultados técnicos.
- Não pode alterar classificação/ranking nesta sprint.

## Regras para júri

- Pode consultar elegibilidade de candidaturas submetidas, se a política permitir.
- Não pode configurar regras, salvo permissão explícita.
- Não pode alterar dados do candidato.

## Regras para auditor

- Pode consultar histórico e logs conforme permissões.
- Não pode executar alterações.
- Não pode modificar resultado.

## Regras para admin

- Pode gerir rule sets e critérios.
- Pode consultar e executar verificações.
- Pode arquivar e duplicar regras.

---

# Rotas

## Área do candidato

Preferência em português:

```text
GET /area-candidato/elegibilidade
POST /area-candidato/elegibilidade/pre-verificar
GET /area-candidato/elegibilidade/{eligibilityCheck}
GET /area-candidato/elegibilidade/historico
```

Nomes recomendados:

```text
candidate.eligibility.index
candidate.eligibility.pre-check
candidate.eligibility.show
candidate.eligibility.history
```

Se existir seleção por concurso:

```text
GET /area-candidato/elegibilidade/concursos/{contest}
POST /area-candidato/elegibilidade/concursos/{contest}/pre-verificar
```

---

## Backoffice

```text
GET /backoffice/eligibility/rule-sets
GET /backoffice/eligibility/rule-sets/create
POST /backoffice/eligibility/rule-sets
GET /backoffice/eligibility/rule-sets/{eligibilityRuleSet}
GET /backoffice/eligibility/rule-sets/{eligibilityRuleSet}/edit
PUT/PATCH /backoffice/eligibility/rule-sets/{eligibilityRuleSet}
POST /backoffice/eligibility/rule-sets/{eligibilityRuleSet}/activate
POST /backoffice/eligibility/rule-sets/{eligibilityRuleSet}/archive
POST /backoffice/eligibility/rule-sets/{eligibilityRuleSet}/duplicate

GET /backoffice/eligibility/rule-sets/{eligibilityRuleSet}/criteria
GET /backoffice/eligibility/rule-sets/{eligibilityRuleSet}/criteria/create
POST /backoffice/eligibility/rule-sets/{eligibilityRuleSet}/criteria
GET /backoffice/eligibility/criteria/{eligibilityCriterion}/edit
PUT/PATCH /backoffice/eligibility/criteria/{eligibilityCriterion}
POST /backoffice/eligibility/criteria/{eligibilityCriterion}/activate
POST /backoffice/eligibility/criteria/{eligibilityCriterion}/inactivate

GET /backoffice/eligibility/checks
GET /backoffice/eligibility/checks/{eligibilityCheck}
POST /backoffice/eligibility/applications/{application}/run
POST /backoffice/eligibility/checks/{eligibilityCheck}/rerun
```

Se `Application` ainda não existir, não criar rotas dependentes de application ou deixá-las condicionadas/documentadas.

Nomes recomendados:

```text
backoffice.eligibility.rule-sets.*
backoffice.eligibility.criteria.*
backoffice.eligibility.checks.*
```

---

# Views / páginas

Se o projeto usa Blade, criar:

## Área do candidato

```text
resources/views/candidate/eligibility/index.blade.php
resources/views/candidate/eligibility/show.blade.php
resources/views/candidate/eligibility/history.blade.php
```

## Backoffice

```text
resources/views/backoffice/eligibility/rule-sets/index.blade.php
resources/views/backoffice/eligibility/rule-sets/create.blade.php
resources/views/backoffice/eligibility/rule-sets/edit.blade.php
resources/views/backoffice/eligibility/rule-sets/show.blade.php

resources/views/backoffice/eligibility/criteria/index.blade.php
resources/views/backoffice/eligibility/criteria/create.blade.php
resources/views/backoffice/eligibility/criteria/edit.blade.php

resources/views/backoffice/eligibility/checks/index.blade.php
resources/views/backoffice/eligibility/checks/show.blade.php
```

Se o projeto usa Inertia/Vue/React, criar equivalentes.

---

# UX — Área do candidato

## Página de elegibilidade

Deve mostrar:

```text
Explicação simples da elegibilidade
Aviso de que a verificação é indicativa
Lista de programas/concursos disponíveis para pré-check
Última verificação realizada
Resultado global
Critérios cumpridos
Critérios em falta
Critérios que exigem análise
CTAs para corrigir dados
```

## Mensagens por resultado

### eligible

```text
Com base nos dados atualmente declarados, reúne as condições mínimas indicadas para este programa ou concurso. A confirmação final dependerá da análise dos serviços municipais.
```

### ineligible

```text
Com base nos dados atualmente declarados, existem condições mínimas que não se encontram cumpridas. Consulte os pontos assinalados e confirme se os seus dados estão corretos.
```

### insufficient_data

```text
Não existem ainda dados suficientes para avaliar a elegibilidade. Complete o seu registo, agregado, rendimentos, situação habitacional e documentos.
```

### requires_review

```text
Alguns elementos exigem análise pelos serviços municipais. O resultado apresentado é indicativo e poderá ser confirmado posteriormente.
```

### not_applicable

```text
Não foi possível aplicar regras de elegibilidade ao contexto selecionado.
```

## CTAs recomendados

```text
Completar Registo de Adesão
Atualizar Agregado
Atualizar Rendimentos
Atualizar Situação Habitacional
Consultar Documentos
Ver Concursos
```

---

# UX — Backoffice

## Rule Sets

Listagem deve mostrar:

```text
Nome
Programa
Concurso
Estado
N.º de critérios
Data de início/fim
Ações
```

## Critérios

Listagem deve mostrar:

```text
Código
Nome
Categoria
Operador
Valor esperado
Obrigatório
Requer análise manual
Estado
Ordem
Ações
```

## Resultado de check

Detalhe deve mostrar:

```text
Resultado global
Tipo de check
Programa
Concurso
Candidato
Candidatura, se existir
Data de execução
Executado por
Resumo
Dados em falta
Alertas
Tabela de critérios avaliados
Resultado por critério
Mensagem visível ao candidato
Mensagem técnica
Snapshots
```

Não expor dados excessivos em listagens.

---

# Integração com documentos

Se a Sprint 6 estiver implementada, o motor deve conseguir avaliar:

```text
Documentos obrigatórios em falta
Documentos submetidos
Documentos validados
Documentos rejeitados
Documentos expirados
```

Critérios mínimos:

```text
has_required_documents_submitted
has_required_documents_validated
```

## Regras

- Se o critério exigir documentos submetidos, aceitar estados:
    - submitted;
    - under_review;
    - validated.

- Se o critério exigir documentos validados, aceitar apenas:
    - validated.

- Estados que falham:
    - missing;
    - rejected;
    - expired;
    - cancelled.

A decisão sobre exigir apenas submissão ou validação deve ser configurável por critério.

---

# Integração com rendimentos

O motor deve calcular ou obter:

```text
Rendimento mensal total do agregado
Rendimento anual total do agregado
Rendimento mensal per capita
Rendimento anual per capita
Número de membros do agregado
Número de adultos
Número de menores
Número de dependentes
```

## Critérios mínimos de rendimento

```text
income_above_minimum
income_below_maximum
```

## Regras

- Se faltar informação de rendimentos, resultado do critério deve ser `insufficient_data`.
- Se houver membros adultos sem rendimento declarado nem declaração de ausência de rendimento, resultado deve ser `insufficient_data`.
- Não tomar decisões de fraude.
- Não criar integração externa com AT ou Segurança Social.

---

# Integração com situação habitacional

O motor deve avaliar, quando configurado:

```text
resides_in_municipality
works_in_municipality
current_housing_status
current_housing_condition
risk_of_eviction
homelessness
temporary_accommodation
accessibility_needs
high_rent_burden
```

Algumas condições podem ser informativas ou exigir análise manual, não necessariamente excluir.

---

# Adequação tipológica

Implementar estrutura inicial para adequação entre composição do agregado e tipologia.

## Modelo simples permitido

Criar serviço ou método:

```text
TypologyAdequacyService
```

Ou incluir no EligibilityDataProvider.

## Regras base

Se existirem housing units ou typologies associadas ao concurso, avaliar:

```text
Número de membros do agregado
Número de quartos/tipologia disponível
Regras mínimas configuradas
```

Se não existirem dados suficientes:

```text
requires_review
```

ou:

```text
not_applicable
```

Não implementar atribuição.

Não reservar habitações.

Não fazer ranking por tipologia.

---

# Integração com candidatura

Se `Application` já existir:

- Permitir executar check formal a partir do backoffice.
- Permitir mostrar último resultado na página da candidatura.
- Guardar `application_id` em `eligibility_checks`.
- Usar snapshots da candidatura se existirem.
- Não alterar automaticamente estado da candidatura para `eligible` ou `ineligible`, salvo se já existir workflow claro.

Se for necessário alterar estado da candidatura, fazer apenas através de service e documentar.

Recomendação nesta sprint:

```text
O check de elegibilidade gera resultado, mas não executa decisão administrativa final automática.
```

Se `Application` não existir:

- Implementar apenas pré-check.
- Criar interfaces/hooks para futura integração.

---

# Auditoria

Se auditoria existir, auditar:

```text
Criação de rule set
Atualização de rule set
Ativação de rule set
Arquivo de rule set
Duplicação de rule set
Criação de critério
Atualização de critério
Ativação/inativação de critério
Execução de pré-check
Execução de check formal
Reexecução de check
Consulta administrativa de check, se aplicável
```

Não guardar dados pessoais excessivos nos logs.

Não guardar snapshots completos em audit logs.

---

# RGPD e segurança

## Regras obrigatórias

- Resultados de elegibilidade são dados sensíveis.
- Candidato só consulta os seus próprios resultados.
- Candidato não consulta checks de outros candidatos.
- Backoffice exige permissões.
- Snapshots devem ser protegidos.
- Dados sensíveis não aparecem em páginas públicas.
- Mensagens ao candidato devem ser claras, mas sem expor lógica interna excessiva.
- Mensagens técnicas ficam restritas ao backoffice.
- Não guardar documentos nos snapshots.
- Não guardar paths internos de ficheiros.
- Não usar dados reais em seeders.
- Não permitir mass assignment de resultado.
- Não permitir alteração manual de resultado sem service.
- Não criar endpoint público de verificação sem autenticação.

---

# Seeders e factories

Criar factories:

```text
EligibilityRuleSetFactory
EligibilityCriterionFactory
EligibilityCheckFactory
EligibilityCheckResultFactory
EligibilitySnapshotFactory
```

Criar seeders:

```text
EligibilityBaseCriteriaSeeder
EligibilityDemoRuleSetSeeder
```

## Dados demo

Usar apenas dados fictícios.

Exemplos permitidos:

```text
Regra Demo — Programa Municipal de Arrendamento Acessível
Critério Demo — Registo de Adesão Finalizado
Critério Demo — Rendimento Máximo
Critério Demo — Documentos Submetidos
```

Não usar dados pessoais reais.

---

# Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text
guest_cannot_access_candidate_eligibility
candidate_can_access_own_eligibility_page
candidate_cannot_access_another_candidate_eligibility_check
non_candidate_cannot_access_candidate_eligibility_area
admin_can_access_eligibility_rule_sets
technician_can_access_eligibility_checks_if_authorized
candidate_cannot_access_backoffice_eligibility
```

## Rule sets

```text
admin_can_create_eligibility_rule_set
admin_can_update_eligibility_rule_set
admin_can_activate_eligibility_rule_set
admin_can_archive_eligibility_rule_set
rule_set_requires_program_or_contest
active_contest_rule_set_takes_precedence_over_program_rule_set
draft_rule_set_is_not_used_for_checks
archived_rule_set_is_not_used_for_checks
```

## Critérios

```text
admin_can_create_eligibility_criterion
admin_can_update_eligibility_criterion
criterion_code_must_be_unique_within_rule_set
inactive_criterion_is_not_evaluated
mandatory_failed_criterion_makes_check_ineligible
manual_review_criterion_makes_check_requires_review_when_no_failure
```

## Engine

```text
eligibility_engine_creates_check
eligibility_engine_creates_result_for_each_active_criterion
eligibility_engine_creates_snapshots
eligibility_engine_returns_eligible_when_all_mandatory_criteria_pass
eligibility_engine_returns_ineligible_when_mandatory_criterion_fails
eligibility_engine_returns_insufficient_data_when_required_data_is_missing
eligibility_engine_returns_requires_review_when_manual_review_is_needed
```

## Critérios específicos

```text
registration_is_registered_passes_for_registered_registration
registration_is_registered_fails_for_incomplete_registration
candidate_is_adult_passes_for_adult_candidate
candidate_is_adult_fails_for_minor_candidate
contest_is_open_passes_for_open_contest
contest_is_open_fails_for_closed_contest
has_household_passes_when_household_exists
has_household_fails_when_household_missing
has_applicant_member_passes_when_applicant_member_exists
has_income_information_returns_insufficient_data_when_income_missing
income_below_maximum_passes_when_total_income_is_below_limit
income_below_maximum_fails_when_total_income_exceeds_limit
income_above_minimum_passes_when_total_income_is_above_limit
income_above_minimum_fails_when_total_income_is_below_limit
has_current_housing_situation_passes_when_exists
resides_in_municipality_passes_when_declared_true
works_in_municipality_passes_when_declared_true
```

## Documentos

```text
required_documents_submitted_passes_when_all_required_documents_are_submitted_or_validated
required_documents_submitted_fails_when_required_document_is_missing
required_documents_submitted_fails_when_required_document_is_rejected
required_documents_validated_passes_when_all_required_documents_are_validated
required_documents_validated_fails_when_document_is_only_submitted
```

Se Sprint 6 não existir, documentar testes documentais como pendentes.

## Application, se existir

```text
formal_application_check_can_be_run_for_submitted_application
formal_application_check_belongs_to_application
application_latest_eligibility_check_is_available
candidate_cannot_run_check_for_other_candidate_application
```

Se Application não existir, não criar testes quebrados.

## Segurança

```text
candidate_cannot_mass_assign_eligibility_result
candidate_cannot_create_rule_set
candidate_cannot_create_criterion
eligibility_snapshots_are_not_public
technical_messages_are_not_visible_to_candidate
candidate_messages_do_not_expose_sensitive_internal_details
```

## Auditoria, se existir

```text
creating_rule_set_generates_audit_log
updating_criterion_generates_audit_log
running_eligibility_check_generates_audit_log
```

Se auditoria não existir, documentar pendência em vez de criar teste quebrado.

---

# Comandos de validação

No final, executar:

```bash
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash
php artisan migrate
```

Se o projeto usar build frontend:

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

# Atualização documental obrigatória

Atualizar, se existirem:

```text
docs/backlog/sprint-7-motor-elegibilidade.md
docs/backlog/roadmap.md
docs/product/functional-requirements.md
docs/product/process-workflows.md
docs/architecture/data-model-overview.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Registar:

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
Pendências para Sprint 8, Sprint 9 e Sprint 10
```

---

# Critérios de aceitação da Sprint 7

A Sprint 7 está concluída quando:

```text
Existe modelo de rule sets de elegibilidade
Existem critérios configuráveis
Rule sets podem ser associados a programa
Rule sets podem ser associados a concurso
Rule set de concurso prevalece sobre rule set de programa
Apenas rule sets ativos são usados
Critérios inativos não são avaliados
O motor executa pré-check do candidato
O motor executa check formal de candidatura, se Application existir
Cada check gera resultado global
Cada critério gera resultado individual
O sistema guarda histórico de verificações
O sistema guarda snapshots mínimos dos dados avaliados
O sistema identifica dados em falta
O sistema identifica critérios falhados
O sistema identifica critérios que exigem análise manual
O candidato vê resultado simplificado
O backoffice vê resultado técnico
O candidato não vê resultados de outros candidatos
O candidato não vê mensagens técnicas internas
O backoffice consegue configurar regras, conforme permissões
O sistema não implementa classificação
O sistema não implementa ranking
O sistema não implementa listas
O sistema não implementa atribuição
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
```

---

# Resposta final esperada do Codex

No final da execução, responder com:

```text
1. Resumo do que foi implementado na Sprint 7
2. Ficheiros criados
3. Ficheiros alterados
4. Migrations criadas
5. Models criados ou alterados
6. Enums criados
7. Controllers criados ou alterados
8. Form Requests criados
9. Policies criadas
10. Services criados
11. Views/páginas criadas ou alteradas
12. Rotas criadas
13. Seeders/factories criados ou alterados
14. Testes criados ou alterados
15. Resultado dos comandos executados
16. Problemas encontrados
17. Pendências
18. Confirmação de que não foram implementadas funcionalidades fora de âmbito
19. Recomendação objetiva para avançar ou não para Sprint 8, Sprint 9 ou Sprint 10
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# Definition of Done

A Sprint 7 só está concluída quando a plataforma tiver um motor de elegibilidade configurável, rastreável, testado e explicável, capaz de avaliar candidatos ou candidaturas com base em regras de programa/concurso, sem confundir elegibilidade com classificação.

Fim da Sprint 7.

---

# Execução concluída em 11/06/2026

## Implementado

- cinco tabelas de elegibilidade;
- sete enums de estados, tipos, resultados, categorias e operadores;
- models e relações com programa, concurso, utilizador, adesão e candidatura;
- resolver com precedência de concurso e vigência temporal;
- data provider para agregado, rendimentos, taxa de esforço, documentos e duplicação;
- evaluator para os operadores definidos na master prompt;
- agregação determinística, motor transacional, snapshots e auditoria;
- pré-check do candidato e check formal de candidatura;
- configuração e consulta técnica no backoffice;
- seeders demo, cinco factories e testes.

## Estruturas

- Migration: `2026_06_11_020000_create_eligibility_tables.php`.
- Tabelas: `eligibility_rule_sets`, `eligibility_criteria`, `eligibility_checks`, `eligibility_check_results`, `eligibility_snapshots`.
- Services: resolver, engine, evaluator, check service, snapshot service, aggregator, data provider e message service.
- Rotas: quatro de candidato e vinte de backoffice.

## Validação

- `php artisan migrate --force`: concluído.
- `php artisan route:list`: concluído, 194 rotas.
- `php artisan test`: 93 testes e 525 asserções aprovados.
- `npm run build`: concluído com Vite 8.0.16.
- `./vendor/bin/pint`: concluído; dois ficheiros corrigidos.
- PHPStan/Psalm: não instalados.
- Browser local em `127.0.0.1:8001`: candidato móvel e backoffice desktop validados.

## Problemas resolvidos

- A checklist documental foi estendida para receber programa/concurso no pré-check, reutilizando a lógica da Sprint 6.
- A lista de dados em falta passou a considerar apenas critérios ativos e avaliados.
- O painel de browser integrado não estava ativo; a validação visual foi concluída com Playwright isolado.

## Pendências

- substituir regras demo por regras municipais aprovadas;
- implementar decisão administrativa na Sprint 9;
- implementar classificação e ranking apenas na Sprint 10;
- definir retenção, anonimização e eventual DPIA na Sprint 18.

Não foram implementados classificação, pontuação, ranking, listas, reclamações, atribuição, sorteio, contratos, pagamentos, manutenção, notificações reais ou integrações externas.
