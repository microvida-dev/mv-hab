# SPRINT PHPSTAN-09 — Domain Services Hardening

**Projeto:** CRM MV HAB  
**Framework:** Laravel 13.8+  
**PHP:** 8.4  
**Data:** 2026-06-23  
**Âmbito:** Services consumidores dos Models Core, lógica de domínio, enum/string mismatch, nullability e relações expostas após PHPSTAN-08.

---

## 1. Objetivo

Executar uma remediação incremental, segura e auditável dos erros PHPStan remanescentes nos **Domain Services** da plataforma MV HAB, dando prioridade aos serviços que consomem os modelos core já tipados na PHPSTAN-08.

A PHPSTAN-08 reduziu os erros globais de `2192` para `1876`, deixou `Application`, `User`, `Contract`, `Contest`, `Program` e `HousingUnit` sem erros diretos e expôs dívida agora visível em services consumidores, sobretudo em scoring, allocation, listas, contratos e workflows administrativos.

O objetivo desta sprint é corrigir apenas erros com causa compreendida, mantendo intactas as regras funcionais.

---

## 2. Estado de Partida

Após PHPSTAN-08:

| Métrica | Valor |
| --- | ---: |
| Erros PHPStan globais | 1876 |
| Redução da PHPSTAN-08 | -316 |
| PHPUnit | 283 testes / 1775 asserções |
| Pint | OK |
| Rotas | 1083 |
| Modelos core alvo | 0 erros diretos |

Top identificadores remanescentes após PHPSTAN-08:

| Identificador | Quantidade |
| --- | ---: |
| `missingType.generics` | 734 |
| `missingType.iterableValue` | 177 |
| `argument.type` | 164 |
| `property.notFound` | 125 |
| `property.nonObject` | 89 |
| `nullsafe.neverNull` | 86 |
| `method.nonObject` | 69 |
| `notIdentical.alwaysTrue` | 65 |
| `return.type` | 50 |
| `identical.alwaysFalse` | 49 |

---

## 3. Serviços Prioritários

Atuar por ordem estrita:

1. `app/Services/Scoring/ScoringDataProvider.php`
2. `app/Services/Scoring/ScoringCriterionEvaluator.php`
3. `app/Services/Eligibility/EligibilityDataProvider.php`
4. `app/Services/Applications/ApplicationValidationService.php`
5. `app/Services/Applications/ApplicationSnapshotService.php`
6. `app/Services/Contracts/LeaseContractService.php`
7. `app/Services/Contracts/RentSnapshotService.php`
8. `app/Services/Allocation/AllocationEngine.php`
9. `app/Services/Allocation/LotteryService.php`
10. `app/Services/Administrative/AdministrativeTimelineService.php`

Não avançar para o serviço seguinte se o anterior introduzir erros novos, regressões ou ambiguidade funcional.

---

## 4. Regras Absolutas

### 4.1. Não alterar regras de negócio

É proibido alterar:

- fórmulas de elegibilidade;
- fórmulas de pontuação;
- critérios de classificação;
- desempates;
- ordenação de listas;
- regras de concurso;
- regras de atribuição;
- sorteios;
- contratos;
- rendas;
- RGPD;
- auditoria;
- policies;
- permissões;
- estados de candidatura;
- estados de contrato;
- estados de listas;
- workflow documental.

### 4.2. Não criar atalhos PHPStan

É proibido:

- criar baseline;
- adicionar `ignoreErrors`;
- usar `@phpstan-ignore-line`;
- usar `@phpstan-ignore-next-line`;
- usar `mixed` para esconder erro;
- alargar contratos para `Model|Collection|array` sem prova;
- converter enums para strings por conveniência;
- alterar casts apenas para satisfazer PHPStan;
- alterar migrations ou seeders.

---

## 5. Estratégia Técnica

### 5.1. Corrigir apenas quando a causa estiver provada

Cada correção deve ser classificada antes de ser aplicada:

| Código | Significado |
| --- | --- |
| BR | Bug real |
| FP | Falso positivo por inferência |
| DT | Dívida técnica |
| TS | Tipagem segura |
| SR | Segurança/RGPD |
| PF | Performance |

### 5.2. Correções permitidas

São permitidas:

- PHPDoc preciso;
- array shapes;
- `Collection<int, Model>`;
- `Builder<Model>`;
- guards locais;
- validação explícita de `Model|null`;
- `assert($model instanceof ModelEsperado)` apenas quando a relação é obrigatória por schema ou workflow;
- normalização de enums quando o cast já existe;
- remoção de `?->` redundante apenas quando PHPStan prova não-null e não há impacto funcional;
- estreitamento de tipo antes de chamar services.

### 5.3. Correções proibidas

São proibidas:

- alterar fórmulas;
- alterar queries de domínio;
- alterar ordenações de classificação;
- alterar filtros de elegibilidade;
- alterar transições de estado;
- alterar autorização;
- alterar persistência;
- alterar eventos ou notificações funcionais;
- alterar UX/frontend.

---

## 6. Fase 1 — Baseline Local da Sprint

Executar:

```bash
php artisan optimize:clear

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-09-before.txt
```

Registar:

- total global de erros;
- erros por ficheiro prioritário;
- identificadores por service;
- erros novos aparentes após PHPSTAN-08;
- lista de erros de alto risco.

---

## 7. Fase 2 — Scoring

### Ficheiros

```text
app/Services/Scoring/ScoringDataProvider.php
app/Services/Scoring/ScoringCriterionEvaluator.php
```

### Objetivos

- Reduzir erros de relações agora tipadas em `Application`, `Contest`, `Program` e `HousingUnit`.
- Corrigir `property.notFound`, `argument.type`, `property.nonObject` e enum/string mismatch.
- Tipar arrays de dados de scoring sem alterar fórmulas.
- Garantir que snapshots e critérios continuam determinísticos.

### Proibido

- alterar pesos;
- alterar critérios;
- alterar desempates;
- alterar cálculo final;
- alterar arredondamentos;
- alterar estado de pontuação.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Scoring
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Score
```

---

## 8. Fase 3 — Eligibility

### Ficheiro

```text
app/Services/Eligibility/EligibilityDataProvider.php
```

### Objetivos

- Corrigir nullability e tipos de relações usadas na elegibilidade.
- Tipar payloads e collections.
- Remover ruído provocado por casts já existentes.
- Preservar integralmente todas as regras de elegibilidade.

### Proibido

- alterar limites;
- alterar regras de rendimento;
- alterar tipologia;
- alterar critérios de exclusão;
- alterar impedimentos;
- alterar resultado elegível/não elegível.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Eligibility
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Simulator
```

---

## 9. Fase 4 — Application Services

### Ficheiros

```text
app/Services/Applications/ApplicationValidationService.php
app/Services/Applications/ApplicationSnapshotService.php
```

### Objetivos

- Corrigir `argument.type`, `missingType.iterableValue`, `return.type` e `property.notFound`.
- Tipar snapshots como payloads imutáveis.
- Estreitar relações antes de consumir household, income records, documents e contest.
- Não alterar processo de submissão.

### Proibido

- alterar validações funcionais;
- alterar estados de candidatura;
- alterar documentos obrigatórios;
- alterar bloqueios de submissão;
- alterar snapshot funcional.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Application
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Candidate
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Document
```

---

## 10. Fase 5 — Contracts & Rent

### Ficheiros

```text
app/Services/Contracts/LeaseContractService.php
app/Services/Contracts/RentSnapshotService.php
```

### Objetivos

- Corrigir erros de relações com `Contract`, `User`, `HousingUnit`, rendas e pagamentos.
- Tipar snapshots financeiros.
- Corrigir nullability local sem alterar cálculo ou emissão.

### Proibido

- alterar cálculo de renda;
- alterar estados contratuais;
- alterar emissão de contratos;
- alterar pagamentos;
- alterar recibos;
- alterar renovações/rescisões.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contract
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Lease
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rent
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Finance
```

---

## 11. Fase 6 — Allocation & Lottery

### Ficheiros

```text
app/Services/Allocation/AllocationEngine.php
app/Services/Allocation/LotteryService.php
```

### Objetivos

- Corrigir inferência de definitive lists, entries, offers e allocations.
- Resolver apenas falsos positivos provados de scopes/relações.
- Validar nullability antes de processar atribuições.
- Documentar qualquer risco de concorrência ou estado impossível.

### Proibido

- alterar seleção de candidatos;
- alterar sorteio;
- alterar ordenação;
- alterar desempate;
- alterar locking;
- alterar transições de allocation;
- alterar contratos gerados após atribuição.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Allocation
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Lottery
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter DefinitiveList
```

---

## 12. Fase 7 — Administrative Timeline

### Ficheiro

```text
app/Services/Administrative/AdministrativeTimelineService.php
```

### Objetivos

- Tipar eventos de timeline.
- Corrigir arrays e collections.
- Validar relações opcionais.
- Manter histórico processual íntegro.

### Proibido

- alterar ordem cronológica;
- alterar texto legal/processual;
- alterar estados administrativos;
- alterar auditoria;
- alterar notificações.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Administrative
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Timeline
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Process
```

---

## 13. Critérios de Bloqueio

Parar e reverter o lote se ocorrer:

- aumento global de erros PHPStan;
- erro novo em domínio crítico;
- falha de teste dirigido;
- alteração de resultado funcional;
- necessidade de alterar migration;
- necessidade de alterar policy;
- necessidade de alterar fórmula;
- alteração de query que influencie classificação/elegibilidade;
- enum/string mismatch sem cast confirmado;
- relação obrigatória sem prova por schema/teste.

---

## 14. Validação por Lote

Após cada bloco executar:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-09-after-<bloco>.txt

./vendor/bin/pint --test
```

Blocos:

- `scoring`
- `eligibility`
- `applications`
- `contracts`
- `allocation`
- `administrative`

---

## 15. Validação Final Obrigatória

Executar:

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml

php artisan route:list --except-vendor

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-09-final.txt
```

Resultado obrigatório:

- Pint OK;
- PHPUnit OK;
- route list OK;
- PHPStan com redução líquida;
- 0 erros novos exatos;
- sem alterações funcionais;
- sem suppressions;
- sem baseline.

---

## 16. Artefactos a Criar

```text
docs/qa/phpstan-09-domain-services-hardening-report.md

storage/phpstan/phpstan-09-before.txt
storage/phpstan/phpstan-09-after-scoring.txt
storage/phpstan/phpstan-09-after-eligibility.txt
storage/phpstan/phpstan-09-after-applications.txt
storage/phpstan/phpstan-09-after-contracts.txt
storage/phpstan/phpstan-09-after-allocation.txt
storage/phpstan/phpstan-09-after-administrative.txt
storage/phpstan/phpstan-09-final.txt
storage/phpstan/phpstan-09-pint-test.txt
storage/phpstan/phpstan-09-phpunit.txt
storage/phpstan/phpstan-09-route-list.txt
```

---

## 17. Relatório Final Obrigatório

O relatório final deve conter:

### 17.1. Métricas

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros PHPStan globais | X | Y | Z |
| Ficheiros com erros | X | Y | Z |
| `argument.type` | X | Y | Z |
| `property.notFound` | X | Y | Z |
| `property.nonObject` | X | Y | Z |
| `method.nonObject` | X | Y | Z |
| `missingType.iterableValue` | X | Y | Z |
| `return.type` | X | Y | Z |

### 17.2. Ficheiros alterados

Listar todos os ficheiros alterados.

### 17.3. Correções por domínio

Agrupar por:

- scoring;
- eligibility;
- applications;
- contracts;
- allocation;
- administrative.

### 17.4. Bugs reais encontrados

Classificar:

| Código | Tipo |
| --- | --- |
| BR | Bug real |
| FP | Falso positivo |
| DT | Dívida técnica |
| SR | Segurança/RGPD |
| PF | Performance |
| RF | Risco funcional |

### 17.5. Correções adiadas

Listar:

- ficheiro;
- erro;
- motivo;
- risco;
- sprint recomendada.

### 17.6. Testes executados

Listar:

- comando;
- resultado;
- número de testes;
- número de asserções.

### 17.7. Riscos residuais

Listar riscos em:

- Allocation;
- Lottery;
- Definitive Lists;
- Provisional Lists;
- Policies;
- RGPD;
- Audit;
- Finance;
- Maintenance;
- Inspections.

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
-250 a -350 erros PHPStan
```

### Objetivo ambicioso

Atingir:

```text
< 1600 erros PHPStan globais
```

Sem regressões funcionais.

---

## 19. Próxima Sprint Prevista

## PHPSTAN-10 — Allocation, Lists & Administrative Workflow Hardening

Foco previsto:

- `app/Models/Allocation.php`
- `app/Models/DefinitiveList.php`
- `app/Models/ProvisionalList.php`
- `app/Models/DefinitiveListEntry.php`
- `app/Models/ProvisionalListEntry.php`
- `app/Services/Lists/*`
- `app/Services/Allocation/*`
- `app/Services/Hearings/*`
- `app/Services/Complaints/*`

A PHPSTAN-10 só deve iniciar após a PHPSTAN-09 concluir com:

- PHPUnit verde;
- Pint verde;
- PHPStan reduzido;
- 0 erros novos;
- sem alterações funcionais;
- sem suppressions;
- sem baseline.
