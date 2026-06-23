# Sprint 24 — Backoffice Operacional e Gestão do Procedimento

## Prioridade de desenvolvimento

Esta sprint pertence à fase de reforço operacional do backoffice municipal, com foco em eficiência administrativa, controlo do procedimento, indicadores, automatizações, gestão documental, minutas, listas, atas e confirmações processuais.

A Sprint 24 deve consolidar a capacidade da Câmara Municipal para gerir concursos, candidaturas, visitas, documentos, relatórios, listas provisórias/finais, atas e comunicações oficiais com maior rigor, rastreabilidade e produtividade.

Esta sprint deve preservar os módulos existentes de:

```text
Registo de adesão
Simulador avançado
Portal público de oferta habitacional
Candidaturas
Gestão documental
Workflow administrativo
Acompanhamento processual
Visitas
Tickets
Notificações
Audiência prévia
Reclamações
Listas
Classificação
Auditoria/RGPD
Relatórios
```

---

# 1. Objetivo da Sprint

Reforçar a eficiência administrativa.

Implementar:

```text
Dashboard de métricas
Estatísticas de visitas
Relatórios por candidatura
Standardização automática de documentos
Gestão documental avançada
Alertas internos
Gestão de minutas
Automatização das listas provisórias e finais
Geração de atas
Confirmações automáticas com número de processo
```

A plataforma deve permitir que os serviços municipais:

```text
Consultem indicadores executivos e operacionais
Acompanhem volumes de candidaturas por estado
Acompanhem documentos pendentes, rejeitados e validados
Acompanhem visitas agendadas, concluídas, canceladas e faltas
Gerem relatório individual por candidatura
Padronizem automaticamente o dossier documental da candidatura
Façam gestão documental avançada
Recebam alertas internos sobre prazos e ações pendentes
Gerem e mantenham minutas oficiais
Gerem automaticamente listas provisórias e definitivas/finais
Gerem atas do procedimento a partir de dados estruturados
Emitam confirmações automáticas com número de processo
Mantenham histórico, auditoria e rastreabilidade
```

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 24.

Não avances para Sprint 25 ou qualquer sprint futura sem validação explícita.

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

docs/backlog/sprint-8-candidaturas-submissao-formal.md
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
docs/backlog/sprint-10-matriz-classificacao-ranking.md
docs/backlog/sprint-11-listas-provisorias-reclamacoes-audiencia.md
docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
docs/backlog/sprint-17-relatorios-indicadores-dashboard-executivo.md
docs/backlog/sprint-18-rgpd-seguranca-auditoria-avancada.md
docs/backlog/sprint-19-testes-integrados-qualidade.md
docs/backlog/sprint-22-candidaturas-visitas-apoio-candidato.md
docs/backlog/sprint-23-acompanhamento-processual-avancado.md
docs/backlog/sprint-24-backoffice-operacional-gestao-procedimento.md

docs/candidate-experience/process-tracking.md
docs/candidate-experience/process-timeline.md
docs/candidate-experience/additional-documents-and-corrections.md
docs/qa/test-coverage-matrix.md
docs/qa/quality-gates.md
docs/qa/regression-test-plan.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

Não alterar `.env`.

Não introduzir credenciais.

Não usar dados pessoais reais.

Não criar integrações externas obrigatórias.

Não reescrever o workflow administrativo existente.

Não duplicar módulos já existentes de relatórios, notificações, listas, documentos ou auditoria; reaproveitar e consolidar.

---

# 3. PHPStan obrigatório antes de publicar — contexto com 2471 erros legados

O projeto tem atualmente:

```text
2471 erros PHPStan legados
```

A Sprint 24 não tem como objetivo corrigir todos os erros legados.

A Sprint 24 tem como objetivo obrigatório:

```text
Não aumentar o número de erros PHPStan.
Não introduzir novos erros PHPStan nos ficheiros criados ou alterados.
Identificar claramente erros legados versus erros introduzidos pela sprint.
Executar PHPStan antes da implementação e antes da publicação.
Corrigir todos os erros PHPStan diretamente causados pela Sprint 24.
```

## 3.1 Verificação PHPStan inicial

Antes de criar ou alterar ficheiros, executar, se PHPStan existir:

```bash
mkdir -p storage/phpstan

php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint24-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint24-before.txt || true
```

Se existir `phpstan.neon`, usar:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint24-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint24-before.txt || true
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
Não corrigir erros PHPStan fora do âmbito da Sprint 24, salvo se bloquearem diretamente a sprint.
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

Antes de considerar a Sprint 24 pronta para publicação, executar:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint24-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint24-after.txt || true
```

Com config, se existir:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint24-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint24-after.txt || true
```

Depois, identificar erros nos ficheiros criados ou alterados nesta sprint.

Se existirem erros PHPStan em ficheiros da Sprint 24:

```text
Corrigir antes de concluir.
Não publicar como concluído enquanto houver erro novo causado pela Sprint 24.
```

Se existirem apenas os 2471 erros legados:

```text
Documentar que o passivo PHPStan legado permanece.
Confirmar que a Sprint 24 não adicionou erros novos nos ficheiros alterados.
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
Novos erros introduzidos pela Sprint 24: sim/não
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
Sistema de roles/permissões
Sistema de policies
Sistema de Form Requests
Sistema de candidaturas
Sistema de concursos
Sistema de classificação/ranking
Sistema de listas provisórias/finais
Sistema documental
Sistema de minutas, se existir
Sistema de relatórios/exportações, se existir
Sistema de PDF, se existir
Sistema de visitas
Sistema de notificações
Sistema de alertas, se existir
Sistema de workflow administrativo
Sistema de auditoria/RGPD
Sistema de atas, se existir
Sistema de dashboard/indicadores, se existir
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
ApplicationStatusHistory
ApplicationSnapshot
Contest
ContestDeadline
Program
HousingUnit
HousingVisit
VisitSlot
DocumentSubmission
DocumentVersion
RequiredDocument
DocumentChecklist
CorrectionRequest
EligibilityCheck
ApplicationScore
RankingSnapshot
ProvisionalList
DefinitiveList
Complaint
OfficialNotification
CommunicationLog
ProcessTimelineEvent
AuditEvent
ReportExport
ReportRun
DocumentTemplate
OfficialTemplate
Minute
MeetingMinute
ProcedureMinute
InternalAlert
BackofficeDashboard
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
ExecutiveDashboard
OperationalDashboard
ApplicationReport
DocumentStandardization
DocumentDossier
InternalAlert
TemplateManagement
ProcedureTemplate
ProvisionalListAutomation
DefinitiveListAutomation
ProcedureMinute
AutomaticProcessConfirmation
```

reaproveitar ou adaptar com compatibilidade.

---

# 5. Dependências funcionais

Esta sprint depende preferencialmente de:

```text
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 10 — Matriz de Classificação e Ranking
Sprint 11 — Listas Provisórias, Reclamações e Audiência
Sprint 16 — Notificações, Comunicações e Modelos Documentais
Sprint 17 — Relatórios, Indicadores e Dashboard Executivo
Sprint 18 — RGPD, Segurança e Auditoria Avançada
Sprint 22 — Candidaturas, Visitas e Apoio ao Candidato
Sprint 23 — Acompanhamento Processual Avançado
```

Dependências mínimas:

```text
User
Application
Contest
DocumentSubmission
Backoffice
Roles/permissions
```

Se o módulo de relatórios já existir:

```text
Reutilizar registry, exportadores e relatórios existentes.
Não criar segundo sistema de relatórios incompatível.
```

Se o módulo documental já existir:

```text
Reutilizar DocumentSubmission, DocumentVersion, RequiredDocument e storage privado.
Não duplicar documentos de candidatura.
```

Se listas provisórias/finais já existirem:

```text
Criar camada de automatização operacional sobre os modelos existentes.
Não substituir regras de classificação/ranking.
```

Se minutas já existirem:

```text
Reutilizar templates e variáveis existentes.
Não criar sistema paralelo de minutas se existir mecanismo configurável.
```

Se algum módulo não existir:

```text
Implementar camada tolerante a dependências parciais.
Documentar limitação.
Não inventar decisão administrativa inexistente.
Não criar automatização final sem dados mínimos.
```

---

# 6. Validação funcional, administrativa e RGPD

Regras obrigatórias:

```text
Dashboards devem respeitar permissões.
Indicadores não devem expor dados pessoais desnecessários.
Relatórios por candidatura devem estar protegidos por policy.
Dossiers documentais devem usar storage privado.
Documentos privados não podem ficar públicos.
Alertas internos não devem ser visíveis ao candidato.
Minutas devem ter controlo de versão.
Listas provisórias/finais devem ser geradas apenas a partir de dados validados.
Atas devem indicar origem dos dados e data de geração.
Confirmações automáticas devem conter número de processo.
Número de processo deve ser único.
Cada ação crítica deve ser auditada.
Automatizações devem ser rastreáveis e reversíveis quando aplicável.
```

Copy obrigatório em relatórios automáticos:

```text
Este documento foi gerado automaticamente com base nos dados registados na plataforma à data da emissão. A validação final compete aos serviços municipais.
```

Copy obrigatório em listas automáticas:

```text
A lista foi gerada com base nos critérios, estados e dados disponíveis na plataforma. Deve ser revista e validada pelos serviços competentes antes de publicação.
```

Copy obrigatório em atas:

```text
A ata foi preparada automaticamente a partir dos dados do procedimento e deve ser revista, validada e aprovada pelos responsáveis competentes.
```

---

# 7. Âmbito incluído

Implementar:

```text
Dashboard executivo
Dashboard operacional
Métricas de candidaturas
Métricas de documentos
Métricas de visitas
Métricas de prazos
Métricas de tickets/apoio, se módulo existir
Relatório por candidatura
Dossier documental padronizado por candidatura
Standardização automática de documentos
Gestão documental avançada no backoffice
Alertas internos
Gestão de minutas
Variáveis dinâmicas em minutas
Versionamento de minutas
Automatização de listas provisórias
Automatização de listas finais/definitivas
Geração de atas
Confirmações automáticas com número de processo
Serviços de exportação/geração documental
Policies
Form Requests
Controllers
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
Novo motor de elegibilidade
Novo motor de classificação
Nova regra substantiva de ranking
Assinatura digital
Integração externa com gestão documental municipal
Integração externa com BI
Integração externa com arquivo
Integração com sistema de expediente externo
Envio real de SMS sem configuração existente
Envio real de e-mail sem configuração existente
OCR
IA documental
Workflow jurídico completo de aprovação externa
Publicação automática sem validação humana
```

Esta sprint melhora backoffice, automações internas e geração documental, mas não substitui validação humana exigida no procedimento.

---

# 9. Fluxos funcionais obrigatórios

## 9.1 Dashboard executivo

```text
Utilizador autorizado acede ao backoffice
→ Sistema carrega indicadores por município/programa/concurso
→ Sistema mostra candidaturas por estado
→ Sistema mostra documentos pendentes
→ Sistema mostra prazos críticos
→ Sistema mostra visitas e tickets
→ Sistema mostra listas e publicações
→ Sistema mostra alertas internos
```

## 9.2 Relatório por candidatura

```text
Técnico acede à candidatura
→ Seleciona gerar relatório
→ Sistema agrega dados principais
→ Sistema agrega checklist documental
→ Sistema agrega elegibilidade/classificação, se existir
→ Sistema agrega histórico processual
→ Sistema gera relatório em formato suportado
→ Sistema regista exportação/auditoria
```

## 9.3 Standardização documental

```text
Técnico acede ao dossier da candidatura
→ Sistema identifica documentos obrigatórios
→ Sistema ordena documentos por categoria
→ Sistema identifica falta, duplicado, expirado ou rejeitado
→ Sistema gera índice documental padronizado
→ Sistema permite exportar/consultar dossier
```

## 9.4 Alertas internos

```text
Sistema identifica prazo ou ação crítica
→ Cria alerta interno
→ Atribui a técnico/role/equipa
→ Mostra no dashboard
→ Permite marcar como visto/resolvido
→ Regista histórico/auditoria
```

## 9.5 Gestão de minutas

```text
Admin/técnico autorizado cria minuta
→ Define tipo, versão e variáveis
→ Sistema valida placeholders
→ Minuta fica em rascunho
→ Utilizador autorizado publica minuta
→ Sistema permite gerar documento com dados do procedimento
```

## 9.6 Automatização de listas

```text
Técnico autorizado seleciona concurso
→ Sistema valida dados necessários
→ Sistema obtém candidatos elegíveis/admitidos/classificados
→ Sistema gera lista provisória ou final
→ Sistema cria snapshot
→ Sistema permite revisão
→ Sistema gera documento/listagem
→ Sistema regista auditoria
```

## 9.7 Geração de ata

```text
Técnico autorizado seleciona procedimento/concurso/reunião
→ Sistema agrega dados do procedimento
→ Sistema aplica minuta de ata
→ Sistema gera ata em rascunho
→ Utilizador revê e valida
→ Sistema guarda versão
→ Sistema regista auditoria
```

## 9.8 Confirmação automática com número de processo

```text
Candidatura é submetida ou recebida
→ Sistema verifica se existe número de processo
→ Se não existir, gera número único
→ Sistema cria confirmação automática
→ Sistema associa à candidatura
→ Sistema cria notificação/comunicação
→ Sistema regista evento processual
```

---

# 10. Estados e tipos recomendados

## DashboardMetricType

```text
applications
documents
visits
tickets
deadlines
alerts
lists
reports
minutes
notifications
```

## ReportFormat

```text
html
pdf
csv
xlsx
json
```

## ApplicationReportStatus

```text
draft
generated
reviewed
approved
archived
failed
```

## DocumentDossierStatus

```text
draft
complete
incomplete
requires_review
standardized
exported
archived
```

## InternalAlertStatus

```text
open
seen
in_progress
resolved
dismissed
expired
```

## InternalAlertSeverity

```text
info
warning
high
critical
```

## InternalAlertType

```text
deadline_approaching
deadline_expired
documents_pending
documents_rejected
application_unassigned
visit_pending
ticket_pending
list_generation_pending
minute_review_pending
report_failed
process_confirmation_pending
```

## TemplateType

```text
application_report
document_dossier
provisional_list
definitive_list
procedure_minute
notification
process_confirmation
internal_note
```

## TemplateStatus

```text
draft
active
inactive
archived
superseded
```

## ListAutomationType

```text
provisional
definitive
final
admitted
excluded
ranked
allocated
```

## ListAutomationStatus

```text
draft
generated
under_review
approved
published
archived
failed
```

## ProcedureMinuteStatus

```text
draft
generated
under_review
approved
archived
cancelled
```

## ProcessConfirmationStatus

```text
pending
generated
sent
read
failed
cancelled
```

---

# 11. Modelo de dados

## 11.1 BackofficeDashboardSnapshot

Criar entidade:

```text
BackofficeDashboardSnapshot
```

Tabela:

```text
backoffice_dashboard_snapshots
```

Campos mínimos:

```text
id
snapshot_number
municipality_id nullable
program_id nullable
contest_id nullable

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
Guardar snapshots opcionais de métricas operacionais para auditoria, comparação e relatórios.
```

Regras:

```text
metrics deve ser JSON.
Não guardar dados pessoais desnecessários.
```

## 11.2 ApplicationReport

Criar ou adaptar entidade existente:

```text
ApplicationReport
```

Tabela:

```text
application_reports
```

Campos:

```text
id
report_number
application_id
contest_id nullable
user_id nullable

status
format
title
summary
payload
file_path nullable
generated_by nullable
generated_at
reviewed_by nullable
reviewed_at nullable
approved_by nullable
approved_at nullable

created_at
updated_at
deleted_at
```

Regras:

```text
Relatórios contendo dados pessoais devem ficar em storage privado.
Download deve passar por controller autorizado.
```

## 11.3 DocumentDossier

Criar entidade:

```text
DocumentDossier
```

Tabela:

```text
document_dossiers
```

Campos:

```text
id
dossier_number
application_id
user_id nullable
contest_id nullable

status
title
summary
standardization_payload
missing_documents_count
rejected_documents_count
expired_documents_count
validated_documents_count
file_path nullable
standardized_at nullable
exported_at nullable
created_by nullable

created_at
updated_at
deleted_at
```

## 11.4 DocumentDossierItem

Criar entidade:

```text
DocumentDossierItem
```

Tabela:

```text
document_dossier_items
```

Campos:

```text
id
document_dossier_id
document_submission_id nullable
required_document_id nullable
document_type_id nullable

category
label
status
sort_order
is_required
is_missing
is_rejected
is_expired
is_validated
notes nullable

created_at
updated_at
```

## 11.5 InternalAlert

Criar entidade:

```text
InternalAlert
```

Tabela:

```text
internal_alerts
```

Campos:

```text
id
alert_number

type
severity
status
title
message

assigned_to nullable
assigned_role nullable
municipality_id nullable
program_id nullable
contest_id nullable
application_id nullable

due_at nullable
seen_at nullable
resolved_at nullable
resolved_by nullable

related_type nullable
related_id nullable
metadata

created_by nullable
created_at
updated_at
deleted_at
```

## 11.6 ProcedureTemplate

Criar ou adaptar entidade existente:

```text
ProcedureTemplate
```

Tabela:

```text
procedure_templates
```

Campos:

```text
id
template_number
type
status
name
description
version
content
variables
published_at nullable
published_by nullable
superseded_by nullable
created_by
updated_by nullable
created_at
updated_at
deleted_at
```

Regras:

```text
Minutas devem ter versionamento.
Minutas ativas devem ser imutáveis ou gerar nova versão ao alterar.
content pode conter placeholders.
variables deve documentar placeholders disponíveis.
```

## 11.7 GeneratedProcedureDocument

Criar entidade:

```text
GeneratedProcedureDocument
```

Tabela:

```text
generated_procedure_documents
```

Campos:

```text
id
document_number
procedure_template_id nullable

type
status
title
format

application_id nullable
contest_id nullable
program_id nullable
related_type nullable
related_id nullable

payload
content_snapshot
file_path nullable
generated_by nullable
generated_at
approved_by nullable
approved_at nullable

created_at
updated_at
deleted_at
```

## 11.8 ListAutomationRun

Criar entidade:

```text
ListAutomationRun
```

Tabela:

```text
list_automation_runs
```

Campos:

```text
id
run_number
contest_id
type
status

source_ranking_snapshot_id nullable
source_provisional_list_id nullable
source_definitive_list_id nullable

total_candidates
included_count
excluded_count
warnings_count

criteria_snapshot
result_payload
file_path nullable

generated_by
generated_at
reviewed_by nullable
reviewed_at nullable
approved_by nullable
approved_at nullable
published_at nullable

created_at
updated_at
deleted_at
```

Regras:

```text
Gerar snapshot para rastreabilidade.
Não publicar automaticamente sem validação humana, salvo regra existente.
```

## 11.9 ProcedureMinute

Criar ou adaptar entidade existente:

```text
ProcedureMinute
```

Tabela:

```text
procedure_minutes
```

Campos:

```text
id
minute_number
contest_id nullable
program_id nullable
application_id nullable
procedure_template_id nullable

status
title
meeting_date nullable
subject
summary
content_snapshot
payload
file_path nullable

generated_by
generated_at
reviewed_by nullable
reviewed_at nullable
approved_by nullable
approved_at nullable

created_at
updated_at
deleted_at
```

## 11.10 ProcessConfirmation

Criar entidade:

```text
ProcessConfirmation
```

Tabela:

```text
process_confirmations
```

Campos:

```text
id
confirmation_number
process_number
application_id
user_id
contest_id nullable

status
title
message
payload
sent_at nullable
read_at nullable
failed_at nullable
failure_reason nullable

generated_by nullable
created_at
updated_at
deleted_at
```

Regras:

```text
process_number deve ser único.
Gerar número de processo de forma determinística/configurável.
Não regenerar número se já existir.
```

---

# 12. Índices e performance

Adicionar índices seguros:

```text
backoffice_dashboard_snapshots.snapshot_number unique
backoffice_dashboard_snapshots.contest_id
backoffice_dashboard_snapshots.generated_at

application_reports.report_number unique
application_reports.application_id
application_reports.status
application_reports.generated_at

document_dossiers.dossier_number unique
document_dossiers.application_id
document_dossiers.status
document_dossier_items.document_dossier_id
document_dossier_items.status
document_dossier_items.sort_order

internal_alerts.alert_number unique
internal_alerts.type
internal_alerts.severity
internal_alerts.status
internal_alerts.assigned_to
internal_alerts.assigned_role
internal_alerts.application_id
internal_alerts.contest_id
internal_alerts.due_at

procedure_templates.template_number unique
procedure_templates.type
procedure_templates.status
procedure_templates.version

generated_procedure_documents.document_number unique
generated_procedure_documents.type
generated_procedure_documents.status
generated_procedure_documents.application_id
generated_procedure_documents.contest_id

list_automation_runs.run_number unique
list_automation_runs.contest_id
list_automation_runs.type
list_automation_runs.status
list_automation_runs.generated_at

procedure_minutes.minute_number unique
procedure_minutes.contest_id
procedure_minutes.status
procedure_minutes.generated_at

process_confirmations.confirmation_number unique
process_confirmations.process_number unique
process_confirmations.application_id
process_confirmations.user_id
process_confirmations.status
```

Migrations devem ser reversíveis.

Não adicionar índices duplicados.

Usar eager loading e agregações otimizadas em dashboards.

Evitar dashboards com queries N+1.

Paginar relatórios, alertas e documentos gerados.

---

# 13. Services obrigatórios

Criar namespaces:

```text
App\Services\BackofficeDashboard
App\Services\OperationalReports
App\Services\DocumentStandardization
App\Services\InternalAlerts
App\Services\ProcedureTemplates
App\Services\ListAutomation
App\Services\ProcedureMinutes
App\Services\ProcessConfirmations
```

Criar services:

```text
App\Services\BackofficeDashboard\ExecutiveDashboardService
App\Services\BackofficeDashboard\OperationalDashboardService
App\Services\BackofficeDashboard\DashboardMetricAggregator
App\Services\BackofficeDashboard\VisitStatisticsService
App\Services\BackofficeDashboard\DeadlineStatisticsService

App\Services\OperationalReports\ApplicationReportService
App\Services\OperationalReports\ApplicationReportPayloadBuilder
App\Services\OperationalReports\ApplicationReportExportService

App\Services\DocumentStandardization\DocumentDossierService
App\Services\DocumentStandardization\DocumentDossierBuilder
App\Services\DocumentStandardization\DocumentStandardizationService
App\Services\DocumentStandardization\DocumentDossierExportService

App\Services\InternalAlerts\InternalAlertService
App\Services\InternalAlerts\InternalAlertDetector
App\Services\InternalAlerts\InternalAlertResolver

App\Services\ProcedureTemplates\ProcedureTemplateService
App\Services\ProcedureTemplates\TemplateVariableResolver
App\Services\ProcedureTemplates\TemplateRenderingService
App\Services\ProcedureTemplates\GeneratedProcedureDocumentService

App\Services\ListAutomation\ProvisionalListAutomationService
App\Services\ListAutomation\DefinitiveListAutomationService
App\Services\ListAutomation\ListAutomationRunService
App\Services\ListAutomation\ListAutomationValidator

App\Services\ProcedureMinutes\ProcedureMinuteService
App\Services\ProcedureMinutes\ProcedureMinutePayloadBuilder
App\Services\ProcedureMinutes\ProcedureMinuteExportService

App\Services\ProcessConfirmations\ProcessNumberGenerator
App\Services\ProcessConfirmations\ProcessConfirmationService
App\Services\ProcessConfirmations\AutomaticProcessConfirmationService
```

## 13.1 ExecutiveDashboardService

Responsável por:

```text
Construir dashboard executivo
Agregações por concurso/programa/período
Indicadores de candidaturas
Indicadores documentais
Indicadores de visitas
Indicadores de prazos
Indicadores de listas
Indicadores de alertas
```

## 13.2 OperationalDashboardService

Responsável por:

```text
Construir dashboard operacional diário
Listar ações pendentes
Listar processos críticos
Listar prazos próximos
Listar documentos pendentes
Listar visitas/tickets pendentes
Listar listas/atas por rever
```

## 13.3 DashboardMetricAggregator

Responsável por:

```text
Executar queries agregadas
Evitar N+1
Normalizar métricas
Aplicar filtros
Retornar payload estruturado
```

## 13.4 VisitStatisticsService

Responsável por:

```text
Calcular visitas agendadas
Calcular visitas confirmadas
Calcular visitas concluídas
Calcular cancelamentos
Calcular faltas/não comparência
Calcular taxa de ocupação dos slots
Agrupar por concurso/imóvel/técnico/período
```

## 13.5 ApplicationReportService

Responsável por:

```text
Gerar relatório por candidatura
Integrar dados pessoais permitidos
Integrar agregado/rendimentos se autorizado
Integrar documentos
Integrar elegibilidade/classificação se existir
Integrar timeline processual
Integrar notificações relevantes
Criar relatório em formato suportado
Auditar geração e download
```

## 13.6 DocumentDossierService

Responsável por:

```text
Criar dossier documental por candidatura
Atualizar dossier
Calcular completude
Identificar documentos em falta
Identificar documentos rejeitados
Identificar documentos expirados
Identificar duplicados
Gerar índice padronizado
```

## 13.7 DocumentStandardizationService

Responsável por:

```text
Padronizar nomes de documentos
Padronizar categorias
Padronizar ordenação
Padronizar estado documental
Aplicar regras do checklist
Gerar metadados de dossier
```

## 13.8 InternalAlertDetector

Responsável por:

```text
Detetar prazos próximos
Detetar prazos expirados
Detetar candidaturas sem técnico
Detetar documentos pendentes
Detetar visitas pendentes
Detetar tickets pendentes
Detetar listas por gerar
Detetar atas por rever
Detetar confirmações de processo pendentes
```

## 13.9 ProcedureTemplateService

Responsável por:

```text
Criar minuta
Versionar minuta
Publicar minuta
Arquivar minuta
Validar placeholders
Listar minutas ativas por tipo
```

## 13.10 TemplateVariableResolver

Responsável por:

```text
Resolver variáveis de candidatura
Resolver variáveis de concurso
Resolver variáveis de lista
Resolver variáveis de ata
Resolver variáveis de confirmação
Mascarar dados sensíveis quando necessário
```

## 13.11 TemplateRenderingService

Responsável por:

```text
Renderizar minuta com variáveis
Validar placeholders em falta
Gerar content_snapshot
Evitar execução de código dinâmico
Escapar conteúdo conforme formato
```

## 13.12 ProvisionalListAutomationService

Responsável por:

```text
Validar concurso
Validar candidaturas admitidas
Validar ranking/classificação
Gerar lista provisória em rascunho
Gerar snapshot
Gerar warnings
Criar auditoria
```

## 13.13 DefinitiveListAutomationService

Responsável por:

```text
Validar lista provisória
Considerar reclamações/audiências decididas
Gerar lista final/definitiva
Gerar snapshot
Gerar warnings
Criar auditoria
```

## 13.14 ProcedureMinuteService

Responsável por:

```text
Gerar ata do procedimento
Usar minuta ativa
Agregação de dados do procedimento
Criar documento em rascunho
Permitir revisão/aprovação
Auditar geração
```

## 13.15 ProcessNumberGenerator

Responsável por:

```text
Gerar número único de processo
Respeitar formato configurável
Evitar colisões
Não regenerar número existente
Garantir transação/lock quando necessário
```

Formato recomendado:

```text
HAB-{ANO}-{CONCURSO}-{SEQUENCIAL}
```

Adaptar ao formato real do Município.

## 13.16 AutomaticProcessConfirmationService

Responsável por:

```text
Detetar candidatura sem confirmação
Gerar número de processo
Criar confirmação automática
Criar notificação/comunicação
Criar timeline event se Sprint 23 existir
Auditar ação
```

---

# 14. Controllers

Criar ou completar:

```text
App\Http\Controllers\Backoffice\ExecutiveDashboardController
App\Http\Controllers\Backoffice\OperationalDashboardController
App\Http\Controllers\Backoffice\ApplicationReportController
App\Http\Controllers\Backoffice\DocumentDossierController
App\Http\Controllers\Backoffice\InternalAlertController
App\Http\Controllers\Backoffice\ProcedureTemplateController
App\Http\Controllers\Backoffice\GeneratedProcedureDocumentController
App\Http\Controllers\Backoffice\ListAutomationController
App\Http\Controllers\Backoffice\ProcedureMinuteController
App\Http\Controllers\Backoffice\ProcessConfirmationController
```

Controllers devem ser magros e delegar regras para Services.

Não colocar lógica de geração complexa em controllers.

---

# 15. Form Requests

Criar:

```text
FilterExecutiveDashboardRequest
FilterOperationalDashboardRequest
GenerateApplicationReportRequest
DownloadApplicationReportRequest
GenerateDocumentDossierRequest
UpdateDocumentDossierRequest
ResolveInternalAlertRequest
StoreProcedureTemplateRequest
UpdateProcedureTemplateRequest
PublishProcedureTemplateRequest
RenderProcedureTemplateRequest
GenerateProcedureDocumentRequest
RunProvisionalListAutomationRequest
RunDefinitiveListAutomationRequest
ReviewListAutomationRunRequest
ApproveListAutomationRunRequest
GenerateProcedureMinuteRequest
ReviewProcedureMinuteRequest
ApproveProcedureMinuteRequest
GenerateProcessConfirmationRequest
SendProcessConfirmationRequest
```

## FilterExecutiveDashboardRequest

```php
'program_id' => ['nullable', 'exists:programs,id'],
'contest_id' => ['nullable', 'exists:contests,id'],
'period_start' => ['nullable', 'date'],
'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
```

## GenerateApplicationReportRequest

```php
'application_id' => ['required', 'exists:applications,id'],
'format' => ['required', 'string', 'in:html,pdf,csv,xlsx'],
'include_documents' => ['nullable', 'boolean'],
'include_timeline' => ['nullable', 'boolean'],
'include_internal_notes' => ['nullable', 'boolean'],
```

## GenerateDocumentDossierRequest

```php
'application_id' => ['required', 'exists:applications,id'],
'include_rejected' => ['nullable', 'boolean'],
'include_expired' => ['nullable', 'boolean'],
'export_format' => ['nullable', 'string', 'in:html,pdf,zip'],
```

## StoreProcedureTemplateRequest

```php
'type' => ['required', 'string', 'max:100'],
'name' => ['required', 'string', 'max:180'],
'description' => ['nullable', 'string', 'max:2000'],
'content' => ['required', 'string', 'min:10'],
'variables' => ['nullable', 'array'],
```

## RunProvisionalListAutomationRequest

```php
'contest_id' => ['required', 'exists:contests,id'],
'confirm_snapshot_generation' => ['accepted'],
```

## RunDefinitiveListAutomationRequest

```php
'contest_id' => ['required', 'exists:contests,id'],
'confirm_complaints_reviewed' => ['accepted'],
'confirm_snapshot_generation' => ['accepted'],
```

## GenerateProcedureMinuteRequest

```php
'contest_id' => ['nullable', 'exists:contests,id'],
'application_id' => ['nullable', 'exists:applications,id'],
'procedure_template_id' => ['required', 'exists:procedure_templates,id'],
'meeting_date' => ['nullable', 'date'],
'subject' => ['required', 'string', 'max:255'],
```

## GenerateProcessConfirmationRequest

```php
'application_id' => ['required', 'exists:applications,id'],
'force_regenerate' => ['nullable', 'boolean'],
```

---

# 16. Policies

Criar ou completar:

```text
ExecutiveDashboardPolicy
OperationalDashboardPolicy
ApplicationReportPolicy
DocumentDossierPolicy
InternalAlertPolicy
ProcedureTemplatePolicy
GeneratedProcedureDocumentPolicy
ListAutomationRunPolicy
ProcedureMinutePolicy
ProcessConfirmationPolicy
```

Regras:

```text
Guest não acede ao backoffice.
Candidato não acede ao backoffice.
Técnico vê dashboards conforme permissões.
Técnico gera relatórios apenas para candidaturas autorizadas.
Técnico acede apenas a documentos permitidos.
Auditor consulta relatórios e dashboards sem alterar.
Admin gere minutas e configurações.
Aprovação/publicação de listas exige permissão específica.
Geração de atas exige permissão específica.
Confirmações de processo exigem permissão específica ou job autorizado.
```

Nunca confiar apenas no frontend para esconder ações.

---

# 17. Rotas de backoffice

Adicionar, adaptando à estrutura real:

```php
Route::middleware(['auth'])->prefix('backoffice')->name('backoffice.')->group(function (): void {
    Route::get('/dashboard/executivo', [ExecutiveDashboardController::class, 'index'])
        ->name('dashboard.executive');

    Route::get('/dashboard/operacional', [OperationalDashboardController::class, 'index'])
        ->name('dashboard.operational');

    Route::get('/candidaturas/{application}/relatorio', [ApplicationReportController::class, 'show'])
        ->name('applications.report.show');
    Route::post('/candidaturas/{application}/relatorio/gerar', [ApplicationReportController::class, 'generate'])
        ->name('applications.report.generate');
    Route::get('/relatorios-candidatura/{applicationReport}/download', [ApplicationReportController::class, 'download'])
        ->name('application-reports.download');

    Route::get('/candidaturas/{application}/dossier-documental', [DocumentDossierController::class, 'show'])
        ->name('applications.document-dossier.show');
    Route::post('/candidaturas/{application}/dossier-documental/gerar', [DocumentDossierController::class, 'generate'])
        ->name('applications.document-dossier.generate');
    Route::get('/dossiers-documentais/{documentDossier}/download', [DocumentDossierController::class, 'download'])
        ->name('document-dossiers.download');

    Route::get('/alertas-internos', [InternalAlertController::class, 'index'])
        ->name('internal-alerts.index');
    Route::get('/alertas-internos/{internalAlert}', [InternalAlertController::class, 'show'])
        ->name('internal-alerts.show');
    Route::post('/alertas-internos/{internalAlert}/resolver', [InternalAlertController::class, 'resolve'])
        ->name('internal-alerts.resolve');
    Route::post('/alertas-internos/{internalAlert}/dispensar', [InternalAlertController::class, 'dismiss'])
        ->name('internal-alerts.dismiss');

    Route::resource('/minutas-procedimento', ProcedureTemplateController::class)
        ->names('procedure-templates');
    Route::post('/minutas-procedimento/{procedureTemplate}/publicar', [ProcedureTemplateController::class, 'publish'])
        ->name('procedure-templates.publish');
    Route::post('/minutas-procedimento/{procedureTemplate}/renderizar', [ProcedureTemplateController::class, 'render'])
        ->name('procedure-templates.render');

    Route::get('/documentos-gerados', [GeneratedProcedureDocumentController::class, 'index'])
        ->name('generated-documents.index');
    Route::get('/documentos-gerados/{generatedProcedureDocument}', [GeneratedProcedureDocumentController::class, 'show'])
        ->name('generated-documents.show');
    Route::get('/documentos-gerados/{generatedProcedureDocument}/download', [GeneratedProcedureDocumentController::class, 'download'])
        ->name('generated-documents.download');

    Route::get('/concursos/{contest}/listas/automatizacao', [ListAutomationController::class, 'index'])
        ->name('contests.list-automation.index');
    Route::post('/concursos/{contest}/listas/provisoria/gerar', [ListAutomationController::class, 'generateProvisional'])
        ->name('contests.list-automation.provisional.generate');
    Route::post('/concursos/{contest}/listas/definitiva/gerar', [ListAutomationController::class, 'generateDefinitive'])
        ->name('contests.list-automation.definitive.generate');
    Route::post('/listas-automatizadas/{listAutomationRun}/aprovar', [ListAutomationController::class, 'approve'])
        ->name('list-automation-runs.approve');

    Route::get('/atas', [ProcedureMinuteController::class, 'index'])
        ->name('procedure-minutes.index');
    Route::post('/atas/gerar', [ProcedureMinuteController::class, 'generate'])
        ->name('procedure-minutes.generate');
    Route::get('/atas/{procedureMinute}', [ProcedureMinuteController::class, 'show'])
        ->name('procedure-minutes.show');
    Route::post('/atas/{procedureMinute}/aprovar', [ProcedureMinuteController::class, 'approve'])
        ->name('procedure-minutes.approve');
    Route::get('/atas/{procedureMinute}/download', [ProcedureMinuteController::class, 'download'])
        ->name('procedure-minutes.download');

    Route::get('/confirmacoes-processo', [ProcessConfirmationController::class, 'index'])
        ->name('process-confirmations.index');
    Route::post('/candidaturas/{application}/confirmacao-processo/gerar', [ProcessConfirmationController::class, 'generate'])
        ->name('applications.process-confirmation.generate');
    Route::post('/confirmacoes-processo/{processConfirmation}/enviar', [ProcessConfirmationController::class, 'send'])
        ->name('process-confirmations.send');
});
```

Todas as rotas devem respeitar middleware, policies e convenções existentes.

---

# 18. Views / páginas

Se o projeto usa Blade, criar:

```text
resources/views/backoffice/dashboard/executive.blade.php
resources/views/backoffice/dashboard/operational.blade.php

resources/views/backoffice/application-reports/show.blade.php
resources/views/backoffice/application-reports/preview.blade.php

resources/views/backoffice/document-dossiers/show.blade.php
resources/views/backoffice/document-dossiers/preview.blade.php

resources/views/backoffice/internal-alerts/index.blade.php
resources/views/backoffice/internal-alerts/show.blade.php

resources/views/backoffice/procedure-templates/index.blade.php
resources/views/backoffice/procedure-templates/create.blade.php
resources/views/backoffice/procedure-templates/edit.blade.php
resources/views/backoffice/procedure-templates/show.blade.php
resources/views/backoffice/procedure-templates/preview.blade.php

resources/views/backoffice/generated-documents/index.blade.php
resources/views/backoffice/generated-documents/show.blade.php

resources/views/backoffice/list-automation/index.blade.php
resources/views/backoffice/list-automation/show.blade.php

resources/views/backoffice/procedure-minutes/index.blade.php
resources/views/backoffice/procedure-minutes/show.blade.php
resources/views/backoffice/procedure-minutes/preview.blade.php

resources/views/backoffice/process-confirmations/index.blade.php
resources/views/backoffice/process-confirmations/show.blade.php
```

Se o projeto usa Inertia/React/Vue, criar equivalentes.

Não mudar stack frontend.

---

# 19. UX obrigatória

## 19.1 Dashboard executivo

Mostrar:

```text
Candidaturas por estado
Candidaturas por concurso
Candidaturas submetidas por período
Candidaturas em análise
Candidaturas com ação pendente
Documentos pendentes
Documentos rejeitados
Documentos validados
Visitas agendadas
Visitas concluídas
Visitas canceladas
Tickets abertos
Prazos críticos
Alertas internos críticos
Listas por gerar/rever/publicar
Atas por rever
```

## 19.2 Dashboard operacional

Mostrar:

```text
Fila de trabalho do técnico
Candidaturas sem técnico
Candidaturas com prazo a expirar
Pedidos de aperfeiçoamento pendentes
Documentos por validar
Visitas do dia/semana
Tickets pendentes
Alertas atribuídos ao utilizador
Ações rápidas
```

## 19.3 Relatório por candidatura

Mostrar:

```text
Identificação do processo
Número de processo
Concurso
Estado
Dados principais do candidato, conforme permissão
Resumo do agregado
Resumo de rendimentos, conforme permissão
Resumo documental
Elegibilidade
Pontuação/classificação, se existir
Histórico processual
Pedidos de aperfeiçoamento
Notificações relevantes
Observações técnicas autorizadas
```

## 19.4 Dossier documental

Mostrar:

```text
Índice documental
Documentos obrigatórios
Documentos submetidos
Documentos em falta
Documentos rejeitados
Documentos expirados
Documentos validados
Versões
Estado global do dossier
Ações de exportação
```

## 19.5 Alertas internos

Mostrar:

```text
Tipo
Severidade
Estado
Processo/candidatura associada
Prazo
Responsável
Mensagem
Ações de resolver/dispensar
Histórico
```

## 19.6 Minutas

Mostrar:

```text
Tipo
Nome
Versão
Estado
Variáveis disponíveis
Conteúdo
Pré-visualização
Publicar
Arquivar
Criar nova versão
```

## 19.7 Automatização de listas

Mostrar:

```text
Concurso
Tipo de lista
Fonte dos dados
Total de candidatos
Incluídos
Excluídos
Warnings
Resultado gerado
Aprovação
Histórico de runs
```

## 19.8 Atas

Mostrar:

```text
Concurso/procedimento
Assunto
Data
Minuta usada
Conteúdo gerado
Dados de origem
Estado
Revisão
Aprovação
Download
```

---

# 20. Regras de dashboards e métricas

Dashboards devem:

```text
Usar agregações otimizadas
Aplicar filtros por programa/concurso/período
Respeitar permissões
Não expor dados sensíveis sem necessidade
Permitir drill-down para listagens autorizadas
Ser responsivos
Mostrar estado vazio quando não há dados
Mostrar data/hora da última atualização
```

Métricas mínimas:

```text
Total de candidaturas
Candidaturas por estado
Candidaturas submetidas hoje/semana/mês
Candidaturas por concurso
Documentos pendentes
Documentos rejeitados
Documentos validados
Visitas agendadas
Visitas concluídas
Visitas canceladas
Tickets abertos
Prazos críticos
Alertas internos
Listas pendentes
Atas pendentes
Relatórios gerados
```

---

# 21. Regras de relatórios por candidatura

Regras obrigatórias:

```text
Relatório deve ser gerado apenas por utilizador autorizado.
Relatório deve registar auditoria.
Relatório com dados pessoais deve ficar privado.
Download deve passar por controller autorizado.
Relatório deve indicar data/hora de geração.
Relatório deve indicar fonte dos dados.
Relatório não deve substituir decisão administrativa.
```

Formatos:

```text
HTML obrigatório
PDF se infraestrutura existir
CSV/XLSX opcional quando aplicável
Fallback documentado se PDF real não existir
```

---

# 22. Regras de standardização documental

Regras obrigatórias:

```text
Não mover/apagar documentos originais.
Não expor documentos privados.
Criar índice padronizado.
Preservar versões.
Identificar documentos em falta.
Identificar rejeitados.
Identificar expirados.
Identificar duplicados se possível.
Gerar estado global do dossier.
Permitir exportação apenas com permissão.
```

Categorias recomendadas:

```text
Identificação
Residência
Agregado familiar
Rendimentos
Situação habitacional
Deficiência/incapacidade
Declarações
Comprovativos complementares
Documentos do procedimento
Outros
```

---

# 23. Regras de alertas internos

Alertas devem ser criados para:

```text
Prazos a expirar
Prazos expirados
Documentos pendentes
Documentos rejeitados sem resposta
Candidaturas sem técnico
Pedidos de aperfeiçoamento sem resposta
Visitas pendentes
Tickets sem resposta
Listas por gerar
Atas por rever
Confirmações de processo por emitir
```

Regras:

```text
Não duplicar alerta ativo igual.
Permitir resolver/dispensar.
Guardar histórico.
Auditar resolução de alertas críticos.
```

---

# 24. Regras de minutas

Regras obrigatórias:

```text
Minuta tem tipo.
Minuta tem versão.
Minuta ativa não deve ser alterada diretamente; criar nova versão.
Placeholders devem ser validados.
Variáveis desconhecidas devem gerar warning/erro.
Renderização não deve executar código arbitrário.
Conteúdo deve ser escapado quando renderizado em HTML.
Alterações devem ser auditadas.
```

Placeholders recomendados:

```text
{{ process_number }}
{{ application_number }}
{{ candidate_name }}
{{ contest_title }}
{{ contest_code }}
{{ municipality_name }}
{{ submitted_at }}
{{ current_status }}
{{ ranking_position }}
{{ total_score }}
{{ generated_at }}
```

Usar apenas dados permitidos pela policy.

---

# 25. Regras de automatização de listas

Regras obrigatórias:

```text
Não gerar lista sem concurso válido.
Não gerar lista sem dados mínimos.
Usar ranking/classificação existente.
Usar snapshots.
Gerar warnings se existirem candidaturas incompletas.
Gerar warnings se existirem reclamações pendentes para lista definitiva.
Não publicar automaticamente sem validação humana, salvo regra já existente.
Auditar geração, aprovação e publicação.
```

Lista provisória:

```text
Usar candidaturas admitidas/classificadas.
Incluir excluídos se o procedimento exigir lista motivada.
Gerar documento/listagem em rascunho.
```

Lista definitiva/final:

```text
Validar reclamações/audiência decididas.
Usar ranking final.
Gerar snapshot final.
```

---

# 26. Regras de atas

Regras obrigatórias:

```text
Ata deve ter minuta.
Ata deve ter conteúdo gerado.
Ata deve ter fonte de dados.
Ata deve ficar em rascunho até revisão.
Ata deve permitir aprovação.
Ata aprovada não deve ser editada diretamente; criar nova versão ou documento retificado.
Ata deve ficar em storage privado se contiver dados pessoais.
```

---

# 27. Regras de confirmações automáticas com número de processo

Regras obrigatórias:

```text
Número de processo único.
Número gerado uma única vez por candidatura.
Confirmação deve ser associada à candidatura.
Confirmação deve ser registada.
Candidato deve receber notificação interna se módulo existir.
Timeline deve ser atualizada se Sprint 23 existir.
Erro de envio não deve apagar confirmação.
```

Formato recomendado:

```text
HAB-{ANO}-{CODIGO_CONCURSO}-{SEQUENCIAL}
```

Adaptar ao formato existente do projeto ou do Município.

---

# 28. Notificações

Se Sprint 16 existir, emitir notificações para:

```text
Confirmação de número de processo
Relatório gerado
Dossier documental padronizado
Alerta crítico atribuído
Lista provisória gerada
Lista definitiva gerada
Ata gerada
Ata aprovada
Documento gerado
```

Não enviar e-mail/SMS real sem configuração segura.

Se notificações não existirem, criar eventos internos ou documentar pendência.

---

# 29. Auditoria e RGPD

Auditar, se existir auditoria:

```text
Consulta de dashboard sensível
Geração de relatório por candidatura
Download de relatório
Geração de dossier documental
Download de dossier
Resolução de alerta interno
Criação/alteração/publicação de minuta
Geração de documento por minuta
Geração de lista provisória
Geração de lista definitiva
Aprovação de lista
Geração de ata
Aprovação de ata
Geração de número de processo
Envio de confirmação automática
```

RGPD:

```text
Não expor documentos privados.
Não exportar dados pessoais sem permissão.
Não guardar dados sensíveis em logs técnicos.
Não incluir dados desnecessários em dashboards.
Não permitir downloads diretos por path.
Registar acesso a relatórios sensíveis.
```

---

# 30. Factories e seeders

Criar factories:

```text
BackofficeDashboardSnapshotFactory
ApplicationReportFactory
DocumentDossierFactory
DocumentDossierItemFactory
InternalAlertFactory
ProcedureTemplateFactory
GeneratedProcedureDocumentFactory
ListAutomationRunFactory
ProcedureMinuteFactory
ProcessConfirmationFactory
```

Criar seeder opcional:

```text
Database\Seeders\BackofficeOperationalDemoSeeder
```

Dados fictícios:

```text
Dashboard com métricas
Candidaturas em vários estados
Visitas com estatísticas
Documentos pendentes e validados
Relatório por candidatura
Dossier documental
Alertas internos
Minutas ativas
Lista provisória gerada
Lista definitiva gerada
Ata em rascunho
Confirmação de processo
```

Não usar dados reais.

---

# 31. Testes obrigatórios

Criar ou completar testes.

## 31.1 Dashboards

```text
tests/Feature/Backoffice/ExecutiveDashboardTest.php
tests/Feature/Backoffice/OperationalDashboardTest.php
tests/Unit/BackofficeDashboard/DashboardMetricAggregatorTest.php
tests/Unit/BackofficeDashboard/VisitStatisticsServiceTest.php
```

Cobrir:

```text
Admin vê dashboard executivo
Técnico autorizado vê dashboard operacional
Candidato não acede ao dashboard
Métricas de candidaturas são calculadas
Métricas de visitas são calculadas
Filtros por concurso funcionam
Dados sensíveis não aparecem indevidamente
```

## 31.2 Relatórios por candidatura

```text
tests/Feature/Backoffice/ApplicationReportTest.php
tests/Unit/OperationalReports/ApplicationReportServiceTest.php
tests/Unit/OperationalReports/ApplicationReportPayloadBuilderTest.php
```

Cobrir:

```text
Técnico autorizado gera relatório
Utilizador sem permissão não gera relatório
Relatório inclui número de processo
Relatório inclui estado e histórico
Relatório fica privado
Download exige autorização
Geração cria auditoria se existir
```

## 31.3 Dossier documental e standardização

```text
tests/Feature/Backoffice/DocumentDossierTest.php
tests/Unit/DocumentStandardization/DocumentDossierBuilderTest.php
tests/Unit/DocumentStandardization/DocumentStandardizationServiceTest.php
```

Cobrir:

```text
Dossier é gerado por candidatura
Documentos obrigatórios aparecem ordenados
Documentos em falta são identificados
Documentos rejeitados são identificados
Documentos expirados são identificados
Documentos validados são contados
Download exige autorização
Documentos originais não são apagados
```

## 31.4 Alertas internos

```text
tests/Feature/Backoffice/InternalAlertTest.php
tests/Unit/InternalAlerts/InternalAlertDetectorTest.php
tests/Unit/InternalAlerts/InternalAlertServiceTest.php
```

Cobrir:

```text
Alerta interno é criado
Alerta duplicado ativo não é criado
Alerta crítico aparece no dashboard
Técnico resolve alerta
Auditor consulta sem alterar
Candidato não vê alerta interno
```

## 31.5 Minutas e documentos gerados

```text
tests/Feature/Backoffice/ProcedureTemplateTest.php
tests/Feature/Backoffice/GeneratedProcedureDocumentTest.php
tests/Unit/ProcedureTemplates/TemplateVariableResolverTest.php
tests/Unit/ProcedureTemplates/TemplateRenderingServiceTest.php
```

Cobrir:

```text
Admin cria minuta
Minuta valida placeholders
Minuta ativa gera nova versão ao alterar
Template renderiza variáveis conhecidas
Template falha com variável desconhecida ou gera warning
Documento gerado preserva content_snapshot
Download exige autorização
```

## 31.6 Automatização de listas

```text
tests/Feature/Backoffice/ListAutomationTest.php
tests/Unit/ListAutomation/ProvisionalListAutomationServiceTest.php
tests/Unit/ListAutomation/DefinitiveListAutomationServiceTest.php
tests/Unit/ListAutomation/ListAutomationValidatorTest.php
```

Cobrir:

```text
Lista provisória é gerada a partir de ranking válido
Lista provisória gera snapshot
Lista definitiva exige reclamações/audiências decididas
Warnings são gerados para dados incompletos
Utilizador sem permissão não aprova lista
Geração cria auditoria se existir
```

## 31.7 Atas

```text
tests/Feature/Backoffice/ProcedureMinuteTest.php
tests/Unit/ProcedureMinutes/ProcedureMinuteServiceTest.php
tests/Unit/ProcedureMinutes/ProcedureMinutePayloadBuilderTest.php
```

Cobrir:

```text
Ata é gerada por minuta
Ata fica em rascunho
Ata pode ser aprovada por utilizador autorizado
Ata aprovada não é editada diretamente
Ata preserva payload e content_snapshot
Download exige autorização
```

## 31.8 Confirmações automáticas

```text
tests/Feature/Backoffice/ProcessConfirmationTest.php
tests/Unit/ProcessConfirmations/ProcessNumberGeneratorTest.php
tests/Unit/ProcessConfirmations/AutomaticProcessConfirmationServiceTest.php
```

Cobrir:

```text
Número de processo é gerado
Número é único
Número não é regenerado se já existir
Confirmação é criada
Confirmação é associada à candidatura
Notificação/timeline é criada se módulos existirem
Erro de envio não apaga confirmação
```

## 31.9 Segurança/RGPD

```text
tests/Feature/Security/BackofficeOperationalPrivacyTest.php
```

Cobrir:

```text
Guest não acede ao backoffice
Candidato não acede ao backoffice
Utilizador sem permissão não exporta relatório
Utilizador sem permissão não descarrega dossier
Relatório privado não é acessível por URL público
Dossier privado não é acessível por URL público
Mass assignment de status é bloqueado
Mass assignment de approved_by é bloqueado
```

---

# 32. PHPStan específico da Sprint 24

Após implementar testes e código:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint24-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint24-after.txt || true
```

Verificar especialmente ficheiros novos:

```text
app/Models/BackofficeDashboardSnapshot.php
app/Models/ApplicationReport.php
app/Models/DocumentDossier.php
app/Models/DocumentDossierItem.php
app/Models/InternalAlert.php
app/Models/ProcedureTemplate.php
app/Models/GeneratedProcedureDocument.php
app/Models/ListAutomationRun.php
app/Models/ProcedureMinute.php
app/Models/ProcessConfirmation.php
app/Services/BackofficeDashboard/*
app/Services/OperationalReports/*
app/Services/DocumentStandardization/*
app/Services/InternalAlerts/*
app/Services/ProcedureTemplates/*
app/Services/ListAutomation/*
app/Services/ProcedureMinutes/*
app/Services/ProcessConfirmations/*
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
/** @return BelongsTo<Application, ApplicationReport> */
public function application(): BelongsTo
{
    return $this->belongsTo(Application::class);
}
```

Em arrays estruturados, usar PHPDoc:

```php
/** @return array{applications: int, documents_pending: int, visits_scheduled: int, alerts_critical: int} */
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
docs/backlog/sprint-24-backoffice-operacional-gestao-procedimento.md
docs/backoffice/operational-dashboard.md
docs/backoffice/executive-dashboard.md
docs/backoffice/application-reports.md
docs/backoffice/document-dossiers.md
docs/backoffice/internal-alerts.md
docs/backoffice/procedure-templates.md
docs/backoffice/list-automation.md
docs/backoffice/procedure-minutes.md
docs/backoffice/process-confirmations.md
docs/qa/test-coverage-matrix.md
docs/qa/sprint-24-quality-report.md
docs/backlog/roadmap.md
```

## docs/backoffice/operational-dashboard.md

Incluir:

```text
Objetivo
Indicadores
Filtros
Permissões
Queries críticas
Limitações
```

## docs/backoffice/executive-dashboard.md

Incluir:

```text
Objetivo
Métricas executivas
Filtros por concurso/programa/período
Interpretação dos dados
Limitações
```

## docs/backoffice/application-reports.md

Incluir:

```text
Objetivo
Dados incluídos
Formatos
Permissões
Auditoria
Limitações
```

## docs/backoffice/document-dossiers.md

Incluir:

```text
Objetivo
Standardização
Categorias
Estados documentais
Exportação
Storage privado
Limitações
```

## docs/backoffice/internal-alerts.md

Incluir:

```text
Tipos de alerta
Severidades
Estados
Resolução
Auditoria
Limitações
```

## docs/backoffice/procedure-templates.md

Incluir:

```text
Tipos de minuta
Versionamento
Variáveis
Publicação
Renderização
Limitações
```

## docs/backoffice/list-automation.md

Incluir:

```text
Lista provisória
Lista definitiva/final
Dados de origem
Snapshots
Warnings
Aprovação
Limitações
```

## docs/backoffice/procedure-minutes.md

Incluir:

```text
Objetivo
Minutas
Dados de origem
Geração
Revisão
Aprovação
Limitações
```

## docs/backoffice/process-confirmations.md

Incluir:

```text
Número de processo
Formato
Confirmação automática
Notificações
Timeline
Limitações
```

## docs/qa/sprint-24-quality-report.md

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

A Sprint 24 está concluída quando:

```text
Existe dashboard executivo.
Existe dashboard operacional.
Dashboard mostra métricas de candidaturas.
Dashboard mostra estatísticas de visitas.
Dashboard mostra documentos pendentes/validados/rejeitados.
Dashboard mostra alertas internos.
Relatório por candidatura pode ser gerado.
Relatório por candidatura respeita permissões.
Dossier documental pode ser gerado.
Dossier documental standardiza documentos.
Dossier identifica documentos em falta/rejeitados/expirados.
Gestão documental avançada está disponível no backoffice.
Alertas internos são criados e resolvidos.
Minutas podem ser geridas e versionadas.
Minutas podem ser renderizadas com variáveis.
Listas provisórias podem ser automatizadas.
Listas finais/definitivas podem ser automatizadas.
Automatização de listas gera snapshot e warnings.
Atas podem ser geradas a partir de minutas.
Atas ficam em rascunho até revisão/aprovação.
Confirmações automáticas com número de processo existem.
Número de processo é único e não é regenerado indevidamente.
Downloads sensíveis passam por controller autorizado.
Auditoria é criada se módulo existir.
Dados pessoais não são expostos indevidamente.
PHPStan foi executado antes da implementação, se disponível.
PHPStan foi executado antes da publicação, se disponível.
Foram considerados os 2471 erros legados.
Sprint 24 não introduz erros PHPStan novos nos ficheiros alterados.
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
6. Novos erros PHPStan introduzidos pela Sprint 24: sim/não
7. Models criados ou alterados
8. Migrations criadas
9. Services criados ou alterados
10. Controllers criados ou alterados
11. Form Requests criados ou alterados
12. Policies criadas ou alteradas
13. Rotas de backoffice criadas ou alteradas
14. Views/components criados ou alterados
15. Estado do dashboard executivo
16. Estado do dashboard operacional
17. Estado das estatísticas de visitas
18. Estado dos relatórios por candidatura
19. Estado da standardização documental
20. Estado da gestão documental avançada
21. Estado dos alertas internos
22. Estado da gestão de minutas
23. Estado da automatização de listas provisórias/finais
24. Estado da geração de atas
25. Estado das confirmações automáticas com número de processo
26. Estado das notificações/auditoria
27. Testes criados ou alterados
28. Resultado de php artisan route:list
29. Resultado de php artisan test
30. Resultado de php artisan migrate, se aplicável
31. Resultado de npm run build, se aplicável
32. Resultado de ./vendor/bin/pint, se aplicável
33. Resultado de PHPStan/Psalm, se aplicável
34. Riscos ainda existentes
35. Pendências técnicas
36. Confirmação de que não foram usados dados pessoais reais
37. Confirmação de que não foram usadas credenciais
38. Confirmação de que não foram implementadas funcionalidades fora de âmbito
39. Recomendação objetiva para avançar ou não para Sprint 25
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 37. Definition of Done

A Sprint 24 só está concluída quando existir backoffice operacional reforçado com dashboard executivo, dashboard operacional, estatísticas de visitas, relatórios por candidatura, standardização documental, gestão documental avançada, alertas internos, gestão de minutas, automatização de listas provisórias/finais, geração de atas e confirmações automáticas com número de processo, com permissões, RGPD, auditoria, testes e validação PHPStan sem aumento do passivo legado de 2471 erros.

Fim da Sprint 24.

---

# 38. Execução realizada em 19/06/2026

## Implementado

- Dashboard operacional em `backoffice.operational.dashboard`.
- Dashboard executivo em `backoffice.operational.executive-dashboard`.
- Métricas agregadas de candidaturas, documentos, prazos, visitas, tickets, alertas, listas, relatórios e atas.
- Relatório operacional por candidatura com payload preservado e exportação em storage privado.
- Dossier documental padronizado com itens, contadores e exportação em storage privado.
- Alertas internos com deteção manual, resolução e dispensa.
- Minutas de procedimento com rascunho, publicação, rendering por variáveis e versionamento conservador.
- Documentos de procedimento gerados a partir de minutas e aprováveis por backoffice.
- Automação assistida de listas provisórias e definitivas, sempre com revisão/aprovação humana antes de publicação.
- Atas de procedimento geradas a partir de minutas, com aprovação humana.
- Confirmações automáticas com número de processo único.
- Factories e seeder demo da Sprint 24.
- Teste funcional dedicado: `tests/Feature/Sprint24BackofficeOperationalTest.php`.

## Ficheiros principais criados

- `database/migrations/2026_06_20_000000_create_backoffice_operational_tables.php`
- `app/Models/*` para dashboards, relatórios, dossiers, alertas, minutas, listas, atas e confirmações.
- `app/Services/BackofficeDashboard/*`
- `app/Services/OperationalReports/*`
- `app/Services/DocumentStandardization/*`
- `app/Services/InternalAlerts/*`
- `app/Services/ProcedureTemplates/*`
- `app/Services/ListAutomation/*`
- `app/Services/ProcedureMinutes/*`
- `app/Services/ProcessConfirmations/*`
- `resources/views/backoffice/dashboard/*`
- `resources/views/backoffice/application-reports/*`
- `resources/views/backoffice/document-dossiers/*`
- `resources/views/backoffice/internal-alerts/*`
- `resources/views/backoffice/procedure-templates/*`
- `resources/views/backoffice/generated-documents/*`
- `resources/views/backoffice/list-automation/*`
- `resources/views/backoffice/procedure-minutes/*`
- `resources/views/backoffice/process-confirmations/*`
- `database/seeders/Sprint24BackofficeOperationalSeeder.php`
- `docs/backoffice/*`
- `docs/qa/sprint-24-quality-report.md`

## Ficheiros alterados

- `routes/web.php`
- `app/Models/Application.php`
- `app/Models/Contest.php`
- `database/seeders/DatabaseSeeder.php`
- `docs/backlog/roadmap.md`
- `docs/qa/test-coverage-matrix.md`

## Guardrails mantidos

- Não foi criada publicação automática de listas.
- Não foi implementada decisão administrativa automática.
- Não foram introduzidas integrações externas.
- Não foram usados dados pessoais reais.
- Não foram usadas credenciais.
- Downloads sensíveis passam por controller autorizado.
- Documentos gerados ficam no storage privado `local`.

## Pendências

- Validar juridicamente minutas finais, textos de relatório, atas e listas.
- Definir scheduler de alertas internos.
- Confirmar KPIs oficiais por município.
- Confirmar formato oficial do número de processo.
- Confirmar política final de retenção/anonimização destes novos artefactos.
