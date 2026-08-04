# Auditoria arquitetural — Onboarding Municipal MV-HAB

**Projeto:** MV-HAB
**Bloco:** Onboarding Municipal e catálogo inicial de Alcanena
**Branch auditada:** `feature/mvhab-municipality-onboarding`
**Commit-base:** `e5b2053ff1476061d8a83d1834cd0d3a35a60ce3`
**Tag de referência:** `mvhab-53i-mariadb103-rc5`
**Bundle de auditoria:** `mvhab-onboarding-audit-20260803T234154Z.tar.gz`
**SHA-256 do bundle:** `95a066865e7b7e9655bac3cd8375a702b01b057ba24dc5a7becb08e2261eb22c`
**Data da recolha:** 3 de agosto de 2026, 23:41:54 UTC
**Classificação:** `GO_FOR_IMPLEMENTATION / DEPLOYMENT_GATED`

---

## 1. Resumo executivo

A auditoria confirma que o repositório RC5 possui uma base sólida para implementar o Onboarding Municipal, mas o caso de uso não pode ser composto apenas pelos Services administrativos existentes.

O bloqueio é estrutural e correto:

```text
operador global explícito
        ↓
municipality_id = null
        ↓
AccessMunicipalScopeService falha fechado
        ↓
RoleManagementService e RoleAssignmentService normais
não conseguem criar e atribuir o primeiro perfil municipal
```

Enfraquecer esses Services para aceitar um operador global seria um erro, porque abriria um bypass transversal no modelo permission-first. A solução deve criar uma **fronteira de bootstrap municipal dedicada**, autorizada por `PlatformOperatorScopeService`, limitada a uma única operação inicial e protegida por locks, fingerprints, constraints e auditoria.

Também foi confirmado que:

- o template operacional `tecnico-operacoes-concurso` já existe, está versionado e contém 120 permissions exatas;
- não existe template específico para o primeiro administrador municipal;
- o system role `administrator` contém wildcard e não pode ser usado como perfil municipal inicial;
- o preview normal de templates escreve auditoria e, por isso, não pode sustentar um `--dry-run` de zero escritas;
- o mecanismo atual de password reset é síncrono e não possui lifecycle persistido de convite;
- a infraestrutura RC5 já disponibiliza queue `database`, worker de notificações e `afterCommit`, devendo ser reutilizada;
- o seeder de Alcanena contém identidades oficiais úteis, mas inclui dados de demonstração, publicação imediata, utilizadores demo e valores sem valor jurídico;
- Programa e Concurso devem ser provisionados por um serviço separado, em `draft`, sem regras fictícias, sem entitlements e sem publicação;
- o schema de `contests` exige `opens_at` e `closes_at`, pelo que o catálogo inicial terá de usar datas provisórias explicitamente marcadas ou alterar o schema. A recomendação é **preservar o schema** e usar as datas locais apenas como provisórias enquanto o concurso permanecer em rascunho.

Decisão final:

```text
GO para implementação da infraestrutura e dos comandos.
NO-GO para execução em produção sem dados oficiais, mailer validado,
backup, preview sem conflitos e aprovação humana.
```

---

## 2. Baseline Git e qualidade da recolha

### 2.1 Estado confirmado

A recolha foi executada com as proteções previstas:

```text
ACTUAL_BRANCH=feature/mvhab-municipality-onboarding
ACTUAL_HEAD=e5b2053ff1476061d8a83d1834cd0d3a35a60ce3
WORKING_TREE_CLEAN=true
SEEDERS_EXECUTED=0
MIGRATIONS_EXECUTED=0
DATABASE_WRITES_REQUESTED=0
```

A branch local estava sincronizada com:

```text
origin/feature/mvhab-municipality-onboarding
```

### 2.2 Integridade do bundle

O bundle contém:

- metadata Git;
- inventário de ficheiros;
- pesquisas dirigidas;
- outputs Artisan read-only;
- arquivo integral do código versionado no commit auditado;
- manifesto e checksums.

Todos os checksums internos foram validados sem divergências.

### 2.3 Runtime local observado

O ambiente usado para recolher a auditoria apresentava:

```text
Laravel 13.12.0
PHP 8.4.21
Database mysql
Queue sync
Mail log
Config não cacheada
```

Estes valores descrevem apenas o ambiente local de auditoria. A referência operacional de produção continua a ser a RC5, com PHP 8.4.23, queue `database`, workers, scheduler e caches ativos.

### 2.4 Baseline de rotas

A Route Collection auditada contém:

```text
Rotas totais:                         1199
Rotas nomeadas:                       1196
Nomes duplicados:                        0
Rotas com permission middleware:       937
Rotas com role middleware:             216
Rotas com active.backoffice:           936
Rotas com mfa.backoffice:              936
Rotas com log.backoffice:              936
```

### 2.5 Migrations

O ambiente local reportou as 86 migrations aplicadas e zero pendentes. A migration mais recente é:

```text
2026_08_01_000055_add_template_metadata_to_roles_table
```

O Onboarding Municipal será, portanto, a primeira alteração de schema posterior à RC5.

---

## 3. Inventário dos ficheiros relevantes

### 3.1 Município e utilizador

```text
app/Models/Municipality.php
app/Models/User.php
database/migrations/2026_06_10_000000_create_municipalities_table.php
database/migrations/0001_01_01_000000_create_users_table.php
database/migrations/2026_06_10_000002_add_foundation_fields_to_users_table.php
database/migrations/2026_06_24_000030_create_user_role_competency_management_tables.php
database/factories/MunicipalityFactory.php
database/factories/UserFactory.php
```

### 3.2 Roles, permissions e scope

```text
app/Models/Role.php
app/Models/Permission.php
app/Models/AccessChangeEvent.php
app/Policies/RolePolicy.php
app/Policies/RoleAssignmentPolicy.php
app/Services/Access/AccessMunicipalScopeService.php
app/Services/Access/RoleManagementService.php
app/Services/Access/RoleAssignmentService.php
app/Services/Access/MunicipalRoleTemplateRegistry.php
app/Services/Access/SystemRoleDefinitionRegistry.php
app/Services/Access/PermissionCatalogService.php
app/Services/Access/AccessChangeLogger.php
app/Services/Access/UserAdministrationService.php
```

### 3.3 Operador de plataforma

```text
app/Models/PlatformOperatorAssignment.php
app/Services/Platform/PlatformOperatorScopeService.php
app/Services/Platform/PlatformOperatorManagementService.php
app/Policies/PlatformOperatorAssignmentPolicy.php
app/Console/Commands/BootstrapPlatformOperators.php
database/migrations/2026_07_23_000036_create_platform_operator_assignments_table.php
```

### 3.4 Auditoria

```text
app/Models/AuditEvent.php
app/Services/Audit/AuditLogger.php
app/Services/Audit/AuditTrailService.php
app/Services/Audit/AuditEventFormatter.php
app/Services/Access/AccessChangeLogger.php
app/Http/Middleware/RequestCorrelationId.php
```

### 3.5 MFA, password reset e filas

```text
app/Models/MfaDevice.php
app/Http/Middleware/EnsureBackofficeMfa.php
app/Services/Security/MfaService.php
app/Http/Controllers/Auth/NewPasswordController.php
app/Http/Controllers/Auth/PasswordResetLinkController.php
config/auth.php
config/queue.php
config/mail.php
database/migrations/0001_01_01_000000_create_users_table.php
database/migrations/0001_01_01_000002_create_jobs_table.php
```

### 3.6 Programa, concurso e regulação

```text
app/Models/Program.php
app/Models/Contest.php
app/Enums/ProgramStatus.php
app/Enums/ContestStatus.php
app/Enums/RegulatoryConfigurationStatus.php
app/Services/Programs/ProgramService.php
app/Services/Contests/ContestService.php
app/Services/Regulatory/RegulatorySnapshotService.php
app/Services/Regulatory/RegulatoryPublicationReadinessService.php
database/migrations/2026_06_10_010000_create_programs_table.php
database/migrations/2026_06_10_010001_create_contests_table.php
```

### 3.7 Seeders de Alcanena

```text
database/seeders/DemoAlcanenaAffordableRentSeeder.php
database/seeders/AffordableRentRegulatoryProfileSeeder.php
database/seeders/SystemAccessSeeder.php
```

---

## 4. Arquitetura existente

## 4.1 Município

A tabela `municipalities` possui:

```text
id
name
code                     UNIQUE
tax_number               NULLABLE UNIQUE
contact_email            NULLABLE
settings                 JSON NULLABLE
active                   BOOLEAN INDEX
timestamps
```

Pontos positivos:

- código municipal e NIF/NIPC já possuem proteção de unicidade;
- `settings` permite metadata não estrutural;
- o modelo é pequeno e adequado à criação transacional;
- a relação com utilizadores e programas já existe.

Lacunas:

- `contact_email` não é único;
- não existe normalizador ou Value Object canónico para código, NIF/NIPC e email;
- não existe soft delete, logo não há conflito com registos eliminados logicamente, mas qualquer criação é permanente;
- não existe lifecycle de onboarding.

## 4.2 Utilizador municipal

A tabela `users` já suporta:

```text
municipality_id nullable
status
mfa_required
email_verified_at
password hashed
last_login_at
deactivated/reactivated metadata
```

O model `User` implementa `MustVerifyEmail` e utiliza cast `hashed` para password.

O administrador inicial pode ser criado de forma segura com:

```text
municipality_id = Município novo
status = active
mfa_required = true
password = string aleatória criptograficamente forte
email_verified_at = decisão explícita do onboarding
```

A password aleatória não será uma credencial comunicada. A primeira credencial utilizável deverá ser definida através do convite seguro.

## 4.3 Roles e permissions

O modelo atual é:

```text
User
  ↕ role_user
Role
  ↕ permission_role
Permission
```

As roles podem ser:

- system roles globais;
- roles municipais customizadas;
- roles baseadas em templates versionados.

O schema de `roles` já dispõe de:

```text
municipality_id
template_key
template_version
template_fingerprint
scope
is_system
is_active
```

Existe constraint única:

```text
(municipality_id, template_key)
```

Isto é suficiente para criar um template municipal administrativo idempotente.

## 4.4 Scope global

O `PlatformOperatorScopeService` é a fonte canónica para provar scope global. Exige cumulativamente:

```text
conta ativa
não candidate
municipality_id = null
PlatformOperatorAssignment ativo
```

A ausência de Município não concede, isoladamente, acesso global.

## 4.5 Scope municipal

O `AccessMunicipalScopeService` falha fechado quando o ator tem `municipality_id = null`.

Isto é correto nos fluxos normais, mas impede que o operador global:

- crie uma role municipal através do fluxo normal;
- atribua essa role ao primeiro administrador;
- utilize as Policies municipais como se já pertencesse ao Município.

## 4.6 Entitlements

A tabela `municipality_feature_entitlements` tem unicidade por Município e feature.

A migration histórica ativou as três features para Municípios já existentes quando foi executada:

```text
applications.intake
applications.review
applications.export
```

Esse backfill não se aplica a Municípios criados agora. O onboarding deve criar:

```text
zero registos de entitlement
```

ou, caso se pretenda materializar o estado, três registos com `enabled=false`. A recomendação é **zero registos**, preservando a ausência como fail-closed e evitando escrita desnecessária.

---

## 5. Gaps e conflitos com os Services atuais

## 5.1 `RoleAssignmentService::assign()`

O método normal exige:

```text
RoleAssignmentPolicy::assign
ator diferente do alvo
role ativa
target ativo
sem conflito candidate/auditor
AccessMunicipalScopeService::ownsUser
ator possui todas as permissions da role
```

O ponto bloqueante é:

```php
$this->municipalScope->ownsUser($actor, $target)
```

Como o operador global tem `municipality_id = null`, o scope municipal devolve sempre query vazia.

### Decisão

Não alterar nem flexibilizar `assign()`.

Criar um método dedicado, por exemplo:

```php
PlatformMunicipalRoleAssignmentService::assignInitialAdministrator(...)
```

Esse método deve:

- aceitar apenas onboarding em processamento;
- provar o scope global com `PlatformOperatorScopeService`;
- exigir permission exata do ator;
- exigir role municipal do Município acabado de criar;
- exigir target no mesmo Município;
- rejeitar candidate, auditor e platform operator assignment;
- usar `syncWithoutDetaching` sob lock;
- produzir a mesma auditoria estrutural do serviço normal.

## 5.2 `RoleManagementService::previewTemplate()`

O preview existente:

- deriva o Município do ator;
- exige ator municipal;
- regista `municipal_role_template_previewed`.

Logo, não é read-only.

### Decisão

Separar planeamento de efeito lateral:

```text
MunicipalRoleTemplatePlanner
    resolve
    validate
    diff
    preview puro

RoleManagementService
    auditoria e aplicação administrativa normal
```

O comando de onboarding deverá usar o planner puro.

## 5.3 `RoleManagementService::applyTemplate()`

A aplicação normal exige:

- ator municipal;
- Policy de criação;
- ator com todas as permissions da role a aplicar.

Não pode criar a primeira role municipal através do operador global sem quebrar a separação de scope.

### Decisão

Criar um provisionador inicial limitado ao template `municipal-administrator` e ao Município criado na mesma operação.

## 5.4 `UserAdministrationService::create()`

O serviço normal deriva o Município do ator e executa a criação no contexto municipal. Não é adequado ao bootstrap global.

### Decisão

Reutilizar as regras, mas não o método público existente. Extrair, quando útil:

- normalização de dados;
- geração de password aleatória;
- validação de status;
- auditoria minimizada.

A orquestração deve permanecer no `MunicipalityOnboardingService`.

## 5.5 Password reset atual

O fluxo normal usa o Password Broker, mas a notificação Laravel padrão não é queued e não existe estado persistido de convite.

### Decisão

Criar lifecycle próprio de convite, continuando a usar o broker oficial para emissão e validação do token.

---

## 6. Arquitetura final recomendada

```text
OnboardMunicipalityCommand
        │
        ▼
MunicipalityOnboardingPlanner      read-only
        │
        ├── MunicipalityIdentityNormalizer
        ├── MunicipalAdministratorTemplateRegistry/Registry existente
        ├── PermissionCatalogService
        └── conflito e fingerprint
        │
        ▼ --confirm
MunicipalityOnboardingService
        │
        ├── PlatformOperatorScopeService
        ├── MunicipalAdministratorRoleProvisioningService
        ├── PlatformMunicipalRoleAssignmentService
        ├── AuditTrailService
        ├── AccessChangeLogger adaptado/serviço dedicado
        └── MunicipalAdministratorInvitationService
        │
        ▼
MunicipalityOnboardingRun
Municipality
Role municipal-administrator
User administrador
role_user
MunicipalAdministratorInvitation
        │
        ▼ afterCommit
SendMunicipalAdministratorInvitation Job
        │
        ▼
Password Broker + Notification queued
```

O catálogo inicial deve permanecer separado:

```text
ProvisionInitialMunicipalityCatalogCommand
        │
        ▼
AlcanenaInitialCatalogService
        ├── Program draft
        └── Contest draft com datas provisórias
```

---

## 7. DTOs necessários

## 7.1 `MunicipalityOnboardingData`

```php
final readonly class MunicipalityOnboardingData
{
    public string $name;
    public string $code;
    public string $taxNumber;
    public string $contactEmail;
    public string $adminName;
    public string $adminEmail;
    public string $justification;
    public int $actorId;
}
```

O DTO deve receber dados já normalizados ou normalizá-los num named constructor.

## 7.2 `MunicipalityOnboardingPreview`

Deve conter apenas informação segura:

```text
operation_id efémero
input_fingerprint
municipality_code
role_template_key
role_template_version
role_template_fingerprint
permission_count
mfa_required
conflict_codes
writes=0
entitlements_activated=0
```

Não deve conter:

- nome do administrador;
- emails;
- NIF/NIPC integral;
- password;
- token.

## 7.3 `MunicipalityOnboardingResult`

```text
operation_id
run_id
municipality_id
admin_user_id
role_id
invitation_id
invitation_status
mfa_required
entitlements_activated
idempotent_replay
```

## 7.4 `MunicipalityOnboardingConflict`

Enum ou Value Object com códigos estáveis:

```text
municipality_code_exists
municipality_tax_number_exists
municipality_contact_email_exists
administrator_email_exists
onboarding_already_completed
onboarding_in_progress
role_identifier_reserved
permission_catalog_incomplete
actor_not_global_operator
actor_missing_permission
actor_mfa_not_confirmed
```

## 7.5 Catálogo inicial

```text
InitialMunicipalityCatalogData
InitialMunicipalityCatalogPreview
InitialMunicipalityCatalogResult
```

---

## 8. Models e migrations necessárias

## 8.1 `municipality_onboarding_runs`

Campos recomendados:

```text
id
operation_id UUID UNIQUE
municipality_code VARCHAR UNIQUE
municipality_id NULLABLE UNIQUE FK RESTRICT
actor_id FK RESTRICT
admin_user_id NULLABLE UNIQUE FK RESTRICT
status VARCHAR(40) INDEX
input_fingerprint CHAR(64)
role_template_key VARCHAR(120)
role_template_version VARCHAR(40)
role_template_fingerprint CHAR(64)
attempt_count UNSIGNED SMALLINT DEFAULT 1
failure_code VARCHAR(120) NULLABLE
started_at DATETIME
completed_at DATETIME NULLABLE
failed_at DATETIME NULLABLE
timestamps
```

Não persistir os dados de entrada com PII.

### Justificação da tabela

A tabela permite:

- idempotência;
- bloqueio de segunda execução;
- recuperação de falha;
- correlação operacional;
- auditoria sem reconstruir estados por inferência.

## 8.2 `municipal_administrator_invitations`

Campos recomendados:

```text
id
onboarding_run_id UNIQUE FK RESTRICT
user_id UNIQUE FK RESTRICT
idempotency_key VARCHAR UNIQUE
status VARCHAR(40) INDEX
attempt_count UNSIGNED SMALLINT DEFAULT 0
queued_at DATETIME NULLABLE
sent_at DATETIME NULLABLE
failed_at DATETIME NULLABLE
consumed_at DATETIME NULLABLE
expires_at DATETIME NULLABLE
last_failure_code VARCHAR(120) NULLABLE
timestamps
```

Não guardar:

- token em claro;
- email;
- URL;
- password.

O token continua a ser gerido pelo Password Broker.

## 8.3 Unicidade de `contact_email`

A regra funcional exige impedir duplicação de email institucional. Existem duas opções:

### Opção A — índice único global

```php
$table->unique('contact_email', 'municipalities_contact_email_unique');
```

Vantagem: proteção forte contra concorrência.

Risco: pode falhar em ambientes que já possuam Municípios com email repetido.

### Opção B — hash normalizado no onboarding ledger

Protege apenas onboardings futuros e não torna o domínio municipal globalmente coerente.

### Decisão recomendada

Adotar a Opção A, precedida por teste de duplicados na migration e falha explícita antes de criar o índice. Como produção contém zero Municípios, o risco operacional atual é baixo. A migration não deve eliminar ou corrigir dados automaticamente.

## 8.4 MariaDB 10.3

Usar:

```php
$table->dateTime(...)
```

para datas funcionais.

Evitar:

- `timestampTz`;
- defaults temporais múltiplos incompatíveis;
- índices com comprimento excessivo;
- generated columns dependentes de versões mais recentes.

Todas as migrations devem passar:

```text
up
down
up
```

---

## 9. Matriz de estados

## 9.1 Onboarding run

| Estado | Significado | Transições permitidas |
|---|---|---|
| `processing` | Lock operacional adquirido; operação em curso | `completed`, `failed` |
| `completed` | Município, administrador, role, assignment e intenção de convite persistidos | terminal |
| `failed` | Operação de domínio não concluída | nova tentativa controlada |

Não usar `rolled_back` quando a transação de domínio nunca produziu commit.

## 9.2 Convite

| Estado | Significado | Transições permitidas |
|---|---|---|
| `pending` | Intenção criada na transação | `queued` |
| `queued` | Job aceite pela fila | `sent`, `failed` |
| `sent` | Notification submetida ao transport | `consumed`, `expired`, `failed` operacional |
| `failed` | Falha recuperável de dispatch/transport | `queued` |
| `consumed` | Password definida com sucesso | terminal |
| `expired` | TTL ultrapassado sem consumo | novo convite explícito |

## 9.3 Programa e Concurso

Estados canónicos existentes:

```text
ProgramStatus::Draft
ContestStatus::Draft
```

Não existe `configuration_pending` nos enums. Não criar um estado novo neste bloco.

O estado de incompletude deve ser expresso por:

- ausência de snapshot regulamentar;
- ausência de perfil completo;
- metadata segura em `settings` ou domínio de readiness já existente;
- falha dos serviços de publicação.

---

## 10. Matriz de auditoria

| Evento | Persistência | Ator | Subject/Auditable | Metadata permitida |
|---|---|---|---|---|
| `municipality_onboarding_started` | ledger/audit | operador global | run | operation_id, fingerprint, template |
| `municipality_created` | transação | operador global | Município | IDs, code técnico, active |
| `municipal_administrator_role_created` | transação | operador global | Role | IDs, template, versão, fingerprint, count |
| `municipal_administrator_created` | transação | operador global | User | IDs, Município, MFA=true, status |
| `municipal_administrator_assigned` | transação | operador global | User/Role | IDs e before/after de role IDs |
| `municipal_administrator_invitation_queued` | after commit | sistema/ator | Invitation | IDs, status, attempt |
| `municipal_administrator_invitation_sent` | worker | sistema | Invitation | IDs, timestamps, transport class |
| `municipal_administrator_invitation_failed` | worker | sistema | Invitation | IDs, failure_code, attempt |
| `municipal_administrator_invitation_consumed` | event listener | administrador | Invitation | IDs, consumed_at |
| `municipality_onboarding_completed` | transação | operador global | run | IDs, duration, count |
| `municipality_onboarding_failed` | fora da transação falhada | operador global | run | failure_code, stage |
| `municipality_initial_catalog_created` | catálogo | operador/administrador autorizado | Município | program_id, contest_id, draft=true |

### Proibições

Não incluir:

```text
nome
email
NIF/NIPC
password
token
link de reset
morada
telefone
justificação integral
exception stack com input
```

A justificação deve ser validada, limitada e persistida apenas no audit trail previsto. Nos logs técnicos, usar `operation_id` e `failure_code`.

### Nota sobre falhas

Uma auditoria criada dentro da transação que falha também é revertida. Para conservar evidência da falha:

1. criar/lockar o run numa transação curta;
2. executar a transação de domínio;
3. no `catch`, atualizar o run para `failed` e criar auditoria minimizada numa nova transação.

---

## 11. Matriz de permissions

## 11.1 Permission do comando

A solução mais incremental é reutilizar:

```text
municipalities.create
```

cumulativamente com:

```text
PlatformOperatorScope::Global
MFA configurado/confirmado no dispositivo
conta ativa
--confirm
justificação
```

Criar uma permission nova `municipalities.onboard` exigiria sincronização controlada do catálogo na produção sem voltar a executar `SystemAccessSeeder`. Pode ser feito por migration, mas não acrescenta segurança proporcional neste primeiro bloco.

### Decisão

Usar `municipalities.create` nesta versão e documentar a operação específica no Service e no comando.

## 11.2 Template `municipal-administrator`

Requisitos:

```text
version = 1.0.0
scope = municipal
is_system = false
wildcards = 0
entitlement_dependencies = []
MFA obrigatório
```

O template deve incluir duas camadas.

### Camada A — administração municipal

```text
dashboard.view
security.manage_own_mfa
users.view
users.create
users.update
users.disable
users.reset_password
users.sessions.revoke
users.force_mfa
users.audit
roles.view
roles.create
roles.update
roles.delete
roles.assign
roles.audit
teams.view
teams.create
teams.update
teams.manage_members
teams.audit
access_audit.view
access_audit.export
access_audit.audit
security.view
security.update
security.resolve
security.approve
security.view_access_logs
security.revoke_sessions
security.audit_sensitive_access
permission_reviews.view
permission_reviews.create
permission_reviews.update
permission_reviews.complete
permission_reviews.audit
municipalities.view
municipalities.update
municipalities.audit
```

Os nomes finais devem ser reconciliados com o catálogo real; não criar aliases silenciosos.

### Camada B — delegação do template técnico

O `RoleManagementService` impede que um ator aplique uma role com permissions que não possui. Para o administrador municipal conseguir aplicar `tecnico-operacoes-concurso`, deve possuir as 120 permissions exatas desse template.

A definição pode ser composta no registry por uma constante partilhada, mas o resultado resolvido e o fingerprint devem conter a lista plana e determinística.

### Exclusões obrigatórias

```text
*
platform_operators.*
municipality_features.*
qualquer permission global de plataforma
bypass de MFA
privacy/rgpd sensível não necessário
finance/contracts/payments não necessários ao arranque
```

A ativação de entitlements continuará reservada ao operador global através da Policy atual.

## 11.3 Primeiro administrador e entitlements

O administrador municipal não deverá receber:

```text
PlatformOperatorAssignment
permissions diretas
wildcard
municipality_features.manage
```

A ativação posterior de:

```text
applications.intake
applications.review
applications.export
```

será executada separadamente pelo operador global.

---

## 12. Matriz dos dados extraídos do seeder de Alcanena

## 12.1 Dados reutilizáveis

| Entidade | Campo | Valor |
|---|---|---|
| Município | code | `ALCANENA` |
| Município | name | `Município de Alcanena` |
| Programa | slug | `programa-municipal-arrendamento-acessivel-alcanena` |
| Programa | name | `Programa Municipal de Arrendamento Acessível de Alcanena` |
| Programa | legal_basis | Regulamento Municipal de Arrendamento Acessível — Edital n.º 1820/2024, sujeito a revisão final |
| Concurso | code | `ALC-RAA-01-2026` |
| Concurso | slug | `concurso-01-2026-arrendamento-municipal-acessivel-alcanena` |
| Concurso | title | `Concurso n.º 01/2026 — Arrendamento Municipal Acessível de Alcanena` |

## 12.2 Dados provisórios que exigem validação oficial

```text
program starts_at = 2026-01-01
contest opens_at = 2026-06-01 09:00 Europe/Lisbon
contest closes_at = 2026-12-31 17:00 Europe/Lisbon
summary e description finais
application_instructions
perfil e regime regulamentar
limites de rendimento
rendas
fogos
regras de elegibilidade
scoring
júri
deadlines
documentos obrigatórios
minutas e templates
```

### Decisão sobre datas

Como `contests.opens_at` e `contests.closes_at` são NOT NULL, o catálogo inicial deve usar as datas locais como **provisórias**, mantendo:

```text
status = draft
published_at = null
regulatory_profile_id = null
regulatory_snapshot_id = null
```

A metadata de provenance deve indicar:

```text
source = local_demo_seeder
validation_status = pending_official_confirmation
publication_blocked = true
```

Não tornar as colunas nullable neste bloco, evitando uma alteração transversal ao domínio temporal de concursos.

## 12.3 Dados proibidos em produção

```text
MVHAB_REGULATORY_DEMO_MODE=true
execução de DemoAlcanenaAffordableRentSeeder
execução de SystemAccessSeeder
admin-demo@exemplo.pt
tecnicos ou júri demo
passwords demo
emails example.test
profiles DEMO
DEMO-IRS-2026-SEM-VALOR-JURIDICO
sixth_irs_bracket_upper_limit=999999
metadata demo_only
ProgramStatus::Published
ContestStatus::Published
published_at demo
fogos demo
rendas demo
scoring demo
snapshots demo
```

---

## 13. Desenho transacional

## 13.1 Preview

```text
normalizar input
resolver ator
provar global scope
resolver template
calcular fingerprint
consultar conflitos
construir preview seguro
zero writes
```

## 13.2 Onboarding confirmado

### Transação curta de aquisição

```text
lock/create MunicipalityOnboardingRun by municipality_code
validar estado
incrementar attempt
```

### Transação principal

```text
lock run
revalidar actor/global scope
revalidar conflitos
criar Municipality
criar role municipal-administrator
sincronizar permissions exatas
criar User administrador
atribuir role
criar Invitation pending
criar audit events essenciais
marcar run completed
commit
```

### Após commit

```text
dispatch SendMunicipalAdministratorInvitation
queue=notifications
afterCommit=true
```

## 13.3 Catálogo inicial

Transação separada:

```text
lock Municipality
find/create Program by slug
find/create Contest by code/slug
preservar draft
published_at null
sem regras, snapshots ou entitlements
criar auditoria
commit
```

Uma falha no catálogo não deve remover o Município nem o administrador.

---

## 14. Concorrência e idempotência

### 14.1 Constraints

A proteção deve combinar:

```text
municipalities.code UNIQUE
municipalities.tax_number UNIQUE
municipalities.contact_email UNIQUE novo
users.email UNIQUE
roles(municipality_id, template_key) UNIQUE
municipality_onboarding_runs.operation_id UNIQUE
municipality_onboarding_runs.municipality_code UNIQUE
municipality_onboarding_runs.municipality_id UNIQUE
invitations.onboarding_run_id UNIQUE
invitations.user_id UNIQUE
invitations.idempotency_key UNIQUE
role_user PK
```

### 14.2 Locks

Aplicar `lockForUpdate()` a:

- onboarding run;
- Município quando já criado numa repetição;
- role template do Município;
- utilizador administrador;
- convite.

### 14.3 Reexecução

| Situação | Resultado |
|---|---|
| Mesmo fingerprint e run `completed` | retorno idempotente do resultado existente |
| Dados diferentes para o mesmo code | conflito explícito, zero writes |
| Run `processing` recente | recusa `onboarding_in_progress` |
| Run `processing` stale | recuperação apenas por comando/runbook específico |
| Run `failed` | nova tentativa controlada, sem duplicar domínio |
| Convite `failed` | retry do convite, sem repetir onboarding |
| Convite `sent` não consumido | reenvio explícito cria novo token, mesma conta |
| Convite `consumed` | reenvio bloqueado por defeito |

### 14.4 Teste concorrente esperado

Duas ligações MySQL/MariaDB independentes devem produzir:

```text
1 Municipality
1 onboarding completed
1 municipal admin role
1 administrator user
1 role assignment
1 invitation
0 entitlements
0 duplicates
```

---

## 15. Convite seguro após commit

## 15.1 Job

```php
final class SendMunicipalAdministratorInvitation implements ShouldQueue, ShouldBeUnique
```

Configuração recomendada:

```text
queue = notifications
uniqueId = municipal-administrator-invitation:<invitation_id>
timeout = 60
tries = 5
backoff = [60, 300, 900, 1800]
```

O Job recebe apenas `invitation_id`.

## 15.2 Emissão do token

Sob lock:

1. validar onboarding `completed`;
2. validar utilizador ativo e municipal;
3. validar convite não consumido/expirado;
4. gerar token através do Password Broker;
5. enviar Notification queued;
6. marcar `sent` apenas quando o dispatch/transport não lançar exceção;
7. nunca persistir token em claro.

## 15.3 Consumo

Escutar o evento oficial `PasswordReset` ou chamar um serviço dedicado após reset concluído para marcar o convite `consumed`.

A associação deve ser feita por `user_id`, não pelo token.

## 15.4 Email verification

Para o primeiro administrador criado por um operador global com email institucional validado, recomenda-se:

```text
email_verified_at = now()
```

Isto evita exigir dois links paralelos. A decisão deve ser auditada como verificação administrativa, não como auto-verificação silenciosa.

## 15.5 Falha do mailer

Uma falha de email após commit resulta em:

```text
Município preservado
administrador preservado
role preservada
invitation.status = failed
retry disponível
```

O runbook deve incluir teste real de entrega antes da execução de produção.

---

## 16. Contrato do comando

## 16.1 Comando principal

```bash
php artisan mvhab:municipality:onboard
```

Opções finais recomendadas:

```text
--actor-id=                obrigatório
--name=
--code=
--tax-number=
--contact-email=
--admin-name=
--admin-email=
--justification=
--dry-run
--confirm
```

`--actor-id` é indispensável porque um comando CLI não possui sessão autenticada. Não selecionar o ator por email.

## 16.2 Comportamento

### Sem `--confirm`

Preview puro.

### Com `--dry-run`

Preview puro e zero escrita, mesmo que `--confirm` seja fornecido. O comando deve rejeitar a combinação ambígua ou dar precedência explícita ao dry-run.

### Com `--confirm`

Executa apenas se:

- todos os argumentos existirem;
- ator for operador global ativo;
- ator possuir `municipalities.create`;
- ator tiver MFA configurado e requerido;
- preview não tiver conflitos;
- fingerprint entre preview interno e execução coincidir.

## 16.3 Exit codes

```text
0  sucesso/preview sem conflitos
10 input inválido
11 ator inválido ou não autorizado
12 MFA não confirmado/configurado
20 conflito de identidade
21 onboarding já concluído com dados divergentes
22 onboarding em curso
30 falha transacional
31 falha de auditoria crítica
40 convite criado mas dispatch inicial falhou
50 dependência/configuração ausente
```

O exit code 40 não reverte o onboarding; sinaliza recuperação necessária.

## 16.4 Output

```text
MUNICIPALITY_ONBOARDING=PASS|PREVIEW|FAILED
OPERATION_ID=<uuid>
MUNICIPALITY_CODE=ALCANENA
ROLE_TEMPLATE=municipal-administrator
ROLE_VERSION=1.0.0
ROLE_PERMISSION_COUNT=<n>
MFA_REQUIRED=true
INVITATION_STATUS=pending|queued|sent|failed
ENTITLEMENTS_ACTIVATED=0
CONFLICTS=0
```

Sem PII.

---

## 17. Serviço de catálogo inicial de Alcanena

## 17.1 Comando

```bash
php artisan mvhab:municipality:provision-initial-catalog \
  --actor-id=<id> \
  --municipality=ALCANENA \
  --profile=alcanena-2026 \
  --dry-run
```

Execução:

```bash
php artisan mvhab:municipality:provision-initial-catalog \
  --actor-id=<id> \
  --municipality=ALCANENA \
  --profile=alcanena-2026 \
  --confirm
```

## 17.2 Resultado

```text
1 Program draft
1 Contest draft
0 rules
0 deadlines
0 jury members
0 housing units
0 regulatory snapshots
0 entitlements
0 notifications
0 demo users
```

## 17.3 Idempotência

Chaves:

```text
Program.slug
Contest.code
Contest.slug
```

Uma segunda execução com o mesmo fingerprint retorna o resultado existente. Divergência produz conflito e não atualiza silenciosamente.

---

## 18. Plano de testes

## 18.1 Unitários

```text
MunicipalityIdentityNormalizerTest
PortugueseTaxNumberTest
MunicipalityOnboardingFingerprintTest
MunicipalityOnboardingPlannerTest
MunicipalAdministratorTemplateTest
MunicipalityOnboardingAuditMetadataTest
AlcanenaInitialCatalogDefinitionTest
```

Cobrir:

- uppercase/trim do código;
- NIF/NIPC com checksum;
- lowercase de emails;
- fingerprint determinístico;
- ausência de PII;
- ausência de wildcard;
- 120 permissions técnicas incluídas na role administrativa;
- zero entitlements.

## 18.2 Feature — comando

```text
MunicipalityOnboardingCommandTest
InitialMunicipalityCatalogCommandTest
```

Cenários:

- branch funcional independente do ambiente;
- sem confirm;
- dry-run;
- confirm;
- input em falta;
- ator inexistente;
- ator municipal;
- ator sem assignment global;
- ator sem permission;
- ator sem MFA;
- duplicação de code;
- duplicação de NIF/NIPC;
- duplicação de contact email;
- duplicação de admin email;
- segunda execução igual;
- segunda execução divergente.

## 18.3 Transação e falhas injetadas

Falhar em:

```text
Municipality create
Role create
Permission sync
User create
Role assignment
Invitation create
Audit event
Run completion
```

Confirmar rollback integral do domínio.

## 18.4 Convite

```text
MunicipalAdministratorInvitationTest
MunicipalAdministratorInvitationRetryTest
MunicipalAdministratorInvitationConsumptionTest
```

Confirmar:

- dispatch afterCommit;
- nenhuma fila após rollback;
- unique Job;
- token temporário;
- uso único;
- expiração;
- consumo após reset;
- retry não cria utilizador;
- token/password nunca aparecem em output ou audit.

## 18.5 Concorrência real

```text
MunicipalityOnboardingMysqlConcurrencyTest
MunicipalAdministratorInvitationMysqlConcurrencyTest
InitialMunicipalityCatalogMysqlConcurrencyTest
```

Usar processos ou ligações independentes.

## 18.6 Segurança

Confirmar:

```text
MFA required
sem wildcard
sem permissions diretas
sem PlatformOperatorAssignment no admin
operador global inalterado
scope municipal correto
outro Município recusado
candidate recusado
auditor sem mutação
entitlements zero
PII ausente de logs/audit/output
```

## 18.7 Migrations e gates

```text
migrate
rollback
migrate
PHPUnit dirigido
PHPUnit completo
UX
PHPStan nível atual
Pint global
Composer validate/audit
Vite build
route audit
access audit
integridade dos testes
git diff --check
```

---

## 19. Lista integral de ficheiros a criar ou alterar

A lista final poderá ajustar nomes sem alterar responsabilidades.

### Criar

```text
app/Console/Commands/OnboardMunicipality.php
app/Console/Commands/ProvisionInitialMunicipalityCatalog.php
app/Data/Municipalities/MunicipalityOnboardingData.php
app/Data/Municipalities/MunicipalityOnboardingPreview.php
app/Data/Municipalities/MunicipalityOnboardingResult.php
app/Data/Municipalities/InitialMunicipalityCatalogData.php
app/Data/Municipalities/InitialMunicipalityCatalogPreview.php
app/Data/Municipalities/InitialMunicipalityCatalogResult.php
app/Enums/MunicipalityOnboardingStatus.php
app/Enums/MunicipalAdministratorInvitationStatus.php
app/Jobs/SendMunicipalAdministratorInvitation.php
app/Models/MunicipalityOnboardingRun.php
app/Models/MunicipalAdministratorInvitation.php
app/Notifications/MunicipalAdministratorInvitationNotification.php
app/Services/Municipalities/MunicipalityIdentityNormalizer.php
app/Services/Municipalities/MunicipalityOnboardingPlanner.php
app/Services/Municipalities/MunicipalityOnboardingService.php
app/Services/Municipalities/MunicipalAdministratorRoleProvisioningService.php
app/Services/Municipalities/PlatformMunicipalRoleAssignmentService.php
app/Services/Municipalities/MunicipalAdministratorInvitationService.php
app/Services/Municipalities/AlcanenaInitialCatalogService.php
app/Listeners/MarkMunicipalAdministratorInvitationConsumed.php
database/migrations/<timestamp>_create_municipality_onboarding_tables.php
database/migrations/<timestamp>_add_unique_contact_email_to_municipalities.php
tests/Feature/Municipalities/MunicipalityOnboardingCommandTest.php
tests/Feature/Municipalities/MunicipalityOnboardingRollbackTest.php
tests/Feature/Municipalities/MunicipalityOnboardingSecurityTest.php
tests/Feature/Municipalities/MunicipalAdministratorInvitationTest.php
tests/Feature/Municipalities/InitialMunicipalityCatalogCommandTest.php
tests/Integration/Mysql/MunicipalityOnboardingConcurrencyTest.php
tests/Integration/Mysql/InitialMunicipalityCatalogConcurrencyTest.php
tests/Unit/Municipalities/MunicipalityIdentityNormalizerTest.php
tests/Unit/Municipalities/MunicipalityOnboardingPlannerTest.php
tests/Unit/Access/MunicipalAdministratorTemplateTest.php
docs/operations/municipality-onboarding.md
docs/security/municipality-onboarding-threat-model.md
docs/runbooks/municipality-onboarding-production.md
docs/runbooks/municipality-onboarding-recovery.md
```

### Alterar

```text
app/Models/Municipality.php
app/Models/User.php
app/Services/Access/MunicipalRoleTemplateRegistry.php
app/Services/Access/AccessChangeLogger.php ou adapter dedicado
app/Providers/AppServiceProvider.php, apenas se necessário para listener
config/mvhab.php
```

### Não alterar

```text
RoleAssignmentService::assign() — preservar fronteira municipal normal
AccessMunicipalScopeService — preservar fail-closed
PlatformOperatorScopeService — reutilizar sem bypass
SystemAccessSeeder — não executar nem converter em bootstrap
DemoAlcanenaAffordableRentSeeder — preservar como demo
DatabaseSeeder — não executar
```

---

## 20. Riscos e bloqueios reais

| Risco | Impacto | Mitigação |
|---|---|---|
| Aplicar role normal com operador global | bypass de scope | serviço dedicado de bootstrap |
| Reutilizar preview com auditoria | dry-run deixa de ser read-only | planner puro |
| Usar system `administrator` | wildcard municipal | template exato novo |
| Admin não possuir permissions do técnico | não consegue aplicar template | incluir conjunto exato das 120 permissions |
| Email enviado dentro da transação | efeitos externos não reversíveis | outbox/afterCommit |
| Falha após envio antes de marcar estado | duplicação em retry | Job único + estado + idempotency key |
| Contact email sem unique | corrida | migration fail-closed para unique |
| Contest dates NOT NULL | catálogo incompleto impossível | datas provisórias + draft |
| Dados demo confundidos com oficiais | publicação juridicamente incorreta | whitelist e publicação bloqueada |
| Auditoria de falha revertida | perda de evidência | run ledger + catch externo |
| CLI sem ator autenticado | auditoria e autorização ambíguas | `--actor-id` obrigatório |
| Mailer não validado | admin sem convite | gate de produção e retry |
| Rollback de código após novos dados | incompatibilidade | forward fix preferencial e matriz de rollback |

### Dados ainda necessários para produção

```text
NIF/NIPC oficial do Município
email institucional do Município
nome e email do primeiro administrador
justificação aprovada
confirmação oficial das datas do Concurso
mailer real e entrega testada
```

Estes dados não bloqueiam a implementação, apenas a execução real.

---

## 21. Decisão GO/NO-GO

### GO

Autoriza-se a implementação de:

- migrations de onboarding;
- template administrativo exato;
- planner read-only;
- Service transacional;
- comando com `--actor-id`, preview, dry-run e confirm;
- lifecycle de convite queued after commit;
- auditoria minimizada;
- testes unitários, feature e concorrência;
- serviço separado de catálogo inicial de Alcanena;
- Programa e Concurso em `draft`.

### NO-GO

Continua proibido:

- executar `DatabaseSeeder`;
- executar novamente `SystemAccessSeeder`;
- executar `DemoAlcanenaAffordableRentSeeder` em produção;
- usar role `administrator` wildcard no Município;
- atribuir permissions diretamente ao administrador;
- atribuir Município ao operador global;
- ativar entitlements no onboarding;
- publicar Programa ou Concurso;
- criar utilizadores, fogos, júri, scoring ou regras demo;
- executar produção sem backup, mailer, preview e aprovação.

### Classificação

```text
ARCHITECTURE_AUDIT=PASS
IMPLEMENTATION_DECISION=GO
PRODUCTION_EXECUTION=DEPLOYMENT_GATED
```

---

## 22. Próximo macrobloco recomendado

Executar uma implementação integral, sem fragmentação por ficheiro, composta por:

```text
1. migrations e models de lifecycle
2. normalização, planner e DTOs
3. template municipal-administrator
4. provisionamento e assignment dedicados
5. Service transacional
6. comando operacional
7. convite queued afterCommit
8. catálogo inicial de Alcanena draft
9. testes completos
10. documentação e script consolidado de validação
```

A suite integral deverá ser executada apenas depois de estabilizados os testes dirigidos e de concorrência.
