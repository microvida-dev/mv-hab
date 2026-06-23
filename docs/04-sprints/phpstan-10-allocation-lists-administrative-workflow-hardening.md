# SPRINT PHPSTAN-10 — Allocation, Lists & Administrative Workflow Hardening

**Projeto:** CRM MV HAB  
**Framework:** Laravel 13.8+  
**PHP:** 8.4  
**Data:** 2026-06-23  
**Âmbito:** Allocation, listas provisórias/definitivas, audiência prévia, reclamações e workflow administrativo.

---

## 1. Objetivo

Executar uma remediação incremental, segura e auditável dos erros PHPStan remanescentes nos domínios de:

- Atribuição / Allocation
- Sorteios
- Listas definitivas
- Listas provisórias
- Entradas de lista
- Audiência prévia
- Reclamações
- Pedidos de aperfeiçoamento
- Workflow administrativo
- Transições processuais
- Histórico processual

A PHPSTAN-09 reduziu os erros globais de `1876` para `1625`, deixou os 10 serviços alvo sem erros diretos e recomenda avançar sobre generics/relações, `argument.type`, `return.type`, `method.nonObject`, `property.nonObject`, `method.notFound`, `identical.alwaysFalse` e `deadCode.unreachable` em áreas fora do sprint anterior.

---

## 2. Estado de Partida

Após PHPSTAN-09:

| Métrica | Valor |
| --- | ---: |
| Erros PHPStan globais | 1625 |
| Redução da PHPSTAN-09 | -251 |
| Ficheiros com erros | 374 |
| PHPUnit | 283 testes / 1775 asserções |
| Pint | OK |
| Rotas | 1083 |
| Erros diretos nos 10 serviços da PHPSTAN-09 | 0 |

Top identificadores remanescentes após PHPSTAN-09:

| Identificador | Quantidade |
| --- | ---: |
| `missingType.generics` | 727 |
| `missingType.iterableValue` | 153 |
| `argument.type` | 151 |
| `property.notFound` | 71 |
| `property.nonObject` | 63 |
| `method.nonObject` | 61 |
| `return.type` | 46 |
| `nullsafe.neverNull` | 43 |
| `notIdentical.alwaysTrue` | 42 |
| `deadCode.unreachable` | 40 |
| `identical.alwaysFalse` | 27 |
| `method.notFound` | 25 |
| `function.impossibleType` | 25 |

---

## 3. Ficheiros Prioritários

Atuar por ordem estrita:

1. `app/Models/Allocation.php`
2. `app/Models/AllocationRun.php`
3. `app/Models/AllocationOffer.php`
4. `app/Models/DefinitiveList.php`
5. `app/Models/DefinitiveListEntry.php`
6. `app/Models/ProvisionalList.php`
7. `app/Models/ProvisionalListEntry.php`
8. `app/Services/Allocation/AllocationOfferService.php`
9. `app/Services/Allocation/AllocationResponseService.php`
10. `app/Services/Allocation/AllocationReportService.php`
11. `app/Services/Allocation/ContractReadinessService.php`
12. `app/Services/Lists/DefinitiveListService.php`
13. `app/Services/Lists/ProvisionalListService.php`
14. `app/Services/Lists/ListPublicationService.php`
15. `app/Services/Hearings/HearingService.php`
16. `app/Services/Hearings/HearingSubmissionService.php`
17. `app/Services/Complaints/ComplaintService.php`
18. `app/Services/Complaints/ComplaintDecisionService.php`
19. `app/Services/Complaints/AdditionalInformationService.php`
20. `app/Services/Administrative/AdministrativeDeadlineService.php`
21. `app/Services/Administrative/AdministrativeDecisionService.php`
22. `app/Services/Administrative/AdministrativeProcessService.php`
23. `app/Services/Administrative/AdministrativeWorkflowTransitionService.php`
24. `app/Services/Administrative/CorrectionRequestService.php`
25. `app/Services/Administrative/CorrectionResponseService.php`
26. `app/Services/AdministrativeDecision/FinalDecisionReadinessService.php`

Não avançar para o ficheiro seguinte se o anterior introduzir erros novos, regressões ou ambiguidade funcional.

---

## 4. Regras Absolutas

### 4.1. Não alterar regras de negócio

É proibido alterar:

- critérios de elegibilidade;
- pontuação;
- ordenação de ranking;
- desempates;
- seleção de candidatos;
- motor de sorteio;
- regras de atribuição;
- regras de aceitação/recusa de oferta;
- estados de lista;
- estados de candidatura;
- estados de contrato;
- regras de audiência prévia;
- regras de reclamação;
- prazos legais;
- notificações legais;
- permissões;
- policies;
- RGPD;
- auditoria;
- migrations;
- seeders.

### 4.2. Não criar atalhos PHPStan

É proibido:

- criar baseline;
- adicionar `ignoreErrors`;
- usar `@phpstan-ignore-line`;
- usar `@phpstan-ignore-next-line`;
- usar `mixed` para ocultar erro;
- usar casts forçados sem prova;
- alargar contratos para `Model|Collection|array`;
- converter enums para string por conveniência;
- alterar query funcional para satisfazer PHPStan;
- remover branches sem teste.

---

## 5. Estratégia Técnica

### 5.1. Correções permitidas

São permitidas:

- PHPDoc generics Eloquent;
- `Collection<int, Model>`;
- `Builder<Model>`;
- array shapes;
- guards locais;
- validação explícita de `Model|null`;
- normalização de enum apenas quando o cast já existe;
- helpers privados para leitura segura de enum/date;
- remoção de `?->` redundante quando provado;
- correção de return types incompatíveis;
- estreitamento de tipos antes de chamadas a services;
- tipagem de scopes Eloquent simples.

### 5.2. Correções de risco elevado

Exigem teste dirigido antes e depois:

- `method.notFound`;
- `method.nonObject`;
- `property.nonObject`;
- `identical.alwaysFalse`;
- `notIdentical.alwaysTrue`;
- `deadCode.unreachable`;
- `function.impossibleType`;
- `match.alwaysFalse`;
- enum/string mismatch;
- date/string mismatch.

### 5.3. Classificação obrigatória por erro

Cada correção deve ser classificada:

| Código | Significado |
| --- | --- |
| BR | Bug real |
| FP | Falso positivo por inferência |
| DT | Dívida técnica |
| TS | Tipagem segura |
| RF | Risco funcional |
| SR | Segurança/RGPD |
| PF | Performance |

---

## 6. Fase 1 — Baseline Local da Sprint

Executar:

```bash
php artisan optimize:clear

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-10-before.txt
```

Registar:

- total global de erros;
- erros por ficheiro prioritário;
- identificadores por domínio;
- erros em allocation;
- erros em listas;
- erros em audiência/reclamações;
- erros em workflow administrativo;
- erros de alto risco.

---

## 7. Fase 2 — Models de Allocation e Listas

### Ficheiros

```text
app/Models/Allocation.php
app/Models/AllocationRun.php
app/Models/AllocationOffer.php
app/Models/DefinitiveList.php
app/Models/DefinitiveListEntry.php
app/Models/ProvisionalList.php
app/Models/ProvisionalListEntry.php
```

### Objetivos

- Tipar relações Eloquent simples.
- Tipar scopes usados por services.
- Validar casts de enum e datas.
- Corrigir falsos positivos de `ranked()`, `eligibleForAllocation()` e `readyForContract()` apenas se forem inferência de relação.
- Corrigir nullability sem alterar lógica.

### Proibido

- alterar ranking;
- alterar ordenação;
- alterar desempates;
- alterar estados;
- alterar publicação;
- alterar elegibilidade para atribuição;
- alterar readiness para contrato.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Allocation
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Lottery
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter DefinitiveList
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter ProvisionalList
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter List
```

---

## 8. Fase 3 — Allocation Services

### Ficheiros

```text
app/Services/Allocation/AllocationOfferService.php
app/Services/Allocation/AllocationResponseService.php
app/Services/Allocation/AllocationReportService.php
app/Services/Allocation/ContractReadinessService.php
```

### Objetivos

- Corrigir `method.nonObject`, `property.nonObject`, `argument.type` e `deadCode.unreachable`.
- Validar relações obrigatórias antes de `forceFill()`, `refresh()`, `entries()` ou `label()`.
- Tipar relatórios de atribuição.
- Preservar locks, transações e transições de estado.

### Proibido

- alterar seleção;
- alterar aceitação/recusa;
- alterar sequência de ofertas;
- alterar geração de contrato;
- alterar auditoria;
- alterar notificações.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Allocation
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Offer
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter ContractReadiness
```

---

## 9. Fase 4 — List Services

### Ficheiros

```text
app/Services/Lists/DefinitiveListService.php
app/Services/Lists/ProvisionalListService.php
app/Services/Lists/ListPublicationService.php
```

### Objetivos

- Corrigir `deadCode.unreachable`, `method.unused`, `return.type`, `property.nonObject` e enum/date mismatch.
- Tipar coleções de entries.
- Validar datas de publicação e prazos.
- Manter publication pipeline íntegro.

### Proibido

- alterar geração de listas;
- alterar ordenação;
- alterar candidatos incluídos/excluídos;
- alterar prazos;
- alterar publicação;
- alterar notificações legais.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter List
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Publication
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Definitive
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Provisional
```

---

## 10. Fase 5 — Audiência Prévia e Reclamações

### Ficheiros

```text
app/Services/Hearings/HearingService.php
app/Services/Hearings/HearingSubmissionService.php
app/Services/Complaints/ComplaintService.php
app/Services/Complaints/ComplaintDecisionService.php
app/Services/Complaints/AdditionalInformationService.php
```

### Objetivos

- Corrigir nullability e return types.
- Tipar payloads de reclamação/audiência.
- Validar prazos como datas, não strings.
- Preservar submissão, decisão e resposta administrativa.

### Proibido

- alterar admissibilidade;
- alterar prazo;
- alterar estado;
- alterar decisão;
- alterar notificações;
- alterar relação com candidatura/lista.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Hearing
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Complaint
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter AdditionalInformation
```

---

## 11. Fase 6 — Workflow Administrativo

### Ficheiros

```text
app/Services/Administrative/AdministrativeDeadlineService.php
app/Services/Administrative/AdministrativeDecisionService.php
app/Services/Administrative/AdministrativeProcessService.php
app/Services/Administrative/AdministrativeWorkflowTransitionService.php
app/Services/Administrative/CorrectionRequestService.php
app/Services/Administrative/CorrectionResponseService.php
app/Services/AdministrativeDecision/FinalDecisionReadinessService.php
```

### Objetivos

- Corrigir enum/string mismatch.
- Corrigir chamadas em `Model|null`.
- Tipar transitions, deadlines e decisions.
- Validar datas com casts existentes.
- Preservar histórico processual.

### Proibido

- alterar transições;
- alterar resultado de decisão;
- alterar deadlines;
- alterar aperfeiçoamentos;
- alterar notificações;
- alterar auditoria;
- alterar admissibilidade para scoring/final decision.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Administrative
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Decision
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Correction
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Process
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Deadline
```

---

## 12. Critérios de Bloqueio

Parar e reverter o lote se ocorrer:

- aumento global de erros PHPStan;
- erro novo em segurança/RGPD/policies;
- falha de teste dirigido;
- alteração de resultado funcional;
- alteração de ranking/lista;
- alteração de seleção de candidato;
- alteração de prazos;
- alteração de estado administrativo;
- alteração de query de elegibilidade/pontuação;
- necessidade de alterar migration;
- necessidade de alterar policy;
- enum/string mismatch sem cast confirmado;
- relação obrigatória sem prova por schema, teste ou workflow.

---

## 13. Validação por Lote

Após cada bloco executar:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-10-after-<bloco>.txt

./vendor/bin/pint --test
```

Blocos:

- `models-allocation-lists`
- `allocation-services`
- `list-services`
- `hearings-complaints`
- `administrative-workflow`

---

## 14. Validação Final Obrigatória

Executar:

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml

php artisan route:list --except-vendor

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-10-final.txt
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

## 15. Artefactos a Criar

```text
docs/qa/phpstan-10-allocation-lists-administrative-workflow-hardening-report.md

storage/phpstan/phpstan-10-before.txt
storage/phpstan/phpstan-10-after-models-allocation-lists.txt
storage/phpstan/phpstan-10-after-allocation-services.txt
storage/phpstan/phpstan-10-after-list-services.txt
storage/phpstan/phpstan-10-after-hearings-complaints.txt
storage/phpstan/phpstan-10-after-administrative-workflow.txt
storage/phpstan/phpstan-10-final.txt
storage/phpstan/phpstan-10-pint-test.txt
storage/phpstan/phpstan-10-phpunit.txt
storage/phpstan/phpstan-10-route-list.txt
```

---

## 16. Relatório Final Obrigatório

O relatório final deve conter:

### 16.1. Métricas

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros PHPStan globais | X | Y | Z |
| Ficheiros com erros | X | Y | Z |
| `missingType.generics` | X | Y | Z |
| `missingType.iterableValue` | X | Y | Z |
| `argument.type` | X | Y | Z |
| `property.notFound` | X | Y | Z |
| `property.nonObject` | X | Y | Z |
| `method.nonObject` | X | Y | Z |
| `method.notFound` | X | Y | Z |
| `deadCode.unreachable` | X | Y | Z |

### 16.2. Ficheiros alterados

Listar todos os ficheiros alterados.

### 16.3. Correções por domínio

Agrupar por:

- allocation;
- lottery;
- definitive lists;
- provisional lists;
- hearings;
- complaints;
- administrative workflow.

### 16.4. Bugs reais encontrados

Classificar:

| Código | Tipo |
| --- | --- |
| BR | Bug real |
| FP | Falso positivo |
| DT | Dívida técnica |
| RF | Risco funcional |
| SR | Segurança/RGPD |
| PF | Performance |

### 16.5. Correções adiadas

Listar:

- ficheiro;
- erro;
- motivo;
- risco;
- sprint recomendada.

### 16.6. Testes executados

Listar:

- comando;
- resultado;
- número de testes;
- número de asserções.

### 16.7. Riscos residuais

Listar riscos em:

- Policies;
- RGPD;
- Audit;
- Finance;
- Maintenance;
- Inspections;
- Public Portal;
- Controllers;
- remaining models.

---

## 17. Meta da Sprint

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
< 1300 erros PHPStan globais
```

Sem regressões funcionais.

---

## 18. Próxima Sprint Prevista

## PHPSTAN-11 — Policies, Controllers & Candidate Workflow Hardening

Foco previsto:

- `app/Policies/*`
- controllers de candidato;
- controllers de backoffice com `argument.type`;
- workflows de candidato;
- documentos privados;
- household/current housing;
- submissão e reutilização de dados;
- permissões e ownership.

A PHPSTAN-11 só deve iniciar após a PHPSTAN-10 concluir com:

- PHPUnit verde;
- Pint verde;
- PHPStan reduzido;
- 0 erros novos;
- sem alterações funcionais;
- sem suppressions;
- sem baseline.
