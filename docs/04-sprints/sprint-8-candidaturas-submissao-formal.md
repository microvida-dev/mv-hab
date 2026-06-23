# Sprint 8 — Candidaturas e Submissão Formal

## Prioridade de desenvolvimento

Esta sprint pertence à prioridade funcional:

```text
1. Candidatura
```

A Sprint 8 transforma a preparação feita nas Sprints 3, 4, 5 e 6 num fluxo formal de candidatura a concurso.

Esta sprint depende diretamente de:

```text
Sprint 3 — Portal público e programas
Sprint 4 — Registo de adesão e área pessoal
Sprint 5 — Agregado familiar, rendimentos e situação habitacional
Sprint 6 — Gestão documental avançada
```

A Sprint 8 também deve estar preparada para integração com:

```text
Sprint 7 — Motor de elegibilidade
Sprint 9 — Workflow administrativo e aperfeiçoamento
Sprint 10 — Matriz de classificação e ranking
Sprint 11 — Listas provisórias, reclamações e audiência
Sprint 12 — Atribuição de habitações
```

Se a Sprint 7 ainda não estiver implementada, esta sprint deve criar apenas pontos de integração e validações base, sem implementar o motor completo de elegibilidade.

---

# Objetivo da Sprint

Implementar o módulo de candidaturas formais, permitindo que um candidato autenticado possa:

- Consultar concursos abertos;
- Iniciar candidatura a um concurso;
- Reutilizar dados do Registo de Adesão;
- Confirmar dados pessoais;
- Confirmar agregado familiar;
- Confirmar rendimentos;
- Confirmar situação habitacional;
- Confirmar ou associar documentos;
- Selecionar habitação ou preferências, quando aplicável;
- Declarar compromisso de honra;
- Aceitar termos e tratamento de dados;
- Guardar candidatura como rascunho;
- Ver resumo antes da submissão;
- Submeter formalmente candidatura;
- Receber número de processo;
- Receber comprovativo de submissão;
- Acompanhar estado inicial da candidatura na área pessoal.

Esta sprint deve criar a candidatura formal, mas não deve implementar classificação, ranking, reclamações, listas, atribuição ou contrato.

---

# Instrução operacional para Codex

Executa apenas esta Sprint 8.

Não avances para Sprint 9, Sprint 10, Sprint 11, Sprint 12 ou qualquer sprint futura sem validação explícita.

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

docs/backlog/sprint-3-portal-publico-programas.md
docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/sprint-5-agregado-rendimentos-situacao-habitacional.md
docs/backlog/sprint-6-gestao-documental-avancada.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md

docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Se algum ficheiro de documentação não existir, continua se for tecnicamente possível, mas documenta a ausência na resposta final.

Antes de implementar, confirma que existem ou identifica alternativas para:

```text
Área do candidato
Role candidate
AdhesionRegistration
Household
HouseholdMember
IncomeRecord
CurrentHousingSituation
DocumentSubmission
DocumentChecklistService ou equivalente
Program
Contest
ContestDeadline, se existir
Estados de concurso
Backoffice
Roles e permissões
Auditoria, se existir
Componentes UI da área do candidato
Componentes UI do backoffice
```

Se as Sprints 4, 5 e 6 não estiverem implementadas, interrompe a execução e informa que a Sprint 8 depende do Registo de Adesão, Agregado, Rendimentos, Situação Habitacional e Gestão Documental.

Não duplicar entidades existentes.

Se já existir tabela/model de candidaturas no CRM atual, avaliar se deve ser reaproveitada, migrada ou adaptada com compatibilidade.

Não apagar dados existentes.

---

# Âmbito desta Sprint

## Incluído

Implementar:

```text
Modelo formal de candidatura
Estados da candidatura
Criação de candidatura em rascunho
Resumo de dados antes da submissão
Confirmação de dados do registo de adesão
Confirmação de agregado
Confirmação de rendimentos
Confirmação de situação habitacional
Associação de documentos à candidatura
Checklist mínima para submissão
Seleção de habitação ou preferências, se aplicável ao concurso
Declaração sob compromisso de honra
Consentimentos da candidatura
Submissão formal
Número único de processo
Comprovativo de submissão
Histórico de estado
Bloqueio de edição após submissão
Área “As minhas candidaturas”
Detalhe de candidatura para candidato
Lista inicial de candidaturas no backoffice
Detalhe administrativo da candidatura
Auditoria, se existir
Testes mínimos
Atualização documental
```

## Fora de âmbito

Não implementar nesta sprint:

```text
Motor completo de elegibilidade
Pontuação automática
Matriz de classificação
Ranking
Listas provisórias
Reclamações
Audiência de interessados
Listas definitivas
Atribuição de habitações
Sorteio
Contrato
Cálculo de renda contratual
Pagamentos
Manutenção
Revisão de renda
Notificações reais por email/SMS
Integrações com AT
Integrações com Segurança Social
Integração com Autenticação.GOV
Assinatura digital
Validação administrativa completa
Aperfeiçoamento processual completo
```

Esta sprint pode preparar estados e hooks para essas funcionalidades futuras, mas não deve implementá-las.

---

# Conceito funcional

A candidatura formal representa a participação do candidato num concurso específico.

A candidatura deve ser sempre associada a:

```text
Candidato autenticado
Registo de Adesão
Concurso
Programa
Agregado
Rendimentos declarados
Situação habitacional
Documentos
Declarações e consentimentos
```

O candidato deve conseguir preparar a candidatura como rascunho, rever os dados e submeter apenas quando os requisitos mínimos estiverem completos.

Após submissão formal:

```text
A candidatura recebe número único
O estado muda para submitted
Os dados principais ficam bloqueados para edição direta
O sistema guarda submitted_at
O candidato recebe comprovativo visual/HTML/PDF se possível
A candidatura fica disponível no backoffice para análise futura
```

---

# Estados da candidatura

Criar estados formais:

```text
draft
submitted
under_review
requires_correction
correction_submitted
eligible
ineligible
excluded
cancelled
withdrawn
expired
```

## Significado dos estados

### draft

Candidatura iniciada, mas ainda não submetida.

### submitted

Candidatura formalmente submetida.

### under_review

Candidatura em análise pelos serviços municipais.

### requires_correction

Candidatura com pedido de correção/aperfeiçoamento futuro.

### correction_submitted

Candidato respondeu a pedido de correção futuro.

### eligible

Candidatura considerada elegível em fase futura.

### ineligible

Candidatura considerada não elegível em fase futura.

### excluded

Candidatura excluída administrativamente em fase futura.

### cancelled

Candidatura cancelada administrativamente.

### withdrawn

Candidatura desistida pelo candidato.

### expired

Candidatura expirada por prazo ou validade.

Nesta sprint, implementar funcionalmente apenas:

```text
draft
submitted
withdrawn
cancelled, se necessário para backoffice
```

Os restantes estados devem existir para preparação futura, mas não precisam de workflow completo.

---

# Regras de candidatura

## Concurso aberto

O candidato só pode iniciar candidatura se o concurso estiver em estado:

```text
open
```

ou, se a arquitetura usar apenas published + datas:

```text
published com data atual dentro do período de candidatura
```

Não permitir candidatura a concursos:

```text
draft
closed
under_review
finalized
archived
deleted
```

## Registo de adesão

O candidato só pode iniciar candidatura se tiver Registo de Adesão em estado:

```text
registered
```

Se o registo estiver incompleto, mostrar CTA:

```text
Complete o seu Registo de Adesão antes de iniciar uma candidatura.
```

## Agregado

O candidato deve ter:

```text
Agregado criado
Pelo menos um membro requerente
Dados mínimos dos membros
```

Se faltar informação, mostrar CTA para a área do agregado.

## Rendimentos

O candidato deve ter rendimentos declarados ou declaração explícita de ausência de rendimentos por membro aplicável.

Não implementar cálculo de elegibilidade nesta sprint.

## Situação habitacional

O candidato deve ter situação habitacional atual preenchida.

## Documentos

A candidatura deve verificar a checklist documental da Sprint 6.

Regra recomendada para esta sprint:

```text
Permitir submissão apenas se todos os documentos obrigatórios estiverem submitted ou validated.
```

Documentos em estado:

```text
missing
rejected
expired
cancelled
```

devem bloquear submissão formal, salvo decisão explícita contrária.

## Duplicação de candidatura

Impedir que o mesmo candidato tenha mais de uma candidatura ativa para o mesmo concurso.

Estados considerados ativos:

```text
draft
submitted
under_review
requires_correction
correction_submitted
eligible
```

Permitir nova candidatura apenas se a anterior estiver:

```text
withdrawn
cancelled
expired
excluded
```

Esta regra deve ser testada.

---

# Modelo de dados

## 1. Application

Criar ou adaptar entidade:

```text
Application
```

Tabela recomendada:

```text
applications
```

Se o CRM atual já tiver `housing_applications`, avaliar compatibilidade.

Pode optar por:

```text
Manter housing_applications e adaptar
```

ou criar:

```text
applications
```

A decisão deve ser documentada.

## Campos mínimos

```text
id
application_number

user_id
adhesion_registration_id
program_id
contest_id
household_id
current_housing_situation_id

status

submitted_at
withdrawn_at
cancelled_at
expires_at

declaration_accepted
declaration_accepted_at
data_processing_accepted
data_processing_accepted_at

candidate_notes
internal_notes

created_by
updated_by

created_at
updated_at
deleted_at
```

## Regras

- `application_number` deve ser único.
- `user_id` obrigatório.
- `adhesion_registration_id` obrigatório.
- `program_id` obrigatório.
- `contest_id` obrigatório.
- `household_id` obrigatório.
- `status` default `draft`.
- Usar soft deletes.
- Não permitir mass assignment de `status`, `user_id`, `application_number`.
- Mudanças de estado devem passar por service.

---

## 2. ApplicationStatusHistory

Criar entidade:

```text
ApplicationStatusHistory
```

Tabela:

```text
application_status_histories
```

## Campos mínimos

```text
id
application_id
from_status
to_status
changed_by
reason
created_at
```

## Regras

- Registar sempre que o estado mudar.
- `changed_by` pode ser o utilizador autenticado.
- `reason` opcional, mas recomendado para cancelamento/desistência.
- Não guardar dados sensíveis desnecessários no campo `reason`.

---

## 3. ApplicationSnapshot

Criar entidade:

```text
ApplicationSnapshot
```

Tabela:

```text
application_snapshots
```

## Objetivo

Guardar uma fotografia dos dados principais no momento da submissão, para preservar o conteúdo submetido mesmo que o candidato venha a atualizar posteriormente o registo de adesão.

## Campos mínimos

```text
id
application_id
snapshot_type
data
created_at
updated_at
```

## Valores de `snapshot_type`

```text
adhesion_registration
household
household_members
income_records
current_housing_situation
documents
summary
```

## Regras

- Criar snapshots no momento da submissão.
- Guardar `data` em JSON.
- Não guardar ficheiros no snapshot; apenas metadados documentais.
- Não duplicar conteúdos sensíveis sem necessidade.
- Garantir que o snapshot é acessível apenas por utilizadores autorizados.

---

## 4. ApplicationDocument

Criar entidade pivot:

```text
ApplicationDocument
```

Tabela:

```text
application_documents
```

## Campos mínimos

```text
id
application_id
document_submission_id
document_type_id
is_required
status_at_submission
created_at
updated_at
```

## Objetivo

Associar documentos submetidos à candidatura.

## Regras

- No momento da submissão, associar os documentos relevantes.
- Guardar estado documental no momento da submissão.
- Não mover ficheiros.
- Não duplicar ficheiros.
- Manter ligação ao `DocumentSubmission`.

---

## 5. ApplicationPreference

Criar entidade:

```text
ApplicationPreference
```

Tabela:

```text
application_preferences
```

## Objetivo

Permitir que o candidato indique preferências de habitação quando o concurso permitir.

## Campos mínimos

```text
id
application_id
housing_unit_id
preference_order
notes
created_at
updated_at
```

## Regras

- Opcional nesta sprint.
- Só usar se já existirem habitações associadas ao concurso.
- `preference_order` deve ser único dentro da candidatura.
- Não implementar atribuição nesta sprint.

---

## 6. ApplicationDeclaration

Criar entidade, se for útil separar declarações:

```text
ApplicationDeclaration
```

Tabela:

```text
application_declarations
```

## Campos mínimos

```text
id
application_id
declaration_type
accepted
accepted_at
text_version
created_at
updated_at
```

## Declarações mínimas

```text
Declaração sob compromisso de honra
Aceitação do regulamento/aviso do concurso
Consentimento/tratamento de dados para candidatura
Confirmação de veracidade dos dados
```

Se for preferível manter estes campos diretamente em `applications`, documentar a decisão.

---

# Enums recomendados

Criar, se a versão do PHP permitir:

```text
App\Enums\ApplicationStatus
App\Enums\ApplicationSnapshotType
App\Enums\ApplicationDeclarationType
```

## ApplicationStatus

```text
draft
submitted
under_review
requires_correction
correction_submitted
eligible
ineligible
excluded
cancelled
withdrawn
expired
```

## ApplicationSnapshotType

```text
adhesion_registration
household
household_members
income_records
current_housing_situation
documents
summary
```

## ApplicationDeclarationType

```text
honour_declaration
contest_rules_acceptance
data_processing
truthfulness
```

Se o projeto não suportar enums PHP, usar classes de constantes.

---

# Relações

## User

Adicionar:

```text
User hasMany Application
```

## AdhesionRegistration

Adicionar:

```text
AdhesionRegistration hasMany Application
```

## Program

Adicionar:

```text
Program hasMany Application
```

## Contest

Adicionar:

```text
Contest hasMany Application
```

## Household

Adicionar:

```text
Household hasMany Application
```

## CurrentHousingSituation

Adicionar:

```text
CurrentHousingSituation hasMany Application
```

## Application

Adicionar:

```text
Application belongsTo User
Application belongsTo AdhesionRegistration
Application belongsTo Program
Application belongsTo Contest
Application belongsTo Household
Application belongsTo CurrentHousingSituation
Application hasMany ApplicationStatusHistory
Application hasMany ApplicationSnapshot
Application hasMany ApplicationDocument
Application hasMany ApplicationPreference
Application hasMany ApplicationDeclaration, se existir
Application belongsTo User as createdBy
Application belongsTo User as updatedBy
```

## DocumentSubmission

Adicionar:

```text
DocumentSubmission belongsToMany Application through ApplicationDocument
```

---

# Services

Criar services para evitar lógica pesada em controllers.

## Services recomendados

```text
App\Services\Applications\ApplicationService
App\Services\Applications\ApplicationValidationService
App\Services\Applications\ApplicationSubmissionService
App\Services\Applications\ApplicationNumberService
App\Services\Applications\ApplicationSnapshotService
App\Services\Applications\ApplicationDocumentService
App\Services\Applications\ApplicationPreferenceService
App\Services\Applications\ApplicationReceiptService
```

---

## ApplicationService

Responsável por:

```text
Criar candidatura em rascunho
Atualizar candidatura em rascunho
Obter candidatura ativa do candidato para concurso
Cancelar candidatura em rascunho
Permitir desistência
Bloquear edição após submissão
Controlar transições de estado base
```

---

## ApplicationValidationService

Responsável por validações antes de submissão.

Validações mínimas:

```text
Concurso está aberto
Registo de adesão existe
Registo de adesão está registered
Agregado existe
Existe membro requerente
Rendimentos foram declarados ou marcados como ausência
Situação habitacional existe
Documentos obrigatórios estão submitted ou validated
Declarações obrigatórias foram aceites
Não existe candidatura ativa duplicada para o mesmo concurso
```

Não implementar motor completo de elegibilidade nesta sprint.

Se existir Sprint 7, integrar com método:

```text
EligibilityService::preCheck($application)
```

Se não existir, criar apenas interface/contrato ou comentário documentado, sem falso cálculo de elegibilidade.

---

## ApplicationSubmissionService

Responsável por:

```text
Validar candidatura antes de submissão
Gerar número de candidatura
Criar snapshots
Associar documentos
Guardar declarações
Mudar estado para submitted
Guardar submitted_at
Criar histórico de estado
Criar comprovativo
Auditar submissão, se auditoria existir
```

A submissão deve ocorrer numa transação de base de dados.

Se qualquer passo falhar, a candidatura não deve ficar parcialmente submetida.

---

## ApplicationNumberService

Responsável por gerar número único.

Formato recomendado:

```text
CAND-{ANO}-{CONTEST_REFERENCE}-{SEQUENCIAL}
```

Exemplo:

```text
CAND-2026-AA-001-000123
```

Se não existir referência de concurso:

```text
CAND-{ANO}-{SEQUENCIAL}
```

Regras:

- Número único.
- Sequencial seguro.
- Evitar colisões.
- Testável.

---

## ApplicationSnapshotService

Responsável por:

```text
Capturar dados do Registo de Adesão
Capturar dados do agregado
Capturar membros
Capturar rendimentos
Capturar situação habitacional
Capturar metadados dos documentos
Criar snapshots em JSON
```

Não guardar ficheiros no snapshot.

---

## ApplicationDocumentService

Responsável por:

```text
Identificar documentos relevantes
Validar estados documentais mínimos
Associar documentos à candidatura
Guardar status_at_submission
Listar documentos da candidatura
```

---

## ApplicationPreferenceService

Responsável por:

```text
Guardar preferências de habitação
Ordenar preferências
Validar se habitação pertence ao concurso, se existir relação
Impedir duplicação de ordem
```

Não implementar atribuição.

---

## ApplicationReceiptService

Responsável por:

```text
Gerar comprovativo HTML
Gerar PDF, se o projeto já tiver suporte seguro
Guardar ou disponibilizar comprovativo
Mostrar resumo de submissão
```

Se não existir biblioteca PDF, não instalar sem necessidade. Criar comprovativo HTML imprimível e documentar PDF como pendente.

---

# Controllers

Criar controllers em:

```text
App\Http\Controllers\Candidate
App\Http\Controllers\Backoffice
```

## Área do candidato

Controllers recomendados:

```text
Candidate\ApplicationController
Candidate\ApplicationSubmissionController
Candidate\ApplicationReceiptController
Candidate\ApplicationPreferenceController
```

### Candidate\ApplicationController

Ações:

```text
index
show
create
store
edit
update
withdraw
```

### Candidate\ApplicationSubmissionController

Ações:

```text
review
submit
```

### Candidate\ApplicationReceiptController

Ações:

```text
show
download, se PDF existir
print
```

### Candidate\ApplicationPreferenceController

Ações:

```text
edit
update
```

---

## Backoffice

Criar:

```text
Backoffice\ApplicationController
```

Ações:

```text
index
show
```

Nesta sprint, backoffice deve permitir apenas consulta inicial, não workflow completo.

Pode permitir alteração administrativa básica para `cancelled` apenas se existir autorização clara.

Não implementar análise técnica completa nesta sprint.

---

# Form Requests

Criar Form Requests:

```text
StoreApplicationRequest
UpdateApplicationRequest
SubmitApplicationRequest
WithdrawApplicationRequest
UpdateApplicationPreferencesRequest
```

## StoreApplicationRequest

Validações mínimas:

```text
contest_id required|exists:contests,id
```

Regras adicionais no service:

```text
Concurso aberto
Registo de adesão válido
Sem candidatura ativa duplicada
```

## UpdateApplicationRequest

Permitir apenas campos editáveis em rascunho:

```text
candidate_notes nullable|string|max:3000
```

Não permitir alteração direta de:

```text
user_id
adhesion_registration_id
program_id
contest_id
household_id
status
application_number
submitted_at
```

## SubmitApplicationRequest

Validações:

```text
declaration_accepted accepted
data_processing_accepted accepted
truthfulness_accepted accepted
```

Pode incluir:

```text
confirm_data_is_current accepted
```

## WithdrawApplicationRequest

```text
reason nullable|string|max:2000
```

Só permitir desistência em estados configurados.

## UpdateApplicationPreferencesRequest

```text
preferences array
preferences.*.housing_unit_id required|exists:housing_units,id
preferences.*.preference_order required|integer|min:1
preferences.*.notes nullable|string|max:1000
```

Se não existirem housing units associadas ao concurso, não expor esta funcionalidade.

---

# Policies e autorização

Criar ou atualizar:

```text
ApplicationPolicy
ApplicationStatusHistoryPolicy
ApplicationSnapshotPolicy
ApplicationDocumentPolicy
ApplicationPreferencePolicy
```

## Regras para candidato

- Candidato só vê as suas candidaturas.
- Candidato só cria candidatura para si próprio.
- Candidato só edita candidatura em estado `draft`.
- Candidato só submete candidatura própria em estado `draft`.
- Candidato não altera candidatura submetida diretamente.
- Candidato pode desistir de candidatura própria quando permitido.
- Candidato não vê snapshots de outros candidatos.
- Candidato não vê documentos de outros candidatos.
- Candidato não altera estado manualmente.

## Regras para técnico municipal

- Pode consultar candidaturas no backoffice.
- Pode consultar detalhe da candidatura.
- Não deve implementar análise completa nesta sprint.
- Não deve alterar classificação/elegibilidade nesta sprint.

## Regras para auditor

- Pode consultar dados conforme permissões futuras.
- Acesso a dados sensíveis deve ser restrito e auditado.

## Regras para admin

- Pode consultar candidaturas.
- Pode gerir conforme permissões globais.

---

# Rotas

## Área do candidato

Preferência em português:

```text
GET /area-candidato/candidaturas
GET /area-candidato/candidaturas/criar/{contest}
POST /area-candidato/candidaturas
GET /area-candidato/candidaturas/{application}
GET /area-candidato/candidaturas/{application}/editar
PUT/PATCH /area-candidato/candidaturas/{application}
GET /area-candidato/candidaturas/{application}/rever
POST /area-candidato/candidaturas/{application}/submeter
POST /area-candidato/candidaturas/{application}/desistir
GET /area-candidato/candidaturas/{application}/comprovativo
GET /area-candidato/candidaturas/{application}/imprimir
GET /area-candidato/candidaturas/{application}/preferencias
PUT/PATCH /area-candidato/candidaturas/{application}/preferencias
```

Nomes recomendados:

```text
candidate.applications.index
candidate.applications.create
candidate.applications.store
candidate.applications.show
candidate.applications.edit
candidate.applications.update
candidate.applications.review
candidate.applications.submit
candidate.applications.withdraw
candidate.applications.receipt
candidate.applications.print
candidate.applications.preferences.edit
candidate.applications.preferences.update
```

## Backoffice

```text
GET /backoffice/applications
GET /backoffice/applications/{application}
```

Nomes recomendados:

```text
backoffice.applications.index
backoffice.applications.show
```

## Portal público

Atualizar detalhe de concurso para incluir CTA:

```text
Iniciar candidatura
```

Se o concurso estiver aberto.

Se o utilizador não estiver autenticado, CTA deve encaminhar para login/registo.

Se estiver autenticado como candidato, CTA deve encaminhar para criação de candidatura.

---

# Views / páginas

Se o projeto usa Blade, criar:

## Área do candidato

```text
resources/views/candidate/applications/index.blade.php
resources/views/candidate/applications/create.blade.php
resources/views/candidate/applications/show.blade.php
resources/views/candidate/applications/edit.blade.php
resources/views/candidate/applications/review.blade.php
resources/views/candidate/applications/receipt.blade.php
resources/views/candidate/applications/print.blade.php
resources/views/candidate/applications/preferences.blade.php
```

## Backoffice

```text
resources/views/backoffice/applications/index.blade.php
resources/views/backoffice/applications/show.blade.php
```

Se o projeto usa Inertia/Vue/React, criar equivalentes.

---

# Fluxo do candidato

## 1. Consultar concurso

O candidato consulta o concurso na área pública.

Se concurso estiver aberto, vê CTA:

```text
Iniciar candidatura
```

## 2. Autenticação

Se não estiver autenticado, é encaminhado para login/registo.

## 3. Pré-verificação

Ao iniciar candidatura, o sistema verifica:

```text
Registo de adesão finalizado
Agregado preenchido
Rendimentos preenchidos
Situação habitacional preenchida
Documentos mínimos submetidos/validados
Sem candidatura ativa duplicada
Concurso aberto
```

Se houver bloqueios, mostrar página com checklist de pendências e CTAs para corrigir.

## 4. Criar rascunho

Se a pré-verificação permitir, cria candidatura em `draft`.

## 5. Rever dados

Mostrar resumo:

```text
Dados pessoais
Agregado
Rendimentos
Situação habitacional
Documentos
Preferências, se existirem
Declarações
```

## 6. Aceitar declarações

O candidato deve aceitar:

```text
Declaração sob compromisso de honra
Aceitação do regulamento/aviso do concurso
Consentimento para tratamento de dados
Confirmação de veracidade dos dados
```

## 7. Submeter

Após submissão:

```text
status = submitted
application_number gerado
submitted_at preenchido
snapshots criados
documentos associados
histórico criado
comprovativo disponível
```

## 8. Acompanhar

Na área “As minhas candidaturas”, o candidato vê:

```text
Número de candidatura
Concurso
Estado
Data de submissão
Ações disponíveis
```

---

# Página “As minhas candidaturas”

## Conteúdo obrigatório

Lista de candidaturas do candidato autenticado.

Cada item deve mostrar:

```text
Número
Concurso
Programa
Estado
Data de criação
Data de submissão
Ações
```

## Estados visuais

Usar `status-badge`.

## Empty state

```text
Ainda não iniciou nenhuma candidatura.
Consulte os concursos disponíveis para iniciar uma candidatura.
```

CTA:

```text
Ver concursos disponíveis
```

---

# Página de detalhe da candidatura

## Conteúdo obrigatório

Mostrar:

```text
Número da candidatura
Estado
Concurso
Programa
Data de criação
Data de submissão
Resumo do candidato
Resumo do agregado
Resumo de rendimentos
Resumo da situação habitacional
Documentos associados
Declarações aceites
Histórico de estado
Ações disponíveis
```

## Ações por estado

### draft

```text
Editar
Rever e submeter
Desistir/remover rascunho
```

### submitted

```text
Ver comprovativo
Imprimir comprovativo
Consultar estado
```

### withdrawn

```text
Ver detalhe
```

---

# Página de revisão antes da submissão

## Objetivo

Garantir que o candidato confirma todos os dados antes da submissão.

## Secções obrigatórias

```text
1. Dados do concurso
2. Dados pessoais
3. Agregado familiar
4. Rendimentos
5. Situação habitacional
6. Documentos
7. Preferências, se aplicável
8. Declarações
```

## Aviso obrigatório

```text
Antes de submeter, confirme cuidadosamente todos os dados. Após a submissão, a candidatura ficará bloqueada para edição direta e será analisada pelos serviços municipais.
```

## Declarações obrigatórias

```text
Declaro, sob compromisso de honra, que todas as informações prestadas correspondem à verdade.

Declaro que tomei conhecimento das regras do concurso e do programa aplicável.

Autorizo o tratamento dos dados pessoais necessários à análise e gestão da candidatura.

Confirmo que os dados do meu registo, agregado, rendimentos, situação habitacional e documentos estão corretos e atualizados.
```

---

# Comprovativo de submissão

## Objetivo

Disponibilizar comprovativo ao candidato.

## Conteúdo obrigatório

```text
Título: Comprovativo de Submissão de Candidatura
Número da candidatura
Nome do candidato
Concurso
Programa
Data e hora de submissão
Estado
Resumo dos dados submetidos
Lista de documentos associados
Declarações aceites
Informação de acompanhamento
```

## Copy sugerido

```text
A sua candidatura foi submetida com sucesso. Guarde este comprovativo para referência futura. Poderá acompanhar o estado da candidatura na sua área pessoal.
```

## PDF

Se o projeto já tiver geração PDF segura, gerar PDF.

Se não tiver, criar versão HTML imprimível e documentar PDF como pendência.

Não instalar biblioteca PDF nesta sprint sem necessidade.

---

# Backoffice de candidaturas

## Lista administrativa

Criar listagem simples em:

```text
/backoffice/applications
```

Mostrar:

```text
Número
Candidato
Concurso
Programa
Estado
Data de submissão
Data de criação
Ações
```

## Filtros recomendados

```text
Estado
Concurso
Programa
Data de submissão
Pesquisa por número
```

Filtros são recomendados, mas podem ser simples.

## Detalhe administrativo

Mostrar:

```text
Dados principais
Snapshots
Documentos associados
Histórico de estado
Declarações
Notas internas
```

Não implementar análise técnica completa.

Não implementar pedidos de aperfeiçoamento.

Não implementar elegibilidade final.

---

# Integração com documentos

## Regras

- Ao rever candidatura, mostrar estado documental.
- Documentos obrigatórios em falta devem bloquear submissão.
- Documentos rejeitados devem bloquear submissão.
- Documentos submetidos devem permitir submissão, se a regra definida for `submitted ou validated`.
- Documentos validados devem permitir submissão.
- Associar documentos à candidatura no momento de submissão.
- Guardar `status_at_submission`.

## Se não existir checklist documental

Se Sprint 6 não estiver implementada, interromper a Sprint 8 e informar dependência.

Não criar lógica documental simplificada duplicada.

---

# Integração com elegibilidade

## Se Sprint 7 existir

Integrar pré-check de elegibilidade, se disponível:

```text
EligibilityService::preCheck($application)
```

A submissão pode mostrar aviso se pré-check falhar, conforme regra definida.

## Se Sprint 7 não existir

Não implementar motor de elegibilidade.

Criar apenas ponto de integração documentado:

```text
ApplicationValidationService::runEligibilityPreCheck()
```

Este método pode devolver `not_available` ou ignorar a validação, com comentário técnico/documentação clara.

Não criar falsas decisões de elegibilidade.

---

# Auditoria

Se auditoria existir, auditar:

```text
Criação de candidatura
Atualização de candidatura em rascunho
Submissão de candidatura
Desistência de candidatura
Cancelamento administrativo
Criação de snapshots
Associação de documentos
Geração de comprovativo
Consulta administrativa, se aplicável
```

Não guardar dados excessivos nos logs.

Não guardar ficheiros em logs.

---

# RGPD e segurança

## Regras obrigatórias

- Candidaturas contêm dados sensíveis.
- Candidato só vê as suas candidaturas.
- Candidato não vê candidaturas de outros candidatos.
- Backoffice só acessa conforme permissões.
- Snapshots devem estar protegidos.
- Documentos associados devem manter autorização.
- Não expor dados em URLs públicas.
- Não expor dados sensíveis em logs.
- Não permitir mass assignment de campos críticos.
- Não permitir submissão forjada para outro `user_id`.
- Não permitir alteração direta de `status`.
- Após submissão, bloquear edição direta dos dados da candidatura.
- Manter histórico de estado.
- Usar soft deletes quando aplicável.

## Campos críticos protegidos

```text
user_id
adhesion_registration_id
program_id
contest_id
household_id
status
application_number
submitted_at
created_by
updated_by
```

---

# Seeders e factories

Criar factories:

```text
ApplicationFactory
ApplicationStatusHistoryFactory
ApplicationSnapshotFactory
ApplicationDocumentFactory
ApplicationPreferenceFactory
ApplicationDeclarationFactory, se existir
```

Criar seeder opcional:

```text
ApplicationDemoSeeder
```

Dados demo permitidos:

```text
1 candidatura em rascunho
1 candidatura submetida
```

Usar apenas dados fictícios:

```text
candidato.demo@example.test
CAND-2026-DEMO-000001
```

Não usar dados pessoais reais.

---

# Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso

```text
guest_cannot_access_candidate_applications
non_candidate_cannot_access_candidate_applications
candidate_can_access_own_applications
candidate_cannot_access_another_candidate_application
candidate_can_access_application_detail
```

## Concurso

```text
candidate_can_start_application_for_open_contest
candidate_cannot_start_application_for_draft_contest
candidate_cannot_start_application_for_closed_contest
candidate_cannot_start_application_outside_application_period
public_contest_show_displays_apply_cta_when_open
```

## Registo de adesão

```text
candidate_cannot_start_application_without_adhesion_registration
candidate_cannot_start_application_with_incomplete_registration
candidate_can_start_application_with_registered_adhesion_registration
```

## Agregado, rendimentos e situação habitacional

```text
candidate_cannot_submit_application_without_household
candidate_cannot_submit_application_without_applicant_member
candidate_cannot_submit_application_without_income_information
candidate_cannot_submit_application_without_current_housing_situation
```

## Documentos

```text
candidate_cannot_submit_application_with_missing_required_documents
candidate_cannot_submit_application_with_rejected_required_documents
candidate_can_submit_application_with_submitted_required_documents
candidate_can_submit_application_with_validated_required_documents
submission_associates_documents_to_application
application_document_stores_status_at_submission
```

## Candidatura

```text
candidate_can_create_draft_application
new_application_status_is_draft
candidate_cannot_create_duplicate_active_application_for_same_contest
candidate_can_update_draft_application_notes
candidate_cannot_update_submitted_application
candidate_can_withdraw_draft_application
candidate_can_withdraw_submitted_application_if_allowed
```

## Submissão

```text
candidate_cannot_submit_without_required_declarations
candidate_can_submit_valid_application
submitted_application_status_is_submitted
submitted_application_has_submitted_at
submitted_application_has_unique_application_number
submission_creates_status_history
submission_creates_snapshots
submission_is_atomic
```

## Comprovativo

```text
candidate_can_view_application_receipt_after_submission
candidate_cannot_view_receipt_for_other_candidate_application
receipt_contains_application_number
receipt_contains_submission_date
```

## Backoffice

```text
authorized_technician_can_view_applications_index
authorized_technician_can_view_application_detail
candidate_cannot_access_backoffice_applications
guest_cannot_access_backoffice_applications
```

## Segurança

```text
user_cannot_mass_assign_application_status
user_cannot_mass_assign_application_user_id
user_cannot_mass_assign_application_number
candidate_data_is_not_publicly_exposed
application_snapshots_are_not_accessible_to_other_candidates
```

## Auditoria, se existir

```text
creating_application_generates_audit_log
submitting_application_generates_audit_log
withdrawing_application_generates_audit_log
```

Se auditoria não existir, documentar teste como pendente em vez de criar teste quebrado.

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
docs/backlog/sprint-8-candidaturas-submissao-formal.md
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
Seeders/factories criados
Testes criados
Comandos executados
Pendências para Sprint 7, 9 e 10
```

---

# Critérios de aceitação da Sprint 8

A Sprint 8 está concluída quando:

```text
O candidato consegue ver a área “As minhas candidaturas”
O candidato consegue iniciar candidatura para concurso aberto
O sistema impede candidatura a concurso fechado/rascunho
O sistema impede candidatura sem Registo de Adesão finalizado
O sistema impede candidatura sem agregado mínimo
O sistema impede candidatura sem rendimentos ou declaração equivalente
O sistema impede candidatura sem situação habitacional
O sistema impede candidatura com documentos obrigatórios em falta ou rejeitados
O sistema impede candidatura ativa duplicada ao mesmo concurso
A candidatura é criada em estado draft
O candidato consegue rever dados antes de submeter
O candidato aceita declarações obrigatórias
O candidato consegue submeter candidatura válida
A candidatura submetida recebe número único
A candidatura submetida guarda submitted_at
A candidatura submetida cria histórico de estado
A candidatura submetida cria snapshots
A candidatura submetida associa documentos
A candidatura submetida bloqueia edição direta
O candidato consegue ver comprovativo de submissão
O candidato não consegue ver candidaturas de outros candidatos
O backoffice consegue consultar candidaturas submetidas
Dados sensíveis não são expostos publicamente
Mass assignment de campos críticos está bloqueado
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
Não foi implementado ranking
Não foi implementada classificação
Não foi implementada atribuição
Não foi implementado contrato
```

---

# Resposta final esperada do Codex

No final da execução, responder com:

```text
1. Resumo do que foi implementado na Sprint 8
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
13. Seeders/factories criados ou alterados
14. Testes criados ou alterados
15. Resultado dos comandos executados
16. Problemas encontrados
17. Pendências
18. Confirmação de que não foram implementadas funcionalidades fora de âmbito
19. Recomendação objetiva para avançar ou não para Sprint 7, Sprint 9 ou Sprint 10
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# Execução concluída em 11/06/2026

Implementado:

- candidatura processual separada do CRM legado;
- estados formais, rascunho, submissão, desistência e histórico;
- validação de concurso, adesão, agregado, rendimentos, habitação, duplicado e documentos;
- cinco declarações versionadas;
- número único, associação documental e sete snapshots;
- área do candidato, comprovativo HTML/impressão e backoffice read-only;
- policies, Form Requests, services, auditoria, factories e testes.

Tabelas criadas:

```text
applications
application_status_histories
application_snapshots
application_documents
application_preferences
application_declarations
```

Integração:

- FK adicionada a `document_submissions.application_id`;
- `HousingApplication`/`housing_applications` preservados para compatibilidade;
- preferências sem UI até existir associação processual entre concurso e fogos;
- elegibilidade reportada como indisponível, sem simulação.

Validação:

```text
php artisan migrate: aprovado
php artisan route:list: 11 rotas candidate + 2 backoffice
php artisan test: 78 testes, 455 asserções
npm run build: aprovado
./vendor/bin/pint: aprovado
browser: desktop e 390x844, sem overflow ou erros de consola
```

Pendências:

- Sprint 7: motor de elegibilidade versionado e explicável;
- Sprint 9: aperfeiçoamento, notificações e transições administrativas;
- Sprint 10: classificação e ranking;
- Sprint 18: validação jurídica de declarações e retenção.

# Definition of Done

A Sprint 8 só está concluída quando a plataforma permitir criar, rever e submeter formalmente uma candidatura a concurso aberto, com número único, comprovativo, snapshots, documentos associados, histórico de estado, proteção de dados e bloqueio de edição após submissão.

Fim da Sprint 8.
