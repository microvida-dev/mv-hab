# MASTER PROMPT — EXECUÇÃO DA SPRINT 7: MOTOR DE ELEGIBILIDADE

Atua como arquiteto sénior Laravel, tech lead e product engineer.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 7 — Motor de Elegibilidade
```

Esta sprint pertence à prioridade funcional:

```text
2. Elegibilidade
```

A Sprint 7 deve criar um motor configurável, auditável e explicável para verificar se um candidato ou candidatura reúne as condições mínimas de acesso a um programa ou concurso municipal de Arrendamento Acessível.

---

# 1. Regra principal

Executa apenas a Sprint 7.

Não avances para Sprint 8, Sprint 9, Sprint 10, Sprint 11 ou qualquer outra sprint sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash
git branch --show-current
```

Não interromper a execução por causa da branch atual.

---

# 2. Documentação obrigatória a ler antes de implementar

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
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Lê também:

```text
docs/backlog/sprint-3-portal-publico-programas.md
docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/sprint-5-agregado-rendimentos-situacao-habitacional.md
docs/backlog/sprint-6-gestao-documental-avancada.md
docs/backlog/sprint-7-motor-elegibilidade.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md
```

O ficheiro principal desta execução é:

```text
docs/backlog/sprint-7-motor-elegibilidade.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

---

# 3. Inspeção inicial obrigatória

Antes de implementar, inspeciona o repositório e identifica:

```text
Versão do Laravel
Versão do PHP
Sistema de autenticação
Sistema de roles/permissões
Stack frontend
Estrutura de routes
Controllers existentes
Models existentes
Migrations existentes
Seeders existentes
Factories existentes
Policies existentes
Services existentes
Views/componentes existentes
Tests existentes
Sistema de storage
Sistema de auditoria, se existir
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
Application, se existir
```

Não duplicar entidades já existentes.

Se já existir algo equivalente a:

```text
ProgramRule
EligibilityRule
EligibilityRuleSet
EligibilityCriterion
EligibilityCheck
EligibilityResult
ApplicationEligibility
```

reaproveitar ou adaptar com compatibilidade, em vez de criar estrutura paralela.

Não apagar dados existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir tokens, passwords reais ou APP_KEY.

---

# 4. Dependências da Sprint 7

A Sprint 7 depende preferencialmente de:

```text
Sprint 3 — Portal Público e Programas
Sprint 4 — Registo de Adesão e Área Pessoal
Sprint 5 — Agregado Familiar, Rendimentos e Situação Habitacional
Sprint 6 — Gestão Documental Avançada
```

Se `AdhesionRegistration`, `Household`, `HouseholdMember`, `IncomeRecord` ou `CurrentHousingSituation` não existirem, interrompe a implementação funcional e informa que a Sprint 7 não pode ser executada com segurança.

Se `DocumentSubmission` ou checklist documental ainda não existirem, implementa o motor de elegibilidade sem avaliação documental completa e documenta os critérios documentais como pendência.

Se `Application` ainda não existir, implementa apenas:

```text
Pré-verificação do candidato
Rule sets
Critérios
Resultados
Snapshots
Backoffice de configuração
Área do candidato com resultado indicativo
```

e prepara hooks para integração futura com candidaturas formais.

---

# 5. Objetivo da implementação

Implementar um motor de elegibilidade configurável, transparente e auditável, capaz de:

```text
Definir regras de elegibilidade por programa
Definir regras de elegibilidade por concurso
Executar pré-verificação para candidatos
Executar verificação formal para candidaturas, se Application existir
Avaliar requisitos mínimos
Avaliar impedimentos
Avaliar limites de rendimento
Avaliar composição do agregado
Avaliar residência ou atividade profissional no município
Avaliar situação habitacional
Avaliar documentação mínima, se Sprint 6 existir
Registar resultado global
Registar resultado por critério
Registar dados em falta
Registar alertas
Guardar snapshots mínimos dos dados avaliados
Mostrar resultado simples ao candidato
Mostrar detalhe técnico no backoffice
Preservar histórico de verificações
```

---

# 6. Separação conceptual obrigatória

A elegibilidade não é classificação.

Nesta sprint, garantir separação clara:

```text
Elegibilidade = verifica se o candidato cumpre condições mínimas de acesso.
Classificação = pontua e ordena candidatos elegíveis.
```

Não implementar:

```text
Pontuação social
Matriz de classificação
Ranking
Ordenação de candidatos
Listas provisórias
Listas definitivas
Atribuição
Sorteio
Contrato
```

---

# 7. Âmbito incluído

Implementar:

```text
EligibilityRuleSet
EligibilityCriterion
EligibilityCheck
EligibilityCheckResult
EligibilitySnapshot

Enums de elegibilidade
Services do motor de elegibilidade
Backoffice para configurar rule sets
Backoffice para configurar critérios
Backoffice para consultar checks
Área do candidato para pré-verificação
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

# 8. Fora de âmbito

Não implementar nesta sprint:

```text
Classificação competitiva
Pontuação social
Ranking
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
Integração com Autoridade Tributária
Integração com Segurança Social
Integração com Autenticação.GOV
OCR
Assinatura digital
Validação automática de autenticidade documental
```

Podem ser criados pontos de integração para estas funcionalidades, mas não as implementar.

---

# 9. Resultado global da elegibilidade

O motor deve devolver um dos seguintes resultados:

```text
eligible
ineligible
requires_review
insufficient_data
not_applicable
```

## eligible

Todos os critérios obrigatórios aplicáveis foram cumpridos.

## ineligible

Um ou mais critérios obrigatórios falharam.

## requires_review

Existem critérios que exigem avaliação técnica/manual e nenhum critério obrigatório falhou.

## insufficient_data

Faltam dados essenciais para avaliar.

## not_applicable

Não existem regras aplicáveis ao contexto avaliado.

---

# 10. Resultado por critério

Cada critério avaliado deve gerar um resultado individual:

```text
passed
failed
requires_review
insufficient_data
not_applicable
```

Cada resultado deve conter:

```text
Código do critério
Nome do critério
Categoria
Resultado
Valor esperado
Valor real, quando seguro
Mensagem simples
Mensagem técnica, apenas backoffice
Indicação se requer análise manual
```

Não expor dados sensíveis desnecessários ao candidato.

---

# 11. Modelo de dados a implementar

## 11.1 EligibilityRuleSet

Tabela:

```text
eligibility_rule_sets
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

Estados:

```text
draft
active
archived
```

Regras:

```text
Pode estar associado a programa
Pode estar associado a concurso
Rule set de concurso prevalece sobre rule set de programa
Apenas rule sets ativos são usados na avaliação
Não apagar rule sets já usados em checks
Preferir arquivar
```

---

## 11.2 EligibilityCriterion

Tabela:

```text
eligibility_criteria
```

Campos mínimos:

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

Categorias recomendadas:

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

Targets recomendados:

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

Operadores recomendados:

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

---

## 11.3 EligibilityCheck

Tabela:

```text
eligibility_checks
```

Campos mínimos:

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

Tipos:

```text
candidate_pre_check
application_formal_check
backoffice_manual_check
system_recheck
```

Estados:

```text
draft
completed
failed
cancelled
```

---

## 11.4 EligibilityCheckResult

Tabela:

```text
eligibility_check_results
```

Campos mínimos:

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

---

## 11.5 EligibilitySnapshot

Tabela:

```text
eligibility_snapshots
```

Campos mínimos:

```text
id
eligibility_check_id
snapshot_type
data
created_at
updated_at
```

Tipos de snapshot:

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

Não guardar ficheiros.

Não guardar paths internos.

Guardar apenas dados mínimos necessários para rastreabilidade.

---

# 12. Enums a criar

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

Se o projeto não suportar enums PHP, usar classes de constantes.

---

# 13. Relações obrigatórias

Implementar relações conforme existam os models relacionados.

## Program

```text
hasMany EligibilityRuleSet
hasMany EligibilityCheck
```

## Contest

```text
hasMany EligibilityRuleSet
hasMany EligibilityCheck
```

## User

```text
hasMany EligibilityCheck
```

## AdhesionRegistration

```text
hasMany EligibilityCheck
```

## Application, se existir

```text
hasMany EligibilityCheck
hasOne latestEligibilityCheck
```

## EligibilityRuleSet

```text
belongsTo Program nullable
belongsTo Contest nullable
hasMany EligibilityCriterion
hasMany EligibilityCheck
belongsTo User as createdBy
belongsTo User as updatedBy
```

## EligibilityCriterion

```text
belongsTo EligibilityRuleSet
hasMany EligibilityCheckResult
```

## EligibilityCheck

```text
belongsTo EligibilityRuleSet
belongsTo Program nullable
belongsTo Contest nullable
belongsTo Application nullable
belongsTo AdhesionRegistration nullable
belongsTo User nullable
belongsTo User as executedBy
hasMany EligibilityCheckResult
hasMany EligibilitySnapshot
```

## EligibilityCheckResult

```text
belongsTo EligibilityCheck
belongsTo EligibilityCriterion
```

## EligibilitySnapshot

```text
belongsTo EligibilityCheck
```

---

# 14. Services obrigatórios

Criar os seguintes services:

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

## 14.1 EligibilityRuleSetResolver

Responsável por:

```text
Resolver rule set aplicável a programa
Resolver rule set aplicável a concurso
Dar prioridade ao rule set específico do concurso
Usar apenas rule sets ativos
Ignorar draft e archived
Gerar erro claro quando não existir regra aplicável
```

---

## 14.2 EligibilityEngine

Responsável por:

```text
Receber contexto de avaliação
Resolver rule set aplicável
Percorrer critérios ativos
Avaliar cada critério
Criar EligibilityCheck
Criar EligibilityCheckResult
Criar EligibilitySnapshot
Agregar resultado global
Guardar missing_data
Guardar warnings
Devolver resultado estruturado
```

A execução deve ser transacional sempre que criar registos persistentes.

---

## 14.3 EligibilityCriteriaEvaluator

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

Critérios específicos mínimos:

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

Se alguma dependência não existir, devolver `insufficient_data` ou `not_applicable`, conforme o caso, sem quebrar a aplicação.

---

## 14.4 EligibilityCheckService

Responsável por:

```text
Executar pré-check de candidato
Executar check formal de candidatura, se Application existir
Reexecutar check
Obter último check
Listar histórico
Garantir permissões de execução
```

---

## 14.5 EligibilitySnapshotService

Responsável por:

```text
Capturar dados mínimos avaliados
Capturar agregado
Capturar membros do agregado
Capturar rendimentos agregados
Capturar situação habitacional
Capturar estado documental
Capturar candidatura, se existir
Guardar snapshot JSON seguro
```

---

## 14.6 EligibilityResultAggregator

Regras de agregação:

```text
Se qualquer critério obrigatório falhar → ineligible
Se existir critério obrigatório com insufficient_data → insufficient_data
Se existir critério requires_review e nenhum obrigatório falhou → requires_review
Se todos os critérios obrigatórios passaram → eligible
Se nenhum critério aplicável existir → not_applicable
```

Precedência:

```text
ineligible prevalece sobre requires_review
insufficient_data prevalece sobre requires_review
```

---

## 14.7 EligibilityDataProvider

Responsável por calcular ou obter:

```text
Idade do candidato
Número de membros do agregado
Número de adultos
Número de menores
Número de dependentes
Rendimento mensal total
Rendimento anual total
Rendimento mensal per capita
Rendimento anual per capita
Taxa de esforço atual
Estado da situação habitacional
Documentos obrigatórios em falta
Documentos rejeitados
Documentos submetidos
Documentos validados
Existência de candidatura ativa duplicada
```

Reutilizar services existentes de rendimentos, documentos ou progresso se existirem.

Não duplicar cálculos já implementados.

---

## 14.8 EligibilityMessageService

Responsável por:

```text
Gerar mensagens simples para candidato
Gerar mensagens técnicas para backoffice
Usar mensagens configuradas nos critérios
Evitar exposição de dados sensíveis
Separar message de technical_message
```

---

# 15. Critérios base a semear

Criar seeder:

```text
EligibilityBaseCriteriaSeeder
```

Criar também, se útil:

```text
EligibilityDemoRuleSetSeeder
```

Critérios base recomendados:

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
typology_is_adequate
no_declared_property_impediment
no_incompatible_housing_support
requires_manual_review_for_special_conditions
```

Não assumir todos como ativos universalmente.

A ativação deve depender do rule set configurado.

---

# 16. Backoffice

Criar área administrativa de elegibilidade.

Controllers recomendados:

```text
App\Http\Controllers\Backoffice\EligibilityRuleSetController
App\Http\Controllers\Backoffice\EligibilityCriterionController
App\Http\Controllers\Backoffice\EligibilityCheckController
```

## Rule sets

Permitir:

```text
Listar
Ver detalhe
Criar
Editar
Ativar
Arquivar
Duplicar
```

Não apagar rule sets usados em checks.

## Critérios

Permitir:

```text
Listar critérios de um rule set
Criar critério
Editar critério
Ativar
Inativar
Ordenar, se viável
```

Preferir inativar em vez de apagar.

## Checks

Permitir:

```text
Listar verificações
Ver detalhe técnico
Reexecutar check quando permitido
Executar check formal de candidatura se Application existir
```

---

# 17. Área do candidato

Criar área de pré-verificação.

Controller recomendado:

```text
App\Http\Controllers\Candidate\EligibilityController
```

Ações:

```text
index
preCheck
show
history
```

O candidato deve poder:

```text
Ver explicação simples sobre elegibilidade
Selecionar programa ou concurso, se aplicável
Executar pré-verificação
Ver resultado global
Ver critérios cumpridos
Ver critérios em falta
Ver critérios que exigem análise
Ver histórico das suas verificações
Aceder a CTAs para corrigir dados
```

## Aviso obrigatório ao candidato

Mostrar sempre:

```text
Esta verificação é indicativa e baseia-se nos dados atualmente declarados. A decisão final depende da análise dos serviços municipais e das regras do programa ou concurso.
```

---

# 18. Mensagens ao candidato

## eligible

```text
Com base nos dados atualmente declarados, reúne as condições mínimas indicadas para este programa ou concurso. A confirmação final dependerá da análise dos serviços municipais.
```

## ineligible

```text
Com base nos dados atualmente declarados, existem condições mínimas que não se encontram cumpridas. Consulte os pontos assinalados e confirme se os seus dados estão corretos.
```

## insufficient_data

```text
Não existem ainda dados suficientes para avaliar a elegibilidade. Complete o seu registo, agregado, rendimentos, situação habitacional e documentos.
```

## requires_review

```text
Alguns elementos exigem análise pelos serviços municipais. O resultado apresentado é indicativo e poderá ser confirmado posteriormente.
```

## not_applicable

```text
Não foi possível aplicar regras de elegibilidade ao contexto selecionado.
```

---

# 19. CTAs de correção

Quando existirem dados em falta, mostrar CTAs para:

```text
Completar Registo de Adesão
Atualizar Agregado
Atualizar Rendimentos
Atualizar Situação Habitacional
Consultar Documentos
Ver Concursos
```

Usar rotas existentes se existirem.

Se alguma rota não existir, ocultar CTA ou documentar pendência.

---

# 20. Form Requests

Criar:

```text
StoreEligibilityRuleSetRequest
UpdateEligibilityRuleSetRequest
StoreEligibilityCriterionRequest
UpdateEligibilityCriterionRequest
RunCandidatePreCheckRequest
RunApplicationEligibilityCheckRequest, apenas se Application existir
```

Garantir validação de:

```text
program_id ou contest_id obrigatório para rule set
program_id ou contest_id obrigatório para pré-check
code obrigatório e único dentro do rule set
operator dentro dos valores permitidos
status dentro dos valores permitidos
minimum_value e maximum_value coerentes
```

---

# 21. Policies

Criar:

```text
EligibilityRuleSetPolicy
EligibilityCriterionPolicy
EligibilityCheckPolicy
EligibilityCheckResultPolicy
EligibilitySnapshotPolicy
```

Regras obrigatórias:

```text
Candidato só consulta os seus próprios checks
Candidato pode executar pré-check apenas sobre os seus próprios dados
Candidato não cria nem edita rule sets
Candidato não cria nem edita critérios
Candidato não vê mensagens técnicas internas
Técnico municipal pode consultar checks se autorizado
Admin pode gerir rule sets e critérios
Auditor pode consultar histórico, sem alterar
Backoffice exige autenticação e permissões
```

---

# 22. Rotas

## Área do candidato

Criar, preferencialmente:

```text
GET /area-candidato/elegibilidade
POST /area-candidato/elegibilidade/pre-verificar
GET /area-candidato/elegibilidade/{eligibilityCheck}
GET /area-candidato/elegibilidade/historico
```

Nomes:

```text
candidate.eligibility.index
candidate.eligibility.pre-check
candidate.eligibility.show
candidate.eligibility.history
```

## Backoffice

Criar, preferencialmente:

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
POST /backoffice/eligibility/checks/{eligibilityCheck}/rerun
```

Se `Application` existir:

```text
POST /backoffice/eligibility/applications/{application}/run
```

Não criar rotas dependentes de Application se o model não existir.

---

# 23. Views / páginas

Se o projeto usa Blade, criar:

## Candidato

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

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 24. Integração com documentos

Se existir Sprint 6, o motor deve avaliar:

```text
Documentos obrigatórios em falta
Documentos submetidos
Documentos validados
Documentos rejeitados
Documentos expirados
```

Critérios:

```text
has_required_documents_submitted
has_required_documents_validated
```

Regras:

```text
submitted, under_review e validated contam como submetidos
validated conta como validado
missing, rejected, expired e cancelled falham
```

Se a gestão documental não existir, não criar versão simplificada paralela. Documentar pendência.

---

# 25. Integração com rendimentos

Calcular ou obter:

```text
Rendimento mensal total
Rendimento anual total
Rendimento mensal per capita
Rendimento anual per capita
Número de membros
Número de adultos
Número de menores
Número de dependentes
```

Critérios mínimos:

```text
income_above_minimum
income_below_maximum
has_income_information
```

Se faltar informação:

```text
insufficient_data
```

Não integrar com AT ou Segurança Social.

---

# 26. Integração com situação habitacional

Avaliar, quando configurado:

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

Condições especiais podem devolver:

```text
requires_review
```

em vez de decisão automática.

---

# 27. Integração com candidatura

Se `Application` existir:

```text
Permitir executar check formal da candidatura
Guardar application_id em eligibility_checks
Mostrar último check na página da candidatura, se existir local adequado
Usar snapshots da candidatura, se existirem
```

Recomendação:

```text
O resultado de elegibilidade não deve alterar automaticamente o estado da candidatura nesta sprint.
```

Se for alterado, deve passar por service e ficar documentado.

Se `Application` não existir:

```text
Implementar apenas pré-check
Criar pontos de integração futura
Não criar Application nesta sprint
```

---

# 28. Auditoria

Se existir auditoria da Sprint 1, auditar:

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

Não criar auditoria paralela.

Se auditoria não existir, documentar pendência.

---

# 29. RGPD e segurança

Regras obrigatórias:

```text
Resultados de elegibilidade são dados sensíveis
Candidato só consulta os seus próprios resultados
Candidato não consulta checks de outros candidatos
Backoffice exige permissões
Snapshots são protegidos por policy
Dados sensíveis não aparecem em páginas públicas
Mensagens ao candidato não expõem lógica técnica interna excessiva
Mensagens técnicas ficam apenas no backoffice
Não guardar documentos nos snapshots
Não guardar paths internos de ficheiros
Não usar dados reais em seeders
Não permitir mass assignment de resultado
Não permitir alteração manual de resultado fora dos services
Não criar endpoint público de verificação sem autenticação
```

---

# 30. Seeders e factories

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

Usar apenas dados fictícios.

Não usar dados pessoais reais.

---

# 31. Testes obrigatórios

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

## Documentos, se Sprint 6 existir

```text
required_documents_submitted_passes_when_all_required_documents_are_submitted_or_validated
required_documents_submitted_fails_when_required_document_is_missing
required_documents_submitted_fails_when_required_document_is_rejected
required_documents_validated_passes_when_all_required_documents_are_validated
required_documents_validated_fails_when_document_is_only_submitted
```

## Application, se existir

```text
formal_application_check_can_be_run_for_submitted_application
formal_application_check_belongs_to_application
application_latest_eligibility_check_is_available
candidate_cannot_run_check_for_other_candidate_application
```

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

Se alguma dependência não existir, documentar o teste como pendente em vez de criar teste quebrado.

---

# 32. Comandos de validação

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

# 33. Atualização documental obrigatória

No final, atualizar, se existirem:

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
Pendências para Sprint 8, Sprint 9 e Sprint 10
```

---

# 34. Critérios de aceitação

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

# 35. Resposta final obrigatória

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
20. Recomendação objetiva para avançar ou não para Sprint 8, Sprint 9 ou Sprint 10
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 36. Execução imediata

Executa agora apenas:

```text
Sprint 7 — Motor de Elegibilidade
```

Usa como referência principal:

```text
docs/backlog/sprint-7-motor-elegibilidade.md
```

Fim da master prompt da Sprint 7.
