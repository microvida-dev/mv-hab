# SPRINT PHPSTAN-08 — Models Core & Eloquent Relations Hardening

**Projeto:** CRM MV HAB  
**Framework:** Laravel 13.8+  
**PHP:** 8.4  
**Data:** 2026-06-23  
**Âmbito:** Models Core, Eloquent Relations, casts, scopes e relação entre modelos centrais.

---

## 1. Objetivo

Executar uma remediação incremental, segura e auditável dos erros PHPStan concentrados nos **Models Core** da plataforma MV HAB, começando por:

- `Application`
- `User`
- `Contract`
- `Contest`
- `Program`
- `HousingUnit`

O objetivo principal é reduzir dívida técnica em relações Eloquent, casts, scopes e inferência de modelos sem alterar regras de negócio, permissões, workflows administrativos ou comportamento funcional.

---

## 2. Estado de Partida

Após a PHPSTAN-07:

| Métrica | Valor |
| --- | ---: |
| Erros PHPStan globais | 2192 |
| Ficheiros com erros PHPStan | 393 |
| PHPUnit | 283 testes / 1775 asserções |
| Pint | OK |
| Erros novos introduzidos na sprint anterior | 0 |

A PHPSTAN-07 atingiu o objetivo máximo, reduzindo os erros globais de 2513 para 2192 e deixando como principal recomendação avançar para **Models Core & Eloquent Relations Hardening**.

---

## 3. Modelos Prioritários

A sprint deve atuar por ordem estrita:

1. `app/Models/Application.php`
2. `app/Models/User.php`
3. `app/Models/Contract.php`
4. `app/Models/Contest.php`
5. `app/Models/Program.php`
6. `app/Models/HousingUnit.php`

Não avançar para o modelo seguinte se o anterior introduzir erros novos, alterações funcionais ou ambiguidade não resolvida.

---

## 4. Regras Absolutas

### 4.1. Não alterar regras de negócio

É proibido alterar:

- Elegibilidade
- Pontuação
- Classificação
- Fórmulas de scoring
- Estados de candidatura
- Estados de contrato
- Regras de concurso
- Sorteios
- Allocation
- Rendas
- Vistorias
- Manutenção
- RGPD
- Auditoria
- Policies
- MFA
- Segurança
- Workflow documental

### 4.2. Não alterar infraestrutura

É proibido criar ou alterar:

- Migrations
- Seeders
- Factories fora do objetivo imediato
- Configurações PHPStan
- Baselines
- Suppressions
- `@phpstan-ignore-line`
- `@phpstan-ignore-next-line`
- `ignoreErrors`
- Dependências Composer/NPM

### 4.3. Não usar widening artificial

Não corrigir erros através de:

- `mixed` desnecessário
- `Model|Collection|array` artificial
- `object` genérico
- `@var mixed`
- casts forçados sem prova funcional
- alterações de assinatura que enfraqueçam contratos existentes

---

## 5. Estratégia Técnica

### 5.1. Tipagem de relações Eloquent

Aplicar PHPDoc generics Laravel/Larastan com o padrão validado nas sprints anteriores:

```php
/**
 * @return BelongsTo<User, $this>
 */
public function user(): BelongsTo
```

```php
/**
 * @return HasMany<ApplicationDocument, $this>
 */
public function documents(): HasMany
```

```php
/**
 * @return HasOne<Household, $this>
 */
public function household(): HasOne
```

Usar `$this` como declaring model, não `self`, porque o padrão `self` já demonstrou gerar `return.type` em massa.

### 5.2. Relações permitidas nesta sprint

Podem ser corrigidas:

- `BelongsTo`
- `HasOne`
- `HasMany`
- `BelongsToMany`
- `HasManyThrough`
- `HasOneThrough`
- scopes simples com `Builder<self>`
- traits `HasFactory` com `@use HasFactory<FactoryClass>` quando a factory existir e for inequívoca

### 5.3. Relações que exigem validação manual

Não corrigir automaticamente sem leitura profunda:

- `MorphTo`
- `MorphMany`
- `MorphOne`
- `MorphToMany`
- relações com `withTrashed()`
- relações que atravessam models sensíveis
- relações de documentos privados
- relações de auditoria/RGPD
- relações usadas em policies críticas

Estas devem ser listadas em “Correções adiadas”.

---

## 6. Fase 1 — Baseline Local da Sprint

Executar:

```bash
php artisan optimize:clear

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-08-before.txt
```

Registar:

- total de erros
- ficheiros afetados
- erros por model prioritário
- identificadores dominantes
- erros `missingType.generics`
- erros `method.notFound`
- erros `property.notFound`
- erros `property.nonObject`
- erros `method.nonObject`
- erros enum/string mismatch

---

## 7. Fase 2 — Application Model

### Ficheiro

```text
app/Models/Application.php
```

### Objetivos

- Tipar relações Eloquent simples.
- Validar casts de estados e datas.
- Validar scopes existentes sem alterar queries.
- Resolver falsos positivos relacionados com `eligibleForAllocation()` e `readyForContract()` apenas se a causa for tipagem de relação.

### Atenção máxima

Não alterar:

- submissão de candidatura
- retirada/desistência
- elegibilidade
- pontuação
- estados administrativos
- relações documentais privadas
- histórico processual

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Application

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Candidate

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Eligibility
```

---

## 8. Fase 3 — User Model

### Ficheiro

```text
app/Models/User.php
```

### Objetivos

- Tipar relações Eloquent simples.
- Consolidar relações já tocadas em RGPD.
- Evitar ruído em autenticação, roles, permissões e titularidade de dados.
- Não alterar comportamento de autenticação/autorização.

### Atenção máxima

Não alterar:

- autenticação
- roles
- permissões
- RGPD
- MFA
- auditoria
- ownership de candidaturas/documentos

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter User

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Auth

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security
```

---

## 9. Fase 4 — Contract Model

### Ficheiro

```text
app/Models/Contract.php
```

### Objetivos

- Tipar relações de contrato.
- Validar casts de datas e estados.
- Reduzir erros de relações com rendas, pagamentos, inquilinos e habitações.
- Não alterar lógica financeira.

### Atenção máxima

Não alterar:

- cálculo de renda
- emissão de pagamentos
- conta financeira do inquilino
- faturas/recibos
- estado contratual
- renovações
- rescisões

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contract

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Finance

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rent

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Tenant
```

---

## 10. Fase 5 — Contest Model

### Ficheiro

```text
app/Models/Contest.php
```

### Objetivos

- Tipar relações com programa, habitações, candidaturas, listas e critérios.
- Validar casts de datas de abertura/fecho/publicação.
- Não alterar abertura, encerramento ou publicação de concursos.

### Atenção máxima

Não alterar:

- regras de concurso
- elegibilidade por concurso
- editais
- prazos
- listas provisórias/definitivas
- audiência prévia
- reclamações

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contest

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter List

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Hearing
```

---

## 11. Fase 6 — Program Model

### Ficheiro

```text
app/Models/Program.php
```

### Objetivos

- Tipar relações com concursos, critérios, regras e configurações.
- Validar casts e atributos calculados.
- Não alterar configurações funcionais de programas.

### Atenção máxima

Não alterar:

- critérios de programa
- regras de elegibilidade
- fórmulas associadas
- templates/minutas
- configurações administrativas

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Program

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Eligibility

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Scoring
```

---

## 12. Fase 7 — HousingUnit Model

### Ficheiro

```text
app/Models/HousingUnit.php
```

### Objetivos

- Tipar relações de habitações, documentos públicos, contratos, visitas e alocações.
- Resolver erro de `publiclyVisible()` apenas se a causa for inferência relacional.
- Não alterar visibilidade pública, disponibilidade ou critérios de tipologia.

### Atenção máxima

Não alterar:

- disponibilidade de fogos
- tipologia
- renda
- publicação pública
- documentos públicos
- visitas
- associação a concursos
- atribuição/alocação

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Housing

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter PublicPortal

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Allocation
```

---

## 13. Critérios de Bloqueio

Parar e reverter o lote se ocorrer qualquer uma destas situações:

- aumento do total global de erros PHPStan;
- introdução de identificadores novos;
- alteração de comportamento em testes;
- necessidade de alterar queries de domínio;
- necessidade de alterar policies;
- necessidade de alterar migrations;
- erro novo em RGPD, Security, Finance ou Allocation;
- falha em testes dirigidos;
- ambiguidade entre falso positivo e bug real.

---

## 14. Validação por Lote

Após cada model, executar:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-08-after-<model>.txt

./vendor/bin/pint --test
```

Substituir `<model>` por:

- `application`
- `user`
- `contract`
- `contest`
- `program`
- `housing-unit`

---

## 15. Validação Final Obrigatória

Executar:

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml

php artisan route:list --except-vendor

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-08-final.txt
```

Resultado obrigatório:

- Pint OK
- PHPUnit OK
- Route list OK
- PHPStan com redução líquida
- 0 erros novos exatos
- nenhuma alteração funcional

---

## 16. Artefactos a Criar

```text
docs/qa/phpstan-08-models-core-eloquent-relations-hardening-report.md

storage/phpstan/phpstan-08-before.txt
storage/phpstan/phpstan-08-after-application.txt
storage/phpstan/phpstan-08-after-user.txt
storage/phpstan/phpstan-08-after-contract.txt
storage/phpstan/phpstan-08-after-contest.txt
storage/phpstan/phpstan-08-after-program.txt
storage/phpstan/phpstan-08-after-housing-unit.txt
storage/phpstan/phpstan-08-final.txt
storage/phpstan/phpstan-08-pint-test.txt
storage/phpstan/phpstan-08-phpunit.txt
storage/phpstan/phpstan-08-route-list.txt
```

---

## 17. Relatório Final Obrigatório

O relatório final deve conter:

### 17.1. Métricas

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros PHPStan globais | X | Y | Z |
| Ficheiros com erros | X | Y | Z |
| `missingType.generics` | X | Y | Z |
| `property.notFound` | X | Y | Z |
| `method.notFound` | X | Y | Z |
| `property.nonObject` | X | Y | Z |
| `method.nonObject` | X | Y | Z |

### 17.2. Ficheiros alterados

Listar todos os ficheiros alterados.

### 17.3. Relações corrigidas

Listar por model:

- relação
- tipo Eloquent
- generic aplicado
- risco

### 17.4. Relações adiadas

Listar:

- relação
- motivo
- risco
- sprint recomendada

### 17.5. Bugs reais encontrados

Classificar:

| Código | Tipo |
| --- | --- |
| BR | Bug real |
| FP | Falso positivo |
| DT | Dívida técnica |
| SR | Segurança/RGPD |
| PF | Performance |

### 17.6. Testes executados

Listar:

- comando
- resultado
- número de testes
- número de asserções

### 17.7. Riscos residuais

Listar riscos em:

- Allocation
- Eligibility
- Scoring
- Finance
- Policies
- RGPD
- Audit
- Administrative workflows

---

## 18. Meta da Sprint

### Objetivo mínimo

Reduzir pelo menos:

```text
-150 erros PHPStan
```

### Objetivo esperado

Reduzir:

```text
-250 a -400 erros PHPStan
```

### Objetivo ambicioso

Atingir:

```text
< 1900 erros PHPStan globais
```

Sem regressões funcionais.

---

## 19. Próxima Sprint Prevista

## PHPSTAN-09 — Domain Services Hardening

Foco previsto:

- `ApplicationValidationService`
- `ApplicationSnapshotService`
- `EligibilityDataProvider`
- `ScoringDataProvider`
- `ScoringCriterionEvaluator`
- `LeaseContractService`
- `RentSnapshotService`
- `AllocationEngine`
- `LotteryService`

A PHPSTAN-09 só deve iniciar após a PHPSTAN-08 concluir com:

- PHPUnit verde
- Pint verde
- PHPStan reduzido
- 0 erros novos
- sem alterações funcionais
- sem suppressions
- sem baseline
