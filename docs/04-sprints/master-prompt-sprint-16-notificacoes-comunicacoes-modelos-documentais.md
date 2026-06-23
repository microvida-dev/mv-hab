# MASTER PROMPT — EXECUÇÃO DA SPRINT 16: NOTIFICAÇÕES, COMUNICAÇÕES E MODELOS DOCUMENTAIS

Atua como arquiteto sénior Laravel, tech lead e product engineer.

O objetivo desta execução é implementar exclusivamente a:

```text id="l2zt7h"
Sprint 16 — Notificações, Comunicações e Modelos Documentais
```

Esta sprint pertence à camada transversal de comunicações oficiais da plataforma municipal de Arrendamento Acessível.

A Sprint 16 deve centralizar todas as notificações, comunicações, templates, comprovativos e modelos documentais usados nos vários módulos da plataforma, evitando que cada módulo implemente comunicações próprias, duplicadas, sem histórico ou sem auditabilidade.

---

# 1. Regra principal

Executa apenas a Sprint 16.

Não avances para Sprint 17, Sprint 18, Sprint 19 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash id="77ixkp"
git branch --show-current
```

Não interromper a execução por causa da branch atual.

---

# 2. Ficheiro principal da sprint

Usa como referência principal:

```text id="2yfb47"
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 16.

Não improvisar uma implementação sem o ficheiro de sprint.

---

# 3. Documentação obrigatória a ler antes de implementar

Antes de alterar código, lê, se existirem:

```text id="j2tizr"
docs/architecture/technical-architecture.md
docs/architecture/data-model-overview.md
docs/product/product-vision.md
docs/product/functional-requirements.md
docs/product/user-roles.md
docs/product/process-workflows.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/backlog/roadmap.md

docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
docs/backlog/sprint-11-listas-provisorias-reclamacoes-audiencia.md
docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
docs/backlog/sprint-14-pagamentos-incumprimentos-revisao-renda.md
docs/backlog/sprint-15-manutencao-vistorias-gestao-imovel.md
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md

docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

---

# 4. Inspeção inicial obrigatória

Antes de implementar, inspeciona o repositório e identifica:

```text id="y8keso"
Versão do Laravel
Versão do PHP
Sistema de autenticação
Sistema de roles/permissões
Stack frontend
Sistema de filas/queues
Sistema de mail configurado
Sistema de notificações Laravel existente
Sistema de SMS existente, se existir
Sistema de storage privado
Sistema documental existente
Sistema de PDF/document generation, se existir
Sistema de auditoria, se existir

Modelo User
Modelo Municipality, se existir
Modelo Program
Modelo Contest
Modelo AdhesionRegistration
Modelo Application
Modelo DocumentSubmission
Modelo AdministrativeProcess
Modelo CorrectionRequest
Modelo PublicationList
Modelo Complaint
Modelo Allocation
Modelo LeaseContract ou Contract
Modelo RentInstallment ou Payment
Modelo DefaultNotice
Modelo MaintenanceRequest
Modelo OfficialNotification, se existir
Modelo CommunicationLog, se existir
Modelo DocumentTemplate, se existir
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text id="exbc8l"
Notification
OfficialNotification
NotificationTemplate
NotificationPreference
CommunicationLog
CommunicationDelivery
CommunicationReceipt
CommunicationAttempt
MessageTemplate
MailTemplate
SmsTemplate
DocumentTemplate
DocumentTemplateVersion
GeneratedDocument
TemplateVariable
```

reaproveitar ou adaptar com compatibilidade.

Não apagar comunicações existentes.

Não apagar notificações existentes.

Não apagar templates existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY, chaves SMTP, tokens SMS ou credenciais.

---

# 5. Dependências obrigatórias

Esta sprint depende obrigatoriamente de:

```text id="r7yt2h"
Sistema de utilizadores
Sistema de autenticação
Área pessoal do candidato
Backoffice
```

Depende preferencialmente de:

```text id="5slr9v"
Sprint 4 — Registo de Adesão e Área Pessoal
Sprint 6 — Gestão Documental Avançada
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 11 — Listas Provisórias, Reclamações e Audiência
Sprint 12 — Atribuição de Habitações
Sprint 13 — Cálculo de Renda, Contratos e Caução
Sprint 14 — Pagamentos, Incumprimentos e Revisão de Renda
Sprint 15 — Manutenção, Vistorias e Gestão do Imóvel
Sistema de auditoria
Sistema de storage privado
Sistema de queues
Sistema de PDF
```

## Dependência da área pessoal

Se a área pessoal do candidato não existir, interrompe a componente de consulta pelo candidato e documenta:

```text id="gnfmrt"
A consulta de notificações pelo candidato depende da área pessoal do candidato.
```

## Dependência de e-mail

Se o sistema de e-mail não estiver configurado:

```text id="omvfpv"
Implementar templates, logs, filas e estados de entrega.
Não afirmar que existe envio real.
Marcar entregas de e-mail como pending_configuration quando aplicável.
```

## Dependência de SMS

Se SMS não estiver configurado:

```text id="odcszt"
Implementar templates e abstração de canal SMS.
Não enviar SMS real.
Marcar entregas SMS como disabled ou simulated.
Documentar pendência.
```

---

# 6. Validação jurídica, administrativa e RGPD

Esta sprint trata comunicações oficiais e pode ter impacto em prazos, audiência, reclamações, notificações de decisão e procedimentos administrativos.

Regras obrigatórias:

```text id="9f3n7x"
Comunicações oficiais devem ficar historizadas.
Comunicações críticas devem indicar evento, destinatário, canal, data e estado.
Templates oficiais devem ser versionados.
Alterações a templates devem ficar auditadas.
Comprovativos de envio devem ser guardados.
Erros de envio devem ficar registados.
Reenvios devem criar nova tentativa.
O candidato só vê comunicações próprias.
Técnicos só veem comunicações conforme permissões.
Notificações internas não devem expor dados sensíveis desnecessários.
Comunicações por e-mail/SMS dependem de consentimento, configuração ou base legal aplicável.
Comunicação postal deve ser registada como canal possível, mesmo que não haja integração automática.
Modelos documentais oficiais devem ser minutas parametrizáveis e sujeitos a validação jurídica.
```

Não implementar nesta sprint:

```text id="41qy79"
Notificação eletrónica certificada
ViaCTT
CTT API
Correio registado automático
Assinatura digital qualificada
Carimbo temporal qualificado
Gateway SMS real sem configuração existente
Integração externa de e-mail além do mailer Laravel já configurado
```

Não declarar uma comunicação como entregue se apenas foi colocada em fila.

Não marcar SMS/e-mail como enviado se não houve integração real.

---

# 7. Objetivo da implementação

Implementar um sistema transversal de notificações, comunicações e modelos documentais oficiais.

A plataforma deve permitir que o Município:

```text id="7ed1if"
Crie centro de notificações
Crie notificações internas
Crie notificações ao candidato
Crie templates de e-mail
Crie templates de SMS, se aplicável
Crie templates de notificação in-app
Crie templates de documentos oficiais
Associe templates a eventos críticos
Gere comunicações automáticas por evento
Consulte histórico completo de comunicações
Consulte comprovativos de envio
Consulte tentativas de envio
Consulte erros de envio
Reenvie comunicações quando permitido
Cancele comunicações ainda não enviadas
Arquive comunicações
Controle leitura pelo candidato
Controle tomada de conhecimento
Gere documentos oficiais a partir de modelos parametrizáveis
Mantenha versões de templates
Mantenha histórico de alterações de templates
```

A plataforma deve permitir que o candidato/arrendatário:

```text id="7fq8g9"
Consulte notificações na área pessoal
Leia comunicações oficiais
Assinale tomada de conhecimento quando aplicável
Consulte documentos oficiais disponibilizados
Descarregue documentos autorizados
Veja o estado das comunicações recebidas
Receba alertas internos sobre eventos críticos
```

---

# 8. Âmbito incluído

Implementar:

```text id="m3jzkn"
Centro de notificações
Notificações internas
Notificações ao candidato
Templates configuráveis
Templates de e-mail
Templates de SMS, se aplicável
Templates de notificação in-app
Templates de documentos oficiais
Versionamento de templates
Variáveis de template
Pré-visualização de templates
Validação de variáveis obrigatórias
Eventos críticos de comunicação
Regras de comunicação por evento
Histórico de comunicações
Tentativas de envio
Comprovativos de envio
Registo de leitura
Registo de tomada de conhecimento
Preferências/canais do destinatário
Fila lógica de envio
Reenvio controlado
Cancelamento de comunicação pendente
Arquivo de comunicação
Geração de documentos oficiais a partir de modelos
Download seguro de documentos oficiais
Policies
Form Requests
Services
Events/listeners/jobs
Rotas
Views/páginas
Seeders/factories
Testes
Atualização documental
```

---

# 9. Fora de âmbito

Não implementar nesta sprint:

```text id="l22hj1"
Notificação eletrónica certificada
ViaCTT
CTT API
Correio registado automático
Assinatura digital qualificada
Carimbo temporal qualificado
Gateway SMS real sem configuração existente
Gateway e-mail externo além do mailer Laravel já configurado
Integração com serviços externos sem validação
Envio massivo promocional
Newsletter comercial
Marketing automation
Chat em tempo real
WhatsApp
Push notifications nativas
App mobile nativa
OCR
Editor visual WYSIWYG avançado
Gestão de consentimentos RGPD complexa além das preferências necessárias
```

Podem ser criados pontos de integração para futuras sprints, mas não implementar funcionalidades fora do âmbito.

---

# 10. Fluxo funcional obrigatório

## 10.1 Comunicação por evento

O fluxo base deve ser:

```text id="oqj2ne"
Evento crítico ocorre no sistema
→ Regra de comunicação é resolvida
→ Template ativo é identificado
→ Destinatários são resolvidos
→ Variáveis são preenchidas
→ Comunicação é criada
→ Notificação interna ou in-app é criada
→ Entregas por canal são criadas
→ Envio é executado, simulado ou marcado como pendente conforme configuração
→ Tentativas ficam registadas
→ Comprovativo é gerado quando aplicável
→ Candidato vê a notificação na área pessoal
→ Candidato lê ou toma conhecimento
→ Histórico fica disponível no backoffice
```

## 10.2 Modelo documental

O fluxo de modelo documental deve ser:

```text id="y6fn6z"
Município cria modelo documental
→ Define categoria e evento associado
→ Define variáveis permitidas
→ Publica versão ativa
→ Evento ou técnico gera documento
→ Sistema renderiza conteúdo com variáveis
→ Documento é guardado em storage privado
→ Comunicação pode anexar ou disponibilizar documento
→ Candidato descarrega apenas se autorizado
→ Histórico fica registado
```

---

# 11. Eventos críticos obrigatórios

Criar suporte para os seguintes eventos:

```text id="hll2b9"
adhesion_registration_created
adhesion_registration_completed
application_submitted
document_rejected
correction_requested
correction_response_received
provisional_list_published
complaint_submitted
complaint_decided
hearing_opened
hearing_decided
final_list_published
housing_allocated
housing_allocation_accepted
housing_allocation_refused
contract_issued
contract_signed
contract_active
rent_installment_issued
payment_registered
payment_overdue
default_notice_issued
rent_review_requested
rent_review_applied
maintenance_request_created
maintenance_request_scheduled
maintenance_request_resolved
inspection_scheduled
inspection_report_available
```

Eventos mínimos obrigatórios da Sprint 16:

```text id="mlemtq"
Registo criado
Candidatura submetida
Documento rejeitado
Aperfeiçoamento solicitado
Lista publicada
Reclamação decidida
Habitação atribuída
Contrato emitido
Pagamento em atraso
```

---

# 12. Canais de comunicação

Suportar os seguintes canais:

```text id="k333ew"
in_app
internal
email
sms
postal
document
```

## in_app

Notificação visível na área pessoal do candidato.

## internal

Notificação visível no backoffice para técnicos.

## email

Usar Mail Laravel se estiver configurado.

Se não estiver configurado, criar entrega com estado `pending_configuration`.

## sms

Criar abstração.

Não enviar SMS real sem gateway configurado.

Se não existir gateway SMS, criar entrega com estado `disabled` ou `simulated`.

## postal

Canal manual.

Permitir registo de envio postal manual, referência, data, observações e comprovativo digitalizado.

## document

Documento oficial gerado e disponibilizado na área pessoal ou no backoffice.

---

# 13. Estados obrigatórios

## NotificationStatus

```text id="s1wavs"
draft
queued
published
read
acknowledged
archived
cancelled
expired
```

## CommunicationStatus

```text id="nx1e46"
draft
queued
processing
sent
partially_sent
failed
cancelled
archived
```

## CommunicationDeliveryStatus

```text id="l0r9ce"
pending
queued
processing
sent
delivered
failed
bounced
cancelled
disabled
simulated
pending_configuration
```

## CommunicationAttemptStatus

```text id="asjdm5"
started
success
failed
cancelled
skipped
```

## TemplateStatus

```text id="ecb9hp"
draft
active
inactive
archived
```

## TemplateType

```text id="kgy66y"
in_app
internal
email
sms
postal
document
```

## TemplateVariableType

```text id="ehz5px"
string
number
date
datetime
currency
boolean
url
html
plain_text
```

## DocumentGenerationStatus

```text id="xvfhmy"
draft
generated
issued
cancelled
archived
failed
```

## NotificationPriority

```text id="fv0ysf"
low
normal
high
urgent
critical
```

---

# 14. Modelo de dados a implementar

## 14.1 NotificationTemplate

Criar entidade:

```text id="414ga0"
NotificationTemplate
```

Tabela:

```text id="8fqiwb"
notification_templates
```

Campos mínimos:

```text id="jdtuow"
id
municipality_id nullable
program_id nullable
contest_id nullable

code
name
description
template_type
channel
status
language

subject
title
body
html_body
sms_body

requires_acknowledgement
is_official
is_default

active_version_id nullable

created_by
updated_by

created_at
updated_at
deleted_at
```

Regras:

```text id="fqokpn"
code obrigatório e único por municipality/program/contest/channel.
Templates oficiais devem ser versionados.
Apenas um template ativo por código/canal/contexto.
Não apagar template usado em comunicação; arquivar.
```

---

## 14.2 NotificationTemplateVersion

Criar entidade:

```text id="ib46co"
NotificationTemplateVersion
```

Tabela:

```text id="5wfb0w"
notification_template_versions
```

Campos mínimos:

```text id="sx4ss5"
id
notification_template_id

version_number
status

subject
title
body
html_body
sms_body

variables_schema
change_summary

created_by
approved_by
approved_at
activated_at
archived_at

created_at
updated_at
```

Regras:

```text id="z4a23h"
Comunicações devem guardar o template_version_id usado.
Alteração de template cria nova versão.
Não alterar versões já usadas em comunicações.
```

---

## 14.3 TemplateVariable

Criar entidade:

```text id="sxt4ea"
TemplateVariable
```

Tabela:

```text id="1sn55o"
template_variables
```

Campos mínimos:

```text id="qr5qre"
id
code
name
description
variable_type
source_key
example_value
is_required
is_sensitive
is_active
created_at
updated_at
deleted_at
```

Exemplos:

```text id="h5n4v5"
candidate.name
candidate.email
application.number
contest.title
program.name
document.type
correction.deadline
publication_list.title
complaint.decision
allocation.housing_reference
contract.number
payment.amount
payment.due_date
maintenance.request_number
inspection.number
municipality.name
municipality.email
```

Regras:

```text id="lqso0m"
Variáveis sensíveis não devem ser usadas em SMS por defeito.
Variáveis obrigatórias devem ser validadas antes de enviar.
```

---

## 14.4 NotificationEventRule

Criar entidade:

```text id="6j1tod"
NotificationEventRule
```

Tabela:

```text id="jx8erf"
notification_event_rules
```

Campos mínimos:

```text id="fa5ix2"
id
municipality_id nullable
program_id nullable
contest_id nullable

event_code
name
description
is_active

recipient_type
channel
notification_template_id
requires_acknowledgement
priority

send_immediately
delay_minutes

created_by
updated_by

created_at
updated_at
deleted_at
```

Valores recomendados para `recipient_type`:

```text id="0jiqcf"
candidate
tenant
municipal_technician
jury_member
finance_manager
maintenance_manager
admin
custom_user
external_email
```

Regras:

```text id="px6fqw"
Evento pode gerar múltiplas comunicações por canais diferentes.
Regra inativa não gera comunicação.
Não enviar para destinatário sem contacto válido no canal escolhido.
```

---

## 14.5 OfficialNotification

Criar ou adaptar entidade:

```text id="n7zoso"
OfficialNotification
```

Tabela:

```text id="rfrccr"
official_notifications
```

Campos mínimos:

```text id="fedmeq"
id
notification_number

recipient_user_id
recipient_email
recipient_phone

related_type
related_id

event_code
channel
status
priority

title
body
action_url
requires_acknowledgement

read_at
acknowledged_at
archived_at
cancelled_at
expires_at

created_by
created_at
updated_at
deleted_at
```

Regras:

```text id="s4nsbf"
notification_number obrigatório e único.
Candidato só vê notificações próprias.
Notificação oficial deve ficar associada a CommunicationLog quando aplicável.
```

---

## 14.6 CommunicationLog

Criar entidade:

```text id="vfsgak"
CommunicationLog
```

Tabela:

```text id="tf7oqo"
communication_logs
```

Campos mínimos:

```text id="r3tpt1"
id
communication_number

event_code
status
priority

recipient_user_id
recipient_name
recipient_email
recipient_phone
recipient_address

related_type
related_id

notification_template_id
notification_template_version_id

subject
title
body_snapshot
html_snapshot

is_official
requires_acknowledgement

created_by
queued_at
sent_at
failed_at
cancelled_at
archived_at

failure_reason
internal_notes

created_at
updated_at
deleted_at
```

Regras:

```text id="w4utnt"
communication_number obrigatório e único.
Guardar snapshot do conteúdo enviado.
Não depender do template atual para consultar comunicação antiga.
Não apagar comunicação oficial; arquivar.
```

---

## 14.7 CommunicationDelivery

Criar entidade:

```text id="3e4942"
CommunicationDelivery
```

Tabela:

```text id="29p00n"
communication_deliveries
```

Campos mínimos:

```text id="n4kahb"
id
communication_log_id
official_notification_id nullable

channel
status

destination
provider
provider_message_id
provider_response

queued_at
processing_at
sent_at
delivered_at
failed_at
cancelled_at

failure_reason

created_at
updated_at
deleted_at
```

Regras:

```text id="4qc9qt"
Uma comunicação pode ter várias entregas.
Cada canal tem estado próprio.
Não guardar respostas de provider com dados sensíveis excessivos.
```

---

## 14.8 CommunicationAttempt

Criar entidade:

```text id="8snk7a"
CommunicationAttempt
```

Tabela:

```text id="590fay"
communication_attempts
```

Campos mínimos:

```text id="y1jmx8"
id
communication_delivery_id

attempt_number
status

started_at
finished_at

provider
request_payload_summary
response_payload_summary
error_code
error_message

created_by
created_at
```

Regras:

```text id="he6o5h"
Não guardar payload completo com dados pessoais.
Guardar apenas resumo técnico seguro.
Tentativas são append-only.
```

---

## 14.9 CommunicationReceipt

Criar entidade:

```text id="z7nxxc"
CommunicationReceipt
```

Tabela:

```text id="rn8nvr"
communication_receipts
```

Campos mínimos:

```text id="3c4flq"
id
communication_log_id
communication_delivery_id nullable

receipt_number
receipt_type

storage_disk
storage_path
mime_type
file_size
checksum

generated_by
generated_at

created_at
updated_at
deleted_at
```

Valores recomendados para `receipt_type`:

```text id="2dw3p1"
send_proof
delivery_proof
read_proof
acknowledgement_proof
postal_proof
manual_upload
```

Regras:

```text id="zwitk4"
Comprovativos devem ficar em storage privado.
Não expor storage_path.
Downloads exigem policy.
```

---

## 14.10 NotificationPreference

Criar entidade:

```text id="n5pn0c"
NotificationPreference
```

Tabela:

```text id="hn48sl"
notification_preferences
```

Campos mínimos:

```text id="jzl5tu"
id
user_id

allow_in_app
allow_email
allow_sms
allow_postal

preferred_channel
email_for_notifications
phone_for_notifications
postal_address

consented_at
revoked_at

created_at
updated_at
```

Regras:

```text id="fm5zun"
Não bloquear comunicações obrigatórias por mera preferência quando exista base legal/procedimental.
Preferências devem ser consideradas para canais opcionais.
Mudanças devem ser auditadas se existir auditoria.
```

---

## 14.11 DocumentTemplate

Criar ou adaptar entidade:

```text id="7lx35x"
DocumentTemplate
```

Tabela:

```text id="d8e305"
document_templates
```

Campos mínimos:

```text id="poscak"
id
municipality_id nullable
program_id nullable
contest_id nullable

code
name
description
category
status
language

title
body
html_body
footer
header

is_official
is_default
requires_approval

active_version_id nullable

created_by
updated_by

created_at
updated_at
deleted_at
```

Categorias recomendadas:

```text id="4rzoeb"
application_receipt
document_rejection_notice
correction_request
provisional_list_notice
complaint_decision
hearing_notice
final_list_notice
allocation_notice
contract_cover_letter
payment_default_notice
maintenance_notice
inspection_report
generic_official_notice
```

---

## 14.12 DocumentTemplateVersion

Criar entidade:

```text id="l5pcfb"
DocumentTemplateVersion
```

Tabela:

```text id="k1bh2o"
document_template_versions
```

Campos mínimos:

```text id="pd5bc8"
id
document_template_id

version_number
status

title
body
html_body
header
footer
variables_schema
change_summary

created_by
approved_by
approved_at
activated_at
archived_at

created_at
updated_at
```

---

## 14.13 GeneratedOfficialDocument

Criar entidade:

```text id="jf31d3"
GeneratedOfficialDocument
```

Tabela:

```text id="mbm8rt"
generated_official_documents
```

Campos mínimos:

```text id="5phmig"
id
document_number

document_template_id
document_template_version_id

related_type
related_id

recipient_user_id
recipient_name

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
issued_by
issued_at
cancelled_by
cancelled_at
cancellation_reason

created_at
updated_at
deleted_at
```

Regras:

```text id="nno8vx"
Documento gerado deve guardar snapshot do HTML.
PDF só deve ser gerado se biblioteca existir.
Se não existir PDF, criar HTML imprimível e documentar pendência.
Documentos devem ficar em storage privado.
```

---

# 15. Enums obrigatórios

Criar, se a versão do PHP permitir:

```text id="14hy86"
App\Enums\NotificationStatus
App\Enums\CommunicationStatus
App\Enums\CommunicationDeliveryStatus
App\Enums\CommunicationAttemptStatus
App\Enums\NotificationChannel
App\Enums\NotificationPriority
App\Enums\TemplateStatus
App\Enums\TemplateType
App\Enums\TemplateVariableType
App\Enums\CommunicationReceiptType
App\Enums\DocumentTemplateCategory
App\Enums\DocumentGenerationStatus
App\Enums\NotificationEventCode
App\Enums\NotificationRecipientType
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 16. Relações obrigatórias

## User

Adicionar:

```text id="8pjqmq"
hasMany OfficialNotification as receivedNotifications
hasMany CommunicationLog as communications
hasOne NotificationPreference
hasMany GeneratedOfficialDocument as generatedDocuments
```

## NotificationTemplate

```text id="r2nfq5"
hasMany NotificationTemplateVersion
belongsTo activeVersion
hasMany NotificationEventRule
hasMany CommunicationLog
```

## NotificationTemplateVersion

```text id="0bq2yp"
belongsTo NotificationTemplate
belongsTo User as createdBy
belongsTo User as approvedBy nullable
```

## NotificationEventRule

```text id="sfvlyl"
belongsTo NotificationTemplate
belongsTo Program nullable
belongsTo Contest nullable
belongsTo User as createdBy
belongsTo User as updatedBy nullable
```

## OfficialNotification

```text id="8a6nn3"
belongsTo User as recipient
morphTo related
belongsTo User as createdBy nullable
hasOne CommunicationDelivery nullable
```

## CommunicationLog

```text id="p2atxp"
belongsTo User as recipient nullable
belongsTo NotificationTemplate nullable
belongsTo NotificationTemplateVersion nullable
morphTo related
belongsTo User as createdBy nullable
hasMany CommunicationDelivery
hasMany CommunicationReceipt
```

## CommunicationDelivery

```text id="baiv4d"
belongsTo CommunicationLog
belongsTo OfficialNotification nullable
hasMany CommunicationAttempt
hasMany CommunicationReceipt
```

## CommunicationAttempt

```text id="ejrkl0"
belongsTo CommunicationDelivery
belongsTo User as createdBy nullable
```

## CommunicationReceipt

```text id="a8fy9n"
belongsTo CommunicationLog
belongsTo CommunicationDelivery nullable
belongsTo User as generatedBy nullable
```

## DocumentTemplate

```text id="9qn72w"
hasMany DocumentTemplateVersion
belongsTo activeVersion
hasMany GeneratedOfficialDocument
```

## GeneratedOfficialDocument

```text id="1mryjk"
belongsTo DocumentTemplate
belongsTo DocumentTemplateVersion
morphTo related
belongsTo User as recipient nullable
belongsTo User as generatedBy nullable
belongsTo User as issuedBy nullable
belongsTo User as cancelledBy nullable
```

---

# 17. Services obrigatórios

Criar:

```text id="9ub9ml"
App\Services\Notifications\NotificationEventDispatcher
App\Services\Notifications\NotificationEventRuleResolver
App\Services\Notifications\RecipientResolver
App\Services\Notifications\NotificationTemplateResolver
App\Services\Notifications\TemplateRenderingService
App\Services\Notifications\NotificationCenterService
App\Services\Notifications\OfficialNotificationService
App\Services\Notifications\CommunicationLogService
App\Services\Notifications\CommunicationDeliveryService
App\Services\Notifications\CommunicationAttemptService
App\Services\Notifications\CommunicationReceiptService
App\Services\Notifications\NotificationPreferenceService

App\Services\Notifications\Channels\InAppChannelService
App\Services\Notifications\Channels\InternalChannelService
App\Services\Notifications\Channels\EmailChannelService
App\Services\Notifications\Channels\SmsChannelService
App\Services\Notifications\Channels\PostalChannelService

App\Services\Documents\DocumentTemplateService
App\Services\Documents\DocumentTemplateVersionService
App\Services\Documents\OfficialDocumentGenerationService
App\Services\Documents\OfficialDocumentDownloadService
```

## NotificationEventDispatcher

Responsável por:

```text id="nfzhs1"
Receber evento crítico
Resolver regras ativas
Resolver destinatários
Resolver templates
Criar CommunicationLog
Criar OfficialNotification
Criar CommunicationDelivery por canal
Disparar envio imediato ou fila
Evitar duplicados quando configurado
Registar falhas
```

## NotificationEventRuleResolver

Responsável por:

```text id="2o9j7g"
Resolver regras por event_code
Priorizar regra específica de concurso
Depois regra específica de programa
Depois regra municipal
Depois regra global
Ignorar regras inativas
Validar canal e template ativo
```

## RecipientResolver

Responsável por:

```text id="6twmsx"
Resolver candidato
Resolver arrendatário
Resolver técnico municipal
Resolver júri
Resolver gestor financeiro
Resolver gestor de manutenção
Resolver administradores
Resolver destinatário externo manual
Validar contactos disponíveis
Aplicar preferências quando aplicável
```

## NotificationTemplateResolver

Responsável por:

```text id="r4n34b"
Obter template ativo
Obter versão ativa
Validar compatibilidade de canal
Validar variáveis obrigatórias
Resolver fallback global
```

## TemplateRenderingService

Responsável por:

```text id="gq6g48"
Renderizar subject
Renderizar title
Renderizar body
Renderizar html_body
Renderizar sms_body
Substituir variáveis
Escapar conteúdo quando necessário
Validar variáveis em falta
Bloquear variáveis sensíveis em canais curtos quando configurado
Criar snapshot renderizado
```

Sintaxe recomendada de variáveis:

```text id="7jj4ak"
{{ candidate.name }}
{{ application.number }}
{{ contest.title }}
{{ deadline.date }}
{{ municipality.name }}
```

## NotificationCenterService

Responsável por:

```text id="elv8ho"
Listar notificações do candidato
Listar notificações internas do backoffice
Contar notificações não lidas
Marcar como lida
Marcar tomada de conhecimento
Arquivar notificação
Filtrar por estado, evento, prioridade e data
```

## CommunicationReceiptService

Responsável por:

```text id="iuc82p"
Gerar comprovativo de envio
Gerar comprovativo de disponibilização
Gerar comprovativo de leitura
Gerar comprovativo de tomada de conhecimento
Permitir upload manual de comprovativo postal
Guardar comprovativo em storage privado
Permitir download seguro
```

## EmailChannelService

Responsável por:

```text id="wvhrec"
Verificar se mailer está configurado
Enviar e-mail usando Mail Laravel se disponível
Registar sucesso ou falha
Não afirmar envio se mailer não estiver configurado
Criar tentativa
```

## SmsChannelService

Responsável por:

```text id="40r75k"
Verificar se gateway SMS existe
Se não existir, marcar como disabled ou simulated
Criar abstração para envio futuro
Registar tentativa apenas se houver execução real ou simulação explícita
Não enviar SMS real sem configuração
```

## PostalChannelService

Responsável por:

```text id="ge8vkk"
Criar entrega postal manual
Permitir registar data de envio
Permitir registar referência postal
Permitir anexar comprovativo
Permitir marcar como enviado manualmente
```

## OfficialDocumentGenerationService

Responsável por:

```text id="syljzb"
Gerar documento oficial
Resolver template ativo
Renderizar variáveis
Guardar HTML snapshot
Gerar PDF se infraestrutura existir
Guardar em storage privado
Gerar número de documento
Associar documento a comunicação
Associar documento a processo/candidatura/contrato
```

---

# 18. Events, Listeners e Jobs

Criar eventos ou listeners conforme a arquitetura existente.

Eventos recomendados:

```text id="uym1ql"
AdhesionRegistrationCreated
AdhesionRegistrationCompleted
ApplicationSubmitted
DocumentRejected
CorrectionRequested
CorrectionResponseReceived
PublicationListPublished
ComplaintSubmitted
ComplaintDecided
HearingOpened
HearingDecided
FinalListPublished
HousingAllocated
HousingAllocationAccepted
HousingAllocationRefused
ContractIssued
ContractSigned
PaymentOverdue
DefaultNoticeIssued
MaintenanceRequestCreated
InspectionScheduled
```

Se os eventos já existirem, adicionar listeners.

Listeners recomendados:

```text id="hftk7g"
DispatchNotificationForAdhesionRegistrationCreated
DispatchNotificationForApplicationSubmitted
DispatchNotificationForDocumentRejected
DispatchNotificationForCorrectionRequested
DispatchNotificationForPublicationListPublished
DispatchNotificationForComplaintDecided
DispatchNotificationForHousingAllocated
DispatchNotificationForContractIssued
DispatchNotificationForPaymentOverdue
```

Se o sistema ainda não tiver eventos Laravel formais, criar método service-level:

```php id="g8tmvj"
NotificationEventDispatcher::dispatch(string $eventCode, Model $related, array $context = []);
```

Criar jobs se o projeto usar filas:

```text id="jw2fxt"
SendCommunicationDeliveryJob
GenerateCommunicationReceiptJob
GenerateOfficialDocumentJob
ProcessPendingCommunicationsJob
```

Se queues não estiverem configuradas, permitir fallback síncrono controlado e documentar.

Nunca assumir que queue worker está ativo.

---

# 19. Controllers obrigatórios

Criar controllers conforme a arquitetura existente.

## Backoffice

Namespace recomendado:

```text id="cmxeii"
App\Http\Controllers\Backoffice
```

Controllers:

```text id="wqwtas"
Backoffice\NotificationCenterController
Backoffice\NotificationTemplateController
Backoffice\NotificationTemplateVersionController
Backoffice\TemplateVariableController
Backoffice\NotificationEventRuleController
Backoffice\CommunicationLogController
Backoffice\CommunicationDeliveryController
Backoffice\CommunicationReceiptController
Backoffice\DocumentTemplateController
Backoffice\DocumentTemplateVersionController
Backoffice\GeneratedOfficialDocumentController
Backoffice\NotificationPreferenceController
```

## Área do candidato / arrendatário

Namespace recomendado:

```text id="jsl8n5"
App\Http\Controllers\Candidate
```

Controllers:

```text id="okpfqd"
Candidate\NotificationCenterController
Candidate\OfficialNotificationController
Candidate\CommunicationController
Candidate\GeneratedOfficialDocumentController
Candidate\NotificationPreferenceController
```

---

# 20. Form Requests obrigatórios

Criar:

```text id="1b3o4s"
StoreNotificationTemplateRequest
UpdateNotificationTemplateRequest
StoreNotificationTemplateVersionRequest
ApproveNotificationTemplateVersionRequest
ActivateNotificationTemplateVersionRequest
ArchiveNotificationTemplateRequest

StoreTemplateVariableRequest
UpdateTemplateVariableRequest

StoreNotificationEventRuleRequest
UpdateNotificationEventRuleRequest

StoreCommunicationLogRequest
CancelCommunicationLogRequest
ResendCommunicationDeliveryRequest
RegisterPostalDeliveryRequest
UploadCommunicationReceiptRequest

StoreDocumentTemplateRequest
UpdateDocumentTemplateRequest
StoreDocumentTemplateVersionRequest
ApproveDocumentTemplateVersionRequest
ActivateDocumentTemplateVersionRequest
GenerateOfficialDocumentRequest
CancelGeneratedOfficialDocumentRequest

UpdateNotificationPreferenceRequest
MarkNotificationReadRequest
AcknowledgeNotificationRequest
```

## StoreNotificationTemplateRequest

```text id="de44t4"
code required|string|max:150
name required|string|max:255
description nullable|string|max:3000
template_type required|string|max:100
channel required|string|max:100
status nullable|string|max:100
language required|string|max:10
subject nullable|string|max:255
title nullable|string|max:255
body required|string|min:1|max:20000
html_body nullable|string|max:50000
sms_body nullable|string|max:1000
requires_acknowledgement boolean
is_official boolean
is_default boolean
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
```

## StoreNotificationEventRuleRequest

```text id="w2rrbg"
event_code required|string|max:150
name required|string|max:255
description nullable|string|max:3000
recipient_type required|string|max:100
channel required|string|max:100
notification_template_id required|exists:notification_templates,id
requires_acknowledgement boolean
priority required|string|max:100
send_immediately boolean
delay_minutes nullable|integer|min:0|max:10080
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
is_active boolean
```

## StoreCommunicationLogRequest

```text id="9r5u5o"
recipient_user_id nullable|exists:users,id
recipient_email nullable|email|max:255
recipient_phone nullable|string|max:50
event_code required|string|max:150
channel required|string|max:100
subject nullable|string|max:255
title required|string|max:255
body required|string|min:1|max:20000
priority required|string|max:100
requires_acknowledgement boolean
related_type nullable|string|max:255
related_id nullable|integer
```

## RegisterPostalDeliveryRequest

```text id="any0c3"
communication_delivery_id required|exists:communication_deliveries,id
sent_at required|date
postal_reference nullable|string|max:255
notes nullable|string|max:3000
receipt_file nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240
```

## StoreDocumentTemplateRequest

```text id="dj8zcu"
code required|string|max:150
name required|string|max:255
description nullable|string|max:3000
category required|string|max:150
status nullable|string|max:100
language required|string|max:10
title required|string|max:255
body required|string|min:1|max:50000
html_body nullable|string|max:100000
header nullable|string|max:10000
footer nullable|string|max:10000
is_official boolean
is_default boolean
requires_approval boolean
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
```

## GenerateOfficialDocumentRequest

```text id="kkz86t"
document_template_id required|exists:document_templates,id
related_type nullable|string|max:255
related_id nullable|integer
recipient_user_id nullable|exists:users,id
variables nullable|array
issue_immediately boolean
```

## UpdateNotificationPreferenceRequest

```text id="3t5hqs"
allow_in_app boolean
allow_email boolean
allow_sms boolean
allow_postal boolean
preferred_channel nullable|string|max:100
email_for_notifications nullable|email|max:255
phone_for_notifications nullable|string|max:50
postal_address nullable|string|max:1000
```

---

# 21. Policies obrigatórias

Criar:

```text id="45qi4r"
NotificationTemplatePolicy
NotificationTemplateVersionPolicy
TemplateVariablePolicy
NotificationEventRulePolicy
OfficialNotificationPolicy
CommunicationLogPolicy
CommunicationDeliveryPolicy
CommunicationAttemptPolicy
CommunicationReceiptPolicy
NotificationPreferencePolicy
DocumentTemplatePolicy
DocumentTemplateVersionPolicy
GeneratedOfficialDocumentPolicy
```

## Regras para candidato/arrendatário

```text id="0sow2j"
Só vê notificações próprias.
Só marca como lidas notificações próprias.
Só assinala tomada de conhecimento de notificações próprias.
Só vê comunicações próprias.
Só descarrega documentos próprios e autorizados.
Só altera preferências próprias.
Não cria templates.
Não edita templates.
Não consulta comunicações de terceiros.
Não vê logs técnicos de envio.
Não vê provider_response.
```

## Regras para técnico municipal

```text id="5ndhcy"
Pode consultar comunicações dos processos que gere.
Pode criar comunicação manual se autorizado.
Pode gerar documentos oficiais se autorizado.
Pode consultar templates ativos.
Não edita templates oficiais se não tiver permissão.
Não vê dados técnicos sensíveis de provider salvo permissão.
```

## Regras para admin

```text id="7tfma9"
Pode gerir templates.
Pode gerir regras de evento.
Pode consultar histórico global.
Pode reenviar comunicações.
Pode cancelar comunicações pendentes.
Pode gerir modelos documentais.
Pode ativar versões.
```

## Regras para auditor

```text id="a2eycn"
Pode consultar histórico de comunicações.
Pode consultar comprovativos.
Pode consultar versões de templates.
Não pode enviar, reenviar, cancelar ou editar comunicações.
Não pode editar templates.
```

---

# 22. Rotas obrigatórias

## Backoffice

Criar, preferencialmente:

```text id="0qec3h"
GET /backoffice/communications
GET /backoffice/communications/dashboard

GET /backoffice/communications/templates
GET /backoffice/communications/templates/create
POST /backoffice/communications/templates
GET /backoffice/communications/templates/{notificationTemplate}
GET /backoffice/communications/templates/{notificationTemplate}/edit
PUT/PATCH /backoffice/communications/templates/{notificationTemplate}
POST /backoffice/communications/templates/{notificationTemplate}/archive

POST /backoffice/communications/templates/{notificationTemplate}/versions
GET /backoffice/communications/template-versions/{notificationTemplateVersion}
POST /backoffice/communications/template-versions/{notificationTemplateVersion}/approve
POST /backoffice/communications/template-versions/{notificationTemplateVersion}/activate
POST /backoffice/communications/template-versions/{notificationTemplateVersion}/archive
POST /backoffice/communications/templates/{notificationTemplate}/preview

GET /backoffice/communications/variables
POST /backoffice/communications/variables
PUT/PATCH /backoffice/communications/variables/{templateVariable}

GET /backoffice/communications/event-rules
GET /backoffice/communications/event-rules/create
POST /backoffice/communications/event-rules
GET /backoffice/communications/event-rules/{notificationEventRule}/edit
PUT/PATCH /backoffice/communications/event-rules/{notificationEventRule}
POST /backoffice/communications/event-rules/{notificationEventRule}/activate
POST /backoffice/communications/event-rules/{notificationEventRule}/deactivate

GET /backoffice/communications/logs
GET /backoffice/communications/logs/{communicationLog}
POST /backoffice/communications/logs
POST /backoffice/communications/logs/{communicationLog}/cancel
POST /backoffice/communications/deliveries/{communicationDelivery}/resend
POST /backoffice/communications/deliveries/{communicationDelivery}/register-postal
GET /backoffice/communications/receipts/{communicationReceipt}/download

GET /backoffice/document-templates
GET /backoffice/document-templates/create
POST /backoffice/document-templates
GET /backoffice/document-templates/{documentTemplate}
GET /backoffice/document-templates/{documentTemplate}/edit
PUT/PATCH /backoffice/document-templates/{documentTemplate}
POST /backoffice/document-templates/{documentTemplate}/archive
POST /backoffice/document-templates/{documentTemplate}/versions
GET /backoffice/document-template-versions/{documentTemplateVersion}
POST /backoffice/document-template-versions/{documentTemplateVersion}/approve
POST /backoffice/document-template-versions/{documentTemplateVersion}/activate
POST /backoffice/document-templates/{documentTemplate}/preview

GET /backoffice/official-documents
POST /backoffice/official-documents/generate
GET /backoffice/official-documents/{generatedOfficialDocument}
GET /backoffice/official-documents/{generatedOfficialDocument}/download
POST /backoffice/official-documents/{generatedOfficialDocument}/issue
POST /backoffice/official-documents/{generatedOfficialDocument}/cancel
```

## Área do candidato / arrendatário

Criar, preferencialmente:

```text id="zd8x76"
GET /area-candidato/notificacoes
GET /area-candidato/notificacoes/{officialNotification}
POST /area-candidato/notificacoes/{officialNotification}/lida
POST /area-candidato/notificacoes/{officialNotification}/tomar-conhecimento
POST /area-candidato/notificacoes/{officialNotification}/arquivar

GET /area-candidato/comunicacoes
GET /area-candidato/comunicacoes/{communicationLog}

GET /area-candidato/documentos-oficiais
GET /area-candidato/documentos-oficiais/{generatedOfficialDocument}
GET /area-candidato/documentos-oficiais/{generatedOfficialDocument}/download

GET /area-candidato/preferencias-notificacao
PUT/PATCH /area-candidato/preferencias-notificacao
```

---

# 23. Views / páginas obrigatórias

Se o projeto usa Blade, criar:

## Backoffice

```text id="dokjyt"
resources/views/backoffice/communications/dashboard.blade.php

resources/views/backoffice/communications/templates/index.blade.php
resources/views/backoffice/communications/templates/create.blade.php
resources/views/backoffice/communications/templates/edit.blade.php
resources/views/backoffice/communications/templates/show.blade.php
resources/views/backoffice/communications/templates/preview.blade.php

resources/views/backoffice/communications/template-versions/show.blade.php

resources/views/backoffice/communications/event-rules/index.blade.php
resources/views/backoffice/communications/event-rules/create.blade.php
resources/views/backoffice/communications/event-rules/edit.blade.php

resources/views/backoffice/communications/logs/index.blade.php
resources/views/backoffice/communications/logs/show.blade.php

resources/views/backoffice/document-templates/index.blade.php
resources/views/backoffice/document-templates/create.blade.php
resources/views/backoffice/document-templates/edit.blade.php
resources/views/backoffice/document-templates/show.blade.php
resources/views/backoffice/document-templates/preview.blade.php

resources/views/backoffice/official-documents/index.blade.php
resources/views/backoffice/official-documents/show.blade.php
```

## Área do candidato

```text id="zsek2m"
resources/views/candidate/notifications/index.blade.php
resources/views/candidate/notifications/show.blade.php
resources/views/candidate/communications/index.blade.php
resources/views/candidate/communications/show.blade.php
resources/views/candidate/official-documents/index.blade.php
resources/views/candidate/official-documents/show.blade.php
resources/views/candidate/notification-preferences/edit.blade.php
```

## Templates renderizáveis

```text id="qozpuu"
resources/views/communications/email/generic.blade.php
resources/views/communications/receipts/send-proof.blade.php
resources/views/documents/official/generic-document.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 24. UX obrigatória no backoffice

## Dashboard de comunicações

Mostrar:

```text id="vlu5sc"
Total de comunicações hoje
Comunicações pendentes
Comunicações enviadas
Comunicações falhadas
Comunicações por canal
Comunicações por evento
Notificações por ler
Notificações com tomada de conhecimento pendente
Templates ativos
Templates em rascunho
Modelos documentais ativos
Falhas recentes
```

## Lista de comunicações

Mostrar:

```text id="jrtan9"
Número
Evento
Destinatário
Canal
Estado
Prioridade
Processo relacionado
Template usado
Data de criação
Data de envio
Última tentativa
Ações
```

## Detalhe de comunicação

Mostrar:

```text id="5mtgbd"
Número da comunicação
Evento
Destinatário
Contactos usados
Assunto
Conteúdo enviado
Template e versão
Canal
Estado global
Entregas por canal
Tentativas
Erros
Comprovativos
Documento associado
Processo relacionado
Auditoria, se existir
```

## Gestão de templates

Mostrar:

```text id="b4yzjm"
Código
Nome
Canal
Tipo
Estado
Programa/concurso
Versão ativa
Variáveis usadas
Última atualização
Ações
```

## Editor de template

Deve permitir:

```text id="013sud"
Editar subject/title/body
Editar SMS curto
Editar HTML simples
Ver variáveis disponíveis
Pré-visualizar com dados demo
Validar variáveis obrigatórias
Criar nova versão
Ativar versão
Arquivar template
```

---

# 25. UX obrigatória para candidato

## Centro de notificações

Mostrar:

```text id="ddka5z"
Notificações por ler
Notificações lidas
Notificações arquivadas
Prioridade
Data
Assunto
Processo relacionado
Ação necessária
Prazo, se existir
```

Copy obrigatório:

```text id="edzv5w"
Consulte regularmente as notificações da plataforma para acompanhar o estado dos seus processos e responder dentro dos prazos indicados.
```

## Detalhe da notificação

Mostrar:

```text id="cvlicz"
Assunto
Data
Conteúdo
Processo relacionado
Prazo, se existir
Documentos anexos, se existirem
Botão marcar como lida
Botão tomar conhecimento, se aplicável
```

Copy obrigatório quando exige tomada de conhecimento:

```text id="1e6uo6"
Ao assinalar tomada de conhecimento, confirma que leu esta comunicação na plataforma.
```

## Preferências de notificação

Mostrar:

```text id="nw84rc"
E-mail para notificações
Telemóvel para SMS, se aplicável
Morada postal, se aplicável
Canais preferenciais
Canais obrigatórios informativos
Data do consentimento ou atualização
```

Copy obrigatório:

```text id="zdt77b"
Algumas comunicações oficiais podem ser enviadas por canais obrigatórios definidos pelo Município ou pela lei aplicável, independentemente das preferências opcionais.
```

---

# 26. Templates mínimos a criar por seed

Criar templates iniciais para:

```text id="o5fxad"
registration_created_in_app
registration_created_email
application_submitted_in_app
application_submitted_email
document_rejected_in_app
document_rejected_email
correction_requested_in_app
correction_requested_email
provisional_list_published_in_app
complaint_decided_in_app
complaint_decided_email
housing_allocated_in_app
housing_allocated_email
contract_issued_in_app
contract_issued_email
payment_overdue_in_app
payment_overdue_email
default_notice_document
generic_official_notice_document
```

Conteúdo dos templates deve ser neutro, municipal e parametrizável.

Não incluir dados reais.

Não criar promessas legais absolutas.

Usar mensagens como:

```text id="ncc75g"
Consulte a sua área pessoal para mais detalhes.
Responda dentro do prazo indicado, quando aplicável.
Esta comunicação foi gerada no âmbito do seu processo habitacional.
```

---

# 27. Modelos documentais mínimos

Criar modelos editáveis para:

```text id="66u0c7"
Comprovativo de submissão de candidatura
Notificação de rejeição de documento
Pedido de aperfeiçoamento
Notificação de publicação de lista provisória
Decisão de reclamação
Audiência de interessados
Notificação de lista definitiva
Notificação de atribuição de habitação
Carta de emissão de contrato
Aviso de pagamento em atraso
Aviso genérico oficial
```

Cada modelo deve suportar:

```text id="121y1z"
Título
Cabeçalho
Corpo
Rodapé
Variáveis
Versões
Pré-visualização
Geração de HTML
Geração de PDF se infraestrutura existir
Storage privado
Download seguro
```

---

# 28. Integração por módulo

## Sprint 4 — Registo de Adesão

Eventos:

```text id="l1ren7"
adhesion_registration_created
adhesion_registration_completed
```

Comunicações:

```text id="uxu9rd"
Confirmação de registo criado
Confirmação de registo concluído
Aviso de necessidade de atualização anual, se aplicável
```

## Sprint 8 — Candidatura

Evento:

```text id="kltag9"
application_submitted
```

Comunicações:

```text id="8ovvmp"
Confirmação de candidatura submetida
Comprovativo de submissão
Número de candidatura
```

## Sprint 6 — Documentos

Evento:

```text id="1sioft"
document_rejected
```

Comunicações:

```text id="3twzhw"
Documento rejeitado
Motivo de rejeição
Pedido de nova submissão
Prazo, se aplicável
```

## Sprint 9 — Aperfeiçoamento

Eventos:

```text id="jt4ha9"
correction_requested
correction_response_received
```

Comunicações:

```text id="2vppeh"
Pedido de aperfeiçoamento
Pedido de informação complementar
Confirmação de resposta recebida
```

## Sprint 11 — Listas e reclamações

Eventos:

```text id="sz6atf"
provisional_list_published
complaint_decided
hearing_opened
final_list_published
```

Comunicações:

```text id="8ptyu8"
Publicação de lista provisória
Abertura de período de reclamação
Decisão de reclamação
Audiência de interessados
Publicação de lista definitiva
```

## Sprint 12 — Atribuição

Evento:

```text id="bv6jjy"
housing_allocated
```

Comunicações:

```text id="pup561"
Habitação atribuída
Prazo de aceitação
Prazo de recusa
Pedido de documentação complementar, se aplicável
```

## Sprint 13 — Contrato

Evento:

```text id="sij65w"
contract_issued
```

Comunicações:

```text id="fafus5"
Contrato emitido
Minuta disponível
Prazo para validação/assinatura
```

## Sprint 14 — Pagamentos

Eventos:

```text id="nlqyo6"
payment_overdue
default_notice_issued
```

Comunicações:

```text id="xe0dgl"
Pagamento em atraso
Aviso de incumprimento
Acordo de regularização, se aplicável
```

## Sprint 15 — Manutenção

Eventos:

```text id="fzl0f7"
maintenance_request_created
maintenance_request_scheduled
maintenance_request_resolved
inspection_report_available
```

Comunicações:

```text id="ns3ufj"
Pedido de manutenção registado
Intervenção agendada
Pedido resolvido
Auto de vistoria disponível
```

---

# 29. Integração com e-mail

Se o Laravel Mail estiver configurado:

```text id="b6ao63"
Criar Mailable genérico para CommunicationLog
Usar subject renderizado
Usar html_snapshot
Registar CommunicationAttempt
Atualizar CommunicationDelivery
```

Se não estiver configurado:

```text id="x4l8t2"
Não tentar envio real.
Marcar delivery como pending_configuration.
Documentar na resposta final.
```

Não guardar credenciais SMTP.

Não alterar `.env`.

---

# 30. Integração com SMS

Se existir serviço SMS no projeto:

```text id="2az4yz"
Criar SmsChannelService usando interface existente
Enviar apenas sms_body
Registar provider_message_id se devolvido
Registar tentativa
```

Se não existir serviço SMS:

```text id="t0ljem"
Criar interface futura
Marcar canal como disabled ou simulated
Não afirmar envio real
Documentar pendência
```

---

# 31. Integração com documentos e storage

Documentos oficiais, comprovativos e anexos devem:

```text id="a7amou"
Ficar em storage privado
Não usar public/storage
Não expor storage_path
Usar controller de download autorizado
Gerar nomes de ficheiro sem NIF, email ou nome completo
Guardar checksum
Guardar mime_type e file_size
```

Se existir sistema documental da Sprint 6, integrar sempre que possível.

Se não existir biblioteca PDF:

```text id="tbhuzy"
Gerar HTML imprimível
Documentar ausência de PDF real
Não afirmar que PDF foi gerado
```

---

# 32. Auditoria

Se existir auditoria, auditar:

```text id="z7av5h"
Criação de template
Alteração de template
Criação de versão
Ativação de versão
Arquivo de template
Criação de regra de evento
Ativação/desativação de regra
Criação de comunicação
Envio de comunicação
Falha de comunicação
Reenvio de comunicação
Cancelamento de comunicação
Upload de comprovativo
Download de comprovativo
Leitura de notificação
Tomada de conhecimento
Criação de modelo documental
Geração de documento oficial
Download de documento oficial
Cancelamento de documento oficial
Alteração de preferências de notificação
```

Não criar auditoria paralela se já existir sistema de auditoria.

Se não existir auditoria, documentar pendência.

Não guardar conteúdo sensível excessivo em logs técnicos.

---

# 33. RGPD e segurança

Regras obrigatórias:

```text id="a2119i"
Comunicações podem conter dados pessoais.
Candidato só vê comunicações próprias.
Backoffice exige permissões.
Auditor não altera dados.
Templates não devem expor dados reais em exemplos.
Pré-visualizações devem usar dados fictícios ou dados autorizados.
Não expor storage_path.
Não colocar NIF, email, telefone ou nome completo no nome de ficheiros.
Não guardar payloads completos de providers.
Não enviar variáveis sensíveis por SMS por defeito.
Não permitir mass assignment de estados.
Não permitir download sem policy.
Não permitir reenvio por utilizador não autorizado.
Não permitir edição de versão de template já usada.
```

---

# 34. Seeders e factories

Criar factories:

```text id="hplj5k"
NotificationTemplateFactory
NotificationTemplateVersionFactory
TemplateVariableFactory
NotificationEventRuleFactory
OfficialNotificationFactory
CommunicationLogFactory
CommunicationDeliveryFactory
CommunicationAttemptFactory
CommunicationReceiptFactory
NotificationPreferenceFactory
DocumentTemplateFactory
DocumentTemplateVersionFactory
GeneratedOfficialDocumentFactory
```

Criar seeders:

```text id="3wj9i1"
TemplateVariableSeeder
NotificationTemplateSeeder
NotificationEventRuleSeeder
DocumentTemplateSeeder
CommunicationDemoSeeder
```

Usar apenas dados fictícios.

Templates demo devem conter aviso interno:

```text id="qyobk1"
TEMPLATE DEMO — SUJEITO A VALIDAÇÃO MUNICIPAL/JURÍDICA
```

---

# 35. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text id="30479c"
guest_cannot_access_candidate_notifications
candidate_can_view_own_notifications
candidate_cannot_view_other_user_notifications
candidate_can_mark_own_notification_as_read
candidate_can_acknowledge_own_notification_when_required
candidate_cannot_access_backoffice_communications
admin_can_manage_notification_templates
auditor_can_view_communication_logs_without_editing
technician_can_view_related_process_communications_when_authorized
```

## Templates

```text id="ecpi09"
notification_template_can_be_created
notification_template_requires_code_name_channel_and_body
notification_template_code_is_unique_per_context
notification_template_version_is_created_on_change
active_template_version_can_be_resolved
used_template_version_cannot_be_mutated
template_can_be_previewed_with_demo_variables
template_validation_fails_when_required_variable_missing
```

## Variáveis

```text id="3wfmw4"
template_variable_can_be_created
required_template_variable_is_validated
sensitive_variable_is_blocked_from_sms_when_configured
unknown_template_variable_returns_validation_error
```

## Regras de evento

```text id="mv4enc"
notification_event_rule_can_be_created
event_rule_requires_event_code_recipient_channel_and_template
inactive_event_rule_does_not_dispatch_notification
contest_specific_rule_has_priority_over_program_rule
program_specific_rule_has_priority_over_global_rule
```

## Dispatch de eventos

```text id="45kgvd"
registration_created_event_creates_notification
application_submitted_event_creates_notification
document_rejected_event_creates_notification
correction_requested_event_creates_notification
provisional_list_published_event_creates_notification
complaint_decided_event_creates_notification
housing_allocated_event_creates_notification
contract_issued_event_creates_notification
payment_overdue_event_creates_notification
```

## Comunicações

```text id="1xwyj4"
communication_log_is_created_with_snapshot
communication_number_is_unique
communication_delivery_is_created_per_channel
communication_attempt_is_recorded_on_send
failed_delivery_stores_failure_reason
communication_can_be_resent_by_authorized_user
pending_communication_can_be_cancelled
sent_communication_cannot_be_deleted
```

## E-mail e SMS

```text id="07vqk2"
email_delivery_uses_mailer_when_configured
email_delivery_marks_pending_configuration_when_mailer_missing
sms_delivery_is_disabled_when_gateway_missing
sms_delivery_does_not_claim_real_send_without_gateway
```

## Comprovativos

```text id="23uxk8"
send_receipt_can_be_generated
read_receipt_can_be_generated
acknowledgement_receipt_can_be_generated
postal_receipt_can_be_uploaded
receipt_file_is_stored_privately
receipt_download_requires_authorization
```

## Modelos documentais

```text id="chfj1g"
document_template_can_be_created
document_template_version_can_be_created
active_document_template_can_generate_document
generated_document_stores_html_snapshot
generated_document_is_stored_privately_when_pdf_exists
generated_document_download_requires_authorization
generated_document_can_be_cancelled_with_reason
```

## Área pessoal

```text id="kzp4j6"
candidate_notification_center_lists_unread_notifications
candidate_notification_center_lists_read_notifications
candidate_sees_action_required_when_acknowledgement_required
candidate_can_update_notification_preferences
candidate_cannot_disable_mandatory_official_communications_when_configured
```

## Segurança

```text id="2f6evv"
candidate_cannot_mass_assign_notification_status
candidate_cannot_resend_communication
candidate_cannot_cancel_communication
candidate_cannot_view_provider_response
candidate_cannot_download_other_user_receipt
candidate_cannot_download_other_user_official_document
generated_file_name_does_not_contain_nif
generated_file_name_does_not_contain_email
generated_file_name_does_not_contain_phone
receipt_file_is_not_publicly_accessible
official_document_file_is_not_publicly_accessible
```

## Auditoria, se existir

```text id="d6webz"
creating_template_generates_audit_log
activating_template_version_generates_audit_log
creating_event_rule_generates_audit_log
sending_communication_generates_audit_log
resending_communication_generates_audit_log
acknowledging_notification_generates_audit_log
generating_official_document_generates_audit_log
downloading_receipt_generates_audit_log
```

Se alguma dependência não existir, documentar teste como pendente em vez de criar teste quebrado.

---

# 36. Comandos de validação

No final, executar:

```bash id="uewctu"
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash id="e22zte"
php artisan migrate
```

Se o projeto usar frontend build:

```bash id="93ebs6"
npm run build
```

Se o projeto usar Pint:

```bash id="k0qmu2"
./vendor/bin/pint
```

Se existir PHPStan/Psalm:

```bash id="n89wyp"
./vendor/bin/phpstan analyse
```

Se algum comando falhar, documentar:

```text id="yqt93x"
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
```

Não afirmar que comandos passaram se não foram executados.

---

# 37. Atualização documental obrigatória

No final, atualizar, se existirem:

```text id="140lb6"
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
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

```text id="wn5sp9"
O que foi implementado
Tabelas criadas
Models criados
Enums criados
Controllers criados
Requests criados
Policies criadas
Services criados
Events/listeners/jobs criados
Views criadas
Rotas criadas
Seeders/factories criados
Testes criados
Comandos executados
Resultado dos comandos
Problemas encontrados
Pendências para Sprint 17
Limitações de e-mail
Limitações de SMS
Limitações de PDF
Limitações de comunicação postal
Regras de privacidade aplicadas
```

---

# 38. Critérios de aceitação

A Sprint 16 está concluída quando:

```text id="42fols"
Existe centro de notificações
O candidato vê notificações na área pessoal
O candidato consegue marcar notificações como lidas
O candidato consegue tomar conhecimento quando exigido
O backoffice consulta notificações e comunicações
Existem templates configuráveis
Existem templates por canal
Templates podem ser ajustados sem alterar código
Templates oficiais são versionados
Eventos críticos geram comunicações
Registo criado gera comunicação
Candidatura submetida gera comunicação
Documento rejeitado gera comunicação
Aperfeiçoamento solicitado gera comunicação
Lista publicada gera comunicação
Reclamação decidida gera comunicação
Habitação atribuída gera comunicação
Contrato emitido gera comunicação
Pagamento em atraso gera comunicação
Comunicações guardam snapshot do conteúdo
Comunicações guardam canal, destinatário, evento e estado
Tentativas de envio ficam registadas
Falhas ficam registadas
Comprovativos de envio/disponibilização podem ser gerados
Modelos documentais oficiais são editáveis
Documentos oficiais são gerados a partir de modelos
Documentos oficiais ficam em storage privado
Downloads exigem autorização
Candidato não vê comunicações de terceiros
Backoffice exige permissões
Auditoria é usada se existir
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foi implementada notificação certificada externa
Não foi implementado gateway SMS real sem configuração
Não foi implementada comunicação postal automática
Não foram introduzidas credenciais
```

---

# 39. Resposta final obrigatória

No final da execução, responder com:

```text id="rye68z"
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
12. Events/listeners/jobs criados ou alterados
13. Views/páginas criadas ou alteradas
14. Rotas criadas
15. Seeders/factories criados ou alterados
16. Testes criados ou alterados
17. Resultado dos comandos executados
18. Problemas encontrados
19. Pendências
20. Limitações de e-mail
21. Limitações de SMS
22. Limitações de PDF
23. Limitações de comunicação postal
24. Regras de privacidade implementadas
25. Confirmação de que não foram implementadas funcionalidades fora de âmbito
26. Recomendação objetiva para avançar ou não para Sprint 17
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 40. Execução imediata

Executa agora apenas:

```text id="9fs6vq"
Sprint 16 — Notificações, Comunicações e Modelos Documentais
```

Usa como referência principal:

```text id="s9v8nb"
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
```

Fim da master prompt da Sprint 16.
