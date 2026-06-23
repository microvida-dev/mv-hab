# SPRINT PHPSTAN-15 — Security, RGPD, Audit & Policies Final Hardening

**Projeto:** CRM MV HAB  
**Framework:** Laravel 13.8+  
**PHP:** 8.4  
**Data:** 2026-06-23  
**Âmbito:** Segurança, RGPD, auditoria, policies, permissões, MFA, consentimentos, retenção, anonimização e acessos sensíveis.

---

## 1. Objetivo

Executar uma remediação PHPStan conservadora, auditável e sem regressões nos domínios críticos remanescentes:

- Security
- RGPD
- Audit
- Policies
- MFA
- Access Logs
- Sensitive Data Access
- Consentimentos
- Retenção
- Anonimização
- Exportação de dados pessoais
- Pedidos dos titulares
- Permissões e ownership

Esta sprint sucede à PHPSTAN-14, que reduziu os erros globais de `708` para `544`, com `exact_new = 0`, preservando scoring, elegibilidade, ranking, sorteios, concursos, contratos, rendas, RGPD, auditoria, policies e permissões sem alterações funcionais.

---

## 2. Estado de Partida

Após PHPSTAN-14:

| Métrica | Valor |
| --- | ---: |
| Erros PHPStan globais | 544 |
| Ficheiros com erros | 208 |
| Redução PHPSTAN-14 | -164 |
| Erros removidos por assinatura exata | 164 |
| Erros novos por assinatura exata | 0 |
| PHPUnit direto com memory_limit=-1 | OK — 283 testes / 1775 asserções |
| Pint | OK |
| Route list | OK — 1083 rotas |

Principais identificadores remanescentes esperados:

| Identificador | Risco |
| --- | --- |
| `missingType.generics` | Médio |
| `missingType.iterableValue` | Médio |
| `argument.type` | Alto |
| `property.nonObject` | Alto |
| `method.nonObject` | Alto |
| `property.notFound` | Alto |
| `return.type` | Médio/Alto |
| `identical.alwaysFalse` | Alto |
| `notIdentical.alwaysTrue` | Alto |
| `deadCode.unreachable` | Alto |

---

## 3. Observação Operacional Obrigatória

`php artisan test` continua não recomendado neste ambiente porque o processo filho fica limitado a `128MB`.

A validação funcional oficial deve usar:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

O comando `php artisan test` pode ser registado como risco operacional, mas não deve bloquear a sprint enquanto o PHPUnit direto com `memory_limit=-1` passar.

---

## 4. Regras Absolutas

### 4.1. Não alterar segurança funcional

É proibido alterar:

- permissões efetivas;
- regras de ownership;
- gates;
- policies;
- roles;
- MFA;
- autenticação;
- autorização;
- escopo municipal;
- acesso a documentos privados;
- acesso a dados sensíveis;
- regras de exportação RGPD;
- regras de anonimização;
- regras de retenção;
- logs de auditoria;
- trilho processual;
- logging de acessos sensíveis.

### 4.2. Não alterar regras de negócio

É proibido alterar:

- elegibilidade;
- pontuação;
- ranking;
- sorteios;
- concursos;
- listas;
- contratos;
- rendas;
- manutenção;
- vistorias;
- notificações legais;
- estados administrativos;
- workflows de candidato.

### 4.3. Não silenciar PHPStan

É proibido:

- criar baseline;
- alterar baseline;
- adicionar `ignoreErrors`;
- usar `@phpstan-ignore-line`;
- usar `@phpstan-ignore-next-line`;
- usar `mixed` para esconder erro;
- alargar contratos de tipo artificialmente;
- alterar migrations;
- alterar seeders;
- alterar dependências.

### 4.4. Política obrigatória

Manter:

```text
exact_new = 0
```

Qualquer erro novo por assinatura exata deve ser corrigido no mesmo lote ou revertido.

---

## 5. Âmbito Prioritário

Atuar por ordem estrita:

1. Policies críticas.
2. Security services.
3. RGPD services.
4. Audit services.
5. Access logs e sensitive data access.
6. MFA e recovery codes.
7. Consentimentos.
8. Retenção/anonimização.
9. Controllers RGPD/Security se ainda tiverem dívida residual.
10. Correções adjacentes mínimas para manter `exact_new = 0`.

---

## 6. Fase 1 — Baseline Local da Sprint

Executar:

```bash
php artisan optimize:clear

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-15-before.txt
```

Registar:

- total global de erros;
- ficheiros afetados;
- distribuição por identificador;
- erros por domínio;
- erros exatos da baseline;
- top ficheiros Security/RGPD/Audit/Policies.

---

## 7. Fase 2 — Policies

### Diretórios

```text
app/Policies/*
```

### Objetivos

- Corrigir generics e return types.
- Corrigir `argument.type`.
- Corrigir `property.nonObject` e `method.nonObject` com guards conservadores.
- Validar ownership antes de acesso a dados.
- Garantir que nenhuma policy alarga permissões.

### Proibido

- substituir `false` por `true`;
- remover verificações de ownership;
- remover escopo municipal;
- remover checks de role;
- alterar permissões efetivas;
- flexibilizar documentos privados;
- alterar regras de acesso de candidato/inquilino/técnico.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Policy
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Candidate
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Document
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security
```

Se `--filter Policy` não encontrar testes, documentar a lacuna e executar testes funcionais que cobrem policies indiretamente.

---

## 8. Fase 3 — Security Services

### Diretórios

```text
app/Services/Security/*
```

### Ficheiros prováveis

```text
AccessLogService
SecurityAlertService
SensitiveDataAccessService
MfaDeviceService
PasswordPolicyService
BackupReviewService
DocumentStorageSecurityReviewService
SensitiveFieldEncryptionReviewService
```

### Objetivos

- Corrigir payloads `array<string, mixed>`.
- Corrigir nullability de actor/subject.
- Corrigir enum/string mismatch.
- Validar MFA e recovery codes sem reduzir segurança.
- Garantir logs consistentes.

### Proibido

- enfraquecer MFA;
- alterar hashing de recovery codes;
- alterar autenticação;
- reduzir logging;
- ignorar falhas de segurança;
- alterar regras de password sem requisito;
- remover alertas.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Mfa
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter AccessLog
```

---

## 9. Fase 4 — RGPD Services

### Diretórios

```text
app/Services/Rgpd/*
```

### Ficheiros prováveis

```text
DataSubjectRequestService
DataExportService
DataInventoryService
UserConsentService
ConsentPurposeService
RetentionPolicyService
RetentionExecutionService
AnonymizationService
DataSubjectRequestWorkflowService
```

### Objetivos

- Corrigir arrays sem shape.
- Corrigir `argument.type` com `User|null`.
- Corrigir `property.nonObject`.
- Corrigir enum/string mismatch.
- Corrigir `return.type`.
- Garantir que exportações, anonimização e retenção continuam auditáveis.

### Proibido

- alterar prazos RGPD;
- alterar titularidade;
- alterar scope de dados exportados;
- reduzir auditoria;
- alterar anonimização funcional;
- alterar consentimentos obrigatórios;
- alterar retenção legal;
- alterar permissões de backoffice.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Privacy
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Consent
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Export
```

---

## 10. Fase 5 — Audit Services

### Diretórios

```text
app/Services/Audit/*
app/Models/Audit*
app/Models/AccessLog.php
app/Models/SensitiveDataAccessLog.php
```

### Objetivos

- Tipar payloads de auditoria.
- Corrigir actor/subject nullable.
- Tipar relações Eloquent.
- Garantir integridade de logs.
- Corrigir `missingType.iterableValue`.
- Corrigir `argument.type`.

### Proibido

- remover logs;
- reduzir metadados;
- alterar eventos críticos;
- alterar retenção de auditoria;
- alterar formato funcional de trilho processual;
- alterar identificação de actor/subject.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Audit
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Access
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Sensitive
```

---

## 11. Fase 6 — Models de Segurança/RGPD/Auditoria

### Ficheiros prováveis

```text
app/Models/AccessLog.php
app/Models/SensitiveDataAccessLog.php
app/Models/DataSubjectRequest.php
app/Models/DataExportPackage.php
app/Models/UserConsent.php
app/Models/ConsentPurpose.php
app/Models/RetentionPolicy.php
app/Models/RetentionExecution.php
app/Models/SecurityAlert.php
app/Models/MfaDevice.php
```

### Objetivos

- Adicionar generics Eloquent.
- Tipar factories quando existirem.
- Tipar scopes simples.
- Documentar casts enum/datetime existentes.
- Corrigir nullability sem alterar persistência.

### Proibido

- alterar casts funcionais;
- alterar fillable/guarded sem necessidade comprovada;
- alterar tabela ou schema;
- alterar hidden/visible em modelos sensíveis;
- alterar serialização pública sem teste.

---

## 12. Validação por Lote

Após cada bloco executar:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-15-after-<bloco>.txt

./vendor/bin/pint --test
```

Blocos:

- `policies`
- `security`
- `rgpd`
- `audit`
- `models`

---

## 13. Validação Final Obrigatória

Executar:

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml

php artisan route:list --except-vendor

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-15-final.txt
```

Resultado obrigatório:

- Pint OK;
- PHPUnit direto OK;
- route list OK;
- PHPStan com redução líquida;
- `exact_new = 0`;
- sem alterações funcionais;
- sem suppressions;
- sem baseline.

---

## 14. Artefactos a Criar

```text
docs/qa/phpstan-15-security-rgpd-audit-policies-final-hardening-report.md

storage/phpstan/phpstan-15-before.txt
storage/phpstan/phpstan-15-after-policies.txt
storage/phpstan/phpstan-15-after-security.txt
storage/phpstan/phpstan-15-after-rgpd.txt
storage/phpstan/phpstan-15-after-audit.txt
storage/phpstan/phpstan-15-after-models.txt
storage/phpstan/phpstan-15-final.txt
storage/phpstan/phpstan-15-summary.txt
storage/phpstan/phpstan-15-phpunit.txt
storage/phpstan/phpstan-15-directed-tests.txt
storage/phpstan/phpstan-15-pint-final.txt
storage/phpstan/phpstan-15-route-list.txt
```

---

## 15. Relatório Final Obrigatório

O relatório deve conter:

### 15.1. Métricas

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros PHPStan globais | X | Y | Z |
| Ficheiros com erros | X | Y | Z |
| Erros removidos por assinatura exata | X | Y | Z |
| Erros novos por assinatura exata | 0 | 0 | 0 |
| `missingType.generics` | X | Y | Z |
| `missingType.iterableValue` | X | Y | Z |
| `argument.type` | X | Y | Z |
| `property.nonObject` | X | Y | Z |
| `method.nonObject` | X | Y | Z |
| `return.type` | X | Y | Z |

### 15.2. Ficheiros alterados

Agrupar por:

- policies;
- security;
- RGPD;
- audit;
- models;
- controllers adjacentes.

### 15.3. Segurança

Confirmar explicitamente:

- nenhuma permissão foi alargada;
- nenhum ownership foi removido;
- nenhum log foi removido;
- MFA preservado;
- acesso a dados sensíveis preservado;
- documentos privados preservados.

### 15.4. Testes executados

Listar:

- comando;
- resultado;
- testes;
- asserções;
- filtros sem testes encontrados.

### 15.5. Bugs reais encontrados

Classificar:

| Código | Tipo |
| --- | --- |
| BR | Bug real |
| FP | Falso positivo |
| DT | Dívida técnica |
| RF | Risco funcional |
| SR | Segurança/RGPD |
| TS | Tipagem segura |

### 15.6. Riscos residuais

Listar riscos em:

- remaining models;
- seeders demo;
- integrations;
- controllers periféricos;
- CI/CD memory limit;
- PHPStan residual.

---

## 16. Meta da Sprint

### Mínimo

```text
-100 erros PHPStan
```

### Esperado

```text
-150 a -250 erros PHPStan
```

### Excelente

```text
< 350 erros PHPStan globais
```

Sem regressões funcionais.

---

## 17. Próxima Sprint Prevista

## PHPSTAN-16 — Residual Models, Seeders, Integrations & CI Quality Gate

Foco previsto:

- modelos remanescentes;
- seeders demo;
- integrations;
- controllers periféricos;
- `missingType.generics`;
- `missingType.iterableValue`;
- `nullsafe.neverNull`;
- quality gate progressivo;
- preparação de CI/CD com threshold de regressão zero.

A PHPSTAN-16 só deve iniciar após a PHPSTAN-15 concluir com:

- PHPUnit direto verde;
- Pint verde;
- route list OK;
- PHPStan reduzido;
- `exact_new = 0`;
- sem alterações funcionais;
- sem suppressions;
- sem baseline.
