# Sprint 14 — Pagamentos, Incumprimentos e Revisão de Renda

## Prioridade de desenvolvimento

Esta sprint pertence à fase de gestão financeira pós-contrato da plataforma municipal de Arrendamento Acessível.

A Sprint 14 deve ser executada depois da Sprint 13, usando contratos ativos, rendas calculadas, cauções registadas e habitações contratualizadas.

Ordem operacional recomendada:

```text
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 10 — Matriz de Classificação e Ranking
Sprint 11 — Listas Provisórias, Reclamações e Audiência
Sprint 12 — Atribuição de Habitações
Sprint 13 — Cálculo de Renda, Contratos e Caução
Sprint 14 — Pagamentos, Incumprimentos e Revisão de Renda
Sprint 15 — Ocupação, Ocorrências e Manutenção
```

---

# 1. Objetivo da Sprint

Implementar a gestão financeira pós-contrato.

A plataforma deve permitir que o Município:

```text
Crie plano mensal de rendas por contrato ativo
Gere prestações mensais de renda
Registe pagamentos
Associe pagamentos a prestações
Importe pagamentos manualmente
Importe pagamentos por ficheiro, se aplicável
Controle saldos por contrato
Controle valores pagos, em dívida e em atraso
Gere recibos internos ou comprovativos de pagamento
Controle atrasos
Gere alertas de incumprimento
Emita avisos de incumprimento
Crie acordos de regularização
Controle pagamentos de acordos
Faça revisão periódica de renda
Solicite atualização documental anual
Registe alteração de rendimentos durante o contrato
Recalcule renda com histórico
Mantenha histórico financeiro completo
Permita ao arrendatário consultar a sua situação financeira
```

Esta sprint deve completar a gestão financeira operacional, mas não deve implementar integração bancária real, faturação fiscal, comunicação à Autoridade Tributária ou cobrança automática sem validação explícita.

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 14.

Não avances para Sprint 15, Sprint 16, Sprint 17 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash
git branch --show-current
```

Não interromper execução por causa da branch atual.

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
docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
docs/backlog/sprint-14-pagamentos-incumprimentos-revisao-renda.md

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
Modelo HousingUnit
Modelo Allocation
Modelo LeaseContract ou Contract
Modelo RentCalculation
Modelo ContractDeposit
Modelo LeaseContractDocument
Modelo OfficialNotification, se existir
Modelo AuditLog, se existir
Modelo Payment, se existir
Modelo PaymentReceipt, se existir
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
TenantFinancialAccount
RentSchedule
RentInstallment
LeasePayment
Payment
PaymentAllocation
PaymentImport
PaymentReceipt
FinancialTransaction
Arrear
DefaultNotice
RegularizationAgreement
RegularizationInstallment
RentReview
RentReviewRequest
IncomeChangeDeclaration
AnnualDocumentUpdateRequest
ContractFinancialHistory
```

reaproveitar ou adaptar com compatibilidade.

Não apagar contratos existentes.

Não apagar pagamentos existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY ou credenciais.

---

# 3. Dependências funcionais

Esta sprint depende obrigatoriamente de:

```text
Sprint 13 — Cálculo de Renda, Contratos e Caução
```

Depende preferencialmente de:

```text
Sprint 5 — Agregado, Rendimentos e Situação Habitacional
Sprint 6 — Gestão Documental Avançada
Sistema de notificações internas
Sistema de auditoria
Sistema de geração de PDF
```

## Dependência da Sprint 13

Se não existir `LeaseContract`, `Contract` ou equivalente, interrompe a implementação funcional e informa:

```text
A Sprint 14 depende da Sprint 13 — Cálculo de Renda, Contratos e Caução.
```

A Sprint 14 deve atuar apenas sobre contratos nos estados:

```text
active
suspended
terminated
renewed
expired
```

ou equivalentes existentes.

A geração automática de plano mensal deve aplicar-se sobretudo a contratos `active`.

Não criar contratos nesta sprint.

Não recalcular contrato inicial nesta sprint, salvo revisão formal de renda.

---

# 4. Validação jurídica, financeira e fiscal obrigatória

Esta sprint tem impacto financeiro e jurídico.

Não implementar faturação fiscal real sem validação.

Não comunicar dados à Autoridade Tributária.

Não emitir documentos fiscais oficiais se o sistema não estiver integrado e validado para esse efeito.

Não implementar cobrança bancária real.

Não implementar débito direto, referência Multibanco, MB WAY, SEPA, SIBS, gateway de pagamento ou integração bancária sem instrução explícita.

Regras obrigatórias:

```text
Recibos gerados nesta sprint devem ser tratados como recibos internos/comprovativos, salvo validação fiscal posterior.
Avisos de incumprimento devem ser minutas configuráveis.
Acordos de regularização devem ficar documentados e auditados.
Revisões de renda devem preservar histórico.
Alterações de rendimento devem gerar snapshot.
Atualização documental anual deve ficar registada.
Importações de pagamentos devem ser reversíveis ou auditáveis.
Pagamentos não conciliados devem ficar pendentes.
Pagamentos associados manualmente devem identificar utilizador e data.
```

Textos financeiros, avisos, recibos e acordos devem ser parametrizáveis e sujeitos a validação jurídica/fiscal.

---

# 5. Âmbito incluído

Implementar:

```text
Conta corrente do contrato
Plano mensal de rendas
Prestações mensais
Registo manual de pagamentos
Importação manual por ficheiro, se aplicável
Conciliação manual de pagamentos
Associação de pagamentos a prestações
Recibos internos/comprovativos
Controlo de atrasos
Cálculo de dias em atraso
Alertas de incumprimento
Avisos de incumprimento
Acordos de regularização
Prestações de acordo de regularização
Histórico financeiro
Revisão periódica de renda
Pedido anual de atualização documental
Declaração de alteração de rendimento
Recalculo de renda durante contrato
Aplicação de nova renda com data de eficácia
Notificações internas
Consulta financeira pelo arrendatário
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

```text
Integração bancária real
Débito direto
Referências Multibanco
MB WAY
Gateway de pagamento online
Comunicação à Autoridade Tributária
Emissão fiscal oficial
SAF-T
Faturação eletrónica certificada
Contabilidade geral
Processos judiciais de despejo
Penhoras
Juros legais automáticos sem validação jurídica
Cálculo automático de indemnizações legais
Integração com Segurança Social
Integração com AT
Assinatura digital
Manutenção
Vistorias
Gestão de obras
Renovação contratual complexa
Cessação contratual avançada
```

Podem ser criados pontos de integração para sprints futuras, mas não implementar funcionalidades fora do âmbito.

---

# 7. Conceito funcional

O fluxo da Sprint 14 deve ser:

```text
Contrato ativo
→ Criação da conta financeira do contrato
→ Geração do plano mensal de rendas
→ Geração de prestações mensais
→ Registo/importação de pagamentos
→ Conciliação com prestações
→ Atualização de saldo
→ Emissão de recibo interno/comprovativo
→ Verificação de atrasos
→ Geração de alertas
→ Emissão de aviso de incumprimento, se aplicável
→ Criação de acordo de regularização, se aplicável
→ Pagamento de prestações em atraso
→ Revisão periódica de renda
→ Pedido anual de atualização documental
→ Alteração de rendimentos durante contrato
→ Recalculo de renda
→ Aplicação da nova renda com histórico
```

---

# 8. Estados principais

## FinancialAccountStatus

```text
active
suspended
closed
archived
```

## RentScheduleStatus

```text
draft
active
suspended
closed
cancelled
archived
```

## RentInstallmentStatus

```text
scheduled
issued
partially_paid
paid
overdue
waived
cancelled
under_agreement
```

## LeasePaymentStatus

```text
draft
pending
confirmed
partially_allocated
allocated
reversed
cancelled
failed
```

## PaymentAllocationStatus

```text
active
reversed
cancelled
```

## PaymentImportStatus

```text
draft
processing
processed
partially_processed
failed
cancelled
reversed
```

## PaymentReceiptStatus

```text
draft
issued
cancelled
reissued
archived
```

## ArrearStatus

```text
open
notified
under_agreement
partially_regularized
regularized
waived
closed
cancelled
```

## DefaultNoticeStatus

```text
draft
issued
sent_internal
acknowledged
cancelled
archived
```

## RegularizationAgreementStatus

```text
draft
proposed
active
completed
breached
cancelled
archived
```

## RegularizationInstallmentStatus

```text
scheduled
paid
partially_paid
overdue
waived
cancelled
```

## RentReviewStatus

```text
draft
requested
under_review
requires_documents
calculated
approved
rejected
applied
cancelled
```

## IncomeChangeStatus

```text
draft
submitted
under_review
accepted
rejected
cancelled
```

## AnnualDocumentUpdateStatus

```text
draft
requested
submitted
under_review
accepted
rejected
overdue
cancelled
closed
```

---

# 9. Modelo de dados

## 9.1 TenantFinancialAccount

Criar entidade:

```text
TenantFinancialAccount
```

Tabela:

```text
tenant_financial_accounts
```

Objetivo:

```text
Representar a conta corrente financeira de um contrato.
```

Campos mínimos:

```text
id
lease_contract_id
application_id
user_id
housing_unit_id

account_number
status

opening_balance
current_balance
total_due
total_paid
total_overdue
total_waived
total_refunded

opened_at
closed_at

created_by
updated_by

created_at
updated_at
deleted_at
```

Regras:

```text
Um contrato ativo deve ter uma conta financeira.
account_number obrigatório e único.
Saldo deve ser recalculável a partir dos movimentos.
Não permitir mass assignment de saldos.
Usar soft deletes.
```

---

## 9.2 RentSchedule

Criar entidade:

```text
RentSchedule
```

Tabela:

```text
rent_schedules
```

Objetivo:

```text
Guardar o plano mensal de rendas de um contrato.
```

Campos mínimos:

```text
id
tenant_financial_account_id
lease_contract_id
application_id
user_id

status
start_date
end_date
monthly_rent
payment_day
currency

generated_by
generated_at
suspended_at
closed_at
cancelled_at

notes
internal_notes

created_at
updated_at
deleted_at
```

Regras:

```text
Gerar apenas para contratos ativos ou emitidos conforme regra.
Um contrato pode ter vários schedules versionados, mas apenas um active.
Alterações de renda devem criar novo schedule ou nova versão a partir da data de eficácia.
```

---

## 9.3 RentInstallment

Criar entidade:

```text
RentInstallment
```

Tabela:

```text
rent_installments
```

Objetivo:

```text
Representar cada renda mensal devida.
```

Campos mínimos:

```text
id
tenant_financial_account_id
rent_schedule_id
lease_contract_id
application_id
user_id

installment_number
reference
status

period_year
period_month
period_start_date
period_end_date

due_date
issued_at

rent_amount
additional_charges
discount_amount
waived_amount
total_amount
paid_amount
outstanding_amount

paid_at
overdue_at
cancelled_at

notes
internal_notes

created_at
updated_at
deleted_at
```

Regras:

```text
reference obrigatório e único por prestação.
Não duplicar prestação para o mesmo contrato/ano/mês.
outstanding_amount deve ser calculado por service.
Prestação paga não deve ser alterada manualmente sem registo.
```

---

## 9.4 LeasePayment

Criar entidade:

```text
LeasePayment
```

Tabela:

```text
lease_payments
```

Objetivo:

```text
Registar pagamentos recebidos.
```

Campos mínimos:

```text
id
tenant_financial_account_id
lease_contract_id
application_id
user_id

payment_number
status

payment_date
value_date
amount
currency

payment_method
payment_reference
external_reference
payer_name
payer_identifier

source
import_batch_id

confirmed_by
confirmed_at

reversed_by
reversed_at
reversal_reason

notes
internal_notes

created_at
updated_at
deleted_at
```

Valores recomendados para `payment_method`:

```text
bank_transfer
cash
cheque
pos
manual_adjustment
other
```

Valores recomendados para `source`:

```text
manual
csv_import
bank_file
automatic_import
adjustment
other
```

Regras:

```text
payment_number obrigatório e único.
Não apagar pagamento confirmado.
Reversões devem criar histórico.
Não guardar dados bancários sensíveis desnecessários.
```

---

## 9.5 PaymentAllocation

Criar entidade:

```text
PaymentAllocation
```

Tabela:

```text
payment_allocations
```

Objetivo:

```text
Associar pagamentos a prestações de renda.
```

Campos mínimos:

```text
id
lease_payment_id
rent_installment_id
tenant_financial_account_id

status
allocated_amount
allocated_by
allocated_at

reversed_by
reversed_at
reversal_reason

created_at
updated_at
deleted_at
```

Regras:

```text
Um pagamento pode liquidar várias prestações.
Uma prestação pode receber vários pagamentos.
allocated_amount não pode exceder valor disponível do pagamento.
allocated_amount não pode exceder dívida da prestação, salvo crédito configurado.
Reversões devem atualizar saldos.
```

---

## 9.6 PaymentImportBatch

Criar entidade:

```text
PaymentImportBatch
```

Tabela:

```text
payment_import_batches
```

Objetivo:

```text
Guardar importações de pagamentos.
```

Campos mínimos:

```text
id
status
source
filename
storage_disk
storage_path
mime_type
file_size
checksum

total_rows
processed_rows
matched_rows
unmatched_rows
failed_rows

imported_by
imported_at
processed_at
failed_at
failure_reason

notes

created_at
updated_at
deleted_at
```

Regras:

```text
Ficheiro deve ser guardado em storage privado.
Não expor storage_path.
Importação deve ser auditável.
Não marcar como processed se houver erro não tratado.
```

---

## 9.7 PaymentImportRow

Criar entidade:

```text
PaymentImportRow
```

Tabela:

```text
payment_import_rows
```

Campos mínimos:

```text
id
payment_import_batch_id

row_number
status

raw_data
parsed_payment_date
parsed_amount
parsed_reference
parsed_payer_name
parsed_description

matched_lease_contract_id
matched_tenant_financial_account_id
matched_rent_installment_id
created_lease_payment_id

error_message

created_at
updated_at
```

Estados recomendados:

```text
pending
matched
unmatched
imported
failed
ignored
```

Regras:

```text
raw_data deve evitar dados excessivos.
Linhas unmatched devem poder ser conciliadas manualmente.
```

---

## 9.8 PaymentReceipt

Criar entidade:

```text
PaymentReceipt
```

Tabela:

```text
payment_receipts
```

Objetivo:

```text
Gerar recibo interno ou comprovativo de pagamento.
```

Campos mínimos:

```text
id
lease_payment_id
tenant_financial_account_id
lease_contract_id
application_id
user_id

receipt_number
status
receipt_type

amount
currency
payment_date

title
html_content
storage_disk
storage_path
mime_type
file_size
checksum

issued_by
issued_at
cancelled_by
cancelled_at
cancellation_reason

created_at
updated_at
deleted_at
```

Valores recomendados para `receipt_type`:

```text
internal_receipt
payment_confirmation
regularization_payment
deposit_payment
other
```

Regras:

```text
receipt_number obrigatório e único.
Tratar como recibo interno/comprovativo, não documento fiscal oficial.
Se PDF for gerado, guardar em storage privado.
Não expor storage_path.
```

---

## 9.9 FinancialTransaction

Criar entidade:

```text
FinancialTransaction
```

Tabela:

```text
financial_transactions
```

Objetivo:

```text
Guardar histórico financeiro da conta corrente.
```

Campos mínimos:

```text
id
tenant_financial_account_id
lease_contract_id
application_id
user_id

transaction_type
source_type
source_id

description
debit_amount
credit_amount
balance_after
transaction_date

created_by
created_at
```

Valores recomendados para `transaction_type`:

```text
rent_issued
payment_received
payment_allocated
payment_reversed
waiver
adjustment
arrear_created
arrear_regularized
deposit_requested
deposit_paid
rent_review_applied
other
```

Regras:

```text
Movimentos devem ser append-only sempre que possível.
Não apagar histórico financeiro.
```

---

## 9.10 Arrear

Criar entidade:

```text
Arrear
```

Tabela:

```text
arrears
```

Objetivo:

```text
Registar incumprimentos/atrasos.
```

Campos mínimos:

```text
id
tenant_financial_account_id
lease_contract_id
application_id
user_id
rent_installment_id

status
amount_due
amount_paid
outstanding_amount

due_date
overdue_since
days_overdue

detected_at
last_checked_at
notified_at
regularized_at
closed_at

notes
internal_notes

created_at
updated_at
deleted_at
```

Regras:

```text
Arrear deve refletir prestação vencida não paga ou parcialmente paga.
days_overdue deve ser recalculável.
Não duplicar arrear ativo para a mesma prestação.
```

---

## 9.11 DefaultNotice

Criar entidade:

```text
DefaultNotice
```

Tabela:

```text
default_notices
```

Objetivo:

```text
Guardar avisos de incumprimento.
```

Campos mínimos:

```text
id
tenant_financial_account_id
lease_contract_id
application_id
user_id
arrear_id

notice_number
status
notice_type

subject
body
legal_basis
amount_due
days_overdue
response_deadline_at

issued_by
issued_at
acknowledged_at
cancelled_at
cancellation_reason

candidate_visible

created_at
updated_at
deleted_at
```

Valores recomendados para `notice_type`:

```text
payment_delay
persistent_default
regularization_warning
agreement_breach
other
```

Regras:

```text
Aviso deve ser minuta configurável ou conteúdo gerado por service.
Não enviar email/SMS real sem integração segura.
candidate_visible controla visibilidade ao arrendatário.
```

---

## 9.12 RegularizationAgreement

Criar entidade:

```text
RegularizationAgreement
```

Tabela:

```text
regularization_agreements
```

Objetivo:

```text
Gerir acordos de regularização de dívida.
```

Campos mínimos:

```text
id
tenant_financial_account_id
lease_contract_id
application_id
user_id

agreement_number
status

total_debt_amount
initial_payment_amount
installment_amount
installments_count
start_date
end_date

terms
legal_basis
internal_notes

proposed_by
proposed_at
approved_by
approved_at
accepted_by_candidate_at

completed_at
breached_at
cancelled_at
cancellation_reason

created_at
updated_at
deleted_at
```

Regras:

```text
agreement_number obrigatório e único.
Acordo ativo deve criar prestações de regularização.
Dívida incluída no acordo deve ficar marcada como under_agreement.
```

---

## 9.13 RegularizationInstallment

Criar entidade:

```text
RegularizationInstallment
```

Tabela:

```text
regularization_installments
```

Campos mínimos:

```text
id
regularization_agreement_id
tenant_financial_account_id
lease_contract_id
application_id
user_id

installment_number
status
due_date
amount
paid_amount
outstanding_amount

paid_at
overdue_at
cancelled_at

created_at
updated_at
deleted_at
```

Regras:

```text
Prestações de acordo devem ser conciliáveis com pagamentos.
Atrasos em acordo podem gerar aviso de incumprimento do acordo.
```

---

## 9.14 RentReview

Criar entidade:

```text
RentReview
```

Tabela:

```text
rent_reviews
```

Objetivo:

```text
Gerir revisão periódica ou extraordinária da renda.
```

Campos mínimos:

```text
id
lease_contract_id
tenant_financial_account_id
application_id
user_id
household_id

status
review_type

current_rent
proposed_rent
approved_rent

current_income_snapshot
new_income_snapshot

requested_by
requested_at
submitted_at
reviewed_by
reviewed_at
approved_by
approved_at
applied_at

effective_from
effective_until

reason
legal_basis
internal_notes

created_at
updated_at
deleted_at
```

Valores recomendados para `review_type`:

```text
annual
periodic
income_change
manual
extraordinary
other
```

Regras:

```text
Revisão deve criar histórico.
Nova renda só deve ser aplicada após aprovação.
Aplicação deve criar novo RentSchedule ou nova versão.
```

---

## 9.15 IncomeChangeDeclaration

Criar entidade:

```text
IncomeChangeDeclaration
```

Tabela:

```text
income_change_declarations
```

Objetivo:

```text
Permitir declaração de alteração de rendimentos durante o contrato.
```

Campos mínimos:

```text
id
lease_contract_id
application_id
user_id
household_id

status
change_type
description

declared_monthly_income
declared_annual_income
effective_from

submitted_at
reviewed_by
reviewed_at
decision
decision_reason

created_at
updated_at
deleted_at
```

Valores recomendados para `change_type`:

```text
income_increase
income_decrease
employment_loss
new_employment
household_change
other
```

Regras:

```text
Declarações aceites podem originar RentReview.
Candidato só declara alterações do seu próprio contrato.
```

---

## 9.16 AnnualDocumentUpdateRequest

Criar entidade:

```text
AnnualDocumentUpdateRequest
```

Tabela:

```text
annual_document_update_requests
```

Objetivo:

```text
Solicitar atualização documental anual para revisão de renda ou validação do contrato.
```

Campos mínimos:

```text
id
lease_contract_id
application_id
user_id
household_id

request_number
status
year
subject
message
deadline_at

issued_by
issued_at
submitted_at
reviewed_by
reviewed_at
decision
decision_reason

created_at
updated_at
deleted_at
```

Regras:

```text
Pode integrar com DocumentSubmission se Sprint 6 existir.
Não criar storage documental paralelo.
Pedidos vencidos devem gerar alerta.
```

---

## 9.17 AnnualDocumentUpdateSubmission

Criar entidade:

```text
AnnualDocumentUpdateSubmission
```

Tabela:

```text
annual_document_update_submissions
```

Campos mínimos:

```text
id
annual_document_update_request_id
lease_contract_id
application_id
user_id

document_submission_id
description
submitted_at
status
reviewed_by
reviewed_at
review_notes

created_at
updated_at
deleted_at
```

Estados recomendados:

```text
submitted
accepted
rejected
cancelled
```

---

# 10. Enums recomendados

Criar, se a versão do PHP permitir:

```text
App\Enums\FinancialAccountStatus
App\Enums\RentScheduleStatus
App\Enums\RentInstallmentStatus
App\Enums\LeasePaymentStatus
App\Enums\PaymentAllocationStatus
App\Enums\PaymentImportStatus
App\Enums\PaymentImportRowStatus
App\Enums\PaymentReceiptStatus
App\Enums\FinancialTransactionType
App\Enums\ArrearStatus
App\Enums\DefaultNoticeStatus
App\Enums\DefaultNoticeType
App\Enums\RegularizationAgreementStatus
App\Enums\RegularizationInstallmentStatus
App\Enums\RentReviewStatus
App\Enums\RentReviewType
App\Enums\IncomeChangeStatus
App\Enums\IncomeChangeType
App\Enums\AnnualDocumentUpdateStatus
App\Enums\AnnualDocumentSubmissionStatus
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 11. Relações obrigatórias

## LeaseContract

Adicionar:

```text
hasOne TenantFinancialAccount
hasMany RentSchedule
hasMany RentInstallment
hasMany LeasePayment
hasMany PaymentReceipt
hasMany Arrear
hasMany DefaultNotice
hasMany RegularizationAgreement
hasMany RentReview
hasMany IncomeChangeDeclaration
hasMany AnnualDocumentUpdateRequest
```

## TenantFinancialAccount

```text
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
belongsTo HousingUnit
hasMany RentSchedule
hasMany RentInstallment
hasMany LeasePayment
hasMany PaymentAllocation
hasMany PaymentReceipt
hasMany FinancialTransaction
hasMany Arrear
hasMany DefaultNotice
hasMany RegularizationAgreement
hasMany RentReview
```

## RentSchedule

```text
belongsTo TenantFinancialAccount
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
hasMany RentInstallment
```

## RentInstallment

```text
belongsTo TenantFinancialAccount
belongsTo RentSchedule
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
hasMany PaymentAllocation
hasOne Arrear
```

## LeasePayment

```text
belongsTo TenantFinancialAccount
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
belongsTo PaymentImportBatch as importBatch nullable
belongsTo User as confirmedBy nullable
belongsTo User as reversedBy nullable
hasMany PaymentAllocation
hasMany PaymentReceipt
```

## PaymentAllocation

```text
belongsTo LeasePayment
belongsTo RentInstallment
belongsTo TenantFinancialAccount
belongsTo User as allocatedBy
belongsTo User as reversedBy nullable
```

## PaymentImportBatch

```text
belongsTo User as importedBy
hasMany PaymentImportRow
hasMany LeasePayment
```

## PaymentReceipt

```text
belongsTo LeasePayment
belongsTo TenantFinancialAccount
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
belongsTo User as issuedBy
belongsTo User as cancelledBy nullable
```

## Arrear

```text
belongsTo TenantFinancialAccount
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
belongsTo RentInstallment
hasMany DefaultNotice
```

## DefaultNotice

```text
belongsTo TenantFinancialAccount
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
belongsTo Arrear nullable
belongsTo User as issuedBy
```

## RegularizationAgreement

```text
belongsTo TenantFinancialAccount
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
belongsTo User as proposedBy
belongsTo User as approvedBy nullable
hasMany RegularizationInstallment
```

## RegularizationInstallment

```text
belongsTo RegularizationAgreement
belongsTo TenantFinancialAccount
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
```

## RentReview

```text
belongsTo LeaseContract
belongsTo TenantFinancialAccount
belongsTo Application
belongsTo User as tenant
belongsTo Household nullable
belongsTo User as requestedBy nullable
belongsTo User as reviewedBy nullable
belongsTo User as approvedBy nullable
```

## IncomeChangeDeclaration

```text
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
belongsTo Household nullable
belongsTo User as reviewedBy nullable
```

## AnnualDocumentUpdateRequest

```text
belongsTo LeaseContract
belongsTo Application
belongsTo User as tenant
belongsTo Household nullable
belongsTo User as issuedBy
belongsTo User as reviewedBy nullable
hasMany AnnualDocumentUpdateSubmission
```

---

# 12. Services obrigatórios

Criar:

```text
App\Services\Finance\TenantFinancialAccountService
App\Services\Finance\RentScheduleService
App\Services\Finance\RentInstallmentService
App\Services\Finance\LeasePaymentService
App\Services\Finance\PaymentAllocationService
App\Services\Finance\PaymentImportService
App\Services\Finance\PaymentReceiptService
App\Services\Finance\FinancialTransactionService
App\Services\Finance\AccountStatementService

App\Services\Finance\ArrearDetectionService
App\Services\Finance\DefaultNoticeService
App\Services\Finance\RegularizationAgreementService
App\Services\Finance\RegularizationPaymentService

App\Services\Finance\RentReviewService
App\Services\Finance\IncomeChangeService
App\Services\Finance\AnnualDocumentUpdateService
App\Services\Finance\RentRecalculationService

App\Services\Finance\FinanceNotificationService
```

---

## 12.1 TenantFinancialAccountService

Responsável por:

```text
Criar conta financeira para contrato ativo
Gerar número de conta
Recalcular saldos
Consultar conta corrente
Fechar conta quando contrato termina
Impedir duplicação de conta ativa
```

---

## 12.2 RentScheduleService

Responsável por:

```text
Criar plano mensal de rendas
Gerar prestações entre start_date e end_date
Criar novo plano após revisão de renda
Suspender plano
Fechar plano
Cancelar plano quando permitido
```

Regras:

```text
Não gerar prestações duplicadas para o mesmo mês.
Novo plano por revisão deve respeitar effective_from.
Plano ativo deve refletir renda em vigor.
```

---

## 12.3 RentInstallmentService

Responsável por:

```text
Criar prestação mensal
Emitir prestação
Atualizar valor pago
Atualizar valor em dívida
Marcar como paga
Marcar como parcialmente paga
Marcar como vencida
Cancelar prestação quando permitido
Aplicar dispensa/waiver com justificação
```

---

## 12.4 LeasePaymentService

Responsável por:

```text
Registar pagamento manual
Confirmar pagamento
Reverter pagamento
Validar duplicados por referência/data/valor
Associar pagamento à conta financeira
Criar movimento financeiro
```

---

## 12.5 PaymentAllocationService

Responsável por:

```text
Alocar pagamento a prestações antigas primeiro, salvo indicação manual
Permitir alocação manual
Validar saldo disponível
Atualizar prestação
Atualizar pagamento
Atualizar conta corrente
Reverter alocação
Criar movimentos financeiros
```

Regra recomendada:

```text
Por defeito, alocar pagamentos à prestação vencida mais antiga.
```

---

## 12.6 PaymentImportService

Responsável por:

```text
Receber ficheiro de importação
Guardar ficheiro em storage privado
Criar PaymentImportBatch
Ler CSV ou formato simples configurável
Criar PaymentImportRow
Tentar matching por referência de prestação, contrato ou conta
Criar pagamentos pendentes ou confirmados conforme regra
Marcar linhas unmatched
Permitir conciliação manual posterior
Documentar erros por linha
```

Não implementar ligação bancária real nesta sprint.

---

## 12.7 PaymentReceiptService

Responsável por:

```text
Gerar recibo interno/comprovativo
Gerar número de recibo
Gerar HTML
Gerar PDF se infraestrutura existir
Guardar em storage privado
Permitir download seguro
Cancelar recibo com motivo
Reemitir recibo quando permitido
```

Aviso obrigatório no recibo interno, se não houver validação fiscal:

```text
Documento interno de confirmação de pagamento. Não substitui documento fiscal certificado, salvo validação legal aplicável.
```

---

## 12.8 FinancialTransactionService

Responsável por:

```text
Criar movimentos financeiros
Calcular balance_after
Garantir rastreabilidade
Impedir alteração indevida de movimentos
Recalcular saldo a partir do histórico
```

---

## 12.9 AccountStatementService

Responsável por:

```text
Gerar conta corrente do contrato
Listar prestações
Listar pagamentos
Listar alocações
Listar recibos
Listar valores em atraso
Calcular saldo
Preparar vista para backoffice
Preparar vista simplificada para arrendatário
```

---

## 12.10 ArrearDetectionService

Responsável por:

```text
Identificar prestações vencidas não pagas
Criar Arrear
Atualizar days_overdue
Atualizar outstanding_amount
Fechar Arrear quando regularizado
Gerar alertas internos
```

Pode criar command Artisan opcional:

```bash
php artisan finance:detect-arrears
```

se fizer sentido na arquitetura.

---

## 12.11 DefaultNoticeService

Responsável por:

```text
Criar aviso de incumprimento
Gerar número de aviso
Gerar minuta
Emitir aviso internamente
Marcar como visível ao arrendatário quando aplicável
Cancelar aviso com motivo
Criar notificação interna
```

Não enviar email/SMS real sem integração segura.

---

## 12.12 RegularizationAgreementService

Responsável por:

```text
Criar acordo de regularização
Selecionar dívidas incluídas
Calcular prestações do acordo
Criar RegularizationInstallment
Aprovar acordo
Ativar acordo
Marcar acordo como concluído
Marcar acordo como incumprido
Cancelar acordo
```

---

## 12.13 RegularizationPaymentService

Responsável por:

```text
Alocar pagamentos a prestações de acordo
Atualizar estado das prestações
Atualizar estado do acordo
Detetar incumprimento de acordo
Gerar aviso de incumprimento do acordo
```

---

## 12.14 RentReviewService

Responsável por:

```text
Criar revisão periódica anual
Criar revisão extraordinária
Solicitar documentos
Analisar dados
Calcular nova renda
Aprovar revisão
Rejeitar revisão
Aplicar nova renda com data de eficácia
Criar histórico
```

---

## 12.15 IncomeChangeService

Responsável por:

```text
Permitir declaração de alteração de rendimentos pelo arrendatário
Guardar descrição e valores declarados
Permitir anexos se Sprint 6 existir
Submeter declaração
Analisar declaração no backoffice
Aceitar ou rejeitar
Gerar RentReview quando aceite
```

---

## 12.16 AnnualDocumentUpdateService

Responsável por:

```text
Criar pedido anual de atualização documental
Definir prazo
Notificar arrendatário internamente
Permitir submissão de documentos
Analisar submissão
Aceitar ou rejeitar
Marcar pedido como overdue
Gerar RentReview quando aplicável
```

---

## 12.17 RentRecalculationService

Responsável por:

```text
Reutilizar regras de renda da Sprint 13
Criar novo cálculo de renda
Comparar renda anterior e nova renda
Guardar snapshot
Propor nova renda
Aplicar nova renda após aprovação
Criar novo RentSchedule a partir da data de eficácia
Preservar histórico anterior
```

---

## 12.18 FinanceNotificationService

Responsável por:

```text
Criar notificação interna de renda emitida
Criar notificação de pagamento registado
Criar notificação de recibo disponível
Criar notificação de atraso
Criar notificação de aviso de incumprimento
Criar notificação de acordo de regularização
Criar notificação de pedido anual documental
Criar notificação de revisão de renda
Usar OfficialNotification se existir
Não enviar email/SMS real sem integração segura
```

---

# 13. Controllers

Criar controllers conforme a arquitetura existente.

## Backoffice

Namespace recomendado:

```text
App\Http\Controllers\Backoffice
```

Controllers:

```text
Backoffice\TenantFinancialAccountController
Backoffice\RentScheduleController
Backoffice\RentInstallmentController
Backoffice\LeasePaymentController
Backoffice\PaymentImportController
Backoffice\PaymentReceiptController
Backoffice\AccountStatementController
Backoffice\ArrearController
Backoffice\DefaultNoticeController
Backoffice\RegularizationAgreementController
Backoffice\RentReviewController
Backoffice\IncomeChangeDeclarationController
Backoffice\AnnualDocumentUpdateRequestController
```

## Área do candidato / arrendatário

Namespace recomendado:

```text
App\Http\Controllers\Candidate
```

Controllers:

```text
Candidate\FinancialAccountController
Candidate\RentInstallmentController
Candidate\LeasePaymentController
Candidate\PaymentReceiptController
Candidate\DefaultNoticeController
Candidate\RegularizationAgreementController
Candidate\RentReviewController
Candidate\IncomeChangeDeclarationController
Candidate\AnnualDocumentUpdateRequestController
```

O candidato/arrendatário pode consultar a sua situação financeira, mas não pode alterar pagamentos, saldos ou estados financeiros.

---

# 14. Form Requests

Criar:

```text
StoreTenantFinancialAccountRequest
GenerateRentScheduleRequest
StoreRentInstallmentRequest
UpdateRentInstallmentRequest

StoreLeasePaymentRequest
ConfirmLeasePaymentRequest
ReverseLeasePaymentRequest
AllocatePaymentRequest
ReversePaymentAllocationRequest

ImportPaymentsRequest
ProcessPaymentImportRequest
ReconcilePaymentImportRowRequest

GeneratePaymentReceiptRequest
CancelPaymentReceiptRequest

CreateDefaultNoticeRequest
IssueDefaultNoticeRequest
CancelDefaultNoticeRequest

StoreRegularizationAgreementRequest
ApproveRegularizationAgreementRequest
CancelRegularizationAgreementRequest

StoreRentReviewRequest
ApproveRentReviewRequest
RejectRentReviewRequest
ApplyRentReviewRequest

StoreIncomeChangeDeclarationRequest
SubmitIncomeChangeDeclarationRequest
ReviewIncomeChangeDeclarationRequest

StoreAnnualDocumentUpdateRequestRequest
SubmitAnnualDocumentUpdateRequest
ReviewAnnualDocumentUpdateSubmissionRequest
```

## StoreLeasePaymentRequest

```text
lease_contract_id required|exists:lease_contracts,id
tenant_financial_account_id required|exists:tenant_financial_accounts,id
payment_date required|date
value_date nullable|date
amount required|numeric|min:0.01
currency required|string|max:3
payment_method required|string|max:100
payment_reference nullable|string|max:255
external_reference nullable|string|max:255
payer_name nullable|string|max:255
notes nullable|string|max:3000
```

## AllocatePaymentRequest

```text
lease_payment_id required|exists:lease_payments,id
allocations required|array|min:1
allocations.*.rent_installment_id required|exists:rent_installments,id
allocations.*.allocated_amount required|numeric|min:0.01
```

## ImportPaymentsRequest

```text
file required|file|mimes:csv,txt|max:10240
source required|string|max:100
notes nullable|string|max:3000
```

## CreateDefaultNoticeRequest

```text
arrear_id required|exists:arrears,id
notice_type required|string|max:100
subject required|string|max:255
body required|string|min:10|max:10000
legal_basis nullable|string|max:3000
response_deadline_at nullable|date|after:now
candidate_visible boolean
```

## StoreRegularizationAgreementRequest

```text
lease_contract_id required|exists:lease_contracts,id
tenant_financial_account_id required|exists:tenant_financial_accounts,id
total_debt_amount required|numeric|min:0.01
initial_payment_amount nullable|numeric|min:0
installment_amount required|numeric|min:0.01
installments_count required|integer|min:1|max:120
start_date required|date
end_date required|date|after_or_equal:start_date
terms required|string|min:10|max:10000
legal_basis nullable|string|max:3000
internal_notes nullable|string|max:3000
```

## StoreRentReviewRequest

```text
lease_contract_id required|exists:lease_contracts,id
review_type required|string|max:100
reason required|string|min:10|max:5000
effective_from nullable|date
legal_basis nullable|string|max:3000
internal_notes nullable|string|max:3000
```

## StoreIncomeChangeDeclarationRequest

```text
lease_contract_id required|exists:lease_contracts,id
change_type required|string|max:100
description required|string|min:10|max:5000
declared_monthly_income nullable|numeric|min:0
declared_annual_income nullable|numeric|min:0
effective_from required|date
```

## StoreAnnualDocumentUpdateRequestRequest

```text
lease_contract_id required|exists:lease_contracts,id
year required|integer|min:2000|max:2100
subject required|string|max:255
message required|string|min:10|max:5000
deadline_at required|date|after:now
```

---

# 15. Policies

Criar:

```text
TenantFinancialAccountPolicy
RentSchedulePolicy
RentInstallmentPolicy
LeasePaymentPolicy
PaymentAllocationPolicy
PaymentImportPolicy
PaymentReceiptPolicy
FinancialTransactionPolicy
ArrearPolicy
DefaultNoticePolicy
RegularizationAgreementPolicy
RentReviewPolicy
IncomeChangeDeclarationPolicy
AnnualDocumentUpdateRequestPolicy
```

## Regras para arrendatário/candidato

```text
Só vê a sua própria conta financeira.
Só vê prestações do seu próprio contrato.
Só vê pagamentos do seu próprio contrato.
Só vê recibos próprios.
Só descarrega recibos próprios.
Só vê avisos próprios.
Só vê acordos de regularização próprios.
Só submete alteração de rendimentos do seu próprio contrato.
Só submete documentos em pedidos próprios.
Não cria pagamentos.
Não confirma pagamentos.
Não reverte pagamentos.
Não altera saldos.
Não cria avisos de incumprimento.
Não aprova revisões de renda.
Não acede ao backoffice financeiro.
```

## Regras para técnico municipal

```text
Pode consultar situação financeira conforme permissão.
Pode gerar plano de rendas se autorizado.
Pode emitir avisos se autorizado.
Pode propor acordo de regularização.
Pode iniciar revisão de renda.
Não confirma pagamento se política exigir perfil financeiro.
```

## Regras para gestor financeiro

```text
Pode registar pagamentos.
Pode importar pagamentos.
Pode confirmar pagamentos.
Pode alocar pagamentos.
Pode emitir recibos internos.
Pode consultar saldos.
Pode gerir acordos financeiros se autorizado.
```

## Regras para admin

```text
Pode gerir toda a área financeira.
Pode reverter pagamentos com justificação.
Pode aprovar acordos.
Pode aplicar revisões de renda.
Pode gerar avisos.
```

## Regras para auditor

```text
Pode consultar histórico financeiro.
Pode consultar importações.
Pode consultar recibos.
Pode consultar incumprimentos.
Não pode alterar pagamentos.
Não pode reverter pagamentos.
Não pode aplicar revisão de renda.
```

---

# 16. Rotas

## Backoffice

Criar, preferencialmente:

```text
GET /backoffice/finance/accounts
GET /backoffice/finance/accounts/{tenantFinancialAccount}
POST /backoffice/finance/contracts/{leaseContract}/accounts

GET /backoffice/finance/rent-schedules
GET /backoffice/finance/rent-schedules/{rentSchedule}
POST /backoffice/finance/contracts/{leaseContract}/rent-schedules/generate
POST /backoffice/finance/rent-schedules/{rentSchedule}/suspend
POST /backoffice/finance/rent-schedules/{rentSchedule}/close
POST /backoffice/finance/rent-schedules/{rentSchedule}/cancel

GET /backoffice/finance/installments
GET /backoffice/finance/installments/{rentInstallment}
POST /backoffice/finance/installments/{rentInstallment}/issue
POST /backoffice/finance/installments/{rentInstallment}/waive
POST /backoffice/finance/installments/{rentInstallment}/cancel

GET /backoffice/finance/payments
GET /backoffice/finance/payments/create
POST /backoffice/finance/payments
GET /backoffice/finance/payments/{leasePayment}
POST /backoffice/finance/payments/{leasePayment}/confirm
POST /backoffice/finance/payments/{leasePayment}/reverse
POST /backoffice/finance/payments/{leasePayment}/allocate

GET /backoffice/finance/imports
GET /backoffice/finance/imports/create
POST /backoffice/finance/imports
GET /backoffice/finance/imports/{paymentImportBatch}
POST /backoffice/finance/imports/{paymentImportBatch}/process
POST /backoffice/finance/import-rows/{paymentImportRow}/reconcile
POST /backoffice/finance/imports/{paymentImportBatch}/reverse

GET /backoffice/finance/receipts
GET /backoffice/finance/receipts/{paymentReceipt}
POST /backoffice/finance/payments/{leasePayment}/receipts/generate
GET /backoffice/finance/receipts/{paymentReceipt}/download
POST /backoffice/finance/receipts/{paymentReceipt}/cancel

GET /backoffice/finance/account-statements/{tenantFinancialAccount}

GET /backoffice/finance/arrears
GET /backoffice/finance/arrears/{arrear}
POST /backoffice/finance/arrears/detect
POST /backoffice/finance/arrears/{arrear}/close
POST /backoffice/finance/arrears/{arrear}/waive

GET /backoffice/finance/default-notices
GET /backoffice/finance/default-notices/create
POST /backoffice/finance/default-notices
GET /backoffice/finance/default-notices/{defaultNotice}
POST /backoffice/finance/default-notices/{defaultNotice}/issue
POST /backoffice/finance/default-notices/{defaultNotice}/cancel

GET /backoffice/finance/regularization-agreements
GET /backoffice/finance/regularization-agreements/create
POST /backoffice/finance/regularization-agreements
GET /backoffice/finance/regularization-agreements/{regularizationAgreement}
POST /backoffice/finance/regularization-agreements/{regularizationAgreement}/approve
POST /backoffice/finance/regularization-agreements/{regularizationAgreement}/activate
POST /backoffice/finance/regularization-agreements/{regularizationAgreement}/cancel
POST /backoffice/finance/regularization-agreements/{regularizationAgreement}/mark-breached

GET /backoffice/finance/rent-reviews
GET /backoffice/finance/rent-reviews/create
POST /backoffice/finance/rent-reviews
GET /backoffice/finance/rent-reviews/{rentReview}
POST /backoffice/finance/rent-reviews/{rentReview}/calculate
POST /backoffice/finance/rent-reviews/{rentReview}/approve
POST /backoffice/finance/rent-reviews/{rentReview}/reject
POST /backoffice/finance/rent-reviews/{rentReview}/apply

GET /backoffice/finance/income-changes
GET /backoffice/finance/income-changes/{incomeChangeDeclaration}
POST /backoffice/finance/income-changes/{incomeChangeDeclaration}/accept
POST /backoffice/finance/income-changes/{incomeChangeDeclaration}/reject

GET /backoffice/finance/annual-document-updates
POST /backoffice/finance/annual-document-updates
GET /backoffice/finance/annual-document-updates/{annualDocumentUpdateRequest}
POST /backoffice/finance/annual-document-updates/{annualDocumentUpdateRequest}/accept
POST /backoffice/finance/annual-document-updates/{annualDocumentUpdateRequest}/reject
POST /backoffice/finance/annual-document-updates/{annualDocumentUpdateRequest}/mark-overdue
```

## Área do candidato / arrendatário

Criar, preferencialmente:

```text
GET /area-candidato/financeiro
GET /area-candidato/financeiro/conta-corrente
GET /area-candidato/financeiro/prestacoes
GET /area-candidato/financeiro/prestacoes/{rentInstallment}
GET /area-candidato/financeiro/pagamentos
GET /area-candidato/financeiro/pagamentos/{leasePayment}
GET /area-candidato/financeiro/recibos
GET /area-candidato/financeiro/recibos/{paymentReceipt}
GET /area-candidato/financeiro/recibos/{paymentReceipt}/download

GET /area-candidato/financeiro/incumprimentos
GET /area-candidato/financeiro/avisos/{defaultNotice}

GET /area-candidato/financeiro/acordos
GET /area-candidato/financeiro/acordos/{regularizationAgreement}

GET /area-candidato/financeiro/revisoes-renda
GET /area-candidato/financeiro/revisoes-renda/{rentReview}

GET /area-candidato/financeiro/alteracao-rendimentos/criar
POST /area-candidato/financeiro/alteracao-rendimentos
GET /area-candidato/financeiro/alteracao-rendimentos/{incomeChangeDeclaration}
POST /area-candidato/financeiro/alteracao-rendimentos/{incomeChangeDeclaration}/submeter

GET /area-candidato/financeiro/atualizacao-documental
GET /area-candidato/financeiro/atualizacao-documental/{annualDocumentUpdateRequest}
POST /area-candidato/financeiro/atualizacao-documental/{annualDocumentUpdateRequest}/submeter
```

---

# 17. Views / páginas

Se o projeto usa Blade, criar:

## Backoffice

```text
resources/views/backoffice/finance/accounts/index.blade.php
resources/views/backoffice/finance/accounts/show.blade.php

resources/views/backoffice/finance/rent-schedules/index.blade.php
resources/views/backoffice/finance/rent-schedules/show.blade.php

resources/views/backoffice/finance/installments/index.blade.php
resources/views/backoffice/finance/installments/show.blade.php

resources/views/backoffice/finance/payments/index.blade.php
resources/views/backoffice/finance/payments/create.blade.php
resources/views/backoffice/finance/payments/show.blade.php

resources/views/backoffice/finance/imports/index.blade.php
resources/views/backoffice/finance/imports/create.blade.php
resources/views/backoffice/finance/imports/show.blade.php

resources/views/backoffice/finance/receipts/index.blade.php
resources/views/backoffice/finance/receipts/show.blade.php

resources/views/backoffice/finance/account-statements/show.blade.php

resources/views/backoffice/finance/arrears/index.blade.php
resources/views/backoffice/finance/arrears/show.blade.php

resources/views/backoffice/finance/default-notices/index.blade.php
resources/views/backoffice/finance/default-notices/create.blade.php
resources/views/backoffice/finance/default-notices/show.blade.php

resources/views/backoffice/finance/regularization-agreements/index.blade.php
resources/views/backoffice/finance/regularization-agreements/create.blade.php
resources/views/backoffice/finance/regularization-agreements/show.blade.php

resources/views/backoffice/finance/rent-reviews/index.blade.php
resources/views/backoffice/finance/rent-reviews/create.blade.php
resources/views/backoffice/finance/rent-reviews/show.blade.php

resources/views/backoffice/finance/income-changes/index.blade.php
resources/views/backoffice/finance/income-changes/show.blade.php

resources/views/backoffice/finance/annual-document-updates/index.blade.php
resources/views/backoffice/finance/annual-document-updates/show.blade.php
```

## Área do candidato

```text
resources/views/candidate/finance/dashboard.blade.php
resources/views/candidate/finance/account-statement.blade.php
resources/views/candidate/finance/installments/index.blade.php
resources/views/candidate/finance/installments/show.blade.php
resources/views/candidate/finance/payments/index.blade.php
resources/views/candidate/finance/receipts/index.blade.php
resources/views/candidate/finance/receipts/show.blade.php
resources/views/candidate/finance/default-notices/index.blade.php
resources/views/candidate/finance/default-notices/show.blade.php
resources/views/candidate/finance/regularization-agreements/index.blade.php
resources/views/candidate/finance/regularization-agreements/show.blade.php
resources/views/candidate/finance/rent-reviews/index.blade.php
resources/views/candidate/finance/rent-reviews/show.blade.php
resources/views/candidate/finance/income-changes/create.blade.php
resources/views/candidate/finance/income-changes/show.blade.php
resources/views/candidate/finance/annual-document-updates/index.blade.php
resources/views/candidate/finance/annual-document-updates/show.blade.php
```

## Documentos financeiros

Criar templates:

```text
resources/views/finance/receipts/payment-receipt.blade.php
resources/views/finance/notices/default-notice.blade.php
resources/views/finance/regularization-agreements/agreement.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 18. UX obrigatória no backoffice

## Dashboard financeiro

Mostrar:

```text
Total de contratos ativos
Total mensal emitido
Total pago no mês
Total em atraso
Número de contratos com atraso
Atraso médio em dias
Acordos ativos
Revisões de renda pendentes
Pedidos documentais pendentes
```

## Conta corrente

Mostrar:

```text
Contrato
Arrendatário
Habitação
Renda atual
Saldo atual
Total emitido
Total pago
Total em atraso
Prestações
Pagamentos
Recibos
Avisos
Acordos
Revisões de renda
Histórico financeiro
```

## Prestação mensal

Mostrar:

```text
Período
Data de vencimento
Valor
Valor pago
Valor em dívida
Estado
Dias em atraso
Pagamentos associados
Ações
```

## Pagamento

Mostrar:

```text
Número de pagamento
Data de pagamento
Valor
Método
Referência
Estado
Origem
Prestações liquidadas
Valor por alocar
Recibos
Ações
```

## Incumprimento

Mostrar:

```text
Contrato
Arrendatário
Prestação
Valor em dívida
Dias em atraso
Avisos emitidos
Acordo de regularização, se existir
Estado
Ações
```

## Revisão de renda

Mostrar:

```text
Contrato
Renda atual
Motivo da revisão
Rendimentos anteriores
Novos rendimentos
Renda proposta
Data de eficácia
Estado
Histórico
Ações
```

---

# 19. UX obrigatória para arrendatário

## Dashboard financeiro

Mostrar:

```text
Renda mensal atual
Próxima prestação
Data de vencimento
Valor em aberto
Pagamentos registados
Recibos disponíveis
Avisos pendentes
Acordos ativos
Revisões de renda
```

Copy obrigatório:

```text
Esta área apresenta a situação financeira registada no sistema municipal. Caso detete alguma divergência, contacte os serviços municipais.
```

## Conta corrente

Mostrar:

```text
Prestações emitidas
Pagamentos registados
Recibos internos disponíveis
Valores em atraso
Acordos de regularização
Histórico resumido
```

## Alteração de rendimentos

Copy obrigatório:

```text
Pode comunicar alterações relevantes aos rendimentos do agregado durante a vigência do contrato. A alteração será analisada pelos serviços municipais e poderá originar revisão da renda.
```

## Atualização documental anual

Copy obrigatório:

```text
Os serviços municipais solicitaram a atualização documental anual associada ao seu contrato. Submeta os documentos pedidos dentro do prazo indicado.
```

---

# 20. Regras de plano mensal de rendas

O plano mensal deve:

```text
Usar contrato ativo
Usar monthly_rent do contrato
Usar payment_day do contrato, se existir
Gerar uma prestação por mês
Evitar duplicados
Criar referência única por prestação
Permitir suspensão/fecho
Permitir novo plano após revisão de renda
```

Se contrato não tiver `payment_day`, usar regra configurável ou fallback documentado.

Não criar prestações para períodos fora do contrato.

---

# 21. Regras de pagamento e conciliação

Pagamentos devem:

```text
Ter número único
Ter data
Ter valor
Ter método
Ter estado
Ser associados à conta financeira
Poder ser alocados a uma ou mais prestações
Atualizar saldo da prestação
Atualizar saldo da conta
Criar movimento financeiro
```

Alocação padrão:

```text
Pagar primeiro a prestação vencida mais antiga.
Depois prestações seguintes.
Se sobrar valor, manter crédito em conta ou marcar como parcialmente alocado conforme regra.
```

Não eliminar pagamento confirmado.

Usar reversão com motivo.

---

# 22. Regras de atrasos e incumprimento

O sistema deve:

```text
Identificar prestações vencidas não pagas
Identificar prestações parcialmente pagas
Calcular dias em atraso
Criar Arrear
Atualizar Arrear
Fechar Arrear quando regularizado
Gerar alertas internos
Permitir aviso de incumprimento
```

Não aplicar sanções legais automáticas sem validação.

Não avançar para processo judicial.

---

# 23. Regras de acordos de regularização

O acordo deve:

```text
Estar associado a contrato e conta financeira
Incluir dívida total
Definir número de prestações
Definir valor por prestação
Definir calendário
Gerar prestações de regularização
Marcar dívida como under_agreement
Controlar pagamentos
Marcar acordo como completed quando liquidado
Marcar acordo como breached quando houver incumprimento
```

Não alterar contrato automaticamente por causa do acordo, salvo regra explícita.

---

# 24. Regras de revisão de renda

A revisão de renda deve:

```text
Preservar renda anterior
Preservar snapshot anterior
Guardar novos dados
Calcular nova renda usando regras da Sprint 13
Exigir aprovação
Aplicar nova renda apenas a partir de effective_from
Criar novo RentSchedule ou versão
Manter histórico
```

Tipos de revisão:

```text
annual
periodic
income_change
manual
extraordinary
```

Não substituir histórico financeiro anterior.

Não alterar prestações já pagas, salvo operação manual justificada.

---

# 25. Integração com documentos

Se Sprint 6 existir:

```text
Atualização documental anual deve usar DocumentSubmission.
Declaração de alteração de rendimentos pode associar documentos.
Acordo de regularização assinado pode associar documento.
Recibos e avisos gerados devem usar storage privado.
Downloads devem passar por controller autorizado.
```

Não criar storage documental paralelo.

---

# 26. Integração com contrato — Sprint 13

A Sprint 14 deve consumir dados da Sprint 13.

Regras:

```text
Usar LeaseContract ativo.
Usar monthly_rent do contrato.
Usar payment_day do contrato.
Usar ContractDeposit apenas para consulta/integração.
Não alterar dados contratuais base sem revisão formal.
Não ativar contrato nesta sprint.
Não emitir contrato nesta sprint.
```

Quando renda for revista:

```text
Criar RentReview.
Criar novo cálculo.
Aplicar nova renda com effective_from.
Criar novo RentSchedule.
Preservar histórico.
```

---

# 27. Integração com notificações

Se existir `OfficialNotification`, usar esse modelo para:

```text
rent_installment_issued
payment_registered
payment_receipt_available
rent_overdue
default_notice_issued
regularization_agreement_created
regularization_agreement_breached
rent_review_requested
rent_review_applied
annual_document_update_requested
income_change_received
```

Se não existir:

```text
Criar registo interno equivalente ou documentar pendência.
Não enviar email/SMS real.
```

Não marcar notificação como `sent` sem envio real.

---

# 28. Auditoria

Se existir auditoria, auditar:

```text
Criação de conta financeira
Geração de plano de rendas
Geração de prestação
Registo de pagamento
Confirmação de pagamento
Alocação de pagamento
Reversão de pagamento
Importação de pagamentos
Conciliação de importação
Geração de recibo
Cancelamento de recibo
Deteção de incumprimento
Emissão de aviso de incumprimento
Criação de acordo de regularização
Aprovação de acordo
Incumprimento de acordo
Pedido de revisão de renda
Aprovação de revisão de renda
Aplicação de nova renda
Pedido anual de documentação
Submissão de alteração de rendimentos
Aceitação/rejeição de alteração de rendimentos
```

Não criar auditoria paralela se já existir sistema de auditoria.

Se não existir auditoria, documentar pendência.

Não guardar dados sensíveis excessivos nos logs.

---

# 29. RGPD e segurança

Regras obrigatórias:

```text
Dados financeiros são sensíveis.
Arrendatário só vê a sua própria situação financeira.
Arrendatário não vê situação financeira de terceiros.
Backoffice exige permissões.
Auditor não altera dados.
Gestor financeiro só altera dados permitidos.
Não expor recibos publicamente.
Não guardar recibos em storage público.
Não expor storage_path.
Não colocar NIF, email ou nome no nome do ficheiro.
Não guardar dados bancários sensíveis desnecessários.
Não permitir mass assignment de saldos.
Não permitir mass assignment de estados financeiros.
Não permitir download sem policy.
Importações devem usar storage privado.
```

---

# 30. Seeders e factories

Criar factories:

```text
TenantFinancialAccountFactory
RentScheduleFactory
RentInstallmentFactory
LeasePaymentFactory
PaymentAllocationFactory
PaymentImportBatchFactory
PaymentImportRowFactory
PaymentReceiptFactory
FinancialTransactionFactory
ArrearFactory
DefaultNoticeFactory
RegularizationAgreementFactory
RegularizationInstallmentFactory
RentReviewFactory
IncomeChangeDeclarationFactory
AnnualDocumentUpdateRequestFactory
AnnualDocumentUpdateSubmissionFactory
```

Criar seeders opcionais:

```text
FinanceDemoSeeder
RentScheduleDemoSeeder
PaymentDemoSeeder
DefaultNoticeDemoSeeder
RegularizationAgreementDemoSeeder
```

Usar apenas dados fictícios.

Não usar dados pessoais reais.

Recibos/avisos demo devem conter aviso:

```text
DOCUMENTO DEMO — SUJEITO A VALIDAÇÃO JURÍDICA/FISCAL
```

---

# 31. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text
guest_cannot_access_financial_area
tenant_can_access_own_financial_account
tenant_cannot_access_other_financial_account
tenant_can_view_own_installments
tenant_can_view_own_payments
tenant_can_download_own_receipt
tenant_cannot_download_other_receipt
tenant_cannot_access_backoffice_finance
finance_manager_can_register_payment_if_authorized
auditor_can_view_financial_history_without_editing
```

## Conta financeira

```text
financial_account_can_be_created_for_active_contract
financial_account_requires_contract
contract_can_have_only_one_active_financial_account
account_number_is_unique
account_balance_is_recalculated_from_transactions
```

## Plano mensal

```text
rent_schedule_can_be_generated_for_active_contract
rent_schedule_generates_monthly_installments
rent_schedule_uses_contract_monthly_rent
rent_schedule_uses_contract_payment_day
rent_schedule_does_not_duplicate_installments
rent_schedule_does_not_generate_installments_outside_contract_period
```

## Pagamentos

```text
finance_manager_can_register_manual_payment
payment_requires_amount_and_date
payment_number_is_unique
payment_can_be_confirmed
confirmed_payment_creates_financial_transaction
payment_can_be_allocated_to_oldest_installment
payment_allocation_updates_installment_paid_amount
payment_allocation_updates_outstanding_amount
payment_can_be_reversed_with_reason
reversed_payment_updates_account_balance
```

## Importação

```text
payment_import_batch_can_be_created_from_csv
payment_import_file_is_stored_privately
payment_import_creates_rows
payment_import_matches_installment_by_reference
unmatched_rows_remain_pending
import_row_can_be_reconciled_manually
failed_rows_store_error_message
```

## Recibos internos

```text
receipt_can_be_generated_for_confirmed_payment
receipt_number_is_unique
receipt_is_stored_privately
receipt_download_requires_authorization
receipt_can_be_cancelled_with_reason
receipt_contains_internal_document_warning_when_not_fiscal
```

## Incumprimentos

```text
arrear_detection_finds_overdue_unpaid_installments
arrear_detection_finds_partially_paid_overdue_installments
arrear_days_overdue_are_calculated
arrear_is_closed_when_installment_is_paid
default_notice_can_be_created_for_arrear
default_notice_requires_subject_and_body
issued_default_notice_can_be_visible_to_tenant
tenant_can_view_own_default_notice
```

## Acordos de regularização

```text
regularization_agreement_can_be_created_for_debt
regularization_agreement_generates_installments
agreement_activation_marks_arrears_under_agreement
regularization_payment_updates_agreement_installment
agreement_is_completed_when_all_installments_paid
agreement_can_be_marked_breached_when_installment_overdue
```

## Revisão de renda

```text
rent_review_can_be_created_for_active_contract
rent_review_preserves_current_rent
rent_review_creates_new_income_snapshot
rent_review_can_recalculate_rent
rent_review_requires_approval_before_application
approved_rent_review_creates_new_rent_schedule
applied_rent_review_preserves_old_schedule_history
```

## Alteração de rendimentos

```text
tenant_can_submit_income_change_for_own_contract
tenant_cannot_submit_income_change_for_other_contract
income_change_requires_description
accepted_income_change_can_create_rent_review
rejected_income_change_does_not_change_rent
```

## Atualização documental anual

```text
annual_document_update_request_can_be_created
annual_document_update_has_deadline
tenant_can_submit_own_annual_documents
tenant_cannot_submit_documents_for_other_contract
overdue_document_update_can_be_marked_overdue
accepted_document_update_can_trigger_rent_review
```

## Segurança

```text
tenant_cannot_mass_assign_payment_status
tenant_cannot_mass_assign_account_balance
tenant_cannot_confirm_payment
tenant_cannot_reverse_payment
tenant_cannot_create_default_notice
tenant_cannot_apply_rent_review
receipt_file_name_does_not_contain_nif
receipt_file_name_does_not_contain_email
receipt_file_is_not_publicly_accessible
import_file_is_not_publicly_accessible
```

## Auditoria, se existir

```text
creating_financial_account_generates_audit_log
generating_rent_schedule_generates_audit_log
registering_payment_generates_audit_log
allocating_payment_generates_audit_log
reversing_payment_generates_audit_log
generating_receipt_generates_audit_log
issuing_default_notice_generates_audit_log
applying_rent_review_generates_audit_log
```

Se alguma dependência não existir, documentar teste como pendente em vez de criar teste quebrado.

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

Atualizar, se existirem:

```text
docs/backlog/sprint-14-pagamentos-incumprimentos-revisao-renda.md
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
Pendências para Sprint 15
Validações jurídicas/fiscais pendentes
Limitações dos recibos
Limitações da importação de pagamentos
Limitações de notificações
```

---

# 34. Critérios de aceitação

A Sprint 14 está concluída quando:

```text
Existe conta corrente por contrato ativo
O sistema cria plano mensal de rendas
O sistema gera prestações mensais
O Município consegue registar pagamentos
O Município consegue importar pagamentos por ficheiro, se aplicável
O Município consegue associar pagamentos a prestações
O sistema atualiza valores pagos e em dívida
O sistema gera recibo interno/comprovativo
O Município consegue consultar quem pagou
O Município consegue consultar quem está em atraso
O Município consegue consultar há quanto tempo existe atraso
O sistema gera alertas de incumprimento
O sistema permite emitir aviso de incumprimento
O sistema permite criar acordo de regularização
O sistema controla prestações do acordo
A renda pode ser revista com histórico
A alteração de rendimentos pode originar revisão
O pedido anual de atualização documental funciona
A nova renda é aplicada apenas após aprovação
O histórico financeiro é preservado
O arrendatário consegue consultar a sua situação financeira
O arrendatário não vê dados financeiros de terceiros
Backoffice exige permissões
Auditoria é usada se existir
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foi implementada cobrança real
Não foi implementada faturação fiscal oficial
Não foi implementada integração bancária real
Não foi implementada comunicação à AT
```

---

# 35. Resposta final esperada do Codex

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
19. Validações jurídicas/fiscais pendentes
20. Limitações dos recibos
21. Limitações da importação de pagamentos
22. Limitações de notificações
23. Confirmação de que não foram implementadas funcionalidades fora de âmbito
24. Recomendação objetiva para avançar ou não para Sprint 15
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 36. Definition of Done

A Sprint 14 só está concluída quando a plataforma permitir gerir a conta corrente do contrato, emitir e acompanhar prestações mensais, registar e conciliar pagamentos, controlar incumprimentos, gerar avisos, criar acordos de regularização, rever renda com histórico e permitir ao arrendatário consultar a sua situação financeira.

O resultado deve permitir que a Sprint 15 trate a ocupação, ocorrências, manutenção e gestão operacional da habitação já contratualizada.

Fim da Sprint 14.

---

# Resultado da execução da Sprint 14

## Implementado

- Conta financeira por contrato ativo (`tenant_financial_accounts`).
- Plano de rendas (`rent_schedules`) e prestações mensais (`rent_installments`).
- Pagamentos processuais (`lease_payments`) e imputações (`payment_allocations`), preservando o `payments` legado.
- Importação CSV interna (`payment_import_batches`, `payment_import_rows`).
- Comprovativos internos HTML em storage privado (`payment_receipts`).
- Extrato financeiro (`financial_transactions`).
- Incumprimentos (`arrears`), avisos (`default_notices`) e acordos de regularização (`regularization_agreements`, `regularization_installments`).
- Revisão de renda pós-contrato (`rent_reviews`) e alteração de rendimentos (`income_change_declarations`).
- Atualização documental anual (`annual_document_update_requests`, `annual_document_update_submissions`).
- Policies, Form Requests, Services, Controllers, rotas e views para backoffice e área do candidato.
- Navegação backoffice/candidato para o módulo financeiro.
- Testes de feature específicos da sprint.

## Limitações assumidas

- Comprovativos são internos e não têm valor de recibo fiscal oficial.
- Importação de pagamentos é CSV interno e não representa integração bancária, SEPA, MB, SIBS ou gateway.
- Não existe comunicação à AT.
- Não existe cobrança real.
- Notificações permanecem internas/in-app, sem email/SMS/carta registada ou prova externa de entrega.
- A revisão de renda pós-contrato usa cálculo manual/documentado nesta sprint e requer aprovação antes de aplicação.

## Comandos executados

- `php artisan route:list` passou e apresentou 590 rotas.
- `php artisan migrate` passou e aplicou `2026_06_13_010000_create_finance_tables.php`.
- `php artisan test --filter=Sprint14FinanceTest` passou com 4 testes/34 asserções após correção de duas falhas detetadas.
- `./vendor/bin/pint` passou e formatou ficheiros.
- `./vendor/bin/pint --test` passou.
- `php artisan test` passou com 126 testes/755 asserções.
- `npm run build` passou com Vite.
- `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.

## Problemas encontrados e correções

- A primeira execução do teste específico falhou porque `rent_installments.reference` não era preenchido ao usar `forceFill`; foi corrigido no `RentScheduleService`.
- A primeira execução também expôs recriação indevida de conta financeira quando o objeto `Contract` tinha a relação cacheada como nula; `TenantFinancialAccountService` passou a consultar a relação via query.

## Pendências para Sprint 15

- Gestão de ocupação, manutenção, ocorrências, vistorias e pedidos técnicos.
- Integração futura entre incumprimentos financeiros e decisões operacionais de manutenção/ocupação apenas após validação municipal.
- Validação jurídica/fiscal de comprovativos, avisos, acordos e revisão de renda.
