# SPRINT PHPSTAN-14 — Scoring, Eligibility, Lottery & Contest Domain Hardening

**Projeto:** CRM MV HAB  
**Framework:** Laravel 13.8+  
**PHP:** 8.4  
**Data:** 2026-06-23  
**Âmbito:** Pontuação, elegibilidade, ranking, desempates, sorteios, concursos e consistência entre simulador/candidatura.

---

## 1. Objetivo

Executar uma remediação PHPStan conservadora, auditável e sem regressões nos domínios remanescentes mais sensíveis:

- Scoring
- Ranking
- Tie Breakers
- Eligibility
- Simulation consistency
- Lottery
- Contests
- Application Scores
- Eligibility Checks
- Procedure Templates

Esta sprint sucede à PHPSTAN-13, que reduziu os erros globais de `872` para `708`, deixou Public Portal, Reports, Dashboard, Timeline e DocumentAi sem erros diretos e confirmou que os erros remanescentes estão concentrados em scoring, elegibilidade, lottery, contests e services de domínio.

---

## 2. Estado de Partida

Após PHPSTAN-13:

| Métrica | Valor |
| --- | ---: |
| Erros PHPStan globais | 708 |
| Ficheiros com erros | 233 |
| Erros removidos na PHPSTAN-13 | 163 |
| Erros novos por assinatura exata | 0 |
| PHPUnit direto com memory_limit=-1 | OK — 283 testes / 1775 asserções |
| Pint | OK |
| Rotas | OK — 1086 rotas |

Principais identificadores remanescentes:

| Identificador | Quantidade |
| --- | ---: |
| `missingType.generics` | 331 |
| `missingType.iterableValue` | 94 |
| `argument.type` | 46 |
| `nullsafe.neverNull` | 30 |
| `method.nonObject` | 28 |
| `property.notFound` | 28 |
| `property.nonObject` | 24 |
| `return.type` | 21 |
| `notIdentical.alwaysTrue` | 17 |
| `identical.alwaysFalse` | 13 |

Ficheiros com maior concentração residual:

| Ficheiro | Erros |
| --- | ---: |
| `app/Services/Scoring/RankingService.php` | 15 |
| `app/Services/CandidateExperience/ApplicationSimulationConsistencyService.php` | 13 |
| `app/Models/LotteryRun.php` | 12 |
| `app/Models/ApplicationScore.php` | 11 |
| `app/Models/ScoringRuleSet.php` | 10 |
| `app/Services/Contests/ContestService.php` | 10 |
| `app/Services/Eligibility/EligibilityEngine.php` | 10 |
| `app/Models/EligibilityCheck.php` | 9 |
| `app/Services/ProcedureTemplates/TemplateVariableResolver.php` | 9 |
| `app/Services/Scoring/ApplicationScoreService.php` | 9 |
| `app/Services/Scoring/TieBreakerService.php` | 9 |

---

## 3. Observação Operacional Obrigatória

`php artisan test` não é fiável neste ambiente porque o processo filho fica limitado a `128MB`.

A validação funcional oficial deve usar:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

O comando `php artisan test` pode ser registado como risco operacional, mas não deve bloquear a sprint enquanto o PHPUnit direto com `memory_limit=-1` passar.

---

## 4. Regras Absolutas

Não alterar:

- fórmulas de elegibilidade;
- fórmulas de pontuação;
- pesos de critérios;
- ordem de ranking;
- desempates;
- critérios de exclusão;
- limites de rendimento;
- regras de tipologia;
- regras de simulação;
- regras de sorteio;
- seleção de candidatos;
- estados de concurso;
- estados de candidatura;
- publicação de listas;
- contratos;
- rendas;
- RGPD;
- auditoria;
- policies;
- permissões.

Não usar:

- baseline;
- `ignoreErrors`;
- `@phpstan-ignore-line`;
- `@phpstan-ignore-next-line`;
- `mixed` para esconder erro;
- casts forçados sem prova;
- alterações de schema;
- alterações de seeders;
- alterações de migrations;
- alterações de dependências.

Manter obrigatoriamente:

```text
erros novos por assinatura exata = 0
```

---

## 5. Estratégia Técnica

Atuar por ordem estrita:

1. Models de scoring/elegibilidade/lottery.
2. Services de scoring/ranking/desempate.
3. Eligibility engine.
4. Simulation consistency.
5. Contest service.
6. Procedure template resolver.
7. Correções adjacentes apenas se necessárias para `exact_new = 0`.

São permitidas:

- PHPDoc generics Eloquent;
- `@use HasFactory<...>`;
- `Collection<int, Model>`;
- `Builder<Model>`;
- array shapes;
- return types documentais;
- guards explícitos antes de relações opcionais;
- normalização de enum quando o cast já existe;
- remoção de `?->` redundante quando provado;
- estreitamento de tipos antes de chamadas a services;
- helpers privados de leitura segura de enum/data.

Exigem teste antes/depois:

- `identical.alwaysFalse`;
- `notIdentical.alwaysTrue`;
- `method.nonObject`;
- `property.nonObject`;
- `method.notFound`;
- `property.notFound`;
- `argument.type`;
- enum/string mismatch;
- date/string mismatch;
- ranking/sorting logic.

---

## 6. Fase 1 — Baseline Local da Sprint

```bash
php artisan optimize:clear

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-14-before.txt
```

Registar:

- total global de erros;
- ficheiros afetados;
- distribuição por identificador;
- erros por ficheiro alvo;
- erros exatos da baseline.

---

## 7. Fase 2 — Models de Scoring, Eligibility e Lottery

### Ficheiros

```text
app/Models/ApplicationScore.php
app/Models/ScoringRuleSet.php
app/Models/EligibilityCheck.php
app/Models/LotteryRun.php
app/Models/LotteryEntry.php
app/Models/ScoringCriterion.php
app/Models/ScoringRule.php
app/Models/ContestCriterion.php
```

### Objetivos

- Adicionar generics Eloquent nas relações simples.
- Tipar scopes com `Builder<self>`.
- Documentar casts enum/datetime já existentes.
- Corrigir `missingType.generics`.
- Corrigir `method.nonObject` apenas quando a causa for cast/datetime comprovado.

### Proibido

- alterar critérios;
- alterar pesos;
- alterar regra de elegibilidade;
- alterar estados;
- alterar sorteio;
- alterar seleção;
- alterar persistência.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Scoring
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Score
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Eligibility
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Lottery
```

---

## 8. Fase 3 — Ranking e Pontuação

### Ficheiros

```text
app/Services/Scoring/RankingService.php
app/Services/Scoring/ApplicationScoreService.php
app/Services/Scoring/TieBreakerService.php
app/Services/Scoring/ScoringEngine.php
app/Services/Scoring/ScoringRuleSetService.php
```

### Objetivos

- Corrigir `missingType.iterableValue`, `argument.type`, `return.type` e `property.nonObject`.
- Tipar coleções de candidatos/list entries/scores.
- Tipar payloads de ranking.
- Normalizar enums sem alterar valores.
- Confirmar que ordenação e desempate permanecem iguais.

### Proibido

- alterar `sortBy`, `sortByDesc`, `orderBy`, `rank`, `score`;
- alterar desempates;
- alterar fórmula;
- alterar arredondamentos;
- alterar resultado final;
- alterar exclusões/inclusões.

### Testes obrigatórios antes e depois

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Ranking
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter TieBreaker
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Scoring
```

Se não existirem testes nominais, documentar a lacuna e executar a suite global.

---

## 9. Fase 4 — Elegibilidade

### Ficheiros

```text
app/Services/Eligibility/EligibilityEngine.php
app/Services/Eligibility/EligibilityRuleEvaluator.php
app/Services/Eligibility/EligibilityResultBuilder.php
app/Services/Eligibility/EligibilityCheckService.php
```

### Objetivos

- Tipar arrays de resultado.
- Corrigir nullability de relações usadas no cálculo.
- Normalizar leitura de enum/casts.
- Preservar integralmente o resultado `eligible/ineligible`.
- Alinhar payloads com o simulador sem alterar regras.

### Proibido

- alterar limites de rendimento;
- alterar exclusões;
- alterar tipologia;
- alterar validações de agregado;
- alterar validações documentais;
- alterar impedimentos;
- alterar resultado de elegibilidade.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Eligibility
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Simulator
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Application
```

---

## 10. Fase 5 — Consistência Simulador/Candidatura

### Ficheiro

```text
app/Services/CandidateExperience/ApplicationSimulationConsistencyService.php
```

### Objetivos

- Corrigir `missingType.iterableValue`, `argument.type` e `return.type`.
- Tipar payloads de comparação.
- Garantir null-safety em dados de simulação.
- Manter apenas comparação/diagnóstico, sem bloquear ou desbloquear fluxos.

### Proibido

- alterar regra que condiciona candidatura;
- alterar resultado do simulador;
- alterar workflow de submissão;
- alterar mensagens de decisão regulamentar.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Simulation
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Simulator
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Candidate
```

---

## 11. Fase 6 — Concursos

### Ficheiro

```text
app/Services/Contests/ContestService.php
```

### Objetivos

- Corrigir generics e arrays.
- Tipar payloads de criação/atualização.
- Corrigir nullability sem alterar estado.
- Preservar abertura, encerramento, publicação e prazos.

### Proibido

- alterar regras de concurso;
- alterar datas;
- alterar publicação;
- alterar associação de habitações;
- alterar editais;
- alterar critérios;
- alterar notificações legais.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contest
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Public
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Application
```

---

## 12. Fase 7 — Procedure Templates

### Ficheiro

```text
app/Services/ProcedureTemplates/TemplateVariableResolver.php
```

### Objetivos

- Corrigir `argument.type`, `property.notFound`, `missingType.iterableValue`.
- Tipar variáveis de template.
- Validar relações antes de resolver dados de candidatura/contrato/concurso.
- Preservar nomes das variáveis existentes.

### Proibido

- alterar minutas;
- alterar placeholders públicos;
- alterar textos legais;
- alterar geração de documentos;
- alterar dados de decisão.

### Testes dirigidos

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Procedure
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Template
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Document
```

---

## 13. Validação por Lote

Após cada fase executar:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-14-after-<bloco>.txt

./vendor/bin/pint --test
```

Blocos:

- `models`
- `scoring`
- `eligibility`
- `simulation`
- `contests`
- `templates`

---

## 14. Validação Final Obrigatória

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml

php artisan route:list --except-vendor

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-14-final.txt
```

Resultado obrigatório:

- Pint OK;
- PHPUnit direto OK;
- route list OK;
- PHPStan com redução líquida;
- 0 erros novos por assinatura exata;
- sem alterações funcionais;
- sem suppressions;
- sem baseline.

---

## 15. Artefactos a Criar

```text
docs/qa/phpstan-14-scoring-eligibility-lottery-contests-report.md

storage/phpstan/phpstan-14-before.txt
storage/phpstan/phpstan-14-after-models.txt
storage/phpstan/phpstan-14-after-scoring.txt
storage/phpstan/phpstan-14-after-eligibility.txt
storage/phpstan/phpstan-14-after-simulation.txt
storage/phpstan/phpstan-14-after-contests.txt
storage/phpstan/phpstan-14-after-templates.txt
storage/phpstan/phpstan-14-final.txt
storage/phpstan/phpstan-14-summary.txt
storage/phpstan/phpstan-14-phpunit.txt
storage/phpstan/phpstan-14-directed-tests.txt
storage/phpstan/phpstan-14-pint-final.txt
storage/phpstan/phpstan-14-route-list.txt
```

---

## 16. Relatório Final Obrigatório

O relatório deve conter:

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros PHPStan globais | X | Y | Z |
| Ficheiros com erros | X | Y | Z |
| Erros removidos por assinatura exata | X | Y | Z |
| Erros novos por assinatura exata | 0 | 0 | 0 |
| `missingType.generics` | X | Y | Z |
| `missingType.iterableValue` | X | Y | Z |
| `argument.type` | X | Y | Z |
| `method.nonObject` | X | Y | Z |
| `property.nonObject` | X | Y | Z |
| `return.type` | X | Y | Z |

Também incluir:

- ficheiros alterados por domínio;
- testes executados;
- filtros sem testes encontrados;
- bugs reais encontrados;
- falsos positivos;
- correções adiadas;
- riscos residuais em Security, RGPD, Audit, Policies, remaining models, seeders demo, integrations e CI/CD memory limit.

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
< 500 erros PHPStan globais
```

Sem regressões funcionais.

---

## 18. Próxima Sprint Prevista

## PHPSTAN-15 — Security, RGPD, Audit & Policies Final Hardening

Foco previsto:

- `app/Services/Security/*`
- `app/Services/Rgpd/*`
- `app/Services/Audit/*`
- `app/Policies/*`
- `AccessLog`
- `SensitiveDataAccessLog`
- `DataSubjectRequest`
- `DataExportPackage`
- `UserConsent`
- `RetentionPolicy`
- `RetentionExecution`
- MFA
- consentimentos
- anonimização
- exportação de dados

A PHPSTAN-15 só deve iniciar após a PHPSTAN-14 concluir com:

- PHPUnit direto verde;
- Pint verde;
- route list OK;
- PHPStan reduzido;
- 0 erros novos por assinatura exata;
- sem alterações funcionais;
- sem suppressions;
- sem baseline.
