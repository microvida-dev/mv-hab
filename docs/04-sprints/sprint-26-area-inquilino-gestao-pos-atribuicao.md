# Sprint 26 — Área do Inquilino e Gestão Pós-Atribuição

## Estado de execução — 19/06/2026

Sprint executada tecnicamente.

### Implementado

- Área do inquilino em `/area-inquilino` com dashboard, contratos, faturas, pagamentos, manutenção, vistorias e comunicações.
- Perfil/acesso de inquilino com `TenantProfile` e `TenantContractAccess`.
- Faturas operacionais em `tenant_invoices`.
- Pagamentos operacionais em `tenant_payments`.
- Cobranças internas em `tenant_charge_runs` e `tenant_charge_run_items`.
- Comunicações pós-atribuição em `tenant_communications` e `tenant_communication_messages`.
- Dashboard operacional do senhorio/município com snapshots.
- Comandos `tenants:generate-charges` e `tenants:mark-overdue-invoices`.
- Reutilização dos módulos existentes de contratos, contas financeiras, manutenção e vistorias.
- Teste `tests/Feature/Sprint26TenantPostAwardTest.php`.

### Ficheiros/documentação criados

- `docs/tenant/tenant-portal.md`
- `docs/tenant/contracts.md`
- `docs/tenant/invoices-and-payments.md`
- `docs/tenant/automatic-charges.md`
- `docs/tenant/maintenance-requests.md`
- `docs/tenant/inspections.md`
- `docs/tenant/communications.md`
- `docs/backoffice/landlord-dashboard.md`
- `docs/backoffice/maintenance-reports.md`
- `docs/qa/sprint-26-quality-report.md`

### Pendências

- Validar juridicamente textos financeiros e comprovativos oficiais.
- Validar regras municipais de bloqueio/suspensão do acesso de inquilino.
- Definir exportações oficiais de manutenção e KPIs finais.
- Integrações bancárias, SEPA, gateways, recibos fiscais oficiais e assinatura digital continuam fora de âmbito.

### PHPStan

PHPStan não foi executado nesta sprint por instrução explícita posterior do utilizador: “ignore o phpstan”.

### Comandos já executados

- `php artisan migrate` — sucesso.
- `php artisan test --filter=Sprint26TenantPostAwardTest` — sucesso, 5 testes e 31 asserções.
- `php artisan route:list` — sucesso, 1067 rotas.
- `php artisan test` — falhou por limite de memória PHP de 128 MB apesar de reportar 206 testes e 1342 asserções passadas antes do fim prematuro do processo.
- `php -d memory_limit=-1 ./vendor/bin/phpunit` — sucesso, 215 testes e 1394 asserções.
- `npm run build` — sucesso.
- `./vendor/bin/pint` — formatou ficheiros.
- `./vendor/bin/pint --test` — sucesso.

## Prioridade de desenvolvimento

Esta sprint pertence à fase de exploração habitacional e gestão pós-atribuição, com foco na consolidação da área do inquilino, contratos, faturas, pagamentos, cobranças, manutenção, vistorias, comunicações, histórico de intervenções e dashboard operacional do senhorio/município.

A Sprint 26 deve transformar a transição do candidato vencedor para inquilino numa área funcional completa, mantendo a rastreabilidade desde a candidatura até à gestão corrente do contrato de arrendamento.

Esta sprint deve preservar os módulos existentes de:

```text
Registo de adesão
Simulador
Candidaturas
Gestão documental
Workflow administrativo
Classificação e ranking
Listas provisórias e definitivas
Sorteios e atribuição
Contratos
Pagamentos
Notificações
Acompanhamento processual
Relatórios
Atas
Auditoria/RGPD
Área do inquilino
Manutenção
Vistorias
Comunicações
```

---

# 1. Objetivo da Sprint

Consolidar toda a fase pós-contratual.

Implementar:

```text
Consulta de contratos
Consulta de faturas
Gestão de pagamentos
Cobranças automáticas
Pedidos de manutenção
Agendamento de vistorias
Comunicações entre município e inquilino
Histórico de intervenções
Estado dos pedidos
Relatórios de manutenção
Dashboard operacional do senhorio
```

Entregáveis:

```text
Portal completo do inquilino
Gestão integrada da exploração habitacional
```

A plataforma deve permitir que o inquilino:

```text
Consulte contratos ativos e históricos
Consulte faturas/rendas emitidas
Consulte estado dos pagamentos
Descarregue comprovativos autorizados
Submeta pedidos de manutenção
Acompanhe estado dos pedidos
Consulte agendamentos de vistorias
Receba comunicações do município
Responda a comunicações quando aplicável
Consulte histórico de intervenções
Consulte notificações relacionadas com contrato, pagamentos, manutenção e vistorias
```

A plataforma deve permitir que o município/senhorio:

```text
Consulte dashboard operacional de exploração habitacional
Acompanhe contratos ativos
Acompanhe rendas/faturas emitidas, vencidas e pagas
Controle cobranças internas automáticas
Registe pagamentos manuais ou importados
Acompanhe incumprimentos
Receba e trate pedidos de manutenção
Planeie vistorias
Atribua técnicos/intervenções
Registe intervenções realizadas
Gere relatórios de manutenção
Comunique com inquilinos
Mantenha histórico e auditoria
```

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 26.

Não avances para Sprint 27 ou qualquer sprint futura sem validação explícita.

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

docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
docs/backlog/sprint-14-pagamentos-incumprimentos-revisao-renda.md
docs/backlog/sprint-15-manutencao-vistorias-gestao-imovel.md
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
docs/backlog/sprint-17-relatorios-indicadores-dashboard-executivo.md
docs/backlog/sprint-18-rgpd-seguranca-auditoria-avancada.md
docs/backlog/sprint-19-testes-integrados-qualidade.md
docs/backlog/sprint-23-acompanhamento-processual-avancado.md
docs/backlog/sprint-24-backoffice-operacional-gestao-procedimento.md
docs/backlog/sprint-25-sorteios-ordenacao-fecho-concurso.md
docs/backlog/sprint-26-area-inquilino-gestao-pos-atribuicao.md

docs/backoffice/tenant-transition.md
docs/backoffice/key-handover.md
docs/candidate-experience/process-tracking.md
docs/candidate-experience/process-timeline.md
docs/qa/test-coverage-matrix.md
docs/qa/quality-gates.md
docs/qa/regression-test-plan.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

Não alterar `.env`.

Não introduzir credenciais.

Não usar dados pessoais reais.

Não criar integração bancária real nesta sprint.

Não criar Débito Direto SEPA real sem configuração existente e aprovação explícita.

Não criar gateway de pagamento real sem configuração existente e aprovação explícita.

Não criar assinatura digital nesta sprint.

Não substituir o módulo de contratos existente.

Não substituir o módulo de pagamentos existente se já existir.

Não substituir o módulo de manutenção/vistorias existente se já existir.

Não duplicar entidades já existentes.

---

# 3. PHPStan obrigatório antes de publicar — contexto com 2471 erros legados

O projeto tem atualmente:

```text
2471 erros PHPStan legados
```

A Sprint 26 não tem como objetivo corrigir todos os erros legados.

A Sprint 26 tem como objetivo obrigatório:

```text
Não aumentar o número de erros PHPStan.
Não introduzir novos erros PHPStan nos ficheiros criados ou alterados.
Identificar claramente erros legados versus erros introduzidos pela sprint.
Executar PHPStan antes da implementação e antes da publicação.
Corrigir todos os erros PHPStan diretamente causados pela Sprint 26.
```

## 3.1 Verificação PHPStan inicial

Antes de criar ou alterar ficheiros, executar, se PHPStan existir:

```bash
mkdir -p storage/phpstan

php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint26-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint26-before.txt || true
```

Se existir `phpstan.neon`, usar:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint26-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint26-before.txt || true
```

Se existir script no `composer.json`, usar também o comando do projeto, por exemplo:

```bash
composer phpstan
```

Registar no relatório final:

```text
PHPStan inicial executado: sim/não
Total de erros legados conhecido: 2471
Ficheiro de output inicial criado
Comando usado
Falhou por memória: sim/não
Falhou por configuração: sim/não
```

Se PHPStan não existir, documentar:

```text
PHPStan/Larastan não está instalado/configurado. Não foi possível executar análise estática.
```

## 3.2 Estratégia para não misturar erros legados

Durante a implementação:

```text
Não corrigir erros PHPStan fora do âmbito da Sprint 26, salvo se bloquearem diretamente a sprint.
Não alterar ficheiros apenas para reduzir ruído PHPStan legado.
Não criar baseline artificial sem autorização.
Não esconder erros novos com ignoreErrors genéricos.
Não adicionar @phpstan-ignore sem justificação objetiva.
Não reduzir o nível do PHPStan.
Não remover paths analisados.
Não alterar configuração PHPStan para ocultar problemas.
Não alterar regras de análise estática para “passar”.
```

## 3.3 Verificação PHPStan antes de publicação

Antes de considerar a Sprint 26 pronta para publicação, executar:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint26-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint26-after.txt || true
```

Com config, se existir:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint26-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint26-after.txt || true
```

Depois, identificar erros nos ficheiros criados ou alterados nesta sprint.

Se existirem erros PHPStan em ficheiros da Sprint 26:

```text
Corrigir antes de concluir.
Não publicar como concluído enquanto houver erro novo causado pela Sprint 26.
```

Se existirem apenas os 2471 erros legados:

```text
Documentar que o passivo PHPStan legado permanece.
Confirmar que a Sprint 26 não adicionou erros novos nos ficheiros alterados.
```

Se a contagem aumentar:

```text
Identificar ficheiros novos/alterados.
Corrigir erros introduzidos.
Reexecutar PHPStan.
Documentar diferença.
```

## 3.4 Resultado PHPStan obrigatório no relatório final

A resposta final deve incluir:

```text
Estado PHPStan inicial
Estado PHPStan antes de publicação
Contagem legada assumida: 2471
Novos erros introduzidos pela Sprint 26: sim/não
Erros PHPStan em ficheiros criados/alterados: sim/não
Correções PHPStan aplicadas
Bloqueia publicação: sim/não
```

---

# 4. Inspeção inicial obrigatória

Antes de implementar, identificar:

```text
Versão do Laravel
Versão do PHP
Stack frontend real
Sistema de autenticação
Sistema de backoffice
Sistema de área do inquilino
Sistema de roles/permissões
Sistema de policies
Sistema de Form Requests
Sistema de candidaturas
Sistema de atribuição
Sistema de contratos
Sistema de rendas/faturas
Sistema de pagamentos
Sistema de cobranças
Sistema de incumprimentos
Sistema de manutenção
Sistema de vistorias
Sistema de comunicações
Sistema de notificações
Sistema de documentos privados
Sistema de relatórios
Sistema de dashboard operacional
Sistema de auditoria/RGPD
Sistema de testes
Configuração PHPStan/Larastan
Configuração Pint
Configuração PHPUnit/Pest
Comandos disponíveis em composer.json
Comandos disponíveis em package.json
```

Inspecionar models, migrations, controllers, services, requests, policies, factories e views existentes relacionados com:

```text
User
Role
Permission
Application
Contest
HousingUnit
Allocation
WinnerRegistration
Tenant
TenantProfile
TenantTransition
Contract
LeaseContract
ContractDocument
RentSchedule
RentInvoice
Invoice
Payment
PaymentReceipt
PaymentPlan
Debt
Arrears
Charge
AutomaticCharge
MaintenanceRequest
MaintenanceCategory
MaintenanceAttachment
MaintenanceIntervention
MaintenanceReport
Inspection
InspectionSchedule
InspectionReport
Communication
TenantCommunication
OfficialNotification
DocumentSubmission
AuditEvent
SensitiveDataAccessLog
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
TenantPortal
TenantDashboard
TenantInvoice
TenantPayment
TenantCharge
MaintenanceRequest
MaintenanceWorkOrder
InspectionAppointment
TenantCommunication
LandlordOperationalDashboard
MaintenanceReport
```

reaproveitar ou adaptar com compatibilidade.

---

# 5. Dependências funcionais

Esta sprint depende preferencialmente de:

```text
Sprint 13 — Cálculo de Renda, Contratos e Caução
Sprint 14 — Pagamentos, Incumprimentos e Revisão de Renda
Sprint 15 — Manutenção, Vistorias e Gestão de Imóvel
Sprint 16 — Notificações, Comunicações e Modelos Documentais
Sprint 17 — Relatórios, Indicadores e Dashboard Executivo
Sprint 18 — RGPD, Segurança e Auditoria Avançada
Sprint 23 — Acompanhamento Processual Avançado
Sprint 24 — Backoffice Operacional e Gestão do Procedimento
Sprint 25 — Sorteios, Ordenação e Fecho do Concurso
```

Dependências mínimas:

```text
User
HousingUnit
Contract ou atribuição equivalente
Tenant ou perfil de inquilino equivalente
Backoffice
Roles/permissions
```

Se o módulo de contratos existir:

```text
Reutilizar contratos existentes.
Não criar contrato paralelo incompatível.
Permitir consulta pelo inquilino apenas aos seus contratos.
```

Se o módulo de pagamentos existir:

```text
Reutilizar pagamentos existentes.
Não criar gateway externo.
Criar camada de consulta, estado e cobranças internas.
```

Se o módulo de manutenção existir:

```text
Reutilizar pedidos, categorias, anexos e intervenções existentes.
Não criar segundo módulo de manutenção incompatível.
```

Se o módulo de vistorias existir:

```text
Reutilizar agendamentos e relatórios existentes.
Integrar com área do inquilino e dashboard operacional.
```

Se o módulo de notificações existir:

```text
Usar notificações internas existentes.
Não criar sistema paralelo.
```

Se algum módulo não existir:

```text
Implementar camada mínima e extensível.
Documentar limitação.
Não inventar integrações externas.
Não assumir pagamentos reais se não houver configuração.
```

---

# 6. Validação funcional, administrativa e RGPD

Regras obrigatórias:

```text
Inquilino só vê contratos próprios.
Inquilino só vê faturas próprias.
Inquilino só vê pagamentos próprios.
Inquilino só vê pedidos de manutenção próprios ou da sua habitação contratada.
Inquilino só vê vistorias próprias ou da sua habitação contratada.
Inquilino só vê comunicações próprias.
Documentos contratuais devem ser privados.
Faturas e recibos devem ser privados.
Relatórios de manutenção com dados pessoais devem ser privados.
Backoffice deve respeitar roles e policies.
Cobranças automáticas nesta sprint são internas/simuladas/operacionais, não bancárias, salvo integração já existente.
Todas as ações críticas devem ser auditadas.
Não guardar dados sensíveis em logs técnicos.
Não expor paths de storage.
```

Copy obrigatório na área de pagamentos:

```text
Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.
```

Copy obrigatório nas cobranças automáticas internas:

```text
As cobranças automáticas registadas nesta plataforma correspondem à geração operacional de valores a cobrar e não implicam, por si só, movimento bancário externo sem integração devidamente configurada.
```

Copy obrigatório nos pedidos de manutenção:

```text
Os pedidos de manutenção serão analisados pelos serviços municipais, podendo ser solicitada informação adicional ou agendada vistoria/intervenção técnica.
```

Copy obrigatório nas vistorias:

```text
O agendamento de vistoria está sujeito à disponibilidade dos serviços municipais e à confirmação das partes envolvidas.
```

---

# 7. Âmbito incluído

Implementar:

```text
Portal completo do inquilino
Dashboard do inquilino
Consulta de contratos
Consulta de documentos contratuais
Consulta de faturas/rendas
Consulta de pagamentos
Consulta de recibos/comprovativos
Gestão interna de pagamentos
Cobranças automáticas internas
Jobs de geração de cobranças
Pedidos de manutenção
Categorias de manutenção
Anexos privados de manutenção
Estado dos pedidos
Agendamento de vistorias
Comunicações entre município e inquilino
Histórico de intervenções
Relatórios de manutenção
Dashboard operacional do senhorio/município
Indicadores de contratos
Indicadores de pagamentos
Indicadores de manutenção
Indicadores de vistorias
Notificações
Auditoria
Policies
Form Requests
Controllers
Services
Views/páginas
Factories
Seeders
Testes
Documentação
PHPStan antes/depois
```

---

# 8. Fora de âmbito

Não implementar nesta sprint:

```text
Gateway real de pagamento
Débito Direto SEPA real
Integração bancária
Reconciliação bancária automática real
Assinatura digital
Novo motor jurídico de contratos
Nova regra substantiva de renda
Integração externa com ERP
Integração externa com software de manutenção
IoT/sensores
Gestão de condomínio
Portal público de manutenção
Chat em tempo real por WebSocket
SMS real sem configuração existente
```

Esta sprint consolida a exploração habitacional e a área do inquilino, mas não cria integrações financeiras externas sem configuração existente.

---

# 9. Fluxos funcionais obrigatórios

## 9.1 Acesso à área do inquilino

```text
Utilizador autenticado entra na área do inquilino
→ Sistema valida perfil/role/tenant ativo
→ Sistema carrega contratos ativos
→ Sistema mostra resumo de faturas, pagamentos, pedidos e comunicações
→ Sistema bloqueia acesso se não existir perfil de inquilino ativo
```

## 9.2 Consulta de contratos

```text
Inquilino acede a contratos
→ Sistema lista contratos próprios
→ Inquilino abre contrato
→ Sistema mostra dados principais
→ Sistema mostra documentos contratuais autorizados
→ Sistema permite download autorizado
→ Sistema regista acesso se aplicável
```

## 9.3 Consulta de faturas e pagamentos

```text
Inquilino acede a pagamentos
→ Sistema lista faturas/rendas
→ Sistema mostra estado: emitida, vencida, paga, parcial, anulada
→ Sistema mostra pagamentos associados
→ Sistema permite descarregar recibo autorizado
→ Sistema mostra avisos quando há valores vencidos
```

## 9.4 Cobranças automáticas internas

```text
Job/ação autorizada identifica contratos ativos
→ Sistema calcula período de cobrança
→ Sistema gera fatura/renda interna
→ Sistema evita duplicados para o mesmo período
→ Sistema atualiza estado
→ Sistema cria notificação
→ Sistema audita execução
```

## 9.5 Registo de pagamento

```text
Técnico autorizado acede a fatura
→ Regista pagamento
→ Sistema valida valor, data e método
→ Sistema atualiza saldo
→ Sistema marca fatura como paga/parcial
→ Sistema gera comprovativo se aplicável
→ Sistema notifica inquilino
→ Sistema audita ação
```

## 9.6 Pedido de manutenção

```text
Inquilino cria pedido de manutenção
→ Seleciona habitação/contrato
→ Seleciona categoria
→ Descreve problema
→ Anexa ficheiros, se necessário
→ Sistema valida acesso
→ Sistema cria pedido
→ Sistema notifica backoffice
→ Sistema mostra estado ao inquilino
```

## 9.7 Tratamento de manutenção

```text
Técnico abre pedido
→ Classifica prioridade
→ Atribui responsável
→ Solicita informação adicional ou agenda vistoria/intervenção
→ Atualiza estado
→ Regista intervenção
→ Fecha pedido com relatório
→ Sistema notifica inquilino
→ Sistema audita ação
```

## 9.8 Agendamento de vistoria

```text
Técnico cria vistoria
→ Define habitação, contrato, data, local e objetivo
→ Sistema notifica inquilino
→ Inquilino consulta agendamento
→ Técnico realiza vistoria
→ Regista relatório
→ Sistema atualiza histórico
```

## 9.9 Comunicação município-inquilino

```text
Município cria comunicação
→ Associa contrato/habitação/pedido
→ Inquilino recebe notificação
→ Inquilino lê e responde quando permitido
→ Sistema mantém histórico
→ Mensagens internas não aparecem ao inquilino
```

## 9.10 Dashboard operacional do senhorio

```text
Técnico autorizado acede ao dashboard
→ Sistema mostra contratos ativos
→ Sistema mostra faturas vencidas
→ Sistema mostra pagamentos pendentes
→ Sistema mostra pedidos de manutenção por estado
→ Sistema mostra vistorias agendadas
→ Sistema mostra alertas operacionais
→ Sistema permite drill-down autorizado
```

---

# 10. Estados e tipos recomendados

## TenantPortalStatus

```text
active
blocked
pending_activation
archived
```

## ContractOccupancyStatus

```text
active
pending_start
suspended
terminated
expired
archived
```

## TenantInvoiceStatus

```text
draft
issued
sent
partially_paid
paid
overdue
cancelled
voided
under_review
```

## TenantPaymentStatus

```text
pending
registered
confirmed
reconciled
failed
cancelled
refunded
partial
```

## ChargeRunStatus

```text
draft
running
completed
completed_with_warnings
failed
cancelled
```

## ChargeType

```text
rent
deposit
fee
adjustment
penalty
maintenance_charge
other
```

## MaintenanceRequestStatus

```text
draft
submitted
received
under_review
waiting_tenant
scheduled
in_progress
completed
rejected
cancelled
closed
```

## MaintenancePriority

```text
low
normal
high
urgent
emergency
```

## MaintenanceInterventionStatus

```text
scheduled
in_progress
completed
cancelled
requires_follow_up
```

## InspectionStatus

```text
draft
scheduled
confirmed
rescheduled
completed
cancelled
missed
report_pending
closed
```

## TenantCommunicationStatus

```text
draft
sent
delivered
read
answered
archived
failed
cancelled
```

## TenantCommunicationVisibility

```text
tenant_visible
backoffice_only
system
```

---

# 11. Modelo de dados

## 11.1 TenantProfile

Criar ou adaptar entidade existente:

```text
TenantProfile
```

Tabela recomendada se não existir:

```text
tenant_profiles
```

Campos mínimos:

```text
id
user_id
application_id nullable
allocation_id nullable
housing_unit_id nullable
status
activated_at nullable
blocked_at nullable
archived_at nullable
metadata
created_at
updated_at
deleted_at
```

Regras:

```text
Um utilizador pode ter histórico, mas apenas perfis ativos autorizados devem aceder ao portal.
Não duplicar perfil ativo para a mesma habitação/contrato.
```

## 11.2 TenantContractAccess

Criar se necessário para controlar acesso do inquilino a contratos:

```text
TenantContractAccess
```

Tabela:

```text
tenant_contract_accesses
```

Campos:

```text
id
tenant_profile_id
user_id
contract_id
housing_unit_id nullable
status
granted_at
revoked_at nullable
granted_by nullable
revoked_by nullable
created_at
updated_at
```

## 11.3 TenantInvoice

Criar ou adaptar entidade existente:

```text
TenantInvoice
```

Tabela:

```text
tenant_invoices
```

Campos:

```text
id
invoice_number
tenant_profile_id nullable
user_id
contract_id nullable
housing_unit_id nullable

type
status
period_start nullable
period_end nullable
issued_at nullable
due_at nullable
paid_at nullable

currency
subtotal_amount
tax_amount
total_amount
paid_amount
outstanding_amount

description
payload
file_path nullable

created_by nullable
updated_by nullable
created_at
updated_at
deleted_at
```

Regras:

```text
invoice_number deve ser único.
Não criar duplicado para o mesmo contrato/período/tipo.
Ficheiros devem ficar privados.
```

## 11.4 TenantPayment

Criar ou adaptar:

```text
TenantPayment
```

Tabela:

```text
tenant_payments
```

Campos:

```text
id
payment_number
tenant_invoice_id nullable
tenant_profile_id nullable
user_id
contract_id nullable

status
method
reference nullable
paid_at nullable
registered_at nullable
confirmed_at nullable

currency
amount
metadata
receipt_path nullable

registered_by nullable
confirmed_by nullable

created_at
updated_at
deleted_at
```

Métodos recomendados:

```text
manual
bank_transfer
cash
card
reference
direct_debit
other
```

Não implementar gateway real se não existir.

## 11.5 TenantChargeRun

Criar entidade:

```text
TenantChargeRun
```

Tabela:

```text
tenant_charge_runs
```

Campos:

```text
id
run_number
status
type
period_start
period_end
started_at nullable
completed_at nullable
failed_at nullable
failure_reason nullable
total_contracts
generated_count
skipped_count
warnings_count
payload
created_by nullable
created_at
updated_at
```

## 11.6 TenantChargeRunItem

Criar entidade:

```text
TenantChargeRunItem
```

Tabela:

```text
tenant_charge_run_items
```

Campos:

```text
id
tenant_charge_run_id
tenant_invoice_id nullable
tenant_profile_id nullable
contract_id nullable
user_id nullable
status
amount
message nullable
metadata
created_at
updated_at
```

## 11.7 TenantMaintenanceRequest

Criar ou adaptar `MaintenanceRequest` existente:

```text
TenantMaintenanceRequest
```

Tabela se não existir equivalente:

```text
tenant_maintenance_requests
```

Campos:

```text
id
request_number
tenant_profile_id nullable
user_id
contract_id nullable
housing_unit_id

category_id nullable
status
priority
title
description
location_details nullable

submitted_at nullable
received_at nullable
assigned_to nullable
scheduled_at nullable
completed_at nullable
closed_at nullable

resolution_summary nullable
tenant_visible_notes nullable
internal_notes nullable

created_at
updated_at
deleted_at
```

## 11.8 TenantMaintenanceAttachment

Criar se necessário:

```text
TenantMaintenanceAttachment
```

Tabela:

```text
tenant_maintenance_attachments
```

Campos:

```text
id
tenant_maintenance_request_id
uploaded_by
filename
original_filename
path
mime_type
size_bytes
checksum
visibility
created_at
updated_at
deleted_at
```

Regras:

```text
Storage privado.
Download autorizado.
Não aceitar executáveis.
Não expor path.
```

## 11.9 MaintenanceIntervention

Criar ou adaptar:

```text
MaintenanceIntervention
```

Tabela:

```text
maintenance_interventions
```

Campos:

```text
id
intervention_number
tenant_maintenance_request_id nullable
housing_unit_id
contract_id nullable
assigned_to nullable

status
scheduled_at nullable
started_at nullable
completed_at nullable

description
work_performed nullable
materials_used nullable
cost_amount nullable
internal_notes nullable
tenant_visible_summary nullable

created_by
updated_by nullable
created_at
updated_at
deleted_at
```

## 11.10 TenantInspection

Criar ou adaptar:

```text
TenantInspection
```

Tabela:

```text
tenant_inspections
```

Campos:

```text
id
inspection_number
tenant_profile_id nullable
user_id
contract_id nullable
housing_unit_id
maintenance_request_id nullable

status
type
scheduled_at nullable
completed_at nullable
cancelled_at nullable
cancelled_by nullable
cancellation_reason nullable

location
purpose
tenant_instructions nullable
internal_notes nullable

created_by
updated_by nullable
created_at
updated_at
deleted_at
```

Tipos recomendados:

```text
initial
periodic
maintenance
complaint
exit
extraordinary
```

## 11.11 TenantInspectionReport

Criar entidade:

```text
TenantInspectionReport
```

Tabela:

```text
tenant_inspection_reports
```

Campos:

```text
id
report_number
tenant_inspection_id
housing_unit_id
contract_id nullable
status
summary
findings
recommendations
file_path nullable
generated_by nullable
generated_at nullable
approved_by nullable
approved_at nullable
created_at
updated_at
deleted_at
```

## 11.12 TenantCommunication

Criar ou adaptar:

```text
TenantCommunication
```

Tabela:

```text
tenant_communications
```

Campos:

```text
id
communication_number
tenant_profile_id nullable
user_id
contract_id nullable
housing_unit_id nullable
related_type nullable
related_id nullable

status
visibility
subject
message
sent_at nullable
read_at nullable
answered_at nullable

created_by nullable
created_at
updated_at
deleted_at
```

## 11.13 TenantCommunicationMessage

Criar se for necessário thread/conversação:

```text
TenantCommunicationMessage
```

Tabela:

```text
tenant_communication_messages
```

Campos:

```text
id
tenant_communication_id
sender_user_id nullable
visibility
message
metadata
read_at nullable
created_at
updated_at
deleted_at
```

## 11.14 LandlordDashboardSnapshot

Criar entidade opcional:

```text
LandlordDashboardSnapshot
```

Tabela:

```text
landlord_dashboard_snapshots
```

Campos:

```text
id
snapshot_number
period_start nullable
period_end nullable
metrics
generated_by nullable
generated_at
created_at
updated_at
```

Objetivo:

```text
Guardar snapshots operacionais da exploração habitacional sem dados pessoais desnecessários.
```

---

# 12. Índices e performance

Adicionar índices seguros:

```text
tenant_profiles.user_id
tenant_profiles.application_id
tenant_profiles.allocation_id
tenant_profiles.housing_unit_id
tenant_profiles.status

tenant_contract_accesses.tenant_profile_id
tenant_contract_accesses.user_id
tenant_contract_accesses.contract_id
tenant_contract_accesses.status

tenant_invoices.invoice_number unique
tenant_invoices.user_id
tenant_invoices.contract_id
tenant_invoices.housing_unit_id
tenant_invoices.status
tenant_invoices.due_at
tenant_invoices.period_start
tenant_invoices.period_end

tenant_payments.payment_number unique
tenant_payments.tenant_invoice_id
tenant_payments.user_id
tenant_payments.status
tenant_payments.paid_at

tenant_charge_runs.run_number unique
tenant_charge_runs.status
tenant_charge_runs.type
tenant_charge_runs.period_start
tenant_charge_runs.period_end

tenant_charge_run_items.tenant_charge_run_id
tenant_charge_run_items.tenant_invoice_id
tenant_charge_run_items.status

tenant_maintenance_requests.request_number unique
tenant_maintenance_requests.user_id
tenant_maintenance_requests.housing_unit_id
tenant_maintenance_requests.contract_id
tenant_maintenance_requests.status
tenant_maintenance_requests.priority
tenant_maintenance_requests.assigned_to
tenant_maintenance_requests.submitted_at

tenant_maintenance_attachments.tenant_maintenance_request_id
tenant_maintenance_attachments.uploaded_by

maintenance_interventions.intervention_number unique
maintenance_interventions.tenant_maintenance_request_id
maintenance_interventions.housing_unit_id
maintenance_interventions.status
maintenance_interventions.scheduled_at

tenant_inspections.inspection_number unique
tenant_inspections.user_id
tenant_inspections.housing_unit_id
tenant_inspections.contract_id
tenant_inspections.status
tenant_inspections.scheduled_at

tenant_inspection_reports.report_number unique
tenant_inspection_reports.tenant_inspection_id
tenant_inspection_reports.status

tenant_communications.communication_number unique
tenant_communications.user_id
tenant_communications.contract_id
tenant_communications.status
tenant_communications.visibility
tenant_communications.sent_at

tenant_communication_messages.tenant_communication_id
tenant_communication_messages.sender_user_id
tenant_communication_messages.visibility

landlord_dashboard_snapshots.snapshot_number unique
landlord_dashboard_snapshots.generated_at
```

Migrations devem ser reversíveis.

Não adicionar índices duplicados.

Usar eager loading em dashboard, faturas, manutenção, vistorias e comunicações.

Paginar listagens de faturas, pagamentos, pedidos, intervenções e comunicações.

---

# 13. Services obrigatórios

Criar namespaces:

```text
App\Services\TenantPortal
App\Services\TenantBilling
App\Services\TenantMaintenance
App\Services\TenantInspections
App\Services\TenantCommunications
App\Services\LandlordOperations
```

Criar services:

```text
App\Services\TenantPortal\TenantPortalAccessService
App\Services\TenantPortal\TenantDashboardService
App\Services\TenantPortal\TenantContractService
App\Services\TenantPortal\TenantDocumentAccessService

App\Services\TenantBilling\TenantInvoiceService
App\Services\TenantBilling\TenantPaymentService
App\Services\TenantBilling\TenantReceiptService
App\Services\TenantBilling\TenantChargeRunService
App\Services\TenantBilling\AutomaticTenantChargeService
App\Services\TenantBilling\TenantDebtStatusService

App\Services\TenantMaintenance\TenantMaintenanceRequestService
App\Services\TenantMaintenance\TenantMaintenanceAttachmentService
App\Services\TenantMaintenance\MaintenanceInterventionService
App\Services\TenantMaintenance\MaintenanceReportService
App\Services\TenantMaintenance\MaintenanceNotificationService

App\Services\TenantInspections\TenantInspectionService
App\Services\TenantInspections\TenantInspectionSchedulingService
App\Services\TenantInspections\TenantInspectionReportService
App\Services\TenantInspections\InspectionNotificationService

App\Services\TenantCommunications\TenantCommunicationService
App\Services\TenantCommunications\TenantCommunicationMessageService
App\Services\TenantCommunications\TenantCommunicationNotificationService

App\Services\LandlordOperations\LandlordDashboardService
App\Services\LandlordOperations\LandlordMetricAggregator
App\Services\LandlordOperations\LandlordAlertService
```

## 13.1 TenantPortalAccessService

Responsável por:

```text
Validar acesso à área do inquilino
Determinar contratos acessíveis
Determinar habitações acessíveis
Bloquear utilizadores sem perfil ativo
Evitar acesso a dados de terceiros
```

## 13.2 TenantDashboardService

Responsável por:

```text
Construir dashboard do inquilino
Resumo de contratos
Resumo de faturas
Resumo de pagamentos
Resumo de manutenção
Resumo de vistorias
Resumo de comunicações
Alertas e ações pendentes
```

## 13.3 TenantContractService

Responsável por:

```text
Listar contratos do inquilino
Consultar contrato
Resolver estado contratual
Listar documentos contratuais
Mascarar dados não autorizados
```

## 13.4 TenantInvoiceService

Responsável por:

```text
Listar faturas
Consultar fatura
Emitir fatura interna
Atualizar estado de fatura
Calcular saldo pendente
Evitar duplicados por contrato/período/tipo
```

## 13.5 TenantPaymentService

Responsável por:

```text
Registar pagamento
Confirmar pagamento
Anular pagamento quando permitido
Associar pagamento a fatura
Atualizar paid_amount/outstanding_amount
Gerar eventos e auditoria
```

## 13.6 TenantChargeRunService

Responsável por:

```text
Criar execução de cobranças internas
Executar cobranças por período
Gerar itens de execução
Registar contratos ignorados
Registar warnings
Evitar duplicados
Auditar execução
```

## 13.7 AutomaticTenantChargeService

Responsável por:

```text
Identificar contratos ativos
Calcular valores de renda conforme dados existentes
Gerar faturas internas
Criar notificações
Marcar faturas vencidas quando aplicável
```

Não realizar movimentos bancários externos.

## 13.8 TenantMaintenanceRequestService

Responsável por:

```text
Criar pedido de manutenção
Validar contrato/habitação do inquilino
Classificar categoria/prioridade
Atualizar estado
Atribuir técnico
Solicitar informação adicional
Fechar pedido
Criar histórico e auditoria
```

## 13.9 MaintenanceInterventionService

Responsável por:

```text
Criar intervenção
Agendar intervenção
Registar início
Registar conclusão
Registar materiais/custos internos se aplicável
Gerar resumo visível ao inquilino
Gerar histórico
```

## 13.10 MaintenanceReportService

Responsável por:

```text
Gerar relatório de manutenção
Agrupar pedidos por estado
Agrupar intervenções
Calcular tempos médios
Gerar payload/exportação
Guardar ficheiro privado se aplicável
```

## 13.11 TenantInspectionService

Responsável por:

```text
Criar vistoria
Atualizar estado
Cancelar/reagendar
Concluir vistoria
Associar a manutenção quando aplicável
Criar histórico e auditoria
```

## 13.12 TenantInspectionSchedulingService

Responsável por:

```text
Validar disponibilidade
Agendar vistoria
Evitar sobreposição básica
Notificar inquilino
Registar reagendamentos
```

## 13.13 TenantInspectionReportService

Responsável por:

```text
Criar relatório de vistoria
Registar conclusões
Gerar documento/ficheiro privado se aplicável
Aprovar relatório
Controlar acesso
```

## 13.14 TenantCommunicationService

Responsável por:

```text
Criar comunicação
Enviar comunicação interna
Marcar como lida
Arquivar
Associar a contrato/habitação/pedido
Gerir visibilidade
Criar histórico
```

## 13.15 TenantCommunicationMessageService

Responsável por:

```text
Adicionar mensagem
Validar autor
Validar visibilidade
Impedir acesso a conversas de terceiros
Escapar conteúdo
Criar notificação
```

## 13.16 LandlordDashboardService

Responsável por:

```text
Construir dashboard operacional do senhorio/município
Indicadores de contratos
Indicadores de faturação
Indicadores de pagamentos
Indicadores de incumprimento
Indicadores de manutenção
Indicadores de vistorias
Indicadores de comunicações
Alertas operacionais
```

## 13.17 LandlordMetricAggregator

Responsável por:

```text
Executar queries agregadas
Evitar N+1
Aplicar filtros por habitação/contrato/período/estado
Retornar payload estruturado
Não expor dados pessoais desnecessários
```

---

# 14. Controllers

Criar ou completar:

```text
App\Http\Controllers\Tenant\TenantDashboardController
App\Http\Controllers\Tenant\TenantContractController
App\Http\Controllers\Tenant\TenantInvoiceController
App\Http\Controllers\Tenant\TenantPaymentController
App\Http\Controllers\Tenant\TenantMaintenanceRequestController
App\Http\Controllers\Tenant\TenantMaintenanceAttachmentController
App\Http\Controllers\Tenant\TenantInspectionController
App\Http\Controllers\Tenant\TenantCommunicationController
App\Http\Controllers\Tenant\TenantCommunicationMessageController

App\Http\Controllers\Backoffice\LandlordDashboardController
App\Http\Controllers\Backoffice\TenantProfileController
App\Http\Controllers\Backoffice\TenantInvoiceController
App\Http\Controllers\Backoffice\TenantPaymentController
App\Http\Controllers\Backoffice\TenantChargeRunController
App\Http\Controllers\Backoffice\TenantMaintenanceRequestController
App\Http\Controllers\Backoffice\MaintenanceInterventionController
App\Http\Controllers\Backoffice\TenantInspectionController
App\Http\Controllers\Backoffice\TenantInspectionReportController
App\Http\Controllers\Backoffice\TenantCommunicationController
App\Http\Controllers\Backoffice\MaintenanceReportController
```

Controllers devem ser magros.

Toda lógica crítica deve ficar em Services.

Pagamentos, cobranças, manutenção, vistorias e comunicações devem usar policies.

---

# 15. Form Requests

Criar:

```text
StoreTenantMaintenanceRequestRequest
UpdateTenantMaintenanceRequestRequest
StoreTenantMaintenanceAttachmentRequest
UpdateMaintenanceRequestStatusRequest

StoreMaintenanceInterventionRequest
UpdateMaintenanceInterventionRequest
CompleteMaintenanceInterventionRequest

StoreTenantInspectionRequest
UpdateTenantInspectionRequest
RescheduleTenantInspectionRequest
CompleteTenantInspectionRequest
CancelTenantInspectionRequest
StoreTenantInspectionReportRequest
ApproveTenantInspectionReportRequest

StoreTenantCommunicationRequest
StoreTenantCommunicationMessageRequest
MarkTenantCommunicationReadRequest
ArchiveTenantCommunicationRequest

GenerateTenantInvoiceRequest
UpdateTenantInvoiceRequest
RegisterTenantPaymentRequest
ConfirmTenantPaymentRequest
CancelTenantPaymentRequest
RunTenantChargeRunRequest

FilterLandlordDashboardRequest
GenerateMaintenanceReportRequest
DownloadMaintenanceReportRequest
```

## StoreTenantMaintenanceRequestRequest

```php
'housing_unit_id' => ['required', 'exists:housing_units,id'],
'contract_id' => ['nullable', 'integer'],
'category_id' => ['nullable', 'integer'],
'title' => ['required', 'string', 'max:180'],
'description' => ['required', 'string', 'min:10', 'max:10000'],
'location_details' => ['nullable', 'string', 'max:1000'],
'attachments' => ['nullable', 'array'],
'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
```

## UpdateMaintenanceRequestStatusRequest

```php
'status' => ['required', 'string', 'max:50'],
'priority' => ['nullable', 'string', 'max:50'],
'assigned_to' => ['nullable', 'exists:users,id'],
'tenant_visible_notes' => ['nullable', 'string', 'max:3000'],
'internal_notes' => ['nullable', 'string', 'max:3000'],
```

## StoreMaintenanceInterventionRequest

```php
'tenant_maintenance_request_id' => ['nullable', 'integer'],
'housing_unit_id' => ['required', 'exists:housing_units,id'],
'assigned_to' => ['nullable', 'exists:users,id'],
'scheduled_at' => ['nullable', 'date'],
'description' => ['required', 'string', 'max:5000'],
```

## StoreTenantInspectionRequest

```php
'housing_unit_id' => ['required', 'exists:housing_units,id'],
'contract_id' => ['nullable', 'integer'],
'maintenance_request_id' => ['nullable', 'integer'],
'type' => ['required', 'string', 'max:100'],
'scheduled_at' => ['required', 'date'],
'location' => ['required', 'string', 'max:255'],
'purpose' => ['required', 'string', 'max:3000'],
'tenant_instructions' => ['nullable', 'string', 'max:3000'],
```

## RegisterTenantPaymentRequest

```php
'tenant_invoice_id' => ['required', 'exists:tenant_invoices,id'],
'method' => ['required', 'string', 'max:100'],
'amount' => ['required', 'numeric', 'min:0.01'],
'paid_at' => ['required', 'date'],
'reference' => ['nullable', 'string', 'max:180'],
```

## RunTenantChargeRunRequest

```php
'type' => ['required', 'string', 'max:100'],
'period_start' => ['required', 'date'],
'period_end' => ['required', 'date', 'after_or_equal:period_start'],
'confirm_internal_charge_generation' => ['accepted'],
```

## StoreTenantCommunicationRequest

```php
'user_id' => ['required', 'exists:users,id'],
'contract_id' => ['nullable', 'integer'],
'housing_unit_id' => ['nullable', 'exists:housing_units,id'],
'subject' => ['required', 'string', 'max:180'],
'message' => ['required', 'string', 'min:1', 'max:10000'],
'visibility' => ['nullable', 'string', 'max:50'],
```

## FilterLandlordDashboardRequest

```php
'housing_unit_id' => ['nullable', 'exists:housing_units,id'],
'contract_id' => ['nullable', 'integer'],
'period_start' => ['nullable', 'date'],
'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
'status' => ['nullable', 'string', 'max:50'],
```

---

# 16. Policies

Criar ou completar:

```text
TenantProfilePolicy
TenantContractAccessPolicy
TenantInvoicePolicy
TenantPaymentPolicy
TenantChargeRunPolicy
TenantMaintenanceRequestPolicy
TenantMaintenanceAttachmentPolicy
MaintenanceInterventionPolicy
TenantInspectionPolicy
TenantInspectionReportPolicy
TenantCommunicationPolicy
TenantCommunicationMessagePolicy
LandlordDashboardPolicy
MaintenanceReportPolicy
```

Regras:

```text
Guest não acede à área do inquilino.
Candidato sem perfil de inquilino ativo não acede à área do inquilino.
Inquilino só vê contratos próprios.
Inquilino só vê faturas próprias.
Inquilino só vê pagamentos próprios.
Inquilino só cria manutenção para habitação contratada.
Inquilino só vê manutenção própria ou da sua habitação.
Inquilino só vê vistorias próprias ou da sua habitação.
Inquilino só vê comunicações próprias.
Técnico vê dados conforme permissões.
Auditor consulta sem alterar.
Admin gere configurações e dashboards.
Downloads privados exigem autorização.
```

Nunca confiar apenas no frontend para esconder ações.

---

# 17. Rotas da área do inquilino

Adicionar, adaptando à estrutura real:

```php
Route::middleware(['auth'])->prefix('area-inquilino')->name('tenant.')->group(function (): void {
    Route::get('/', [TenantDashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/contratos', [TenantContractController::class, 'index'])
        ->name('contracts.index');
    Route::get('/contratos/{contract}', [TenantContractController::class, 'show'])
        ->name('contracts.show');
    Route::get('/contratos/{contract}/documentos/{document}/download', [TenantContractController::class, 'downloadDocument'])
        ->name('contracts.documents.download');

    Route::get('/faturas', [TenantInvoiceController::class, 'index'])
        ->name('invoices.index');
    Route::get('/faturas/{tenantInvoice}', [TenantInvoiceController::class, 'show'])
        ->name('invoices.show');
    Route::get('/faturas/{tenantInvoice}/download', [TenantInvoiceController::class, 'download'])
        ->name('invoices.download');

    Route::get('/pagamentos', [TenantPaymentController::class, 'index'])
        ->name('payments.index');
    Route::get('/pagamentos/{tenantPayment}/recibo', [TenantPaymentController::class, 'downloadReceipt'])
        ->name('payments.receipt.download');

    Route::get('/manutencao', [TenantMaintenanceRequestController::class, 'index'])
        ->name('maintenance.index');
    Route::get('/manutencao/criar', [TenantMaintenanceRequestController::class, 'create'])
        ->name('maintenance.create');
    Route::post('/manutencao', [TenantMaintenanceRequestController::class, 'store'])
        ->name('maintenance.store');
    Route::get('/manutencao/{tenantMaintenanceRequest}', [TenantMaintenanceRequestController::class, 'show'])
        ->name('maintenance.show');

    Route::get('/vistorias', [TenantInspectionController::class, 'index'])
        ->name('inspections.index');
    Route::get('/vistorias/{tenantInspection}', [TenantInspectionController::class, 'show'])
        ->name('inspections.show');

    Route::get('/comunicacoes', [TenantCommunicationController::class, 'index'])
        ->name('communications.index');
    Route::get('/comunicacoes/{tenantCommunication}', [TenantCommunicationController::class, 'show'])
        ->name('communications.show');
    Route::post('/comunicacoes/{tenantCommunication}/mensagens', [TenantCommunicationMessageController::class, 'store'])
        ->name('communications.messages.store');
    Route::post('/comunicacoes/{tenantCommunication}/ler', [TenantCommunicationController::class, 'markRead'])
        ->name('communications.read');
});
```

---

# 18. Rotas de backoffice

Adicionar, adaptando à estrutura real:

```php
Route::middleware(['auth'])->prefix('backoffice')->name('backoffice.')->group(function (): void {
    Route::get('/senhorio/dashboard', [LandlordDashboardController::class, 'index'])
        ->name('landlord.dashboard');

    Route::get('/inquilinos', [TenantProfileController::class, 'index'])
        ->name('tenants.index');
    Route::get('/inquilinos/{tenantProfile}', [TenantProfileController::class, 'show'])
        ->name('tenants.show');

    Route::get('/inquilinos/faturas', [TenantInvoiceController::class, 'index'])
        ->name('tenant-invoices.index');
    Route::post('/inquilinos/faturas/gerar', [TenantInvoiceController::class, 'generate'])
        ->name('tenant-invoices.generate');
    Route::get('/inquilinos/faturas/{tenantInvoice}', [TenantInvoiceController::class, 'show'])
        ->name('tenant-invoices.show');

    Route::get('/inquilinos/pagamentos', [TenantPaymentController::class, 'index'])
        ->name('tenant-payments.index');
    Route::post('/inquilinos/pagamentos', [TenantPaymentController::class, 'store'])
        ->name('tenant-payments.store');
    Route::post('/inquilinos/pagamentos/{tenantPayment}/confirmar', [TenantPaymentController::class, 'confirm'])
        ->name('tenant-payments.confirm');

    Route::get('/cobrancas', [TenantChargeRunController::class, 'index'])
        ->name('tenant-charge-runs.index');
    Route::post('/cobrancas/executar', [TenantChargeRunController::class, 'run'])
        ->name('tenant-charge-runs.run');
    Route::get('/cobrancas/{tenantChargeRun}', [TenantChargeRunController::class, 'show'])
        ->name('tenant-charge-runs.show');

    Route::get('/manutencao', [TenantMaintenanceRequestController::class, 'index'])
        ->name('tenant-maintenance.index');
    Route::get('/manutencao/{tenantMaintenanceRequest}', [TenantMaintenanceRequestController::class, 'show'])
        ->name('tenant-maintenance.show');
    Route::post('/manutencao/{tenantMaintenanceRequest}/estado', [TenantMaintenanceRequestController::class, 'updateStatus'])
        ->name('tenant-maintenance.status');

    Route::post('/intervencoes-manutencao', [MaintenanceInterventionController::class, 'store'])
        ->name('maintenance-interventions.store');
    Route::post('/intervencoes-manutencao/{maintenanceIntervention}/concluir', [MaintenanceInterventionController::class, 'complete'])
        ->name('maintenance-interventions.complete');

    Route::get('/vistorias-inquilino', [TenantInspectionController::class, 'index'])
        ->name('tenant-inspections.index');
    Route::post('/vistorias-inquilino', [TenantInspectionController::class, 'store'])
        ->name('tenant-inspections.store');
    Route::get('/vistorias-inquilino/{tenantInspection}', [TenantInspectionController::class, 'show'])
        ->name('tenant-inspections.show');
    Route::post('/vistorias-inquilino/{tenantInspection}/concluir', [TenantInspectionController::class, 'complete'])
        ->name('tenant-inspections.complete');

    Route::post('/vistorias-inquilino/{tenantInspection}/relatorio', [TenantInspectionReportController::class, 'store'])
        ->name('tenant-inspection-reports.store');

    Route::get('/comunicacoes-inquilino', [TenantCommunicationController::class, 'index'])
        ->name('tenant-communications.index');
    Route::post('/comunicacoes-inquilino', [TenantCommunicationController::class, 'store'])
        ->name('tenant-communications.store');
    Route::get('/comunicacoes-inquilino/{tenantCommunication}', [TenantCommunicationController::class, 'show'])
        ->name('tenant-communications.show');

    Route::get('/relatorios-manutencao', [MaintenanceReportController::class, 'index'])
        ->name('maintenance-reports.index');
    Route::post('/relatorios-manutencao/gerar', [MaintenanceReportController::class, 'generate'])
        ->name('maintenance-reports.generate');
});
```

Todas as rotas devem respeitar middleware, policies e convenções existentes.

---

# 19. Views / páginas

Se o projeto usa Blade, criar:

```text
resources/views/tenant/dashboard.blade.php

resources/views/tenant/contracts/index.blade.php
resources/views/tenant/contracts/show.blade.php

resources/views/tenant/invoices/index.blade.php
resources/views/tenant/invoices/show.blade.php

resources/views/tenant/payments/index.blade.php

resources/views/tenant/maintenance/index.blade.php
resources/views/tenant/maintenance/create.blade.php
resources/views/tenant/maintenance/show.blade.php

resources/views/tenant/inspections/index.blade.php
resources/views/tenant/inspections/show.blade.php

resources/views/tenant/communications/index.blade.php
resources/views/tenant/communications/show.blade.php

resources/views/backoffice/landlord/dashboard.blade.php
resources/views/backoffice/tenants/index.blade.php
resources/views/backoffice/tenants/show.blade.php

resources/views/backoffice/tenant-invoices/index.blade.php
resources/views/backoffice/tenant-invoices/show.blade.php

resources/views/backoffice/tenant-payments/index.blade.php
resources/views/backoffice/tenant-payments/show.blade.php

resources/views/backoffice/tenant-charge-runs/index.blade.php
resources/views/backoffice/tenant-charge-runs/show.blade.php

resources/views/backoffice/tenant-maintenance/index.blade.php
resources/views/backoffice/tenant-maintenance/show.blade.php

resources/views/backoffice/maintenance-interventions/show.blade.php

resources/views/backoffice/tenant-inspections/index.blade.php
resources/views/backoffice/tenant-inspections/show.blade.php

resources/views/backoffice/tenant-communications/index.blade.php
resources/views/backoffice/tenant-communications/show.blade.php

resources/views/backoffice/maintenance-reports/index.blade.php
resources/views/backoffice/maintenance-reports/show.blade.php
```

Se o projeto usa Inertia/React/Vue, criar equivalentes.

Não mudar stack frontend.

Não misturar Blade e Inertia se o projeto já tiver uma stack definida.

---

# 20. UX obrigatória

## 20.1 Dashboard do inquilino

Mostrar:

```text
Contrato ativo
Habitação associada
Próxima renda/fatura
Pagamentos pendentes
Últimos pagamentos
Pedidos de manutenção recentes
Vistorias agendadas
Comunicações não lidas
Alertas importantes
Ações rápidas
```

## 20.2 Contratos

Mostrar:

```text
Número do contrato
Habitação
Data de início
Data de fim
Estado
Renda mensal
Caução, se aplicável
Documentos contratuais autorizados
Histórico básico
```

## 20.3 Faturas e pagamentos

Mostrar:

```text
Número da fatura
Período
Data de emissão
Data de vencimento
Valor total
Valor pago
Valor pendente
Estado
Pagamentos associados
Recibo/comprovativo autorizado
```

## 20.4 Pedido de manutenção

Mostrar ao inquilino:

```text
Número do pedido
Categoria
Título
Descrição
Estado
Prioridade visível quando aplicável
Data de submissão
Atualizações
Intervenções visíveis
Anexos
Mensagens/observações visíveis
```

Mostrar ao backoffice:

```text
Pedido
Habitação
Contrato
Inquilino conforme permissão
Categoria
Prioridade
Estado
Técnico responsável
Histórico
Anexos
Intervenções
Notas internas
Ações de estado
```

## 20.5 Vistorias

Mostrar:

```text
Tipo de vistoria
Data/hora
Local
Objetivo
Estado
Instruções
Relatório visível quando aplicável
```

## 20.6 Comunicações

Mostrar:

```text
Assunto
Mensagem
Data
Estado de leitura
Contrato/habitação associada
Thread de respostas quando aplicável
Separação entre mensagens visíveis e internas
```

## 20.7 Dashboard operacional do senhorio

Mostrar:

```text
Contratos ativos
Contratos a terminar
Faturas emitidas
Faturas vencidas
Pagamentos recebidos
Valores em dívida
Pedidos de manutenção abertos
Pedidos urgentes
Intervenções em curso
Vistorias agendadas
Comunicações pendentes
Alertas operacionais
```

---

# 21. Regras de contratos

Regras obrigatórias:

```text
Inquilino só vê contratos próprios.
Contrato terminado pode continuar visível como histórico, se permitido.
Documentos contratuais devem estar em storage privado.
Download deve passar por controller autorizado.
Dados sensíveis devem ser minimizados.
Backoffice pode consultar conforme permissões.
```

---

# 22. Regras de faturas e pagamentos

Regras obrigatórias:

```text
Fatura deve pertencer a contrato/inquilino.
Inquilino só vê faturas próprias.
Pagamento deve ficar associado a fatura ou contrato.
Pagamento parcial deve atualizar saldo pendente.
Fatura paga deve ter paid_at.
Fatura vencida deve poder ser sinalizada.
Não criar movimento bancário real.
Recibos/comprovativos devem ser privados.
Downloads exigem autorização.
```

Regras de cobrança automática interna:

```text
Cobrança deve gerar fatura interna.
Cobrança deve evitar duplicados por período.
Cobrança deve registar execução.
Cobrança deve suportar warnings.
Cobrança deve poder ser executada por comando/job ou ação autorizada.
```

Comando recomendado:

```bash
php artisan tenants:generate-charges --period=YYYY-MM
```

Se o projeto preferir comandos com outro namespace, adaptar.

---

# 23. Regras de manutenção

Regras obrigatórias:

```text
Inquilino só cria pedido para habitação/contrato próprio.
Pedido deve ter número único.
Pedido deve ter estado.
Pedido deve permitir anexos privados.
Backoffice pode alterar prioridade e estado.
Backoffice pode atribuir técnico.
Intervenções devem ficar no histórico.
Notas internas não aparecem ao inquilino.
Resumo visível ao inquilino deve ser separado de notas internas.
Fecho deve registar resolução.
```

Prioridade `emergency` deve gerar alerta interno se módulo existir.

---

# 24. Regras de vistorias

Regras obrigatórias:

```text
Vistoria deve estar associada a habitação.
Vistoria pode estar associada a contrato, manutenção ou inquilino.
Agendamento deve ter data/hora/local.
Inquilino só vê vistorias próprias.
Conclusão deve registar técnico responsável.
Relatório deve ficar privado se tiver dados pessoais.
Relatório visível ao inquilino deve ser controlado por policy.
```

---

# 25. Regras de comunicações

Regras obrigatórias:

```text
Comunicação deve ter destinatário.
Comunicação pode estar associada a contrato, habitação, pedido ou vistoria.
Comunicação tenant_visible aparece ao inquilino.
Comunicação backoffice_only não aparece ao inquilino.
Mensagens devem ser escapadas.
Comunicações devem manter histórico.
Leitura deve poder ser registada.
```

---

# 26. Dashboard operacional do senhorio

Métricas mínimas:

```text
Total de contratos ativos
Contratos a terminar nos próximos 30/60/90 dias
Faturas emitidas no período
Faturas vencidas
Valor total em dívida
Pagamentos registados no período
Pedidos de manutenção abertos
Pedidos de manutenção urgentes
Tempo médio de resposta
Tempo médio de resolução
Vistorias agendadas
Vistorias concluídas
Comunicações não lidas/respondidas
```

Regras:

```text
Usar queries agregadas.
Evitar N+1.
Aplicar filtros por período, habitação, estado e técnico.
Não expor dados pessoais desnecessários.
Permitir drill-down apenas para utilizadores autorizados.
```

---

# 27. Notificações

Se Sprint 16 existir, emitir notificações para:

```text
Nova fatura emitida
Fatura próxima do vencimento
Fatura vencida
Pagamento registado
Pagamento confirmado
Pedido de manutenção recebido
Pedido de manutenção atualizado
Intervenção agendada
Intervenção concluída
Vistoria agendada
Vistoria reagendada
Vistoria concluída
Nova comunicação do município
Resposta do inquilino
```

Não enviar e-mail/SMS real sem configuração segura.

Se notificações não existirem, criar eventos internos ou documentar pendência.

---

# 28. Auditoria e RGPD

Auditar, se existir auditoria:

```text
Consulta de contrato
Download de documento contratual
Consulta de fatura
Download de fatura/recibo
Geração de cobrança interna
Registo de pagamento
Confirmação de pagamento
Criação de pedido de manutenção
Alteração de estado de manutenção
Upload/download de anexo de manutenção
Criação/conclusão de intervenção
Criação/reagendamento/cancelamento/conclusão de vistoria
Criação/leitura/resposta de comunicação
Geração de relatório de manutenção
Consulta de dashboard operacional sensível
```

RGPD:

```text
Não expor dados de outros inquilinos.
Não expor documentos privados.
Não guardar dados sensíveis em logs técnicos.
Não permitir download por path direto.
Mascarar dados quando o perfil não tem permissão.
Separar notas internas de conteúdo visível ao inquilino.
Minimizar dados pessoais em dashboards.
```

---

# 29. Jobs, eventos e comandos

Criar eventos, se a arquitetura usar events:

```text
TenantInvoiceIssued
TenantInvoiceOverdue
TenantPaymentRegistered
TenantPaymentConfirmed
TenantMaintenanceRequestSubmitted
TenantMaintenanceStatusChanged
MaintenanceInterventionCompleted
TenantInspectionScheduled
TenantInspectionCompleted
TenantCommunicationSent
TenantCommunicationRead
```

Criar jobs, se aplicável:

```text
GenerateTenantChargesJob
MarkOverdueTenantInvoicesJob
SendTenantInvoiceNotificationsJob
SendMaintenanceStatusNotificationJob
SendInspectionNotificationJob
```

Criar comandos, se aplicável:

```bash
php artisan tenants:generate-charges
php artisan tenants:mark-overdue-invoices
php artisan tenants:send-charge-reminders
```

Os comandos devem ser seguros, idempotentes e documentados.

---

# 30. Factories e seeders

Criar factories:

```text
TenantProfileFactory
TenantContractAccessFactory
TenantInvoiceFactory
TenantPaymentFactory
TenantChargeRunFactory
TenantChargeRunItemFactory
TenantMaintenanceRequestFactory
TenantMaintenanceAttachmentFactory
MaintenanceInterventionFactory
TenantInspectionFactory
TenantInspectionReportFactory
TenantCommunicationFactory
TenantCommunicationMessageFactory
LandlordDashboardSnapshotFactory
```

Criar seeder opcional:

```text
Database\Seeders\TenantPortalDemoSeeder
```

Dados fictícios:

```text
Inquilino ativo
Contrato ativo
Faturas emitidas/pagas/vencidas
Pagamentos registados
Cobrança interna executada
Pedidos de manutenção em vários estados
Intervenções concluídas
Vistorias agendadas/concluídas
Comunicações lidas/não lidas
Dashboard operacional com métricas
```

Não usar dados reais.

---

# 31. Testes obrigatórios

Criar ou completar testes.

## 31.1 Área do inquilino

```text
tests/Feature/Tenant/TenantDashboardTest.php
tests/Feature/Tenant/TenantAccessTest.php
tests/Unit/TenantPortal/TenantPortalAccessServiceTest.php
tests/Unit/TenantPortal/TenantDashboardServiceTest.php
```

Cobrir:

```text
Inquilino ativo acede ao dashboard
Utilizador sem perfil de inquilino não acede
Inquilino vê resumo próprio
Inquilino não vê dados de outro inquilino
Dashboard mostra contratos, faturas, manutenção e comunicações
```

## 31.2 Contratos

```text
tests/Feature/Tenant/TenantContractTest.php
tests/Unit/TenantPortal/TenantContractServiceTest.php
```

Cobrir:

```text
Inquilino vê contratos próprios
Inquilino não vê contrato de terceiro
Download de documento contratual exige autorização
Contrato terminado aparece como histórico quando permitido
```

## 31.3 Faturas, pagamentos e cobranças

```text
tests/Feature/Tenant/TenantInvoiceTest.php
tests/Feature/Tenant/TenantPaymentTest.php
tests/Feature/Backoffice/TenantBillingManagementTest.php
tests/Unit/TenantBilling/TenantInvoiceServiceTest.php
tests/Unit/TenantBilling/TenantPaymentServiceTest.php
tests/Unit/TenantBilling/TenantChargeRunServiceTest.php
tests/Unit/TenantBilling/AutomaticTenantChargeServiceTest.php
```

Cobrir:

```text
Inquilino vê faturas próprias
Inquilino não vê fatura de terceiro
Pagamento parcial atualiza saldo
Pagamento confirmado atualiza fatura
Cobrança interna gera fatura
Cobrança interna evita duplicados
Fatura vencida é identificada
Download de recibo exige autorização
Não há gateway externo obrigatório
```

## 31.4 Manutenção

```text
tests/Feature/Tenant/TenantMaintenanceRequestTest.php
tests/Feature/Backoffice/TenantMaintenanceManagementTest.php
tests/Unit/TenantMaintenance/TenantMaintenanceRequestServiceTest.php
tests/Unit/TenantMaintenance/MaintenanceInterventionServiceTest.php
```

Cobrir:

```text
Inquilino cria pedido para habitação própria
Inquilino não cria pedido para habitação de terceiro
Pedido aceita anexos válidos
Executáveis são rejeitados
Backoffice altera estado
Backoffice atribui técnico
Intervenção é registada
Notas internas não aparecem ao inquilino
Pedido fechado mantém histórico
```

## 31.5 Vistorias

```text
tests/Feature/Tenant/TenantInspectionTest.php
tests/Feature/Backoffice/TenantInspectionManagementTest.php
tests/Unit/TenantInspections/TenantInspectionServiceTest.php
tests/Unit/TenantInspections/TenantInspectionReportServiceTest.php
```

Cobrir:

```text
Backoffice agenda vistoria
Inquilino vê vistoria própria
Inquilino não vê vistoria de terceiro
Vistoria pode ser reagendada
Vistoria pode ser concluída
Relatório é criado
Relatório privado exige autorização
```

## 31.6 Comunicações

```text
tests/Feature/Tenant/TenantCommunicationTest.php
tests/Feature/Backoffice/TenantCommunicationManagementTest.php
tests/Unit/TenantCommunications/TenantCommunicationServiceTest.php
tests/Unit/TenantCommunications/TenantCommunicationMessageServiceTest.php
```

Cobrir:

```text
Município envia comunicação
Inquilino vê comunicação própria
Inquilino não vê comunicação de terceiro
Inquilino marca como lida
Inquilino responde quando permitido
Mensagem interna não aparece ao inquilino
Conteúdo é escapado
```

## 31.7 Dashboard operacional do senhorio

```text
tests/Feature/Backoffice/LandlordDashboardTest.php
tests/Unit/LandlordOperations/LandlordDashboardServiceTest.php
tests/Unit/LandlordOperations/LandlordMetricAggregatorTest.php
```

Cobrir:

```text
Técnico autorizado vê dashboard
Candidato/inquilino não acede ao backoffice
Métricas de contratos são calculadas
Métricas de faturas são calculadas
Métricas de manutenção são calculadas
Métricas de vistorias são calculadas
Filtros funcionam
Dados pessoais desnecessários não aparecem
```

## 31.8 Segurança/RGPD

```text
tests/Feature/Security/TenantPortalPrivacyTest.php
```

Cobrir:

```text
Guest não acede à área do inquilino
Inquilino não vê contrato de terceiro
Inquilino não vê fatura de terceiro
Inquilino não vê pagamento de terceiro
Inquilino não vê pedido de manutenção de terceiro
Inquilino não vê vistoria de terceiro
Inquilino não vê comunicação de terceiro
Relatório privado não é acessível por URL público
Mass assignment de status é bloqueado
Mass assignment de user_id é bloqueado
Mass assignment de paid_amount é bloqueado
Notas internas não aparecem ao inquilino
```

---

# 32. PHPStan específico da Sprint 26

Após implementar testes e código:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint26-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint26-after.txt || true
```

Verificar especialmente ficheiros novos:

```text
app/Models/TenantProfile.php
app/Models/TenantContractAccess.php
app/Models/TenantInvoice.php
app/Models/TenantPayment.php
app/Models/TenantChargeRun.php
app/Models/TenantChargeRunItem.php
app/Models/TenantMaintenanceRequest.php
app/Models/TenantMaintenanceAttachment.php
app/Models/MaintenanceIntervention.php
app/Models/TenantInspection.php
app/Models/TenantInspectionReport.php
app/Models/TenantCommunication.php
app/Models/TenantCommunicationMessage.php
app/Models/LandlordDashboardSnapshot.php
app/Services/TenantPortal/*
app/Services/TenantBilling/*
app/Services/TenantMaintenance/*
app/Services/TenantInspections/*
app/Services/TenantCommunications/*
app/Services/LandlordOperations/*
app/Http/Controllers/Tenant/*
app/Http/Controllers/Backoffice/*
app/Http/Requests/*
tests/Feature/*
tests/Unit/*
```

Corrigir:

```text
missingType.generics
missingType.iterableValue
argument.type
return.type
property.notFound
method.notFound
enum/value type mismatch
invalid relation generics
```

Em relações Eloquent, usar PHPDoc generics corretos:

```php
/** @return BelongsTo<User, TenantInvoice> */
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

Em arrays estruturados, usar PHPDoc:

```php
/** @return array{contracts_active: int, invoices_overdue: int, maintenance_open: int, inspections_scheduled: int} */
```

Não adicionar `mixed` sem necessidade.

---

# 33. Comandos obrigatórios finais

Executar:

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

Se existir PHPStan:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse
```

Se existir Psalm:

```bash
./vendor/bin/psalm
```

Se algum comando falhar, documentar:

```text
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
Bloqueia publicação: sim/não
```

Não afirmar que comandos passaram se não foram executados.

Não ocultar erros.

---

# 34. Documentação obrigatória

Criar ou atualizar:

```text
docs/backlog/sprint-26-area-inquilino-gestao-pos-atribuicao.md
docs/tenant/tenant-portal.md
docs/tenant/contracts.md
docs/tenant/invoices-and-payments.md
docs/tenant/automatic-charges.md
docs/tenant/maintenance-requests.md
docs/tenant/inspections.md
docs/tenant/communications.md
docs/backoffice/landlord-dashboard.md
docs/backoffice/maintenance-reports.md
docs/qa/test-coverage-matrix.md
docs/qa/sprint-26-quality-report.md
docs/backlog/roadmap.md
```

## docs/tenant/tenant-portal.md

Incluir:

```text
Objetivo
Acesso
Dashboard
Permissões
Estados
Limitações
```

## docs/tenant/contracts.md

Incluir:

```text
Consulta de contratos
Documentos contratuais
Downloads
Histórico
Permissões
Limitações
```

## docs/tenant/invoices-and-payments.md

Incluir:

```text
Faturas
Estados
Pagamentos
Recibos
Saldos
Avisos
Permissões
Limitações
```

## docs/tenant/automatic-charges.md

Incluir:

```text
Objetivo
Cobranças internas
Jobs/comandos
Idempotência
Limites
Ausência de integração bancária real
```

## docs/tenant/maintenance-requests.md

Incluir:

```text
Criação de pedido
Categorias
Prioridades
Estados
Anexos
Intervenções
Histórico
Permissões
```

## docs/tenant/inspections.md

Incluir:

```text
Agendamento
Estados
Relatórios
Notificações
Permissões
Limitações
```

## docs/tenant/communications.md

Incluir:

```text
Comunicações
Mensagens
Visibilidade
Leitura
Respostas
Histórico
Permissões
```

## docs/backoffice/landlord-dashboard.md

Incluir:

```text
Objetivo
Métricas
Filtros
Drill-down
Permissões
Limitações
```

## docs/backoffice/maintenance-reports.md

Incluir:

```text
Objetivo
Indicadores
Relatórios
Exportação
Storage privado
Auditoria
Limitações
```

## docs/qa/sprint-26-quality-report.md

Incluir:

```text
PHPStan inicial
PHPStan final
Erros legados assumidos: 2471
Erros novos introduzidos: sim/não
Testes executados
Funcionalidades concluídas
Funcionalidades pendentes
Riscos RGPD
Riscos funcionais
Riscos de publicação
```

---

# 35. Critérios de aceitação

A Sprint 26 está concluída quando:

```text
Existe portal completo do inquilino.
Inquilino acede apenas aos seus dados.
Inquilino consulta contratos próprios.
Inquilino consulta faturas próprias.
Inquilino consulta pagamentos próprios.
Inquilino descarrega documentos autorizados.
Backoffice gere faturas/rendas.
Backoffice regista pagamentos.
Cobranças automáticas internas existem e são idempotentes.
Não existe integração bancária real não autorizada.
Inquilino cria pedidos de manutenção.
Backoffice gere pedidos de manutenção.
Intervenções ficam historizadas.
Inquilino consulta estado dos pedidos.
Backoffice agenda vistorias.
Inquilino consulta vistorias próprias.
Relatórios de vistoria/manutenção ficam protegidos.
Comunicações município-inquilino existem.
Mensagens internas não aparecem ao inquilino.
Histórico de intervenções existe.
Relatórios de manutenção podem ser gerados.
Dashboard operacional do senhorio existe.
Dashboard mostra contratos, faturas, pagamentos, manutenção e vistorias.
Notificações são emitidas se módulo existir.
Auditoria é criada se módulo existir.
Dados pessoais não são expostos indevidamente.
PHPStan foi executado antes da implementação, se disponível.
PHPStan foi executado antes da publicação, se disponível.
Foram considerados os 2471 erros legados.
Sprint 26 não introduz erros PHPStan novos nos ficheiros alterados.
php artisan route:list executa sem erro.
php artisan test executa sem erro ou falhas são documentadas.
npm run build executa sem erro ou falhas são documentadas.
./vendor/bin/pint executa sem erro ou alterações são documentadas.
Documentação foi criada/atualizada.
Não foram usadas credenciais.
Não foram usados dados pessoais reais.
Não foram implementadas funcionalidades fora de âmbito.
```

---

# 36. Resposta final esperada do Codex

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Estado PHPStan inicial
4. Estado PHPStan antes de publicação
5. Erros PHPStan legados considerados: 2471
6. Novos erros PHPStan introduzidos pela Sprint 26: sim/não
7. Models criados ou alterados
8. Migrations criadas
9. Services criados ou alterados
10. Controllers criados ou alterados
11. Form Requests criados ou alterados
12. Policies criadas ou alteradas
13. Rotas da área do inquilino criadas ou alteradas
14. Rotas de backoffice criadas ou alteradas
15. Views/components criados ou alterados
16. Estado do portal do inquilino
17. Estado da consulta de contratos
18. Estado da consulta de faturas
19. Estado da gestão de pagamentos
20. Estado das cobranças automáticas internas
21. Estado dos pedidos de manutenção
22. Estado do agendamento de vistorias
23. Estado das comunicações município-inquilino
24. Estado do histórico de intervenções
25. Estado dos relatórios de manutenção
26. Estado do dashboard operacional do senhorio
27. Estado das notificações/auditoria
28. Testes criados ou alterados
29. Resultado de php artisan route:list
30. Resultado de php artisan test
31. Resultado de php artisan migrate, se aplicável
32. Resultado de npm run build, se aplicável
33. Resultado de ./vendor/bin/pint, se aplicável
34. Resultado de PHPStan/Psalm, se aplicável
35. Riscos ainda existentes
36. Pendências técnicas
37. Confirmação de que não foram usados dados pessoais reais
38. Confirmação de que não foram usadas credenciais
39. Confirmação de que não foram implementadas funcionalidades fora de âmbito
40. Recomendação objetiva para avançar ou não para Sprint 27
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 37. Definition of Done

A Sprint 26 só está concluída quando existir uma área completa do inquilino e gestão integrada da exploração habitacional, incluindo consulta de contratos, faturas, pagamentos, cobranças internas, pedidos de manutenção, vistorias, comunicações, histórico de intervenções, relatórios de manutenção e dashboard operacional do senhorio, com permissões, RGPD, auditoria, testes e validação PHPStan sem aumento do passivo legado de 2471 erros.

Fim da Sprint 26.
