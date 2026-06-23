# Sprint 16 — Notificações, Comunicações e Modelos Documentais

## Prioridade de desenvolvimento

Esta sprint pertence à fase transversal de comunicação oficial, notificações, prazos e modelos documentais da plataforma municipal de Arrendamento Acessível.

A Sprint 16 deve ser executada depois das principais fases processuais já existirem:

```text id="lviin5"
Sprint 4 — Registo de Adesão e Área Pessoal
Sprint 6 — Gestão Documental Avançada
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 11 — Listas Provisórias, Reclamações e Audiência
Sprint 12 — Atribuição de Habitações
Sprint 13 — Cálculo de Renda, Contratos e Caução
Sprint 14 — Pagamentos, Incumprimentos e Revisão de Renda
Sprint 15 — Manutenção, Vistorias e Gestão do Imóvel
Sprint 16 — Notificações, Comunicações e Modelos Documentais
```

Esta sprint deve centralizar todas as comunicações oficiais e evitar que cada módulo tenha notificações improvisadas, duplicadas ou não auditáveis.

---

# 1. Objetivo da Sprint

Implementar um sistema transversal de notificações, comunicações e modelos documentais oficiais.

A plataforma deve permitir que o Município:

```text id="9ortwz"
Crie e configure templates de comunicação
Crie templates de e-mail
Crie templates de SMS, se aplicável
Crie templates de notificação interna
Crie templates de notificação ao candidato
Crie modelos editáveis de documentos oficiais
Associe templates a eventos do sistema
Gere notificações automáticas por evento crítico
Consulte o histórico completo de comunicações
Consulte comprovativos de envio
Consulte tentativas de envio
Consulte erros de envio
Reenvie comunicações quando permitido
Cancele comunicações ainda não enviadas
Arquive comunicações
Controle leitura pelo candidato
Controle tomada de conhecimento
Gere documentos oficiais a partir de modelos parametrizáveis
Mantenha versões dos templates
Mantenha histórico de alterações de templates
```

A plataforma deve permitir que o candidato/arrendatário:

```text id="4wphky"
Consulte notificações na área pessoal
Leia comunicações oficiais
Assinale tomada de conhecimento quando aplicável
Consulte documentos oficiais disponibilizados
Descarregue documentos autorizados
Veja o estado das comunicações recebidas
Receba alertas internos sobre eventos críticos
```

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 16.

Não avances para Sprint 17, Sprint 18, Sprint 19 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash id="p5ruog"
git branch --show-current
```

Não interromper execução por causa da branch atual.

Antes de alterar código, lê, se existirem:

```text id="6d51u3"
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

Antes de implementar, identifica:

```text id="ksk466"
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

```text id="5p5ej8"
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

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY, chaves SMTP, tokens SMS ou credenciais.

---

# 3. Dependências funcionais

Esta sprint depende obrigatoriamente de:

```text id="h6pl4g"
Sistema de utilizadores
Sistema de autenticação
Área pessoal do candidato
Backoffice
```

Depende preferencialmente de:

```text id="vgem2f"
Sprint 4 — Registo de Adesão e Área Pessoal
Sprint 6 — Gestão Documental Avançada
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 11 — Listas Provisórias, Reclamações e Audiência
Sprint 12 — Atribuição de Habitações
Sprint 13 — Contratos
Sprint 14 — Pagamentos e Incumprimentos
Sprint 15 — Manutenção e Vistorias
Sistema de auditoria
Sistema de storage privado
Sistema de queues
Sistema de PDF
```

Se a área pessoal do candidato não existir, interromper a componente de consulta pelo candidato e documentar:

```text id="15rg7x"
A consulta de notificações pelo candidato depende da área pessoal do candidato.
```

Se o sistema de e-mail não estiver configurado, implementar templates e fila lógica, mas não afirmar que existe envio real.

Se SMS não estiver configurado, implementar templates e abstração de canal SMS, mas manter o canal como `disabled` ou `simulated`.

---

# 4. Validação jurídica, administrativa e RGPD

Esta sprint trata comunicações oficiais e pode ter impacto procedimental.

Regras obrigatórias:

```text id="5vpb1k"
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

Não implementar notificação eletrónica certificada, via CTT, ViaCTT, correio registado automático, assinatura digital qualificada ou integração com gateways externos sem instrução explícita.

Não declarar uma comunicação como entregue se apenas foi colocada em fila.

Não marcar SMS/e-mail como enviado se não houve integração real.

---

# 5. Âmbito incluído

Implementar:

```text id="wk6keo"
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
Rotas
Views/páginas
Seeders/factories
Testes
Atualização documental
```

---

# 6. Fora de âmbito

Não implementar nesta sprint:

```text id="okdkff"
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

Podem ser criados pontos de integração para futuras sprints.

---

# 7. Conceito funcional

O fluxo base deve ser:

```text id="7k9f4v"
Evento crítico ocorre no sistema
→ Regra de comunicação é resolvida
→ Template ativo é identificado
→ Destinatários são resolvidos
→ Variáveis são preenchidas
→ Comunicação é criada
→ Notificação interna é criada
→ Entregas por canal são criadas
→ Envio é executado ou simulado conforme configuração
→ Tentativas ficam registadas
→ Comprovativo é gerado
→ Candidato vê a notificação na área pessoal
→ Candidato lê ou toma conhecimento
→ Histórico fica disponível no backoffice
```

O fluxo de modelo documental deve ser:

```text id="bnuhpq"
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

# 8. Eventos críticos obrigatórios

Criar suporte para os seguintes eventos:

```text id="0b1x5i"
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

Eventos mínimos obrigatórios da sprint:

```text id="r60n33"
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

# 9. Canais de comunicação

Suportar os seguintes canais:

```text id="fdujm8"
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

Se não estiver configurado, criar entrega com estado `pending_configuration` ou `failed_configuration`.

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

# 10. Estados principais

## NotificationStatus

```text id="0r2jld"
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

```text id="706s9l"
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

```text id="nm0ddg"
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

```text id="111jig"
started
success
failed
cancelled
skipped
```

## TemplateStatus

```text id="3r5pc4"
draft
active
inactive
archived
```

## TemplateType

```text id="hnhdti"
in_app
internal
email
sms
postal
document
```

## TemplateVariableType

```text id="6vmw40"
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

```text id="7gfavq"
draft
generated
issued
cancelled
archived
failed
```

## NotificationPriority

```text id="66m9w9"
low
normal
high
urgent
critical
```

---

# 11. Modelo de dados

## 11.1 NotificationTemplate

Criar entidade:

```text id="2gu63z"
NotificationTemplate
```

Tabela:

```text id="5zd8ku"
notification_templates
```

Objetivo:

```text id="qq2oau"
Guardar templates configuráveis para notificações in-app, internas, e-mail, SMS, comunicação postal e documentos simples.
```

Campos mínimos:

```text id="rshre0"
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

```text id="rlo1x8"
code obrigatório e único por municipality/program/contest/channel.
Templates oficiais devem ser versionados.
Apenas um template ativo por código/canal/contexto.
Não apagar template usado em comunicação; arquivar.
```

---

## 11.2 NotificationTemplateVersion

Criar entidade:

```text id="qtmvgc"
NotificationTemplateVersion
```

Tabela:

```text id="41zmfb"
notification_template_versions
```

Objetivo:

```text id="vldhr8"
Preservar versões dos templates.
```

Campos mínimos:

```text id="ibfii2"
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

```text id="rpbrz2"
Comunicações devem guardar o template_version_id usado.
Alteração de template cria nova versão.
Não alterar versões já usadas em comunicações.
```

---

## 11.3 TemplateVariable

Criar entidade:

```text id="d66noe"
TemplateVariable
```

Tabela:

```text id="t4tgby"
template_variables
```

Objetivo:

```text id="x6ighq"
Definir variáveis disponíveis para templates.
```

Campos mínimos:

```text id="n4psdg"
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

```text id="9khmlj"
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

```text id="w1nce7"
Variáveis sensíveis não devem ser usadas em SMS por defeito.
Variáveis obrigatórias devem ser validadas antes de enviar.
```

---

## 11.4 NotificationEventRule

Criar entidade:

```text id="8wde71"
NotificationEventRule
```

Tabela:

```text id="8mql41"
notification_event_rules
```

Objetivo:

```text id="93sv9p"
Configurar que comunicações são geradas para cada evento.
```

Campos mínimos:

```text id="6pibag"
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

```text id="g3nqkg"
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

```text id="kdjogu"
Evento pode gerar múltiplas comunicações por canais diferentes.
Regra inativa não gera comunicação.
Não enviar para destinatário sem contacto válido no canal escolhido.
```

---

## 11.5 OfficialNotification

Criar ou adaptar entidade:

```text id="nn41g0"
OfficialNotification
```

Tabela:

```text id="um8x5g"
official_notifications
```

Objetivo:

```text id="kz1180"
Representar notificação visível ao utilizador ou ao backoffice.
```

Campos mínimos:

```text id="9s6ehl"
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

```text id="9jgyku"
notification_number obrigatório e único.
Candidato só vê notificações próprias.
Notificação oficial deve ficar associada a CommunicationLog quando aplicável.
```

---

## 11.6 CommunicationLog

Criar entidade:

```text id="fjdubv"
CommunicationLog
```

Tabela:

```text id="oa8z5w"
communication_logs
```

Objetivo:

```text id="e8xg3j"
Guardar histórico consolidado de comunicações oficiais.
```

Campos mínimos:

```text id="wgp5vg"
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

```text id="k8pexo"
communication_number obrigatório e único.
Guardar snapshot do conteúdo enviado.
Não depender do template atual para consultar comunicação antiga.
Não apagar comunicação oficial; arquivar.
```

---

## 11.7 CommunicationDelivery

Criar entidade:

```text id="f3k9e9"
CommunicationDelivery
```

Tabela:

```text id="3l2hqe"
communication_deliveries
```

Objetivo:

```text id="jq8wnj"
Registar entrega por canal.
```

Campos mínimos:

```text id="lfe2bs"
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

```text id="2ls9mg"
Uma comunicação pode ter várias entregas.
Cada canal tem estado próprio.
Não guardar respostas de provider com dados sensíveis excessivos.
```

---

## 11.8 CommunicationAttempt

Criar entidade:

```text id="8wj529"
CommunicationAttempt
```

Tabela:

```text id="5lvesb"
communication_attempts
```

Objetivo:

```text id="uewkhl"
Registar tentativas de envio.
```

Campos mínimos:

```text id="bnbrs7"
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

```text id="6ys71d"
Não guardar payload completo com dados pessoais.
Guardar apenas resumo técnico seguro.
Tentativas são append-only.
```

---

## 11.9 CommunicationReceipt

Criar entidade:

```text id="1bvppn"
CommunicationReceipt
```

Tabela:

```text id="f5u3ke"
communication_receipts
```

Objetivo:

```text id="hhq7to"
Guardar comprovativos de envio, disponibilização, leitura ou tomada de conhecimento.
```

Campos mínimos:

```text id="ji5me5"
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

```text id="q2qi7q"
send_proof
delivery_proof
read_proof
acknowledgement_proof
postal_proof
manual_upload
```

Regras:

```text id="58itmu"
Comprovativos devem ficar em storage privado.
Não expor storage_path.
Downloads exigem policy.
```

---

## 11.10 NotificationPreference

Criar entidade:

```text id="e96ozu"
NotificationPreference
```

Tabela:

```text id="clt96n"
notification_preferences
```

Objetivo:

```text id="ai9rpm"
Guardar preferências de canal do utilizador quando aplicável.
```

Campos mínimos:

```text id="dfrr2c"
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

```text id="qevi4o"
Não bloquear comunicações obrigatórias por mera preferência quando exista base legal/procedimental.
Preferências devem ser consideradas para canais opcionais.
Mudanças devem ser auditadas se existir auditoria.
```

---

## 11.11 DocumentTemplate

Criar ou adaptar entidade:

```text id="5m2ayj"
DocumentTemplate
```

Tabela:

```text id="d3d6nw"
document_templates
```

Objetivo:

```text id="40fh45"
Guardar modelos editáveis de documentos oficiais.
```

Campos mínimos:

```text id="bogk6o"
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

```text id="la4eqn"
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

## 11.12 DocumentTemplateVersion

Criar entidade:

```text id="f1tc5w"
DocumentTemplateVersion
```

Tabela:

```text id="s80gwi"
document_template_versions
```

Campos mínimos:

```text id="wft40p"
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

## 11.13 GeneratedOfficialDocument

Criar entidade:

```text id="lggqmd"
GeneratedOfficialDocument
```

Tabela:

```text id="yh75lo"
generated_official_documents
```

Objetivo:

```text id="b38b1j"
Guardar documentos oficiais gerados a partir de templates.
```

Campos mínimos:

```text id="8ub64d"
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

```text id="hf92ng"
Documento gerado deve guardar snapshot do HTML.
PDF só deve ser gerado se biblioteca existir.
Se não existir PDF, criar HTML imprimível e documentar pendência.
Documentos devem ficar em storage privado.
```

---

# 12. Enums recomendados

Criar, se a versão do PHP permitir:

```text id="7dz9zn"
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

# 13. Relações obrigatórias

## User

Adicionar:

```text id="b3sx2r"
hasMany OfficialNotification as receivedNotifications
hasMany CommunicationLog as communications
hasOne NotificationPreference
hasMany GeneratedOfficialDocument as generatedDocuments
```

## NotificationTemplate

```text id="p9tqqk"
hasMany NotificationTemplateVersion
belongsTo activeVersion
hasMany NotificationEventRule
hasMany CommunicationLog
```

## NotificationTemplateVersion

```text id="rh6ll9"
belongsTo NotificationTemplate
belongsTo User as createdBy
belongsTo User as approvedBy nullable
```

## NotificationEventRule

```text id="2heurt"
belongsTo NotificationTemplate
belongsTo Program nullable
belongsTo Contest nullable
belongsTo User as createdBy
belongsTo User as updatedBy nullable
```

## OfficialNotification

```text id="fryi5b"
belongsTo User as recipient
morphTo related
belongsTo User as createdBy nullable
hasOne CommunicationDelivery nullable
```

## CommunicationLog

```text id="9wzaz0"
belongsTo User as recipient nullable
belongsTo NotificationTemplate nullable
belongsTo NotificationTemplateVersion nullable
morphTo related
belongsTo User as createdBy nullable
hasMany CommunicationDelivery
hasMany CommunicationReceipt
```

## CommunicationDelivery

```text id="zqiihh"
belongsTo CommunicationLog
belongsTo OfficialNotification nullable
hasMany CommunicationAttempt
hasMany CommunicationReceipt
```

## CommunicationAttempt

```text id="tb8kdf"
belongsTo CommunicationDelivery
belongsTo User as createdBy nullable
```

## CommunicationReceipt

```text id="0xerfa"
belongsTo CommunicationLog
belongsTo CommunicationDelivery nullable
belongsTo User as generatedBy nullable
```

## DocumentTemplate

```text id="hj3k8n"
hasMany DocumentTemplateVersion
belongsTo activeVersion
hasMany GeneratedOfficialDocument
```

## GeneratedOfficialDocument

```text id="fl02kp"
belongsTo DocumentTemplate
belongsTo DocumentTemplateVersion
morphTo related
belongsTo User as recipient nullable
belongsTo User as generatedBy nullable
belongsTo User as issuedBy nullable
belongsTo User as cancelledBy nullable
```

---

# 14. Services obrigatórios

Criar:

```text id="00kidf"
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

---

## 14.1 NotificationEventDispatcher

Responsável por:

```text id="w56uk0"
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

---

## 14.2 NotificationEventRuleResolver

Responsável por:

```text id="xw2e56"
Resolver regras por event_code
Priorizar regra específica de concurso
Depois regra específica de programa
Depois regra municipal
Depois regra global
Ignorar regras inativas
Validar canal e template ativo
```

---

## 14.3 RecipientResolver

Responsável por:

```text id="v8fu9e"
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

---

## 14.4 NotificationTemplateResolver

Responsável por:

```text id="8lws09"
Obter template ativo
Obter versão ativa
Validar compatibilidade de canal
Validar variáveis obrigatórias
Resolver fallback global
```

---

## 14.5 TemplateRenderingService

Responsável por:

```text id="ay7e5j"
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

```text id="hadl1a"
{{ candidate.name }}
{{ application.number }}
{{ contest.title }}
{{ deadline.date }}
{{ municipality.name }}
```

---

## 14.6 NotificationCenterService

Responsável por:

```text id="spt8cj"
Listar notificações do candidato
Listar notificações internas do backoffice
Contar notificações não lidas
Marcar como lida
Marcar tomada de conhecimento
Arquivar notificação
Filtrar por estado, evento, prioridade e data
```

---

## 14.7 OfficialNotificationService

Responsável por:

```text id="9bhqhz"
Criar notificação in-app
Gerar número único
Publicar notificação
Marcar como lida
Marcar tomada de conhecimento
Cancelar notificação
Arquivar notificação
```

---

## 14.8 CommunicationLogService

Responsável por:

```text id="h5nu5b"
Criar comunicação oficial
Gerar número único
Guardar snapshot do conteúdo
Atualizar estado global
Consultar histórico
Filtrar por candidato, evento, módulo e canal
Cancelar comunicação pendente
Arquivar comunicação
```

---

## 14.9 CommunicationDeliveryService

Responsável por:

```text id="ug6l7z"
Criar entregas por canal
Executar envio por canal
Atualizar estado da entrega
Registar provider
Registar provider_message_id quando existir
Reenviar quando permitido
Marcar canal como disabled/simulated/pending_configuration
```

---

## 14.10 CommunicationAttemptService

Responsável por:

```text id="6h9f81"
Criar tentativa de envio
Registar início
Registar sucesso
Registar falha
Registar erro técnico
Guardar resumo seguro de request/response
```

---

## 14.11 CommunicationReceiptService

Responsável por:

```text id="ok76hh"
Gerar comprovativo de envio
Gerar comprovativo de disponibilização
Gerar comprovativo de leitura
Gerar comprovativo de tomada de conhecimento
Permitir upload manual de comprovativo postal
Guardar comprovativo em storage privado
Permitir download seguro
```

---

## 14.12 EmailChannelService

Responsável por:

```text id="bva2ar"
Verificar se mailer está configurado
Enviar e-mail usando Mail Laravel se disponível
Registar sucesso ou falha
Não afirmar envio se mailer não estiver configurado
Criar tentativa
```

---

## 14.13 SmsChannelService

Responsável por:

```text id="s3h3b3"
Verificar se gateway SMS existe
Se não existir, marcar como disabled ou simulated
Criar abstração para envio futuro
Registar tentativa apenas se houver execução real ou simulação explícita
Não enviar SMS real sem configuração
```

---

## 14.14 PostalChannelService

Responsável por:

```text id="ich89j"
Criar entrega postal manual
Permitir registar data de envio
Permitir registar referência postal
Permitir anexar comprovativo
Permitir marcar como enviado manualmente
```

---

## 14.15 DocumentTemplateService

Responsável por:

```text id="l0h9gh"
Criar modelo documental
Editar modelo documental
Arquivar modelo documental
Validar variáveis
Gerir estado draft/active/inactive/archived
```

---

## 14.16 DocumentTemplateVersionService

Responsável por:

```text id="zv3nbr"
Criar versão
Aprovar versão
Ativar versão
Arquivar versão
Impedir alteração de versão usada
```

---

## 14.17 OfficialDocumentGenerationService

Responsável por:

```text id="q3i25v"
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

# 15. Listeners / Events

Criar eventos ou listeners conforme a arquitetura existente.

Eventos recomendados:

```text id="xusxbg"
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

```text id="ek3w9a"
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

```text id="vfxuyr"
NotificationEventDispatcher::dispatch(string $eventCode, Model $related, array $context = [])
```

---

# 16. Jobs / Queues

Criar jobs se o projeto usar filas:

```text id="jq18ow"
SendCommunicationDeliveryJob
GenerateCommunicationReceiptJob
GenerateOfficialDocumentJob
ProcessPendingCommunicationsJob
```

Se queues não estiverem configuradas, permitir fallback síncrono controlado e documentar.

Nunca assumir que queue worker está ativo.

---

# 17. Controllers

Criar controllers conforme a arquitetura existente.

## Backoffice

Namespace recomendado:

```text id="d8sr8s"
App\Http\Controllers\Backoffice
```

Controllers:

```text id="d43cwp"
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

```text id="9c6p4w"
App\Http\Controllers\Candidate
```

Controllers:

```text id="yxivj7"
Candidate\NotificationCenterController
Candidate\OfficialNotificationController
Candidate\CommunicationController
Candidate\GeneratedOfficialDocumentController
Candidate\NotificationPreferenceController
```

---

# 18. Form Requests

Criar:

```text id="ixx7kg"
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

```text id="5ebfc6"
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

```text id="13vrwz"
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

```text id="627u6p"
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

```text id="8wehz4"
communication_delivery_id required|exists:communication_deliveries,id
sent_at required|date
postal_reference nullable|string|max:255
notes nullable|string|max:3000
receipt_file nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240
```

## StoreDocumentTemplateRequest

```text id="52sxj3"
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

```text id="srx9fn"
document_template_id required|exists:document_templates,id
related_type nullable|string|max:255
related_id nullable|integer
recipient_user_id nullable|exists:users,id
variables nullable|array
issue_immediately boolean
```

## UpdateNotificationPreferenceRequest

```text id="bag11p"
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

# 19. Policies

Criar:

```text id="qc2534"
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

```text id="g3zwbi"
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

```text id="px9qds"
Pode consultar comunicações dos processos que gere.
Pode criar comunicação manual se autorizado.
Pode gerar documentos oficiais se autorizado.
Pode consultar templates ativos.
Não edita templates oficiais se não tiver permissão.
Não vê dados técnicos sensíveis de provider salvo permissão.
```

## Regras para admin

```text id="8wy9x5"
Pode gerir templates.
Pode gerir regras de evento.
Pode consultar histórico global.
Pode reenviar comunicações.
Pode cancelar comunicações pendentes.
Pode gerir modelos documentais.
Pode ativar versões.
```

## Regras para auditor

```text id="8oxb93"
Pode consultar histórico de comunicações.
Pode consultar comprovativos.
Pode consultar versões de templates.
Não pode enviar, reenviar, cancelar ou editar comunicações.
Não pode editar templates.
```

---

# 20. Rotas

## Backoffice

Criar, preferencialmente:

```text id="zipiwy"
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

```text id="qsbnc2"
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

# 21. Views / páginas

Se o projeto usa Blade, criar:

## Backoffice

```text id="kcvr4y"
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

```text id="h07915"
resources/views/candidate/notifications/index.blade.php
resources/views/candidate/notifications/show.blade.php
resources/views/candidate/communications/index.blade.php
resources/views/candidate/communications/show.blade.php
resources/views/candidate/official-documents/index.blade.php
resources/views/candidate/official-documents/show.blade.php
resources/views/candidate/notification-preferences/edit.blade.php
```

## Templates renderizáveis

```text id="9avjmq"
resources/views/communications/email/generic.blade.php
resources/views/communications/receipts/send-proof.blade.php
resources/views/documents/official/generic-document.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 22. UX obrigatória no backoffice

## Dashboard de comunicações

Mostrar:

```text id="m4u4pc"
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

```text id="0u45zf"
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

```text id="59l1yi"
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

```text id="sezvxd"
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

```text id="gv0hpy"
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

# 23. UX obrigatória para candidato

## Centro de notificações

Mostrar:

```text id="6k8oai"
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

```text id="07br8l"
Consulte regularmente as notificações da plataforma para acompanhar o estado dos seus processos e responder dentro dos prazos indicados.
```

## Detalhe da notificação

Mostrar:

```text id="lv1gfr"
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

```text id="p0f5jl"
Ao assinalar tomada de conhecimento, confirma que leu esta comunicação na plataforma.
```

## Preferências de notificação

Mostrar:

```text id="7ilr70"
E-mail para notificações
Telemóvel para SMS, se aplicável
Morada postal, se aplicável
Canais preferenciais
Canais obrigatórios informativos
Data do consentimento ou atualização
```

Copy obrigatório:

```text id="pfx572"
Algumas comunicações oficiais podem ser enviadas por canais obrigatórios definidos pelo Município ou pela lei aplicável, independentemente das preferências opcionais.
```

---

# 24. Templates mínimos a criar por seed

Criar templates iniciais para:

```text id="1s9w2g"
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

```text id="66fqre"
Consulte a sua área pessoal para mais detalhes.
Responda dentro do prazo indicado, quando aplicável.
Esta comunicação foi gerada no âmbito do seu processo habitacional.
```

---

# 25. Modelos documentais mínimos

Criar modelos editáveis para:

```text id="7u8ku5"
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

```text id="znccdd"
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

# 26. Integração por módulo

## Sprint 4 — Registo de Adesão

Evento:

```text id="7h5t5t"
adhesion_registration_created
adhesion_registration_completed
```

Comunicações:

```text id="178ko5"
Confirmação de registo criado
Confirmação de registo concluído
Aviso de necessidade de atualização anual, se aplicável
```

## Sprint 8 — Candidatura

Evento:

```text id="3r2cqz"
application_submitted
```

Comunicações:

```text id="gkrtvd"
Confirmação de candidatura submetida
Comprovativo de submissão
Número de candidatura
```

## Sprint 6 — Documentos

Evento:

```text id="ikqnxb"
document_rejected
```

Comunicações:

```text id="xpv76d"
Documento rejeitado
Motivo de rejeição
Pedido de nova submissão
Prazo, se aplicável
```

## Sprint 9 — Aperfeiçoamento

Evento:

```text id="u32o9s"
correction_requested
correction_response_received
```

Comunicações:

```text id="23b8aj"
Pedido de aperfeiçoamento
Pedido de informação complementar
Confirmação de resposta recebida
```

## Sprint 11 — Listas e reclamações

Eventos:

```text id="npzc89"
provisional_list_published
complaint_decided
hearing_opened
final_list_published
```

Comunicações:

```text id="w7ecfp"
Publicação de lista provisória
Abertura de período de reclamação
Decisão de reclamação
Audiência de interessados
Publicação de lista definitiva
```

## Sprint 12 — Atribuição

Evento:

```text id="6m5dvh"
housing_allocated
```

Comunicações:

```text id="xdmnzb"
Habitação atribuída
Prazo de aceitação
Prazo de recusa
Pedido de documentação complementar, se aplicável
```

## Sprint 13 — Contrato

Evento:

```text id="m6vd0p"
contract_issued
```

Comunicações:

```text id="39m8dh"
Contrato emitido
Minuta disponível
Prazo para validação/assinatura
```

## Sprint 14 — Pagamentos

Evento:

```text id="84eq43"
payment_overdue
default_notice_issued
```

Comunicações:

```text id="tcn0k6"
Pagamento em atraso
Aviso de incumprimento
Acordo de regularização, se aplicável
```

## Sprint 15 — Manutenção

Evento:

```text id="zl57iv"
maintenance_request_created
maintenance_request_scheduled
maintenance_request_resolved
inspection_report_available
```

Comunicações:

```text id="x7hjvl"
Pedido de manutenção registado
Intervenção agendada
Pedido resolvido
Auto de vistoria disponível
```

---

# 27. Integração com e-mail

Se o Laravel Mail estiver configurado:

```text id="qwasat"
Criar Mailable genérico para CommunicationLog
Usar subject renderizado
Usar html_snapshot
Registar CommunicationAttempt
Atualizar CommunicationDelivery
```

Se não estiver configurado:

```text id="w9c0gy"
Não tentar envio real.
Marcar delivery como pending_configuration.
Documentar na resposta final.
```

Não guardar credenciais SMTP.

Não alterar `.env`.

---

# 28. Integração com SMS

Se existir serviço SMS no projeto:

```text id="2999yn"
Criar SmsChannelService usando interface existente
Enviar apenas sms_body
Registar provider_message_id se devolvido
Registar tentativa
```

Se não existir serviço SMS:

```text id="jld1wz"
Criar interface futura
Marcar canal como disabled ou simulated
Não afirmar envio real
Documentar pendência
```

---

# 29. Integração com documentos e storage

Documentos oficiais, comprovativos e anexos devem:

```text id="lxj4ta"
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

```text id="53nhvb"
Gerar HTML imprimível
Documentar ausência de PDF real
Não afirmar que PDF foi gerado
```

---

# 30. Auditoria

Se existir auditoria, auditar:

```text id="9cikfp"
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

# 31. RGPD e segurança

Regras obrigatórias:

```text id="1tvd92"
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

# 32. Seeders e factories

Criar factories:

```text id="k252xf"
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

```text id="vu4s84"
TemplateVariableSeeder
NotificationTemplateSeeder
NotificationEventRuleSeeder
DocumentTemplateSeeder
CommunicationDemoSeeder
```

Usar apenas dados fictícios.

Templates demo devem conter aviso interno:

```text id="ogwfih"
TEMPLATE DEMO — SUJEITO A VALIDAÇÃO MUNICIPAL/JURÍDICA
```

---

# 33. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text id="uj31ju"
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

```text id="32zx1v"
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

```text id="frsw9h"
template_variable_can_be_created
required_template_variable_is_validated
sensitive_variable_is_blocked_from_sms_when_configured
unknown_template_variable_returns_validation_error
```

## Regras de evento

```text id="1d8ma5"
notification_event_rule_can_be_created
event_rule_requires_event_code_recipient_channel_and_template
inactive_event_rule_does_not_dispatch_notification
contest_specific_rule_has_priority_over_program_rule
program_specific_rule_has_priority_over_global_rule
```

## Dispatch de eventos

```text id="m9haod"
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

```text id="bqz454"
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

```text id="n5lxqr"
email_delivery_uses_mailer_when_configured
email_delivery_marks_pending_configuration_when_mailer_missing
sms_delivery_is_disabled_when_gateway_missing
sms_delivery_does_not_claim_real_send_without_gateway
```

## Comprovativos

```text id="4kzikr"
send_receipt_can_be_generated
read_receipt_can_be_generated
acknowledgement_receipt_can_be_generated
postal_receipt_can_be_uploaded
receipt_file_is_stored_privately
receipt_download_requires_authorization
```

## Modelos documentais

```text id="yipdyp"
document_template_can_be_created
document_template_version_can_be_created
active_document_template_can_generate_document
generated_document_stores_html_snapshot
generated_document_is_stored_privately_when_pdf_exists
generated_document_download_requires_authorization
generated_document_can_be_cancelled_with_reason
```

## Área pessoal

```text id="cmhbs6"
candidate_notification_center_lists_unread_notifications
candidate_notification_center_lists_read_notifications
candidate_sees_action_required_when_acknowledgement_required
candidate_can_update_notification_preferences
candidate_cannot_disable_mandatory_official_communications_when_configured
```

## Segurança

```text id="ty3xlp"
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

```text id="l2xfmr"
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

# 34. Comandos de validação

No final, executar:

```bash id="xiun8p"
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash id="i0nkzf"
php artisan migrate
```

Se o projeto usar frontend build:

```bash id="tme7j7"
npm run build
```

Se o projeto usar Pint:

```bash id="wmay3k"
./vendor/bin/pint
```

Se existir PHPStan/Psalm:

```bash id="qcc1iq"
./vendor/bin/phpstan analyse
```

Se algum comando falhar, documentar:

```text id="lsyshp"
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
```

Não afirmar que comandos passaram se não foram executados.

---

# 35. Atualização documental obrigatória

Atualizar, se existirem:

```text id="3fhz6g"
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

```text id="st5zds"
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
Pendências para Sprint 17
Limitações de e-mail
Limitações de SMS
Limitações de PDF
Limitações de comunicação postal
Regras de privacidade aplicadas
```

---

# 36. Critérios de aceitação

A Sprint 16 está concluída quando:

```text id="qc2qkl"
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

# 37. Resposta final esperada do Codex

No final da execução, responder com:

```text id="t7hioq"
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

# 38. Definition of Done

A Sprint 16 só está concluída quando a plataforma tiver um sistema transversal de notificações e comunicações oficiais, com templates configuráveis, histórico auditável, comprovativos, área pessoal do candidato, regras por evento e modelos documentais editáveis.

O resultado deve permitir que todas as fases futuras da plataforma usem um sistema único de comunicações, em vez de notificações isoladas por módulo.

Fim da Sprint 16.

---

# Relatório de execução — 15/06/2026

## Estado

Sprint 16 implementada e validada. Não foi iniciada a Sprint 17.

## Implementado

- centro de notificações e comunicações no backoffice;
- caixa de notificações, histórico, documentos oficiais e preferências na área do candidato;
- evolução compatível de `official_notifications`;
- templates, versões, variáveis e regras por evento;
- logs, entregas, tentativas, comprovativos e preferências;
- canais in-app, interno, email, SMS, postal e documento;
- eventos/listener e jobs para execução assíncrona;
- modelos documentais versionados e geração HTML privada;
- policies, Form Requests, services, controllers, rotas e views;
- factories e seeders fictícios;
- integração automática dos módulos anteriores através de `OfficialNotificationService`.

## Tabelas criadas

`notification_templates`, `notification_template_versions`, `template_variables`, `notification_event_rules`, `communication_logs`, `communication_deliveries`, `communication_attempts`, `communication_receipts`, `notification_preferences`, `document_templates`, `document_template_versions`, `generated_official_documents`.

`official_notifications` foi adaptada de forma aditiva, sem apagar registos.

## Validação executada

- `composer validate`: passou.
- `php artisan route:list`: passou, com 733 rotas registadas.
- `php artisan event:list`: confirmou `CriticalNotificationEvent` e listener.
- `php artisan view:cache`: passou.
- `php artisan test tests/Feature/Sprint16CommunicationsTest.php`: passou com 9 testes/56 asserções.
- `php artisan test`: passou com 139 testes/854 asserções.
- `./vendor/bin/pint` nos ficheiros da sprint: executado e corrigiu formatação.
- `./vendor/bin/pint --test`: passou após as correções finais.
- `npm run build`: passou.
- `php artisan migrate --force`: migration aplicada com sucesso.
- `php artisan migrate:status`: migration `2026_06_15_010000_create_communication_document_tables` confirmada como executada.
- seeders específicos da Sprint 16: executados com sucesso.
- servidor Laravel iniciado em `http://127.0.0.1:8001`;
- pedidos HTTP sem sessão ao centro de comunicações e notificações do candidato devolveram `302` para `/login`;
- `/login` respondeu com `200`;
- autenticação com a conta administrativa fictícia local abriu `/backoffice/communications`;
- lista de templates abriu com 17 templates ativos e sem erros de consola;
- PHPStan/Psalm: não executados porque não estão instalados em `vendor/bin`.

## Problemas encontrados e correções

1. O primeiro teste do dispatcher falhou com variável `$eventCode` não definida no método privado.
   Correção: usar `NotificationEventRule::$event_code`.
2. A primeira regressão completa falhou por incompatibilidade de copy com teste antigo (`Sem notificações`).
   Correção: preservada a mensagem anterior na nova página.
3. Duas consultas de diagnóstico em `php artisan tinker` falharam por assumirem interfaces inexistentes: coluna `users.role` e método `User::roleNames()`.
   Causa: o projeto usa relação many-to-many `roles()`.
   Correção: a consulta final carregou `roles:id,name` e confirmou os perfis existentes.
4. A captura de ecrã pelo navegador integrado expirou em `Page.captureScreenshot`.
   Impacto: não foi produzido artefacto PNG.
   Mitigação: navegação, DOM, conteúdo dos 17 templates e ausência de erros de consola foram confirmados no navegador; os estados HTTP foram também verificados por `curl`.

As correções aplicáveis foram validadas pela suíte completa.

## Limitações

- Email: o mailer local `log`/não configurado gera estado `pending_configuration`; não existe envio externo real.
- SMS: não existe gateway; o canal fica `disabled` e a tentativa `skipped`.
- PDF: não existe biblioteca/serviço instalado; documentos e comprovativos são HTML imprimível privado.
- Postal: preparação e registo são manuais; não há CTT, ViaCTT ou correio registado automático.
- Comprovativos internos não constituem notificação eletrónica certificada.

## Privacidade e segurança

- ownership aplicado a candidato;
- backoffice protegido por roles/permissões e policies;
- auditor sem escrita;
- storage privado e downloads autorizados;
- sem exposição de paths;
- snapshots e payloads técnicos minimizados;
- variáveis sensíveis bloqueadas em SMS;
- autorização de leitura, tomada de conhecimento e arquivo reforçada também na camada de serviço;
- conteúdo configurável é apresentado como texto escapado nos documentos, previews e emails enquanto não existir sanitizador HTML aprovado;
- seeders sem dados pessoais reais;
- sem alteração de `.env`, passwords, tokens, APP_KEY ou credenciais.

## Pendências para Sprint 17

- definir indicadores e agregações executivas sem expor dados pessoais;
- usar `communication_logs` e `communication_deliveries` como fontes dos indicadores de comunicação;
- validar necessidades de exportação e anonimização de relatórios;
- manter integrações reais de email/SMS/PDF/postal fora da Sprint 17 salvo decisão explícita.
