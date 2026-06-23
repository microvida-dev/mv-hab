# SPRINT PHPSTAN-04 — DOMAIN LOGIC VALIDATION & HIDDEN BUG DISCOVERY

## Objetivo

Transformar a dívida PHPStan remanescente num mecanismo de descoberta controlada de bugs reais, estados impossíveis, inconsistências de domínio e riscos funcionais da plataforma MV HAB.

Este sprint já não tem como objetivo principal reduzir volume de erros. O objetivo principal é **provar, classificar e priorizar defeitos reais** antes de qualquer correção estrutural.

A plataforma encontra-se após:

- **PHPSTAN-01:** 2897 erros inventariados em 561 ficheiros.
- **PHPSTAN-02:** redução para 2828 erros, com foco em generics Eloquent seguros.
- **PHPSTAN-03:** redução para 2755 erros, com foco em Form Requests, arrays, return types simples, Reporting e factories.

A partir daqui, qualquer intervenção pode tocar em lógica crítica. Por isso, a execução deve ser extremamente conservadora.

---

## Estado Atual de Referência

### Métricas pós-PHPSTAN-03

| Indicador | Valor |
| --- | ---: |
| Total PHPStan remanescente | 2755 |
| Ficheiros afetados | 493 |
| `missingType.generics` | 1093 |
| `missingType.iterableValue` | 314 |
| `property.notFound` | 284 |
| `argument.type` | 249 |
| `property.nonObject` | 123 |
| `method.nonObject` | 93 |
| `nullsafe.neverNull` | 80 |
| `return.type` | 64 |
| `deadCode.unreachable` | 51 |
| `notIdentical.alwaysTrue` | 46 |

### Domínios com maior dívida remanescente

| Domínio | Erros |
| --- | ---: |
| Models | 1082 |
| Services — Outros | 768 |
| Contracts/Finance | 177 |
| Reporting | 133 |
| Scoring | 104 |
| Allocation | 94 |
| Documents | 85 |
| Eligibility | 62 |
| Document Intelligence | 51 |
| Controllers | 46 |

---

## Princípio Central

Não corrigir automaticamente.

A ordem obrigatória é:

1. Compreender.
2. Reproduzir.
3. Classificar.
4. Provar com teste ou análise.
5. Propor correção.
6. Corrigir apenas quando o risco estiver controlado.
7. Validar com testes dirigidos.

Se não for possível provar o impacto, **não corrigir**.

---

## Regras Absolutas

Não alterar sem teste dirigido:

- Elegibilidade.
- Pontuação.
- Classificação.
- Critérios de concurso.
- Submissão de candidaturas.
- Validação documental.
- Listas provisórias/definitivas.
- Audiência prévia.
- Reclamações.
- Sorteios.
- Atribuições.
- Contratos.
- Rendas.
- Pagamentos.
- RGPD.
- Auditoria.
- Policies.
- Permissões.
- Documentos privados.
- Logs sensíveis.

Não usar:

- `@phpstan-ignore`.
- Baseline para esconder erros.
- Widening artificial de tipos.
- Casts inseguros para calar PHPStan.
- Alterações massivas.
- Refactors arquiteturais não pedidos.
- Alterações de schema.
- Alterações de seeders.
- Alterações de migrations.

---

## Âmbito Técnico Prioritário

Este sprint deve analisar, por ordem:

1. `method.notFound`
2. `property.nonObject`
3. `method.nonObject`
4. `classConstant.notFound`
5. `offsetAccess.nonOffsetAccessible`
6. `foreach.nonIterable`
7. `binaryOp.invalid`
8. `match.alwaysFalse`
9. `identical.alwaysFalse`
10. `booleanAnd.alwaysFalse`
11. `booleanOr.alwaysTrue`
12. `function.impossibleType`
13. `argument.type` em domínios críticos
14. `return.type` em domínios críticos

---

# FASE 0 — Preparação e Baseline Técnico

Executar:

```bash
php artisan optimize:clear
./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-04-before.txt
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Nota operacional:

`php artisan test` não é fiável neste ambiente por limite efetivo de memória de 128 MB. Usar o comando PHPUnit direto como baseline de validação.

Criar artefacto:

```text
storage/phpstan/phpstan-04-before.txt
```

---

# FASE 1 — Inventário de Bugs Prováveis

Criar uma lista dos erros remanescentes com maior probabilidade de bug real.

Prioridade absoluta para:

## A. Métodos ou scopes inexistentes

Exemplos já identificados:

```text
readyForContract()
eligibleForAllocation()
ranked()
publiclyVisible()
entries()
withTrashed()
```

Analisar se são:

- scopes realmente em falta;
- métodos existentes mas não inferidos por Larastan;
- relações mal tipadas;
- chamadas em relação errada;
- bugs reais em workflows de atribuição/listas/contratos.

## B. Constantes inexistentes

Exemplo já identificado:

```text
DocumentDossierStatus::Generated
```

Determinar se:

- a constante deveria existir;
- o enum mudou e o código ficou legado;
- o estado correto é outro;
- há testes ausentes para geração de dossiês documentais.

## C. Chamadas em string onde se espera Carbon

Exemplos de risco:

```text
isFuture()
isPast()
lte()
gte()
toIso8601String()
```

Validar casts em Models:

- datas de reclamações;
- prazos de correção;
- visitas;
- documentos públicos;
- listas;
- notificações;
- contratos.

## D. Enum/string mismatch

Exemplos de risco:

```text
Strict comparison using === between string and App\Enums\...
Match arm comparison between string and App\Enums\...
in_array() com string contra array de enums
```

Validar:

- casts de enum nos Models;
- uso de `$enum->value`;
- comparações antigas;
- estados persistidos na base de dados;
- transições de workflow.

## E. Null safety em relações críticas

Exemplos:

```text
Cannot access property on Model|null
Cannot call method forceFill() on Model|null
Cannot call method refresh() on Model|null
```

Validar se o domínio permite null ou se falta invariant.

---

# FASE 2 — Classificação Formal

Para cada erro analisado, classificar:

| Código | Tipo | Descrição |
| --- | --- | --- |
| BR | Bug real | Código pode falhar em runtime ou produzir resultado errado |
| FP | Falso positivo | Larastan não infere corretamente, mas runtime está seguro |
| CD | Código morto | Ramo nunca executável ou legado abandonado |
| RI | Regra impossível | Condição contraditória ou workflow impossível |
| PD | Programação defensiva | Guarda redundante mas intencional |
| DT | Dívida técnica | Código ambíguo ou frágil, mas sem bug provado |
| RF | Risco funcional | Pode afetar decisão/processo, exige teste |
| SR | Segurança/RGPD | Pode afetar acesso, privacidade, auditoria ou dados pessoais |

---

# FASE 3 — Matriz de Severidade

Classificar severidade por impacto real:

| Severidade | Critério |
| --- | --- |
| P0 — Crítico | Segurança, RGPD, documentos privados, auditoria, permissões ou decisão administrativa incorreta |
| P1 — Alto | Elegibilidade, pontuação, listas, atribuição, contratos, rendas ou workflows core |
| P2 — Médio | Funcionalidade operacional relevante, mas com contorno ou baixo impacto jurídico |
| P3 — Baixo | Código morto, redundância, inferência estática, UX administrativa secundária |

---

# FASE 4 — Auditoria dos Workflows Core

## Fluxo A — Registo → Agregado → Elegibilidade → Candidatura

Verificar:

- duplicação de candidaturas;
- registo sem agregado;
- candidatura sem simulador válido;
- dados reutilizados incorretamente;
- transições impossíveis;
- enums comparados como string;
- policies a permitir alteração após submissão.

Testes esperados, se for corrigido algo:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Application
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Eligibility
```

## Fluxo B — Documentos → Validação → Aperfeiçoamento

Verificar:

- upload órfão;
- documento privado exposto;
- download sem auditoria;
- substituição sem versionamento;
- checklist inconsistente;
- pedidos de aperfeiçoamento sobre processos inexistentes;
- casts de datas em prazos.

Testes esperados:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Document
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Correction
```

## Fluxo C — Pontuação → Lista Provisória → Audiência Prévia → Lista Definitiva

Verificar:

- ordenação incorreta;
- desempate não determinístico;
- pontuação recalculada após publicação;
- lista publicada com entradas inválidas;
- audiência fora de prazo;
- enum/string mismatch em estados de scoring/listas.

Testes esperados:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Scoring
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter List
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Hearing
```

## Fluxo D — Sorteio → Atribuição → Oferta → Contrato

Verificar especialmente:

```text
LotteryService
AllocationEngine
AllocationOfferService
AllocationRun
DefinitiveList
DefinitiveListEntry
Application::eligibleForAllocation()
Application::readyForContract()
```

Validar:

- scopes inexistentes;
- atribuição duplicada;
- candidatura elegível não considerada;
- lista definitiva sem entries válidas;
- transações ausentes;
- race conditions;
- estados de allocation comparados incorretamente;
- contrato criado sem oferta válida.

Testes esperados:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Allocation
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Lottery
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contract
```

## Fluxo E — Contrato → Renda → Pagamento → Área do Inquilino

Verificar:

- contrato sem conta financeira;
- fatura emitida sem contrato válido;
- recibo com path nulo;
- pagamento associado a invoice errada;
- estado financeiro inválido;
- pagamento não auditado;
- exposição indevida ao inquilino.

Testes esperados:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contract
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Tenant
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Payment
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Finance
```

## Fluxo F — RGPD → Auditoria → Segurança

Este fluxo deve ser sobretudo de análise nesta sprint.

Verificar:

- pedidos de titular associados a utilizador errado;
- exportação de dados com `Model|null`;
- hash sobre `string|false`;
- acesso sensível sem subject correto;
- retenção/anonymization com enums mal comparados;
- MFA recovery codes com parsing inseguro;
- logs sensíveis sem tipo correto.

Não corrigir automaticamente sem teste e validação funcional.

Testes esperados:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Audit
```

---

# FASE 5 — Correções Permitidas

Só são permitidas correções quando cumprirem todos os critérios:

- bug real reproduzido ou provado;
- impacto funcional entendido;
- alteração mínima;
- sem alteração de regra de negócio;
- teste novo ou existente cobre o caso;
- PHPStan reduz ou mantém sem novos identificadores;
- Pint passa;
- PHPUnit passa.

Correções permitidas:

## 1. Casts corretos em Models

Exemplo:

```php
protected function casts(): array
{
    return [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'status' => SomeStatus::class,
    ];
}
```

Apenas quando:

- a coluna existe;
- o tipo é consistente com usage;
- não altera formato esperado em views/services;
- há teste dirigido.

## 2. Comparação correta de enums

Exemplo:

```php
$status === SomeStatus::Active
```

ou:

```php
$status === SomeStatus::Active->value
```

Escolher apenas o padrão compatível com o cast real do Model.

## 3. Guards explícitos

Exemplo:

```php
if (! $model instanceof ExpectedModel) {
    throw new DomainException('...');
}
```

Usar apenas quando o domínio exige presença obrigatória.

## 4. Tipagem de relações necessária para provar bug

Permitida quando corrige falso positivo e não altera query.

## 5. Correção de constante inexistente

Permitida apenas após confirmar enum real e criar teste.

## 6. Correção de scopes inexistentes

Permitida apenas quando:

- há chamada existente em workflow;
- o scope é claramente necessário;
- o comportamento esperado é dedutível;
- existe teste de regressão.

---

# FASE 6 — Correções Proibidas

Não executar neste sprint:

- refatorar serviços inteiros;
- alterar algoritmos de scoring;
- alterar regras de elegibilidade;
- mudar estados ou nomes de enums sem migration/compatibilidade;
- remover código morto sem teste;
- alterar permissões para “fazer passar” teste;
- transformar erros em `mixed`;
- adicionar `?` apenas para calar PHPStan;
- substituir exceções por `return null`;
- criar baseline PHPStan;
- usar suppressions.

---

# FASE 7 — Auditoria de Segurança Funcional

Analisar, mesmo que sem corrigir:

## Policies

- policies que acedem a `Model|null`;
- policies que tratam enum como string;
- policies que podem permitir alteração após submissão;
- policies que podem permitir acesso a documentos de outro candidato;
- policies sobre relatórios e exports.

## Documentos

- download sempre via controller autorizado;
- storage privado;
- logs de acesso sensível;
- versionamento preservado;
- pedidos de aperfeiçoamento associados ao candidato/processo correto.

## Reporting/Exports

- exports com autorização explícita;
- filtros normalizados;
- dados pessoais minimizados;
- proteção contra CSV injection preservada;
- auditoria de download mantida.

## RGPD

- subject correto em logs;
- exportação de dados validada;
- anonymization sem estado contraditório;
- retention policies sem execução indevida.

---

# FASE 8 — Auditoria de Performance Funcional

Identificar, mas não refatorar em massa:

- N+1 em dashboards;
- queries de reports sem paginação;
- exports com collections gigantes;
- loops sobre candidaturas/documentos sem chunking;
- recalculações de scoring sem cache/materialização;
- listas recalculadas após publicação;
- logs de auditoria sem índices suficientes;
- relações carregadas implicitamente em loops.

Criar Top 20 melhorias potenciais com:

| Ficheiro | Problema | Impacto | Recomendação | Sprint futura |
| --- | --- | --- | --- | --- |

---

# FASE 9 — Validação Final

Executar:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-04-final.txt
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Se houver alterações frontend, executar também:

```bash
npm run build
```

Gerar relatório:

```text
docs/qa/phpstan-04-domain-logic-validation-report.md
```

---

## Entregáveis

Criar relatório final em Markdown com:

1. Resumo executivo.
2. Total PHPStan antes/depois.
3. Lista de erros analisados.
4. Lista de bugs reais confirmados.
5. Lista de falsos positivos.
6. Lista de estados impossíveis.
7. Lista de código morto provável.
8. Lista de riscos P0/P1.
9. Correções executadas.
10. Correções recusadas/adiadas.
11. Testes criados/atualizados.
12. Testes executados.
13. Impacto por workflow.
14. Impacto em segurança/RGPD.
15. Impacto em performance.
16. Riscos residuais.
17. Plano recomendado para PHPSTAN-05.

---

## Template de Registo por Erro

Usar este formato no relatório:

| Campo | Valor |
| --- | --- |
| Ficheiro |  |
| Linha |  |
| Identificador PHPStan |  |
| Domínio |  |
| Severidade | P0/P1/P2/P3 |
| Classificação | BR/FP/CD/RI/PD/DT/RF/SR |
| Causa raiz |  |
| Impacto funcional |  |
| Evidência |  |
| Decisão | Corrigido / Adiado / Recusado / Requer revisão |
| Teste associado |  |
| Risco residual |  |

---

## Critérios de Sucesso

O sprint só é considerado concluído se:

- os erros de alto risco forem classificados;
- os bugs reais prováveis forem analisados individualmente;
- qualquer correção tiver teste dirigido;
- não houver alteração não justificada em regra de negócio;
- não houver permissões alargadas;
- não houver perda de auditoria;
- não houver exposição de documentos privados;
- Pint passar;
- PHPUnit passar;
- PHPStan não introduzir novos identificadores;
- relatório final for produzido.

---

## Critérios de Bloqueio

Interromper o sprint se:

- uma correção alterar comportamento de elegibilidade;
- uma correção alterar scoring/classificação;
- uma correção alterar permissões;
- uma correção tocar RGPD sem teste específico;
- uma correção tocar documentos privados sem teste específico;
- PHPStan aumentar em identificadores de alto risco;
- PHPUnit falhar;
- Pint falhar;
- existir dúvida sobre impacto jurídico/administrativo.

---

## Estratégia Recomendada de Execução

Executar em micro-lotes:

### Lote 1 — Bugs óbvios e isolados

- constante inexistente;
- parsing inseguro;
- path nulo em download;
- casts de data claramente em falta.

### Lote 2 — Enum/string mismatch em Models

- AdhesionRegistration;
- Application;
- ApplicationScore;
- AdministrativeProcess;
- ScoringRuleSet;
- Allocation.

### Lote 3 — Scopes/métodos Eloquent em workflows

- `eligibleForAllocation()`;
- `readyForContract()`;
- `ranked()`;
- `publiclyVisible()`;
- `entries()`.

### Lote 4 — Null safety em services administrativos

- correction requests;
- correction responses;
- administrative decisions;
- timeline/process tracking.

### Lote 5 — RGPD/Security apenas análise

- produzir recomendações e testes necessários;
- não corrigir sem validação explícita.

---

## Próximo Sprint Recomendado

Após PHPSTAN-04, avançar para:

# SPRINT PHPSTAN-05 — SECURITY, RGPD, POLICIES & AUDIT HARDENING

Objetivo:

- corrigir erros críticos em RGPD;
- validar policies;
- reforçar auditoria;
- proteger documentos e exports;
- garantir que nenhuma correção amplia permissões;
- criar testes específicos para segurança e privacidade.

