# Sprint 15 — Manutenção, Vistorias e Gestão do Imóvel

## Prioridade de desenvolvimento

Esta sprint pertence à fase de gestão operacional pós-contrato da plataforma municipal de Arrendamento Acessível.

A Sprint 15 deve ser executada depois da Sprint 13 e, preferencialmente, depois da Sprint 14, usando contratos ativos, habitações contratualizadas e arrendatários com acesso à área pessoal.

Ordem operacional recomendada:

```text
Sprint 12 — Atribuição de Habitações
Sprint 13 — Cálculo de Renda, Contratos e Caução
Sprint 14 — Pagamentos, Incumprimentos e Revisão de Renda
Sprint 15 — Manutenção, Vistorias e Gestão do Imóvel
Sprint 16 — Comunicações, Notificações e Prazos
```

---

# 1. Objetivo da Sprint

Implementar o módulo completo de manutenção, vistorias e histórico técnico do imóvel.

A plataforma deve permitir que o arrendatário:

```text
Submeta pedidos de manutenção
Classifique o problema
Indique urgência percebida
Anexe fotografias e documentos
Acompanhe o estado do pedido
Consulte intervenções realizadas na sua habitação
Consulte vistorias que lhe sejam disponibilizadas
```

A plataforma deve permitir que o Município:

```text
Receba pedidos de manutenção
Classifique urgência técnica
Atribua pedidos a técnico interno ou fornecedor
Agende intervenções
Registe execução
Registe resolução ou rejeição
Registe custos de intervenção
Registe fotografias e anexos
Crie vistorias iniciais, periódicas, finais e extraordinárias
Crie autos de vistoria
Mantenha histórico técnico completo do imóvel
Consulte indicadores de manutenção
Consulte relatórios de custos
```

Esta sprint deve evoluir qualquer módulo de manutenção existente para uma gestão operacional completa da habitação.

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 15.

Não avances para Sprint 16, Sprint 17, Sprint 18 ou qualquer sprint futura sem validação explícita.

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

docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
docs/backlog/sprint-14-pagamentos-incumprimentos-revisao-renda.md
docs/backlog/sprint-15-manutencao-vistorias-gestao-imovel.md

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
Sistema de storage privado, se existir
Sistema de PDF/document generation, se existir

Modelo User
Modelo HousingUnit
Modelo LeaseContract ou Contract
Modelo Application
Modelo Allocation
Modelo DocumentSubmission, se existir
Modelo OfficialNotification, se existir
Modelo AuditLog, se existir
Modelo MaintenanceRequest, se existir
Modelo MaintenanceRequestStatusHistory, se existir
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
MaintenanceRequest
MaintenanceCategory
MaintenancePriority
MaintenanceAssignment
MaintenanceIntervention
MaintenanceVisit
MaintenanceAttachment
MaintenanceCost
MaintenanceSupplier
Inspection
InspectionRecord
InspectionChecklist
InspectionChecklistItem
InspectionAttachment
InspectionPhoto
PropertyTechnicalHistory
PropertyHistoryEvent
PropertyCostReport
```

reaproveitar ou adaptar com compatibilidade.

Não apagar pedidos de manutenção existentes.

Não apagar contratos existentes.

Não apagar histórico técnico existente.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY ou credenciais.

---

# 3. Dependências funcionais

Esta sprint depende obrigatoriamente de:

```text
HousingUnit ou entidade equivalente de habitação
User ou entidade equivalente de utilizador
Sistema de autenticação
```

Depende preferencialmente de:

```text
Sprint 13 — Cálculo de Renda, Contratos e Caução
Sprint 14 — Pagamentos, Incumprimentos e Revisão de Renda
Sprint 6 — Gestão Documental Avançada
Sistema de notificações internas
Sistema de auditoria
```

## Dependência de habitação

Se não existir `HousingUnit` ou equivalente, interrompe a implementação funcional e informa:

```text
A Sprint 15 depende de uma entidade de habitação/imóvel.
```

Não criar um modelo paralelo de imóveis se já existir `HousingUnit`.

## Dependência de contrato

Se não existir `LeaseContract`, `Contract` ou equivalente, ainda é possível criar gestão técnica por imóvel, mas o acesso do arrendatário deve ser limitado.

Se existir contrato, pedidos do arrendatário devem ser associados ao contrato ativo.

Se não existir contrato, documentar:

```text
Pedidos de manutenção pelo arrendatário ficam pendentes de integração com contratos ativos.
```

## Dependência documental

Se a Sprint 6 existir, anexos e fotografias devem usar o sistema documental existente sempre que possível.

Se não existir, criar storage privado próprio para anexos técnicos, sem usar `public/storage` para documentos sensíveis.

---

# 4. Validação jurídica, operacional e RGPD

Esta sprint lida com dados pessoais, habitações, fotografias e situações potencialmente sensíveis.

Regras obrigatórias:

```text
Fotografias de vistorias e manutenção devem ser guardadas em storage privado.
Não expor fotografias publicamente.
Não expor morada completa em listagens públicas.
Arrendatário só pode consultar pedidos da sua própria habitação/contrato.
Fornecedor só deve consultar pedidos que lhe foram atribuídos.
Técnico só deve consultar pedidos conforme permissões.
Auditor não altera dados.
Custos de intervenção devem ser auditáveis.
Rejeições de pedidos devem exigir motivo.
Fecho de pedido deve preservar histórico.
Autos de vistoria devem preservar versão e data.
```

Não implementar comunicação externa com fornecedores sem validação.

Não implementar faturação de fornecedores.

Não implementar compras públicas, contratação pública, cabimentos, compromissos ou pagamentos a fornecedores nesta sprint.

Textos de autos, notificações e relatórios devem ser parametrizáveis ou tratados como minutas sujeitas a validação.

---

# 5. Âmbito incluído

Implementar:

```text
Pedidos de manutenção pelo arrendatário
Pedidos de manutenção criados pelo backoffice
Categorias de manutenção
Classificação de urgência
Estados de manutenção
Atribuição a técnico interno
Atribuição a fornecedor
Agendamento de intervenção
Registo de intervenção
Registo de resolução
Registo de rejeição com motivo
Fecho de pedido
Fotografias e anexos
Custos de intervenção
Histórico de estados
Histórico técnico do imóvel
Vistorias iniciais
Vistorias periódicas
Vistorias finais
Vistorias extraordinárias
Autos de vistoria
Checklist de vistoria
Fotografias e anexos de vistoria
Indicadores de manutenção
Relatórios de custos
Notificações internas
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
Contratação pública
Consulta ao mercado
Processo de compra pública
Gestão de stocks de materiais
Faturação de fornecedores
Pagamentos a fornecedores
Integração contabilística
Integração com seguradoras
Processos judiciais
Peritagens externas certificadas
Assinatura digital qualificada
Envio real de SMS
Envio real de email sem módulo seguro
App mobile nativa
IoT ou sensores de habitação
```

Podem ser criados pontos de integração para futuras sprints.

---

# 7. Conceito funcional

O fluxo de manutenção deve ser:

```text
Arrendatário ou técnico cria pedido
→ Pedido entra como Novo
→ Município analisa
→ Município classifica urgência
→ Município atribui a técnico ou fornecedor
→ Município agenda intervenção
→ Técnico/fornecedor regista execução
→ Município regista custo
→ Pedido é resolvido ou rejeitado
→ Pedido é fechado
→ Histórico técnico do imóvel é atualizado
```

O fluxo de vistoria deve ser:

```text
Município cria vistoria
→ Define tipo de vistoria
→ Agenda vistoria
→ Regista checklist
→ Adiciona fotografias/anexos
→ Gera auto de vistoria
→ Valida internamente
→ Fecha vistoria
→ Histórico técnico do imóvel é atualizado
```

---

# 8. Estados principais

## MaintenanceRequestStatus

```text
new
under_review
scheduled
in_progress
resolved
rejected
closed
cancelled
```

Mapeamento visual em português:

```text
new = Novo
under_review = Em análise
scheduled = Agendado
in_progress = Em execução
resolved = Resolvido
rejected = Rejeitado
closed = Fechado
cancelled = Cancelado
```

## MaintenanceUrgency

```text
low
normal
urgent
emergency
```

## MaintenanceSource

```text
tenant
municipal_technician
inspection
system
supplier
other
```

## MaintenanceAssignmentType

```text
internal_technician
external_supplier
team
unassigned
```

## MaintenanceInterventionStatus

```text
planned
scheduled
in_progress
completed
cancelled
failed
```

## MaintenanceCostStatus

```text
estimated
approved
incurred
rejected
cancelled
```

## InspectionType

```text
initial
periodic
final
extraordinary
```

Mapeamento visual:

```text
initial = Inicial
periodic = Periódica
final = Final
extraordinary = Extraordinária
```

## InspectionStatus

```text
draft
scheduled
in_progress
completed
validated
rejected
closed
cancelled
```

## InspectionCondition

```text
good
acceptable
requires_repair
poor
critical
not_applicable
```

## TechnicalHistoryEventType

```text
maintenance_request_created
maintenance_status_changed
maintenance_assigned
maintenance_intervention_completed
maintenance_cost_registered
inspection_created
inspection_completed
inspection_validated
contract_started
contract_ended
other
```

---

# 9. Modelo de dados

## 9.1 MaintenanceCategory

Criar entidade:

```text
MaintenanceCategory
```

Tabela:

```text
maintenance_categories
```

Objetivo:

```text
Classificar pedidos de manutenção por área técnica.
```

Campos mínimos:

```text
id
parent_id
name
description
code
is_active
sort_order
created_at
updated_at
deleted_at
```

Categorias iniciais recomendadas:

```text
Canalização
Eletricidade
Gás
Estrutura
Cobertura
Caixilharia
Humidade/Infiltrações
Equipamentos
Segurança
Limpeza/Áreas comuns
Outro
```

Usar apenas seeders com dados genéricos.

---

## 9.2 MaintenanceSupplier

Criar entidade se não existir fornecedor equivalente:

```text
MaintenanceSupplier
```

Tabela:

```text
maintenance_suppliers
```

Objetivo:

```text
Registar fornecedores externos que possam receber intervenções.
```

Campos mínimos:

```text
id
name
tax_number
email
phone
address
specialty
is_active
notes
internal_notes
created_at
updated_at
deleted_at
```

Regras:

```text
Fornecedor não deve ter acesso automático ao sistema sem user associado.
Se existir user de fornecedor, associar explicitamente.
Não usar dados reais em seeders.
```

---

## 9.3 MaintenanceRequest

Criar ou adaptar entidade existente:

```text
MaintenanceRequest
```

Tabela recomendada:

```text
maintenance_requests
```

Objetivo:

```text
Registar pedidos de manutenção criados por arrendatário ou backoffice.
```

Campos mínimos:

```text
id
request_number

housing_unit_id
lease_contract_id
application_id
user_id

maintenance_category_id

source
status
urgency
technical_priority

title
description
location_in_property
tenant_availability
access_instructions

reported_at
reviewed_at
scheduled_at
started_at
resolved_at
rejected_at
closed_at
cancelled_at

rejection_reason
resolution_summary
closure_notes

created_by
reviewed_by
closed_by

created_at
updated_at
deleted_at
```

Regras:

```text
request_number obrigatório e único.
Arrendatário só pode criar pedido para habitação/contrato próprio.
Rejeição exige motivo.
Fecho exige estado resolved ou rejected, salvo permissão especial.
Mudança de estado deve criar histórico.
Usar soft deletes.
```

---

## 9.4 MaintenanceRequestStatusHistory

Criar entidade:

```text
MaintenanceRequestStatusHistory
```

Tabela:

```text
maintenance_request_status_histories
```

Campos mínimos:

```text
id
maintenance_request_id
from_status
to_status
changed_by
reason
notes
created_at
```

Regras:

```text
Criar registo sempre que o estado muda.
Não apagar histórico.
```

---

## 9.5 MaintenanceAssignment

Criar entidade:

```text
MaintenanceAssignment
```

Tabela:

```text
maintenance_assignments
```

Objetivo:

```text
Registar atribuição de pedido a técnico interno ou fornecedor.
```

Campos mínimos:

```text
id
maintenance_request_id

assignment_type
assigned_user_id
maintenance_supplier_id

status
assigned_by
assigned_at
accepted_at
rejected_at
completed_at

assignment_notes
rejection_reason

created_at
updated_at
deleted_at
```

Estados recomendados:

```text
assigned
accepted
rejected
completed
cancelled
```

Regras:

```text
Pode existir histórico de várias atribuições.
Apenas uma atribuição ativa por pedido, salvo equipas.
Fornecedor só vê pedidos atribuídos se tiver acesso autenticado e policy.
```

---

## 9.6 MaintenanceIntervention

Criar entidade:

```text
MaintenanceIntervention
```

Tabela:

```text
maintenance_interventions
```

Objetivo:

```text
Registar visitas/intervenções técnicas.
```

Campos mínimos:

```text
id
maintenance_request_id
housing_unit_id
lease_contract_id

status
scheduled_for
started_at
ended_at

performed_by_user_id
maintenance_supplier_id

work_description
materials_used
result_summary
next_steps
requires_follow_up
follow_up_date

created_by
created_at
updated_at
deleted_at
```

Regras:

```text
Intervenção concluída pode atualizar pedido para resolved se configurado.
Intervenção pode gerar custos.
Intervenção deve atualizar histórico técnico do imóvel.
```

---

## 9.7 MaintenanceAttachment

Criar entidade se não for possível reutilizar DocumentSubmission:

```text
MaintenanceAttachment
```

Tabela:

```text
maintenance_attachments
```

Campos mínimos:

```text
id
maintenance_request_id
maintenance_intervention_id
uploaded_by

attachment_type
title
description

storage_disk
storage_path
mime_type
file_size
checksum

is_visible_to_tenant
is_internal

created_at
updated_at
deleted_at
```

Tipos recomendados:

```text
photo
document
invoice
technical_report
other
```

Regras:

```text
Guardar em storage privado.
Não expor storage_path.
Downloads devem passar por controller autorizado.
Se Sprint 6 existir, preferir DocumentSubmission ou DocumentVersion.
```

---

## 9.8 MaintenanceCost

Criar entidade:

```text
MaintenanceCost
```

Tabela:

```text
maintenance_costs
```

Objetivo:

```text
Registar custos estimados e reais de intervenção.
```

Campos mínimos:

```text
id
maintenance_request_id
maintenance_intervention_id
housing_unit_id
lease_contract_id

status
cost_type
description
amount
currency

supplier_id
approved_by
approved_at
incurred_at
rejected_at
rejection_reason

invoice_reference
notes
internal_notes

created_by
created_at
updated_at
deleted_at
```

Tipos recomendados:

```text
labor
materials
travel
inspection
external_service
other
```

Regras:

```text
Custos não geram pagamento financeiro nesta sprint.
Custos devem alimentar relatórios.
Alterações de custo devem ser auditáveis.
```

---

## 9.9 InspectionChecklistTemplate

Criar entidade:

```text
InspectionChecklistTemplate
```

Tabela:

```text
inspection_checklist_templates
```

Objetivo:

```text
Definir modelos de checklist para vistorias.
```

Campos mínimos:

```text
id
name
description
inspection_type
is_active
created_by
updated_by
created_at
updated_at
deleted_at
```

---

## 9.10 InspectionChecklistTemplateItem

Criar entidade:

```text
InspectionChecklistTemplateItem
```

Tabela:

```text
inspection_checklist_template_items
```

Campos mínimos:

```text
id
inspection_checklist_template_id
section
label
description
expected_condition
is_required
sort_order
created_at
updated_at
deleted_at
```

Itens iniciais recomendados:

```text
Estado geral
Paredes e tetos
Pavimentos
Portas e janelas
Instalação elétrica
Canalização
Equipamentos
Casas de banho
Cozinha
Humidades/infiltrações
Segurança
Limpeza
Anomalias observadas
```

---

## 9.11 PropertyInspection

Criar entidade:

```text
PropertyInspection
```

Tabela:

```text
property_inspections
```

Objetivo:

```text
Registar vistorias do imóvel.
```

Campos mínimos:

```text
id
inspection_number

housing_unit_id
lease_contract_id
application_id
user_id

inspection_checklist_template_id

inspection_type
status

scheduled_for
started_at
completed_at
validated_at
closed_at
cancelled_at

inspector_user_id
validated_by

general_condition
summary
recommendations
tenant_present
tenant_observations
internal_notes

created_by
created_at
updated_at
deleted_at
```

Regras:

```text
inspection_number obrigatório e único.
Vistoria final pode estar associada ao fim de contrato.
Vistoria inicial pode estar associada ao início de contrato.
Vistoria concluída deve gerar auto.
Vistoria deve atualizar histórico técnico do imóvel.
```

---

## 9.12 PropertyInspectionItem

Criar entidade:

```text
PropertyInspectionItem
```

Tabela:

```text
property_inspection_items
```

Campos mínimos:

```text
id
property_inspection_id
inspection_checklist_template_item_id

section
label
condition
observations
requires_maintenance
maintenance_request_id

sort_order
created_at
updated_at
deleted_at
```

Regras:

```text
Item com requires_maintenance pode gerar MaintenanceRequest.
Condição crítica deve poder gerar prioridade alta.
```

---

## 9.13 PropertyInspectionAttachment

Criar entidade:

```text
PropertyInspectionAttachment
```

Tabela:

```text
property_inspection_attachments
```

Campos mínimos:

```text
id
property_inspection_id
property_inspection_item_id
uploaded_by

attachment_type
title
description

storage_disk
storage_path
mime_type
file_size
checksum

is_visible_to_tenant
is_internal

created_at
updated_at
deleted_at
```

Regras:

```text
Guardar fotografias e anexos em storage privado.
Não expor storage_path.
Downloads devem passar por policy.
```

---

## 9.14 PropertyInspectionReport

Criar entidade:

```text
PropertyInspectionReport
```

Tabela:

```text
property_inspection_reports
```

Objetivo:

```text
Guardar auto de vistoria.
```

Campos mínimos:

```text
id
property_inspection_id
housing_unit_id
lease_contract_id

report_number
status
title
html_content

storage_disk
storage_path
mime_type
file_size
checksum

generated_by
generated_at
validated_by
validated_at
issued_at
cancelled_at
cancellation_reason

created_at
updated_at
deleted_at
```

Estados recomendados:

```text
draft
generated
validated
issued
cancelled
archived
```

Regras:

```text
Auto pode existir como HTML mesmo sem PDF.
Se PDF for gerado, guardar em storage privado.
Não expor storage_path.
```

---

## 9.15 PropertyHistoryEvent

Criar entidade:

```text
PropertyHistoryEvent
```

Tabela:

```text
property_history_events
```

Objetivo:

```text
Manter histórico técnico consolidado do imóvel.
```

Campos mínimos:

```text
id
housing_unit_id
lease_contract_id
application_id
user_id

event_type
source_type
source_id

title
description
event_date
cost_amount
metadata

created_by
created_at
```

Regras:

```text
Histórico deve ser append-only sempre que possível.
Não guardar dados pessoais excessivos em metadata.
Eventos devem ser criados por services.
```

---

# 10. Enums recomendados

Criar, se a versão do PHP permitir:

```text
App\Enums\MaintenanceRequestStatus
App\Enums\MaintenanceUrgency
App\Enums\MaintenanceSource
App\Enums\MaintenanceAssignmentType
App\Enums\MaintenanceAssignmentStatus
App\Enums\MaintenanceInterventionStatus
App\Enums\MaintenanceAttachmentType
App\Enums\MaintenanceCostStatus
App\Enums\MaintenanceCostType
App\Enums\InspectionType
App\Enums\InspectionStatus
App\Enums\InspectionCondition
App\Enums\InspectionAttachmentType
App\Enums\InspectionReportStatus
App\Enums\TechnicalHistoryEventType
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 11. Relações obrigatórias

## HousingUnit

Adicionar:

```text
hasMany MaintenanceRequest
hasMany MaintenanceIntervention
hasMany MaintenanceCost
hasMany PropertyInspection
hasMany PropertyHistoryEvent
```

## LeaseContract

Adicionar:

```text
hasMany MaintenanceRequest
hasMany MaintenanceIntervention
hasMany MaintenanceCost
hasMany PropertyInspection
hasMany PropertyHistoryEvent
```

## User

Adicionar conforme necessário:

```text
hasMany MaintenanceRequest as requester
hasMany MaintenanceAssignment as assignedTechnician
hasMany PropertyInspection as inspector
```

## MaintenanceCategory

```text
belongsTo MaintenanceCategory as parent nullable
hasMany MaintenanceCategory as children
hasMany MaintenanceRequest
```

## MaintenanceRequest

```text
belongsTo HousingUnit
belongsTo LeaseContract nullable
belongsTo Application nullable
belongsTo User as requester nullable
belongsTo MaintenanceCategory nullable
belongsTo User as createdBy nullable
belongsTo User as reviewedBy nullable
belongsTo User as closedBy nullable

hasMany MaintenanceRequestStatusHistory
hasMany MaintenanceAssignment
hasMany MaintenanceIntervention
hasMany MaintenanceAttachment
hasMany MaintenanceCost
hasMany PropertyHistoryEvent as source
```

## MaintenanceAssignment

```text
belongsTo MaintenanceRequest
belongsTo User as assignedUser nullable
belongsTo MaintenanceSupplier nullable
belongsTo User as assignedBy
```

## MaintenanceIntervention

```text
belongsTo MaintenanceRequest
belongsTo HousingUnit
belongsTo LeaseContract nullable
belongsTo User as performedBy nullable
belongsTo MaintenanceSupplier nullable
belongsTo User as createdBy
hasMany MaintenanceAttachment
hasMany MaintenanceCost
```

## PropertyInspection

```text
belongsTo HousingUnit
belongsTo LeaseContract nullable
belongsTo Application nullable
belongsTo User as tenant nullable
belongsTo InspectionChecklistTemplate nullable
belongsTo User as inspector nullable
belongsTo User as validatedBy nullable
belongsTo User as createdBy

hasMany PropertyInspectionItem
hasMany PropertyInspectionAttachment
hasOne PropertyInspectionReport
```

## PropertyInspectionItem

```text
belongsTo PropertyInspection
belongsTo InspectionChecklistTemplateItem nullable
belongsTo MaintenanceRequest nullable
```

## PropertyInspectionReport

```text
belongsTo PropertyInspection
belongsTo HousingUnit
belongsTo LeaseContract nullable
belongsTo User as generatedBy
belongsTo User as validatedBy nullable
```

## PropertyHistoryEvent

```text
belongsTo HousingUnit
belongsTo LeaseContract nullable
belongsTo Application nullable
belongsTo User as tenant nullable
belongsTo User as createdBy nullable
morphTo source
```

---

# 12. Services obrigatórios

Criar:

```text
App\Services\Maintenance\MaintenanceRequestService
App\Services\Maintenance\MaintenanceStatusService
App\Services\Maintenance\MaintenanceAssignmentService
App\Services\Maintenance\MaintenanceInterventionService
App\Services\Maintenance\MaintenanceAttachmentService
App\Services\Maintenance\MaintenanceCostService
App\Services\Maintenance\MaintenanceNotificationService
App\Services\Maintenance\MaintenanceIndicatorService

App\Services\Inspections\InspectionTemplateService
App\Services\Inspections\PropertyInspectionService
App\Services\Inspections\PropertyInspectionItemService
App\Services\Inspections\PropertyInspectionAttachmentService
App\Services\Inspections\PropertyInspectionReportService

App\Services\Properties\PropertyTechnicalHistoryService
App\Services\Properties\PropertyCostReportService
```

---

## 12.1 MaintenanceRequestService

Responsável por:

```text
Criar pedido de manutenção
Gerar número único do pedido
Associar pedido ao contrato ativo, quando existir
Associar pedido à habitação
Validar acesso do arrendatário
Classificar categoria
Guardar descrição
Definir origem
Definir urgência inicial
Criar histórico técnico
Criar notificação interna
```

---

## 12.2 MaintenanceStatusService

Responsável por:

```text
Controlar transições de estado
Criar histórico de estados
Validar estados permitidos
Exigir motivo em rejeição
Exigir resumo em resolução
Exigir motivo em cancelamento
Impedir edição indevida de pedido fechado
```

Transições mínimas recomendadas:

```text
new → under_review
under_review → scheduled
under_review → rejected
scheduled → in_progress
scheduled → cancelled
in_progress → resolved
resolved → closed
rejected → closed
new → cancelled
under_review → cancelled
```

---

## 12.3 MaintenanceAssignmentService

Responsável por:

```text
Atribuir pedido a técnico interno
Atribuir pedido a fornecedor
Alterar atribuição
Cancelar atribuição
Registar aceitação/rejeição de atribuição
Registar conclusão da atribuição
Criar notificação interna
```

---

## 12.4 MaintenanceInterventionService

Responsável por:

```text
Criar intervenção
Agendar intervenção
Iniciar intervenção
Concluir intervenção
Cancelar intervenção
Registar descrição dos trabalhos
Registar materiais usados
Registar necessidade de seguimento
Atualizar pedido de manutenção quando aplicável
Atualizar histórico técnico do imóvel
```

---

## 12.5 MaintenanceAttachmentService

Responsável por:

```text
Guardar fotografias e anexos
Validar tipo e tamanho
Guardar em storage privado
Gerar checksum
Associar a pedido ou intervenção
Controlar visibilidade para arrendatário
Permitir download seguro
```

Se existir sistema documental da Sprint 6, preferir integração com esse sistema.

---

## 12.6 MaintenanceCostService

Responsável por:

```text
Registar custo estimado
Registar custo real
Aprovar custo
Rejeitar custo
Associar custo a intervenção
Associar custo a fornecedor
Atualizar histórico técnico
Alimentar relatórios de custo
```

Não gerar pagamentos a fornecedores.

---

## 12.7 PropertyInspectionService

Responsável por:

```text
Criar vistoria
Gerar número de vistoria
Definir tipo de vistoria
Agendar vistoria
Iniciar vistoria
Concluir vistoria
Validar vistoria
Cancelar vistoria
Criar itens a partir de template
Atualizar histórico técnico do imóvel
Criar pedidos de manutenção a partir de anomalias críticas
```

---

## 12.8 PropertyInspectionReportService

Responsável por:

```text
Gerar auto de vistoria em HTML
Gerar PDF se infraestrutura existir
Guardar documento em storage privado
Gerar número de auto
Registar checksum
Permitir download seguro
Validar auto
Emitir auto
Cancelar auto com motivo
```

Se não existir biblioteca PDF:

```text
Gerar HTML imprimível
Documentar pendência da geração PDF
Não afirmar que PDF foi gerado
```

---

## 12.9 PropertyTechnicalHistoryService

Responsável por:

```text
Criar evento técnico no histórico do imóvel
Criar eventos de manutenção
Criar eventos de vistoria
Criar eventos de custo
Criar eventos de contrato, se aplicável
Consultar histórico por imóvel
Consultar histórico por contrato
Consultar histórico por período
```

---

## 12.10 MaintenanceIndicatorService

Responsável por:

```text
Calcular número de pedidos por estado
Calcular pedidos por urgência
Calcular tempo médio de resolução
Calcular pedidos em atraso
Calcular custo total por imóvel
Calcular custo total por categoria
Calcular custo total por fornecedor
Calcular imóveis com mais ocorrências
Gerar dados para dashboard
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
Backoffice\MaintenanceCategoryController
Backoffice\MaintenanceSupplierController
Backoffice\MaintenanceRequestController
Backoffice\MaintenanceAssignmentController
Backoffice\MaintenanceInterventionController
Backoffice\MaintenanceAttachmentController
Backoffice\MaintenanceCostController

Backoffice\InspectionChecklistTemplateController
Backoffice\PropertyInspectionController
Backoffice\PropertyInspectionItemController
Backoffice\PropertyInspectionAttachmentController
Backoffice\PropertyInspectionReportController

Backoffice\PropertyTechnicalHistoryController
Backoffice\MaintenanceDashboardController
Backoffice\MaintenanceCostReportController
```

## Área do candidato / arrendatário

Namespace recomendado:

```text
App\Http\Controllers\Candidate
```

Controllers:

```text
Candidate\MaintenanceRequestController
Candidate\MaintenanceAttachmentController
Candidate\PropertyInspectionController
Candidate\PropertyTechnicalHistoryController
```

O arrendatário pode criar e consultar pedidos próprios.

O arrendatário não pode atribuir pedidos, aprovar custos, validar vistorias ou consultar histórico de outros imóveis.

---

# 14. Form Requests

Criar:

```text
StoreMaintenanceCategoryRequest
UpdateMaintenanceCategoryRequest

StoreMaintenanceSupplierRequest
UpdateMaintenanceSupplierRequest

StoreMaintenanceRequestRequest
UpdateMaintenanceRequestRequest
ReviewMaintenanceRequestRequest
RejectMaintenanceRequestRequest
ResolveMaintenanceRequestRequest
CloseMaintenanceRequestRequest

StoreMaintenanceAssignmentRequest
UpdateMaintenanceAssignmentRequest

StoreMaintenanceInterventionRequest
UpdateMaintenanceInterventionRequest
CompleteMaintenanceInterventionRequest

StoreMaintenanceAttachmentRequest
StoreMaintenanceCostRequest
ApproveMaintenanceCostRequest
RejectMaintenanceCostRequest

StoreInspectionChecklistTemplateRequest
UpdateInspectionChecklistTemplateRequest
StoreInspectionChecklistTemplateItemRequest

StorePropertyInspectionRequest
UpdatePropertyInspectionRequest
CompletePropertyInspectionRequest
ValidatePropertyInspectionRequest
CancelPropertyInspectionRequest

StorePropertyInspectionItemRequest
UpdatePropertyInspectionItemRequest
StorePropertyInspectionAttachmentRequest
GeneratePropertyInspectionReportRequest
ValidatePropertyInspectionReportRequest
CancelPropertyInspectionReportRequest
```

## StoreMaintenanceRequestRequest

```text
housing_unit_id nullable|exists:housing_units,id
lease_contract_id nullable|exists:lease_contracts,id
maintenance_category_id nullable|exists:maintenance_categories,id
urgency required|string|max:100
title required|string|max:255
description required|string|min:10|max:10000
location_in_property nullable|string|max:255
tenant_availability nullable|string|max:3000
access_instructions nullable|string|max:3000
attachments nullable|array
attachments.* file|mimes:jpg,jpeg,png,pdf,webp|max:10240
```

Regras adicionais:

```text
Arrendatário só pode criar pedido para contrato/habitação própria.
Backoffice pode criar pedido para qualquer habitação autorizada.
```

## ReviewMaintenanceRequestRequest

```text
technical_priority nullable|string|max:100
maintenance_category_id nullable|exists:maintenance_categories,id
urgency required|string|max:100
review_notes nullable|string|max:3000
```

## RejectMaintenanceRequestRequest

```text
rejection_reason required|string|min:10|max:5000
```

## ResolveMaintenanceRequestRequest

```text
resolution_summary required|string|min:10|max:5000
closure_notes nullable|string|max:3000
```

## StoreMaintenanceAssignmentRequest

```text
maintenance_request_id required|exists:maintenance_requests,id
assignment_type required|string|max:100
assigned_user_id nullable|exists:users,id
maintenance_supplier_id nullable|exists:maintenance_suppliers,id
assignment_notes nullable|string|max:3000
```

Regra adicional:

```text
Deve existir assigned_user_id ou maintenance_supplier_id conforme assignment_type.
```

## StoreMaintenanceInterventionRequest

```text
maintenance_request_id required|exists:maintenance_requests,id
scheduled_for nullable|date
performed_by_user_id nullable|exists:users,id
maintenance_supplier_id nullable|exists:maintenance_suppliers,id
work_description nullable|string|max:10000
materials_used nullable|string|max:5000
```

## CompleteMaintenanceInterventionRequest

```text
work_description required|string|min:10|max:10000
materials_used nullable|string|max:5000
result_summary required|string|min:10|max:5000
next_steps nullable|string|max:5000
requires_follow_up boolean
follow_up_date nullable|date|after_or_equal:today
```

## StoreMaintenanceCostRequest

```text
maintenance_request_id required|exists:maintenance_requests,id
maintenance_intervention_id nullable|exists:maintenance_interventions,id
cost_type required|string|max:100
description required|string|max:3000
amount required|numeric|min:0
currency required|string|max:3
supplier_id nullable|exists:maintenance_suppliers,id
invoice_reference nullable|string|max:255
notes nullable|string|max:3000
internal_notes nullable|string|max:3000
```

## StorePropertyInspectionRequest

```text
housing_unit_id required|exists:housing_units,id
lease_contract_id nullable|exists:lease_contracts,id
inspection_checklist_template_id nullable|exists:inspection_checklist_templates,id
inspection_type required|string|max:100
scheduled_for nullable|date
inspector_user_id nullable|exists:users,id
summary nullable|string|max:5000
internal_notes nullable|string|max:5000
```

## CompletePropertyInspectionRequest

```text
general_condition required|string|max:100
summary required|string|min:10|max:10000
recommendations nullable|string|max:10000
tenant_present boolean
tenant_observations nullable|string|max:5000
items nullable|array
items.*.id nullable|exists:property_inspection_items,id
items.*.condition nullable|string|max:100
items.*.observations nullable|string|max:5000
items.*.requires_maintenance boolean
```

---

# 15. Policies

Criar:

```text
MaintenanceCategoryPolicy
MaintenanceSupplierPolicy
MaintenanceRequestPolicy
MaintenanceAssignmentPolicy
MaintenanceInterventionPolicy
MaintenanceAttachmentPolicy
MaintenanceCostPolicy
InspectionChecklistTemplatePolicy
PropertyInspectionPolicy
PropertyInspectionItemPolicy
PropertyInspectionAttachmentPolicy
PropertyInspectionReportPolicy
PropertyHistoryEventPolicy
MaintenanceDashboardPolicy
MaintenanceCostReportPolicy
```

## Regras para arrendatário/candidato

```text
Só cria pedidos para a sua própria habitação/contrato.
Só vê pedidos próprios.
Só vê intervenções associadas aos seus pedidos ou habitação própria quando permitido.
Só vê anexos marcados como visíveis para arrendatário.
Só vê vistorias da sua habitação quando marcadas como visíveis.
Não vê custos internos, salvo permissão explícita.
Não atribui pedidos.
Não agenda intervenções.
Não fecha pedidos administrativamente.
Não valida vistorias.
Não consulta histórico de outros imóveis.
```

## Regras para técnico municipal

```text
Pode consultar pedidos conforme permissão.
Pode classificar urgência.
Pode atribuir pedidos.
Pode agendar intervenções.
Pode registar intervenção.
Pode registar custos se autorizado.
Pode criar vistorias.
Pode gerar autos.
Não aprova custos se política exigir perfil superior.
```

## Regras para fornecedor

```text
Só vê pedidos/intervenções atribuídos.
Só vê informação necessária para execução.
Não vê dados financeiros do contrato.
Não vê dados pessoais excessivos do arrendatário.
Pode registar execução apenas se autorizado.
```

## Regras para gestor municipal/admin

```text
Pode gerir categorias.
Pode gerir fornecedores.
Pode gerir pedidos.
Pode aprovar custos.
Pode validar vistorias.
Pode consultar indicadores.
Pode consultar histórico técnico.
```

## Regras para auditor

```text
Pode consultar pedidos, vistorias, custos e histórico.
Não altera pedidos.
Não altera custos.
Não valida vistorias.
Não apaga anexos.
```

---

# 16. Rotas

## Backoffice

Criar, preferencialmente:

```text
GET /backoffice/maintenance
GET /backoffice/maintenance/dashboard

GET /backoffice/maintenance/categories
GET /backoffice/maintenance/categories/create
POST /backoffice/maintenance/categories
GET /backoffice/maintenance/categories/{maintenanceCategory}/edit
PUT/PATCH /backoffice/maintenance/categories/{maintenanceCategory}
DELETE /backoffice/maintenance/categories/{maintenanceCategory}

GET /backoffice/maintenance/suppliers
GET /backoffice/maintenance/suppliers/create
POST /backoffice/maintenance/suppliers
GET /backoffice/maintenance/suppliers/{maintenanceSupplier}
GET /backoffice/maintenance/suppliers/{maintenanceSupplier}/edit
PUT/PATCH /backoffice/maintenance/suppliers/{maintenanceSupplier}

GET /backoffice/maintenance/requests
GET /backoffice/maintenance/requests/create
POST /backoffice/maintenance/requests
GET /backoffice/maintenance/requests/{maintenanceRequest}
GET /backoffice/maintenance/requests/{maintenanceRequest}/edit
PUT/PATCH /backoffice/maintenance/requests/{maintenanceRequest}
POST /backoffice/maintenance/requests/{maintenanceRequest}/review
POST /backoffice/maintenance/requests/{maintenanceRequest}/schedule
POST /backoffice/maintenance/requests/{maintenanceRequest}/start
POST /backoffice/maintenance/requests/{maintenanceRequest}/resolve
POST /backoffice/maintenance/requests/{maintenanceRequest}/reject
POST /backoffice/maintenance/requests/{maintenanceRequest}/close
POST /backoffice/maintenance/requests/{maintenanceRequest}/cancel

POST /backoffice/maintenance/requests/{maintenanceRequest}/assignments
POST /backoffice/maintenance/assignments/{maintenanceAssignment}/cancel

POST /backoffice/maintenance/requests/{maintenanceRequest}/interventions
GET /backoffice/maintenance/interventions/{maintenanceIntervention}
POST /backoffice/maintenance/interventions/{maintenanceIntervention}/start
POST /backoffice/maintenance/interventions/{maintenanceIntervention}/complete
POST /backoffice/maintenance/interventions/{maintenanceIntervention}/cancel

POST /backoffice/maintenance/requests/{maintenanceRequest}/attachments
GET /backoffice/maintenance/attachments/{maintenanceAttachment}/download

POST /backoffice/maintenance/requests/{maintenanceRequest}/costs
POST /backoffice/maintenance/costs/{maintenanceCost}/approve
POST /backoffice/maintenance/costs/{maintenanceCost}/reject

GET /backoffice/inspections/templates
GET /backoffice/inspections/templates/create
POST /backoffice/inspections/templates
GET /backoffice/inspections/templates/{inspectionChecklistTemplate}/edit
PUT/PATCH /backoffice/inspections/templates/{inspectionChecklistTemplate}

GET /backoffice/inspections
GET /backoffice/inspections/create
POST /backoffice/inspections
GET /backoffice/inspections/{propertyInspection}
GET /backoffice/inspections/{propertyInspection}/edit
PUT/PATCH /backoffice/inspections/{propertyInspection}
POST /backoffice/inspections/{propertyInspection}/start
POST /backoffice/inspections/{propertyInspection}/complete
POST /backoffice/inspections/{propertyInspection}/validate
POST /backoffice/inspections/{propertyInspection}/close
POST /backoffice/inspections/{propertyInspection}/cancel

POST /backoffice/inspections/{propertyInspection}/attachments
GET /backoffice/inspections/attachments/{propertyInspectionAttachment}/download

POST /backoffice/inspections/{propertyInspection}/reports/generate
GET /backoffice/inspections/reports/{propertyInspectionReport}
GET /backoffice/inspections/reports/{propertyInspectionReport}/download
POST /backoffice/inspections/reports/{propertyInspectionReport}/validate
POST /backoffice/inspections/reports/{propertyInspectionReport}/cancel

GET /backoffice/properties/{housingUnit}/technical-history
GET /backoffice/maintenance/cost-reports
```

## Área do candidato / arrendatário

Criar, preferencialmente:

```text
GET /area-candidato/manutencao
GET /area-candidato/manutencao/pedidos
GET /area-candidato/manutencao/pedidos/criar
POST /area-candidato/manutencao/pedidos
GET /area-candidato/manutencao/pedidos/{maintenanceRequest}
POST /area-candidato/manutencao/pedidos/{maintenanceRequest}/attachments
GET /area-candidato/manutencao/attachments/{maintenanceAttachment}/download

GET /area-candidato/vistorias
GET /area-candidato/vistorias/{propertyInspection}
GET /area-candidato/vistorias/reports/{propertyInspectionReport}/download

GET /area-candidato/imovel/historico-tecnico
```

---

# 17. Views / páginas

Se o projeto usa Blade, criar:

## Backoffice

```text
resources/views/backoffice/maintenance/dashboard.blade.php

resources/views/backoffice/maintenance/categories/index.blade.php
resources/views/backoffice/maintenance/categories/create.blade.php
resources/views/backoffice/maintenance/categories/edit.blade.php

resources/views/backoffice/maintenance/suppliers/index.blade.php
resources/views/backoffice/maintenance/suppliers/create.blade.php
resources/views/backoffice/maintenance/suppliers/edit.blade.php
resources/views/backoffice/maintenance/suppliers/show.blade.php

resources/views/backoffice/maintenance/requests/index.blade.php
resources/views/backoffice/maintenance/requests/create.blade.php
resources/views/backoffice/maintenance/requests/edit.blade.php
resources/views/backoffice/maintenance/requests/show.blade.php

resources/views/backoffice/maintenance/interventions/show.blade.php
resources/views/backoffice/maintenance/costs/index.blade.php
resources/views/backoffice/maintenance/cost-reports/index.blade.php

resources/views/backoffice/inspections/templates/index.blade.php
resources/views/backoffice/inspections/templates/create.blade.php
resources/views/backoffice/inspections/templates/edit.blade.php

resources/views/backoffice/inspections/index.blade.php
resources/views/backoffice/inspections/create.blade.php
resources/views/backoffice/inspections/edit.blade.php
resources/views/backoffice/inspections/show.blade.php
resources/views/backoffice/inspections/reports/show.blade.php

resources/views/backoffice/properties/technical-history.blade.php
```

## Área do candidato

```text
resources/views/candidate/maintenance/index.blade.php
resources/views/candidate/maintenance/requests/index.blade.php
resources/views/candidate/maintenance/requests/create.blade.php
resources/views/candidate/maintenance/requests/show.blade.php

resources/views/candidate/inspections/index.blade.php
resources/views/candidate/inspections/show.blade.php

resources/views/candidate/property/technical-history.blade.php
```

## Documentos

Criar templates:

```text
resources/views/inspections/reports/property-inspection-report.blade.php
resources/views/maintenance/reports/maintenance-cost-report.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 18. UX obrigatória no backoffice

## Dashboard de manutenção

Mostrar:

```text
Total de pedidos novos
Pedidos em análise
Pedidos agendados
Pedidos em execução
Pedidos resolvidos
Pedidos rejeitados
Pedidos fechados
Pedidos urgentes
Pedidos de emergência
Tempo médio de resolução
Custo total no período
Custo por categoria
Custo por imóvel
Imóveis com mais ocorrências
Vistorias agendadas
Vistorias concluídas
```

## Lista de pedidos

Mostrar:

```text
Número
Habitação
Arrendatário, se aplicável
Categoria
Urgência
Estado
Data de reporte
Técnico/fornecedor atribuído
Data agendada
Custo acumulado
Ações
```

## Detalhe do pedido

Mostrar:

```text
Número do pedido
Habitação
Contrato
Arrendatário
Origem
Categoria
Urgência indicada
Prioridade técnica
Estado
Descrição
Localização no imóvel
Disponibilidade do arrendatário
Histórico de estados
Atribuições
Intervenções
Fotografias/anexos
Custos
Notas internas
Ações disponíveis
```

## Vistoria

Mostrar:

```text
Número da vistoria
Tipo
Habitação
Contrato
Arrendatário
Inspetor
Data agendada
Estado
Checklist
Condição geral
Fotografias/anexos
Anomalias
Pedidos de manutenção criados
Auto de vistoria
Histórico
```

---

# 19. UX obrigatória para arrendatário

## Criar pedido de manutenção

Campos mínimos:

```text
Categoria
Urgência
Título
Descrição
Localização no imóvel
Disponibilidade para contacto/intervenção
Fotografias/anexos
```

Copy obrigatório:

```text
Descreva o problema com o máximo de detalhe possível. Pode anexar fotografias para ajudar os serviços municipais a avaliar a situação.
```

## Detalhe do pedido

Mostrar:

```text
Número do pedido
Estado
Categoria
Urgência
Descrição
Data de submissão
Data agendada, se existir
Resumo de resolução, se existir
Motivo de rejeição, se existir
Anexos visíveis
Histórico simplificado
```

## Vistorias

Mostrar apenas vistorias próprias e autorizadas:

```text
Tipo de vistoria
Data
Estado
Resumo
Auto disponível, se emitido
```

Copy obrigatório:

```text
As vistorias associadas à sua habitação ficam disponíveis nesta área quando forem emitidas pelos serviços municipais.
```

---

# 20. Regras de manutenção

Pedidos de manutenção devem:

```text
Ter número único
Estar associados a habitação
Estar associados a contrato ativo quando criados por arrendatário
Ter categoria, se possível
Ter urgência
Ter descrição
Permitir anexos privados
Ter histórico de estados
Permitir atribuição a técnico ou fornecedor
Permitir custos
Atualizar histórico técnico do imóvel
```

Rejeição deve exigir:

```text
Motivo
Utilizador responsável
Data
```

Resolução deve exigir:

```text
Resumo da resolução
Utilizador responsável
Data
```

Fecho deve preservar todo o histórico.

---

# 21. Regras de vistorias

Vistorias devem:

```text
Ter número único
Ter tipo
Estar associadas a habitação
Estar associadas a contrato quando aplicável
Ter data agendada, se aplicável
Ter inspetor
Ter checklist
Permitir fotografias/anexos
Permitir observações por item
Permitir condição geral
Permitir recomendações
Gerar auto de vistoria
Atualizar histórico técnico do imóvel
```

Tipos obrigatórios:

```text
Inicial
Periódica
Final
Extraordinária
```

Auto de vistoria deve conter:

```text
Número da vistoria
Tipo
Habitação
Contrato, se aplicável
Data
Inspetor
Condição geral
Itens avaliados
Observações
Fotografias/anexos referenciados
Recomendações
Assinaturas ou validações internas, se aplicável
```

---

# 22. Regras de custos

Custos devem:

```text
Estar associados a pedido ou intervenção
Ter tipo
Ter descrição
Ter valor
Ter moeda
Ter estado
Poder estar associados a fornecedor
Poder ter referência de fatura
Ser visíveis apenas no backoffice, salvo permissão
Alimentar relatórios de custos
Atualizar histórico técnico do imóvel
```

Não criar pagamento ao fornecedor.

Não integrar contabilidade.

---

# 23. Histórico técnico do imóvel

Cada habitação deve ter histórico técnico consolidado com eventos de:

```text
Pedidos de manutenção
Mudanças de estado relevantes
Intervenções concluídas
Custos registados
Vistorias criadas
Vistorias concluídas
Autos emitidos
Contratos iniciados, se aplicável
Contratos terminados, se aplicável
```

A página de histórico técnico deve permitir filtrar por:

```text
Tipo de evento
Período
Pedido de manutenção
Vistoria
Contrato
Custo
```

---

# 24. Integração com contratos

Se existir `LeaseContract`:

```text
Pedido criado por arrendatário deve associar contrato ativo.
Vistoria inicial pode associar contrato no início.
Vistoria final pode associar contrato no fim.
Histórico técnico deve poder ser filtrado por contrato.
```

Não alterar estados contratuais nesta sprint, salvo criação de eventos históricos informativos.

---

# 25. Integração com documentos

Se Sprint 6 existir:

```text
Anexos podem usar DocumentSubmission.
Autos de vistoria podem ser registados como documentos internos.
Downloads devem respeitar policies documentais.
```

Se Sprint 6 não existir:

```text
Guardar anexos em storage privado.
Criar controllers seguros de download.
Não usar storage público.
```

---

# 26. Integração com notificações

Se existir `OfficialNotification`, usar esse modelo para:

```text
maintenance_request_created
maintenance_request_under_review
maintenance_request_scheduled
maintenance_request_in_progress
maintenance_request_resolved
maintenance_request_rejected
maintenance_request_closed
inspection_scheduled
inspection_completed
inspection_report_available
```

Se não existir:

```text
Criar registo interno equivalente ou documentar pendência.
Não enviar email/SMS real.
```

Não marcar notificação como `sent` sem envio real.

---

# 27. Auditoria

Se existir auditoria, auditar:

```text
Criação de pedido de manutenção
Alteração de urgência/prioridade
Mudança de estado
Atribuição a técnico
Atribuição a fornecedor
Criação de intervenção
Conclusão de intervenção
Upload de anexo
Download de anexo
Registo de custo
Aprovação/rejeição de custo
Criação de vistoria
Conclusão de vistoria
Validação de vistoria
Geração de auto
Download de auto
Criação de evento histórico
```

Não criar auditoria paralela se já existir sistema de auditoria.

Se não existir auditoria, documentar pendência.

Não guardar dados sensíveis excessivos nos logs.

---

# 28. RGPD e segurança

Regras obrigatórias:

```text
Pedidos de manutenção podem conter dados pessoais e fotografias privadas.
Fotografias devem ficar em storage privado.
Autos de vistoria devem ficar em storage privado.
Arrendatário só vê pedidos próprios.
Arrendatário só vê anexos autorizados.
Fornecedor só vê pedidos atribuídos.
Backoffice exige permissões.
Auditor não altera dados.
Não expor morada completa em URLs públicas.
Não expor storage_path.
Não colocar NIF, email ou nome em nomes de ficheiro.
Não permitir mass assignment de status.
Não permitir mass assignment de custos.
Não permitir download sem policy.
```

---

# 29. Seeders e factories

Criar factories:

```text
MaintenanceCategoryFactory
MaintenanceSupplierFactory
MaintenanceRequestFactory
MaintenanceRequestStatusHistoryFactory
MaintenanceAssignmentFactory
MaintenanceInterventionFactory
MaintenanceAttachmentFactory
MaintenanceCostFactory

InspectionChecklistTemplateFactory
InspectionChecklistTemplateItemFactory
PropertyInspectionFactory
PropertyInspectionItemFactory
PropertyInspectionAttachmentFactory
PropertyInspectionReportFactory

PropertyHistoryEventFactory
```

Criar seeders opcionais:

```text
MaintenanceCategorySeeder
InspectionChecklistTemplateSeeder
MaintenanceDemoSeeder
InspectionDemoSeeder
```

Usar apenas dados fictícios.

Não usar dados pessoais reais.

Autos e relatórios demo devem conter aviso:

```text
DOCUMENTO DEMO — SUJEITO A VALIDAÇÃO MUNICIPAL
```

---

# 30. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text
guest_cannot_access_maintenance_area
tenant_can_create_maintenance_request_for_own_contract
tenant_cannot_create_maintenance_request_for_other_contract
tenant_can_view_own_maintenance_request
tenant_cannot_view_other_tenant_maintenance_request
tenant_cannot_access_backoffice_maintenance
technician_can_access_backoffice_maintenance_if_authorized
supplier_can_only_view_assigned_requests_if_supplier_access_exists
auditor_can_view_maintenance_without_editing
```

## Pedidos de manutenção

```text
maintenance_request_requires_title_and_description
maintenance_request_generates_unique_number
maintenance_request_starts_with_new_status
maintenance_request_can_be_reviewed
maintenance_request_can_be_scheduled
maintenance_request_can_be_started
maintenance_request_can_be_resolved
maintenance_request_rejection_requires_reason
maintenance_request_can_be_closed_after_resolution
maintenance_status_change_creates_history
closed_maintenance_request_cannot_be_edited_by_tenant
```

## Atribuições

```text
maintenance_request_can_be_assigned_to_internal_technician
maintenance_request_can_be_assigned_to_supplier
assignment_requires_user_or_supplier
only_one_active_assignment_exists_when_configured
assignment_creates_notification_record_if_available
```

## Intervenções

```text
intervention_can_be_created_for_request
intervention_can_be_scheduled
intervention_can_be_completed
completed_intervention_updates_request_when_configured
completed_intervention_creates_property_history_event
```

## Anexos

```text
tenant_can_upload_attachment_to_own_request
attachment_is_stored_privately
attachment_download_requires_authorization
tenant_cannot_download_internal_attachment
backoffice_can_download_authorized_attachment
attachment_path_is_not_exposed
```

## Custos

```text
maintenance_cost_can_be_registered
maintenance_cost_requires_amount
maintenance_cost_can_be_approved
maintenance_cost_can_be_rejected_with_reason
maintenance_cost_is_not_visible_to_tenant_by_default
maintenance_cost_updates_property_history
```

## Vistorias

```text
inspection_can_be_created_for_housing_unit
inspection_generates_unique_number
inspection_can_be_scheduled
inspection_can_be_started
inspection_can_be_completed
inspection_completion_requires_summary
inspection_can_be_validated
inspection_can_generate_report
inspection_report_is_stored_privately
inspection_report_download_requires_authorization
inspection_updates_property_history
```

## Checklist

```text
inspection_template_can_be_created
inspection_template_items_can_be_created
inspection_created_from_template_generates_items
inspection_item_can_mark_requires_maintenance
inspection_item_requiring_maintenance_can_create_request
```

## Histórico técnico

```text
property_history_records_maintenance_request_created
property_history_records_intervention_completed
property_history_records_maintenance_cost
property_history_records_inspection_completed
property_history_can_be_filtered_by_housing_unit
tenant_only_sees_authorized_history_events
```

## Indicadores

```text
maintenance_dashboard_counts_requests_by_status
maintenance_dashboard_counts_requests_by_urgency
maintenance_dashboard_calculates_average_resolution_time
maintenance_cost_report_groups_costs_by_property
maintenance_cost_report_groups_costs_by_category
```

## Segurança

```text
tenant_cannot_mass_assign_maintenance_status
tenant_cannot_mass_assign_technical_priority
tenant_cannot_register_maintenance_cost
tenant_cannot_validate_inspection
tenant_cannot_generate_inspection_report
tenant_cannot_view_supplier_internal_notes
attachment_file_name_does_not_contain_nif
attachment_file_name_does_not_contain_email
attachment_file_is_not_publicly_accessible
inspection_report_file_is_not_publicly_accessible
```

## Auditoria, se existir

```text
creating_maintenance_request_generates_audit_log
changing_maintenance_status_generates_audit_log
assigning_maintenance_request_generates_audit_log
uploading_maintenance_attachment_generates_audit_log
registering_maintenance_cost_generates_audit_log
creating_inspection_generates_audit_log
generating_inspection_report_generates_audit_log
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

Atualizar, se existirem:

```text
docs/backlog/sprint-15-manutencao-vistorias-gestao-imovel.md
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
Pendências para Sprint 16
Limitações de notificações
Limitações de geração PDF
Limitações de fornecedores
Regras de privacidade aplicadas
```

---

# 33. Critérios de aceitação

A Sprint 15 está concluída quando:

```text
O arrendatário consegue criar pedido de manutenção
O pedido fica associado à sua habitação/contrato
O pedido tem número único
O pedido tem categoria, urgência e descrição
O pedido aceita fotografias/anexos em storage privado
O município consegue consultar pedidos
O município consegue classificar urgência técnica
O município consegue atribuir pedido a técnico ou fornecedor
O município consegue agendar intervenção
O município consegue registar intervenção
O município consegue resolver, rejeitar e fechar pedido
Mudanças de estado ficam registadas
Custos de intervenção ficam registados
Custos alimentam relatórios
O município consegue criar vistoria inicial
O município consegue criar vistoria periódica
O município consegue criar vistoria final
O município consegue criar vistoria extraordinária
A vistoria contém checklist
A vistoria contém fotografias/anexos
A vistoria gera auto de vistoria
O auto fica em storage privado
Cada imóvel tem histórico técnico consolidado
O município consegue consultar indicadores de manutenção
O município consegue consultar relatórios de custos
O arrendatário só vê dados próprios e autorizados
Backoffice exige permissões
Auditoria é usada se existir
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foi implementada faturação de fornecedores
Não foi implementada contratação pública
Não foi implementado pagamento a fornecedores
Não foi implementada integração externa não autorizada
```

---

# 34. Resposta final esperada do Codex

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
19. Limitações de notificações
20. Limitações de geração PDF
21. Limitações de fornecedores
22. Regras de privacidade implementadas
23. Confirmação de que não foram implementadas funcionalidades fora de âmbito
24. Recomendação objetiva para avançar ou não para Sprint 16
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 35. Definition of Done

A Sprint 15 só está concluída quando a plataforma permitir gerir operacionalmente a manutenção e as vistorias das habitações contratualizadas, com pedidos do arrendatário, estados controlados, atribuição técnica, custos, anexos privados, autos de vistoria, indicadores e histórico técnico completo por imóvel.

O resultado deve permitir que a Sprint 16 trate comunicações, notificações e prazos transversais com base em eventos reais de manutenção, vistorias, contratos, finanças e procedimento administrativo.

Fim da Sprint 15.

---

# Estado de execução — 2026-06-14

Implementado:

- pedidos de manutenção por arrendatário autenticado, associados a contrato ativo próprio;
- categorias, fornecedores operacionais, pedidos, histórico de estados, atribuições, intervenções, anexos privados e custos;
- dashboard de manutenção, indicadores e relatório de custos;
- vistorias por habitação/contrato com templates de checklist, itens, anexos privados, validação e auto HTML privado;
- histórico técnico consolidado por imóvel, com visibilidade controlada ao arrendatário;
- navegação backoffice/candidato, Form Requests, policies, services, factories, seeders e teste específico.

Tabelas criadas:

`maintenance_categories`, `maintenance_suppliers`, `maintenance_request_status_histories`, `maintenance_assignments`, `maintenance_interventions`, `maintenance_attachments`, `maintenance_costs`, `inspection_checklist_templates`, `inspection_checklist_template_items`, `property_inspections`, `property_inspection_items`, `property_inspection_attachments`, `property_inspection_reports`, `property_history_events`.

Tabelas adaptadas:

`maintenance_requests`.

Problemas encontrados:

- durante o teste focado, a criação de vistoria falhou porque `inspection_number` estava protegido contra mass assignment e era enviado por `create()`;
- correção aplicada: `PropertyInspectionService` instancia o model com campos atribuíveis e preenche `inspection_number` e `status` com `forceFill()` antes de guardar.

Limitações documentadas:

- autos são HTML privados; PDF real fica pendente por ausência de biblioteca/infraestrutura;
- notificações são apenas internas/in-app, sem email/SMS/carta registada/prova externa;
- fornecedor é registo operacional para atribuição, sem portal, faturação, contratação pública ou pagamento;
- custos são administrativos/operacionais e não substituem fatura ou ordem de pagamento.

Pendências para Sprint 16:

- templates oficiais e prova de comunicação externa para eventos de manutenção/vistoria;
- validação jurídica dos textos de notificação, estados e prazos;
- regras finais de retenção e minimização de anexos/fotografias a consolidar na Sprint 18.

Comandos executados:

- `php artisan migrate`: sem migrations pendentes após execução inicial da migration da sprint;
- `php artisan route:list`: passou, com 668 rotas;
- `php artisan test --filter=Sprint15MaintenanceInspectionTest`: passou com 4 testes/43 asserções;
- `php artisan test`: passou com 130 testes/798 asserções;
- `npm run build`: passou com Vite;
- `./vendor/bin/pint`: executado e aplicou formatação;
- `./vendor/bin/pint --test`: passou;
- `./vendor/bin/phpstan analyse`: não executado porque `vendor/bin/phpstan` não existe;
- `./vendor/bin/psalm`: não executado porque `vendor/bin/psalm` não existe.
