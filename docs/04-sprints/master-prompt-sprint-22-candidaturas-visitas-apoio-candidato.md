# MASTER PROMPT — EXECUÇÃO DA SPRINT 22: CANDIDATURAS, VISITAS E APOIO AO CANDIDATO

Atua como arquiteto sénior Laravel, tech lead, product engineer, QA engineer e especialista em plataformas públicas municipais de Habitação/Arrendamento Acessível.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 22 — Candidaturas, Visitas e Apoio ao Candidato
```

Esta sprint pertence à fase de melhoria da experiência do candidato durante o processo de candidatura.

A Sprint 22 deve acrescentar um módulo integrado de visitas, calendário interno, gestão de disponibilidade, reagendamento, cancelamento, linha de apoio por tickets, conversação entre técnico e candidato, histórico de interações, FAQ contextual e indicadores de inconsistência entre simulador e candidatura.

A implementação deve preservar os módulos existentes de registo, simulador, candidatura, documentos, workflow administrativo, notificações, auditoria, RGPD e portal público.

---

# 1. Regra principal

Executa apenas a Sprint 22.

Não avances para Sprint 23, Sprint 24 ou qualquer sprint futura sem validação explícita.

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
docs/backlog/sprint-22-candidaturas-visitas-apoio-candidato.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 22.

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

docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
docs/backlog/sprint-18-rgpd-seguranca-auditoria-avancada.md
docs/backlog/sprint-19-testes-integrados-qualidade.md
docs/backlog/sprint-20-portal-publico-oferta-habitacional.md
docs/backlog/sprint-21-simulador-avancado-registo-inteligente.md
docs/backlog/sprint-22-candidaturas-visitas-apoio-candidato.md

docs/qa/test-coverage-matrix.md
docs/qa/quality-gates.md
docs/qa/regression-test-plan.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

---

# 4. Inspeção inicial obrigatória

Antes de implementar, inspeciona o repositório e identifica:

```text
Versão do Laravel
Versão do PHP
Stack frontend real
Sistema de autenticação
Sistema de área pessoal do candidato
Sistema de roles/permissões
Sistema de policies
Sistema de Form Requests
Sistema de notificações
Sistema de auditoria
Sistema de RGPD/consentimentos
Sistema de candidaturas
Sistema de workflow administrativo
Sistema de portal público
Sistema de imóveis/habitações
Sistema de simulador Sprint 21
Sistema documental
Sistema de storage privado
Sistema de calendário existente, se existir
Sistema de tickets existente, se existir
Sistema de conversas/mensagens existente, se existir
Sistema de FAQ existente, se existir
Sistema de testes
Configuração PHPStan/Larastan
Configuração Pint
Configuração PHPUnit/Pest
Comandos disponíveis em composer.json
Comandos disponíveis em package.json
```

Inspeciona os modelos existentes:

```text
User
Citizen/Candidate, se existir
AdhesionRegistration
Application
ApplicationStatusHistory
ApplicationSnapshot
ApplicationPrefill
SimulationSession
SimulationResult
SimulationInputSnapshot
Contest
ContestHousingUnit
HousingUnit
HousingUnitImage
HousingUnitPublicDocument
HousingUnitVisit
VisitSchedule
VisitSlot
Ticket
TicketMessage
Faq
FaqCategory
OfficialNotification
CommunicationLog
AuditEvent
SensitiveDataAccessLog
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
Visit
VisitSlot
VisitAvailability
VisitBooking
SupportTicket
TicketMessage
CandidateConversation
ConversationMessage
InteractionHistory
ContextualFaq
SimulatorApplicationInconsistency
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não alterar `.env`.

Não introduzir credenciais.

Não usar dados pessoais reais.

Não criar integração externa obrigatória com Google Calendar, Outlook, SMS, WhatsApp, chatbot, Zendesk, Freshdesk, Intercom ou outro serviço externo.

Não criar funcionalidades fora do âmbito desta sprint.

---

# 5. PHPStan obrigatório antes de publicar — contexto com 2471 erros legados

O projeto tem atualmente:

```text
2471 erros PHPStan legados
```

A Sprint 22 não tem como objetivo corrigir todos os erros legados.

A Sprint 22 tem como objetivo obrigatório:

```text
Não aumentar o número de erros PHPStan.
Não introduzir novos erros PHPStan nos ficheiros criados ou alterados.
Identificar claramente erros legados versus erros introduzidos pela sprint.
Executar PHPStan antes da implementação e antes da publicação.
Corrigir todos os erros PHPStan diretamente causados pela Sprint 22.
```

## 5.1 Verificação PHPStan inicial

Antes de criar ou alterar ficheiros, executar, se PHPStan existir:

```bash
mkdir -p storage/phpstan

php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint22-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint22-before.txt || true
```

Se existir `phpstan.neon`, usar:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint22-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint22-before.txt || true
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

## 5.2 Estratégia para não misturar erros legados

Durante a implementação:

```text
Não corrigir erros PHPStan fora do âmbito da Sprint 22, salvo se bloquearem diretamente a sprint.
Não alterar ficheiros apenas para reduzir ruído PHPStan legado.
Não criar baseline artificial sem autorização.
Não esconder erros novos com ignoreErrors genéricos.
Não adicionar @phpstan-ignore sem justificação objetiva.
Não reduzir o nível do PHPStan.
Não remover paths analisados.
Não alterar configuração PHPStan para ocultar problemas.
```

## 5.3 Verificação PHPStan antes de publicação

Antes de considerar a Sprint 22 pronta para publicação, executar:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint22-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint22-after.txt || true
```

Com config, se existir:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint22-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint22-after.txt || true
```

Depois, identificar erros nos ficheiros criados ou alterados nesta sprint.

Se existirem erros PHPStan em ficheiros da Sprint 22:

```text
Corrigir antes de concluir.
Não publicar como concluído enquanto houver erro novo causado pela Sprint 22.
```

Se existirem apenas os 2471 erros legados:

```text
Documentar que o passivo PHPStan legado permanece.
Confirmar que a Sprint 22 não adicionou erros novos nos ficheiros alterados.
```

Se a contagem aumentar:

```text
Identificar ficheiros novos/alterados.
Corrigir erros introduzidos.
Reexecutar PHPStan.
Documentar diferença.
```

## 5.4 Resultado PHPStan obrigatório no relatório final

A resposta final deve incluir:

```text
Estado PHPStan inicial
Estado PHPStan antes de publicação
Contagem legada assumida: 2471
Novos erros introduzidos pela Sprint 22: sim/não
Erros PHPStan em ficheiros criados/alterados: sim/não
Correções PHPStan aplicadas
Bloqueia publicação: sim/não
```

---

# 6. Dependências funcionais

Esta sprint depende preferencialmente de:

```text
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 16 — Notificações, Comunicações e Modelos Documentais
Sprint 18 — RGPD, Segurança e Auditoria Avançada
Sprint 20 — Portal Público de Oferta Habitacional
Sprint 21 — Simulador Avançado e Registo Inteligente
```

Dependências mínimas:

```text
User
Application
Contest
HousingUnit
Área do candidato
Backoffice
```

Se a Sprint 20 existir:

```text
Usar imóveis/fogos publicados ou associados a concursos para visitas.
Não permitir visita a imóvel oculto ou sem autorização de visita.
```

Se a Sprint 21 existir:

```text
Comparar dados da simulação com dados da candidatura.
Gerar indicadores de inconsistência.
Mostrar alertas ao candidato e ao técnico.
```

Se a Sprint 16 existir:

```text
Usar notificações internas/oficiais para agendamento, reagendamento, cancelamento e tickets.
```

Se a Sprint 18 existir:

```text
Auditar acessos e interações sensíveis.
Respeitar logs e RGPD.
```

Se algum módulo não existir:

```text
Implementar camada tolerante a dependências parciais.
Documentar limitação.
Não inventar resultados definitivos.
Não criar integração externa para substituir módulo ausente.
```

---

# 7. Validação funcional, administrativa e RGPD

Regras obrigatórias:

```text
Visitas só podem ser agendadas por candidatos autorizados.
Candidato só vê visitas próprias.
Candidato só vê tickets próprios.
Técnico só vê tickets/visitas conforme permissões.
Conversas ficam historizadas.
Mensagens devem ser auditáveis.
FAQ contextual não substitui instruções oficiais do concurso.
Reagendamentos e cancelamentos devem respeitar prazos.
Dados pessoais devem ser minimizados.
Não expor contactos pessoais desnecessários.
Não enviar mensagens para canais externos sem configuração.
Não transformar tickets em chat público.
Não permitir anexos inseguros em tickets.
Não permitir acesso a conversas de terceiros.
```

Copy obrigatório no módulo de visitas:

```text
O agendamento de visita está sujeito à disponibilidade dos serviços municipais e poderá ser alterado ou cancelado por motivos operacionais. A confirmação será apresentada na plataforma e, quando aplicável, enviada por notificação.
```

Copy obrigatório no helpdesk:

```text
As mensagens trocadas neste canal ficam associadas ao seu processo e podem ser consultadas pelos serviços municipais para efeitos de acompanhamento, resposta e auditoria.
```

---

# 8. Objetivo da implementação

Implementar:

```text
Agendamento de visitas
Reagendamento
Cancelamento de visitas
Calendário integrado
Gestão de disponibilidade
Linha de apoio através de sistema de tickets
Conversação entre técnico e candidato
Histórico das interações
FAQ contextual
Indicadores de inconsistência entre simulador e candidatura
```

A plataforma deve permitir ao candidato:

```text
Consultar imóveis visitáveis associados à candidatura ou concurso
Solicitar/agendar visita
Reagendar visita dentro das regras definidas
Cancelar visita dentro das regras definidas
Ver calendário de visitas próprias
Receber notificações sobre visitas
Abrir tickets de apoio
Responder a conversas com técnicos
Consultar histórico completo de interações
Ver FAQ contextual durante a candidatura
Receber alertas quando dados da candidatura divergem da simulação
```

A plataforma deve permitir ao Município:

```text
Configurar disponibilidade para visitas
Configurar janelas horárias
Configurar capacidade máxima por visita
Aprovar, recusar, reagendar ou cancelar visitas
Acompanhar calendário de visitas
Atribuir técnicos a visitas
Gerir tickets
Responder a candidatos por conversa interna
Classificar pedidos de apoio
Consultar histórico processual de interações
Identificar inconsistências entre simulação e candidatura
Reduzir contactos dispersos por e-mail/telefone
Manter auditoria e rastreabilidade
```

---

# 9. Âmbito incluído

Implementar:

```text
Módulo de visitas
Agendamento de visitas
Reagendamento de visitas
Cancelamento de visitas
Calendário integrado interno
Gestão de disponibilidade
Bloqueios de horários
Capacidade máxima por slot
Atribuição de técnico/responsável
Histórico de visitas
Notificações de visita, se módulo existir
Linha de apoio através de tickets
Categorias de tickets
Prioridades de tickets
Estados de tickets
Conversação entre técnico e candidato
Mensagens internas
Histórico de interações
FAQ contextual
FAQ por módulo/estado da candidatura
FAQ por concurso, se aplicável
Indicadores de inconsistência entre simulador e candidatura
Alertas de inconsistência
Backoffice de visitas
Backoffice de tickets
Backoffice de FAQ contextual
Services
Controllers
Form Requests
Policies
Views/páginas
Rotas
Factories
Seeders
Testes
Documentação
PHPStan antes/depois
```

---

# 10. Fora de âmbito

Não implementar nesta sprint:

```text
Integração real com Google Calendar
Integração real com Outlook Calendar
Integração real com agenda municipal externa
Integração real com SMS
Integração real com WhatsApp
Integração real com Zendesk/Freshdesk/Intercom
Chat em tempo real com WebSockets
Videochamada
Sistema de senhas presencial
Agendamento de assinatura de contrato
Agendamento de vistoria técnica pós-atribuição
Pagamento online
Call center externo
Bot de IA
Classificação automática jurídica de tickets
```

O calendário deve ser interno à plataforma, com possibilidade de futura integração externa.

---

# 11. Fluxos funcionais obrigatórios

## 11.1 Agendamento de visita

```text
Candidato entra na área pessoal
→ Seleciona candidatura/concurso/imóvel visitável
→ Sistema mostra slots disponíveis
→ Candidato escolhe data/hora
→ Sistema valida disponibilidade e elegibilidade
→ Sistema cria pedido/reserva de visita
→ Sistema confirma ou coloca em pendente conforme regra
→ Sistema notifica candidato e técnico, se notificações existirem
→ Visita fica no calendário interno
```

## 11.2 Reagendamento

```text
Candidato consulta visita agendada
→ Solicita reagendamento
→ Sistema valida prazo mínimo
→ Sistema mostra novos slots
→ Candidato escolhe novo horário
→ Sistema atualiza visita
→ Sistema guarda histórico
→ Sistema notifica intervenientes
```

## 11.3 Cancelamento

```text
Candidato consulta visita agendada
→ Solicita cancelamento
→ Sistema valida regras
→ Candidato indica motivo
→ Sistema cancela visita
→ Slot fica disponível ou bloqueado conforme regra
→ Sistema guarda histórico
→ Sistema notifica intervenientes
```

## 11.4 Gestão de disponibilidade

```text
Técnico/admin acede ao backoffice
→ Define janelas de disponibilidade
→ Define duração dos slots
→ Define capacidade por slot
→ Define imóveis/concurso aplicáveis
→ Define técnico responsável
→ Sistema gera slots
→ Candidatos podem agendar conforme regras
```

## 11.5 Ticket de apoio

```text
Candidato abre ticket
→ Escolhe categoria/contexto
→ Escreve mensagem
→ Sistema associa ao utilizador/candidatura/concurso
→ Sistema cria ticket
→ Técnico responde
→ Candidato responde
→ Sistema mantém histórico
→ Ticket é resolvido/fechado
```

## 11.6 FAQ contextual

```text
Candidato está numa etapa da candidatura
→ Sistema identifica contexto
→ Sistema apresenta perguntas frequentes relevantes
→ Candidato consulta resposta
→ Se não resolver, pode abrir ticket já contextualizado
```

## 11.7 Inconsistência entre simulador e candidatura

```text
Candidato cria ou atualiza candidatura
→ Sistema compara com última simulação relevante
→ Sistema identifica divergências
→ Sistema classifica severidade
→ Sistema mostra alertas
→ Técnico visualiza indicadores no backoffice
→ Candidato pode confirmar alteração ou corrigir dados
```

---

# 12. Estados e tipos obrigatórios

## VisitStatus

```text
draft
requested
pending_confirmation
confirmed
rescheduled
cancelled_by_candidate
cancelled_by_staff
completed
missed
rejected
expired
```

## VisitSlotStatus

```text
available
reserved
full
blocked
cancelled
expired
```

## VisitCancellationReason

```text
candidate_unavailable
municipal_service_unavailable
property_unavailable
duplicate_booking
operational_reason
other
```

## TicketStatus

```text
open
pending_candidate
pending_staff
in_progress
resolved
closed
cancelled
reopened
```

## TicketPriority

```text
low
normal
high
urgent
```

## TicketCategory

```text
application
documents
eligibility
visits
technical_issue
contest_information
notifications
rgpd
other
```

## MessageVisibility

```text
candidate_visible
internal_only
system
```

## InteractionType

```text
visit_scheduled
visit_rescheduled
visit_cancelled
visit_completed
ticket_created
ticket_message_sent
ticket_resolved
faq_viewed
inconsistency_detected
notification_sent
```

## InconsistencySeverity

```text
info
warning
blocking
requires_review
```

## InconsistencyType

```text
household_size_changed
income_changed
housing_situation_changed
preferred_typology_changed
preferred_parish_changed
documents_missing
eligibility_result_changed
rent_estimate_changed
contest_no_longer_matching
simulation_outdated
```

---

# 13. Modelo de dados a implementar

## 13.1 VisitAvailability

Criar entidade:

```text
VisitAvailability
```

Tabela:

```text
visit_availabilities
```

Campos mínimos:

```text
id
contest_id nullable
housing_unit_id nullable
staff_user_id nullable

title
description
starts_at
ends_at
slot_duration_minutes
capacity_per_slot
buffer_minutes
timezone

is_active
created_by
updated_by

created_at
updated_at
deleted_at
```

Regras:

```text
Pode estar associada a concurso, imóvel ou disponibilidade geral.
Não gerar slots fora do intervalo definido.
Capacidade deve ser positiva.
timezone deve respeitar app timezone por defeito.
```

## 13.2 VisitSlot

Criar entidade:

```text
VisitSlot
```

Tabela:

```text
visit_slots
```

Campos mínimos:

```text
id
visit_availability_id
contest_id nullable
housing_unit_id nullable
staff_user_id nullable

starts_at
ends_at
status
capacity
booked_count
location
meeting_point
notes

created_at
updated_at
deleted_at
```

Regras:

```text
booked_count nunca pode exceder capacity.
Slots bloqueados não podem ser reservados.
Slots expirados não devem aceitar reservas.
```

## 13.3 HousingVisit

Criar entidade:

```text
HousingVisit
```

Tabela:

```text
housing_visits
```

Campos mínimos:

```text
id
visit_number
visit_slot_id nullable
application_id nullable
contest_id nullable
housing_unit_id nullable
candidate_user_id
staff_user_id nullable

status
scheduled_at
starts_at
ends_at
confirmed_at
completed_at
cancelled_at
cancelled_by nullable
cancellation_reason nullable
cancellation_notes nullable
rescheduled_from_id nullable

candidate_notes
staff_notes
location
meeting_point

created_at
updated_at
deleted_at
```

Regras:

```text
visit_number obrigatório e único.
Candidato só pode ver as suas visitas.
Visita deve estar associada a candidatura, concurso ou imóvel.
Não permitir dupla reserva do mesmo candidato no mesmo slot.
Não permitir visita a imóvel/concurso não visitável.
```

## 13.4 HousingVisitStatusHistory

Criar entidade:

```text
HousingVisitStatusHistory
```

Tabela:

```text
housing_visit_status_histories
```

Campos:

```text
id
housing_visit_id
from_status nullable
to_status
changed_by nullable
reason nullable
notes nullable
changed_at
created_at
```

## 13.5 SupportTicket

Criar entidade:

```text
SupportTicket
```

Tabela:

```text
support_tickets
```

Campos mínimos:

```text
id
ticket_number
user_id
application_id nullable
contest_id nullable
housing_unit_id nullable
assigned_to nullable

category
priority
status
subject
description
context
resolved_at
closed_at
last_message_at

created_at
updated_at
deleted_at
```

Regras:

```text
ticket_number obrigatório e único.
Candidato só vê tickets próprios.
Técnico vê conforme permissões.
description pode conter dados pessoais; proteger acesso.
```

## 13.6 SupportTicketMessage

Criar entidade:

```text
SupportTicketMessage
```

Tabela:

```text
support_ticket_messages
```

Campos:

```text
id
support_ticket_id
sender_user_id nullable
visibility
message
metadata
read_by_candidate_at nullable
read_by_staff_at nullable
created_at
updated_at
deleted_at
```

Regras:

```text
Mensagens internal_only não aparecem ao candidato.
Mensagens system são geradas pela plataforma.
Não permitir HTML inseguro.
Sanitizar conteúdo renderizado.
```

## 13.7 SupportTicketAttachment

Criar entidade se necessário:

```text
SupportTicketAttachment
```

Tabela:

```text
support_ticket_attachments
```

Campos:

```text
id
support_ticket_id
support_ticket_message_id nullable
uploaded_by
filename
original_filename
path
mime_type
size_bytes
checksum
is_private
created_at
updated_at
deleted_at
```

Regras:

```text
Storage privado.
Download via controller autorizado.
Não aceitar ficheiros executáveis.
Não expor path.
```

## 13.8 CandidateInteraction

Criar entidade:

```text
CandidateInteraction
```

Tabela:

```text
candidate_interactions
```

Campos:

```text
id
user_id
application_id nullable
contest_id nullable
housing_unit_id nullable
interaction_type
related_type nullable
related_id nullable
title
description
metadata
occurred_at
created_by nullable
created_at
```

Objetivo:

```text
Criar histórico unificado de visitas, tickets, mensagens, FAQ, notificações e inconsistências relevantes.
```

## 13.9 ContextualFaqCategory

Criar ou adaptar entidade:

```text
ContextualFaqCategory
```

Tabela:

```text
contextual_faq_categories
```

Campos:

```text
id
code
name
description
sort_order
is_active
created_at
updated_at
deleted_at
```

## 13.10 ContextualFaq

Criar ou adaptar entidade:

```text
ContextualFaq
```

Tabela:

```text
contextual_faqs
```

Campos:

```text
id
contextual_faq_category_id nullable
contest_id nullable
context_key
question
answer
keywords
sort_order
is_active
published_at
created_by
updated_by
created_at
updated_at
deleted_at
```

Context keys recomendados:

```text
application_draft
application_submitted
documents_pending
documents_rejected
eligibility_simulation
visit_scheduling
ticket_creation
correction_request
complaint
allocation
contract
payment
maintenance
rgpd
```

## 13.11 ApplicationSimulationInconsistency

Criar entidade:

```text
ApplicationSimulationInconsistency
```

Tabela:

```text
application_simulation_inconsistencies
```

Campos:

```text
id
application_id
simulation_session_id nullable
user_id

type
severity
field_name nullable
simulation_value
application_value
message
recommendation
is_resolved
resolved_by nullable
resolved_at nullable

created_at
updated_at
deleted_at
```

Regras:

```text
Não bloquear automaticamente candidatura salvo regra expressa.
Alertar candidato e técnico.
Guardar valores com cuidado; mascarar dados sensíveis quando necessário.
```

---

# 14. Índices e performance

Adicionar índices seguros:

```text
visit_availabilities.contest_id
visit_availabilities.housing_unit_id
visit_availabilities.staff_user_id
visit_availabilities.starts_at
visit_availabilities.ends_at
visit_availabilities.is_active

visit_slots.visit_availability_id
visit_slots.contest_id
visit_slots.housing_unit_id
visit_slots.staff_user_id
visit_slots.starts_at
visit_slots.status

housing_visits.visit_number unique
housing_visits.candidate_user_id
housing_visits.application_id
housing_visits.contest_id
housing_visits.housing_unit_id
housing_visits.status
housing_visits.starts_at

support_tickets.ticket_number unique
support_tickets.user_id
support_tickets.application_id
support_tickets.status
support_tickets.category
support_tickets.priority
support_tickets.assigned_to
support_tickets.last_message_at

support_ticket_messages.support_ticket_id
support_ticket_messages.sender_user_id
support_ticket_messages.created_at

candidate_interactions.user_id
candidate_interactions.application_id
candidate_interactions.interaction_type
candidate_interactions.occurred_at

contextual_faqs.context_key
contextual_faqs.contest_id
contextual_faqs.is_active

application_simulation_inconsistencies.application_id
application_simulation_inconsistencies.user_id
application_simulation_inconsistencies.type
application_simulation_inconsistencies.severity
application_simulation_inconsistencies.is_resolved
```

Migrations devem ser reversíveis.

Não adicionar índices duplicados.

---

# 15. Services obrigatórios

Criar namespaces:

```text
App\Services\Visits
App\Services\Support
App\Services\CandidateExperience
```

Criar services:

```text
App\Services\Visits\VisitAvailabilityService
App\Services\Visits\VisitSlotGenerationService
App\Services\Visits\VisitBookingService
App\Services\Visits\VisitReschedulingService
App\Services\Visits\VisitCancellationService
App\Services\Visits\VisitCalendarService
App\Services\Visits\VisitNotificationService
App\Services\Visits\VisitAuditService

App\Services\Support\SupportTicketService
App\Services\Support\SupportTicketMessageService
App\Services\Support\SupportTicketAttachmentService
App\Services\Support\SupportTicketAssignmentService
App\Services\Support\SupportTicketStatusService
App\Services\Support\ContextualFaqService

App\Services\CandidateExperience\CandidateInteractionService
App\Services\CandidateExperience\ApplicationSimulationConsistencyService
App\Services\CandidateExperience\CandidateSupportDashboardService
```

## 15.1 VisitAvailabilityService

Responsável por:

```text
Criar disponibilidade
Atualizar disponibilidade
Ativar/desativar disponibilidade
Validar conflitos
Associar a concurso/imóvel/técnico
```

## 15.2 VisitSlotGenerationService

Responsável por:

```text
Gerar slots a partir de disponibilidade
Respeitar duração
Respeitar buffer
Respeitar capacidade
Evitar duplicados
Bloquear slots fora do horário
```

## 15.3 VisitBookingService

Responsável por:

```text
Validar candidato
Validar candidatura/concurso/imóvel
Validar slot disponível
Reservar slot de forma transacional
Incrementar booked_count
Criar HousingVisit
Criar histórico
Criar interação
Notificar, se possível
```

A reserva deve ser transacional.

Não permitir overbooking.

## 15.4 VisitReschedulingService

Responsável por:

```text
Validar visita existente
Validar prazo mínimo
Libertar slot anterior
Reservar novo slot
Criar nova visita ou atualizar existente conforme regra
Criar histórico
Criar interação
Notificar intervenientes
```

## 15.5 VisitCancellationService

Responsável por:

```text
Validar autorização
Validar prazo mínimo
Registar motivo
Atualizar status
Libertar capacidade
Criar histórico
Criar interação
Notificar intervenientes
```

## 15.6 VisitCalendarService

Responsável por:

```text
Listar visitas por período
Listar por técnico
Listar por imóvel
Listar por concurso
Gerar payload de calendário interno
Evitar exposição de dados indevidos
```

## 15.7 SupportTicketService

Responsável por:

```text
Criar ticket
Gerar número único
Associar candidatura/concurso/imóvel
Classificar categoria e prioridade
Atribuir técnico se aplicável
Alterar estado
Resolver/reabrir/fechar
Criar interação
Notificar intervenientes
```

## 15.8 SupportTicketMessageService

Responsável por:

```text
Adicionar mensagem
Validar visibilidade
Marcar como lida
Criar interação
Proteger mensagens internas
Notificar destinatário
Sanitizar conteúdo
```

## 15.9 ContextualFaqService

Responsável por:

```text
Resolver FAQ por contexto
Resolver FAQ por concurso
Resolver FAQ por etapa da candidatura
Ordenar por relevância
Registar visualização agregada, se aplicável
Gerar sugestões de ticket
```

## 15.10 ApplicationSimulationConsistencyService

Responsável por:

```text
Carregar última simulação relevante
Comparar com candidatura atual
Comparar agregado
Comparar rendimentos
Comparar situação habitacional
Comparar preferências
Comparar tipologia
Comparar estimativa de renda
Gerar inconsistências
Classificar severidade
Marcar inconsistências como resolvidas
```

## 15.11 CandidateInteractionService

Responsável por:

```text
Registar interação
Listar histórico por candidato
Listar histórico por candidatura
Listar histórico para backoffice
Filtrar por tipo
Proteger dados internos
```

---

# 16. Controllers obrigatórios

Criar ou completar:

```text
App\Http\Controllers\Candidate\VisitController
App\Http\Controllers\Candidate\SupportTicketController
App\Http\Controllers\Candidate\SupportTicketMessageController
App\Http\Controllers\Candidate\CandidateInteractionController
App\Http\Controllers\Candidate\ContextualFaqController

App\Http\Controllers\Backoffice\VisitAvailabilityController
App\Http\Controllers\Backoffice\VisitSlotController
App\Http\Controllers\Backoffice\HousingVisitController
App\Http\Controllers\Backoffice\SupportTicketController
App\Http\Controllers\Backoffice\SupportTicketMessageController
App\Http\Controllers\Backoffice\ContextualFaqController
App\Http\Controllers\Backoffice\ApplicationSimulationInconsistencyController
```

Controllers devem ser magros e delegar regras para Services.

Não implementar lógica transacional crítica diretamente em controllers.

---

# 17. Form Requests obrigatórios

Criar:

```text
StoreVisitAvailabilityRequest
UpdateVisitAvailabilityRequest
GenerateVisitSlotsRequest
BookVisitRequest
RescheduleVisitRequest
CancelVisitRequest
CompleteVisitRequest
RejectVisitRequest

StoreSupportTicketRequest
UpdateSupportTicketRequest
AssignSupportTicketRequest
StoreSupportTicketMessageRequest
UpdateSupportTicketStatusRequest
StoreSupportTicketAttachmentRequest

StoreContextualFaqRequest
UpdateContextualFaqRequest
ResolveApplicationSimulationInconsistencyRequest
```

## 17.1 BookVisitRequest

```php
'application_id' => ['nullable', 'exists:applications,id'],
'contest_id' => ['nullable', 'exists:contests,id'],
'housing_unit_id' => ['nullable', 'exists:housing_units,id'],
'visit_slot_id' => ['required', 'exists:visit_slots,id'],
'candidate_notes' => ['nullable', 'string', 'max:2000'],
```

## 17.2 RescheduleVisitRequest

```php
'new_visit_slot_id' => ['required', 'exists:visit_slots,id'],
'reason' => ['nullable', 'string', 'max:1000'],
```

## 17.3 CancelVisitRequest

```php
'cancellation_reason' => ['required', 'string', 'max:100'],
'cancellation_notes' => ['nullable', 'string', 'max:1000'],
```

## 17.4 StoreSupportTicketRequest

```php
'application_id' => ['nullable', 'exists:applications,id'],
'contest_id' => ['nullable', 'exists:contests,id'],
'housing_unit_id' => ['nullable', 'exists:housing_units,id'],
'category' => ['required', 'string', 'max:100'],
'priority' => ['nullable', 'string', 'max:50'],
'subject' => ['required', 'string', 'max:180'],
'description' => ['required', 'string', 'min:10', 'max:10000'],
'context' => ['nullable', 'array'],
```

## 17.5 StoreSupportTicketMessageRequest

```php
'message' => ['required', 'string', 'min:1', 'max:10000'],
'visibility' => ['nullable', 'string', 'max:50'],
```

## 17.6 StoreContextualFaqRequest

```php
'contextual_faq_category_id' => ['nullable', 'exists:contextual_faq_categories,id'],
'contest_id' => ['nullable', 'exists:contests,id'],
'context_key' => ['required', 'string', 'max:100'],
'question' => ['required', 'string', 'max:255'],
'answer' => ['required', 'string', 'max:10000'],
'keywords' => ['nullable', 'array'],
'sort_order' => ['nullable', 'integer', 'min:0'],
'is_active' => ['boolean'],
```

---

# 18. Policies obrigatórias

Criar ou completar:

```text
VisitAvailabilityPolicy
VisitSlotPolicy
HousingVisitPolicy
SupportTicketPolicy
SupportTicketMessagePolicy
SupportTicketAttachmentPolicy
CandidateInteractionPolicy
ContextualFaqPolicy
ApplicationSimulationInconsistencyPolicy
```

Regras:

```text
Guest não acede a visitas nem tickets.
Candidato só vê visitas próprias.
Candidato só agenda visitas para candidatura/concurso/imóvel permitido.
Candidato só cancela/reagenda visitas próprias dentro das regras.
Candidato só vê tickets próprios.
Candidato só responde a tickets próprios não fechados.
Candidato não vê mensagens internal_only.
Técnico vê visitas/tickets conforme permissões.
Auditor consulta histórico sem alterar.
Admin gere disponibilidade, FAQ e configurações.
```

---

# 19. Rotas da área do candidato

Adicionar, adaptando à estrutura real:

```php
Route::middleware(['auth'])->prefix('area-candidato')->name('candidate.')->group(function (): void {
    Route::get('/visitas', [VisitController::class, 'index'])->name('visits.index');
    Route::get('/visitas/agendar', [VisitController::class, 'create'])->name('visits.create');
    Route::post('/visitas', [VisitController::class, 'store'])->name('visits.store');
    Route::get('/visitas/{housingVisit}', [VisitController::class, 'show'])->name('visits.show');
    Route::get('/visitas/{housingVisit}/reagendar', [VisitController::class, 'edit'])->name('visits.reschedule.edit');
    Route::post('/visitas/{housingVisit}/reagendar', [VisitController::class, 'reschedule'])->name('visits.reschedule');
    Route::post('/visitas/{housingVisit}/cancelar', [VisitController::class, 'cancel'])->name('visits.cancel');

    Route::get('/apoio', [SupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('/apoio/criar', [SupportTicketController::class, 'create'])->name('support-tickets.create');
    Route::post('/apoio', [SupportTicketController::class, 'store'])->name('support-tickets.store');
    Route::get('/apoio/{supportTicket}', [SupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::post('/apoio/{supportTicket}/mensagens', [SupportTicketMessageController::class, 'store'])->name('support-ticket-messages.store');

    Route::get('/interacoes', [CandidateInteractionController::class, 'index'])->name('interactions.index');
    Route::get('/faq-contextual', [ContextualFaqController::class, 'index'])->name('contextual-faq.index');
});
```

Todas as rotas devem usar policies.

---

# 20. Rotas de backoffice

Adicionar, adaptando à estrutura real:

```php
Route::middleware(['auth'])->prefix('backoffice')->name('backoffice.')->group(function (): void {
    Route::resource('/visit-availabilities', VisitAvailabilityController::class);
    Route::post('/visit-availabilities/{visitAvailability}/generate-slots', [VisitAvailabilityController::class, 'generateSlots'])
        ->name('visit-availabilities.generate-slots');

    Route::get('/visit-slots', [VisitSlotController::class, 'index'])->name('visit-slots.index');
    Route::patch('/visit-slots/{visitSlot}/block', [VisitSlotController::class, 'block'])->name('visit-slots.block');
    Route::patch('/visit-slots/{visitSlot}/unblock', [VisitSlotController::class, 'unblock'])->name('visit-slots.unblock');

    Route::get('/housing-visits', [HousingVisitController::class, 'index'])->name('housing-visits.index');
    Route::get('/housing-visits/{housingVisit}', [HousingVisitController::class, 'show'])->name('housing-visits.show');
    Route::post('/housing-visits/{housingVisit}/confirm', [HousingVisitController::class, 'confirm'])->name('housing-visits.confirm');
    Route::post('/housing-visits/{housingVisit}/complete', [HousingVisitController::class, 'complete'])->name('housing-visits.complete');
    Route::post('/housing-visits/{housingVisit}/cancel', [HousingVisitController::class, 'cancel'])->name('housing-visits.cancel');
    Route::post('/housing-visits/{housingVisit}/reject', [HousingVisitController::class, 'reject'])->name('housing-visits.reject');

    Route::get('/support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('/support-tickets/{supportTicket}', [SupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::post('/support-tickets/{supportTicket}/assign', [SupportTicketController::class, 'assign'])->name('support-tickets.assign');
    Route::post('/support-tickets/{supportTicket}/status', [SupportTicketController::class, 'updateStatus'])->name('support-tickets.status');
    Route::post('/support-tickets/{supportTicket}/messages', [SupportTicketMessageController::class, 'store'])->name('support-ticket-messages.store');

    Route::resource('/contextual-faqs', ContextualFaqController::class);

    Route::get('/application-inconsistencies', [ApplicationSimulationInconsistencyController::class, 'index'])
        ->name('application-inconsistencies.index');
    Route::post('/application-inconsistencies/{applicationSimulationInconsistency}/resolve', [ApplicationSimulationInconsistencyController::class, 'resolve'])
        ->name('application-inconsistencies.resolve');
});
```

Todas as rotas devem respeitar middleware, policies e convenções existentes.

---

# 21. Views / páginas obrigatórias

Se o projeto usa Blade, criar:

```text
resources/views/candidate/visits/index.blade.php
resources/views/candidate/visits/create.blade.php
resources/views/candidate/visits/show.blade.php
resources/views/candidate/visits/reschedule.blade.php

resources/views/candidate/support-tickets/index.blade.php
resources/views/candidate/support-tickets/create.blade.php
resources/views/candidate/support-tickets/show.blade.php

resources/views/candidate/interactions/index.blade.php
resources/views/candidate/contextual-faq/index.blade.php

resources/views/backoffice/visit-availabilities/index.blade.php
resources/views/backoffice/visit-availabilities/create.blade.php
resources/views/backoffice/visit-availabilities/edit.blade.php
resources/views/backoffice/visit-availabilities/show.blade.php

resources/views/backoffice/visit-slots/index.blade.php
resources/views/backoffice/housing-visits/index.blade.php
resources/views/backoffice/housing-visits/show.blade.php

resources/views/backoffice/support-tickets/index.blade.php
resources/views/backoffice/support-tickets/show.blade.php

resources/views/backoffice/contextual-faqs/index.blade.php
resources/views/backoffice/contextual-faqs/create.blade.php
resources/views/backoffice/contextual-faqs/edit.blade.php

resources/views/backoffice/application-inconsistencies/index.blade.php
```

Se o projeto usa Inertia/React/Vue, criar equivalentes.

Não mudar stack frontend.

---

# 22. UX obrigatória

## 22.1 Área de visitas do candidato

Mostrar:

```text
Visitas agendadas
Visitas disponíveis
Estado da visita
Data e hora
Local/ponto de encontro
Imóvel/concurso associado
Botão reagendar, se permitido
Botão cancelar, se permitido
Histórico de alterações
Aviso de regras
```

## 22.2 Backoffice de visitas

Mostrar:

```text
Calendário diário/semanal
Filtros por concurso
Filtros por imóvel
Filtros por técnico
Filtros por estado
Slots disponíveis
Slots ocupados
Slots bloqueados
Lista de candidatos agendados
Ações de confirmar/concluir/cancelar/rejeitar
```

## 22.3 Área de apoio do candidato

Mostrar:

```text
Lista de tickets
Estado
Categoria
Última mensagem
Criar novo pedido
Conversação
FAQ contextual
Histórico
```

## 22.4 Backoffice de tickets

Mostrar:

```text
Lista de tickets
Filtros por estado
Filtros por prioridade
Filtros por categoria
Filtros por técnico
Detalhe do ticket
Histórico de mensagens
Mensagens internas
Resposta ao candidato
Atribuição
Resolução/fecho
```

## 22.5 FAQ contextual

Mostrar:

```text
Perguntas relevantes para a etapa atual
Pesquisa simples
Categoria
Resposta clara
Botão “Ainda preciso de ajuda”
Ligação para abrir ticket contextualizado
```

## 22.6 Inconsistências

Mostrar ao candidato:

```text
Campo com divergência
Valor usado na simulação
Valor atual da candidatura
Explicação
Ação recomendada
```

Mostrar ao técnico:

```text
Candidatura
Simulação de origem
Tipo de divergência
Severidade
Recomendação
Estado resolvido/não resolvido
```

---

# 23. Regras de calendário e disponibilidade

Regras obrigatórias:

```text
Slots têm início e fim.
Slots têm capacidade.
Slots podem estar disponíveis, completos, bloqueados ou expirados.
Candidato não pode reservar slot completo.
Candidato não pode reservar slot passado.
Candidato não pode reservar dois slots iguais.
Reagendamento deve libertar capacidade anterior.
Cancelamento deve libertar capacidade conforme regra.
Backoffice pode bloquear slots.
Backoffice pode cancelar visitas por motivo operacional.
```

Regras configuráveis recomendadas:

```text
Prazo mínimo para reagendar
Prazo mínimo para cancelar
Número máximo de visitas ativas por candidatura
Número máximo de reagendamentos
Duração padrão da visita
Capacidade por slot
```

Não hardcodar regras se já existir sistema de settings.

---

# 24. Regras de tickets e conversação

Regras obrigatórias:

```text
Ticket pertence a um candidato.
Ticket pode estar associado a candidatura/concurso/imóvel.
Mensagens são ordenadas cronologicamente.
Mensagens internas não são visíveis ao candidato.
Tickets fechados não aceitam resposta do candidato, salvo reabertura.
Técnico pode atribuir, resolver, fechar ou reabrir conforme policy.
Candidato pode criar e responder a tickets próprios.
Conteúdo deve ser escapado/sanitizado.
```

Anexos, se implementados:

```text
Storage privado.
Download autorizado.
MIME e tamanho validados.
Não aceitar executáveis.
Não expor path.
```

---

# 25. Indicadores de inconsistência entre simulador e candidatura

Comparar pelo menos:

```text
Número de elementos do agregado
Número de adultos
Número de dependentes
Rendimento mensal/anual
Situação habitacional
Freguesia preferida
Tipologia pretendida
Resultado de elegibilidade
Estimativa de renda
Concurso recomendado versus concurso escolhido
```

Classificação:

```text
info — diferença menor ou apenas informativa
warning — diferença pode alterar resultado
requires_review — diferença exige análise técnica
blocking — diferença impede avanço apenas se regra formal existir
```

Regras:

```text
Não bloquear automaticamente sem regra expressa.
Mostrar divergências de forma clara.
Permitir marcar como resolvida.
Guardar histórico.
Auditar resolução, se auditoria existir.
```

---

# 26. Notificações

Se Sprint 16 existir, emitir notificações para:

```text
Visita agendada
Visita confirmada
Visita reagendada
Visita cancelada
Visita concluída
Ticket criado
Nova resposta ao ticket
Ticket resolvido
Ticket reaberto
Inconsistência relevante detetada
```

Não enviar e-mail/SMS real sem configuração segura.

Se notificações não existirem, criar eventos internos ou documentar pendência.

---

# 27. Auditoria e histórico

Auditar, se existir auditoria:

```text
Criação de disponibilidade
Alteração de disponibilidade
Geração de slots
Bloqueio de slot
Agendamento de visita
Reagendamento de visita
Cancelamento de visita
Conclusão de visita
Criação de ticket
Resposta a ticket
Mensagem interna
Resolução de ticket
Reabertura de ticket
Criação/alteração de FAQ contextual
Deteção de inconsistência
Resolução de inconsistência
```

Registar em `CandidateInteraction`:

```text
Visitas
Tickets
Mensagens visíveis
FAQ visualizada, se aplicável
Inconsistências
Notificações críticas
```

---

# 28. RGPD e segurança

Regras obrigatórias:

```text
Candidato só acede aos seus dados.
Técnico só acede conforme policy.
Mensagens e tickets podem conter dados pessoais.
Não expor tickets em rotas públicas.
Não expor visitas em rotas públicas.
Não expor anexos sem controller autorizado.
Não guardar dados sensíveis em logs técnicos.
Não mostrar mensagens internas ao candidato.
Não permitir mass assignment de status, assigned_to, visibility ou user_id.
Não permitir alteração de visit_slot_id sem service transacional.
Não permitir overbooking.
```

---

# 29. Backoffice — indicadores mínimos

Criar indicadores simples no backoffice:

```text
Visitas agendadas
Visitas confirmadas
Visitas canceladas
Visitas concluídas
Taxa de não comparência
Slots disponíveis
Slots completos
Tickets abertos
Tickets pendentes do candidato
Tickets pendentes dos serviços
Tempo médio de resposta
Tickets por categoria
Inconsistências abertas
Inconsistências por severidade
```

Pode ser página simples sem BI avançado.

---

# 30. Factories e seeders

Criar factories:

```text
VisitAvailabilityFactory
VisitSlotFactory
HousingVisitFactory
HousingVisitStatusHistoryFactory
SupportTicketFactory
SupportTicketMessageFactory
SupportTicketAttachmentFactory
CandidateInteractionFactory
ContextualFaqCategoryFactory
ContextualFaqFactory
ApplicationSimulationInconsistencyFactory
```

Criar seeder opcional:

```text
Database\Seeders\CandidateSupportDemoSeeder
```

Dados fictícios:

```text
Disponibilidade de visitas
Slots disponíveis
Slots completos
Visita agendada
Visita reagendada
Visita cancelada
Ticket aberto
Ticket em resposta
Ticket resolvido
FAQ contextual
Inconsistência entre simulação e candidatura
```

Não usar dados reais.

---

# 31. Testes obrigatórios

Criar ou completar testes.

## 31.1 Testes de visitas

```text
tests/Feature/Candidate/VisitBookingTest.php
tests/Feature/Candidate/VisitReschedulingTest.php
tests/Feature/Candidate/VisitCancellationTest.php
tests/Feature/Backoffice/VisitAvailabilityManagementTest.php
tests/Unit/Visits/VisitSlotGenerationServiceTest.php
tests/Unit/Visits/VisitBookingServiceTest.php
```

Cobrir:

```text
Candidato vê slots disponíveis
Candidato agenda visita
Candidato não agenda slot completo
Candidato não agenda slot passado
Candidato não agenda visita de terceiro
Candidato reagenda visita própria
Reagendamento liberta slot anterior
Cancelamento liberta slot
Backoffice cria disponibilidade
Backoffice gera slots
Backoffice bloqueia slot
Backoffice confirma visita
Backoffice conclui visita
Overbooking é impedido por transação
```

## 31.2 Testes de tickets

```text
tests/Feature/Candidate/SupportTicketTest.php
tests/Feature/Candidate/SupportTicketConversationTest.php
tests/Feature/Backoffice/SupportTicketManagementTest.php
tests/Unit/Support/SupportTicketServiceTest.php
```

Cobrir:

```text
Candidato cria ticket
Candidato vê apenas tickets próprios
Candidato responde a ticket próprio
Candidato não vê mensagem interna
Técnico responde ao candidato
Técnico cria mensagem interna
Técnico atribui ticket
Técnico resolve ticket
Ticket fechado bloqueia resposta do candidato
Auditor consulta sem alterar
```

## 31.3 Testes de FAQ contextual

```text
tests/Feature/Candidate/ContextualFaqTest.php
tests/Feature/Backoffice/ContextualFaqManagementTest.php
tests/Unit/Support/ContextualFaqServiceTest.php
```

Cobrir:

```text
FAQ aparece por contexto
FAQ inativa não aparece
FAQ por concurso aparece no concurso certo
Candidato abre ticket a partir da FAQ
Admin cria FAQ contextual
Auditor não altera FAQ
```

## 31.4 Testes de inconsistências

```text
tests/Feature/Candidate/ApplicationSimulationInconsistencyTest.php
tests/Feature/Backoffice/ApplicationSimulationInconsistencyManagementTest.php
tests/Unit/CandidateExperience/ApplicationSimulationConsistencyServiceTest.php
```

Cobrir:

```text
Diferença de agregado gera inconsistência
Diferença de rendimento gera inconsistência
Diferença de tipologia gera inconsistência
Simulação desatualizada gera warning
Candidato vê inconsistências próprias
Técnico vê inconsistências conforme permissão
Técnico marca inconsistência como resolvida
Candidato não vê inconsistência de terceiro
```

## 31.5 Testes de segurança/RGPD

```text
tests/Feature/Security/CandidateSupportPrivacyTest.php
```

Cobrir:

```text
Guest não acede a visitas
Guest não acede a tickets
Candidato não vê visita de terceiro
Candidato não vê ticket de terceiro
Candidato não vê mensagem interna
Anexo privado não é descarregado sem autorização
Mass assignment de status é bloqueado
Mass assignment de assigned_to é bloqueado
Mass assignment de user_id é bloqueado
```

---

# 32. PHPStan específico da Sprint 22

Após implementar testes e código:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint22-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint22-after.txt || true
```

Verificar especialmente ficheiros novos:

```text
app/Models/VisitAvailability.php
app/Models/VisitSlot.php
app/Models/HousingVisit.php
app/Models/HousingVisitStatusHistory.php
app/Models/SupportTicket.php
app/Models/SupportTicketMessage.php
app/Models/SupportTicketAttachment.php
app/Models/CandidateInteraction.php
app/Models/ContextualFaq.php
app/Models/ApplicationSimulationInconsistency.php
app/Services/Visits/*
app/Services/Support/*
app/Services/CandidateExperience/*
app/Http/Controllers/Candidate/*
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
/** @return BelongsTo<User, HousingVisit> */
public function candidate(): BelongsTo
{
    return $this->belongsTo(User::class, 'candidate_user_id');
}
```

Em arrays estruturados, usar PHPDoc:

```php
/** @return array{available: int, booked: int, blocked: int} */
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
docs/backlog/sprint-22-candidaturas-visitas-apoio-candidato.md
docs/candidate-experience/visits.md
docs/candidate-experience/support-tickets.md
docs/candidate-experience/contextual-faq.md
docs/candidate-experience/simulation-application-inconsistencies.md
docs/qa/test-coverage-matrix.md
docs/qa/sprint-22-quality-report.md
docs/backlog/roadmap.md
```

## docs/candidate-experience/visits.md

Incluir:

```text
Objetivo
Fluxo de agendamento
Fluxo de reagendamento
Fluxo de cancelamento
Estados
Regras de disponibilidade
Permissões
Notificações
Limitações
```

## docs/candidate-experience/support-tickets.md

Incluir:

```text
Objetivo
Categorias
Prioridades
Estados
Conversação
Mensagens internas
Anexos
Histórico
Permissões
Limitações
```

## docs/candidate-experience/contextual-faq.md

Incluir:

```text
Objetivo
Contextos
Categorias
Associação a concurso
Fluxo para abertura de ticket
Gestão backoffice
```

## docs/candidate-experience/simulation-application-inconsistencies.md

Incluir:

```text
Objetivo
Campos comparados
Tipos de inconsistência
Severidades
Ações recomendadas
Regras de resolução
Limitações
```

## docs/qa/sprint-22-quality-report.md

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

A Sprint 22 está concluída quando:

```text
Existe módulo de visitas.
Candidato consegue agendar visita.
Candidato consegue reagendar visita.
Candidato consegue cancelar visita.
Existe calendário interno de visitas.
Backoffice consegue gerir disponibilidade.
Backoffice consegue gerar/bloquear slots.
Sistema impede overbooking.
Sistema impede acesso a visitas de terceiros.
Existe sistema de tickets integrado.
Candidato consegue abrir ticket.
Candidato consegue responder a ticket próprio.
Técnico consegue responder ao candidato.
Mensagens internas não aparecem ao candidato.
Histórico de interações é registado.
FAQ contextual aparece na área do candidato.
FAQ contextual permite abrir ticket com contexto.
Existem indicadores de inconsistência entre simulador e candidatura.
Candidato vê inconsistências próprias.
Técnico vê inconsistências conforme permissão.
Inconsistências podem ser resolvidas.
Notificações são emitidas se módulo existir.
Auditoria é criada se módulo existir.
Dados pessoais não são expostos indevidamente.
PHPStan foi executado antes da implementação, se disponível.
PHPStan foi executado antes da publicação, se disponível.
Foram considerados os 2471 erros legados.
Sprint 22 não introduz erros PHPStan novos nos ficheiros alterados.
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

# 36. Resposta final obrigatória

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Estado PHPStan inicial
4. Estado PHPStan antes de publicação
5. Erros PHPStan legados considerados: 2471
6. Novos erros PHPStan introduzidos pela Sprint 22: sim/não
7. Models criados ou alterados
8. Migrations criadas
9. Services criados ou alterados
10. Controllers criados ou alterados
11. Form Requests criados ou alterados
12. Policies criadas ou alteradas
13. Rotas da área do candidato criadas ou alteradas
14. Rotas de backoffice criadas ou alteradas
15. Views/components criados ou alterados
16. Estado do módulo de visitas
17. Estado do calendário interno
18. Estado da gestão de disponibilidade
19. Estado do reagendamento/cancelamento
20. Estado do helpdesk/tickets
21. Estado da conversação técnico-candidato
22. Estado do histórico de interações
23. Estado da FAQ contextual
24. Estado dos indicadores de inconsistência
25. Estado das notificações/auditoria
26. Testes criados ou alterados
27. Resultado de php artisan route:list
28. Resultado de php artisan test
29. Resultado de php artisan migrate, se aplicável
30. Resultado de npm run build, se aplicável
31. Resultado de ./vendor/bin/pint, se aplicável
32. Resultado de PHPStan/Psalm, se aplicável
33. Riscos ainda existentes
34. Pendências técnicas
35. Confirmação de que não foram usados dados pessoais reais
36. Confirmação de que não foram usadas credenciais
37. Confirmação de que não foram implementadas funcionalidades fora de âmbito
38. Recomendação objetiva para avançar ou não para Sprint 23
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 37. Definition of Done

A Sprint 22 só está concluída quando existir um módulo integrado de visitas, calendário interno, disponibilidade, reagendamento, cancelamento, helpdesk/tickets, conversação técnico-candidato, histórico de interações, FAQ contextual e indicadores de inconsistência entre simulador e candidatura, com permissões, RGPD, auditoria, testes e validação PHPStan sem aumento do passivo legado de 2471 erros.

---

# 38. Execução imediata

Executa agora apenas:

```text
Sprint 22 — Candidaturas, Visitas e Apoio ao Candidato
```

Usa como referência principal:

```text
docs/backlog/sprint-22-candidaturas-visitas-apoio-candidato.md
```

Fim da master prompt da Sprint 22.
