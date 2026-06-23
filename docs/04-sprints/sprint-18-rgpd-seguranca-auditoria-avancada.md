# Sprint 18 — RGPD, Segurança e Auditoria Avançada

## Prioridade de desenvolvimento

Esta sprint pertence à fase de preparação da plataforma para operação real com dados sensíveis, auditoria externa e utilização municipal em ambiente produtivo.

A Sprint 18 deve ser executada depois de existirem os principais módulos funcionais da plataforma:

```text
Sprint 4 — Registo de Adesão e Área Pessoal
Sprint 6 — Gestão Documental Avançada
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
Sprint 18 — RGPD, Segurança e Auditoria Avançada
```

Esta sprint deve reforçar permissões, autenticação, logs, auditoria, consentimentos, retenção documental, anonimização, exportação de dados, pedidos RGPD, encriptação seletiva, alertas de segurança, armazenamento privado, backups, políticas de password e checklist pré-produção.

---

# 1. Objetivo da Sprint

Preparar a plataforma para operação real com dados sensíveis.

A plataforma deve permitir que o Município:

```text
Reveja permissões por perfil
Reveja permissões por módulo
Reforce autenticação do backoffice com MFA
Registe acessos ao backoffice
Registe acessos a dados sensíveis
Registe downloads de documentos
Registe exportações
Registe alterações críticas
Consulte trilho de auditoria completo
Configure finalidades de tratamento
Registe consentimentos por finalidade
Consulte histórico de consentimentos
Defina políticas de retenção documental
Defina regras de arquivo e eliminação
Execute anonimização controlada
Execute pseudonimização quando aplicável
Responda a pedidos RGPD do titular
Exporte dados do titular
Registe retificação, oposição, apagamento ou limitação
Encripte campos sensíveis quando tecnicamente aplicável
Gere alertas de acesso indevido ou suspeito
Reveja armazenamento privado de documentos
Reveja política de backups
Reveja políticas de passwords
Execute checklist de segurança pré-produção
Produza relatório de conformidade operacional
```

A plataforma deve permitir que o candidato/arrendatário:

```text
Consulte consentimentos próprios
Atualize consentimentos opcionais, quando aplicável
Submeta pedido RGPD
Consulte estado dos seus pedidos RGPD
Descarregue exportação de dados autorizada
Consulte informação sobre finalidades de tratamento
```

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 18.

Não avances para Sprint 19, Sprint 20, Sprint 21 ou qualquer sprint futura sem validação explícita.

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

docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
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
docs/backlog/sprint-18-rgpd-seguranca-auditoria-avancada.md

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
Sistema de middleware
Sistema de policies
Sistema de password reset
Sistema de MFA/2FA, se existir
Sistema de notificações
Sistema de auditoria, se existir
Sistema de logs de acesso, se existir
Sistema documental
Sistema de storage privado
Sistema de exportações
Sistema de PDF
Sistema de filas/queues
Sistema de mail
Configuração de session
Configuração de rate limiting
Configuração de password hashing
Configuração de cookies
Configuração de CSRF
Configuração de backups, se existir

Modelo User
Modelo Role ou Permission, se existir
Modelo Candidate/Citizen, se existir
Modelo AdhesionRegistration
Modelo Application
Modelo Household
Modelo HouseholdMember
Modelo IncomeRecord
Modelo DocumentSubmission
Modelo DocumentVersion
Modelo Contract ou LeaseContract
Modelo Payment/RentInstallment/Arrear
Modelo MaintenanceRequest
Modelo OfficialNotification
Modelo CommunicationLog
Modelo ReportExport
Modelo AuditLog, se existir
Modelo AccessLog, se existir
Modelo Consent, se existir
Modelo DataSubjectRequest, se existir
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
AuditLog
AuditEvent
AccessLog
SensitiveDataAccessLog
SecurityAlert
PermissionReview
ConsentPurpose
UserConsent
ConsentVersion
RetentionPolicy
RetentionRule
RetentionExecution
DataSubjectRequest
DataSubjectRequestAction
DataExportPackage
AnonymizationRequest
AnonymizationJob
EncryptedFieldRegistry
SecurityChecklist
SecurityChecklistItem
BackupReview
PasswordPolicy
MfaDevice
MfaRecoveryCode
MfaChallenge
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não apagar logs existentes.

Não apagar documentos existentes.

Não alterar `.env`.

Não rodar comandos destrutivos.

Não introduzir dados pessoais reais.

---

## Execução realizada — Sprint 18

Implementado nesta execução:

- audit trail avançado em `audit_events`, com número único, categoria, severidade, ator, titular, recurso auditado, request context, valores mascarados e bloqueio de update/delete por model event;
- compatibilidade entre `audit_logs` existente e o novo `audit_events`, sem apagar logs anteriores;
- logs de acesso em `access_logs`, incluindo login, logout, falha de login, acesso backoffice, downloads documentais e downloads de exportações;
- logs de acesso sensível em `sensitive_data_access_logs`, reutilizados por documentos, reporting e exportações RGPD;
- MFA TOTP interno para perfis backoffice, com secret encriptado por cast Laravel, recovery codes apenas hashed e validação por sessão;
- revisão de permissões, finalidades RGPD, consentimentos, pedidos dos titulares, exportação privada, retenção, anonimização controlada, alertas, backups, checklist pré-produção e registry de campos sensíveis;
- área de backoffice `/backoffice/security` e centro RGPD do candidato em `/area-candidato/privacidade`;
- seeders idempotentes, factories e teste específico `tests/Feature/Sprint18RgpdSecurityAuditTest.php`.

Ficheiro referenciado pela master prompt mas ausente:

- `docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md`; existe a variante `docs/backlog/sprint-6-gestao-documental-avancada.md`, considerada como referência funcional.

Fora de âmbito mantido:

- não foi implementada integração externa de MFA, Autenticação.GOV, DPO workflow externo, SIEM, backup provider, OCR, assinatura digital, eliminação automática definitiva em massa, email/SMS real ou Sprint 19;
- retenção real fica conservadora nesta sprint: a execução regista o controlo e exige aprovação, mas não apaga registos automaticamente;
- encriptação física de campos existentes fica preparada por registry, sem alterar campos críticos de login/pesquisa nesta execução.

Comandos já executados nesta sprint até esta atualização documental:

- `php artisan --version` — Laravel Framework 13.12.0;
- `php -v` — PHP 8.5.6;
- `composer validate --no-check-publish` — composer.json válido;
- `php artisan route:list --path=security` — 52 rotas de segurança listadas;
- `php artisan route:list --path=area-candidato/privacidade` — 8 rotas de privacidade do candidato listadas;
- `php artisan test --filter=Sprint18RgpdSecurityAuditTest` — passou com 7 testes e 40 asserções.

Não introduzir passwords reais, tokens, APP_KEY, chaves SMTP, chaves SMS ou credenciais.

---

# 3. Dependências funcionais

Esta sprint depende obrigatoriamente de:

```text
Sistema de autenticação
Sistema de utilizadores
Sistema de permissões
Backoffice
Área pessoal do candidato
Storage privado ou camada documental
```

Depende preferencialmente de:

```text
Sprint 6 — Gestão Documental Avançada
Sprint 16 — Notificações, Comunicações e Modelos Documentais
Sprint 17 — Relatórios e exportações
Sistema de auditoria existente
Sistema de download seguro de ficheiros
Sistema de queues
Sistema de mail
```

## Dependência de autenticação

Se o sistema de autenticação não suportar MFA/2FA, implementar camada compatível com a stack existente.

Preferir, por ordem:

```text
MFA/2FA nativo já existente no projeto
Laravel Fortify, se já estiver instalado
Pacote de TOTP já existente no projeto
Implementação interna segura com TOTP e recovery codes
Fallback documentado se não for seguro implementar MFA real sem dependência
```

Não implementar MFA meramente visual.

Não afirmar que MFA está ativo se apenas foi criado scaffold.

## Dependência documental

Se o sistema documental existir, políticas de retenção e logs de acesso a documentos devem integrar com `DocumentSubmission`, `DocumentVersion` ou equivalente.

Se não existir, implementar camada genérica de storage review e documentar pendência.

## Dependência de exportações

Se Sprint 17 existir, pedidos RGPD e exportações de dados devem reutilizar mecanismos de exportação e storage privado.

Se não existir, criar exportação RGPD simples em JSON/CSV e storage privado.

---

# 4. Validação jurídica, RGPD e segurança

Esta sprint tem impacto direto em proteção de dados pessoais, segurança operacional e preparação para auditoria.

Regras obrigatórias:

```text
Não apresentar conformidade legal absoluta.
Não afirmar que a plataforma está legalmente certificada.
Não substituir parecer jurídico/DPO.
Não eliminar dados automaticamente sem política aprovada.
Não anonimizar dados sem confirmação administrativa.
Não encriptar campos usados em login/pesquisa sem avaliar impacto.
Não alterar APP_KEY.
Não rodar key rotation.
Não expor documentos por URL público.
Não guardar payloads sensíveis completos em logs.
Não guardar passwords ou tokens em logs.
Não guardar códigos MFA em claro.
Não guardar recovery codes em claro.
Não permitir downloads sem policy.
Não permitir exportação RGPD sem registo.
Não permitir acesso a pedidos RGPD de terceiros.
```

O objetivo é preparar a plataforma para operação real e auditoria externa, não declarar certificação final.

Qualquer regra jurídica deve ficar documentada como sujeita a validação pelo Município, DPO ou assessor jurídico.

---

# 5. Âmbito incluído

Implementar:

```text
Revisão de permissões
Matriz de permissões técnica
Autenticação multifator para backoffice
Obrigatoriedade de MFA para perfis sensíveis
Logs de acesso
Logs de acesso a documentos
Logs de acesso a dados sensíveis
Trilho de auditoria completo
Auditoria de ações críticas
Auditoria de downloads
Auditoria de exportações
Auditoria de alterações de permissões
Consentimentos por finalidade
Histórico de consentimentos
Finalidades de tratamento
Política de retenção documental
Regras de retenção por tipo de dado/documento
Execuções de retenção em modo simulação
Mecanismo de anonimização
Mecanismo de pseudonimização
Exportação de dados do titular
Gestão de pedidos RGPD
Encriptação seletiva de campos sensíveis
Registo de campos encriptados
Alertas de acesso indevido
Alertas de comportamento suspeito
Revisão de armazenamento privado de documentos
Revisão de backups
Revisão de políticas de passwords
Checklist de segurança pré-produção
Dashboard de segurança e conformidade
Policies
Middleware
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
Certificação legal final
Parecer jurídico final
DPIA completa automática
Nomeação de DPO
Integração SIEM externa
Integração SOC
Integração WAF
Pentest externo
Scanner de vulnerabilidades externo
Antivírus documental
DLP avançado
Key management service externo
HSM
Rotação automática de APP_KEY
Criptografia homomórfica
Anonimização estatística avançada
Apagamento físico garantido em storage externo
Integração eIDAS
Assinatura digital qualificada
Notificação automática à CNPD
Gestão processual jurídica completa
```

Podem ser criados pontos de integração e checklist para validação futura.

---

# 7. Conceito funcional

O fluxo de auditoria deve ser:

```text
Utilizador executa ação crítica
→ Middleware/service identifica contexto
→ Policy valida autorização
→ Ação é executada
→ AuditTrailService regista evento
→ AccessLogService regista acesso, se aplicável
→ SensitiveDataAccessService regista acesso sensível, se aplicável
→ SecurityAlertService avalia regras de alerta
→ Backoffice pode consultar trilho de auditoria
```

O fluxo de pedido RGPD deve ser:

```text
Titular cria pedido RGPD
→ Sistema gera número de pedido
→ Backoffice recebe pedido
→ Técnico classifica tipo de pedido
→ Sistema calcula prazo interno
→ Município analisa identidade e âmbito
→ Sistema executa ação ou prepara resposta
→ Exportação/retificação/limitação/anonimização fica registada
→ Titular consulta estado
→ Pedido é fechado com decisão e comprovativo
```

O fluxo de retenção documental deve ser:

```text
Município define política de retenção
→ Define regras por entidade/documento/finalidade
→ Sistema simula impacto
→ Técnico revê registos afetados
→ Execução real exige confirmação
→ Ação é auditada
→ Dados são arquivados, anonimizados ou marcados para eliminação conforme regra
```

O fluxo MFA deve ser:

```text
Utilizador de backoffice inicia sessão
→ Sistema identifica perfil sensível
→ MFA é exigido
→ Utilizador configura TOTP ou método permitido
→ Sistema valida segundo fator
→ Recovery codes são gerados e armazenados de forma segura
→ Acesso ao backoffice sensível exige MFA verificado
```

---

# 8. Ações críticas obrigatórias a auditar

Auditar obrigatoriamente:

```text
Login no backoffice
Logout
Falha de login
Ativação de MFA
Desativação de MFA
Regeneração de recovery codes
Alteração de password
Password reset
Alteração de perfil/role
Alteração de permissões
Criação de utilizador backoffice
Desativação de utilizador
Acesso a candidatura
Acesso a agregado familiar
Acesso a rendimentos
Acesso a documento
Download de documento
Upload de documento
Validação de documento
Rejeição de documento
Submissão de candidatura
Alteração de estado de candidatura
Decisão administrativa
Publicação de lista
Decisão de reclamação
Atribuição de habitação
Emissão de contrato
Assinatura/ativação de contrato
Registo de pagamento
Reversão de pagamento
Emissão de aviso de incumprimento
Exportação de relatório
Download de exportação
Geração de documento oficial
Envio de comunicação oficial
Leitura/tomada de conhecimento de comunicação
Criação de pedido RGPD
Análise de pedido RGPD
Exportação de dados do titular
Anonimização/pseudonimização
Execução de política de retenção
Alteração de política de retenção
Alteração de finalidade de consentimento
Registo/revogação de consentimento
Acesso a logs de auditoria
```

---

# 9. Estados e tipos recomendados

## AuditEventSeverity

```text
info
notice
warning
critical
security
```

## AuditEventCategory

```text
authentication
authorization
candidate_data
application
documents
workflow
scoring
allocation
contracts
finance
maintenance
communications
reports
rgpd
security
system
```

## AccessLogType

```text
login
logout
failed_login
page_view
record_view
document_view
document_download
export_download
api_access
admin_access
```

## ConsentStatus

```text
draft
active
withdrawn
expired
revoked
superseded
```

## ConsentLegalBasis

```text
consent
contract
legal_obligation
public_interest
legitimate_interest
vital_interest
```

## DataSubjectRequestType

```text
access
rectification
erasure
restriction
portability
objection
withdraw_consent
information
other
```

## DataSubjectRequestStatus

```text
draft
submitted
received
identity_verification
under_review
requires_information
in_progress
completed
rejected
cancelled
closed
overdue
```

## RetentionAction

```text
keep
archive
restrict
anonymize
pseudonymize
delete_candidate
delete_permanently
review_manually
```

## RetentionExecutionStatus

```text
draft
simulation
pending_approval
approved
running
completed
failed
cancelled
reverted
```

## AnonymizationStatus

```text
draft
pending_approval
approved
running
completed
failed
cancelled
```

## SecurityAlertStatus

```text
open
under_review
confirmed
false_positive
resolved
dismissed
```

## SecurityAlertSeverity

```text
low
medium
high
critical
```

## SecurityChecklistStatus

```text
draft
in_progress
passed
failed
partially_passed
approved
archived
```

---

# 10. Modelo de dados

## 10.1 AuditEvent

Criar ou adaptar entidade:

```text
AuditEvent
```

Tabela recomendada:

```text
audit_events
```

Objetivo:

```text
Guardar trilho de auditoria completo e imutável das ações críticas.
```

Campos mínimos:

```text
id
event_number

user_id nullable
impersonator_user_id nullable

event_code
event_category
severity

auditable_type nullable
auditable_id nullable

subject_user_id nullable
related_type nullable
related_id nullable

ip_address
user_agent
request_method
request_path
route_name

description
old_values
new_values
metadata

occurred_at

created_at
```

Regras:

```text
AuditEvent deve ser append-only.
Não permitir edição via mass assignment.
Não permitir eliminação por interface normal.
Não guardar passwords, tokens ou payloads completos.
Mascarar campos sensíveis.
```

---

## 10.2 AccessLog

Criar entidade:

```text
AccessLog
```

Tabela:

```text
access_logs
```

Objetivo:

```text
Registar acessos relevantes ao sistema.
```

Campos mínimos:

```text
id
user_id nullable
access_type
resource_type nullable
resource_id nullable
route_name nullable
request_path
ip_address
user_agent
session_id_hash
status_code
accessed_at
metadata
created_at
```

Regras:

```text
Guardar hash da sessão, não o ID em claro.
Não guardar query strings com dados sensíveis.
```

---

## 10.3 SensitiveDataAccessLog

Criar entidade:

```text
SensitiveDataAccessLog
```

Tabela:

```text
sensitive_data_access_logs
```

Objetivo:

```text
Registar acesso a dados/documentos sensíveis.
```

Campos mínimos:

```text
id
user_id
subject_user_id nullable
resource_type
resource_id
sensitivity_level
access_reason nullable
action
ip_address
user_agent
accessed_at
created_at
```

Ações recomendadas:

```text
view
download
export
print
share
update
delete_candidate
anonymize
```

Regras:

```text
Obrigatório para documentos, rendimentos, agregado familiar, relatórios sensíveis e exportações.
```

---

## 10.4 PermissionReview

Criar entidade:

```text
PermissionReview
```

Tabela:

```text
permission_reviews
```

Objetivo:

```text
Registar revisões periódicas de permissões e perfis.
```

Campos mínimos:

```text
id
review_number
status
scope
started_by
started_at
completed_by
completed_at
summary
findings
recommendations
created_at
updated_at
deleted_at
```

Estados recomendados:

```text
draft
in_progress
completed
approved
archived
```

---

## 10.5 PermissionReviewItem

Criar entidade:

```text
PermissionReviewItem
```

Tabela:

```text
permission_review_items
```

Campos mínimos:

```text
id
permission_review_id
user_id nullable
role_name nullable
permission_name nullable
module
risk_level
finding
recommendation
decision
decided_by
decided_at
created_at
updated_at
```

---

## 10.6 MfaDevice

Criar entidade se não existir MFA:

```text
MfaDevice
```

Tabela:

```text
mfa_devices
```

Campos mínimos:

```text
id
user_id
type
name
secret_encrypted
confirmed_at
last_used_at
disabled_at
created_at
updated_at
deleted_at
```

Tipos recomendados:

```text
totp
email_otp
recovery_code
```

Regras:

```text
Segredo TOTP deve ser encriptado.
Não guardar OTP em claro.
MFA obrigatório para backoffice sensível.
```

---

## 10.7 MfaRecoveryCode

Criar entidade se não existir recovery codes:

```text
MfaRecoveryCode
```

Tabela:

```text
mfa_recovery_codes
```

Campos mínimos:

```text
id
user_id
code_hash
used_at
created_at
updated_at
```

Regras:

```text
Guardar apenas hash.
Mostrar recovery codes apenas uma vez.
```

---

## 10.8 ConsentPurpose

Criar entidade:

```text
ConsentPurpose
```

Tabela:

```text
consent_purposes
```

Objetivo:

```text
Definir finalidades de tratamento e base legal.
```

Campos mínimos:

```text
id
code
name
description
legal_basis
is_required
is_active
requires_explicit_consent
retention_period_months
created_by
updated_by
created_at
updated_at
deleted_at
```

Finalidades iniciais recomendadas:

```text
account_management
application_processing
eligibility_assessment
document_validation
municipal_housing_allocation
contract_management
rent_management
maintenance_management
official_communications
reporting_and_audit
optional_notifications
```

---

## 10.9 UserConsent

Criar entidade:

```text
UserConsent
```

Tabela:

```text
user_consents
```

Objetivo:

```text
Guardar consentimentos e respetivo histórico.
```

Campos mínimos:

```text
id
user_id
consent_purpose_id
status
consented_at
withdrawn_at
expires_at
source
ip_address
user_agent
text_snapshot
version
created_at
updated_at
```

Regras:

```text
Consentimentos opcionais podem ser retirados.
Consentimentos/base legal obrigatória devem ser exibidos como informação de tratamento, não como opção livre de bloqueio.
Guardar snapshot do texto apresentado.
```

---

## 10.10 RetentionPolicy

Criar entidade:

```text
RetentionPolicy
```

Tabela:

```text
retention_policies
```

Objetivo:

```text
Definir políticas de retenção por tipo de dado/documento.
```

Campos mínimos:

```text
id
code
name
description
status
entity_type
document_type_id nullable
retention_period_months
retention_action
legal_basis
requires_manual_approval
created_by
approved_by
approved_at
created_at
updated_at
deleted_at
```

Estados recomendados:

```text
draft
active
inactive
archived
```

---

## 10.11 RetentionExecution

Criar entidade:

```text
RetentionExecution
```

Tabela:

```text
retention_executions
```

Objetivo:

```text
Registar simulações e execuções de retenção.
```

Campos mínimos:

```text
id
execution_number
retention_policy_id
status
mode
matched_records_count
affected_records_count
started_by
approved_by
started_at
completed_at
failed_at
failure_reason
summary
created_at
updated_at
deleted_at
```

Modos recomendados:

```text
simulation
real
```

Regras:

```text
Execução real deve exigir aprovação quando a política assim o define.
Simulação não deve alterar dados.
```

---

## 10.12 DataSubjectRequest

Criar entidade:

```text
DataSubjectRequest
```

Tabela:

```text
data_subject_requests
```

Objetivo:

```text
Gerir pedidos RGPD dos titulares.
```

Campos mínimos:

```text
id
request_number
user_id nullable
requester_name
requester_email
requester_phone
request_type
status
description
identity_verified_at
received_at
due_at
completed_at
rejected_at
rejection_reason
assigned_to
created_by
closed_by
internal_notes
created_at
updated_at
deleted_at
```

Regras:

```text
request_number obrigatório e único.
Pedido deve ter histórico de ações.
Exportação deve ficar associada ao pedido.
Não permitir acesso por terceiros.
```

---

## 10.13 DataSubjectRequestAction

Criar entidade:

```text
DataSubjectRequestAction
```

Tabela:

```text
data_subject_request_actions
```

Campos mínimos:

```text
id
data_subject_request_id
action_type
status
description
performed_by
performed_at
result_summary
metadata
created_at
updated_at
```

Tipos recomendados:

```text
identity_verification
data_search
data_export
rectification
restriction
erasure_review
anonymization
response_sent
closure
```

---

## 10.14 DataExportPackage

Criar entidade:

```text
DataExportPackage
```

Tabela:

```text
data_export_packages
```

Objetivo:

```text
Guardar exportações de dados do titular.
```

Campos mínimos:

```text
id
data_subject_request_id
user_id
package_number
status
format
storage_disk
storage_path
filename
mime_type
file_size
checksum
generated_by
generated_at
downloaded_at
expires_at
created_at
updated_at
deleted_at
```

Formatos recomendados:

```text
json
csv
zip
pdf
```

Regras:

```text
Guardar em storage privado.
Download exige autorização.
Não colocar dados pessoais no filename.
```

---

## 10.15 AnonymizationRequest

Criar entidade:

```text
AnonymizationRequest
```

Tabela:

```text
anonymization_requests
```

Objetivo:

```text
Controlar anonimização/pseudonimização.
```

Campos mínimos:

```text
id
request_number
data_subject_request_id nullable
user_id nullable
status
anonymization_type
reason
scope
approved_by
approved_at
executed_by
executed_at
summary
failure_reason
created_at
updated_at
deleted_at
```

Tipos:

```text
anonymization
pseudonymization
partial_masking
```

---

## 10.16 EncryptedFieldRegistry

Criar entidade:

```text
EncryptedFieldRegistry
```

Tabela:

```text
encrypted_field_registries
```

Objetivo:

```text
Documentar campos sensíveis encriptados ou candidatos a encriptação.
```

Campos mínimos:

```text
id
model_class
table_name
field_name
encryption_status
search_strategy
notes
migration_required
implemented_at
created_by
updated_by
created_at
updated_at
deleted_at
```

Estados:

```text
not_encrypted
planned
encrypted
hash_indexed
not_applicable
blocked_by_search_requirement
```

Regras:

```text
Não encriptar campos que quebram login/pesquisa sem estratégia.
```

---

## 10.17 SecurityAlertRule

Criar entidade:

```text
SecurityAlertRule
```

Tabela:

```text
security_alert_rules
```

Campos mínimos:

```text
id
code
name
description
event_code
severity
threshold
window_minutes
is_active
created_by
updated_by
created_at
updated_at
deleted_at
```

Regras iniciais:

```text
multiple_failed_logins
sensitive_document_bulk_download
sensitive_report_bulk_export
access_outside_business_hours
access_to_multiple_candidate_records
mfa_disabled_for_admin
permission_changed_for_admin
```

---

## 10.18 SecurityAlert

Criar entidade:

```text
SecurityAlert
```

Tabela:

```text
security_alerts
```

Campos mínimos:

```text
id
alert_number
security_alert_rule_id nullable
user_id nullable
status
severity
title
description
detected_at
reviewed_by
reviewed_at
resolved_by
resolved_at
resolution_notes
metadata
created_at
updated_at
deleted_at
```

---

## 10.19 BackupReview

Criar entidade:

```text
BackupReview
```

Tabela:

```text
backup_reviews
```

Objetivo:

```text
Registar revisão operacional das políticas de backup.
```

Campos mínimos:

```text
id
review_number
status
environment
backup_scope
frequency
retention_period
last_backup_at
last_restore_test_at
findings
recommendations
reviewed_by
reviewed_at
created_at
updated_at
deleted_at
```

Estados:

```text
draft
reviewed
requires_action
approved
archived
```

---

## 10.20 SecurityChecklist

Criar entidade:

```text
SecurityChecklist
```

Tabela:

```text
security_checklists
```

Objetivo:

```text
Checklist de segurança pré-produção.
```

Campos mínimos:

```text
id
checklist_number
name
status
environment
started_by
approved_by
started_at
approved_at
summary
created_at
updated_at
deleted_at
```

---

## 10.21 SecurityChecklistItem

Criar entidade:

```text
SecurityChecklistItem
```

Tabela:

```text
security_checklist_items
```

Campos mínimos:

```text
id
security_checklist_id
category
title
description
status
evidence
recommendation
checked_by
checked_at
created_at
updated_at
deleted_at
```

Categorias recomendadas:

```text
authentication
authorization
storage
documents
backups
logging
audit
rgpd
passwords
headers
sessions
exports
dependencies
production_config
```

---

# 11. Enums recomendados

Criar, se a versão do PHP permitir:

```text
App\Enums\AuditEventSeverity
App\Enums\AuditEventCategory
App\Enums\AccessLogType
App\Enums\ConsentStatus
App\Enums\ConsentLegalBasis
App\Enums\DataSubjectRequestType
App\Enums\DataSubjectRequestStatus
App\Enums\DataSubjectRequestActionType
App\Enums\RetentionAction
App\Enums\RetentionExecutionStatus
App\Enums\AnonymizationStatus
App\Enums\SecurityAlertStatus
App\Enums\SecurityAlertSeverity
App\Enums\SecurityChecklistStatus
App\Enums\BackupReviewStatus
App\Enums\EncryptedFieldStatus
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 12. Relações obrigatórias

## User

Adicionar:

```text
hasMany AuditEvent
hasMany AccessLog
hasMany SensitiveDataAccessLog
hasMany UserConsent
hasMany DataSubjectRequest
hasMany DataExportPackage
hasMany MfaDevice
hasMany MfaRecoveryCode
hasMany SecurityAlert
```

## AuditEvent

```text
belongsTo User nullable
belongsTo User as subjectUser nullable
morphTo auditable nullable
morphTo related nullable
```

## AccessLog

```text
belongsTo User nullable
morphTo resource nullable
```

## SensitiveDataAccessLog

```text
belongsTo User
belongsTo User as subjectUser nullable
morphTo resource
```

## ConsentPurpose

```text
hasMany UserConsent
belongsTo User as createdBy nullable
belongsTo User as updatedBy nullable
```

## UserConsent

```text
belongsTo User
belongsTo ConsentPurpose
```

## RetentionPolicy

```text
hasMany RetentionExecution
belongsTo User as createdBy nullable
belongsTo User as approvedBy nullable
```

## DataSubjectRequest

```text
belongsTo User nullable
belongsTo User as assignedTo nullable
belongsTo User as createdBy nullable
belongsTo User as closedBy nullable
hasMany DataSubjectRequestAction
hasMany DataExportPackage
hasMany AnonymizationRequest
```

## DataExportPackage

```text
belongsTo DataSubjectRequest
belongsTo User
belongsTo User as generatedBy
```

## AnonymizationRequest

```text
belongsTo DataSubjectRequest nullable
belongsTo User nullable
belongsTo User as approvedBy nullable
belongsTo User as executedBy nullable
```

## SecurityAlert

```text
belongsTo SecurityAlertRule nullable
belongsTo User nullable
belongsTo User as reviewedBy nullable
belongsTo User as resolvedBy nullable
```

## SecurityChecklist

```text
hasMany SecurityChecklistItem
belongsTo User as startedBy nullable
belongsTo User as approvedBy nullable
```

---

# 13. Services obrigatórios

Criar:

```text
App\Services\Security\PermissionReviewService
App\Services\Security\MfaEnforcementService
App\Services\Security\MfaDeviceService
App\Services\Security\PasswordPolicyService
App\Services\Security\AccessLogService
App\Services\Security\SensitiveDataAccessService
App\Services\Security\SecurityAlertService
App\Services\Security\SecurityAlertRuleEvaluator
App\Services\Security\DocumentStorageSecurityReviewService
App\Services\Security\BackupReviewService
App\Services\Security\PreProductionSecurityChecklistService
App\Services\Security\SensitiveFieldEncryptionReviewService

App\Services\Audit\AuditTrailService
App\Services\Audit\AuditEventFormatter
App\Services\Audit\AuditRetentionService

App\Services\Rgpd\ConsentPurposeService
App\Services\Rgpd\UserConsentService
App\Services\Rgpd\RetentionPolicyService
App\Services\Rgpd\RetentionExecutionService
App\Services\Rgpd\DataSubjectRequestService
App\Services\Rgpd\DataSubjectRequestWorkflowService
App\Services\Rgpd\DataExportService
App\Services\Rgpd\AnonymizationService
App\Services\Rgpd\PseudonymizationService
App\Services\Rgpd\DataInventoryService
```

---

## 13.1 PermissionReviewService

Responsável por:

```text
Inventariar roles e permissões existentes
Comparar permissões com matriz de acesso
Identificar perfis com permissões excessivas
Identificar utilizadores administrativos sem MFA
Identificar utilizadores inativos com acesso
Gerar revisão de permissões
Criar itens de recomendação
Registar decisões da revisão
```

---

## 13.2 MfaEnforcementService

Responsável por:

```text
Determinar se MFA é obrigatório para o utilizador
Obrigar MFA para backoffice
Obrigar MFA para admin
Obrigar MFA para perfis financeiros
Obrigar MFA para perfis com acesso a dados sensíveis
Validar sessão com MFA recente
Bloquear acesso sensível sem MFA
Registar eventos de MFA em auditoria
```

---

## 13.3 MfaDeviceService

Responsável por:

```text
Configurar TOTP
Confirmar TOTP
Desativar MFA com permissão
Gerar recovery codes
Validar recovery code
Atualizar last_used_at
Guardar segredos de forma segura
```

Não guardar códigos em claro.

---

## 13.4 PasswordPolicyService

Responsável por:

```text
Verificar política de passwords
Validar comprimento mínimo
Validar complexidade quando configurado
Validar reutilização, se houver histórico
Validar expiração, se configurado
Criar relatório de recomendações
Integrar com validators existentes
```

Não bloquear utilizadores existentes sem estratégia de migração.

---

## 13.5 AccessLogService

Responsável por:

```text
Registar login
Registar logout
Registar falha de login
Registar acesso ao backoffice
Registar acesso a documentos
Registar downloads
Registar exportações
Mascarar dados sensíveis
```

---

## 13.6 SensitiveDataAccessService

Responsável por:

```text
Registar acesso a dados sensíveis
Classificar sensibilidade
Registar motivo quando fornecido
Registar subject_user_id
Registar recurso
Integrar com SecurityAlertService
```

---

## 13.7 AuditTrailService

Responsável por:

```text
Criar evento de auditoria
Gerar número único
Guardar before/after com máscara
Associar user, recurso, subject e contexto
Impedir alteração posterior
Permitir consulta filtrada
Integrar com models críticos
```

---

## 13.8 ConsentPurposeService

Responsável por:

```text
Criar finalidade
Atualizar finalidade
Arquivar finalidade
Gerir base legal
Gerir período de retenção associado
```

---

## 13.9 UserConsentService

Responsável por:

```text
Registar consentimento
Registar revogação
Guardar snapshot do texto
Determinar consentimento ativo
Listar consentimentos do titular
Impedir revogação de base legal obrigatória como se fosse consentimento livre
```

---

## 13.10 RetentionPolicyService

Responsável por:

```text
Criar política de retenção
Validar entidade alvo
Validar ação de retenção
Ativar política
Arquivar política
Listar políticas por entidade/documento
```

---

## 13.11 RetentionExecutionService

Responsável por:

```text
Simular execução de política
Contar registos afetados
Gerar resumo
Exigir aprovação para execução real
Executar ação configurada
Registar auditoria
Tratar erros
```

Execução real deve ser conservadora.

Nunca apagar permanentemente sem confirmação explícita.

---

## 13.12 DataSubjectRequestService

Responsável por:

```text
Criar pedido RGPD
Gerar número de pedido
Classificar tipo de pedido
Atribuir responsável
Calcular prazo interno
Alterar estado
Registar decisão
Fechar pedido
Notificar titular, se Sprint 16 existir
```

---

## 13.13 DataExportService

Responsável por:

```text
Recolher dados do titular
Incluir dados de perfil
Incluir registo de adesão
Incluir candidaturas
Incluir agregado familiar
Incluir documentos e metadados autorizados
Incluir contratos, pagamentos e manutenção quando aplicável
Gerar JSON estruturado
Gerar CSV quando aplicável
Gerar pacote ZIP se infraestrutura existir
Guardar em storage privado
Registar checksum
Registar download
```

---

## 13.14 AnonymizationService

Responsável por:

```text
Preparar anonimização
Simular impacto
Substituir identificadores diretos
Remover ou mascarar contactos
Preservar integridade estatística quando possível
Preservar obrigações legais quando aplicável
Registar mapping apenas se estritamente necessário e protegido
Registar auditoria
```

Anonimização real deve exigir aprovação.

---

## 13.15 SensitiveFieldEncryptionReviewService

Responsável por:

```text
Identificar campos sensíveis
Classificar campos por risco
Determinar se podem ser encriptados
Detetar campos usados em login/pesquisa/filtros
Criar registry
Aplicar casts encrypted apenas quando seguro
Documentar campos bloqueados por requisito técnico
```

Campos candidatos:

```text
nif
tax_number
citizen_card_number
social_security_number
iban
phone
address
birth_date
income_details
health_or_disability_notes
document_metadata_sensitive_fields
```

Não encriptar email de login sem estratégia própria.

---

## 13.16 SecurityAlertService

Responsável por:

```text
Criar alerta de segurança
Avaliar regras
Marcar alerta como em análise
Marcar como confirmado
Marcar como falso positivo
Resolver alerta
Notificar admin interno, se Sprint 16 existir
```

---

## 13.17 DocumentStorageSecurityReviewService

Responsável por:

```text
Verificar discos de storage usados
Identificar ficheiros em storage público
Identificar documentos sensíveis fora de storage privado
Verificar controllers de download
Verificar exposição de storage_path
Gerar recomendações
```

---

## 13.18 BackupReviewService

Responsável por:

```text
Registar política de backup declarada
Registar última cópia conhecida
Registar último teste de restore
Registar lacunas
Gerar recomendações
```

Não executar backups reais sem configuração explícita.

---

## 13.19 PreProductionSecurityChecklistService

Responsável por:

```text
Criar checklist pré-produção
Gerar itens padrão
Validar itens
Marcar passed/failed
Registar evidência
Gerar resumo final
```

---

# 14. Middleware obrigatório

Criar ou adaptar:

```text
EnsureBackofficeMfaVerified
LogBackofficeAccess
LogSensitiveResourceAccess
RequireSensitivePermission
BlockInactiveBackofficeUsers
EnforcePasswordPolicyOnChange
```

## EnsureBackofficeMfaVerified

Aplicar a:

```text
/backoffice/*
/admin/*
Rotas sensíveis de documentos
Rotas sensíveis de relatórios
Rotas de exportação
Rotas RGPD
Rotas de permissões
Rotas de auditoria
```

Não bloquear área pública.

Não bloquear área de candidato, salvo decisão explícita.

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
Backoffice\SecurityDashboardController
Backoffice\PermissionReviewController
Backoffice\MfaManagementController
Backoffice\AuditEventController
Backoffice\AccessLogController
Backoffice\SensitiveDataAccessLogController
Backoffice\ConsentPurposeController
Backoffice\UserConsentController
Backoffice\RetentionPolicyController
Backoffice\RetentionExecutionController
Backoffice\DataSubjectRequestController
Backoffice\DataExportPackageController
Backoffice\AnonymizationRequestController
Backoffice\SecurityAlertRuleController
Backoffice\SecurityAlertController
Backoffice\EncryptedFieldRegistryController
Backoffice\DocumentStorageSecurityReviewController
Backoffice\BackupReviewController
Backoffice\SecurityChecklistController
```

## Área do candidato / titular

Namespace recomendado:

```text
App\Http\Controllers\Candidate
```

Controllers:

```text
Candidate\PrivacyCenterController
Candidate\UserConsentController
Candidate\DataSubjectRequestController
Candidate\DataExportPackageController
```

O candidato pode:

```text
Consultar finalidades
Consultar consentimentos
Revogar consentimentos opcionais
Criar pedido RGPD
Consultar pedidos próprios
Descarregar exportação própria autorizada
```

O candidato não pode:

```text
Consultar logs internos
Consultar auditoria interna
Consultar pedidos RGPD de terceiros
Executar anonimização
Aprovar eliminação
Consultar backups
Consultar alertas de segurança
```

---

# 16. Form Requests

Criar:

```text
StorePermissionReviewRequest
CompletePermissionReviewRequest
StorePermissionReviewItemRequest
UpdatePermissionReviewItemRequest

EnableMfaRequest
ConfirmMfaRequest
DisableMfaRequest
VerifyMfaChallengeRequest
RegenerateRecoveryCodesRequest

StoreConsentPurposeRequest
UpdateConsentPurposeRequest
StoreUserConsentRequest
WithdrawUserConsentRequest

StoreRetentionPolicyRequest
UpdateRetentionPolicyRequest
RunRetentionSimulationRequest
ApproveRetentionExecutionRequest
RunRetentionExecutionRequest

StoreDataSubjectRequestRequest
AssignDataSubjectRequestRequest
ReviewDataSubjectRequestRequest
CompleteDataSubjectRequestRequest
RejectDataSubjectRequestRequest

GenerateDataExportPackageRequest
DownloadDataExportPackageRequest

StoreAnonymizationRequestRequest
ApproveAnonymizationRequestRequest
RunAnonymizationRequestRequest

StoreSecurityAlertRuleRequest
UpdateSecurityAlertRuleRequest
ReviewSecurityAlertRequest
ResolveSecurityAlertRequest

StoreBackupReviewRequest
UpdateBackupReviewRequest

StoreSecurityChecklistRequest
UpdateSecurityChecklistItemRequest
ApproveSecurityChecklistRequest
```

## StoreConsentPurposeRequest

```text
code required|string|max:150
name required|string|max:255
description required|string|max:5000
legal_basis required|string|max:100
is_required boolean
is_active boolean
requires_explicit_consent boolean
retention_period_months nullable|integer|min:0|max:1200
```

## StoreDataSubjectRequestRequest

```text
request_type required|string|max:100
description required|string|min:10|max:10000
requester_name nullable|string|max:255
requester_email nullable|email|max:255
requester_phone nullable|string|max:50
```

## StoreRetentionPolicyRequest

```text
code required|string|max:150
name required|string|max:255
description nullable|string|max:5000
entity_type required|string|max:255
document_type_id nullable|integer
retention_period_months required|integer|min:0|max:1200
retention_action required|string|max:100
legal_basis nullable|string|max:3000
requires_manual_approval boolean
```

## StoreAnonymizationRequestRequest

```text
user_id nullable|exists:users,id
data_subject_request_id nullable|exists:data_subject_requests,id
anonymization_type required|string|max:100
reason required|string|min:10|max:5000
scope required|array|min:1
```

## StoreSecurityAlertRuleRequest

```text
code required|string|max:150
name required|string|max:255
description nullable|string|max:5000
event_code required|string|max:150
severity required|string|max:100
threshold nullable|integer|min:1
window_minutes nullable|integer|min:1|max:10080
is_active boolean
```

---

# 17. Policies

Criar:

```text
AuditEventPolicy
AccessLogPolicy
SensitiveDataAccessLogPolicy
PermissionReviewPolicy
MfaDevicePolicy
ConsentPurposePolicy
UserConsentPolicy
RetentionPolicyPolicy
RetentionExecutionPolicy
DataSubjectRequestPolicy
DataExportPackagePolicy
AnonymizationRequestPolicy
EncryptedFieldRegistryPolicy
SecurityAlertRulePolicy
SecurityAlertPolicy
BackupReviewPolicy
SecurityChecklistPolicy
```

## Regras para candidato/titular

```text
Só vê os seus consentimentos.
Só revoga consentimentos opcionais próprios.
Só cria pedidos RGPD próprios.
Só vê pedidos RGPD próprios.
Só descarrega exportações próprias autorizadas.
Não vê auditoria interna.
Não vê logs de acesso.
Não vê alertas de segurança.
Não vê políticas internas de retenção.
Não vê backups.
```

## Regras para técnico municipal

```text
Pode consultar dados conforme permissões operacionais.
Pode criar pedido RGPD em nome do titular se autorizado.
Pode analisar pedido RGPD se autorizado.
Não aprova anonimização sem permissão superior.
Não altera políticas de retenção sem permissão.
Não consulta logs de segurança sem permissão.
```

## Regras para admin

```text
Pode gerir permissões.
Pode gerir MFA.
Pode consultar auditoria.
Pode gerir finalidades.
Pode gerir políticas de retenção.
Pode aprovar execuções sensíveis.
Pode gerir alertas de segurança.
Pode consultar checklist.
```

## Regras para auditor

```text
Pode consultar auditoria.
Pode consultar logs de acesso.
Pode consultar histórico de pedidos RGPD.
Pode consultar exportações e retenções.
Não altera permissões.
Não altera políticas.
Não executa anonimização.
Não apaga logs.
```

## Regras para DPO/perfil de privacidade, se existir

```text
Pode gerir pedidos RGPD.
Pode consultar finalidades e consentimentos.
Pode consultar políticas de retenção.
Pode aprovar respostas RGPD se autorizado.
Pode consultar exportações de dados do titular.
```

---

# 18. Rotas

## Backoffice

Criar, preferencialmente:

```text
GET /backoffice/security
GET /backoffice/security/dashboard

GET /backoffice/security/permissions/reviews
GET /backoffice/security/permissions/reviews/create
POST /backoffice/security/permissions/reviews
GET /backoffice/security/permissions/reviews/{permissionReview}
POST /backoffice/security/permissions/reviews/{permissionReview}/complete

GET /backoffice/security/mfa
POST /backoffice/security/mfa/enable
POST /backoffice/security/mfa/confirm
POST /backoffice/security/mfa/disable
POST /backoffice/security/mfa/recovery-codes/regenerate

GET /backoffice/security/audit-events
GET /backoffice/security/audit-events/{auditEvent}

GET /backoffice/security/access-logs
GET /backoffice/security/sensitive-access-logs

GET /backoffice/rgpd/consent-purposes
GET /backoffice/rgpd/consent-purposes/create
POST /backoffice/rgpd/consent-purposes
GET /backoffice/rgpd/consent-purposes/{consentPurpose}/edit
PUT/PATCH /backoffice/rgpd/consent-purposes/{consentPurpose}

GET /backoffice/rgpd/user-consents
GET /backoffice/rgpd/user-consents/{userConsent}

GET /backoffice/rgpd/data-subject-requests
GET /backoffice/rgpd/data-subject-requests/create
POST /backoffice/rgpd/data-subject-requests
GET /backoffice/rgpd/data-subject-requests/{dataSubjectRequest}
POST /backoffice/rgpd/data-subject-requests/{dataSubjectRequest}/assign
POST /backoffice/rgpd/data-subject-requests/{dataSubjectRequest}/review
POST /backoffice/rgpd/data-subject-requests/{dataSubjectRequest}/complete
POST /backoffice/rgpd/data-subject-requests/{dataSubjectRequest}/reject

POST /backoffice/rgpd/data-subject-requests/{dataSubjectRequest}/exports/generate
GET /backoffice/rgpd/data-exports/{dataExportPackage}
GET /backoffice/rgpd/data-exports/{dataExportPackage}/download

GET /backoffice/rgpd/retention-policies
GET /backoffice/rgpd/retention-policies/create
POST /backoffice/rgpd/retention-policies
GET /backoffice/rgpd/retention-policies/{retentionPolicy}
GET /backoffice/rgpd/retention-policies/{retentionPolicy}/edit
PUT/PATCH /backoffice/rgpd/retention-policies/{retentionPolicy}
POST /backoffice/rgpd/retention-policies/{retentionPolicy}/simulate
POST /backoffice/rgpd/retention-executions/{retentionExecution}/approve
POST /backoffice/rgpd/retention-executions/{retentionExecution}/run

GET /backoffice/rgpd/anonymization-requests
POST /backoffice/rgpd/anonymization-requests
GET /backoffice/rgpd/anonymization-requests/{anonymizationRequest}
POST /backoffice/rgpd/anonymization-requests/{anonymizationRequest}/approve
POST /backoffice/rgpd/anonymization-requests/{anonymizationRequest}/run

GET /backoffice/security/encrypted-fields
GET /backoffice/security/storage-review
POST /backoffice/security/storage-review/run

GET /backoffice/security/alerts
GET /backoffice/security/alerts/{securityAlert}
POST /backoffice/security/alerts/{securityAlert}/review
POST /backoffice/security/alerts/{securityAlert}/resolve
POST /backoffice/security/alerts/{securityAlert}/dismiss

GET /backoffice/security/alert-rules
POST /backoffice/security/alert-rules
PUT/PATCH /backoffice/security/alert-rules/{securityAlertRule}

GET /backoffice/security/backups
POST /backoffice/security/backups/reviews
GET /backoffice/security/backups/reviews/{backupReview}

GET /backoffice/security/checklists
GET /backoffice/security/checklists/create
POST /backoffice/security/checklists
GET /backoffice/security/checklists/{securityChecklist}
PUT/PATCH /backoffice/security/checklists/{securityChecklist}/items/{securityChecklistItem}
POST /backoffice/security/checklists/{securityChecklist}/approve
```

## Área do candidato / titular

Criar, preferencialmente:

```text
GET /area-candidato/privacidade
GET /area-candidato/privacidade/consentimentos
POST /area-candidato/privacidade/consentimentos/{userConsent}/revogar

GET /area-candidato/privacidade/pedidos-rgpd
GET /area-candidato/privacidade/pedidos-rgpd/criar
POST /area-candidato/privacidade/pedidos-rgpd
GET /area-candidato/privacidade/pedidos-rgpd/{dataSubjectRequest}

GET /area-candidato/privacidade/exportacoes/{dataExportPackage}
GET /area-candidato/privacidade/exportacoes/{dataExportPackage}/download
```

Todas as rotas devem estar protegidas por autenticação, middleware e policies.

---

# 19. Views / páginas

Se o projeto usa Blade, criar:

## Backoffice

```text
resources/views/backoffice/security/dashboard.blade.php

resources/views/backoffice/security/permissions/reviews/index.blade.php
resources/views/backoffice/security/permissions/reviews/create.blade.php
resources/views/backoffice/security/permissions/reviews/show.blade.php

resources/views/backoffice/security/mfa/index.blade.php
resources/views/backoffice/security/mfa/setup.blade.php
resources/views/backoffice/security/mfa/challenge.blade.php

resources/views/backoffice/security/audit-events/index.blade.php
resources/views/backoffice/security/audit-events/show.blade.php
resources/views/backoffice/security/access-logs/index.blade.php
resources/views/backoffice/security/sensitive-access-logs/index.blade.php

resources/views/backoffice/rgpd/consent-purposes/index.blade.php
resources/views/backoffice/rgpd/consent-purposes/create.blade.php
resources/views/backoffice/rgpd/consent-purposes/edit.blade.php
resources/views/backoffice/rgpd/user-consents/index.blade.php
resources/views/backoffice/rgpd/user-consents/show.blade.php

resources/views/backoffice/rgpd/data-subject-requests/index.blade.php
resources/views/backoffice/rgpd/data-subject-requests/create.blade.php
resources/views/backoffice/rgpd/data-subject-requests/show.blade.php

resources/views/backoffice/rgpd/retention-policies/index.blade.php
resources/views/backoffice/rgpd/retention-policies/create.blade.php
resources/views/backoffice/rgpd/retention-policies/edit.blade.php
resources/views/backoffice/rgpd/retention-policies/show.blade.php

resources/views/backoffice/rgpd/anonymization-requests/index.blade.php
resources/views/backoffice/rgpd/anonymization-requests/show.blade.php

resources/views/backoffice/security/encrypted-fields/index.blade.php
resources/views/backoffice/security/storage-review/index.blade.php
resources/views/backoffice/security/alerts/index.blade.php
resources/views/backoffice/security/alerts/show.blade.php
resources/views/backoffice/security/alert-rules/index.blade.php
resources/views/backoffice/security/backups/index.blade.php
resources/views/backoffice/security/checklists/index.blade.php
resources/views/backoffice/security/checklists/create.blade.php
resources/views/backoffice/security/checklists/show.blade.php
```

## Área do candidato

```text
resources/views/candidate/privacy/index.blade.php
resources/views/candidate/privacy/consents/index.blade.php
resources/views/candidate/privacy/data-subject-requests/index.blade.php
resources/views/candidate/privacy/data-subject-requests/create.blade.php
resources/views/candidate/privacy/data-subject-requests/show.blade.php
resources/views/candidate/privacy/data-exports/show.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 20. UX obrigatória

## Dashboard de segurança

Mostrar:

```text
Utilizadores backoffice
Utilizadores backoffice sem MFA
Tentativas de login falhadas
Acessos recentes a dados sensíveis
Downloads recentes de documentos
Exportações recentes
Alertas de segurança abertos
Pedidos RGPD pendentes
Políticas de retenção ativas
Última revisão de permissões
Última revisão de backups
Estado da checklist pré-produção
```

## Trilho de auditoria

A listagem deve permitir filtros por:

```text
Utilizador
Categoria
Severidade
Tipo de evento
Recurso
Titular dos dados
Período
IP
```

O detalhe deve mostrar:

```text
Evento
Descrição
Utilizador
Recurso
Data/hora
IP
User agent
Valores anteriores mascarados
Valores novos mascarados
Metadados seguros
```

## Centro RGPD do candidato

Mostrar:

```text
Finalidades de tratamento
Consentimentos ativos
Consentimentos opcionais revogáveis
Pedidos RGPD submetidos
Estado dos pedidos
Exportações disponíveis
Informação sobre prazos e análise municipal
```

Copy obrigatório:

```text
Os pedidos relacionados com dados pessoais serão analisados pelos serviços municipais, podendo ser necessária validação adicional de identidade e enquadramento legal.
```

## Pedido RGPD

Campos mínimos:

```text
Tipo de pedido
Descrição
Contacto preferencial
Documentos de suporte, se aplicável
```

Tipos visíveis:

```text
Acesso aos dados
Retificação
Apagamento
Limitação do tratamento
Portabilidade
Oposição
Retirada de consentimento
Pedido de informação
Outro
```

## Checklist pré-produção

Categorias mínimas:

```text
Autenticação
MFA
Permissões
Storage privado
Documentos
Auditoria
Logs de acesso
Exportações
Backups
Passwords
Sessões
Headers de segurança
RGPD
Retenção
Alertas
Configuração de produção
```

---

# 21. Regras de encriptação de campos sensíveis

Implementar com prudência.

Regras:

```text
Encriptar apenas campos que não sejam usados diretamente em login, joins, filtros ou pesquisa frequente.
Antes de encriptar, registar decisão em EncryptedFieldRegistry.
Quando pesquisa for necessária, considerar hash auxiliar não reversível.
Não alterar APP_KEY.
Não re-encriptar dados existentes sem migração segura e backup validado.
Não encriptar email de login sem estratégia.
Não quebrar unique indexes existentes.
Documentar campos não encriptados por limitação técnica.
```

Campos candidatos a encriptação:

```text
nif
tax_number
citizen_card_number
social_security_number
iban
health_or_disability_notes
income_notes
sensitive_document_metadata
```

Campos que exigem avaliação antes de encriptação:

```text
email
phone
address
birth_date
postal_code
name
```

---

# 22. Regras de anonimização

A anonimização deve:

```text
Exigir aprovação
Ser precedida de simulação
Preservar integridade referencial
Preservar dados agregados quando possível
Remover identificadores diretos
Mascarar contactos
Remover documentos pessoais quando permitido
Registar auditoria
Associar execução a pedido RGPD quando aplicável
```

Não anonimizar:

```text
Dados com obrigação legal de conservação ativa
Contratos ativos
Pagamentos necessários para contabilidade/controlo municipal
Processos administrativos ainda em curso
Dados necessários para defesa de direitos ou auditoria legal
```

Quando não for possível anonimizar, devolver decisão justificada no pedido RGPD.

---

# 23. Regras de retenção documental

A política de retenção deve permitir:

```text
Definir entidade alvo
Definir tipo documental
Definir prazo
Definir ação
Definir base legal
Definir aprovação obrigatória
Simular impacto
Executar ação com auditoria
```

Ações conservadoras por defeito:

```text
review_manually
archive
restrict
pseudonymize
```

Ações destrutivas devem exigir confirmação explícita:

```text
anonymize
delete_candidate
delete_permanently
```

Não executar eliminação permanente automática nesta sprint sem confirmação administrativa e testes.

---

# 24. Regras de alertas de segurança

Criar alertas para:

```text
Múltiplas falhas de login
Download em massa de documentos sensíveis
Exportação em massa de relatórios sensíveis
Acesso a muitos processos em curto período
Acesso fora do horário definido
Desativação de MFA de utilizador sensível
Alteração de permissões administrativas
Tentativa de acesso negado repetida
```

Cada alerta deve ter:

```text
Número
Regra
Severidade
Utilizador
Descrição
Dados contextuais seguros
Estado
Revisor
Resolução
```

Não enviar alertas externos sem integração segura.

---

# 25. Revisão de armazenamento privado

Verificar e reportar:

```text
Documentos em storage público
Downloads diretos sem controller
Exposição de storage_path
Nomes de ficheiro com dados pessoais
Ficheiros sem checksum
Ficheiros sem metadados
Rotas públicas que devolvem documentos
```

Criar relatório interno com:

```text
Risco
Recurso afetado
Descrição
Correção recomendada
Estado
```

---

# 26. Revisão de backups

Não executar backups reais sem configuração explícita.

Criar módulo de revisão com:

```text
Periodicidade declarada
Âmbito declarado
Localização lógica
Retenção
Último backup conhecido
Último teste de reposição
Responsável
Riscos
Recomendações
```

Checklist mínima:

```text
Existe backup da base de dados
Existe backup de storage privado
Existe política de retenção
Existe teste de restore
Existe segregação de acessos
Existe plano de recuperação
```

---

# 27. Integração com notificações — Sprint 16

Se Sprint 16 existir, gerar notificações internas para:

```text
Novo pedido RGPD
Pedido RGPD perto do prazo
Pedido RGPD vencido
Alerta de segurança crítico
MFA desativado para utilizador sensível
Exportação sensível gerada
Execução de retenção aprovada
Checklist pré-produção reprovada
```

Não enviar e-mail/SMS real sem configuração segura.

---

# 28. Integração com relatórios — Sprint 17

Se Sprint 17 existir:

```text
ReportExport deve gerar SensitiveDataAccessLog
Download de ReportExport deve gerar ReportDownloadLog e AuditEvent
Relatórios sensíveis devem aparecer nos logs de segurança
Exportações nominais devem ser rastreadas
```

---

# 29. Auditoria

Se já existir auditoria, integrar com ela.

Se não existir, `AuditEvent` passa a ser a base de auditoria avançada.

Auditoria deve ser:

```text
Append-only
Filtrável
Exportável apenas por perfil autorizado
Com dados sensíveis mascarados
Associada a user, recurso, IP e user agent
Capaz de auditar alterações críticas
Capaz de auditar acesso a documentos
Capaz de auditar exportações
Capaz de auditar pedidos RGPD
```

Não permitir alteração manual de eventos de auditoria por interface comum.

---

# 30. RGPD e segurança

Regras obrigatórias:

```text
Minimização de dados.
Acesso baseado em necessidade.
Registo de acessos sensíveis.
Rastreabilidade de exportações.
Storage privado para documentos e exportações.
Download sempre por controller autorizado.
MFA obrigatório para backoffice sensível.
Consentimentos com finalidade e snapshot.
Pedidos RGPD com histórico.
Retenção com simulação e aprovação.
Anonimização com simulação e aprovação.
Logs sem passwords/tokens.
Logs sem payloads completos sensíveis.
Backups revistos e documentados.
Checklist pré-produção executável.
```

---

# 31. Seeders e factories

Criar factories:

```text
AuditEventFactory
AccessLogFactory
SensitiveDataAccessLogFactory
PermissionReviewFactory
PermissionReviewItemFactory
ConsentPurposeFactory
UserConsentFactory
RetentionPolicyFactory
RetentionExecutionFactory
DataSubjectRequestFactory
DataSubjectRequestActionFactory
DataExportPackageFactory
AnonymizationRequestFactory
EncryptedFieldRegistryFactory
SecurityAlertRuleFactory
SecurityAlertFactory
BackupReviewFactory
SecurityChecklistFactory
SecurityChecklistItemFactory
```

Criar seeders:

```text
ConsentPurposeSeeder
SecurityAlertRuleSeeder
RetentionPolicyDemoSeeder
SecurityChecklistSeeder
SecurityDemoSeeder
```

Usar apenas dados fictícios.

Não usar dados pessoais reais.

Itens demo devem conter aviso interno:

```text
DEMO — SUJEITO A VALIDAÇÃO DO MUNICÍPIO/DPO
```

---

# 32. Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Autenticação e MFA

```text
backoffice_user_without_mfa_is_redirected_to_mfa_setup_when_required
admin_requires_mfa_for_sensitive_routes
candidate_area_does_not_require_backoffice_mfa_by_default
mfa_secret_is_not_stored_in_plain_text
recovery_codes_are_hashed
used_recovery_code_cannot_be_reused
mfa_activation_generates_audit_event
mfa_disable_requires_authorization
```

## Permissões

```text
permission_review_can_be_created
permission_review_lists_admin_users
permission_review_detects_users_without_mfa
permission_review_can_be_completed
unauthorized_user_cannot_view_permission_review
```

## Auditoria

```text
critical_action_creates_audit_event
audit_event_is_append_only
audit_event_masks_sensitive_values
document_download_creates_audit_event
report_export_creates_audit_event
permission_change_creates_audit_event
rgpd_request_action_creates_audit_event
```

## Logs de acesso

```text
login_creates_access_log
failed_login_creates_access_log
sensitive_record_view_creates_sensitive_access_log
document_download_creates_sensitive_access_log
export_download_creates_sensitive_access_log
access_log_does_not_store_plain_session_id
```

## Consentimentos

```text
consent_purpose_can_be_created
user_can_view_own_consents
user_can_withdraw_optional_consent
user_cannot_withdraw_required_legal_basis_as_optional_consent
consent_stores_text_snapshot
consent_withdrawal_creates_audit_event
```

## Pedidos RGPD

```text
candidate_can_create_data_subject_request
candidate_can_view_own_data_subject_request
candidate_cannot_view_other_user_data_subject_request
backoffice_can_assign_data_subject_request
data_subject_request_generates_unique_number
data_subject_request_has_due_date
data_subject_request_can_be_completed
data_subject_request_can_be_rejected_with_reason
```

## Exportação de dados do titular

```text
data_export_package_can_be_generated_for_request
data_export_package_is_stored_privately
data_export_package_has_checksum
candidate_can_download_own_data_export
candidate_cannot_download_other_user_data_export
data_export_download_is_logged
filename_does_not_contain_personal_data
```

## Retenção

```text
retention_policy_can_be_created
retention_simulation_does_not_change_data
retention_execution_requires_approval_when_configured
retention_execution_creates_audit_event
retention_execution_records_affected_count
```

## Anonimização

```text
anonymization_request_can_be_created
anonymization_requires_approval
anonymization_simulation_reports_scope
anonymization_execution_masks_direct_identifiers_when_allowed
anonymization_does_not_run_for_active_contract_without_approval
anonymization_creates_audit_event
```

## Encriptação de campos

```text
encrypted_field_registry_can_be_created
sensitive_field_can_be_marked_as_planned
field_used_for_login_is_marked_blocked_by_search_requirement
encrypted_cast_is_applied_only_when_safe
```

## Alertas de segurança

```text
multiple_failed_logins_create_security_alert
bulk_document_download_creates_security_alert
sensitive_export_creates_security_alert
security_alert_can_be_reviewed
security_alert_can_be_resolved
false_positive_can_be_marked
```

## Storage privado

```text
document_storage_review_detects_public_storage_documents
document_download_requires_authorization
storage_path_is_not_exposed
document_filename_does_not_contain_nif
document_filename_does_not_contain_email
```

## Backups e checklist

```text
backup_review_can_be_created
security_checklist_can_be_created
security_checklist_contains_required_categories
security_checklist_item_can_be_marked_passed
security_checklist_item_can_be_marked_failed
checklist_cannot_be_approved_with_failed_required_items
```

## Segurança geral

```text
unauthorized_user_cannot_access_audit_logs
unauthorized_user_cannot_access_sensitive_access_logs
candidate_cannot_access_security_dashboard
candidate_cannot_access_retention_policies
candidate_cannot_access_security_alerts
audit_log_export_requires_permission
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

Atualizar, se existirem:

```text
docs/backlog/sprint-18-rgpd-seguranca-auditoria-avancada.md
docs/backlog/roadmap.md
docs/product/functional-requirements.md
docs/product/process-workflows.md
docs/architecture/data-model-overview.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/security/pre-production-security-checklist.md
docs/security/data-retention-policy.md
docs/security/audit-trail.md
docs/security/backup-review.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Documentar:

```text
O que foi implementado
Tabelas criadas
Models criados
Enums criados
Middleware criado
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
Pendências para Sprint 19
Estado do MFA
Estado da auditoria
Estado dos consentimentos
Estado dos pedidos RGPD
Estado da retenção documental
Estado da anonimização
Estado da encriptação de campos
Estado dos alertas de segurança
Estado do storage privado
Estado da revisão de backups
Estado da checklist pré-produção
Validações jurídicas/DPO pendentes
```

---

# 35. Critérios de aceitação

A Sprint 18 está concluída quando:

```text
Todas as ações críticas são auditadas
Existe trilho de auditoria consultável
Eventos de auditoria são append-only
Acesso a dados sensíveis fica registado
Acesso a documentos fica registado
Download de documentos fica registado
Exportações ficam registadas
MFA está implementado para backoffice sensível ou pendência técnica está documentada
Permissões foram revistas e há relatório de revisão
Existem finalidades de tratamento
Existem consentimentos por finalidade
Consentimentos opcionais podem ser revogados
Existe módulo de pedidos RGPD
Candidato consegue submeter pedido RGPD
Município consegue gerir pedido RGPD
Existe exportação de dados do titular
Exportação de dados fica em storage privado
Existe política de retenção documental
Retenção pode ser simulada
Execução real de retenção exige aprovação quando aplicável
Existe mecanismo de anonimização/pseudonimização
Anonimização exige aprovação
Campos sensíveis foram avaliados para encriptação
Campos encriptados ou bloqueados estão documentados
Existem alertas de acesso indevido ou suspeito
Armazenamento privado de documentos foi revisto
Backups foram revistos documentalmente
Políticas de passwords foram revistas
Existe checklist de segurança pré-produção
Dados/documentos/exportações não ficam públicos
Backoffice exige permissões
Candidato não acede a dados de terceiros
Auditor consegue consultar logs sem alterar dados
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foram introduzidas credenciais
Não foi alterada APP_KEY
Não foi implementada eliminação destrutiva automática sem aprovação
Não foi declarada certificação legal absoluta
```

---

# 36. Resposta final esperada do Codex

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do que foi implementado
3. Ficheiros criados
4. Ficheiros alterados
5. Migrations criadas
6. Models criados ou alterados
7. Enums criados
8. Middleware criado ou alterado
9. Controllers criados ou alterados
10. Form Requests criados
11. Policies criadas
12. Services criados
13. Views/páginas criadas ou alteradas
14. Rotas criadas
15. Seeders/factories criados ou alterados
16. Testes criados ou alterados
17. Resultado dos comandos executados
18. Problemas encontrados
19. Pendências
20. Estado do MFA
21. Estado da auditoria
22. Estado dos logs de acesso
23. Estado dos consentimentos
24. Estado dos pedidos RGPD
25. Estado da retenção documental
26. Estado da anonimização/pseudonimização
27. Estado da encriptação de campos sensíveis
28. Estado dos alertas de segurança
29. Estado da revisão de storage privado
30. Estado da revisão de backups
31. Estado da checklist pré-produção
32. Validações jurídicas/DPO pendentes
33. Confirmação de que não foram implementadas funcionalidades fora de âmbito
34. Recomendação objetiva para avançar ou não para Sprint 19
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 37. Definition of Done

A Sprint 18 só está concluída quando a plataforma tiver controlo avançado de RGPD, segurança e auditoria, incluindo MFA para backoffice sensível, logs de acesso, trilho de auditoria completo, consentimentos por finalidade, pedidos RGPD, exportação de dados do titular, retenção documental, anonimização controlada, encriptação seletiva, alertas de segurança, revisão de storage privado, revisão de backups e checklist pré-produção.

O resultado deve preparar a plataforma para auditoria externa, testes finais e operação real com dados sensíveis, sem declarar certificação legal automática.

Fim da Sprint 18.
