# Sprint 46B — Liquidação da dívida Pint

## Identificação

- ID: `TECH-QUALITY-001`
- Branch: `sprint-46b-pint-quality-gate`
- Base validada: `610138a8ce35a29f5f4d5bf95543fbbdc58591c9`
- Objetivo: eliminar a dívida Pint global e estabelecer um gate incremental seguro.

## Baseline

O baseline real confirmou 65 ficheiros com diferenças Pint. O inventário completo está em
`docs/quality/tech-quality-001-pint-inventory.md`.

Distribuição:

| Grupo | Âmbito | Ficheiros |
|---|---|---:|
| 1 | Controllers e Form Requests | 8 |
| 2 | Serviços documentais e processuais | 7 |
| 3 | Serviços de Dashboard e Agenda | 27 |
| 4 | Testes | 23 |
| **Total** |  | **65** |

Não existiam migrations, models, policies, factories, seeders, scripts ou comandos na dívida
herdada. Por esse motivo, a validação especial de schema/rollback de migrations não era
aplicável.

## Formatação mecânica

Cada grupo foi formatado, revisto, validado com `php -l` e commitado isoladamente:

- `df9fbe4c` — `style(http): normalizar controllers e form requests`
- `78de5368` — `style(services): normalizar serviços documentais e processuais`
- `0bec498f` — `style(dashboard): normalizar serviços de dashboard e agenda`
- `401a8596` — `style(tests): normalizar testes herdados`

Os diffs ficaram limitados a imports, braces, alinhamento, espaços, quebras de linha,
trailing commas, PHPDoc e remoção de parênteses em construtores sem argumentos. Não foram
alteradas condições, queries, rotas, assinaturas públicas, regras de negócio ou asserções.

Os 23 testes diretamente formatados passaram em conjunto:

- 108 testes;
- 581 asserções;
- 0 falhas.

## Gate Pint incremental

Foi criado `scripts/quality/pint-changed.php` e adicionados os comandos Composer:

- `composer quality:pint`
- `composer quality:pint:changed -- <base-ref>`

O gate incremental:

- exige base explícita, variável de ambiente ou `git config quality.baseRef`;
- respeita a precedência argumento, `QUALITY_BASE_REF`, `GITHUB_BASE_REF` e configuração;
- falha com mensagem clara quando não existe base;
- calcula o merge-base;
- usa `git diff --name-only --diff-filter=ACMR -z`;
- seleciona apenas ficheiros PHP existentes;
- preserva nomes com espaços;
- executa `vendor/bin/pint --test` em blocos;
- lista os ficheiros verificados;
- propaga o exit code do Pint.

Não existe fallback silencioso para `origin/main`.

O teste `tests/Unit/Quality/PintChangedScriptTest.php` cobre seleção NUL-safe, nomes com
espaços, chunking, base inexistente e precedência das fontes de configuração:

- 5 testes;
- 41 asserções;
- 0 falhas.

## Integração contínua

O workflow existente `.github/workflows/quality.yml` foi reforçado, sem criar pipeline
paralela:

- checkout com histórico completo (`fetch-depth: 0`);
- pull requests executam o gate incremental com o SHA-base explícito;
- o Pint global continua obrigatório;
- instalações, versões de PHP/Node e restantes gates foram preservados.

## Validações executadas

| Validação | Resultado |
|---|---|
| `composer quality:pint:changed -- 610138a8...` | PASS — 67 ficheiros PHP |
| `composer quality:pint` | PASS — dívida global zero |
| PHPUnit nos testes formatados | PASS — 108 testes / 581 asserções |
| PHPUnit completo | PASS — 1 034 testes / 7 333 asserções |
| Testes unitários do gate | PASS — 5 testes / 41 asserções |
| PHPStan no script e teste novos | PASS — 0 erros |
| `composer validate --strict` | PASS |
| `npm run build` | PASS |
| `git diff --check` | PASS |
| Catálogo de rotas | Inalterado — 1 165 total / 1 162 sem vendor |
| Auditoria de rotas | Inalterada após remover `generated_at` |

Métricas de acesso preservadas:

- `fixed_role_routes`: 926;
- `backoffice_fixed_role_routes`: 706;
- `candidate_fixed_role_routes`: 220;
- `permission_middleware_routes`: 195;
- backoffice sem `active.backoffice`: 594;
- backoffice sem `mfa.backoffice`: 594;
- backoffice sem `log.backoffice`: 594.

## PHPStan

A Sprint 46B não alterou tipos nem comportamento. O PHPStan mantém a dívida herdada,
sem novos ficheiros ou variação na distribuição normalizada:

- `AuditAccessRoutes.php`: 8;
- `TimelineEvent.php`: 2;
- `DocumentReviewController.php`: 15;
- `AgendaController.php`: 1;
- `ProcedureMinuteController.php`: 1;
- `SimulationController.php`: 1;
- `FavoriteController.php`: 1;
- `StoreCandidateSimulationRequest.php`: 1.

Esta dívida é o âmbito explícito da Sprint 46C.

## Riscos residuais

- `GITHUB_BASE_REF` pode ser apenas um nome de branch em execuções locais; no workflow de
  pull request é usado `QUALITY_BASE_REF` com o SHA-base, eliminando ambiguidade.
- O gate incremental verifica alterações commitadas entre o merge-base e `HEAD`; o Pint
  global continua obrigatório e cobre o repositório completo.

## Decisão

**PASS**

A dívida Pint global foi eliminada, o gate incremental foi testado e integrado na CI, a
suite completa permanece verde e não foi identificada qualquer alteração funcional.
