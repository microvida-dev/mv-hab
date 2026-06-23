# SPRINT PHPSTAN-13 — Public Portal, Reports, Integrations & Stability

**Projeto:** CRM MV HAB
**Framework:** Laravel 13.8+
**PHP:** 8.4
**Data:** 2026-06-23

---

# Objetivo

Concluir a próxima vaga de remediação PHPStan focada em:

- Portal Público
- Relatórios
- Exports
- Dashboards
- Timeline Processual
- Integrações
- Document Intelligence

Esta sprint sucede à PHPSTAN-12 e prepara a entrada na fase crítica:

- Security
- RGPD
- Audit
- Policies

---

# Observação Operacional Obrigatória

## Limitação de Memória do Ambiente

Foi validado que:

```bash
php artisan test
```

falha por:

```text
Allowed memory size of 134217728 bytes exhausted
```

No entanto:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

executa corretamente a suite.

Resultado validado:

```text
280 testes
1758 asserções
OK
```

A partir desta sprint:

```bash
php artisan test
```

não deve ser considerado bloqueador.

O comando de referência passa a ser:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

até correção do ambiente CI/CD.


---

# Fundamentação

A auditoria identificou:

```text
2897 erros
561 ficheiros
```

com forte concentração em:

```text
Reports/Exports
Public Portal
Applications
Documents
Scoring
Services
```

e vários bugs reais prováveis associados a:

- enums incompatíveis
- match.alwaysFalse
- property.nonObject
- method.notFound
- deadCode.unreachable
- argument.type

fileciteturn16file11L29-L66

---

# Domínio 1 — Public Portal

## Âmbito

```text
PublicContestService
PublicHousingSearchService
PublicPortalSettingsService
Public Status
Process Tracking
```

Objetivos:

- missingType.generics
- iterableValue
- return.type
- argument.type

Validar:

- concursos públicos
- filtros
- tipologias
- estado público
- timeline

---

# Domínio 2 — Reports

## Âmbito

```text
DashboardService
ReportRunService
ReportPermissionService
ReportDownloadService
ApplicationReportPayloadBuilder
Operational Reports
```

Prioridade elevada.

Existem erros classificados como críticos:

```text
match.alwaysFalse
property.notFound
argument.type
```

fileciteturn16file11L138-L160

Objetivo:

eliminar falsos positivos e identificar bugs reais.

---

# Domínio 3 — Exports

## Âmbito

```text
CSV
Excel
PDF
Downloads
```

Validar:

- exportações administrativas
- listas
- relatórios
- concursos

Verificar:

```php
FilesystemAdapter::download()
```

sempre com:

```php
abort_unless($path !== null, 404);
```

antes da chamada.

---

# Domínio 4 — Process Tracking

## Âmbito

```text
AdministrativeTimelineService
ProcessTimelineBuilder
ApplicationPublicStatusService
```

Validar:

- timeline administrativa
- histórico processual
- audiência prévia
- reclamações
- listas

---

# Domínio 5 — Document Intelligence

## Âmbito

```text
DocumentAiPipeline
DocumentExtraction*
DocumentAiValidation*
```

Validar:

- extração
- scoring
- validação
- flags

Sem:

- APIs pagas
- dependências externas
- regressões RGPD

---

# Domínio 6 — Controllers de Alto Risco

## Corrigir

### ExecutiveDashboardController

```text
abort_if(bool|null)
```

### OperationalDashboardController

```text
abort_if(bool|null)
```

### ProcessTimelineController

```text
argument.type
```

### ProcedureTemplateController

```text
argument.type
```

Todos identificados na auditoria inicial.

fileciteturn16file11L185-L206

---

# Bugs Prioritários

## ReportPermissionService

Possível bug real:

```text
DashboardType enum vs string
```

Erros:

```text
match.alwaysFalse
```

Validar:

```php
DashboardType::Executive
DashboardType::Financial
DashboardType::Maintenance
```

antes de qualquer correção.

---

## DocumentAiValidationController

```text
property.nonObject
severity|null
```

Garantir:

```php
if ($severity === null) {
    ...
}
```

antes de:

```php
$severity->value
```

---

## PublicPortalSettingsService

Remover verificações impossíveis.

A auditoria aponta:

```text
array_key_exists() sempre true
```

fileciteturn16file11L404-L406

---

# Regras

Não alterar:

- elegibilidade
- pontuação
- contratos
- rendas
- RGPD
- auditoria
- segurança

Proibido:

```text
@phpstan-ignore
baseline
ignoreErrors
mixed para esconder erros
```

---

# Testes Obrigatórios

## Limpeza

```bash
php artisan optimize:clear
```

## Pint

```bash
./vendor/bin/pint --test
```

## PHPUnit Oficial

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

## PHPStan

```bash
./vendor/bin/phpstan analyse --memory-limit=1G
```

---

# Testes Dirigidos

```bash
--filter Public
--filter Contest
--filter Housing
--filter Report
--filter Dashboard
--filter Timeline
--filter DocumentAi
```

---

# Critérios de Sucesso

## Mínimo

```text
-150 erros
```

## Esperado

```text
-250 erros
```

## Excelente

```text
< 500 erros globais
```

---

# Artefactos

```text
docs/qa/phpstan-13-public-portal-reports-integrations.md

storage/phpstan/phpstan-13-before.txt
storage/phpstan/phpstan-13-after-portal.txt
storage/phpstan/phpstan-13-after-reports.txt
storage/phpstan/phpstan-13-after-document-ai.txt
storage/phpstan/phpstan-13-final.txt
```

---

# Próxima Sprint

## PHPSTAN-14 — Security, RGPD, Audit & Policies

Domínios:

- Security
- RGPD
- Audit
- Policies
- MFA
- Consentimentos
- Retenção
- Anonimização
- Exportação de dados
- Auditoria completa

Objetivo:

eliminar os erros classificados como:

```text
Crítico
P0
P1
```

na auditoria estratégica.
