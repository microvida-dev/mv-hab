# MASTER PROMPT — EXECUÇÃO DA SPRINT 6: DOCUMENTAÇÃO E GESTÃO DOCUMENTAL AVANÇADA

Atua como arquiteto sénior Laravel, tech lead e product engineer.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 6 — Documentação e Gestão Documental Avançada
```

Esta sprint pertence à prioridade funcional:

```text
3. Documentação
```

A Sprint 6 deve criar um sistema documental seguro, privado, configurável, versionado e auditável para suportar o Registo de Adesão, a Candidatura, a Elegibilidade, o Procedimento Administrativo e as fases futuras da plataforma municipal de Arrendamento Acessível.

---

# 1. Regra principal

Executa apenas a Sprint 6.

Não avances para Sprint 7, Sprint 8, Sprint 9, Sprint 10, Sprint 11 ou qualquer outra sprint sem validação explícita.

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
docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
```

Se este ficheiro não existir, procurar também:

```text
docs/backlog/sprint-6-gestao-documental-avancada.md
```

Se nenhum existir, interrompe e informa que falta o ficheiro de definição da Sprint 6.

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
docs/backlog/sprint-5-agregado-rendimentos-situacao-habitacional.md
docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

---

# 4. Inspeção inicial obrigatória

Antes de implementar, inspeciona o repositório e identifica:

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
Sistema de storage
Sistema de auditoria, se existir
Modelo User
Modelo Program
Modelo Contest
Modelo AdhesionRegistration
Modelo Household
Modelo HouseholdMember
Modelo IncomeRecord
Modelo CurrentHousingSituation
Modelo Application, se existir
Modelo Document ou documents existente, se existir
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
Document
DocumentType
RequiredDocument
DocumentSubmission
DocumentVersion
DocumentReview
DocumentAccessLog
```

reaproveitar ou adaptar com compatibilidade, em vez de criar estrutura paralela.

Não apagar dados existentes.

Não apagar documentos existentes.

Não mover ficheiros existentes sem migração controlada.

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens ou APP_KEY.

---

# 5. Dependências da Sprint 6

A Sprint 6 depende preferencialmente de:

```text
Sprint 4 — Registo de Adesão e Área Pessoal
Sprint 5 — Agregado Familiar, Rendimentos e Situação Habitacional
```

Se não existir `AdhesionRegistration`, interrompe a implementação funcional e informa que a Sprint 6 depende da Sprint 4.

Se não existirem `Household`, `HouseholdMember`, `IncomeRecord` ou `CurrentHousingSituation`, implementa apenas a base documental genérica e documenta como pendência a checklist dinâmica por agregado, rendimentos e situação habitacional.

Se existir `Application`, preparar associação documental à candidatura.

Se não existir `Application`, criar apenas campos nullable e pontos de integração futura. Não criar candidatura nesta sprint.

---

# 6. Objetivo da implementação

Implementar uma camada documental transversal que permita:

```text
Criar tipos documentais
Configurar documentos obrigatórios
Configurar regras documentais por programa
Configurar regras documentais por concurso
Configurar regras documentais por condição do candidato/agregado
Gerar checklist documental dinâmica
Submeter documentos
Substituir documentos
Guardar versões
Guardar documentos em storage privado
Impedir acesso público direto
Validar documentos no backoffice
Rejeitar documentos com motivo
Mostrar motivo de rejeição ao candidato
Manter notas internas apenas no backoffice
Registar downloads e acessos
Auditar ações documentais quando a auditoria existir
Preparar integração com elegibilidade
Preparar integração com candidatura formal
```

---

# 7. Âmbito incluído

Implementar:

```text
DocumentType
RequiredDocument
DocumentSubmission
DocumentVersion
DocumentReview
DocumentAccessLog

Enums documentais
Services documentais
Storage privado
Upload seguro
Substituição de documentos
Versionamento documental
Checklist documental
Estados documentais
Validação administrativa
Rejeição com motivo
Download seguro
Logs de acesso
Policies
Form Requests
Rotas
Views/páginas
Seeders
Factories
Testes
Atualização documental
```

---

# 8. Fora de âmbito

Não implementar nesta sprint:

```text
Candidatura formal
Motor de elegibilidade completo
Classificação
Ranking
Listas provisórias
Reclamações
Audiência de interessados
Listas definitivas
Atribuição
Sorteio
Contrato
Cálculo de renda
Pagamentos
Manutenção
Notificações reais por email/SMS
Integrações com Autoridade Tributária
Integrações com Segurança Social
Integração com Autenticação.GOV
OCR
Validação automática de autenticidade documental
Assinatura digital
```

Podem ser criados pontos de integração, mas não funcionalidades completas dessas fases.

---

# 9. Estados documentais obrigatórios

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

## Regras de estado

### Upload inicial

```text
status = submitted
submitted_at = now()
submitted_by = user_id
```

### Colocar em análise

```text
status = under_review
reviewed_at = now()
reviewed_by = user_id
```

### Validação

```text
status = validated
validated_at = now()
validated_by = user_id
```

### Rejeição

```text
status = rejected
rejected_at = now()
rejected_by = user_id
rejection_reason obrigatório
```

### Substituição

```text
Versão anterior preservada
Nova versão criada
Nova versão fica submitted
Histórico atualizado
Access log criado
```

---

# 10. Modelo de dados a implementar

## 10.1 DocumentType

Tabela:

```text
document_types
```

Campos mínimos:

```text
id
code
name
description
category
applies_to
is_active
is_required_by_default
requires_issue_date
requires_expiry_date
allowed_mime_types
max_file_size_mb
sort_order
created_at
updated_at
deleted_at
```

Regras:

```text
code obrigatório e único
name obrigatório
allowed_mime_types em JSON ou cast equivalente
max_file_size_mb default 10
soft deletes
```

---

## 10.2 RequiredDocument

Tabela:

```text
required_documents
```

Campos mínimos:

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

Regras:

```text
Se condition_operator = always, a regra aplica-se sempre.
Se contest_id estiver preenchido, aplica-se ao concurso.
Se program_id estiver preenchido e contest_id vazio, aplica-se ao programa.
Se ambos estiverem vazios, regra é global.
Regras de concurso prevalecem sobre regras globais/programa quando aplicável.
```

---

## 10.3 DocumentSubmission

Tabela:

```text
document_submissions
```

Campos mínimos:

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

Regras:

```text
application_id nullable, preparado para Sprint 8.
contract_id nullable, preparado para fase contratual.
storage_path privado.
Não guardar ficheiros em public.
checksum recomendado.
soft deletes.
```

---

## 10.4 DocumentVersion

Tabela:

```text
document_versions
```

Campos mínimos:

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

Regras:

```text
Primeira submissão cria versão 1.
Cada substituição cria nova versão incremental.
Versões antigas são preservadas.
Downloads de versões exigem autorização.
```

---

## 10.5 DocumentReview

Tabela:

```text
document_reviews
```

Campos mínimos:

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

Regras:

```text
Cada alteração relevante de estado cria DocumentReview.
Motivo obrigatório para rejeição.
internal_notes visível apenas no backoffice.
reason pode ser visível ao candidato quando for motivo de rejeição.
```

---

## 10.6 DocumentAccessLog

Tabela:

```text
document_access_logs
```

Campos mínimos:

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

Objetivo:

```text
Registar acessos e ações sobre documentos sensíveis.
```

---

# 11. Enums a criar

Criar, se a versão do PHP permitir:

```text
App\Enums\DocumentStatus
App\Enums\DocumentCategory
App\Enums\DocumentAppliesTo
App\Enums\DocumentReviewDecision
App\Enums\DocumentAccessAction
App\Enums\RequiredDocumentConditionOperator
```

Se o projeto não suportar enums PHP, criar classes de constantes.

---

# 12. Relações obrigatórias

## DocumentType

```text
hasMany RequiredDocument
hasMany DocumentSubmission
```

## RequiredDocument

```text
belongsTo DocumentType
belongsTo Program nullable
belongsTo Contest nullable
hasMany DocumentSubmission
```

## DocumentSubmission

```text
belongsTo DocumentType
belongsTo RequiredDocument nullable
belongsTo User
belongsTo AdhesionRegistration nullable
belongsTo Household nullable
belongsTo HouseholdMember nullable
belongsTo IncomeRecord nullable
belongsTo CurrentHousingSituation nullable
belongsTo Application nullable, se existir
belongsTo Contract nullable, se existir
hasMany DocumentVersion
hasMany DocumentReview
hasMany DocumentAccessLog
belongsTo currentVersion
```

## DocumentVersion

```text
belongsTo DocumentSubmission
belongsTo User as uploadedBy
```

## DocumentReview

```text
belongsTo DocumentSubmission
belongsTo User as reviewedBy
```

## DocumentAccessLog

```text
belongsTo DocumentSubmission
belongsTo DocumentVersion nullable
belongsTo User
```

---

# 13. Storage privado obrigatório

Documentos nunca devem ficar publicamente acessíveis.

Regras:

```text
Não usar storage/app/public para documentos sensíveis.
Não criar symlink público para documentos.
Downloads passam por controller autorizado.
Pré-visualização, se existir, passa por controller autorizado.
Não expor storage_path ao utilizador.
Não usar dados pessoais no nome do ficheiro guardado.
Não usar NIF, nome, email ou número de documento no path.
```

Se já existir disk privado, usar esse disk.

Caso contrário, usar `local` com paths protegidos.

Path recomendado:

```text
documents/{adhesion_registration_id}/{document_submission_id}/{version_number}/{stored_filename}
```

O `stored_filename` deve ser seguro:

```text
uuid.extension
```

---

# 14. Validação de ficheiros

Mime types permitidos por defeito:

```text
application/pdf
image/jpeg
image/png
image/webp
```

Extensões permitidas por defeito:

```text
pdf
jpg
jpeg
png
webp
```

Tamanho máximo por defeito:

```text
10 MB
```

Se `DocumentType` definir `allowed_mime_types` ou `max_file_size_mb`, usar os valores do tipo documental.

Regras:

```text
Validar ficheiro no Form Request.
Validar também no Service antes de guardar.
Não confiar apenas na extensão.
Guardar mime_type real.
Guardar file_size.
Gerar checksum.
Rejeitar executáveis.
Rejeitar ficheiros sem extensão válida.
Rejeitar mime types não permitidos.
```

---

# 15. Services obrigatórios

Criar:

```text
App\Services\Documents\DocumentChecklistService
App\Services\Documents\DocumentUploadService
App\Services\Documents\DocumentReviewService
App\Services\Documents\DocumentAccessService
App\Services\Documents\RequiredDocumentEvaluator
App\Services\Documents\DocumentVersionService
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
Calcular progresso documental
```

## RequiredDocumentEvaluator

Responsável por avaliar:

```text
always
equals
not_equals
greater_than
less_than
greater_than_or_equal
less_than_or_equal
is_true
is_false
exists
not_exists
in
not_in
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
Atualizar current_version_id
Atualizar estado para submitted
Registar upload em DocumentAccessLog
Usar auditoria, se existir
```

## DocumentVersionService

Responsável por:

```text
Criar versão inicial
Criar nova versão em substituição
Calcular próximo version_number
Preservar versões antigas
Marcar estado anterior como replaced quando aplicável
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
Guardar notas internas
Atualizar datas e utilizadores decisores
```

## DocumentAccessService

Responsável por:

```text
Autorizar acesso ao documento
Autorizar acesso à versão
Registar download
Registar preview, se existir
Devolver ficheiro com headers seguros
Impedir acesso indevido
Ocultar storage_path
```

---

# 16. Controllers obrigatórios

## Área do candidato

Criar em:

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

### Candidate\DocumentController

Ações:

```text
index
show
create
store
replaceForm
replace
download
destroy
```

---

## Backoffice

Criar em:

```text
App\Http\Controllers\Backoffice
```

Controllers:

```text
Backoffice\DocumentTypeController
Backoffice\RequiredDocumentController
Backoffice\DocumentReviewController
```

### DocumentTypeController

CRUD de tipos documentais.

### RequiredDocumentController

CRUD de regras de documentos obrigatórios.

### DocumentReviewController

Ações recomendadas:

```text
index
show
markUnderReview
validateDocument
rejectDocument
download
```

---

# 17. Form Requests obrigatórios

Criar:

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

# 18. Policies obrigatórias

Criar:

```text
DocumentTypePolicy
RequiredDocumentPolicy
DocumentSubmissionPolicy
DocumentReviewPolicy
DocumentVersionPolicy
DocumentAccessLogPolicy
```

## Regras para candidato

```text
Candidato só vê os seus documentos.
Candidato só submete documentos no seu próprio registo.
Candidato só associa documentos aos seus próprios membros/rendimentos/situação habitacional.
Candidato só descarrega os seus próprios documentos.
Candidato pode substituir documentos próprios quando permitido.
Candidato não valida documentos.
Candidato não rejeita documentos.
Candidato não altera status diretamente.
Candidato não vê documentos de outros candidatos.
```

## Regras para técnico municipal

```text
Pode consultar documentos submetidos conforme permissões.
Pode colocar documento em análise.
Pode validar documento.
Pode rejeitar documento com motivo.
Pode consultar histórico.
Pode descarregar documento se autorizado.
Não deve alterar ficheiro submetido pelo candidato.
```

## Regras para admin

```text
Pode gerir tipos documentais.
Pode gerir regras de documentos obrigatórios.
Pode consultar e atuar conforme permissões.
```

## Regras para auditor

```text
Pode consultar histórico e logs conforme permissões.
Não pode alterar estados.
Não pode validar/rejeitar.
Acesso a ficheiros deve ser auditado.
```

---

# 19. Rotas

## Área do candidato

Criar, preferencialmente:

```text
GET /area-candidato/documentos
GET /area-candidato/documentos/checklist
GET /area-candidato/documentos/submeter
POST /area-candidato/documentos
GET /area-candidato/documentos/{documentSubmission}
GET /area-candidato/documentos/{documentSubmission}/substituir
POST /area-candidato/documentos/{documentSubmission}/substituir
GET /area-candidato/documentos/{documentSubmission}/download
DELETE /area-candidato/documentos/{documentSubmission}
```

Nomes recomendados:

```text
candidate.documents.index
candidate.documents.checklist
candidate.documents.create
candidate.documents.store
candidate.documents.show
candidate.documents.replace.create
candidate.documents.replace.store
candidate.documents.download
candidate.documents.destroy
```

## Backoffice

Criar, preferencialmente:

```text
GET /backoffice/document-types
GET /backoffice/document-types/create
POST /backoffice/document-types
GET /backoffice/document-types/{documentType}
GET /backoffice/document-types/{documentType}/edit
PUT/PATCH /backoffice/document-types/{documentType}
DELETE /backoffice/document-types/{documentType}

GET /backoffice/required-documents
GET /backoffice/required-documents/create
POST /backoffice/required-documents
GET /backoffice/required-documents/{requiredDocument}
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

---

# 20. Views / páginas

Se o projeto usa Blade, criar:

## Candidato

```text
resources/views/candidate/documents/index.blade.php
resources/views/candidate/documents/checklist.blade.php
resources/views/candidate/documents/show.blade.php
resources/views/candidate/documents/create.blade.php
resources/views/candidate/documents/replace.blade.php
```

## Backoffice

```text
resources/views/backoffice/document-types/index.blade.php
resources/views/backoffice/document-types/create.blade.php
resources/views/backoffice/document-types/edit.blade.php
resources/views/backoffice/document-types/show.blade.php

resources/views/backoffice/required-documents/index.blade.php
resources/views/backoffice/required-documents/create.blade.php
resources/views/backoffice/required-documents/edit.blade.php
resources/views/backoffice/required-documents/show.blade.php

resources/views/backoffice/document-reviews/index.blade.php
resources/views/backoffice/document-reviews/show.blade.php
```

Se o projeto usa Inertia, Vue ou React, criar equivalentes mantendo a stack existente.

---

# 21. Checklist documental do candidato

A checklist deve agrupar:

```text
Documentos do registo
Documentos do agregado
Documentos por membro do agregado
Documentos de rendimentos
Documentos da situação habitacional
Documentos de candidatura, se existir
Documentos em falta
Documentos submetidos
Documentos rejeitados
Documentos validados
```

Cada item deve mostrar:

```text
Nome do documento
Descrição/instruções
Entidade associada
Estado
Obrigatório/opcional
Formato permitido
Tamanho máximo
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
Documentos em falta
Documentos submetidos
Documentos em análise
Documentos validados
Documentos rejeitados
Percentagem de conclusão
```

## Copy obrigatório

```text
A submissão de documentos nesta área prepara o seu processo para futuras candidaturas. A validação final dependerá das regras do programa, do concurso e da análise dos serviços municipais.
```

---

# 22. Upload de documentos

A página de upload deve mostrar:

```text
Tipo documental
Instruções
Formatos permitidos
Tamanho máximo
Campo de ficheiro
Data de emissão, se aplicável
Data de validade, se aplicável
Notas opcionais
Botão submeter
```

Copy recomendado:

```text
Submeta apenas documentos legíveis e correspondentes ao tipo solicitado. Documentos ilegíveis, incompletos ou incorretos poderão ser rejeitados pelos serviços.
```

---

# 23. Substituição de documentos

Permitir substituição em estados:

```text
submitted
rejected
expired
```

Opcionalmente, permitir em `under_review` apenas se a regra de negócio o permitir.

Ao substituir:

```text
Nova versão é criada
Versão anterior é preservada
Nova versão fica submitted
Histórico é atualizado
Access log é criado
```

Copy obrigatório:

```text
Ao substituir este documento, a versão anterior será mantida no histórico do processo e a nova versão ficará pendente de análise.
```

---

# 24. Validação administrativa

## Lista de documentos submetidos

Mostrar:

```text
Candidato
Tipo documental
Entidade associada
Estado
Data de submissão
Data de última atualização
Ações
```

## Detalhe do documento

Mostrar:

```text
Tipo documental
Candidato
Entidade associada
Estado
Ficheiro atual
Histórico de versões
Histórico de decisões
Logs de acesso, se permitido
Ações:
- Colocar em análise
- Validar
- Rejeitar
- Download seguro
```

## Rejeição

Motivo obrigatório.

O candidato deve ver `rejection_reason`.

`internal_notes` fica apenas no backoffice.

---

# 25. Dashboard do candidato

Atualizar dashboard da área do candidato para mostrar resumo documental:

```text
Documentos obrigatórios
Documentos em falta
Documentos submetidos
Documentos em análise
Documentos validados
Documentos rejeitados
Próximo passo documental
Link para checklist
```

Exemplo:

```text
Existem documentos obrigatórios em falta. Aceda à checklist documental para completar o seu processo.
```

---

# 26. Integração com elegibilidade e candidatura

## Para Sprint 7

Preparar métodos para o motor de elegibilidade consultar:

```text
Documentos obrigatórios em falta
Documentos submetidos
Documentos validados
Documentos rejeitados
Documentos expirados
Percentagem documental
```

## Para Sprint 8

Preparar método para candidatura formal associar documentos:

```text
DocumentSubmission relevantes
Estado documental no momento da submissão
Metadados dos documentos
```

Não implementar Sprint 7 ou Sprint 8 nesta sprint.

---

# 27. Auditoria

Se existir auditoria, auditar:

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
Cancelamento de documento
```

Não criar auditoria paralela.

Se auditoria não existir, documentar pendência.

Não guardar conteúdo de ficheiros nos logs.

---

# 28. RGPD e segurança documental

Regras obrigatórias:

```text
Documentos são dados sensíveis.
Ficheiros ficam em storage privado.
Acesso é autorizado por policy.
Downloads são registados.
Pré-visualizações são registadas, se existirem.
Candidato só vê documentos próprios.
Backoffice só acede conforme permissões.
Auditor não altera documentos.
Não expor paths internos.
Não expor documentos por URL pública direta.
Não enviar documentos por email nesta sprint.
Não guardar ficheiros em public.
Não permitir indexação.
Não incluir dados pessoais no stored_filename.
Não incluir NIF, nome ou email no storage_path.
```

---

# 29. Seeders e factories

Criar factories:

```text
DocumentTypeFactory
RequiredDocumentFactory
DocumentSubmissionFactory
DocumentVersionFactory
DocumentReviewFactory
DocumentAccessLogFactory
```

Criar seeders:

```text
DocumentTypeSeeder
RequiredDocumentSeeder
```

Atualizar:

```text
DatabaseSeeder
```

Usar apenas dados fictícios.

Para testes de ficheiro, usar:

```text
UploadedFile::fake()
```

Não criar ficheiros reais com dados pessoais.

---

# 30. Testes obrigatórios

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

## Regras obrigatórias

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

## Logs e histórico

```text
downloading_document_creates_access_log
uploading_document_creates_access_log
replacing_document_creates_access_log
validating_document_creates_review_record
rejecting_document_creates_review_record
document_status_change_is_recorded
```

## RGPD

```text
stored_filename_does_not_contain_candidate_name
storage_path_does_not_contain_nif
document_public_url_is_not_exposed
candidate_document_data_is_not_visible_to_other_candidates
```

## Auditoria, se existir

```text
uploading_document_generates_audit_log
validating_document_generates_audit_log
rejecting_document_generates_audit_log
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

No final, atualizar, se existirem:

```text
docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
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
Storage usado
Seeders/factories criados
Testes criados
Comandos executados
Resultado dos comandos
Problemas encontrados
Pendências para Sprint 7 e Sprint 8
```

---

# 33. Critérios de aceitação

A Sprint 6 está concluída quando:

```text
Existem tipos documentais configuráveis
Existem regras de documentos obrigatórios configuráveis
A checklist documental do candidato existe
A checklist identifica documentos em falta
A checklist identifica documentos submetidos
A checklist identifica documentos em análise
A checklist identifica documentos validados
A checklist identifica documentos rejeitados
O candidato consegue submeter documento
O candidato consegue substituir documento quando permitido
Documentos ficam em storage privado
Download passa por controller autorizado
Candidato não acede a documentos de outro candidato
Estados documentais funcionam
Versões de documento são preservadas
Validação administrativa funciona
Rejeição com motivo funciona
Motivo de rejeição é visível ao candidato
Notas internas não são visíveis ao candidato
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

# 34. Resposta final obrigatória

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
14. Configuração de storage aplicada
15. Seeders/factories criados ou alterados
16. Testes criados ou alterados
17. Resultado dos comandos executados
18. Problemas encontrados
19. Pendências
20. Confirmação de que não foram implementadas funcionalidades fora de âmbito
21. Recomendação objetiva para avançar ou não para Sprint 7/Sprint 8
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 35. Execução imediata

Executa agora apenas:

```text
Sprint 6 — Documentação e Gestão Documental Avançada
```

Usa como referência principal:

```text
docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
```

Fim da master prompt da Sprint 6.
