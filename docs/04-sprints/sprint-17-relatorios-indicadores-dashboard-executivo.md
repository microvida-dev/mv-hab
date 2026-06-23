# Sprint 17 — Relatórios, Indicadores e Dashboard Executivo

## Prioridade de desenvolvimento

Esta sprint pertence à fase de inteligência operacional, reporting municipal e apoio à decisão da plataforma de Arrendamento Acessível.

A Sprint 17 deve ser executada depois de existirem módulos funcionais suficientes para gerar indicadores reais:

```text
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 10 — Matriz de Classificação e Ranking
Sprint 11 — Listas Provisórias, Reclamações e Audiência
Sprint 12 — Atribuição de Habitações
Sprint 13 — Cálculo de Renda, Contratos e Caução
Sprint 14 — Pagamentos, Incumprimentos e Revisão de Renda
Sprint 15 — Manutenção, Vistorias e Gestão do Imóvel
Sprint 16 — Notificações, Comunicações e Modelos Documentais
Sprint 17 — Relatórios, Indicadores e Dashboard Executivo
```

Esta sprint deve permitir que a equipa municipal acompanhe o processo de forma agregada, sem consultar candidatura a candidatura, e consiga preparar reuniões, deliberações, relatórios internos e pontos de situação.

---

# 1. Objetivo da Sprint

Implementar dashboards, indicadores, relatórios e exportações com filtros, permissões e rastreabilidade.

A plataforma deve permitir que o Município:

```text
Consulte dashboard operacional
Consulte dashboard executivo
Consulte indicadores por programa
Consulte indicadores por concurso
Consulte indicadores por período
Consulte indicadores por estado
Consulte candidaturas por concurso
Consulte candidaturas elegíveis
Consulte candidaturas excluídas
Consulte tempo médio de análise
Consulte documentos pendentes
Consulte reclamações
Consulte habitações disponíveis
Consulte habitações atribuídas
Consulte taxa de ocupação
Consulte rendas em atraso
Consulte manutenção pendente
Consulte custos por imóvel
Exporte relatórios em CSV
Exporte relatórios em Excel, se existir suporte
Gere relatórios PDF, se existir suporte
Aplique filtros antes de exportar
Consulte histórico de exportações
Consulte quem gerou/exportou relatórios
Controle permissões para relatórios sensíveis
Proteja dados pessoais em relatórios agregados
```

Esta sprint deve transformar dados operacionais em visão de gestão.

> Estado em 15/06/2026: implementada e validada tecnicamente. O catálogo municipal, fórmulas, perfis e políticas de retenção continuam sujeitos a validação funcional, jurídica e RGPD.

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 17.

Não avances para Sprint 18, Sprint 19, Sprint 20 ou qualquer sprint futura sem validação explícita.

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
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
docs/backlog/sprint-14-pagamentos-incumprimentos-revisao-renda.md
docs/backlog/sprint-15-manutencao-vistorias-gestao-imovel.md
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
docs/backlog/sprint-17-relatorios-indicadores-dashboard-executivo.md

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
Sistema de filas/queues
Sistema de storage privado
Sistema de geração PDF, se existir
Sistema de exportação Excel, se existir
Sistema de auditoria, se existir
Sistema de logs de acesso, se existir

Modelo User
Modelo Municipality, se existir
Modelo Program
Modelo Contest
Modelo AdhesionRegistration
Modelo Application
Modelo AdministrativeProcess
Modelo EligibilityCheck
Modelo ScoringRun
Modelo PublicationList
Modelo Complaint
Modelo Allocation
Modelo HousingUnit
Modelo LeaseContract ou Contract
Modelo RentInstallment
Modelo LeasePayment
Modelo Arrear
Modelo MaintenanceRequest
Modelo MaintenanceCost
Modelo PropertyInspection
Modelo OfficialNotification
Modelo CommunicationLog
Modelo AuditLog, se existir
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
Dashboard
DashboardWidget
ReportDefinition
ReportRun
ReportExport
ReportFilter
ReportFilterPreset
IndicatorDefinition
IndicatorSnapshot
MetricSnapshot
ExportLog
ReportAccessLog
SensitiveReportAccessLog
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY, credenciais ou chaves externas.

---

# 3. Dependências funcionais

Esta sprint depende obrigatoriamente de:

```text
Sistema de autenticação
Sistema de utilizadores
Sistema de permissões
Backoffice
Pelo menos um módulo operacional com dados reportáveis
```

Depende preferencialmente de:

```text
Sprint 8 — Candidaturas
Sprint 9 — Workflow Administrativo
Sprint 10 — Classificação
Sprint 11 — Listas e reclamações
Sprint 12 — Atribuição
Sprint 13 — Contratos
Sprint 14 — Pagamentos
Sprint 15 — Manutenção
Sprint 16 — Comunicações
Sistema de auditoria
Sistema de storage privado
Sistema de PDF
Sistema de Excel
```

## Dependência de candidaturas

Se `Application` ou equivalente não existir, os indicadores de candidaturas devem ficar documentados como pendentes.

## Dependência de habitações

Se `HousingUnit` ou equivalente não existir, os indicadores de habitações disponíveis, atribuídas e taxa de ocupação devem ficar pendentes.

## Dependência financeira

Se a Sprint 14 não existir, os indicadores de rendas em atraso devem ficar pendentes.

## Dependência de manutenção

Se a Sprint 15 não existir, os indicadores de manutenção pendente e custos por imóvel devem ficar pendentes.

A sprint não deve falhar totalmente se alguns módulos ainda não existirem. Deve implementar a infraestrutura de reporting e ativar apenas os indicadores suportados pelos dados existentes.

---

# 4. Validação jurídica, administrativa e RGPD

Relatórios podem conter dados pessoais e dados sensíveis.

Regras obrigatórias:

```text
Relatórios agregados devem ser preferidos por defeito.
Relatórios nominais devem exigir permissão específica.
Exportações devem ficar registadas.
Downloads de exportações devem ficar registados.
Relatórios sensíveis devem exigir autorização.
Indicadores executivos devem evitar dados pessoais identificáveis.
Exportações com dados pessoais devem ser rastreáveis.
Ficheiros exportados devem ficar em storage privado quando persistidos.
Não expor ficheiros por URL público.
Não incluir NIF, e-mail ou nome completo no nome do ficheiro.
Não mostrar dados pessoais no dashboard executivo, salvo permissão expressa.
Não permitir exportações sem filtros quando o volume ou sensibilidade for elevado, salvo permissão admin.
```

Não implementar BI externo, data warehouse externo, Power BI, Tableau, Metabase, Looker, BigQuery ou integrações externas nesta sprint.

Não enviar relatórios por e-mail automaticamente sem validação.

Não gerar relatórios públicos.

---

# 5. Âmbito incluído

Implementar:

```text
Dashboard operacional
Dashboard executivo
Indicadores de candidaturas
Indicadores de elegibilidade
Indicadores de exclusão
Indicadores de tempos de análise
Indicadores documentais
Indicadores de reclamações
Indicadores de habitações
Indicadores de atribuição
Indicadores de ocupação
Indicadores financeiros
Indicadores de incumprimento
Indicadores de manutenção
Indicadores de custos por imóvel
Filtros por período
Filtros por programa
Filtros por concurso
Filtros por estado
Filtros por freguesia/localização, se existir
Exportação CSV
Exportação Excel, se existir biblioteca compatível
Relatórios PDF, se existir biblioteca compatível
Histórico de relatórios gerados
Histórico de exportações
Rastreabilidade de downloads
Permissões para relatórios sensíveis
Máscara/anonimização parcial quando aplicável
Cache opcional de indicadores
Services
Controllers
Form Requests
Policies
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
BI externo
Data warehouse externo
Integração Power BI
Integração Tableau
Integração Metabase
Integração Looker
ETL complexo
Data lake
Envio automático de relatórios por e-mail
Agendamento avançado de relatórios recorrentes
Relatórios públicos
Open data
Anonimização irreversível avançada
Machine learning
Previsão estatística
Mapas GIS avançados
Gráficos geoespaciais complexos
Contabilidade oficial
SAF-T
Relatórios fiscais oficiais
```

Podem ser criados pontos de integração para sprints futuras, mas não implementar funcionalidades fora de âmbito.

---

# 7. Conceito funcional

O fluxo de consulta deve ser:

```text
Utilizador autorizado entra no backoffice
→ Seleciona dashboard operacional ou executivo
→ Define filtros
→ Sistema calcula indicadores
→ Sistema mostra cartões, tabelas e gráficos simples
→ Utilizador acede a relatório operacional
→ Utilizador ajusta filtros
→ Sistema apresenta resultados
→ Utilizador exporta CSV/Excel/PDF
→ Exportação fica registada
→ Download fica registado
```

O fluxo de relatório sensível deve ser:

```text
Utilizador abre relatório com dados pessoais
→ Policy verifica permissão sensível
→ Sistema aplica filtros obrigatórios
→ Sistema apresenta dados conforme autorização
→ Exportação exige confirmação
→ Exportação fica registada com filtros
→ Download fica registado
→ Auditoria é criada, se existir
```

---

# 8. Indicadores obrigatórios

Implementar os seguintes indicadores sempre que os dados existam.

## Candidaturas

```text
Candidaturas por concurso
Candidaturas por programa
Candidaturas por estado
Candidaturas submetidas no período
Candidaturas em análise
Candidaturas admitidas
Candidaturas elegíveis
Candidaturas excluídas
Candidaturas retiradas/canceladas
Tempo médio de análise
Tempo médio entre submissão e decisão administrativa
```

## Documentação

```text
Documentos pendentes
Documentos submetidos
Documentos rejeitados
Documentos validados
Documentos expirados
Tempo médio de validação documental
Candidaturas com documentação incompleta
```

## Reclamações e audiência

```text
Reclamações submetidas
Reclamações em análise
Reclamações deferidas
Reclamações indeferidas
Reclamações parcialmente deferidas
Tempo médio de decisão de reclamação
Audiências abertas
Audiências respondidas
Audiências pendentes
```

## Habitações e atribuição

```text
Habitações disponíveis
Habitações atribuídas
Habitações contratualizadas
Habitações ocupadas
Habitações em manutenção
Taxa de ocupação
Taxa de atribuição
Atribuições aceites
Atribuições recusadas
Lista de suplentes ativa
```

## Financeiro

```text
Rendas emitidas
Rendas pagas
Rendas em atraso
Valor total em atraso
Contratos com atraso
Dias médios de atraso
Acordos de regularização ativos
Acordos incumpridos
Revisões de renda pendentes
```

## Manutenção

```text
Pedidos de manutenção novos
Pedidos em análise
Pedidos agendados
Pedidos em execução
Pedidos resolvidos
Pedidos rejeitados
Manutenção pendente
Tempo médio de resolução
Custos por imóvel
Custos por categoria
Custos por fornecedor
Imóveis com mais ocorrências
Vistorias agendadas
Vistorias concluídas
```

## Comunicações

```text
Comunicações enviadas
Comunicações falhadas
Notificações por ler
Comunicações por evento
Comunicações com tomada de conhecimento pendente
```

---

# 9. Estados e tipos recomendados

## ReportType

```text
operational
executive
sensitive
audit
export
```

## ReportFormat

```text
html
csv
xlsx
pdf
json
```

## ReportExportStatus

```text
pending
processing
completed
failed
cancelled
expired
```

## DashboardType

```text
operational
executive
financial
maintenance
administrative
custom
```

## IndicatorCategory

```text
applications
eligibility
documents
complaints
housing
allocation
contracts
finance
maintenance
communications
system
```

## IndicatorValueType

```text
count
percentage
currency
days
ratio
average
sum
```

## ReportSensitivityLevel

```text
public_internal
restricted
sensitive
highly_sensitive
```

## ExportScope

```text
aggregated
pseudonymized
nominal
full
```

---

# 10. Modelo de dados

## 10.1 DashboardDefinition

Criar entidade:

```text
DashboardDefinition
```

Tabela:

```text
dashboard_definitions
```

Objetivo:

```text
Definir dashboards disponíveis no backoffice.
```

Campos mínimos:

```text
id
code
name
description
dashboard_type
is_active
sort_order
required_permission
created_by
updated_by
created_at
updated_at
deleted_at
```

Regras:

```text
Dashboard executivo deve existir por defeito.
Dashboard operacional deve existir por defeito.
required_permission deve ser respeitada por policy.
```

---

## 10.2 DashboardWidget

Criar entidade:

```text
DashboardWidget
```

Tabela:

```text
dashboard_widgets
```

Objetivo:

```text
Configurar cartões, indicadores, tabelas e gráficos simples do dashboard.
```

Campos mínimos:

```text
id
dashboard_definition_id
indicator_definition_id nullable

code
title
description
widget_type
category
size
sort_order
is_active

default_filters
visual_options
required_permission

created_at
updated_at
deleted_at
```

Valores recomendados para `widget_type`:

```text
metric_card
table
bar_chart
line_chart
pie_chart
status_list
alert_list
```

Regras:

```text
Não usar bibliotecas externas pesadas sem necessidade.
Se a stack frontend não suportar gráficos, usar tabelas e cartões simples.
```

---

## 10.3 IndicatorDefinition

Criar entidade:

```text
IndicatorDefinition
```

Tabela:

```text
indicator_definitions
```

Objetivo:

```text
Definir indicadores calculáveis.
```

Campos mínimos:

```text
id
code
name
description
category
value_type
calculation_service
calculation_method
is_active
is_sensitive
required_permission
cache_ttl_seconds
sort_order
created_at
updated_at
deleted_at
```

Regras:

```text
code obrigatório e único.
Indicador sensível exige permissão.
Indicadores devem ser calculados por services, não diretamente nas views.
```

---

## 10.4 IndicatorSnapshot

Criar entidade:

```text
IndicatorSnapshot
```

Tabela:

```text
indicator_snapshots
```

Objetivo:

```text
Guardar snapshots opcionais de indicadores para performance e histórico.
```

Campos mínimos:

```text
id
indicator_definition_id

period_start
period_end
program_id nullable
contest_id nullable

filters_hash
filters_snapshot

value
value_numeric
value_json

calculated_at
calculated_by

created_at
updated_at
```

Regras:

```text
Snapshots não devem substituir cálculo real quando dados mudam.
Usar cache/snapshot apenas quando tecnicamente útil.
```

---

## 10.5 ReportDefinition

Criar entidade:

```text
ReportDefinition
```

Tabela:

```text
report_definitions
```

Objetivo:

```text
Definir relatórios operacionais e executivos disponíveis.
```

Campos mínimos:

```text
id
code
name
description
report_type
category
sensitivity_level
default_format
allowed_formats
is_active
requires_filters
required_permission
query_service
query_method
created_by
updated_by
created_at
updated_at
deleted_at
```

Relatórios iniciais recomendados:

```text
applications_by_contest
application_status_summary
eligibility_summary
document_pending_report
complaints_summary
housing_occupancy_report
allocation_summary
financial_arrears_report
maintenance_pending_report
maintenance_costs_by_property
executive_summary
```

---

## 10.6 ReportFilterPreset

Criar entidade:

```text
ReportFilterPreset
```

Tabela:

```text
report_filter_presets
```

Objetivo:

```text
Guardar filtros reutilizáveis por utilizador ou globais.
```

Campos mínimos:

```text
id
report_definition_id
user_id nullable

name
description
filters
is_global
is_default

created_at
updated_at
deleted_at
```

---

## 10.7 ReportRun

Criar entidade:

```text
ReportRun
```

Tabela:

```text
report_runs
```

Objetivo:

```text
Registar cada execução de relatório.
```

Campos mínimos:

```text
id
report_definition_id
user_id

status
format
filters
filters_hash
row_count

started_at
completed_at
failed_at
failure_reason

created_at
updated_at
deleted_at
```

Estados recomendados:

```text
started
completed
failed
cancelled
```

Regras:

```text
Execuções de relatórios sensíveis devem ser registadas.
filters deve guardar filtros aplicados.
Não guardar resultados completos se contiverem dados pessoais, salvo exportação autorizada.
```

---

## 10.8 ReportExport

Criar entidade:

```text
ReportExport
```

Tabela:

```text
report_exports
```

Objetivo:

```text
Guardar exportações geradas.
```

Campos mínimos:

```text
id
report_run_id
report_definition_id
user_id

export_number
format
status
scope
sensitivity_level

filename
storage_disk
storage_path
mime_type
file_size
checksum

filters_snapshot
row_count

generated_at
downloaded_at
expires_at
failed_at
failure_reason

created_at
updated_at
deleted_at
```

Regras:

```text
export_number obrigatório e único.
Ficheiros devem ficar em storage privado.
Não expor storage_path.
Downloads devem passar por controller autorizado.
Exportações sensíveis devem ter rastreabilidade.
```

---

## 10.9 ReportDownloadLog

Criar entidade:

```text
ReportDownloadLog
```

Tabela:

```text
report_download_logs
```

Objetivo:

```text
Registar downloads de exportações.
```

Campos mínimos:

```text
id
report_export_id
user_id
ip_address
user_agent
downloaded_at
created_at
```

Regras:

```text
Registar download de todas as exportações.
Para relatórios sensíveis, auditoria adicional se existir.
```

---

## 10.10 ReportAccessLog

Criar entidade:

```text
ReportAccessLog
```

Tabela:

```text
report_access_logs
```

Objetivo:

```text
Registar acesso a relatórios sensíveis ou executivos.
```

Campos mínimos:

```text
id
report_definition_id nullable
dashboard_definition_id nullable
user_id

access_type
sensitivity_level
filters
ip_address
user_agent
accessed_at

created_at
```

Valores de `access_type`:

```text
view_dashboard
view_report
run_report
export_report
download_export
```

---

# 11. Enums recomendados

Criar, se a versão do PHP permitir:

```text
App\Enums\ReportType
App\Enums\ReportFormat
App\Enums\ReportRunStatus
App\Enums\ReportExportStatus
App\Enums\DashboardType
App\Enums\DashboardWidgetType
App\Enums\IndicatorCategory
App\Enums\IndicatorValueType
App\Enums\ReportSensitivityLevel
App\Enums\ExportScope
App\Enums\ReportAccessType
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 12. Relações obrigatórias

## User

Adicionar:

```text
hasMany ReportRun
hasMany ReportExport
hasMany ReportDownloadLog
hasMany ReportAccessLog
hasMany ReportFilterPreset
```

## DashboardDefinition

```text
hasMany DashboardWidget
belongsTo User as createdBy nullable
belongsTo User as updatedBy nullable
```

## DashboardWidget

```text
belongsTo DashboardDefinition
belongsTo IndicatorDefinition nullable
```

## IndicatorDefinition

```text
hasMany IndicatorSnapshot
hasMany DashboardWidget
```

## ReportDefinition

```text
hasMany ReportFilterPreset
hasMany ReportRun
hasMany ReportExport
belongsTo User as createdBy nullable
belongsTo User as updatedBy nullable
```

## ReportRun

```text
belongsTo ReportDefinition
belongsTo User
hasMany ReportExport
```

## ReportExport

```text
belongsTo ReportRun
belongsTo ReportDefinition
belongsTo User
hasMany ReportDownloadLog
```

## ReportDownloadLog

```text
belongsTo ReportExport
belongsTo User
```

## ReportAccessLog

```text
belongsTo ReportDefinition nullable
belongsTo DashboardDefinition nullable
belongsTo User
```

---

# 13. Services obrigatórios

Criar:

```text
App\Services\Reports\DashboardService
App\Services\Reports\ExecutiveDashboardService
App\Services\Reports\OperationalDashboardService
App\Services\Reports\IndicatorRegistry
App\Services\Reports\IndicatorCalculationService
App\Services\Reports\ReportDefinitionService
App\Services\Reports\ReportFilterService
App\Services\Reports\ReportRunService
App\Services\Reports\ReportExportService
App\Services\Reports\ReportDownloadService
App\Services\Reports\ReportAccessLogger
App\Services\Reports\ReportPermissionService
App\Services\Reports\SensitiveDataMaskingService

App\Services\Reports\Indicators\ApplicationIndicatorsService
App\Services\Reports\Indicators\DocumentIndicatorsService
App\Services\Reports\Indicators\ComplaintIndicatorsService
App\Services\Reports\Indicators\HousingIndicatorsService
App\Services\Reports\Indicators\AllocationIndicatorsService
App\Services\Reports\Indicators\FinanceIndicatorsService
App\Services\Reports\Indicators\MaintenanceIndicatorsService
App\Services\Reports\Indicators\CommunicationIndicatorsService

App\Services\Reports\Exports\CsvReportExporter
App\Services\Reports\Exports\ExcelReportExporter
App\Services\Reports\Exports\PdfReportExporter
```

---

## 13.1 DashboardService

Responsável por:

```text
Resolver dashboards disponíveis por utilizador
Aplicar permissões
Aplicar filtros
Obter widgets ativos
Calcular indicadores
Preparar dados para views
Registar acesso quando sensível
```

---

## 13.2 ExecutiveDashboardService

Responsável por:

```text
Gerar visão estratégica agregada
Evitar dados pessoais por defeito
Mostrar indicadores globais
Mostrar evolução por período
Mostrar alertas de gestão
Preparar dados para reuniões e deliberações
```

Indicadores executivos mínimos:

```text
Candidaturas submetidas
Candidaturas elegíveis
Candidaturas excluídas
Tempo médio de análise
Habitações disponíveis
Habitações atribuídas
Taxa de ocupação
Rendas em atraso
Manutenção pendente
Custos por imóvel
```

---

## 13.3 OperationalDashboardService

Responsável por:

```text
Gerar visão diária operacional
Mostrar tarefas pendentes
Mostrar documentos pendentes
Mostrar candidaturas em análise
Mostrar reclamações pendentes
Mostrar pagamentos em atraso
Mostrar manutenção pendente
Mostrar comunicações falhadas
```

---

## 13.4 IndicatorCalculationService

Responsável por:

```text
Executar cálculo de indicadores
Validar filtros
Delegar para service por módulo
Normalizar resultados
Tratar módulos inexistentes
Devolver indicador como indisponível quando dependência faltar
Usar cache quando configurado
```

Formato recomendado de resposta de indicador:

```text
code
label
value
formatted_value
value_type
category
status
description
filters
calculated_at
```

Estados recomendados:

```text
available
unavailable
pending_dependency
error
restricted
```

---

## 13.5 ReportFilterService

Responsável por:

```text
Validar filtros
Normalizar datas
Aplicar período
Aplicar programa
Aplicar concurso
Aplicar estado
Aplicar freguesia/localização, se existir
Aplicar limites de volume
Gerar hash de filtros
Guardar filtros usados
```

Filtros mínimos:

```text
period_start
period_end
program_id
contest_id
status
housing_unit_id
parish_id
include_personal_data
```

---

## 13.6 ReportRunService

Responsável por:

```text
Executar relatório
Validar permissões
Aplicar filtros
Registar ReportRun
Contar linhas
Tratar erros
Registar falhas
```

---

## 13.7 ReportExportService

Responsável por:

```text
Exportar relatório
Validar formato permitido
Validar permissões sensíveis
Gerar CSV
Gerar Excel se biblioteca existir
Gerar PDF se infraestrutura existir
Guardar ficheiro em storage privado
Criar ReportExport
Gerar checksum
Registar filtros
Registar scope
Registar sensibilidade
```

Se não existir biblioteca Excel:

```text
Gerar CSV como fallback.
Documentar ausência de Excel real.
Não afirmar que Excel foi gerado.
```

Se não existir biblioteca PDF:

```text
Gerar HTML imprimível ou documentar pendência.
Não afirmar que PDF foi gerado.
```

---

## 13.8 ReportDownloadService

Responsável por:

```text
Validar acesso ao ficheiro
Registar download
Ocultar storage_path
Servir ficheiro por response seguro
Bloquear exportação expirada
Criar auditoria se existir
```

---

## 13.9 ReportAccessLogger

Responsável por:

```text
Registar acesso a dashboard executivo
Registar execução de relatório
Registar exportação
Registar download
Registar filtros usados
Registar IP e user agent quando disponível
```

---

## 13.10 SensitiveDataMaskingService

Responsável por:

```text
Mascarar nomes
Mascarar NIF
Mascarar e-mail
Mascarar telefone
Remover moradas completas
Gerar referências pseudonimizadas
Aplicar export scope
```

Regras:

```text
Dashboard executivo deve usar dados agregados.
Relatório pseudonimizado deve usar referência/processo sem nome completo.
Relatório nominal exige permissão específica.
```

---

# 14. Indicadores por service

## ApplicationIndicatorsService

Implementar:

```text
countApplicationsByContest
countApplicationsByProgram
countApplicationsByStatus
countSubmittedApplications
countEligibleApplications
countExcludedApplications
averageAnalysisTime
averageSubmissionToDecisionTime
```

## DocumentIndicatorsService

Implementar:

```text
countPendingDocuments
countSubmittedDocuments
countRejectedDocuments
countValidatedDocuments
countExpiredDocuments
averageDocumentValidationTime
countApplicationsWithIncompleteDocuments
```

## ComplaintIndicatorsService

Implementar:

```text
countComplaintsSubmitted
countComplaintsUnderReview
countComplaintsAccepted
countComplaintsRejected
countComplaintsPartiallyAccepted
averageComplaintDecisionTime
countPendingHearings
```

## HousingIndicatorsService

Implementar:

```text
countAvailableHousingUnits
countAllocatedHousingUnits
countContractedHousingUnits
countOccupiedHousingUnits
countHousingUnitsUnderMaintenance
calculateOccupancyRate
```

## AllocationIndicatorsService

Implementar:

```text
countAllocations
countAcceptedAllocations
countRefusedAllocations
countPendingAllocationResponses
countActiveReserveListEntries
calculateAllocationRate
```

## FinanceIndicatorsService

Implementar:

```text
sumIssuedRent
sumPaidRent
sumOverdueRent
countContractsWithArrears
averageDaysOverdue
countActiveRegularizationAgreements
countBreachedRegularizationAgreements
countPendingRentReviews
```

## MaintenanceIndicatorsService

Implementar:

```text
countMaintenanceRequestsByStatus
countPendingMaintenanceRequests
averageMaintenanceResolutionTime
sumMaintenanceCosts
sumMaintenanceCostsByProperty
sumMaintenanceCostsByCategory
countPropertiesWithMostOccurrences
countScheduledInspections
countCompletedInspections
```

## CommunicationIndicatorsService

Implementar:

```text
countSentCommunications
countFailedCommunications
countUnreadNotifications
countCommunicationsByEvent
countPendingAcknowledgements
```

---

# 15. Controllers

Criar controllers conforme a arquitetura existente.

## Backoffice

Namespace recomendado:

```text
App\Http\Controllers\Backoffice
```

Controllers:

```text
Backoffice\ExecutiveDashboardController
Backoffice\OperationalDashboardController
Backoffice\ReportDefinitionController
Backoffice\ReportRunController
Backoffice\ReportExportController
Backoffice\ReportDownloadController
Backoffice\ReportFilterPresetController
Backoffice\IndicatorController
Backoffice\DashboardDefinitionController
Backoffice\DashboardWidgetController
```

Não criar área de relatórios pública.

Não criar área de relatórios para candidato nesta sprint, salvo consulta limitada de dados próprios se já existir padrão. O foco é municipal/backoffice.

---

# 16. Form Requests

Criar:

```text
DashboardFilterRequest
RunReportRequest
ExportReportRequest
DownloadReportExportRequest
StoreReportDefinitionRequest
UpdateReportDefinitionRequest
StoreReportFilterPresetRequest
UpdateReportFilterPresetRequest
StoreDashboardDefinitionRequest
UpdateDashboardDefinitionRequest
StoreDashboardWidgetRequest
UpdateDashboardWidgetRequest
StoreIndicatorDefinitionRequest
UpdateIndicatorDefinitionRequest
```

## DashboardFilterRequest

```text
period_start nullable|date
period_end nullable|date|after_or_equal:period_start
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
status nullable|string|max:100
parish_id nullable|integer
```

## RunReportRequest

```text
report_definition_id required|exists:report_definitions,id
period_start nullable|date
period_end nullable|date|after_or_equal:period_start
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
status nullable|string|max:100
filters nullable|array
include_personal_data nullable|boolean
```

## ExportReportRequest

```text
report_definition_id required|exists:report_definitions,id
format required|string|in:csv,xlsx,pdf
scope required|string|max:100
period_start nullable|date
period_end nullable|date|after_or_equal:period_start
program_id nullable|exists:programs,id
contest_id nullable|exists:contests,id
status nullable|string|max:100
filters nullable|array
include_personal_data nullable|boolean
confirm_sensitive_export nullable|accepted
```

Regras adicionais:

```text
scope nominal ou full exige permissão sensível.
include_personal_data=true exige permissão sensível.
Relatórios altamente sensíveis exigem confirmação.
```

## StoreReportDefinitionRequest

```text
code required|string|max:150
name required|string|max:255
description nullable|string|max:3000
report_type required|string|max:100
category required|string|max:100
sensitivity_level required|string|max:100
default_format required|string|max:20
allowed_formats required|array|min:1
allowed_formats.* string|max:20
is_active boolean
requires_filters boolean
required_permission nullable|string|max:255
query_service required|string|max:255
query_method required|string|max:255
```

## StoreDashboardWidgetRequest

```text
dashboard_definition_id required|exists:dashboard_definitions,id
indicator_definition_id nullable|exists:indicator_definitions,id
code required|string|max:150
title required|string|max:255
description nullable|string|max:3000
widget_type required|string|max:100
category required|string|max:100
size nullable|string|max:50
sort_order nullable|integer|min:0
is_active boolean
default_filters nullable|array
visual_options nullable|array
required_permission nullable|string|max:255
```

---

# 17. Policies

Criar:

```text
DashboardDefinitionPolicy
DashboardWidgetPolicy
IndicatorDefinitionPolicy
IndicatorSnapshotPolicy
ReportDefinitionPolicy
ReportRunPolicy
ReportExportPolicy
ReportDownloadPolicy
ReportFilterPresetPolicy
ReportAccessLogPolicy
```

## Regras para técnico municipal

```text
Pode ver dashboard operacional se autorizado.
Pode ver relatórios operacionais dos processos que gere.
Pode exportar relatórios agregados se autorizado.
Não pode exportar relatórios nominais sensíveis sem permissão específica.
Não pode ver relatórios executivos se não tiver permissão.
```

## Regras para direção/decisor municipal

```text
Pode ver dashboard executivo.
Pode ver indicadores agregados.
Pode exportar relatórios executivos.
Pode consultar relatórios para reunião e deliberação.
```

## Regras para gestor financeiro

```text
Pode ver indicadores financeiros se autorizado.
Pode exportar relatórios financeiros conforme permissão.
Não vê dados de manutenção sensíveis se não autorizado.
```

## Regras para gestor de manutenção

```text
Pode ver indicadores de manutenção.
Pode exportar relatórios de custos por imóvel conforme permissão.
Não vê dados financeiros de rendas se não autorizado.
```

## Regras para admin

```text
Pode gerir dashboards.
Pode gerir definições de relatórios.
Pode gerir widgets.
Pode exportar relatórios conforme política.
Pode consultar histórico de exportações.
```

## Regras para auditor

```text
Pode consultar relatórios e histórico de acessos conforme perfil.
Pode consultar exportações e downloads.
Não altera definições de relatórios.
Não altera dashboards.
Não executa exportações sensíveis salvo permissão explícita.
```

---

# 18. Rotas

Criar, preferencialmente:

```text
GET /backoffice/reports
GET /backoffice/reports/dashboard
GET /backoffice/reports/dashboard/operational
GET /backoffice/reports/dashboard/executive

GET /backoffice/reports/indicators
GET /backoffice/reports/indicators/{indicatorDefinition}

GET /backoffice/reports/definitions
GET /backoffice/reports/definitions/create
POST /backoffice/reports/definitions
GET /backoffice/reports/definitions/{reportDefinition}
GET /backoffice/reports/definitions/{reportDefinition}/edit
PUT/PATCH /backoffice/reports/definitions/{reportDefinition}

POST /backoffice/reports/run
GET /backoffice/reports/runs
GET /backoffice/reports/runs/{reportRun}

POST /backoffice/reports/export
GET /backoffice/reports/exports
GET /backoffice/reports/exports/{reportExport}
GET /backoffice/reports/exports/{reportExport}/download

GET /backoffice/reports/filter-presets
POST /backoffice/reports/filter-presets
PUT/PATCH /backoffice/reports/filter-presets/{reportFilterPreset}
DELETE /backoffice/reports/filter-presets/{reportFilterPreset}

GET /backoffice/reports/dashboards
GET /backoffice/reports/dashboards/create
POST /backoffice/reports/dashboards
GET /backoffice/reports/dashboards/{dashboardDefinition}/edit
PUT/PATCH /backoffice/reports/dashboards/{dashboardDefinition}

GET /backoffice/reports/widgets
POST /backoffice/reports/widgets
PUT/PATCH /backoffice/reports/widgets/{dashboardWidget}
DELETE /backoffice/reports/widgets/{dashboardWidget}

GET /backoffice/reports/access-logs
GET /backoffice/reports/download-logs
```

Todas as rotas devem estar protegidas por autenticação e policies.

---

# 19. Views / páginas

Se o projeto usa Blade, criar:

```text
resources/views/backoffice/reports/index.blade.php
resources/views/backoffice/reports/dashboard.blade.php
resources/views/backoffice/reports/dashboard-operational.blade.php
resources/views/backoffice/reports/dashboard-executive.blade.php

resources/views/backoffice/reports/indicators/index.blade.php
resources/views/backoffice/reports/indicators/show.blade.php

resources/views/backoffice/reports/definitions/index.blade.php
resources/views/backoffice/reports/definitions/create.blade.php
resources/views/backoffice/reports/definitions/edit.blade.php
resources/views/backoffice/reports/definitions/show.blade.php

resources/views/backoffice/reports/runs/index.blade.php
resources/views/backoffice/reports/runs/show.blade.php

resources/views/backoffice/reports/exports/index.blade.php
resources/views/backoffice/reports/exports/show.blade.php

resources/views/backoffice/reports/filter-presets/index.blade.php

resources/views/backoffice/reports/dashboards/index.blade.php
resources/views/backoffice/reports/dashboards/create.blade.php
resources/views/backoffice/reports/dashboards/edit.blade.php

resources/views/backoffice/reports/access-logs/index.blade.php
resources/views/backoffice/reports/download-logs/index.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 20. UX obrigatória

## Dashboard operacional

Mostrar:

```text
Candidaturas em análise
Documentos pendentes
Aperfeiçoamentos pendentes
Reclamações pendentes
Habitações disponíveis
Atribuições pendentes de resposta
Contratos em preparação
Rendas em atraso
Manutenção pendente
Comunicações falhadas
```

## Dashboard executivo

Mostrar:

```text
Candidaturas por concurso
Candidaturas elegíveis
Candidaturas excluídas
Tempo médio de análise
Habitações disponíveis
Habitações atribuídas
Taxa de ocupação
Total de rendas em atraso
Manutenção pendente
Custos por imóvel
Resumo por programa
Resumo por concurso
Tendência mensal
Alertas críticos
```

## Filtros globais

Todas as páginas principais devem permitir:

```text
Período
Programa
Concurso
Estado
Freguesia/localização, se existir
```

## Relatórios

A página de relatório deve mostrar:

```text
Nome do relatório
Descrição
Sensibilidade
Filtros aplicados
Data de execução
Utilizador executor
Total de registos
Tabela de resultados
Botões de exportação autorizados
Aviso de dados sensíveis, se aplicável
```

Copy obrigatório em relatórios sensíveis:

```text
Este relatório pode conter dados pessoais ou informação sensível. A consulta e exportação ficam registadas para efeitos de auditoria.
```

## Exportação

Antes de exportar relatório sensível, mostrar confirmação:

```text
Confirma a exportação deste relatório? A operação ficará registada com os filtros aplicados, data, utilizador e formato exportado.
```

---

# 21. Regras de cálculo

Todos os indicadores devem:

```text
Aplicar filtros ativos
Respeitar permissões
Tratar ausência de módulo como indicador indisponível
Evitar queries N+1
Usar aggregates no banco de dados sempre que possível
Evitar carregar datasets completos em memória
Normalizar datas
Considerar timezone da aplicação
```

## Tempo médio de análise

Calcular preferencialmente entre:

```text
submitted_at
administrative_decision_at
```

ou campos equivalentes.

Se não existirem, usar histórico de estados.

Se não houver dados suficientes, devolver indicador indisponível.

## Taxa de ocupação

Fórmula recomendada:

```text
taxa_ocupacao = habitações_ocupadas / total_habitações_ativas * 100
```

Se denominador for zero, devolver 0 ou indisponível de forma segura.

## Rendas em atraso

Calcular preferencialmente a partir de:

```text
RentInstallment
Arrear
LeasePayment
PaymentAllocation
```

Se Sprint 14 não existir, indicador indisponível.

## Custos por imóvel

Calcular preferencialmente a partir de:

```text
MaintenanceCost
HousingUnit
MaintenanceRequest
```

Se Sprint 15 não existir, indicador indisponível.

---

# 22. Exportações

## CSV

Obrigatório.

Regras:

```text
Gerar UTF-8
Incluir cabeçalhos
Respeitar filtros
Respeitar permissões
Registar exportação
Guardar em storage privado se persistido
```

## Excel/XLSX

Implementar apenas se existir biblioteca compatível.

Se não existir:

```text
Disponibilizar CSV como fallback.
Documentar ausência de suporte Excel real.
Não afirmar que XLSX foi gerado.
```

## PDF

Implementar apenas se existir biblioteca PDF compatível.

Se não existir:

```text
Gerar HTML imprimível ou documentar pendência.
Não afirmar que PDF foi gerado.
```

## Nome de ficheiros

Não incluir:

```text
NIF
email
telefone
nome completo
morada
```

Exemplo permitido:

```text
relatorio-candidaturas-2026-06-15-abc123.csv
```

---

# 23. Relatórios mínimos

Criar definições iniciais para:

## applications_by_contest

```text
Candidaturas por concurso
Formato: tabela + agregado
Filtros: período, programa, concurso, estado
Sensibilidade: restricted
```

## application_status_summary

```text
Resumo de estados de candidatura
Formato: tabela agregada
Filtros: período, programa, concurso
Sensibilidade: public_internal
```

## document_pending_report

```text
Documentos pendentes
Formato: tabela operacional
Filtros: período, programa, concurso, tipo de documento
Sensibilidade: sensitive
```

## complaints_summary

```text
Resumo de reclamações
Formato: tabela agregada
Filtros: período, concurso, estado
Sensibilidade: restricted
```

## housing_occupancy_report

```text
Ocupação de habitações
Formato: tabela agregada
Filtros: estado, tipologia, localização
Sensibilidade: restricted
```

## financial_arrears_report

```text
Rendas em atraso
Formato: tabela operacional
Filtros: período, estado, dias em atraso
Sensibilidade: highly_sensitive
```

## maintenance_pending_report

```text
Manutenção pendente
Formato: tabela operacional
Filtros: estado, urgência, categoria, imóvel
Sensibilidade: restricted
```

## maintenance_costs_by_property

```text
Custos por imóvel
Formato: tabela agregada
Filtros: período, imóvel, categoria
Sensibilidade: sensitive
```

## executive_summary

```text
Resumo executivo
Formato: agregado
Filtros: período, programa, concurso
Sensibilidade: restricted
```

---

# 24. Integração com auditoria

Se existir auditoria, auditar:

```text
Acesso ao dashboard executivo
Acesso a relatório sensível
Execução de relatório
Exportação de relatório
Download de exportação
Criação de definição de relatório
Alteração de definição de relatório
Criação de dashboard
Alteração de dashboard
Criação de widget
Alteração de widget
```

Não criar auditoria paralela se já existir sistema de auditoria.

Se não existir auditoria, usar ReportAccessLog e ReportDownloadLog como rastreabilidade mínima.

---

# 25. RGPD e segurança

Regras obrigatórias:

```text
Dados pessoais devem ser minimizados.
Dashboards executivos devem ser agregados por defeito.
Relatórios nominais exigem permissão específica.
Exportações sensíveis exigem confirmação.
Exportações devem ser registadas.
Downloads devem ser registados.
Ficheiros devem ficar em storage privado.
Não expor storage_path.
Não colocar dados pessoais no nome do ficheiro.
Não permitir mass assignment de permissões.
Não permitir acesso por URL público.
Não permitir exportação full sem permissão admin/sensível.
Logs técnicos não devem conter datasets completos.
```

---

# 26. Seeders e factories

Criar factories:

```text
DashboardDefinitionFactory
DashboardWidgetFactory
IndicatorDefinitionFactory
IndicatorSnapshotFactory
ReportDefinitionFactory
ReportFilterPresetFactory
ReportRunFactory
ReportExportFactory
ReportDownloadLogFactory
ReportAccessLogFactory
```

Criar seeders:

```text
DashboardDefinitionSeeder
IndicatorDefinitionSeeder
ReportDefinitionSeeder
DashboardWidgetSeeder
ReportDemoSeeder
```

Usar apenas dados fictícios.

Não usar dados pessoais reais.

Relatórios demo devem conter aviso interno:

```text
RELATÓRIO DEMO — DADOS FICTÍCIOS
```

---

# 27. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e autorização

```text
guest_cannot_access_reports
unauthorized_user_cannot_access_executive_dashboard
authorized_user_can_access_operational_dashboard
authorized_user_can_access_executive_dashboard
technician_cannot_access_sensitive_financial_report_without_permission
auditor_can_view_report_access_logs_without_editing
admin_can_manage_report_definitions
```

## Dashboards

```text
operational_dashboard_loads_with_available_indicators
executive_dashboard_loads_with_aggregated_indicators
dashboard_applies_period_filter
dashboard_applies_program_filter
dashboard_applies_contest_filter
dashboard_handles_missing_module_as_unavailable_indicator
dashboard_does_not_expose_personal_data_by_default
```

## Indicadores de candidaturas

```text
indicator_counts_applications_by_contest
indicator_counts_eligible_applications
indicator_counts_excluded_applications
indicator_calculates_average_analysis_time_when_dates_exist
indicator_returns_unavailable_when_required_data_missing
```

## Indicadores documentais

```text
indicator_counts_pending_documents
indicator_counts_rejected_documents
indicator_counts_validated_documents
document_indicator_respects_contest_filter
```

## Indicadores de habitação

```text
indicator_counts_available_housing_units
indicator_counts_allocated_housing_units
indicator_calculates_occupancy_rate
occupancy_rate_handles_zero_denominator
```

## Indicadores financeiros

```text
indicator_counts_overdue_rents_when_finance_module_exists
indicator_sums_overdue_amount
financial_indicator_is_restricted
unauthorized_user_cannot_export_financial_arrears
```

## Indicadores de manutenção

```text
indicator_counts_pending_maintenance_requests
indicator_sums_maintenance_costs_by_property
maintenance_indicator_respects_period_filter
```

## Relatórios

```text
report_definition_can_be_created
report_definition_requires_code_name_type_and_service
report_run_is_registered
report_run_stores_filters
report_run_records_failure_reason_on_error
sensitive_report_access_is_logged
```

## Exportações

```text
csv_export_can_be_generated
csv_export_contains_headers
csv_export_respects_filters
export_is_stored_privately
export_download_requires_authorization
export_download_is_logged
sensitive_export_requires_confirmation
nominal_export_requires_sensitive_permission
export_filename_does_not_contain_nif
export_filename_does_not_contain_email
export_filename_does_not_contain_name
```

## PDF e Excel

```text
xlsx_export_uses_library_when_available
xlsx_export_falls_back_or_is_documented_when_library_missing
pdf_report_uses_library_when_available
pdf_report_falls_back_or_is_documented_when_library_missing
```

## Segurança

```text
user_cannot_mass_assign_report_sensitivity
user_cannot_mass_assign_required_permission
user_cannot_download_other_users_restricted_export_without_permission
report_storage_path_is_not_exposed
executive_dashboard_does_not_show_personal_data
```

## Auditoria, se existir

```text
viewing_executive_dashboard_generates_audit_log
running_sensitive_report_generates_audit_log
exporting_sensitive_report_generates_audit_log
downloading_export_generates_audit_log
updating_report_definition_generates_audit_log
```

Se alguma dependência não existir, documentar teste como pendente em vez de criar teste quebrado.

---

# 28. Comandos de validação

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

# 29. Atualização documental obrigatória

Atualizar, se existirem:

```text
docs/backlog/sprint-17-relatorios-indicadores-dashboard-executivo.md
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
Pendências para Sprint 18
Indicadores implementados
Indicadores indisponíveis por dependência em falta
Limitações de Excel
Limitações de PDF
Regras de privacidade aplicadas
Regras de rastreabilidade aplicadas
```

---

# 30. Critérios de aceitação

A Sprint 17 está concluída quando:

```text
Existe dashboard operacional
Existe dashboard executivo
Dashboard operacional mostra pendências reais ou indisponibilidades justificadas
Dashboard executivo mostra indicadores agregados
Existem indicadores de candidaturas por concurso
Existem indicadores de candidaturas elegíveis
Existem indicadores de candidaturas excluídas
Existe indicador de tempo médio de análise
Existem indicadores de documentos pendentes
Existem indicadores de reclamações
Existem indicadores de habitações disponíveis
Existem indicadores de habitações atribuídas
Existe indicador de taxa de ocupação
Existem indicadores de rendas em atraso, se Sprint 14 existir
Existem indicadores de manutenção pendente, se Sprint 15 existir
Existem indicadores de custos por imóvel, se Sprint 15 existir
Existem filtros por período
Existem filtros por programa
Existem filtros por concurso
Existem filtros por estado
Existe exportação CSV
Existe exportação Excel ou fallback documentado
Existe relatório PDF ou fallback documentado
Exportações respeitam filtros
Exportações ficam registadas
Downloads ficam registados
Relatórios sensíveis exigem permissão
Dados pessoais sensíveis são protegidos por permissões
Dashboard executivo não expõe dados pessoais por defeito
A equipa consegue acompanhar o processo sem consultar candidatura a candidatura
Os relatórios ajudam a preparar reuniões e deliberações
Backoffice exige autenticação e autorização
Auditoria é usada se existir
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foi implementado BI externo
Não foram criadas integrações externas não autorizadas
Não foram introduzidas credenciais
```

---

# 31. Resposta final esperada do Codex

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
19. Indicadores implementados
20. Indicadores indisponíveis por dependência em falta
21. Limitações de Excel
22. Limitações de PDF
23. Regras de privacidade implementadas
24. Regras de rastreabilidade implementadas
25. Confirmação de que não foram implementadas funcionalidades fora de âmbito
26. Recomendação objetiva para avançar ou não para Sprint 18
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 32. Definition of Done

A Sprint 17 só está concluída quando a plataforma permitir acompanhar operacional e estrategicamente o programa municipal de Arrendamento Acessível através de dashboards, indicadores, relatórios filtráveis, exportações rastreáveis, proteção de dados sensíveis e histórico de acessos/exportações.

O resultado deve permitir que a equipa municipal prepare reuniões, deliberações e pontos de situação sem consultar candidatura a candidatura.

Fim da Sprint 17.

---

# Relatório de execução — 15/06/2026

## Implementado

- 10 tabelas de reporting, 11 enums, 10 models, 10 Policies e 14 Form Requests.
- 12 controllers/handlers de backoffice e 37 rotas sob `/backoffice/reports`.
- serviços de indicadores por 8 domínios, dashboards, execução, exportação, download, masking, permissões, filtros, registries e logs;
- 23 indicadores iniciais, 2 dashboards, 18 widgets e 10 relatórios em seeders idempotentes;
- páginas Blade para catálogo, dashboards, indicadores, definições, execuções, exportações, presets, configuração e logs;
- CSV privado, HTML imprimível, fallback XLSX→CSV e PDF→HTML;
- teste `Sprint17ReportingDashboardTest` com 8 testes/44 asserções.

## Ficheiros e dados

- migration criada: `2026_06_15_020000_create_reporting_tables.php`;
- seeders executados: acesso, indicadores, dashboards, widgets e relatórios;
- factories criadas para todas as entidades da Sprint 17;
- não foram introduzidos dados pessoais reais, credenciais, tokens ou chaves;
- `.env` não foi alterado.

## Validação executada

- `php artisan migrate --force`: passou;
- seeders específicos da Sprint 17: passaram;
- validação direta dos 23 indicadores: todos `available`;
- validação direta das 10 queries: corrigida incompatibilidade `contests.title`; depois passou;
- `php artisan test tests/Feature/Sprint17ReportingDashboardTest.php`: 8/8, 44 asserções;
- `php artisan test`: 147/147, 898 asserções;
- `npm run build`: passou;
- `composer validate --no-check-publish`: passou;
- `php artisan route:list --path=backoffice/reports`: 37 rotas;
- `php artisan view:cache` e `view:clear`: passaram;
- `./vendor/bin/pint` e `./vendor/bin/pint --test`: passaram;
- validação no browser local em `127.0.0.1:8001`: autenticação obrigatória, catálogo e dashboards operacional/executivo renderizados sem erros de consola;
- validação responsiva em `1440x1000` e `390x844`: sem overflow horizontal e com navegação móvel colapsada;
- captura PNG pelo browser integrado: não concluída por timeout do comando CDP `Page.captureScreenshot`; sem impacto na navegação ou inspeção DOM;
- PHPStan/Psalm: não executados, porque não estão instalados.

## Pendências

- validação municipal dos indicadores, fórmulas, catálogo e perfis;
- testes de volume, índices e estratégia de snapshots periódicos;
- biblioteca XLSX e infraestrutura PDF;
- retenção, anonimização estatística, revisão de perfis, pedidos de titular e DPIA na Sprint 18.

Não foram implementadas funcionalidades da Sprint 18 ou posteriores.
