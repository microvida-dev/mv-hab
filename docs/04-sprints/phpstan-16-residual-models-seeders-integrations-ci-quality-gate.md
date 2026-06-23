# SPRINT PHPSTAN-16 — Residual Models, Seeders, Integrations & CI Quality Gate

**Projeto:** CRM MV HAB  
**Framework:** Laravel 13.8+  
**PHP:** 8.4  
**Data:** 2026-06-23  
**Âmbito:** Models remanescentes, seeders/factories, services periféricos, integrações, controllers residuais e preparação de quality gate CI/CD.

---

## 1. Objetivo

Executar uma nova vaga de remediação PHPStan após a conclusão da PHPSTAN-15, focada em dívida técnica residual fora dos domínios críticos já tratados.

A PHPSTAN-15 concluiu o perímetro **Security/RGPD/Audit/Policies**, removendo todos os erros remanescentes nesse âmbito direto, com `exact_new = 0`.

Esta sprint deve atacar:

- models com relações Eloquent ainda sem generics;
- seeders e factories com array shapes pendentes;
- services periféricos com `method.nonObject`, `property.nonObject` e `return.type`;
- integrações e serviços auxiliares;
- controllers residuais;
- preparação de quality gate progressivo para CI/CD;
- documentação do limite operacional de memória em testes.

---

## 2. Estado de Partida

Após PHPSTAN-15:

| Métrica | Valor |
| --- | ---: |
| Erros PHPStan globais reportados pelo wrapper | 487 |
| Erros normalizados por assinatura | 485 |
| Ficheiros com erros normalizados | 181 |
| Erros removidos na PHPSTAN-15 | 57 |
| Erros novos por assinatura exata | 0 |
| Erros remanescentes Security/RGPD/Audit/Policies | 0 |
| PHPUnit direto com memory_limit=-1 | OK — 283 testes / 1775 asserções |
| Pint | OK |
| Route list | OK — 1083 rotas |

A meta desta sprint não é forçar volume artificial, mas sim reduzir dívida residual sem comprometer domínios funcionais já estabilizados.

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

### 4.1. Não alterar domínios estabilizados

É proibido alterar regras funcionais em:

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
- RGPD;
- auditoria;
- security;
- policies;
- MFA;
- documentos privados;
- workflows administrativos críticos.

### 4.2. Não silenciar PHPStan

É proibido:

- criar baseline;
- alterar baseline;
- adicionar `ignoreErrors`;
- usar `@phpstan-ignore-line`;
- usar `@phpstan-ignore-next-line`;
- usar `mixed` para ocultar erro;
- alargar contratos artificialmente;
- alterar migrations;
- alterar seeders com impacto funcional;
- alterar dependências Composer/NPM.

### 4.3. Política obrigatória

Manter:

```text
exact_new = 0
```

Qualquer erro novo por assinatura exata deve ser corrigido no mesmo lote ou revertido.

---

## 5. Âmbito Prioritário

Atuar por ordem estrita:

1. Models remanescentes com `missingType.generics`.
2. Factories e seeders demo.
3. Services periféricos de Tenant Portal, billing, visits e maintenance residual.
4. Services com `method.nonObject`, `property.nonObject` e `return.type`.
5. Integrations e external services.
6. Controllers periféricos.
7. Preparação de quality gate CI/CD.

Não avançar para o bloco seguinte se o anterior introduzir novos erros por assinatura exata.

---

## 6. Fase 1 — Baseline Local da Sprint

Executar:

```bash
php artisan optimize:clear

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-16-before.txt
```

Registar:

- total global de erros;
- ficheiros afetados;
- distribuição por identificador;
- top 30 ficheiros com mais erros;
- erros em models remanescentes;
- erros em seeders/factories;
- erros em services periféricos;
- erros em integrações;
- erros exatos da baseline.

---

## 7. Fase 2 — Models Remanescentes

### Âmbito

```text
app/Models/*
```

Focar apenas models que ainda tenham:

- `missingType.generics`;
- `missingType.iterableValue`;
- `return.type`;
- `property.notFound`;
- `method.nonObject`;
- `property.nonObject`.

### Objetivos

- Adicionar generics Eloquent em relações simples.
- Tipar scopes com `Builder<self>`.
- Tipar factories com `@use HasFactory<...>` quando inequívoco.
- Documentar casts enum/datetime já existentes.
- Corrigir `nullsafe.neverNull` apenas quando comprovadamente redundante.
- Evitar qualquer alteração de schema ou lógica.

### Relações permitidas

Podem ser corrigidas:

- `BelongsTo`;
- `HasOne`;
- `HasMany`;
- `BelongsToMany`;
- `HasManyThrough`;
- `HasOneThrough`.

### Relações que exigem cautela

Não corrigir automaticamente sem leitura contextual:

- `MorphTo`;
- `MorphMany`;
- `MorphOne`;
- relações de documentos privados;
- relações de auditoria;
- relações usadas em policies;
- relações com `withTrashed`;
- relações dinâmicas.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Model
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Application
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Candidate
```

Se `--filter Model` não encontrar testes, documentar e executar suite global.

---

## 8. Fase 3 — Factories e Seeders Demo

### Âmbito

```text
database/factories/*
database/seeders/*
```

### Objetivos

- Adicionar `@extends Factory<Model>` em factories remanescentes.
- Tipar arrays de seeders com shapes seguros.
- Corrigir enum/string mismatch quando o enum correto é inequívoco.
- Corrigir `nullsafe.neverNull` em factories.
- Manter dados demo funcionalmente equivalentes.

### Proibido

- alterar dados reais;
- alterar IDs usados por testes;
- alterar sequência de seeders;
- alterar regras regulamentares;
- introduzir dependência de ambiente externo.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Seeder
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Factory
```

Se não existirem testes nominais, executar suite global e documentar a lacuna.

---

## 9. Fase 4 — Tenant Portal, Billing, Visits e Maintenance Residual

### Âmbito provável

```text
app/Services/TenantPortal/*
app/Services/TenantBilling/*
app/Services/Visits/*
app/Services/Maintenance/*
app/Http/Controllers/*Tenant*
app/Http/Controllers/*Visit*
app/Http/Controllers/*Maintenance*
```

### Objetivos

- Corrigir `method.nonObject`, `property.nonObject`, `argument.type` e `return.type`.
- Adicionar guards conservadores antes de relações opcionais.
- Tipar arrays de dashboard/portal.
- Tipar payloads de visitas e manutenção.
- Preservar fluxos funcionais.

### Proibido

- alterar contratos;
- alterar rendas;
- alterar pagamentos;
- alterar permissões de inquilino;
- alterar estado de manutenção;
- alterar agendamento de visitas;
- alterar notificações legais.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Tenant
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Billing
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Visit
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Maintenance
```

---

## 10. Fase 5 — Integrations e External Services

### Âmbito provável

```text
app/Services/Integrations/*
app/Services/External/*
app/Services/Notifications/*
app/Jobs/*
app/Events/*
app/Listeners/*
```

### Objetivos

- Tipar responses externas como `array<string, mixed>`.
- Corrigir offsets inseguros.
- Corrigir `argument.type`.
- Garantir null safety em integrações.
- Documentar fallbacks.
- Preservar comportamento assíncrono.

### Proibido

- adicionar APIs pagas;
- alterar credenciais;
- alterar `.env`;
- alterar filas de produção;
- alterar retry/backoff sem requisito;
- alterar notificações legais.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Integration
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Notification
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Job
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Queue
```

---

## 11. Fase 6 — Controllers Periféricos

### Âmbito

```text
app/Http/Controllers/*
```

Focar apenas controllers ainda assinalados pelo PHPStan e que estejam fora de domínios críticos já tratados.

### Objetivos

- Corrigir `argument.type`;
- corrigir `return.type`;
- estreitar tipos antes de chamadas a services;
- substituir `find()` por `findOrFail()` apenas quando o comportamento esperado já era abortar;
- preservar policies e gates.

### Proibido

- remover `authorize`;
- remover `Gate::authorize`;
- alterar redirects funcionais;
- alterar validações de requests;
- alterar payloads públicos;
- alterar status codes sem teste.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Controller
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Backoffice
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Candidate
```

---

## 12. Fase 7 — CI Quality Gate Progressivo

### Objetivo

Criar documentação e scripts de apoio para impedir regressões sem exigir PHPStan verde global imediatamente.

### Criar ou atualizar

```text
docs/qa/phpstan-quality-gate.md
scripts/phpstan-baseline-compare.php
scripts/phpstan-count-errors.php
```

Se já existirem scripts equivalentes, atualizar sem duplicar.

### Política recomendada

O quality gate deve bloquear:

- aumento do número total de erros;
- qualquer erro novo por assinatura exata;
- uso de `@phpstan-ignore`;
- alteração de baseline;
- introdução de `ignoreErrors`;
- falha de Pint;
- falha de PHPUnit direto;
- falha de route list.

### Comandos recomendados para CI local

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml

php artisan route:list --except-vendor

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-current.json

php scripts/phpstan-count-errors.php storage/phpstan/phpstan-current.json

php scripts/phpstan-baseline-compare.php storage/phpstan/phpstan-previous.json storage/phpstan/phpstan-current.json
```

### Nota

Não criar baseline PHPStan oficial para esconder dívida técnica.  
A comparação deve ser por assinatura exata de erro, não por linha, para evitar falsos positivos por deslocamento de código.

---

## 13. Validação por Lote

Após cada bloco executar:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-16-after-<bloco>.txt

./vendor/bin/pint --test
```

Blocos:

- `models`
- `factories-seeders`
- `tenant-billing-visits-maintenance`
- `integrations`
- `controllers`
- `quality-gate`

---

## 14. Validação Final Obrigatória

Executar:

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml

php artisan route:list --except-vendor

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-16-final.txt
```

Resultado obrigatório:

- Pint OK;
- PHPUnit direto OK;
- route list OK;
- PHPStan com redução líquida;
- `exact_new = 0`;
- sem alterações funcionais;
- sem suppressions;
- sem baseline;
- sem alterações indevidas de segurança/RGPD/auditoria.

---

## 15. Artefactos a Criar

```text
docs/qa/phpstan-16-residual-models-seeders-integrations-ci-quality-gate-report.md
docs/qa/phpstan-quality-gate.md

storage/phpstan/phpstan-16-before.txt
storage/phpstan/phpstan-16-after-models.txt
storage/phpstan/phpstan-16-after-factories-seeders.txt
storage/phpstan/phpstan-16-after-tenant-billing-visits-maintenance.txt
storage/phpstan/phpstan-16-after-integrations.txt
storage/phpstan/phpstan-16-after-controllers.txt
storage/phpstan/phpstan-16-after-quality-gate.txt
storage/phpstan/phpstan-16-final.txt
storage/phpstan/phpstan-16-summary.txt
storage/phpstan/phpstan-16-phpunit.txt
storage/phpstan/phpstan-16-directed-tests.txt
storage/phpstan/phpstan-16-pint-final.txt
storage/phpstan/phpstan-16-route-list.txt
```

Opcional, apenas se útil e sem duplicar scripts existentes:

```text
scripts/phpstan-baseline-compare.php
scripts/phpstan-count-errors.php
```

---

## 16. Relatório Final Obrigatório

O relatório deve conter:

### 16.1. Métricas

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros PHPStan globais reportados pelo wrapper | X | Y | Z |
| Erros normalizados por assinatura | X | Y | Z |
| Ficheiros com erros normalizados | X | Y | Z |
| Erros removidos por assinatura exata | X | Y | Z |
| Erros novos por assinatura exata | 0 | 0 | 0 |
| `missingType.generics` | X | Y | Z |
| `missingType.iterableValue` | X | Y | Z |
| `argument.type` | X | Y | Z |
| `method.nonObject` | X | Y | Z |
| `property.nonObject` | X | Y | Z |
| `return.type` | X | Y | Z |

### 16.2. Ficheiros alterados

Agrupar por:

- models;
- factories/seeders;
- tenant/billing/visits/maintenance;
- integrations;
- controllers;
- quality gate.

### 16.3. Testes executados

Listar:

- comando;
- resultado;
- testes;
- asserções;
- filtros sem testes encontrados.

### 16.4. Correções adiadas

Listar:

- ficheiro;
- erro;
- motivo;
- risco;
- sprint recomendada.

### 16.5. Bugs reais encontrados

Classificar:

| Código | Tipo |
| --- | --- |
| BR | Bug real |
| FP | Falso positivo |
| DT | Dívida técnica |
| RF | Risco funcional |
| TS | Tipagem segura |
| CI | Quality gate/CI |

### 16.6. Riscos residuais

Listar riscos em:

- PHPStan residual;
- CI/CD memory limit;
- ausência de Git no diretório atual;
- seeders demo;
- integrations externas;
- remaining models;
- testes ausentes por filtro nominal.

---

## 17. Meta da Sprint

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
< 250 erros PHPStan globais
```

Sem regressões funcionais.

A meta quantitativa pode ser reduzida se o conjunto seguro remanescente for inferior ao esperado. Não forçar correções fora do âmbito apenas para cumprir volume.

---

## 18. Próxima Sprint Prevista

## PHPSTAN-17 — Final Residual Cleanup & Enterprise CI Enforcement

Foco previsto:

- últimos erros PHPStan;
- erros de baixo risco remanescentes;
- `nullsafe.neverNull`;
- `return.type`;
- `missingType.iterableValue`;
- seeders/factories finais;
- CI/CD definitivo;
- quality gate com regressão zero;
- preparação para PHPStan mais estrito.

A PHPSTAN-17 só deve iniciar após a PHPSTAN-16 concluir com:

- PHPUnit direto verde;
- Pint verde;
- route list OK;
- PHPStan reduzido;
- `exact_new = 0`;
- sem alterações funcionais;
- sem suppressions;
- sem baseline;
- quality gate documentado.
