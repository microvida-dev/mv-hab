# Sprint 5 — Agregado Familiar, Rendimentos e Situação Habitacional

## Prioridade de desenvolvimento

Esta sprint pertence à prioridade funcional:

```text
1. Candidatura
```

A Sprint 5 completa a base de dados pessoal iniciada na Sprint 4, adicionando as três áreas fundamentais para que uma futura candidatura possa ser avaliada:

```text
Agregado familiar
Rendimentos
Situação habitacional atual
```

Estes dados serão usados nas sprints seguintes para:

```text
Sprint 6 — Gestão documental avançada
Sprint 7 — Motor de elegibilidade
Sprint 8 — Candidaturas e submissão formal
Sprint 10 — Classificação
Sprint 13 — Cálculo de renda
```

---

# Objetivo da Sprint

Implementar os módulos que permitem ao candidato declarar, gerir e validar os dados essenciais do seu agregado habitacional/familiar, rendimentos e situação habitacional atual.

A plataforma deve permitir que o candidato:

- Adicione membros do agregado;
- Edite membros do agregado;
- Remova membros quando permitido;
- Declare grau de parentesco;
- Declare dependentes;
- Declare deficiência ou incapacidade;
- Declare situação profissional;
- Declare rendimentos por membro;
- Declare fontes de rendimento;
- Declare ausência de rendimentos, quando aplicável;
- Declare situação habitacional atual;
- Declare residência no município;
- Declare trabalho no município;
- Declare motivos do pedido;
- Veja resumo do agregado;
- Veja resumo económico;
- Veja campos em falta;
- Guarde tudo como rascunho;
- Prepare o registo para candidatura futura.

Esta sprint não deve implementar ainda candidatura formal, elegibilidade automática, classificação, upload documental avançado ou decisão administrativa.

---

# Instrução operacional para Codex

Executa apenas esta Sprint 5.

Não avances para Sprint 6, Sprint 7, Sprint 8 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

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
docs/backlog/sprint-0-foundation.md
docs/backlog/sprint-1-foundation.md
docs/backlog/sprint-2-foundation.md
docs/backlog/sprint-3-portal-publico-programas.md
docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/sprint-5-agregado-rendimentos-situacao-habitacional.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Antes de implementar, confirma que existem ou identifica alternativas para:

```text
Área do candidato
Role candidate
AdhesionRegistration
Estados do Registo de Adesão
Candidate layout
Componentes de formulário
Componentes de stepper/progresso
Auditoria, se existir
Policies ou middleware de permissões
```

Se a Sprint 4 não estiver implementada, interrompe a execução e informa que a Sprint 5 depende do Registo de Adesão.

Não duplicar entidades já existentes.

Se o CRM atual já tiver `Household`, `Citizen` ou modelos semelhantes, avaliar se devem ser reaproveitados, adaptados ou mantidos com compatibilidade.

Não apagar dados ou modelos existentes sem necessidade.

---

# Âmbito desta Sprint

## Incluído

Implementar:

```text
Gestão de agregado familiar/habitacional
Gestão de membros do agregado
Gestão de rendimentos por membro
Gestão de fontes de rendimento
Declaração de ausência de rendimentos
Situação profissional
Situação habitacional atual
Motivos do pedido
Resumo do agregado
Resumo económico
Validações base
Histórico simples de alterações críticas, se aplicável
Integração visual com Registo de Adesão
Atualização do progresso do Registo de Adesão
Testes mínimos
Atualização documental
```

## Fora de âmbito

Não implementar nesta sprint:

```text
Submissão formal de candidatura
Upload documental avançado
Validação documental por técnico
Motor de elegibilidade automático
Cálculo final de elegibilidade
Matriz de classificação
Pontuação
Ranking
Listas provisórias
Reclamações
Audiência de interessados
Atribuição
Contratos
Cálculo real de renda contratual
Pagamentos
Manutenção
Notificações reais por email/SMS
Integrações externas com AT, Segurança Social ou Autenticação.GOV
```

Podem existir avisos ou placeholders para documentos que serão exigidos na Sprint 6.

---

# Conceito funcional

O Registo de Adesão deve passar a ser composto por quatro áreas:

```text
1. Utilizador
2. Agregado
3. Rendimentos
4. Habitação Atual
```

A Sprint 4 implementou a base do utilizador.

A Sprint 5 deve implementar:

```text
2. Agregado
3. Rendimentos
4. Habitação Atual
```

Estas áreas devem ser editáveis enquanto o registo estiver em estado:

```text
incomplete
registered
```

Se o registo estiver:

```text
cancelled
removed
blocked
expired
```

a edição deve ser bloqueada ou condicionada, conforme a lógica existente.

---

# Regras gerais

## Registo obrigatório

O candidato deve ter um `AdhesionRegistration` antes de criar agregado, rendimentos ou situação habitacional.

Se não existir registo de adesão, redirecionar para criação do registo ou mostrar CTA:

```text
Iniciar Registo de Adesão
```

## Propriedade dos dados

O candidato só pode ver e editar dados associados ao seu próprio registo.

Nunca expor dados de outro candidato.

## Edição

O candidato pode editar dados enquanto não existir candidatura formal submetida.

Como a candidatura formal ainda não existe nesta sprint, preparar métodos para bloqueio futuro, mas não implementar bloqueio fictício.

## Remoção

O candidato pode remover membros do agregado e rendimentos antes da submissão de candidatura futura.

Se existir dependência técnica, impedir remoção e mostrar erro claro.

---

# Modelo de dados

## 1. Household

Criar ou adaptar entidade:

```text
Household
```

Tabela recomendada:

```text
households
```

Se já existir tabela `households`, adaptar sem destruir compatibilidade.

## Campos mínimos

```text
id
adhesion_registration_id
name
household_type
members_count
notes
created_at
updated_at
deleted_at
```

## Notas

- `adhesion_registration_id` obrigatório.
- `name` pode ser gerado automaticamente, por exemplo: “Agregado de [Nome do candidato]”.
- `members_count` pode ser calculado ou persistido, conforme arquitetura.
- Usar soft deletes.

---

## 2. HouseholdMember

Criar entidade:

```text
HouseholdMember
```

Tabela:

```text
household_members
```

## Campos mínimos

```text
id
household_id
adhesion_registration_id

is_applicant
full_name
birth_date
gender
relationship
nationality

document_type
document_number
document_valid_until
nif

marital_status
professional_status
employment_type
employer_name
workplace_municipality
works_in_municipality

is_dependent
is_student
is_disabled
disability_percentage
has_reduced_mobility
is_informal_caregiver
is_elderly

monthly_declared_income
annual_declared_income
has_no_income
no_income_reason

notes

created_at
updated_at
deleted_at
```

## Regras

- Deve existir pelo menos um membro marcado como `is_applicant`.
- O candidato principal deve poder ser sincronizado com os dados do `AdhesionRegistration`.
- `nif` deve ser único dentro do mesmo agregado, se preenchido.
- `birth_date` deve ser anterior à data atual.
- `disability_percentage` deve estar entre 0 e 100.
- `has_no_income` e rendimentos positivos não devem coexistir sem justificação.
- Não permitir alteração de `adhesion_registration_id` por formulário.

---

## 3. IncomeSource

Criar entidade:

```text
IncomeSource
```

Tabela:

```text
income_sources
```

## Objetivo

Configurar tipos de rendimento usados na declaração.

## Campos mínimos

```text
id
code
name
description
is_active
sort_order
created_at
updated_at
```

## Fontes mínimas a criar por seeder

```text
Trabalho dependente
Trabalho independente
Pensões
Subsídio de desemprego
Prestações sociais
Rendimentos prediais
Rendimentos de capitais
Bolsas
Apoios habitacionais
Outros rendimentos
Sem rendimentos
```

---

## 4. IncomeRecord

Criar entidade:

```text
IncomeRecord
```

Tabela:

```text
income_records
```

## Campos mínimos

```text
id
household_member_id
household_id
adhesion_registration_id
income_source_id

description
monthly_amount
annual_amount
reference_year
starts_at
ends_at
is_current
is_taxable
notes

created_at
updated_at
deleted_at
```

## Regras

- `household_member_id` obrigatório.
- `income_source_id` obrigatório.
- `monthly_amount` deve ser igual ou superior a 0.
- `annual_amount` deve ser igual ou superior a 0.
- Pelo menos um dos campos `monthly_amount` ou `annual_amount` deve ser preenchido.
- Se só for preenchido mensal, o anual pode ser calculado como mensal x 12.
- Se só for preenchido anual, o mensal pode ser calculado como anual / 12.
- Não guardar documentos nesta sprint.
- Documentos comprovativos ficam para Sprint 6.

---

## 5. CurrentHousingSituation

Criar entidade:

```text
CurrentHousingSituation
```

Tabela:

```text
current_housing_situations
```

## Campos mínimos

```text
id
adhesion_registration_id
housing_status
current_address
current_postal_code
current_city
current_parish
current_municipality

resides_in_municipality
residence_years_in_municipality
works_in_municipality
workplace_municipality

current_housing_typology
current_housing_rooms
current_housing_condition
current_monthly_rent
current_housing_expense

is_overcrowded
is_at_risk_of_eviction
is_homeless
is_temporary_accommodation
is_domestic_violence_victim
has_accessibility_needs
has_high_rent_burden

request_reason
additional_notes

created_at
updated_at
deleted_at
```

## Valores recomendados para `housing_status`

```text
rented
owned_by_family
ceded
temporary_accommodation
homeless
institutional_response
employer_provided
other
```

## Valores recomendados para `current_housing_condition`

```text
adequate
overcrowded
degraded
unsafe
inaccessible
temporary
other
```

---

# Enums recomendados

Criar, se a versão do PHP permitir:

```text
App\Enums\HouseholdRelationship
App\Enums\ProfessionalStatus
App\Enums\IncomeSourceType
App\Enums\HousingStatus
App\Enums\HousingCondition
```

Se o projeto não suportar enums PHP, usar classes de constantes.

## HouseholdRelationship

Valores sugeridos:

```text
applicant
spouse
partner
child
parent
sibling
grandparent
grandchild
other_relative
legal_guardian
ward
other
```

## ProfessionalStatus

Valores sugeridos:

```text
employed
self_employed
unemployed
student
retired
pensioner
disabled
domestic_work
other
```

## HousingStatus

Valores sugeridos:

```text
rented
owned
family_home
ceded
temporary
homeless
institutional
employer_provided
other
```

## HousingCondition

Valores sugeridos:

```text
adequate
overcrowded
degraded
unsafe
inaccessible
temporary
other
```

---

# Relações

## AdhesionRegistration

Adicionar relações:

```text
AdhesionRegistration hasOne Household
AdhesionRegistration hasMany HouseholdMember through Household, se aplicável
AdhesionRegistration hasMany IncomeRecord
AdhesionRegistration hasOne CurrentHousingSituation
```

## Household

Adicionar relações:

```text
Household belongsTo AdhesionRegistration
Household hasMany HouseholdMember
Household hasMany IncomeRecord
```

## HouseholdMember

Adicionar relações:

```text
HouseholdMember belongsTo Household
HouseholdMember belongsTo AdhesionRegistration
HouseholdMember hasMany IncomeRecord
```

## IncomeSource

Adicionar relações:

```text
IncomeSource hasMany IncomeRecord
```

## IncomeRecord

Adicionar relações:

```text
IncomeRecord belongsTo HouseholdMember
IncomeRecord belongsTo Household
IncomeRecord belongsTo AdhesionRegistration
IncomeRecord belongsTo IncomeSource
```

## CurrentHousingSituation

Adicionar relações:

```text
CurrentHousingSituation belongsTo AdhesionRegistration
```

---

# Services

Criar services para evitar lógica pesada nos controllers.

## Services recomendados

```text
App\Services\Candidate\HouseholdService
App\Services\Candidate\HouseholdMemberService
App\Services\Candidate\IncomeService
App\Services\Candidate\HousingSituationService
App\Services\Candidate\RegistrationProgressService
```

## HouseholdService

Responsável por:

```text
Criar agregado associado ao registo
Garantir agregado único por registo
Atualizar contagem de membros
Sincronizar candidato principal
Validar se agregado pode ser editado
```

## HouseholdMemberService

Responsável por:

```text
Criar membro
Atualizar membro
Remover membro
Garantir membro requerente
Validar NIF único no agregado
Calcular idade
Determinar dependente provável
```

## IncomeService

Responsável por:

```text
Criar rendimento
Atualizar rendimento
Remover rendimento
Calcular rendimento mensal do membro
Calcular rendimento anual do membro
Calcular rendimento mensal total do agregado
Calcular rendimento anual total do agregado
Identificar membros sem rendimentos declarados
```

## HousingSituationService

Responsável por:

```text
Criar ou atualizar situação habitacional
Validar coerência da situação
Identificar sinais de sobreocupação
Identificar risco habitacional declarado
```

## RegistrationProgressService

Responsável por:

```text
Calcular progresso global do Registo de Adesão
Calcular conclusão da área Utilizador
Calcular conclusão da área Agregado
Calcular conclusão da área Rendimentos
Calcular conclusão da área Habitação Atual
Listar campos em falta
Atualizar dashboard do candidato
```

---

# Controllers

Criar controllers em:

```text
App\Http\Controllers\Candidate
```

## Controllers recomendados

```text
Candidate\HouseholdController
Candidate\HouseholdMemberController
Candidate\IncomeRecordController
Candidate\CurrentHousingSituationController
```

## HouseholdController

Ações:

```text
show
edit
update
```

## HouseholdMemberController

Ações:

```text
index
create
store
edit
update
destroy
```

## IncomeRecordController

Ações:

```text
index
create
store
edit
update
destroy
```

## CurrentHousingSituationController

Ações:

```text
show
edit
update
```

Não criar controllers administrativos nesta sprint, salvo se necessário para suporte técnico já previsto.

---

# Form Requests

Criar Form Requests:

```text
StoreHouseholdMemberRequest
UpdateHouseholdMemberRequest
StoreIncomeRecordRequest
UpdateIncomeRecordRequest
UpdateCurrentHousingSituationRequest
```

## Validação de HouseholdMember

Regras mínimas:

```text
full_name required|string|max:255
birth_date required|date|before:today
relationship required|string|max:100
nationality nullable|string|max:100
document_type nullable|string|max:50
document_number nullable|string|max:100
document_valid_until nullable|date
nif nullable|string|max:20
marital_status nullable|string|max:100
professional_status nullable|string|max:100
employment_type nullable|string|max:100
employer_name nullable|string|max:255
workplace_municipality nullable|string|max:100
works_in_municipality boolean
is_dependent boolean
is_student boolean
is_disabled boolean
disability_percentage nullable|numeric|min:0|max:100
has_reduced_mobility boolean
is_informal_caregiver boolean
monthly_declared_income nullable|numeric|min:0
annual_declared_income nullable|numeric|min:0
has_no_income boolean
no_income_reason nullable|string|max:1000
notes nullable|string|max:2000
```

## Validação de IncomeRecord

Regras mínimas:

```text
household_member_id required|exists:household_members,id
income_source_id required|exists:income_sources,id
description nullable|string|max:255
monthly_amount nullable|numeric|min:0
annual_amount nullable|numeric|min:0
reference_year nullable|integer|min:2000|max:2100
starts_at nullable|date
ends_at nullable|date|after_or_equal:starts_at
is_current boolean
is_taxable boolean
notes nullable|string|max:2000
```

Adicionar validação custom:

```text
Pelo menos monthly_amount ou annual_amount deve estar preenchido.
```

## Validação de CurrentHousingSituation

Regras mínimas:

```text
housing_status required|string|max:100
current_address nullable|string|max:255
current_postal_code nullable|string|max:20
current_city nullable|string|max:100
current_parish nullable|string|max:100
current_municipality nullable|string|max:100
resides_in_municipality boolean
residence_years_in_municipality nullable|numeric|min:0|max:120
works_in_municipality boolean
workplace_municipality nullable|string|max:100
current_housing_typology nullable|string|max:50
current_housing_rooms nullable|integer|min:0|max:20
current_housing_condition nullable|string|max:100
current_monthly_rent nullable|numeric|min:0
current_housing_expense nullable|numeric|min:0
is_overcrowded boolean
is_at_risk_of_eviction boolean
is_homeless boolean
is_temporary_accommodation boolean
is_domestic_violence_victim boolean
has_accessibility_needs boolean
has_high_rent_burden boolean
request_reason nullable|string|max:2000
additional_notes nullable|string|max:3000
```

---

# Policies e autorização

Criar ou atualizar policies:

```text
HouseholdPolicy
HouseholdMemberPolicy
IncomeRecordPolicy
CurrentHousingSituationPolicy
```

## Regras obrigatórias

- Candidato só pode ver o seu próprio agregado.
- Candidato só pode editar o seu próprio agregado.
- Candidato só pode gerir membros do seu próprio agregado.
- Candidato só pode gerir rendimentos do seu próprio agregado.
- Candidato só pode gerir situação habitacional do seu próprio registo.
- Não permitir acesso cruzado entre candidatos.
- Não permitir alteração de dados de outro `adhesion_registration_id`.
- Admin pode consultar, se a arquitetura permitir, mas não é foco desta sprint.
- Técnico municipal não deve editar estes dados nesta sprint.

---

# Rotas

Criar rotas protegidas na área do candidato.

Preferência em português:

```text
GET /area-candidato/agregado
GET /area-candidato/agregado/editar
PUT /area-candidato/agregado

GET /area-candidato/agregado/membros
GET /area-candidato/agregado/membros/criar
POST /area-candidato/agregado/membros
GET /area-candidato/agregado/membros/{member}/editar
PUT/PATCH /area-candidato/agregado/membros/{member}
DELETE /area-candidato/agregado/membros/{member}

GET /area-candidato/rendimentos
GET /area-candidato/rendimentos/criar
POST /area-candidato/rendimentos
GET /area-candidato/rendimentos/{incomeRecord}/editar
PUT/PATCH /area-candidato/rendimentos/{incomeRecord}
DELETE /area-candidato/rendimentos/{incomeRecord}

GET /area-candidato/habitacao-atual
GET /area-candidato/habitacao-atual/editar
PUT/PATCH /area-candidato/habitacao-atual
```

Nomes recomendados:

```text
candidate.household.show
candidate.household.edit
candidate.household.update

candidate.household-members.index
candidate.household-members.create
candidate.household-members.store
candidate.household-members.edit
candidate.household-members.update
candidate.household-members.destroy

candidate.income-records.index
candidate.income-records.create
candidate.income-records.store
candidate.income-records.edit
candidate.income-records.update
candidate.income-records.destroy

candidate.current-housing.show
candidate.current-housing.edit
candidate.current-housing.update
```

Middleware:

```text
auth
role:candidate
```

ou equivalente.

---

# Views / páginas

Se o projeto usa Blade, criar:

```text
resources/views/candidate/household/show.blade.php
resources/views/candidate/household/edit.blade.php

resources/views/candidate/household-members/index.blade.php
resources/views/candidate/household-members/create.blade.php
resources/views/candidate/household-members/edit.blade.php

resources/views/candidate/income-records/index.blade.php
resources/views/candidate/income-records/create.blade.php
resources/views/candidate/income-records/edit.blade.php

resources/views/candidate/current-housing/show.blade.php
resources/views/candidate/current-housing/edit.blade.php
```

Se usa Inertia/Vue/React, criar equivalentes.

---

# Atualização do dashboard do candidato

Atualizar o dashboard criado na Sprint 4 para mostrar:

```text
Estado do Registo de Adesão
Progresso geral
Progresso da área Utilizador
Progresso da área Agregado
Progresso da área Rendimentos
Progresso da área Habitação Atual
Número de membros do agregado
Rendimento mensal total declarado
Rendimento anual total declarado
Situação habitacional resumida
Campos em falta
Próximo passo recomendado
```

## Exemplo de próximos passos

Se não existir agregado:

```text
Adicione os elementos do seu agregado familiar.
```

Se existir agregado mas faltarem rendimentos:

```text
Declare os rendimentos dos elementos do agregado.
```

Se faltarem dados de habitação atual:

```text
Preencha a informação sobre a sua situação habitacional atual.
```

---

# UX obrigatória

## Estrutura por etapas

Mostrar um stepper ou navegação por separadores:

```text
1. Utilizador
2. Agregado
3. Rendimentos
4. Habitação Atual
```

## Regras UX

- Mostrar estado do registo no topo.
- Mostrar progresso.
- Mostrar campos obrigatórios.
- Mostrar erros junto aos campos.
- Usar linguagem simples.
- Usar botões claros:
    - Guardar;
    - Adicionar membro;
    - Adicionar rendimento;
    - Editar;
    - Remover;
    - Voltar.

- Confirmar antes de remover membro ou rendimento.
- Mostrar empty states claros.
- Não sobrecarregar o utilizador com tabelas difíceis em mobile.
- Em mobile, considerar cards para membros e rendimentos.

## Empty state — membros

```text
Ainda não adicionou elementos ao agregado.
Adicione os elementos que vivem consigo ou que fazem parte da sua candidatura habitacional.
```

## Empty state — rendimentos

```text
Ainda não declarou rendimentos.
Declare os rendimentos de cada elemento do agregado ou indique quando um elemento não possui rendimentos.
```

## Empty state — habitação atual

```text
Ainda não preencheu a sua situação habitacional atual.
Esta informação ajuda o município a compreender o contexto habitacional do agregado.
```

---

# Cálculos base

Implementar cálculos simples, sem substituir o motor de elegibilidade da Sprint 7.

## Cálculos permitidos

```text
Idade de cada membro
Número total de membros
Número de dependentes
Número de estudantes
Número de membros com deficiência/incapacidade declarada
Rendimento mensal total declarado
Rendimento anual total declarado
Rendimento mensal médio aproximado
Taxa de esforço atual aproximada, se houver renda atual declarada
```

## Fórmula aproximada da taxa de esforço atual

```text
Taxa de esforço atual = renda atual / rendimento mensal total x 100
```

Se rendimento mensal total for zero, não calcular e mostrar aviso.

## Aviso obrigatório

Estes cálculos devem ser apresentados como informação declarativa/preparatória, não como decisão de elegibilidade.

Copy sugerido:

```text
Os valores apresentados resultam dos dados declarados e servem apenas para preparação do registo. A elegibilidade será avaliada posteriormente de acordo com as regras do programa e do concurso.
```

---

# Auditoria

Se a auditoria da Sprint 1 existir, auditar:

```text
Criação de agregado
Atualização de agregado
Criação de membro do agregado
Atualização de membro do agregado
Remoção de membro do agregado
Criação de rendimento
Atualização de rendimento
Remoção de rendimento
Criação/atualização da situação habitacional
```

Se a auditoria não existir, não criar sistema duplicado.

Documentar como pendência.

Não guardar dados sensíveis desnecessários nos logs.

---

# RGPD e dados sensíveis

## Regras

- Não expor dados entre candidatos.
- Não incluir dados sensíveis em URLs.
- Não guardar documentos nesta sprint.
- Não guardar notas técnicas com dados excessivos.
- Não guardar logs com descrições sensíveis completas.
- Usar soft deletes quando aplicável.
- Permitir que dados removidos deixem de aparecer ao candidato.
- Preservar retenção futura para processos administrativos, quando aplicável.

## Dados sensíveis potenciais

Tratar com especial cuidado:

```text
Deficiência/incapacidade
Mobilidade reduzida
Vítima de violência doméstica
Situação sem-abrigo
Rendimentos
NIF
Documentos de identificação
Morada
```

Não exibir estes dados em zonas públicas.

Não incluir estes dados em listagens administrativas nesta sprint.

---

# Seeders e factories

Criar factories:

```text
HouseholdFactory
HouseholdMemberFactory
IncomeSourceFactory
IncomeRecordFactory
CurrentHousingSituationFactory
```

Criar ou atualizar seeders:

```text
IncomeSourceSeeder
HouseholdDemoSeeder, opcional
DatabaseSeeder
```

## Dados demo

Usar apenas dados fictícios:

```text
Candidato Demo
Agregado Demo
Membro Demo
Rendimento Demo
```

Emails fictícios devem usar:

```text
@example.test
```

Não usar dados pessoais reais.

---

# Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e segurança

```text
guest_cannot_access_household_pages
non_candidate_cannot_access_household_pages
candidate_can_access_own_household_page
candidate_cannot_access_another_candidate_household
candidate_cannot_access_another_candidate_member
candidate_cannot_access_another_candidate_income_record
candidate_cannot_access_another_candidate_housing_situation
```

## Agregado

```text
candidate_can_create_household_if_registration_exists
candidate_cannot_create_household_without_registration
candidate_can_view_own_household
candidate_can_update_own_household
household_members_count_is_updated
```

## Membros

```text
candidate_can_add_household_member
candidate_can_update_household_member
candidate_can_remove_household_member
household_requires_at_least_one_applicant_member
member_birth_date_must_be_before_today
member_disability_percentage_must_be_between_0_and_100
member_nif_must_be_unique_within_household_when_present
```

## Rendimentos

```text
candidate_can_add_income_record
candidate_can_update_income_record
candidate_can_remove_income_record
income_record_requires_income_source
income_record_requires_monthly_or_annual_amount
income_record_monthly_amount_must_be_non_negative
income_record_annual_amount_must_be_non_negative
monthly_amount_generates_annual_amount_when_missing
annual_amount_generates_monthly_amount_when_missing
household_total_monthly_income_is_calculated
household_total_annual_income_is_calculated
```

## Situação habitacional

```text
candidate_can_create_current_housing_situation
candidate_can_update_current_housing_situation
housing_status_is_required
current_rent_must_be_non_negative
residence_years_must_be_valid
housing_situation_belongs_to_candidate_registration
```

## Dashboard/progresso

```text
candidate_dashboard_shows_household_progress
candidate_dashboard_shows_missing_household_fields
candidate_dashboard_shows_income_summary
candidate_dashboard_shows_housing_situation_summary
```

## RGPD/segurança

```text
sensitive_household_data_is_not_public
candidate_cannot_mass_assign_adhesion_registration_id
candidate_cannot_mass_assign_household_id_to_foreign_household
```

## Auditoria, se existir

```text
creating_household_member_generates_audit_log
updating_income_record_generates_audit_log
updating_housing_situation_generates_audit_log
```

Se auditoria não existir, documentar teste como pendente em vez de criar teste quebrado.

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
docs/backlog/sprint-5-agregado-rendimentos-situacao-habitacional.md
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
Controllers criados
Requests criados
Policies criadas
Services criados
Views criadas
Rotas criadas
Seeders/factories criados
Testes criados
Comandos executados
Pendências para Sprint 6
```

---

# Critérios de aceitação da Sprint 5

A Sprint 5 está concluída quando:

```text
O candidato consegue aceder à área de agregado
O candidato consegue criar ou consultar o seu agregado
O candidato consegue adicionar membros do agregado
O candidato consegue editar membros do agregado
O candidato consegue remover membros do agregado quando permitido
Existe pelo menos um membro requerente
O candidato consegue declarar rendimentos por membro
O candidato consegue editar rendimentos
O candidato consegue remover rendimentos
O sistema calcula rendimento mensal total
O sistema calcula rendimento anual total
O candidato consegue declarar situação habitacional atual
O sistema mostra resumo do agregado
O sistema mostra resumo económico
O sistema mostra resumo da situação habitacional
O dashboard do candidato mostra progresso atualizado
O sistema impede acesso entre candidatos
O sistema impede mass assignment indevido
Dados sensíveis não aparecem publicamente
As páginas são responsivas e utilizáveis
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foi implementada candidatura formal
Não foi implementado motor de elegibilidade
Não foi implementada classificação
Não foi implementado upload documental avançado
```

---

# Resposta final esperada do Codex

No final da execução, responder com:

```text
1. Resumo do que foi implementado na Sprint 5
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
19. Recomendação objetiva para avançar ou não para Sprint 6
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para Sprint 6 sem validação explícita.

---

# Definition of Done

A Sprint 5 só está concluída quando o candidato consegue preencher os dados fundamentais do agregado, rendimentos e situação habitacional, com segurança, validação, resumo e proteção contra acesso indevido.

Estes dados devem ficar preparados para alimentar:

```text
Gestão documental
Elegibilidade
Candidatura formal
Classificação
Cálculo futuro de renda
```

Fim da Sprint 5.

---

# Registo de execução — 10/06/2026

## Estado

Sprint 5 implementada e validada. Não foi iniciada a Sprint 6.

## Implementado

- adaptação compatível de `households` para CRM legado e área candidata;
- `HouseholdMember`, `IncomeSource`, `IncomeRecord` e `CurrentHousingSituation`;
- enums de relação, situação profissional, fonte, situação e condição habitacional;
- services transacionais para agregado, membros, rendimentos, habitação e progresso;
- policies de ownership e requests com proteção contra mass assignment;
- rotas e páginas Blade responsivas da área candidata;
- stepper de quatro áreas, dashboard, resumos, cálculos e campos em falta;
- auditoria sem valores sensíveis;
- factories e seeder idempotente com 11 fontes de rendimento;
- testes feature da Sprint 5.

## Migration

`2026_06_10_030000_create_candidate_household_domain.php` foi aplicada incrementalmente. Os quatro agregados legados existentes foram preservados.

## Validação executada

- `php artisan migrate --pretend`: SQL revisto sem erro.
- `php artisan migrate --force`: concluído.
- `php artisan db:seed --class=IncomeSourceSeeder --force`: 11 fontes configuradas.
- `php artisan db:seed --class=SystemAccessSeeder --force`: permissões sincronizadas.
- `php artisan route:list --name=candidate`: 32 rotas candidatas.
- `php artisan test`: 58 testes, 290 asserções, sem falhas.
- `npm run build`: concluído.
- `./vendor/bin/pint`: concluído após correções mecânicas.
- `composer validate`: `composer.json` válido.
- `php artisan migrate:status`: todas as migrations, incluindo a Sprint 5, com estado `Ran`.
- validação no browser: agregado criado, requerente sincronizado, rendimento mensal/anual calculado, situação habitacional guardada e progresso a 100%.
- validação responsiva: sem overflow horizontal em `1440x900` e `390x844`; menu mobile funcional e sem erros de consola.

A captura automática de screenshot não foi produzida devido a timeout do comando CDP `Page.captureScreenshot`. A inspeção DOM, as interações e as verificações de layout/consola foram concluídas.

## Pendências para Sprint 6

- aprovar matriz de documentos por tipo de candidato/concurso;
- definir sensibilidade, retenção, formatos, tamanho máximo e política de substituição;
- confirmar storage privado e estratégia de download autorizado;
- definir estados e motivos da revisão documental;
- validar requisitos de anti-malware/checksum antes da produção.
