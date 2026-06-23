# MASTER PROMPT — EXECUÇÃO DA SPRINT 13: CÁLCULO DE RENDA, CONTRATOS E CAUÇÃO

Atua como arquiteto sénior Laravel, tech lead e product engineer.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 13 — Cálculo de Renda, Contratos e Caução
```

Esta sprint pertence à fase contratual da plataforma municipal de Arrendamento Acessível.

A Sprint 13 deve transformar uma atribuição aceite, criada na Sprint 12, num contrato formal, com cálculo de renda, caução, minuta contratual parametrizável, geração de documento contratual, validação interna, registo de assinatura/manual e ativação do contrato.

---

# 1. Regra principal

Executa apenas a Sprint 13.

Não avances para Sprint 14, Sprint 15, Sprint 16 ou qualquer sprint futura sem validação explícita.

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
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 13.

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

docs/backlog/sprint-5-agregado-rendimentos-situacao-habitacional.md
docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
docs/backlog/sprint-7-motor-elegibilidade.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
docs/backlog/sprint-10-matriz-classificacao-ranking.md
docs/backlog/sprint-11-listas-provisorias-reclamacoes-audiencia.md
docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md

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
Sistema de PDF/document generation, se existir
Sistema de storage privado, se existir

Modelo User
Modelo Program
Modelo Contest
Modelo Application
Modelo Household
Modelo HouseholdMember
Modelo IncomeRecord
Modelo CurrentHousingSituation
Modelo HousingUnit
Modelo ContestHousingUnit
Modelo Allocation
Modelo AllocationOffer
Modelo AllocationRun
Modelo Contract, se existir
Modelo Payment, se existir
Modelo DocumentSubmission
Modelo OfficialNotification, se existir
Modelo AuditLog, se existir
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
Contract
LeaseContract
RentCalculation
RentRuleSet
RentRule
ContractTemplate
ContractClause
ContractDocument
ContractParty
ContractDeposit
Deposit
ContractValidation
ContractSignature
ContractStatusHistory
```

reaproveitar ou adaptar com compatibilidade.

Não apagar contratos existentes.

Não apagar atribuições existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY ou credenciais.

---

# 5. Dependências obrigatórias

Esta sprint depende obrigatoriamente de:

```text
Sprint 5 — Agregado, Rendimentos e Situação Habitacional
Sprint 8 — Candidaturas e Submissão Formal
Sprint 12 — Atribuição de Habitações
```

Depende preferencialmente de:

```text
Sprint 6 — Gestão Documental Avançada
Sprint 7 — Motor de Elegibilidade
Sprint 9 — Workflow Administrativo
Sprint 11 — Listas definitivas
Sistema de notificações internas
Sistema de auditoria
Sistema de geração de PDF
```

## Dependência da Sprint 5

Se não existirem dados de agregado e rendimentos, não é possível calcular renda com base no agregado.

Se `Household`, `HouseholdMember` ou `IncomeRecord` não existirem, interrompe a implementação funcional do motor de renda e informa:

```text
A Sprint 13 depende da Sprint 5 — Agregado, Rendimentos e Situação Habitacional.
```

## Dependência da Sprint 8

Se `Application` não existir, interrompe a implementação funcional e informa:

```text
A Sprint 13 depende da Sprint 8 — Candidaturas e Submissão Formal.
```

## Dependência da Sprint 12

Se `Allocation` ou equivalente não existir, interrompe a implementação funcional e informa:

```text
A Sprint 13 depende da Sprint 12 — Atribuição de Habitações.
```

A Sprint 13 deve criar contratos apenas para atribuições em estado:

```text
accepted
ready_for_contract
```

ou equivalente documentado.

Não criar atribuição simplificada nesta sprint.

---

# 6. Validação jurídica obrigatória

Esta sprint tem impacto jurídico e contratual.

Não hardcodar fórmulas legais, limites de renda, caução, prazos contratuais ou cláusulas jurídicas sem validação.

Todas as regras de renda, caução, prazo e cláusulas devem ser:

```text
Configuráveis por programa/concurso;
Ou documentadas como pendentes de validação jurídica;
Ou implementadas de forma conservadora e reversível.
```

Regras obrigatórias:

```text
Cálculo de renda deve guardar snapshot dos dados usados.
Renda manual deve exigir justificação.
Alterações de renda devem ficar auditadas.
Minutas contratuais devem ser parametrizáveis.
Cláusulas devem ser configuráveis por programa/concurso.
Contrato gerado deve preservar versão da minuta usada.
Caução deve ficar registada separadamente.
Ativação do contrato deve exigir validação interna.
Ativação do contrato deve alterar estado da habitação.
Assinatura digital externa não deve ser simulada.
PDF deve ser gerado apenas com dados validados.
```

Textos contratuais devem ser tratados como minutas configuráveis e sujeitos a validação jurídica.

---

# 7. Objetivo da implementação

Implementar o módulo contratual completo.

A plataforma deve permitir que o Município:

```text
Calcule a renda aplicável ao agregado
Calcule a taxa de esforço
Calcule renda mínima
Calcule renda máxima
Aplique regras configuráveis por programa/concurso
Permita revisão manual justificada da renda
Crie minuta contratual parametrizável
Configure cláusulas por programa/concurso
Crie contratos com dados completos
Gere contrato em PDF ou documento equivalente
Registe validação interna
Registe assinatura interna/manual
Registe caução
Controle estados do contrato
Ative contrato
Atualize o estado da habitação após ativação
Prepare a gestão contratual futura
```

---

# 8. Âmbito incluído

Implementar:

```text
Motor de cálculo de renda
Regras configuráveis de renda
Taxa de esforço
Renda mínima
Renda máxima
Renda aplicável
Snapshot de rendimentos usados
Revisão manual justificada
Histórico de cálculo de renda

Minutas contratuais parametrizáveis
Cláusulas por programa/concurso
Dados contratuais completos
Partes do contrato
Habitação contratada
Prazo do contrato
Datas de início e fim
Condições especiais
Caução
Geração de contrato
Geração de PDF ou HTML imprimível, conforme infraestrutura
Validação interna
Registo de assinatura interna/manual
Estados do contrato
Ativação do contrato
Atualização do estado da habitação
Notificações internas
Auditoria
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
Cobrança real de renda
Faturação
Emissão de recibos
Integração bancária
Referências Multibanco
Débito direto
Pagamento online
Contabilidade
Comunicação à Autoridade Tributária
Integração com AT
Integração com Segurança Social
Integração com Autenticação.GOV
Assinatura digital qualificada
Assinatura remota
Reconhecimento presencial
Renovação contratual avançada
Revisão periódica automática de renda
Suspensão operacional avançada
Cessação contratual avançada
Gestão de incumprimentos
Manutenção pós-contrato
Vistorias
Chaves
Entrega de habitação
```

Podem ser criados pontos de integração para sprints futuras, mas não implementar funcionalidades fora do âmbito.

---

# 10. Fluxo funcional obrigatório

O fluxo da Sprint 13 deve ser:

```text
Atribuição aceite
→ Verificação de readiness para contrato
→ Resolução de regras de renda
→ Snapshot do agregado/rendimentos/habitação
→ Cálculo de renda
→ Validação ou revisão manual da renda
→ Resolução da minuta contratual
→ Seleção de cláusulas aplicáveis
→ Criação do contrato em preparação
→ Registo de caução
→ Geração do contrato
→ Validação interna
→ Emissão do contrato
→ Assinatura/registo interno
→ Ativação do contrato
→ Atualização do estado da habitação
→ Preparação para gestão contratual
```

A Sprint 13 não deve cobrar renda nem gerar recibos.

A Sprint 13 cria o contrato formal e prepara a fase de gestão contratual.

---

# 11. Estados obrigatórios

## RentRuleSetStatus

```text
draft
active
archived
```

## RentCalculationStatus

```text
draft
calculated
requires_manual_review
manually_reviewed
approved
rejected
superseded
cancelled
```

## RentManualReviewStatus

```text
pending
approved
rejected
cancelled
```

## ContractTemplateStatus

```text
draft
active
archived
```

## ContractClauseStatus

```text
draft
active
archived
```

## ContractStatus

```text
preparation
issued
signed
active
suspended
terminated
renewed
cancelled
expired
```

## ContractDocumentStatus

```text
draft
generated
issued
signed
archived
cancelled
```

## ContractValidationStatus

```text
pending
approved
rejected
cancelled
```

## ContractSignatureStatus

```text
pending
signed
refused
waived
cancelled
```

## DepositStatus

```text
not_required
pending
requested
paid
waived
partially_refunded
refunded
retained
cancelled
```

## HousingContractStatus

Quando contrato é ativado, a habitação deve poder passar para estado equivalente a:

```text
contracted
occupied
leased
active_contract
```

Usar o estado existente no projeto se já houver modelo definido.

---

# 12. Modelo de dados a implementar

## 12.1 RentRuleSet

Criar entidade:

```text
RentRuleSet
```

Tabela:

```text
rent_rule_sets
```

Objetivo:

```text
Configurar regras de cálculo de renda por programa/concurso.
```

Campos mínimos:

```text
id
program_id
contest_id

name
description
status

calculation_method
income_period
income_basis

effort_rate_percentage
minimum_rent
maximum_rent
minimum_effort_rate_percentage
maximum_effort_rate_percentage

deposit_months
minimum_deposit
maximum_deposit

rounding_mode
rounding_precision

effective_from
effective_until

requires_manual_approval
allow_manual_override

created_by
updated_by

created_at
updated_at
deleted_at
```

Valores recomendados para `calculation_method`:

```text
fixed_percentage_of_income
effort_rate
income_bracket
fixed_amount
manual
custom
```

Valores recomendados para `income_period`:

```text
monthly
annual
last_3_months_average
last_6_months_average
last_12_months_average
validated_snapshot
```

Valores recomendados para `income_basis`:

```text
gross_income
net_income
declared_income
validated_income
eligible_income
manual_basis
```

Regras:

```text
Rule set de concurso prevalece sobre rule set de programa.
Apenas rule sets active são usados para novos contratos.
Não apagar rule set usado num cálculo.
Usar soft deletes.
```

---

## 12.2 RentRule

Criar entidade:

```text
RentRule
```

Tabela:

```text
rent_rules
```

Objetivo:

```text
Permitir escalões e regras específicas de renda dentro de um RentRuleSet.
```

Campos mínimos:

```text
id
rent_rule_set_id

name
description
rule_type
operator
minimum_value
maximum_value
fixed_amount
percentage
minimum_result
maximum_result
priority_order
is_active

created_at
updated_at
deleted_at
```

Valores recomendados para `rule_type`:

```text
income_bracket
household_size
typology
special_condition
minimum_rent
maximum_rent
deposit
manual_review
other
```

---

## 12.3 RentCalculation

Criar entidade:

```text
RentCalculation
```

Tabela:

```text
rent_calculations
```

Objetivo:

```text
Guardar cada cálculo de renda associado a uma atribuição/contrato.
```

Campos mínimos:

```text
id
rent_rule_set_id
allocation_id
application_id
user_id
household_id
housing_unit_id
contest_housing_unit_id
contract_id

status

calculation_method
income_basis
income_period

monthly_household_income
annual_household_income
monthly_income_per_capita
annual_income_per_capita

calculated_effort_rate_percentage
configured_effort_rate_percentage

base_rent
minimum_rent
maximum_rent
applicable_rent
manual_rent

deposit_amount

calculated_at
calculated_by

approved_at
approved_by

superseded_by_rent_calculation_id

summary
technical_notes
snapshot

created_at
updated_at
deleted_at
```

Regras:

```text
contract_id pode ser nullable até contrato ser criado.
snapshot deve guardar os dados usados no cálculo.
Não recalcular silenciosamente sem criar novo registo ou nova versão.
manual_rent só pode ser preenchida por revisão autorizada.
Usar soft deletes.
```

---

## 12.4 RentCalculationDetail

Criar entidade:

```text
RentCalculationDetail
```

Tabela:

```text
rent_calculation_details
```

Objetivo:

```text
Guardar detalhe das regras aplicadas no cálculo de renda.
```

Campos mínimos:

```text
id
rent_calculation_id
rent_rule_id

code
name
rule_type
result
input_value
output_value
message
technical_message

created_at
updated_at
```

Valores recomendados para `result`:

```text
applied
not_applicable
missing_data
requires_manual_review
failed
```

---

## 12.5 RentManualReview

Criar entidade:

```text
RentManualReview
```

Tabela:

```text
rent_manual_reviews
```

Objetivo:

```text
Registar revisão manual justificada da renda calculada.
```

Campos mínimos:

```text
id
rent_calculation_id
requested_by
reviewed_by

status
original_rent
proposed_rent
approved_rent

reason
legal_basis
internal_notes

requested_at
reviewed_at

created_at
updated_at
deleted_at
```

Regras:

```text
reason obrigatório.
Alteração manual deve exigir permissão.
Alteração manual deve ficar auditada.
Não permitir revisão manual sem justificação.
```

---

## 12.6 ContractTemplate

Criar entidade:

```text
ContractTemplate
```

Tabela:

```text
contract_templates
```

Objetivo:

```text
Guardar minutas contratuais parametrizáveis.
```

Campos mínimos:

```text
id
program_id
contest_id

name
description
status
version_number

template_body
header_html
footer_html

effective_from
effective_until

created_by
updated_by

created_at
updated_at
deleted_at
```

Regras:

```text
Template de concurso prevalece sobre template de programa.
Apenas templates active devem ser usados para novos contratos.
Não alterar template usado por contrato já emitido; criar nova versão.
template_body deve suportar placeholders.
```

Placeholders mínimos esperados:

```text
{{contract.number}}
{{contract.start_date}}
{{contract.end_date}}
{{contract.duration_months}}
{{tenant.name}}
{{tenant.identification_number}}
{{tenant.tax_number}}
{{tenant.address}}
{{housing.address}}
{{housing.typology}}
{{housing.floor}}
{{housing.area}}
{{rent.amount}}
{{rent.effort_rate}}
{{deposit.amount}}
{{program.name}}
{{contest.name}}
{{municipality.name}}
```

---

## 12.7 ContractClause

Criar entidade:

```text
ContractClause
```

Tabela:

```text
contract_clauses
```

Objetivo:

```text
Guardar cláusulas parametrizáveis por programa/concurso.
```

Campos mínimos:

```text
id
program_id
contest_id

code
title
body
category
status
is_mandatory
sort_order

effective_from
effective_until

created_by
updated_by

created_at
updated_at
deleted_at
```

Categorias recomendadas:

```text
rent
deposit
duration
use_of_property
maintenance
termination
renewal
special_condition
data_protection
general
```

---

## 12.8 ContractTemplateClause

Criar entidade pivot:

```text
ContractTemplateClause
```

Tabela:

```text
contract_template_clauses
```

Campos mínimos:

```text
id
contract_template_id
contract_clause_id
sort_order
is_active
created_at
updated_at
```

---

## 12.9 LeaseContract

Criar entidade:

```text
LeaseContract
```

Tabela:

```text
lease_contracts
```

Se já existir `Contract`, adaptar o model existente em vez de criar duplicado.

Campos mínimos:

```text
id
contract_number

program_id
contest_id
application_id
allocation_id
allocation_offer_id
user_id
household_id
housing_unit_id
contest_housing_unit_id

rent_calculation_id
contract_template_id

status

tenant_name
tenant_identification_number
tenant_tax_number
tenant_email
tenant_phone
tenant_address

landlord_name
landlord_tax_number
landlord_address
landlord_representative

housing_address
housing_typology
housing_floor
housing_area
housing_description

start_date
end_date
duration_months
renewal_allowed
renewal_terms

monthly_rent
deposit_amount
payment_day
payment_method_description

special_conditions
internal_notes

issued_at
issued_by

signed_at
signed_by
signature_notes

activated_at
activated_by

suspended_at
terminated_at
renewed_at
cancelled_at

created_by
updated_by

created_at
updated_at
deleted_at
```

Regras:

```text
contract_number obrigatório e único.
allocation_id obrigatório.
Não criar contrato para allocation não aceite.
Contrato ativo deve ter renda definida.
Contrato ativo deve ter habitação definida.
Contrato ativo deve ter datas válidas.
Contrato ativo deve atualizar estado da habitação.
Mudança de estado deve passar por service.
Usar soft deletes.
```

---

## 12.10 LeaseContractParty

Criar entidade:

```text
LeaseContractParty
```

Tabela:

```text
lease_contract_parties
```

Objetivo:

```text
Guardar partes do contrato e permitir múltiplos titulares/representantes quando necessário.
```

Campos mínimos:

```text
id
lease_contract_id
user_id

party_type
name
identification_number
tax_number
email
phone
address
representative_name
representative_role

sort_order
created_at
updated_at
deleted_at
```

Valores de `party_type`:

```text
tenant
co_tenant
landlord
representative
guarantor
other
```

---

## 12.11 LeaseContractClause

Criar entidade:

```text
LeaseContractClause
```

Tabela:

```text
lease_contract_clauses
```

Objetivo:

```text
Guardar snapshot das cláusulas efetivamente usadas no contrato.
```

Campos mínimos:

```text
id
lease_contract_id
contract_clause_id

code
title
body
category
sort_order

created_at
updated_at
```

Regras:

```text
Guardar snapshot do texto da cláusula.
Não depender apenas da cláusula ativa após emissão.
```

---

## 12.12 ContractDeposit

Criar entidade:

```text
ContractDeposit
```

Tabela:

```text
contract_deposits
```

Objetivo:

```text
Registar caução associada ao contrato.
```

Campos mínimos:

```text
id
lease_contract_id
application_id
allocation_id
user_id

status
amount
currency
calculation_basis
due_date

requested_at
paid_at
waived_at
refunded_at
retained_at
cancelled_at

payment_reference
receipt_reference
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
Registar caução mesmo que esteja not_required ou waived.
Não implementar cobrança real nesta sprint.
Não criar recibos fiscais nesta sprint.
```

---

## 12.13 LeaseContractDocument

Criar entidade:

```text
LeaseContractDocument
```

Tabela:

```text
lease_contract_documents
```

Objetivo:

```text
Guardar documentos gerados do contrato.
```

Campos mínimos:

```text
id
lease_contract_id

status
document_type
version_number

title
html_content
storage_disk
storage_path
mime_type
file_size
checksum

generated_by
generated_at
issued_at
signed_at
archived_at

created_at
updated_at
deleted_at
```

Valores de `document_type`:

```text
contract_html
contract_pdf
annex
signature_page
other
```

Regras:

```text
Guardar PDF em storage privado.
Se PDF não puder ser gerado, guardar HTML imprimível e documentar pendência.
Não expor storage_path ao candidato.
Download deve passar por controller autorizado.
```

---

## 12.14 LeaseContractValidation

Criar entidade:

```text
LeaseContractValidation
```

Tabela:

```text
lease_contract_validations
```

Objetivo:

```text
Registar validação interna do contrato antes da emissão/ativação.
```

Campos mínimos:

```text
id
lease_contract_id
validated_by

status
validation_type
summary
rejection_reason
internal_notes

validated_at
created_at
updated_at
deleted_at
```

Valores de `validation_type`:

```text
legal
financial
administrative
technical
final
```

---

## 12.15 LeaseContractSignature

Criar entidade:

```text
LeaseContractSignature
```

Tabela:

```text
lease_contract_signatures
```

Objetivo:

```text
Registar assinatura ou validação manual/interna.
```

Campos mínimos:

```text
id
lease_contract_id
user_id

signature_role
status
signed_by_name
signed_at
signature_method
signature_reference
notes

created_at
updated_at
deleted_at
```

Valores de `signature_role`:

```text
tenant
landlord
representative
witness
internal_validator
```

Valores de `signature_method`:

```text
manual
in_person
uploaded_signed_document
internal_validation
digital_pending
other
```

Regras:

```text
Não simular assinatura digital qualificada.
Não marcar assinatura digital externa sem integração real.
```

---

## 12.16 LeaseContractStatusHistory

Criar entidade:

```text
LeaseContractStatusHistory
```

Tabela:

```text
lease_contract_status_histories
```

Campos mínimos:

```text
id
lease_contract_id
from_status
to_status
changed_by
reason
created_at
```

Regras:

```text
Criar registo sempre que o estado do contrato muda.
Não permitir apagar histórico.
```

---

# 13. Enums a criar

Criar, se a versão do PHP permitir:

```text
App\Enums\RentRuleSetStatus
App\Enums\RentCalculationMethod
App\Enums\RentCalculationStatus
App\Enums\RentCalculationResult
App\Enums\RentManualReviewStatus
App\Enums\ContractTemplateStatus
App\Enums\ContractClauseStatus
App\Enums\ContractStatus
App\Enums\ContractDocumentStatus
App\Enums\ContractDocumentType
App\Enums\ContractValidationStatus
App\Enums\ContractValidationType
App\Enums\ContractSignatureStatus
App\Enums\ContractSignatureRole
App\Enums\ContractSignatureMethod
App\Enums\ContractPartyType
App\Enums\DepositStatus
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 14. Relações obrigatórias

## Program

```text
hasMany RentRuleSet
hasMany ContractTemplate
hasMany ContractClause
hasMany LeaseContract
```

## Contest

```text
hasMany RentRuleSet
hasMany ContractTemplate
hasMany ContractClause
hasMany LeaseContract
```

## Application

Adicionar:

```text
hasMany RentCalculation
hasMany LeaseContract
hasMany ContractDeposit
```

## Allocation

Adicionar:

```text
hasMany RentCalculation
hasOne LeaseContract
hasOne activeLeaseContract
hasOne ContractDeposit
```

## HousingUnit

Adicionar:

```text
hasMany LeaseContract
hasOne activeLeaseContract
```

## Household

Adicionar:

```text
hasMany RentCalculation
hasMany LeaseContract
```

## RentRuleSet

```text
belongsTo Program nullable
belongsTo Contest nullable
belongsTo User as createdBy
belongsTo User as updatedBy
hasMany RentRule
hasMany RentCalculation
```

## RentCalculation

```text
belongsTo RentRuleSet
belongsTo Allocation
belongsTo Application
belongsTo User as candidate
belongsTo Household nullable
belongsTo HousingUnit
belongsTo ContestHousingUnit nullable
belongsTo LeaseContract nullable
belongsTo User as calculatedBy
belongsTo User as approvedBy nullable
hasMany RentCalculationDetail
hasMany RentManualReview
```

## ContractTemplate

```text
belongsTo Program nullable
belongsTo Contest nullable
belongsTo User as createdBy
belongsTo User as updatedBy
hasMany ContractTemplateClause
hasMany ContractClause through pivot
hasMany LeaseContract
```

## ContractClause

```text
belongsTo Program nullable
belongsTo Contest nullable
belongsTo User as createdBy
belongsTo User as updatedBy
hasMany ContractTemplateClause
```

## LeaseContract

```text
belongsTo Program nullable
belongsTo Contest nullable
belongsTo Application
belongsTo Allocation
belongsTo AllocationOffer nullable
belongsTo User as candidate
belongsTo Household nullable
belongsTo HousingUnit
belongsTo ContestHousingUnit nullable
belongsTo RentCalculation
belongsTo ContractTemplate
belongsTo User as issuedBy nullable
belongsTo User as signedBy nullable
belongsTo User as activatedBy nullable
belongsTo User as createdBy
belongsTo User as updatedBy

hasMany LeaseContractParty
hasMany LeaseContractClause
hasMany LeaseContractDocument
hasMany LeaseContractValidation
hasMany LeaseContractSignature
hasMany LeaseContractStatusHistory
hasOne ContractDeposit
```

## ContractDeposit

```text
belongsTo LeaseContract
belongsTo Application
belongsTo Allocation
belongsTo User as candidate
belongsTo User as createdBy
belongsTo User as updatedBy
```

---

# 15. Services obrigatórios

Criar:

```text
App\Services\Contracts\RentRuleSetResolver
App\Services\Contracts\RentCalculationService
App\Services\Contracts\RentEffortRateService
App\Services\Contracts\RentSnapshotService
App\Services\Contracts\RentManualReviewService

App\Services\Contracts\ContractTemplateResolver
App\Services\Contracts\ContractPlaceholderService
App\Services\Contracts\ContractClauseService
App\Services\Contracts\LeaseContractService
App\Services\Contracts\LeaseContractNumberService
App\Services\Contracts\LeaseContractDocumentService
App\Services\Contracts\LeaseContractPdfService
App\Services\Contracts\LeaseContractValidationService
App\Services\Contracts\LeaseContractSignatureService
App\Services\Contracts\LeaseContractStatusService
App\Services\Contracts\ContractDepositService
App\Services\Contracts\ContractActivationService
App\Services\Contracts\ContractNotificationService
```

## RentRuleSetResolver

Responsável por:

```text
Resolver regras de renda por concurso
Resolver regras de renda por programa
Dar prioridade a regras de concurso
Usar apenas regras ativas
Verificar vigência
Impedir cálculo sem regra aplicável, salvo modo manual autorizado
```

## RentSnapshotService

Responsável por:

```text
Criar snapshot do agregado
Criar snapshot dos rendimentos declarados/validados
Criar snapshot da habitação
Criar snapshot da atribuição
Criar snapshot da regra de renda
Evitar recalcular com dados futuros sem histórico
```

O snapshot deve incluir, no mínimo:

```text
Número de membros do agregado
Rendimentos mensais considerados
Rendimentos anuais considerados
Origem dos rendimentos
Data de cálculo
Habitação atribuída
Tipologia
Regra de renda usada
```

Não guardar documentos completos no snapshot.

Não guardar paths internos.

## RentEffortRateService

Responsável por:

```text
Calcular taxa de esforço
Validar divisão por zero
Tratar ausência de rendimento
Devolver resultado seguro quando dados estão incompletos
```

Fórmula base:

```text
taxa_esforco = renda_aplicavel / rendimento_mensal_agregado * 100
```

Se rendimento mensal for zero ou inexistente, devolver:

```text
requires_manual_review
```

e não falhar silenciosamente.

## RentCalculationService

Responsável por:

```text
Receber allocation aceite
Resolver regra de renda
Obter snapshot de dados
Calcular rendimento mensal
Calcular rendimento anual
Calcular rendimento per capita
Calcular renda base
Aplicar renda mínima
Aplicar renda máxima
Calcular renda aplicável
Calcular caução prevista
Calcular taxa de esforço
Criar RentCalculation
Criar RentCalculationDetail
Marcar requires_manual_review quando faltar dado
Permitir aprovação do cálculo
```

Regra base recomendada, configurável:

```text
base_rent = rendimento_mensal_agregado * effort_rate_percentage / 100
applicable_rent = max(minimum_rent, min(base_rent, maximum_rent))
```

Não hardcodar percentagens, rendas mínimas ou máximas.

## RentManualReviewService

Responsável por:

```text
Criar pedido de revisão manual
Guardar renda original
Guardar renda proposta
Exigir justificação
Exigir base legal quando aplicável
Aprovar renda manual
Rejeitar renda manual
Atualizar cálculo aprovado
Auditar alteração
```

## ContractTemplateResolver

Responsável por:

```text
Resolver minuta contratual por concurso
Resolver minuta contratual por programa
Dar prioridade à minuta de concurso
Usar apenas templates ativos
Verificar vigência
Impedir emissão sem minuta aplicável
```

## ContractPlaceholderService

Responsável por:

```text
Mapear placeholders para dados reais
Validar placeholders obrigatórios
Impedir placeholders não resolvidos
Escapar dados sensíveis quando necessário
Gerar HTML final do contrato
```

## ContractClauseService

Responsável por:

```text
Resolver cláusulas aplicáveis
Ordenar cláusulas
Aplicar cláusulas obrigatórias
Aplicar cláusulas por condição especial
Criar snapshot das cláusulas no contrato
```

## LeaseContractService

Responsável por:

```text
Criar contrato a partir de allocation aceite
Validar readiness da allocation
Associar cálculo de renda
Associar minuta contratual
Criar partes contratuais
Criar snapshot da habitação
Criar cláusulas do contrato
Criar caução
Atualizar estado para preparation
Emitir contrato
Cancelar contrato quando permitido
```

Não ativar contrato automaticamente sem validação.

## LeaseContractNumberService

Responsável por:

```text
Gerar número único de contrato
Usar prefixo configurável quando existir
Evitar colisões
Permitir sequência por ano/programa/concurso
```

Exemplo permitido:

```text
CT-HAB-2026-000123
```

Não usar NIF, email ou telefone no número do contrato.

## LeaseContractDocumentService

Responsável por:

```text
Gerar documento HTML do contrato
Criar LeaseContractDocument
Versionar documentos gerados
Guardar conteúdo gerado
Guardar metadata
Permitir download seguro
```

## LeaseContractPdfService

Responsável por:

```text
Gerar PDF do contrato se existir infraestrutura
Guardar PDF em storage privado
Gerar checksum
Registar tamanho e mime type
Não expor path interno
```

Se não existir biblioteca PDF:

```text
Criar HTML imprimível
Documentar pendência da geração PDF
Não afirmar que PDF foi gerado
```

Se for necessário instalar biblioteca PDF, só o fazer se for compatível com a stack e documentar alteração no composer.

## LeaseContractValidationService

Responsável por:

```text
Criar validação interna
Aprovar contrato
Rejeitar contrato com motivo
Impedir emissão sem validação quando configurado
Auditar validação
```

## LeaseContractSignatureService

Responsável por:

```text
Registar assinatura manual/interna
Registar assinatura do arrendatário
Registar assinatura do representante
Registar documento assinado carregado, se sistema documental existir
Não simular assinatura digital externa
```

## LeaseContractStatusService

Responsável por:

```text
Controlar transições de estado
Criar status history
Impedir saltos inválidos
Impedir edição indevida de contrato ativo
Bloquear ativação sem validação mínima
```

Transições mínimas recomendadas:

```text
preparation → issued
issued → signed
signed → active
active → suspended
suspended → active
active → terminated
active → renewed
preparation → cancelled
issued → cancelled
signed → cancelled
active → expired
```

## ContractDepositService

Responsável por:

```text
Criar registo de caução
Calcular valor previsto
Marcar caução como requested
Marcar caução como paid, se registo manual for permitido
Marcar caução como waived com justificação
Marcar caução como refunded/retained em sprints futuras
Auditar alterações
```

Não implementar pagamento real.

Não emitir recibo fiscal.

## ContractActivationService

Responsável por:

```text
Validar contrato
Validar renda
Validar caução conforme regra
Validar assinatura/registo interno
Ativar contrato
Atualizar estado da habitação
Atualizar estado da allocation para ready_for_contract ou contract_active, se existir
Criar histórico
Criar notificação interna
```

Ao ativar contrato:

```text
LeaseContract.status = active
activated_at preenchido
HousingUnit.status atualizado para estado contratual/ocupado equivalente
Allocation.status preservado como ready_for_contract ou atualizado conforme modelo existente
```

## ContractNotificationService

Responsável por:

```text
Criar notificação interna de contrato em preparação
Criar notificação de contrato emitido
Criar notificação de contrato assinado
Criar notificação de contrato ativo
Criar notificação de caução requerida
Usar OfficialNotification se existir
Não enviar email/SMS real sem integração segura
```

---

# 16. Controllers obrigatórios

Criar controllers conforme a arquitetura existente.

## Backoffice

Namespace recomendado:

```text
App\Http\Controllers\Backoffice
```

Controllers:

```text
Backoffice\RentRuleSetController
Backoffice\RentRuleController
Backoffice\RentCalculationController
Backoffice\RentManualReviewController

Backoffice\ContractTemplateController
Backoffice\ContractClauseController
Backoffice\LeaseContractController
Backoffice\LeaseContractDocumentController
Backoffice\LeaseContractValidationController
Backoffice\LeaseContractSignatureController
Backoffice\ContractDepositController
```

## Área do candidato

Namespace recomendado:

```text
App\Http\Controllers\Candidate
```

Controllers:

```text
Candidate\LeaseContractController
Candidate\LeaseContractDocumentController
Candidate\ContractDepositController
```

O candidato pode consultar contratos próprios e documentos autorizados.

O candidato não pode alterar renda, caução ou contrato.

---

# 17. Form Requests obrigatórios

Criar:

```text
StoreRentRuleSetRequest
UpdateRentRuleSetRequest
StoreRentRuleRequest
UpdateRentRuleRequest
CalculateRentRequest
ApproveRentCalculationRequest
RejectRentCalculationRequest
StoreRentManualReviewRequest
ApproveRentManualReviewRequest
RejectRentManualReviewRequest

StoreContractTemplateRequest
UpdateContractTemplateRequest
StoreContractClauseRequest
UpdateContractClauseRequest

StoreLeaseContractRequest
UpdateLeaseContractRequest
IssueLeaseContractRequest
ActivateLeaseContractRequest
SuspendLeaseContractRequest
TerminateLeaseContractRequest
CancelLeaseContractRequest

GenerateLeaseContractDocumentRequest
ValidateLeaseContractRequest
RejectLeaseContractValidationRequest
StoreLeaseContractSignatureRequest

UpdateContractDepositRequest
MarkContractDepositPaidRequest
WaiveContractDepositRequest
```

## StoreRentRuleSetRequest

```text
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
name required|string|max:255
description nullable|string|max:3000
status required|string|max:100
calculation_method required|string|max:100
income_period required|string|max:100
income_basis required|string|max:100
effort_rate_percentage nullable|numeric|min:0|max:100
minimum_rent nullable|numeric|min:0
maximum_rent nullable|numeric|min:0
minimum_effort_rate_percentage nullable|numeric|min:0|max:100
maximum_effort_rate_percentage nullable|numeric|min:0|max:100
deposit_months nullable|numeric|min:0|max:24
minimum_deposit nullable|numeric|min:0
maximum_deposit nullable|numeric|min:0
rounding_mode nullable|string|max:100
rounding_precision nullable|integer|min:0|max:2
effective_from nullable|date
effective_until nullable|date|after_or_equal:effective_from
requires_manual_approval boolean
allow_manual_override boolean
```

Regra adicional:

```text
Deve existir program_id ou contest_id.
maximum_rent deve ser maior ou igual a minimum_rent quando ambos existirem.
```

## CalculateRentRequest

```text
allocation_id required|exists:allocations,id
rent_rule_set_id nullable|exists:rent_rule_sets,id
notes nullable|string|max:3000
```

Regra adicional:

```text
Allocation deve estar accepted ou ready_for_contract.
```

## StoreRentManualReviewRequest

```text
rent_calculation_id required|exists:rent_calculations,id
proposed_rent required|numeric|min:0
reason required|string|min:10|max:5000
legal_basis nullable|string|max:3000
internal_notes nullable|string|max:3000
```

## StoreContractTemplateRequest

```text
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
name required|string|max:255
description nullable|string|max:3000
status required|string|max:100
version_number nullable|integer|min:1
template_body required|string|min:50
header_html nullable|string|max:10000
footer_html nullable|string|max:10000
effective_from nullable|date
effective_until nullable|date|after_or_equal:effective_from
```

Regra adicional:

```text
Deve existir program_id ou contest_id.
```

## StoreContractClauseRequest

```text
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
code required|string|max:100
title required|string|max:255
body required|string|min:10|max:20000
category required|string|max:100
status required|string|max:100
is_mandatory boolean
sort_order nullable|integer|min:0
effective_from nullable|date
effective_until nullable|date|after_or_equal:effective_from
```

## StoreLeaseContractRequest

```text
allocation_id required|exists:allocations,id
rent_calculation_id required|exists:rent_calculations,id
contract_template_id required|exists:contract_templates,id
start_date required|date
end_date required|date|after:start_date
duration_months nullable|integer|min:1|max:600
monthly_rent required|numeric|min:0
deposit_amount nullable|numeric|min:0
payment_day nullable|integer|min:1|max:31
special_conditions nullable|string|max:10000
internal_notes nullable|string|max:5000
```

## ActivateLeaseContractRequest

```text
lease_contract_id required|exists:lease_contracts,id
activation_reason nullable|string|max:3000
confirm_activation accepted
```

## MarkContractDepositPaidRequest

```text
contract_deposit_id required|exists:contract_deposits,id
paid_at required|date
payment_reference nullable|string|max:255
receipt_reference nullable|string|max:255
notes nullable|string|max:3000
```

---

# 18. Policies obrigatórias

Criar:

```text
RentRuleSetPolicy
RentRulePolicy
RentCalculationPolicy
RentManualReviewPolicy
ContractTemplatePolicy
ContractClausePolicy
LeaseContractPolicy
LeaseContractDocumentPolicy
LeaseContractValidationPolicy
LeaseContractSignaturePolicy
ContractDepositPolicy
```

## Regras para candidato

```text
Candidato só vê os seus próprios contratos.
Candidato só descarrega documentos contratuais próprios e autorizados.
Candidato só vê caução do seu próprio contrato.
Candidato não calcula renda.
Candidato não altera renda.
Candidato não altera caução.
Candidato não altera estado do contrato.
Candidato não ativa contrato.
Candidato não valida contrato.
Candidato não acede ao backoffice.
```

## Regras para técnico municipal

```text
Pode consultar contratos conforme permissão.
Pode calcular renda se autorizado.
Pode preparar contrato.
Pode gerar documento contratual.
Pode propor revisão manual.
Pode registar validação interna se autorizado.
Não ativa contrato sem permissão específica.
Não altera regras de renda sem permissão.
```

## Regras para gestor financeiro

```text
Pode consultar renda e caução.
Pode validar caução.
Pode marcar caução como paga/dispensada se autorizado.
Não altera contrato jurídico sem permissão.
```

## Regras para admin

```text
Pode gerir regras de renda.
Pode gerir minutas.
Pode gerir cláusulas.
Pode criar e emitir contratos.
Pode validar e ativar contratos.
Pode gerir caução conforme permissões.
```

## Regras para auditor

```text
Pode consultar cálculo, snapshots, contrato e histórico.
Pode consultar caução.
Não pode alterar renda.
Não pode alterar contrato.
Não pode ativar contrato.
```

---

# 19. Rotas obrigatórias

## Backoffice

Criar, preferencialmente:

```text
GET /backoffice/contracts/rent-rule-sets
GET /backoffice/contracts/rent-rule-sets/create
POST /backoffice/contracts/rent-rule-sets
GET /backoffice/contracts/rent-rule-sets/{rentRuleSet}
GET /backoffice/contracts/rent-rule-sets/{rentRuleSet}/edit
PUT/PATCH /backoffice/contracts/rent-rule-sets/{rentRuleSet}
POST /backoffice/contracts/rent-rule-sets/{rentRuleSet}/activate
POST /backoffice/contracts/rent-rule-sets/{rentRuleSet}/archive
POST /backoffice/contracts/rent-rule-sets/{rentRuleSet}/duplicate

GET /backoffice/contracts/rent-rules
GET /backoffice/contracts/rent-rules/create
POST /backoffice/contracts/rent-rules
GET /backoffice/contracts/rent-rules/{rentRule}/edit
PUT/PATCH /backoffice/contracts/rent-rules/{rentRule}

GET /backoffice/contracts/rent-calculations
GET /backoffice/contracts/rent-calculations/{rentCalculation}
POST /backoffice/contracts/rent-calculations/calculate
POST /backoffice/contracts/rent-calculations/{rentCalculation}/approve
POST /backoffice/contracts/rent-calculations/{rentCalculation}/reject
POST /backoffice/contracts/rent-calculations/{rentCalculation}/recalculate

POST /backoffice/contracts/rent-calculations/{rentCalculation}/manual-reviews
POST /backoffice/contracts/rent-manual-reviews/{rentManualReview}/approve
POST /backoffice/contracts/rent-manual-reviews/{rentManualReview}/reject

GET /backoffice/contracts/templates
GET /backoffice/contracts/templates/create
POST /backoffice/contracts/templates
GET /backoffice/contracts/templates/{contractTemplate}
GET /backoffice/contracts/templates/{contractTemplate}/edit
PUT/PATCH /backoffice/contracts/templates/{contractTemplate}
POST /backoffice/contracts/templates/{contractTemplate}/activate
POST /backoffice/contracts/templates/{contractTemplate}/archive
POST /backoffice/contracts/templates/{contractTemplate}/duplicate

GET /backoffice/contracts/clauses
GET /backoffice/contracts/clauses/create
POST /backoffice/contracts/clauses
GET /backoffice/contracts/clauses/{contractClause}
GET /backoffice/contracts/clauses/{contractClause}/edit
PUT/PATCH /backoffice/contracts/clauses/{contractClause}
POST /backoffice/contracts/clauses/{contractClause}/activate
POST /backoffice/contracts/clauses/{contractClause}/archive

GET /backoffice/contracts/leases
GET /backoffice/contracts/leases/create
POST /backoffice/contracts/leases
GET /backoffice/contracts/leases/{leaseContract}
GET /backoffice/contracts/leases/{leaseContract}/edit
PUT/PATCH /backoffice/contracts/leases/{leaseContract}
POST /backoffice/contracts/leases/{leaseContract}/issue
POST /backoffice/contracts/leases/{leaseContract}/activate
POST /backoffice/contracts/leases/{leaseContract}/suspend
POST /backoffice/contracts/leases/{leaseContract}/terminate
POST /backoffice/contracts/leases/{leaseContract}/cancel

POST /backoffice/contracts/leases/{leaseContract}/documents/generate
GET /backoffice/contracts/documents/{leaseContractDocument}/download

POST /backoffice/contracts/leases/{leaseContract}/validations
POST /backoffice/contracts/validations/{leaseContractValidation}/approve
POST /backoffice/contracts/validations/{leaseContractValidation}/reject

POST /backoffice/contracts/leases/{leaseContract}/signatures

GET /backoffice/contracts/deposits/{contractDeposit}
POST /backoffice/contracts/deposits/{contractDeposit}/requested
POST /backoffice/contracts/deposits/{contractDeposit}/paid
POST /backoffice/contracts/deposits/{contractDeposit}/waived
POST /backoffice/contracts/deposits/{contractDeposit}/cancel
```

## Área do candidato

Criar, preferencialmente:

```text
GET /area-candidato/contratos
GET /area-candidato/contratos/{leaseContract}
GET /area-candidato/contratos/documentos/{leaseContractDocument}/download
GET /area-candidato/contratos/{leaseContract}/caucao
```

---

# 20. Views / páginas obrigatórias

Se o projeto usa Blade, criar:

## Backoffice

```text
resources/views/backoffice/contracts/rent-rule-sets/index.blade.php
resources/views/backoffice/contracts/rent-rule-sets/create.blade.php
resources/views/backoffice/contracts/rent-rule-sets/edit.blade.php
resources/views/backoffice/contracts/rent-rule-sets/show.blade.php

resources/views/backoffice/contracts/rent-calculations/index.blade.php
resources/views/backoffice/contracts/rent-calculations/show.blade.php

resources/views/backoffice/contracts/templates/index.blade.php
resources/views/backoffice/contracts/templates/create.blade.php
resources/views/backoffice/contracts/templates/edit.blade.php
resources/views/backoffice/contracts/templates/show.blade.php

resources/views/backoffice/contracts/clauses/index.blade.php
resources/views/backoffice/contracts/clauses/create.blade.php
resources/views/backoffice/contracts/clauses/edit.blade.php
resources/views/backoffice/contracts/clauses/show.blade.php

resources/views/backoffice/contracts/leases/index.blade.php
resources/views/backoffice/contracts/leases/create.blade.php
resources/views/backoffice/contracts/leases/edit.blade.php
resources/views/backoffice/contracts/leases/show.blade.php

resources/views/backoffice/contracts/documents/show.blade.php
resources/views/backoffice/contracts/deposits/show.blade.php
```

## Área do candidato

```text
resources/views/candidate/contracts/index.blade.php
resources/views/candidate/contracts/show.blade.php
resources/views/candidate/contracts/deposit.blade.php
```

## Documento contratual

Criar template de documento:

```text
resources/views/contracts/documents/lease-contract.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 21. UX obrigatória no backoffice

## Cálculo de renda

A página de cálculo deve mostrar:

```text
Candidato
Candidatura
Agregado
Rendimentos considerados
Habitação atribuída
Regra de renda aplicada
Rendimento mensal agregado
Rendimento anual agregado
Rendimento per capita
Renda base
Renda mínima
Renda máxima
Renda aplicável
Taxa de esforço
Caução calculada
Estado do cálculo
Ações disponíveis
```

Copy obrigatório:

```text
O cálculo da renda é efetuado com base nos dados atualmente registados e nas regras configuradas para o programa ou concurso. Confirme que os rendimentos usados estão validados antes de emitir o contrato.
```

## Revisão manual

Antes de aprovar renda manual, mostrar confirmação:

```text
A alteração manual da renda ficará registada com justificação, utilizador responsável e data. Confirme que existe fundamento administrativo ou jurídico para esta revisão.
```

## Contrato

A página do contrato deve mostrar:

```text
Número do contrato
Estado
Candidato/arrendatário
Habitação
Atribuição de origem
Renda
Taxa de esforço
Caução
Prazo
Data de início
Data de fim
Minuta usada
Cláusulas
Documentos gerados
Validações
Assinaturas/registos
Histórico de estado
Ações disponíveis
```

---

# 22. UX obrigatória para candidato

## Lista de contratos

Mostrar:

```text
Número do contrato
Habitação
Estado
Data de início
Data de fim
Renda mensal
Caução
Ações
```

## Detalhe do contrato

Mostrar:

```text
Contrato
Estado
Habitação
Renda
Caução
Data de início
Data de fim
Documentos disponíveis
Informação de validação/assinatura
```

Copy obrigatório:

```text
O contrato fica disponível para consulta após emissão pelos serviços municipais. Os documentos apresentados nesta área correspondem à versão registada no sistema.
```

## Caução

Mostrar:

```text
Valor da caução
Estado
Data de pedido, se aplicável
Data de pagamento registado, se aplicável
Observações
```

Não permitir ao candidato alterar a caução.

---

# 23. Regras de cálculo de renda

O cálculo de renda deve:

```text
Usar rendimentos declarados/validados
Guardar snapshot dos dados usados
Calcular rendimento mensal agregado
Calcular rendimento anual agregado
Calcular rendimento per capita
Aplicar método configurado
Aplicar renda mínima configurada
Aplicar renda máxima configurada
Calcular taxa de esforço
Calcular caução prevista
Criar detalhe das regras aplicadas
Exigir revisão manual quando dados estejam incompletos
```

Regra base configurável:

```text
base_rent = monthly_household_income * effort_rate_percentage / 100
applicable_rent = max(minimum_rent, min(base_rent, maximum_rent))
effort_rate = applicable_rent / monthly_household_income * 100
```

Se `monthly_household_income <= 0`:

```text
status = requires_manual_review
Não dividir por zero
Não aprovar automaticamente
```

Não hardcodar:

```text
Percentagens de esforço
Renda mínima
Renda máxima
Número de meses de caução
Prazos contratuais
```

---

# 24. Regras contratuais

Contrato só pode ser criado se:

```text
Allocation existe
Allocation está accepted ou ready_for_contract
Existe habitação associada
Existe candidato associado
Existe regra de renda ou autorização manual
Existe cálculo de renda aprovado ou passível de aprovação
Existe minuta contratual ativa
```

Contrato só pode ser emitido se:

```text
Está em preparation
Tem número de contrato
Tem arrendatário
Tem habitação
Tem renda
Tem caução definida
Tem data de início
Tem data de fim
Tem documento gerado ou HTML disponível
```

Contrato só pode ser ativado se:

```text
Está issued ou signed
Tem validação interna aprovada, se configurado
Tem assinatura/registo interno, se configurado
Tem caução registada conforme regra
Habitação ainda está disponível para ativação contratual
```

---

# 25. Geração de contrato

O contrato deve ser gerado a partir de:

```text
ContractTemplate
ContractClause
LeaseContract
RentCalculation
ContractDeposit
HousingUnit
Application
Household
Allocation
```

O documento gerado deve conter, no mínimo:

```text
Número do contrato
Identificação do arrendatário
Identificação do senhorio/município
Identificação da habitação
Prazo
Data de início
Data de fim
Renda mensal
Caução
Condições especiais
Cláusulas aplicáveis
Data de emissão
Espaços de assinatura ou validação
```

Se o PDF for gerado:

```text
Guardar em storage privado
Registar checksum
Registar versão
Permitir download seguro
```

Se o PDF não for gerado por falta de infraestrutura:

```text
Gerar HTML imprimível
Documentar pendência
Não afirmar que PDF foi criado
```

---

# 26. Integração com atribuição — Sprint 12

A Sprint 13 deve consumir dados da Sprint 12.

Regras:

```text
Usar apenas allocations accepted ou ready_for_contract
Não alterar histórico de atribuição
Não reatribuir habitação
Não chamar suplentes
Não aceitar/recusar oferta nesta sprint
Guardar referência à allocation_id
```

Ao ativar contrato:

```text
Marcar Allocation como ready_for_contract ou contract_active, conforme modelo existente
Marcar HousingUnit como contracted/occupied/leased, conforme modelo existente
Marcar ContestHousingUnit como accepted/contracted, conforme modelo existente
```

---

# 27. Integração com documentos

Se Sprint 6 existir:

```text
Contrato gerado pode ser registado como documento interno
Contrato assinado carregado pode usar DocumentSubmission ou LeaseContractDocument
Downloads devem respeitar storage privado
Acesso deve ser auditado, se houver logs documentais
```

Não guardar contratos em storage público.

---

# 28. Integração com notificações

Se existir `OfficialNotification`, usar esse modelo para:

```text
contract_preparation_started
contract_issued
contract_signed
contract_active
deposit_requested
deposit_paid_registered
```

Se não existir:

```text
Criar registo interno equivalente ou documentar pendência.
Não enviar email/SMS real.
```

Não marcar notificação como `sent` sem envio real.

---

# 29. Auditoria

Se existir auditoria, auditar:

```text
Criação de regra de renda
Atualização de regra de renda
Ativação/arquivo de regra de renda
Cálculo de renda
Aprovação de cálculo de renda
Rejeição de cálculo de renda
Revisão manual de renda
Aprovação de revisão manual
Criação de minuta contratual
Alteração de minuta contratual
Ativação de minuta
Criação de cláusula
Alteração de cláusula
Criação de contrato
Emissão de contrato
Geração de documento contratual
Download de contrato
Validação interna de contrato
Registo de assinatura
Ativação de contrato
Suspensão de contrato
Cessação de contrato
Registo de caução
Alteração do estado da caução
Atualização do estado da habitação por ativação contratual
```

Não criar auditoria paralela se já existir sistema de auditoria.

Se não existir auditoria, documentar pendência.

Não guardar dados sensíveis excessivos nos logs.

---

# 30. RGPD e segurança

Regras obrigatórias:

```text
Contratos contêm dados pessoais sensíveis.
Candidato só vê os seus próprios contratos.
Candidato só descarrega documentos próprios.
Backoffice exige permissões.
Auditor não altera contratos.
Gestor financeiro só altera caução se autorizado.
Não expor contratos publicamente.
Não guardar contratos em storage público.
Não expor storage_path.
Não colocar NIF, email ou nome no nome do ficheiro gerado.
Não permitir mass assignment de renda.
Não permitir mass assignment de estado contratual.
Não permitir ativar contrato sem autorização.
Não permitir download sem policy.
Snapshots devem minimizar dados pessoais.
```

---

# 31. Seeders e factories

Criar factories:

```text
RentRuleSetFactory
RentRuleFactory
RentCalculationFactory
RentCalculationDetailFactory
RentManualReviewFactory
ContractTemplateFactory
ContractClauseFactory
ContractTemplateClauseFactory
LeaseContractFactory
LeaseContractPartyFactory
LeaseContractClauseFactory
ContractDepositFactory
LeaseContractDocumentFactory
LeaseContractValidationFactory
LeaseContractSignatureFactory
LeaseContractStatusHistoryFactory
```

Criar seeders opcionais:

```text
RentRuleSetSeeder
ContractTemplateSeeder
ContractClauseSeeder
DemoLeaseContractSeeder
```

Seeders devem usar apenas dados fictícios.

Não usar dados pessoais reais.

Minutas demo devem conter aviso:

```text
MINUTA DEMO — SUJEITA A VALIDAÇÃO JURÍDICA
```

---

# 32. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text
guest_cannot_access_candidate_contracts
candidate_can_access_own_contract
candidate_cannot_access_other_candidate_contract
candidate_can_download_own_contract_document
candidate_cannot_download_other_candidate_contract_document
candidate_cannot_access_backoffice_contracts
technician_can_access_backoffice_contracts_if_authorized
auditor_can_view_contract_without_editing
finance_manager_can_update_deposit_if_authorized
```

## Regras de renda

```text
admin_can_create_rent_rule_set
admin_can_update_rent_rule_set
rent_rule_set_requires_program_or_contest
contest_rent_rule_set_takes_precedence_over_program_rule_set
draft_rent_rule_set_is_not_used
archived_rent_rule_set_is_not_used
```

## Cálculo de renda

```text
rent_can_be_calculated_for_accepted_allocation
rent_cannot_be_calculated_for_refused_allocation
rent_calculation_uses_household_income
rent_calculation_calculates_effort_rate
rent_calculation_applies_minimum_rent
rent_calculation_applies_maximum_rent
rent_calculation_creates_snapshot
rent_calculation_creates_details
rent_calculation_requires_manual_review_when_income_missing
rent_calculation_does_not_divide_by_zero
approved_rent_calculation_can_be_used_for_contract
```

## Revisão manual

```text
manual_rent_review_requires_reason
manual_rent_review_requires_authorization
approved_manual_review_updates_approved_rent
rejected_manual_review_does_not_change_rent
manual_review_is_audited_if_audit_exists
```

## Minutas e cláusulas

```text
admin_can_create_contract_template
admin_can_activate_contract_template
contract_template_requires_body
contract_template_resolves_placeholders
unresolved_required_placeholders_block_document_generation
contract_clause_can_be_created
contract_clause_snapshot_is_saved_on_contract
```

## Contrato

```text
contract_can_be_created_from_accepted_allocation
contract_cannot_be_created_without_approved_rent_calculation
contract_requires_active_template
contract_number_is_unique
contract_stores_tenant_data
contract_stores_housing_data
contract_stores_rent_and_deposit
contract_starts_in_preparation_status
contract_can_be_issued_when_required_data_exists
contract_cannot_be_activated_without_validation_when_required
contract_can_be_activated_after_validation_and_signature
activating_contract_updates_housing_unit_status
contract_status_change_creates_history
```

## Documento/PDF

```text
contract_document_can_be_generated
contract_document_is_versioned
contract_document_is_stored_privately
contract_document_download_requires_authorization
contract_document_does_not_expose_storage_path
pdf_generation_is_documented_when_pdf_library_missing
```

## Caução

```text
deposit_is_created_with_contract
deposit_amount_is_calculated_from_rules
deposit_can_be_marked_requested
deposit_can_be_marked_paid_manually
deposit_can_be_waived_with_reason
candidate_can_view_own_deposit
candidate_cannot_modify_deposit
```

## Segurança

```text
candidate_cannot_mass_assign_contract_status
candidate_cannot_mass_assign_monthly_rent
candidate_cannot_activate_contract
candidate_cannot_mark_deposit_paid
candidate_cannot_generate_contract_document
contract_file_name_does_not_contain_nif
contract_file_name_does_not_contain_email
contract_file_is_not_publicly_accessible
```

## Auditoria, se existir

```text
calculating_rent_generates_audit_log
manual_rent_review_generates_audit_log
creating_contract_generates_audit_log
issuing_contract_generates_audit_log
generating_contract_document_generates_audit_log
activating_contract_generates_audit_log
marking_deposit_paid_generates_audit_log
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
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
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
Pendências para Sprint 14
Validações jurídicas pendentes
Regras de renda implementadas
Regras de caução implementadas
Limitações da geração PDF
Limitações de notificações
```

---

# 35. Critérios de aceitação

A Sprint 13 está concluída quando:

```text
Existe motor de cálculo de renda configurável
A renda é calculada com base nos rendimentos declarados/validados
A taxa de esforço é calculada
A renda mínima é aplicada quando configurada
A renda máxima é aplicada quando configurada
A renda aplicável fica registada
O cálculo guarda snapshot dos dados usados
Dados incompletos geram requires_manual_review
Revisão manual exige justificação
Revisão manual fica auditada se existir auditoria
Existe minuta contratual parametrizável
Existem cláusulas por programa/concurso
Contrato é criado a partir de atribuição aceite
Contrato contém arrendatário
Contrato contém habitação
Contrato contém prazo
Contrato contém renda
Contrato contém caução
Contrato contém data de início e fim
Contrato contém condições especiais quando aplicáveis
Contrato é gerado em documento HTML/PDF conforme infraestrutura
Documento contratual fica em storage privado
Download do contrato exige autorização
Caução fica registada
Contrato tem estados formais
Emissão do contrato funciona
Validação interna funciona
Registo de assinatura interna/manual funciona
Ativação do contrato funciona
Ativação do contrato altera o estado da habitação
Candidato só vê os seus próprios contratos
Backoffice exige permissões
Auditoria é usada se existir
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foi implementada cobrança real
Não foi implementada faturação
Não foi implementada assinatura digital externa
Não foi implementada gestão pós-contratual avançada
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
20. Regras de renda implementadas
21. Regras de caução implementadas
22. Limitações da geração PDF
23. Limitações de notificações
24. Confirmação de que não foram implementadas funcionalidades fora de âmbito
25. Recomendação objetiva para avançar ou não para Sprint 14
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 37. Execução imediata

Executa agora apenas:

```text
Sprint 13 — Cálculo de Renda, Contratos e Caução
```

Usa como referência principal:

```text
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
```

Fim da master prompt da Sprint 13.
