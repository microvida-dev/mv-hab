# Sprint 6 — Gestão Documental Avançada

## Prioridade de desenvolvimento

Esta sprint pertence à prioridade funcional:

```text
1. Candidatura
```

A Sprint 6 cria a base documental necessária para que, na Sprint 8, a candidatura formal possa ser submetida com documentos obrigatórios, checklist dinâmica, validação de ficheiros e estados documentais.

Esta sprint liga-se diretamente às sprints anteriores:

```text
Sprint 3 — Portal público e programas
Sprint 4 — Registo de adesão e área pessoal
Sprint 5 — Agregado familiar, rendimentos e situação habitacional
```

E prepara as sprints seguintes:

```text
Sprint 7 — Motor de elegibilidade
Sprint 8 — Candidaturas e submissão formal
Sprint 9 — Workflow administrativo e aperfeiçoamento
Sprint 11 — Listas provisórias, reclamações e audiência
Sprint 18 — RGPD, segurança e auditoria avançada
```

---

# Objetivo da Sprint

Implementar o sistema documental avançado da plataforma municipal de Arrendamento Acessível.

A plataforma deve permitir:

- Criar tipos de documento;
- Definir documentos obrigatórios por programa;
- Definir documentos obrigatórios por concurso;
- Definir documentos obrigatórios por condição do candidato/agregado;
- Mostrar ao candidato uma checklist documental dinâmica;
- Permitir upload seguro de documentos;
- Guardar documentos em storage privado;
- Associar documentos ao Registo de Adesão;
- Associar documentos ao candidato;
- Associar documentos a membros do agregado;
- Associar documentos a rendimentos;
- Associar documentos à situação habitacional;
- Permitir substituição de documentos;
- Manter histórico de versões;
- Controlar estados documentais;
- Permitir validação administrativa inicial, se backoffice já estiver preparado;
- Proteger acesso a ficheiros;
- Auditar uploads, downloads e alterações de estado;
- Preparar a documentação para futura submissão formal da candidatura.

Esta sprint não deve implementar ainda candidatura formal nem decisão final de elegibilidade.

---

# Instrução operacional para Codex

Executa apenas esta Sprint 6.

Não avances para Sprint 7, Sprint 8, Sprint 9 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Antes de alterar código, lê primeiro, se existirem:

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
docs/backlog/sprint-0-foundation.md
docs/backlog/sprint-1-foundation.md
docs/backlog/sprint-2-foundation.md
docs/backlog/sprint-3-portal-publico-programas.md
docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/sprint-5-agregado-rendimentos-situacao-habitacional.md
docs/backlog/sprint-6-gestao-documental-avancada.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Antes de implementar, confirma que existem ou identifica alternativas para:

```text
Área do candidato
Role candidate
AdhesionRegistration
Household
HouseholdMember
IncomeRecord
CurrentHousingSituation
Program
Contest
Backoffice
Roles e permissões
Storage configurado
Auditoria, se existir
Componentes UI de formulário, tabela, badge e empty state
```

Se as Sprints 4 e 5 não estiverem implementadas, interrompe a execução e informa que a Sprint 6 depende do Registo de Adesão, Agregado, Rendimentos e Situação Habitacional.

Não duplicar entidades já existentes.

Se o CRM atual já tiver model `Document` ou tabela `documents`, avaliar se deve ser reaproveitado, migrado ou adaptado com compatibilidade.

Não apagar documentos existentes.

Não mover ficheiros existentes sem migração controlada.

---

# Âmbito desta Sprint

## Incluído

Implementar:

```text
Tipos de documento
Documentos obrigatórios
Checklist documental dinâmica
Submissão/upload de documentos
Armazenamento privado
Estados documentais
Versões de documento
Substituição de documentos
Validação técnica base
Rejeição com motivo
Download seguro
Pré-visualização segura quando aplicável
Registo de acessos a documentos
Auditoria documental
Integração com área do candidato
Integração com backoffice, se já existir
Seeders de tipos documentais
Factories
Testes mínimos
Atualização documental
```

## Fora de âmbito

Não implementar nesta sprint:

```text
Submissão formal de candidatura
Motor completo de elegibilidade
Classificação automática
Ranking
Listas provisórias
Reclamações
Audiência de interessados
Lista definitiva
Atribuição
Contratos
Pagamentos
Manutenção
Notificações reais por email/SMS
Integração com AT
Integração com Segurança Social
Integração com Autenticação.GOV
Assinatura digital
OCR
Validação automática de autenticidade documental
```

Pode preparar campos e arquitetura para estas funcionalidades futuras, mas não as deve implementar.

---

# Conceito funcional

A gestão documental deve substituir qualquer upload genérico existente por um sistema estruturado.

Cada documento deve ter:

```text
Tipo documental
Entidade associada
Ficheiro atual
Histórico de versões
Estado
Validação
Motivo de rejeição, se aplicável
Utilizador que submeteu
Utilizador que validou/rejeitou
Datas relevantes
Registo de acesso
```

A checklist documental deve variar conforme:

```text
Programa
Concurso
Registo de adesão
Membro do agregado
Situação profissional
Rendimentos declarados
Situação habitacional atual
Condições especiais declaradas
```

Exemplo:

```text
Se existe membro com deficiência/incapacidade declarada:
→ solicitar Atestado Médico de Incapacidade Multiuso.

Se existe rendimento de trabalho dependente:
→ solicitar recibos de vencimento ou comprovativos equivalentes.

Se existe situação de arrendamento atual:
→ solicitar contrato de arrendamento ou recibo de renda, se aplicável.

Se existe membro maior de idade:
→ solicitar documento de identificação e NIF.
```

Nesta sprint, as regras podem ser configuráveis e simples, sem motor de elegibilidade completo.

---

# Estados documentais

Criar estados formais:

```text
missing
submitted
under_review
validated
rejected
expired
replaced
cancelled
```

## Significado dos estados

### missing

Documento obrigatório ainda não submetido.

### submitted

Documento submetido pelo candidato e ainda não analisado.

### under_review

Documento em análise técnica.

### validated

Documento validado pelos serviços.

### rejected

Documento rejeitado, com motivo.

### expired

Documento expirado por validade documental ou regra administrativa.

### replaced

Documento substituído por nova versão.

### cancelled

Documento cancelado/anulado administrativamente.

---

# Regras de estado

## Submissão

Quando o candidato submete um documento:

```text
status = submitted
```

## Análise

Quando um técnico inicia análise:

```text
status = under_review
```

## Validação

Quando o documento é aprovado:

```text
status = validated
validated_at = now()
validated_by = user_id
```

## Rejeição

Quando o documento é rejeitado:

```text
status = rejected
rejected_at = now()
reviewed_by = user_id
rejection_reason obrigatório
```

## Substituição

Quando o candidato substitui documento rejeitado ou submetido:

```text
versão anterior = replaced
nova versão = submitted
```

## Expiração

O estado `expired` deve existir.

Não é obrigatório criar job automático de expiração nesta sprint, mas preparar campos e estrutura.

---

# Modelo de dados

## 1. DocumentType

Criar entidade:

```text
DocumentType
```

Tabela:

```text
document_types
```

## Campos mínimos

```text
id
code
name
description
category
applies_to
is_active
is_required_by_default
requires_expiry_date
requires_issue_date
allowed_mime_types
max_file_size_mb
sort_order
created_at
updated_at
deleted_at
```

## Valores recomendados para `category`

```text
identification
tax
social_security
income
housing
household
health
education
employment
declaration
other
```

## Valores recomendados para `applies_to`

```text
adhesion_registration
household
household_member
income_record
current_housing_situation
application
contract
general
```

## Notas

- `code` obrigatório e único.
- `name` obrigatório.
- `allowed_mime_types` pode ser JSON.
- `max_file_size_mb` default recomendado: 10.
- Usar soft deletes.

---

## 2. RequiredDocument

Criar entidade:

```text
RequiredDocument
```

Tabela:

```text
required_documents
```

## Campos mínimos

```text
id
document_type_id
program_id
contest_id
required_for
condition_key
condition_operator
condition_value
is_required
is_active
instructions
sort_order
created_at
updated_at
deleted_at
```

## Objetivo

Permitir configurar que documentos são exigidos para um determinado contexto.

## Campos explicados

### document_type_id

Tipo de documento exigido.

### program_id

Opcional. Documento obrigatório para um programa.

### contest_id

Opcional. Documento obrigatório para um concurso.

### required_for

Entidade à qual se aplica.

Valores:

```text
adhesion_registration
household
household_member
income_record
current_housing_situation
application
```

### condition_key

Campo ou condição que ativa a obrigatoriedade.

Exemplos:

```text
always
household_member.is_adult
household_member.is_disabled
household_member.is_student
household_member.professional_status
income_record.income_source
current_housing_situation.housing_status
current_housing_situation.is_at_risk_of_eviction
```

### condition_operator

Valores:

```text
always
equals
not_equals
greater_than
less_than
is_true
is_false
exists
```

### condition_value

Valor esperado, quando aplicável.

## Regras

- Se `condition_operator = always`, o documento é sempre exigido no contexto.
- Se `contest_id` estiver preenchido, a regra aplica-se ao concurso.
- Se `program_id` estiver preenchido e `contest_id` vazio, aplica-se ao programa.
- Se ambos estiverem vazios, pode ser regra global.
- Não implementar motor complexo nesta sprint; implementar avaliação simples e testável.

---

## 3. DocumentSubmission

Criar entidade:

```text
DocumentSubmission
```

Tabela:

```text
document_submissions
```

## Campos mínimos

```text
id
document_type_id
required_document_id

user_id
adhesion_registration_id
household_id
household_member_id
income_record_id
current_housing_situation_id
application_id
contract_id

status
title
original_filename
stored_filename
storage_disk
storage_path
mime_type
file_size
checksum

issue_date
expiry_date

submitted_at
submitted_by

reviewed_at
reviewed_by
validated_at
validated_by
rejected_at
rejected_by
rejection_reason

current_version_id
notes

created_at
updated_at
deleted_at
```

## Notas

- `application_id` e `contract_id` podem ser nullable, preparados para sprints futuras.
- `storage_path` deve apontar para storage privado.
- `checksum` deve ser gerado, se possível, para controlo de integridade.
- Não guardar ficheiros em pasta pública.
- Usar soft deletes.

---

## 4. DocumentVersion

Criar entidade:

```text
DocumentVersion
```

Tabela:

```text
document_versions
```

## Campos mínimos

```text
id
document_submission_id
version_number

original_filename
stored_filename
storage_disk
storage_path
mime_type
file_size
checksum

uploaded_by
uploaded_at

status_at_upload
notes

created_at
updated_at
```

## Regras

- A primeira submissão cria versão 1.
- Cada substituição cria versão incremental.
- A versão atual deve ser identificável.
- Versões antigas não devem ser apagadas fisicamente nesta sprint.
- Downloads de versões devem respeitar autorização.

---

## 5. DocumentReview

Criar entidade:

```text
DocumentReview
```

Tabela:

```text
document_reviews
```

## Campos mínimos

```text
id
document_submission_id
reviewed_by
from_status
to_status
decision
reason
internal_notes
created_at
updated_at
```

## Valores recomendados para `decision`

```text
submitted
under_review
validated
rejected
expired
cancelled
```

## Regras

- Cada alteração relevante de estado deve gerar `DocumentReview`.
- Motivo obrigatório para rejeição.
- Notas internas não devem ser visíveis ao candidato, salvo decisão explícita futura.
- O candidato deve ver apenas o motivo de rejeição apropriado.

---

## 6. DocumentAccessLog

Criar entidade:

```text
DocumentAccessLog
```

Tabela:

```text
document_access_logs
```

## Campos mínimos

```text
id
document_submission_id
document_version_id
user_id
action
ip_address
user_agent
url
created_at
```

## Valores recomendados para `action`

```text
view
download
preview
upload
replace
validate
reject
delete
```

## Objetivo

Registar acessos a documentos sensíveis.

---

# Relações

## DocumentType

```text
DocumentType hasMany RequiredDocument
DocumentType hasMany DocumentSubmission
```

## RequiredDocument

```text
RequiredDocument belongsTo DocumentType
RequiredDocument belongsTo Program nullable
RequiredDocument belongsTo Contest nullable
RequiredDocument hasMany DocumentSubmission
```

## DocumentSubmission

```text
DocumentSubmission belongsTo DocumentType
DocumentSubmission belongsTo RequiredDocument nullable
DocumentSubmission belongsTo User
DocumentSubmission belongsTo AdhesionRegistration nullable
DocumentSubmission belongsTo Household nullable
DocumentSubmission belongsTo HouseholdMember nullable
DocumentSubmission belongsTo IncomeRecord nullable
DocumentSubmission belongsTo CurrentHousingSituation nullable
DocumentSubmission hasMany DocumentVersion
DocumentSubmission hasMany DocumentReview
DocumentSubmission hasMany DocumentAccessLog
DocumentSubmission belongsTo currentVersion
```

## DocumentVersion

```text
DocumentVersion belongsTo DocumentSubmission
DocumentVersion belongsTo User as uploadedBy
```

## DocumentReview

```text
DocumentReview belongsTo DocumentSubmission
DocumentReview belongsTo User as reviewedBy
```

## DocumentAccessLog

```text
DocumentAccessLog belongsTo DocumentSubmission
DocumentAccessLog belongsTo DocumentVersion nullable
DocumentAccessLog belongsTo User
```

---

# Enums recomendados

Criar, se a versão do PHP permitir:

```text
App\Enums\DocumentStatus
App\Enums\DocumentCategory
App\Enums\DocumentAppliesTo
App\Enums\DocumentReviewDecision
App\Enums\DocumentAccessAction
```

## DocumentStatus

```text
missing
submitted
under_review
validated
rejected
expired
replaced
cancelled
```

## DocumentCategory

```text
identification
tax
social_security
income
housing
household
health
education
employment
declaration
other
```

## DocumentAppliesTo

```text
adhesion_registration
household
household_member
income_record
current_housing_situation
application
contract
general
```

## DocumentReviewDecision

```text
submitted
under_review
validated
rejected
expired
cancelled
```

## DocumentAccessAction

```text
view
download
preview
upload
replace
validate
reject
delete
```

---

# Tipos documentais mínimos

Criar seeder:

```text
DocumentTypeSeeder
```

Com os seguintes tipos mínimos:

```text
documento_identificacao
Documento de identificação
Categoria: identification
Aplica-se a: household_member

nif
Comprovativo de NIF
Categoria: tax
Aplica-se a: household_member

titulo_residencia
Título de residência
Categoria: identification
Aplica-se a: household_member

comprovativo_domicilio_fiscal
Comprovativo de domicílio fiscal
Categoria: tax
Aplica-se a: household_member

certidao_predial_negativa
Certidão predial negativa
Categoria: housing
Aplica-se a: household_member

irs
Declaração de IRS
Categoria: income
Aplica-se a: household_member

nota_liquidacao_irs
Nota de liquidação de IRS
Categoria: income
Aplica-se a: household_member

recibos_vencimento
Recibos de vencimento
Categoria: income
Aplica-se a: income_record

declaracao_seg_social
Declaração da Segurança Social
Categoria: social_security
Aplica-se a: income_record

comprovativo_pensao
Comprovativo de pensão
Categoria: income
Aplica-se a: income_record

comprovativo_subsidio_desemprego
Comprovativo de subsídio de desemprego
Categoria: income
Aplica-se a: income_record

atestado_incapacidade
Atestado médico de incapacidade multiuso
Categoria: health
Aplica-se a: household_member

comprovativo_estudante
Comprovativo de estudante
Categoria: education
Aplica-se a: household_member

contrato_arrendamento_atual
Contrato de arrendamento atual
Categoria: housing
Aplica-se a: current_housing_situation

recibo_renda
Recibo de renda
Categoria: housing
Aplica-se a: current_housing_situation

declaracao_honra
Declaração sob compromisso de honra
Categoria: declaration
Aplica-se a: adhesion_registration
```

Não usar documentos reais.

Não anexar ficheiros demo reais.

---

# Regras documentais mínimas

Criar seeder opcional:

```text
RequiredDocumentSeeder
```

Com regras globais simples:

```text
Documento de identificação
Obrigatório para cada membro do agregado.

NIF
Obrigatório para cada membro maior de idade.

Comprovativo de domicílio fiscal
Obrigatório para cada membro maior de idade.

Certidão predial negativa
Obrigatório para cada membro maior de idade.

IRS
Obrigatório para cada membro maior de idade, quando aplicável.

Nota de liquidação de IRS
Obrigatório para cada membro maior de idade, quando aplicável.

Recibos de vencimento
Obrigatório quando existe rendimento de trabalho dependente.

Declaração da Segurança Social
Obrigatório quando existe prestação social, subsídio de desemprego ou pensão.

Atestado médico de incapacidade multiuso
Obrigatório quando o membro declara deficiência/incapacidade.

Comprovativo de estudante
Obrigatório quando o membro declara ser estudante.

Contrato de arrendamento atual
Obrigatório quando a situação habitacional é arrendada.

Recibo de renda
Obrigatório quando existe renda atual declarada.

Declaração sob compromisso de honra
Obrigatório para o registo/candidatura.
```

Estas regras devem ser configuráveis e não hardcoded no controller.

---

# Storage

## Regras obrigatórias

- Usar storage privado.
- Não guardar documentos em `public`.
- Não criar symlink público para documentos sensíveis.
- Downloads devem passar por controller autorizado.
- Pré-visualização, se existir, deve passar por autorização.
- Nome original deve ser guardado, mas ficheiro armazenado deve ter nome seguro/único.
- Validar mime type.
- Validar tamanho.
- Rejeitar ficheiros perigosos.
- Preferir PDF, JPG, JPEG, PNG, WEBP quando aplicável.
- Não permitir executáveis.

## Disk recomendado

Se possível, configurar disk privado:

```text
private
```

ou usar:

```text
local
```

com paths protegidos.

Exemplo de path:

```text
documents/{adhesion_registration_id}/{document_submission_id}/{version_number}/{filename}
```

Nunca usar dados pessoais no path.

---

# Validação de ficheiros

## Mime types permitidos por defeito

```text
application/pdf
image/jpeg
image/png
image/webp
```

## Extensões permitidas por defeito

```text
pdf
jpg
jpeg
png
webp
```

## Tamanho máximo por defeito

```text
10 MB
```

Se `DocumentType` definir `allowed_mime_types` ou `max_file_size_mb`, usar esses valores.

## Regras

- Validar ficheiro no Form Request.
- Validar também no Service antes de guardar.
- Não confiar apenas na extensão.
- Guardar `mime_type` detetado.
- Guardar `file_size`.
- Gerar checksum se possível.

---

# Services

Criar services para evitar lógica pesada nos controllers.

## Services recomendados

```text
App\Services\Documents\DocumentChecklistService
App\Services\Documents\DocumentUploadService
App\Services\Documents\DocumentReviewService
App\Services\Documents\DocumentAccessService
App\Services\Documents\RequiredDocumentEvaluator
```

## DocumentChecklistService

Responsável por:

```text
Gerar checklist documental do candidato
Avaliar documentos obrigatórios globais
Avaliar documentos obrigatórios por programa
Avaliar documentos obrigatórios por concurso
Avaliar documentos obrigatórios por membro do agregado
Avaliar documentos obrigatórios por rendimento
Avaliar documentos obrigatórios por situação habitacional
Identificar documentos em falta
Identificar documentos submetidos
Identificar documentos rejeitados
Identificar documentos validados
Calcular percentagem de conclusão documental
```

## DocumentUploadService

Responsável por:

```text
Validar upload
Criar DocumentSubmission
Criar DocumentVersion
Guardar ficheiro em storage privado
Gerar nome seguro
Gerar checksum
Substituir documento
Atualizar estado
Registar acesso/log/auditoria
```

## DocumentReviewService

Responsável por:

```text
Colocar documento em análise
Validar documento
Rejeitar documento
Expirar documento
Cancelar documento
Criar DocumentReview
Guardar motivo de rejeição
Registar utilizador decisor
```

## DocumentAccessService

Responsável por:

```text
Autorizar acesso
Registar download
Registar preview
Devolver ficheiro com headers seguros
Impedir acesso indevido
```

## RequiredDocumentEvaluator

Responsável por:

```text
Avaliar regras simples de obrigatoriedade
Resolver condição always
Resolver condição is_true
Resolver condição equals
Resolver condição exists
Aplicar regras a household members
Aplicar regras a income records
Aplicar regras a current housing situation
```

Não criar motor complexo de elegibilidade nesta sprint.

---

# Controllers

Criar controllers conforme a stack e arquitetura existente.

## Área do candidato

Namespace recomendado:

```text
App\Http\Controllers\Candidate
```

Controllers:

```text
Candidate\DocumentController
Candidate\DocumentChecklistController
```

### Candidate\DocumentChecklistController

Ações:

```text
index
```

Responsável por:

```text
Mostrar checklist documental
Mostrar progresso documental
Mostrar documentos em falta
Mostrar documentos rejeitados
Mostrar documentos validados
```

### Candidate\DocumentController

Ações:

```text
index
show
create
store
replace
download
destroy, se aplicável
```

Responsável por:

```text
Listar documentos do candidato
Submeter documento
Substituir documento
Ver detalhe
Download seguro
Remover/cancelar documento quando permitido
```

## Backoffice

Se backoffice já existe e roles estão implementadas, criar:

```text
App\Http\Controllers\Backoffice\DocumentTypeController
App\Http\Controllers\Backoffice\RequiredDocumentController
App\Http\Controllers\Backoffice\DocumentReviewController
```

### DocumentTypeController

CRUD de tipos documentais.

### RequiredDocumentController

CRUD de documentos obrigatórios.

### DocumentReviewController

Validação/rejeição de documentos submetidos.

Se o backoffice não estiver estável, implementar apenas candidate-side e documentar backoffice como pendência. Preferência: implementar backoffice base se Sprint 1 foi concluída.

---

# Form Requests

Criar Form Requests:

```text
StoreDocumentTypeRequest
UpdateDocumentTypeRequest
StoreRequiredDocumentRequest
UpdateRequiredDocumentRequest
StoreDocumentSubmissionRequest
ReplaceDocumentSubmissionRequest
ValidateDocumentSubmissionRequest
RejectDocumentSubmissionRequest
```

## StoreDocumentSubmissionRequest

Validações mínimas:

```text
document_type_id required|exists:document_types,id
required_document_id nullable|exists:required_documents,id
household_member_id nullable|exists:household_members,id
income_record_id nullable|exists:income_records,id
current_housing_situation_id nullable|exists:current_housing_situations,id
title nullable|string|max:255
issue_date nullable|date
expiry_date nullable|date|after_or_equal:issue_date
file required|file|max:10240|mimes:pdf,jpg,jpeg,png,webp
notes nullable|string|max:2000
```

## ReplaceDocumentSubmissionRequest

```text
file required|file|max:10240|mimes:pdf,jpg,jpeg,png,webp
issue_date nullable|date
expiry_date nullable|date|after_or_equal:issue_date
notes nullable|string|max:2000
```

## RejectDocumentSubmissionRequest

```text
rejection_reason required|string|min:5|max:2000
internal_notes nullable|string|max:3000
```

## ValidateDocumentSubmissionRequest

```text
internal_notes nullable|string|max:3000
```

---

# Policies e autorização

Criar policies:

```text
DocumentTypePolicy
RequiredDocumentPolicy
DocumentSubmissionPolicy
DocumentReviewPolicy
```

## Regras para candidato

- Candidato só vê a sua checklist.
- Candidato só vê os seus documentos.
- Candidato só submete documentos para o seu próprio registo.
- Candidato só associa documentos aos seus próprios membros/rendimentos/situação habitacional.
- Candidato só descarrega os seus próprios documentos.
- Candidato não valida documentos.
- Candidato não rejeita documentos.
- Candidato não acede a documentos de outros candidatos.
- Candidato não altera `status` diretamente.

## Regras para técnico municipal

Se role existir:

- Pode consultar documentos submetidos;
- Pode colocar em análise;
- Pode validar;
- Pode rejeitar com motivo;
- Pode ver histórico;
- Não deve alterar ficheiro submetido pelo candidato;
- Não deve apagar documento sem permissão administrativa.

## Regras para auditor

- Pode consultar logs e histórico;
- Não pode alterar estado;
- Não pode validar/rejeitar;
- Acesso a ficheiros deve ser limitado e auditado.

## Regras para admin

- Pode gerir tipos documentais;
- Pode gerir regras de documentos obrigatórios;
- Pode consultar e atuar conforme permissões.

---

# Rotas

## Área do candidato

Preferência em português:

```text
GET /area-candidato/documentos
GET /area-candidato/documentos/checklist
GET /area-candidato/documentos/{documentSubmission}
GET /area-candidato/documentos/submeter
POST /area-candidato/documentos
GET /area-candidato/documentos/{documentSubmission}/substituir
POST /area-candidato/documentos/{documentSubmission}/substituir
GET /area-candidato/documentos/{documentSubmission}/download
DELETE /area-candidato/documentos/{documentSubmission}
```

Nomes recomendados:

```text
candidate.documents.index
candidate.documents.checklist
candidate.documents.show
candidate.documents.create
candidate.documents.store
candidate.documents.replace.create
candidate.documents.replace.store
candidate.documents.download
candidate.documents.destroy
```

## Backoffice

Se implementado nesta sprint:

```text
GET /backoffice/document-types
GET /backoffice/document-types/create
POST /backoffice/document-types
GET /backoffice/document-types/{documentType}/edit
PUT/PATCH /backoffice/document-types/{documentType}
DELETE /backoffice/document-types/{documentType}

GET /backoffice/required-documents
GET /backoffice/required-documents/create
POST /backoffice/required-documents
GET /backoffice/required-documents/{requiredDocument}/edit
PUT/PATCH /backoffice/required-documents/{requiredDocument}
DELETE /backoffice/required-documents/{requiredDocument}

GET /backoffice/document-reviews
GET /backoffice/document-reviews/{documentSubmission}
POST /backoffice/document-reviews/{documentSubmission}/under-review
POST /backoffice/document-reviews/{documentSubmission}/validate
POST /backoffice/document-reviews/{documentSubmission}/reject
GET /backoffice/document-reviews/{documentSubmission}/download
```

Nomes recomendados:

```text
backoffice.document-types.*
backoffice.required-documents.*
backoffice.document-reviews.index
backoffice.document-reviews.show
backoffice.document-reviews.under-review
backoffice.document-reviews.validate
backoffice.document-reviews.reject
backoffice.document-reviews.download
```

---

# Views / Páginas

Se o projeto usa Blade, criar:

## Área do candidato

```text
resources/views/candidate/documents/index.blade.php
resources/views/candidate/documents/checklist.blade.php
resources/views/candidate/documents/show.blade.php
resources/views/candidate/documents/create.blade.php
resources/views/candidate/documents/replace.blade.php
```

## Backoffice, se aplicável

```text
resources/views/backoffice/document-types/index.blade.php
resources/views/backoffice/document-types/create.blade.php
resources/views/backoffice/document-types/edit.blade.php

resources/views/backoffice/required-documents/index.blade.php
resources/views/backoffice/required-documents/create.blade.php
resources/views/backoffice/required-documents/edit.blade.php

resources/views/backoffice/document-reviews/index.blade.php
resources/views/backoffice/document-reviews/show.blade.php
```

Se o projeto usa Inertia/Vue/React, criar equivalentes.

---

# Checklist documental do candidato

## Objetivo

Mostrar uma visão clara ao candidato.

A checklist deve apresentar grupos:

```text
Documentos do registo
Documentos do agregado
Documentos por membro do agregado
Documentos de rendimentos
Documentos da situação habitacional
Documentos pendentes
Documentos rejeitados
Documentos validados
```

## Cada item da checklist deve mostrar

```text
Nome do documento
Descrição/instruções
Entidade associada
Estado
Obrigatório/opcional
Botão submeter
Botão substituir, se aplicável
Motivo de rejeição, se aplicável
Data de submissão
Data de validação, se aplicável
```

## Progresso documental

Mostrar:

```text
Total de documentos obrigatórios
Documentos submetidos
Documentos validados
Documentos rejeitados
Documentos em falta
Percentagem de conclusão
```

## Aviso obrigatório

Mostrar copy:

```text
A submissão de documentos nesta área prepara o seu processo para futuras candidaturas. A validação final dependerá das regras do programa e do concurso a que se candidatar.
```

---

# Upload de documentos

## Regras UX

- Mostrar tipo documental.
- Mostrar instruções.
- Mostrar formatos permitidos.
- Mostrar tamanho máximo.
- Mostrar campo de data de emissão, se aplicável.
- Mostrar campo de validade, se aplicável.
- Mostrar notas opcionais.
- Mostrar erro claro quando ficheiro é inválido.
- Mostrar sucesso após upload.
- Redirecionar para checklist após submissão.

## Copy de segurança

```text
Submeta apenas documentos legíveis e correspondentes ao tipo solicitado. Documentos ilegíveis, incompletos ou incorretos poderão ser rejeitados pelos serviços.
```

---

# Substituição de documentos

## Regras

- Candidato pode substituir documento em estado:
    - submitted;
    - rejected;
    - expired.

- Ao substituir:
    - versão anterior passa a `replaced`;
    - nova versão fica `submitted`;
    - histórico é mantido.

- Não apagar ficheiro anterior.
- Mostrar aviso antes de substituir.

## Copy

```text
Ao substituir este documento, a versão anterior será mantida no histórico do processo e a nova versão ficará pendente de análise.
```

---

# Validação administrativa

Se backoffice documental for implementado nesta sprint:

## Lista de documentos submetidos

Mostrar:

```text
Candidato
Tipo documental
Entidade associada
Estado
Data de submissão
Ações
```

## Detalhe de documento

Mostrar:

```text
Tipo documental
Dados do candidato
Entidade associada
Ficheiro
Histórico de versões
Histórico de decisões
Ações:
- Colocar em análise
- Validar
- Rejeitar
```

## Rejeição

Motivo obrigatório e visível ao candidato.

## Notas internas

Opcional e visível apenas no backoffice.

---

# Dashboard do candidato

Atualizar o dashboard da área do candidato para mostrar:

```text
Resumo documental
Documentos obrigatórios em falta
Documentos rejeitados
Documentos submetidos
Documentos validados
Próximo passo documental
Link para checklist
```

Exemplo de próximo passo:

```text
Existem documentos obrigatórios em falta. Aceda à checklist documental para completar o seu processo.
```

---

# Integração com progresso do Registo de Adesão

Atualizar `RegistrationProgressService` ou equivalente para incluir:

```text
Progresso documental
Número de documentos obrigatórios
Número de documentos em falta
Número de documentos rejeitados
Número de documentos validados
```

Não impedir finalização do Registo de Adesão nesta sprint, salvo se a regra já estiver definida.

A obrigatoriedade documental para submissão formal será aplicada na Sprint 8.

---

# Auditoria

Se auditoria existir, auditar:

```text
Criação de tipo documental
Atualização de tipo documental
Criação de regra de documento obrigatório
Atualização de regra de documento obrigatório
Upload de documento
Substituição de documento
Download de documento
Validação de documento
Rejeição de documento
Remoção/cancelamento de documento
```

Não guardar conteúdo do ficheiro no log.

Não guardar dados sensíveis desnecessários no log.

---

# RGPD e segurança documental

## Regras obrigatórias

- Documentos são dados sensíveis.
- Ficheiros devem ficar em storage privado.
- Acesso deve ser autorizado.
- Downloads devem ser registados.
- Pré-visualizações devem ser registadas.
- Candidato só vê os seus ficheiros.
- Técnico só vê ficheiros conforme permissões.
- Auditoria de acesso deve existir.
- Não expor paths internos ao utilizador.
- Não expor documentos por URL pública direta.
- Não enviar documentos por email nesta sprint.
- Não guardar ficheiros em `public`.
- Não permitir indexação por motores de busca.
- Não incluir dados sensíveis no nome de ficheiro armazenado.

---

# Seeders e factories

Criar factories:

```text
DocumentTypeFactory
RequiredDocumentFactory
DocumentSubmissionFactory
DocumentVersionFactory
DocumentReviewFactory
DocumentAccessLogFactory
```

Criar ou atualizar seeders:

```text
DocumentTypeSeeder
RequiredDocumentSeeder
DatabaseSeeder
```

Dados demo devem ser fictícios.

Não criar ficheiros reais com dados pessoais.

Se for necessário criar ficheiros fake para testes, usar `UploadedFile::fake()`.

---

# Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso e segurança

```text
guest_cannot_access_candidate_documents
non_candidate_cannot_access_candidate_documents
candidate_can_access_own_document_checklist
candidate_cannot_access_another_candidate_document
candidate_cannot_download_another_candidate_document
document_download_requires_authorization
document_files_are_not_publicly_accessible
```

## Tipos documentais

```text
admin_can_create_document_type
admin_can_update_document_type
document_type_code_must_be_unique
inactive_document_types_are_not_required_in_checklist
```

Se admin/backoffice não estiver implementado, adaptar testes ao que existir e documentar pendências.

## Regras de documentos obrigatórios

```text
required_document_can_be_created_for_program
required_document_can_be_created_for_contest
required_document_with_always_condition_appears_in_checklist
required_document_for_disabled_member_appears_when_member_is_disabled
required_document_for_student_appears_when_member_is_student
required_document_for_rented_housing_appears_when_housing_status_is_rented
```

## Checklist

```text
candidate_checklist_shows_missing_required_documents
candidate_checklist_shows_submitted_documents
candidate_checklist_shows_validated_documents
candidate_checklist_shows_rejected_documents
candidate_checklist_calculates_completion_percentage
```

## Upload

```text
candidate_can_upload_required_document
uploaded_document_status_is_submitted
uploaded_document_creates_version_one
uploaded_document_is_stored_on_private_disk
uploaded_document_stores_original_filename
uploaded_document_stores_mime_type_and_size
uploaded_document_generates_checksum_when_supported
invalid_file_type_is_rejected
file_over_max_size_is_rejected
```

## Substituição

```text
candidate_can_replace_rejected_document
replacing_document_creates_new_version
previous_document_version_is_preserved
previous_submission_status_becomes_replaced_or_version_status_is_marked_replaced
new_document_status_is_submitted
```

## Validação administrativa

```text
authorized_technician_can_mark_document_under_review
authorized_technician_can_validate_document
authorized_technician_can_reject_document_with_reason
rejection_reason_is_required
candidate_can_see_rejection_reason
candidate_cannot_see_internal_notes
```

Se backoffice review não for implementado nesta sprint, documentar estes testes como pendentes.

## Acesso e logs

```text
downloading_document_creates_access_log
previewing_document_creates_access_log_when_preview_exists
uploading_document_creates_access_log
validating_document_creates_review_record
rejecting_document_creates_review_record
```

## RGPD

```text
stored_filename_does_not_contain_candidate_name
storage_path_does_not_contain_nif
document_public_url_is_not_exposed
candidate_document_data_is_not_visible_to_other_candidates
```

---

# Comandos de validação

No final, executar:

```bash
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash
php artisan migrate
```

Se o projeto usar build frontend:

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

# Atualização documental obrigatória

Atualizar, se existirem:

```text
docs/backlog/sprint-6-gestao-documental-avancada.md
docs/backlog/roadmap.md
docs/product/functional-requirements.md
docs/product/process-workflows.md
docs/architecture/data-model-overview.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Registar:

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
Storage usado
Seeders/factories criados
Testes criados
Comandos executados
Pendências para Sprint 7 e Sprint 8
```

---

# Critérios de aceitação da Sprint 6

A Sprint 6 está concluída quando:

```text
Existem tipos documentais configuráveis
Existem regras de documentos obrigatórios configuráveis
A checklist documental do candidato existe
A checklist identifica documentos em falta
A checklist identifica documentos submetidos
A checklist identifica documentos validados
A checklist identifica documentos rejeitados
O candidato consegue submeter documento
O candidato consegue substituir documento quando permitido
Documentos ficam em storage privado
Download passa por controller autorizado
Candidato não acede a documentos de outro candidato
Estados documentais funcionam
Versões de documento são preservadas
Validação administrativa base funciona, se backoffice estiver implementado
Rejeição com motivo funciona, se backoffice estiver implementado
Acesso a documentos é registado
Auditoria documental é usada se existir
Dados sensíveis não aparecem em URLs públicas
Ficheiros perigosos são rejeitados
Tamanho máximo é validado
Mime types são validados
Dashboard do candidato mostra resumo documental
Documentação foi atualizada
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Não foi implementada candidatura formal
Não foi implementado motor de elegibilidade
Não foi implementada classificação
```

---

# Resposta final esperada do Codex

No final da execução, responder com:

```text
1. Resumo do que foi implementado na Sprint 6
2. Ficheiros criados
3. Ficheiros alterados
4. Migrations criadas
5. Models criados ou alterados
6. Enums criados
7. Controllers criados ou alterados
8. Form Requests criados
9. Policies criadas
10. Services criados
11. Views/páginas criadas ou alteradas
12. Rotas criadas
13. Configuração de storage aplicada
14. Seeders/factories criados ou alterados
15. Testes criados ou alterados
16. Resultado dos comandos executados
17. Problemas encontrados
18. Pendências
19. Confirmação de que não foram implementadas funcionalidades fora de âmbito
20. Recomendação objetiva para avançar ou não para Sprint 7/Sprint 8
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para Sprint 7 ou Sprint 8 sem validação explícita.

---

# Definition of Done

A Sprint 6 só está concluída quando a plataforma tiver uma gestão documental segura, privada, estruturada, versionada e preparada para suportar a submissão formal de candidaturas na Sprint 8.

---

# Registo de execução — 11/06/2026

## Estado

Sprint 6 executada no âmbito aprovado. Não foram implementadas candidaturas formais, elegibilidade, classificação, ranking, listas, reclamações, atribuição, contratos, pagamentos, notificações reais ou integrações externas.

## Implementado

- Catálogo administrativo de tipos documentais.
- Regras configuráveis de documentos obrigatórios.
- Checklist documental dinâmica do candidato.
- Submissão e substituição de documentos em storage privado.
- Histórico de versões documentais.
- Estados formais de documento.
- Revisão administrativa base: em análise, validado e rejeitado com motivo.
- Download autorizado por controller/policy.
- Logs de acesso documental.
- Auditoria de upload, substituição, download e decisões de revisão.
- Resumo documental no dashboard do candidato.

## Tabelas criadas

- `document_types`
- `required_documents`
- `document_submissions`
- `document_versions`
- `document_reviews`
- `document_access_logs`

## Models criados ou alterados

Criados:

- `DocumentType`
- `RequiredDocument`
- `DocumentSubmission`
- `DocumentVersion`
- `DocumentReview`
- `DocumentAccessLog`

Alterados:

- `AdhesionRegistration`
- `Household`
- `HouseholdMember`
- `IncomeRecord`
- `CurrentHousingSituation`

## Enums criados

- `DocumentStatus`
- `DocumentCategory`
- `DocumentAppliesTo`
- `DocumentReviewDecision`
- `DocumentAccessAction`
- `RequiredDocumentConditionOperator`

## Controllers criados

- `Candidate\DocumentChecklistController`
- `Candidate\DocumentController`
- `Admin\DocumentTypeController`
- `Admin\RequiredDocumentController`
- `Admin\DocumentReviewController`

## Requests criados

- `StoreDocumentTypeRequest`
- `UpdateDocumentTypeRequest`
- `StoreRequiredDocumentRequest`
- `UpdateRequiredDocumentRequest`
- `StoreDocumentSubmissionRequest`
- `ReplaceDocumentSubmissionRequest`
- `ValidateDocumentSubmissionRequest`
- `RejectDocumentSubmissionRequest`

## Policies criadas

- `DocumentTypePolicy`
- `RequiredDocumentPolicy`
- `DocumentSubmissionPolicy`
- `DocumentReviewPolicy`

## Services criados

- `RequiredDocumentEvaluator`
- `DocumentChecklistService`
- `DocumentAccessService`
- `DocumentUploadService`
- `DocumentReviewService`

## Views criadas ou alteradas

Criadas:

- `resources/views/candidate/documents/index.blade.php`
- `resources/views/candidate/documents/checklist.blade.php`
- `resources/views/candidate/documents/create.blade.php`
- `resources/views/candidate/documents/replace.blade.php`
- `resources/views/candidate/documents/show.blade.php`
- `resources/views/admin/document-types/*`
- `resources/views/admin/required-documents/*`
- `resources/views/admin/document-reviews/index.blade.php`
- `resources/views/admin/document-reviews/show.blade.php`

Alteradas:

- dashboard do candidato;
- navegação principal.

## Rotas criadas

- `candidate.documents.index`
- `candidate.documents.checklist`
- `candidate.documents.create`
- `candidate.documents.store`
- `candidate.documents.show`
- `candidate.documents.replace.create`
- `candidate.documents.replace.store`
- `candidate.documents.download`
- `candidate.documents.destroy`
- `admin.document-types.*`
- `admin.required-documents.*`
- `admin.document-reviews.*`

## Storage usado

- Disk `local`, configurado pela aplicação como privado em `storage/app/private`.
- O path físico é guardado apenas em `document_versions.storage_path`.
- Downloads passam por controller autorizado.

## Seeders e factories criados ou alterados

Criados:

- `DocumentTypeSeeder`
- `RequiredDocumentSeeder`
- `DocumentTypeFactory`
- `RequiredDocumentFactory`
- `DocumentSubmissionFactory`
- `DocumentVersionFactory`
- `DocumentReviewFactory`
- `DocumentAccessLogFactory`

Alterado:

- `DatabaseSeeder`
- `HouseholdFactory`

## Testes criados ou alterados

Criado:

- `tests/Feature/Sprint6DocumentManagementTest.php`

Alterado:

- `tests/Feature/Sprint4AdhesionRegistrationTest.php`, para substituir a expectativa do placeholder documental pela página documental real.

## Comandos executados e resultado

- `php artisan --version`: Laravel Framework 13.12.0.
- `php -v`: PHP 8.5.6.
- `composer validate`: válido.
- `php artisan route:list --name=candidate.documents`: sem erro.
- `php artisan route:list --name=admin.document`: sem erro.
- `php artisan migrate --pretend`: sem erro.
- `php artisan test --filter=Sprint6DocumentManagementTest`: passou, 10 testes e 78 asserções.
- `php artisan migrate`: migration incremental executada.
- `php artisan route:list`: sem erro, 158 rotas.
- `php artisan test`: passou, 68 testes e 369 asserções.
- `npm run build`: passou.
- `./vendor/bin/pint`: passou e corrigiu estilo em 3 ficheiros.
- `php artisan test`: repetido após Pint; passou, 68 testes e 369 asserções.
- `find vendor/bin -maxdepth 1 \( -name phpstan -o -name psalm \) -print`: não encontrou PHPStan/Psalm.
- `php artisan db:seed --class=DocumentTypeSeeder --force`: primeira execução falhou na sandbox por acesso MySQL; repetido fora da sandbox com sucesso.
- `php artisan db:seed --class=RequiredDocumentSeeder --force`: sucesso.
- `php artisan db:seed --class=SystemAccessSeeder --force`: sucesso.
- `php artisan db:seed --class=IncomeSourceSeeder --force`: sucesso.
- Verificação browser em `http://127.0.0.1:8002`: login fictício, checklist, formulário de submissão e viewport móvel `390x844` sem overflow horizontal.

## Problemas encontrados

- A factory `HouseholdFactory::candidate()` criava um novo Registo de Adesão em vez de aceitar o registo do cenário; foi ajustada mantendo compatibilidade.
- Um teste antigo da Sprint 4 ainda esperava o placeholder "Gestão documental futura"; foi atualizado porque a Sprint 6 substituiu o placeholder por funcionalidade real.
- A sandbox bloqueou ligação MySQL para um seeder e escrita do histórico do Tinker; os comandos foram repetidos fora da sandbox e o erro foi documentado.
- O teste de storage público esperava 404, mas Laravel devolve 403 para acesso proibido a `/storage/...`; o teste foi alinhado com o comportamento real de proteção.

## Pendências

- Confirmar juridicamente retenção, sensibilidade e base legal de cada tipo documental.
- Introduzir antivírus/quarentena antes de produção.
- Definir prazos de correção documental e notificações reais.
- Ligar a checklist documental ao bloqueio da submissão formal na Sprint 8.
- Implementar snapshots e associação formal de documentos à candidatura na Sprint 8.

Fim da Sprint 6.
