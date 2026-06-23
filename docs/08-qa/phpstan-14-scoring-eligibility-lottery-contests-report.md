# Relatório PHPSTAN-14 — Scoring, Eligibility, Lottery & Contests

## Resumo executivo

A sprint PHPSTAN-14 foi executada com foco conservador em Scoring, Eligibility, Lottery, Contests, consistência simulador/candidatura e templates processuais.

Resultado final:

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros PHPStan globais | 708 | 544 | -164 |
| Ficheiros com erros | 233 | 208 | -25 |
| Erros removidos por assinatura exata | 0 | 164 | +164 |
| Erros novos por assinatura exata | 0 | 0 | 0 |
| `missingType.generics` | 331 | 255 | -76 |
| `missingType.iterableValue` | 94 | 89 | -5 |
| `argument.type` | 46 | 24 | -22 |
| `method.nonObject` | 28 | 26 | -2 |
| `property.nonObject` | 24 | 17 | -7 |
| `return.type` | 21 | 20 | -1 |

O PHPStan continua a devolver código 1 porque permanecem 544 erros legados fora do conjunto remediado nesta sprint.

## Ficheiros alterados por domínio

### Scoring, ranking e desempates

- `app/Models/ApplicationScore.php`
- `app/Models/RankingEntry.php`
- `app/Models/RankingSnapshot.php`
- `app/Models/ScoringCriterion.php`
- `app/Models/ScoringRule.php`
- `app/Models/ScoringRuleSet.php`
- `app/Models/ScoringRun.php`
- `app/Models/TieBreakerRule.php`
- `app/Services/Scoring/RankingService.php`
- `app/Services/Scoring/ScoringEngine.php`
- `app/Services/Scoring/TieBreakerService.php`
- `app/Http/Controllers/Backoffice/ScoringCriterionController.php`
- `app/Http/Controllers/Backoffice/ScoringRuleController.php`
- `app/Http/Controllers/Backoffice/ScoringRuleSetController.php`
- `app/Http/Controllers/Backoffice/TieBreakerRuleController.php`

### Eligibility

- `app/Models/EligibilityCheck.php`
- `app/Models/EligibilityCriterion.php`
- `app/Models/EligibilityRuleSet.php`
- `app/Services/Eligibility/EligibilityCheckService.php`
- `app/Services/Eligibility/EligibilityCriteriaEvaluator.php`
- `app/Services/Eligibility/EligibilityDataProvider.php`
- `app/Services/Eligibility/EligibilityEngine.php`
- `app/Http/Controllers/Backoffice/EligibilityCriterionController.php`
- `app/Http/Controllers/Backoffice/EligibilityRuleSetController.php`

### Lottery, lists e ranking downstream

- `app/Models/LotteryRun.php`
- `app/Services/Lists/ListEntryBuilderService.php`

### Templates processuais

- `app/Models/Application.php`
- `app/Models/ProcessConfirmation.php`
- `app/Services/ProcedureTemplates/TemplateVariableResolver.php`

## Tipo de correções

- Generics Eloquent em relações `BelongsTo`, `HasMany` e `HasOne`.
- Tipagem de scopes com `Builder<Model>`.
- PHPDoc de casts enum/datetime já existentes.
- Shapes de arrays para elegibilidade e desempates.
- Guards explícitos para relações obrigatórias antes de chamar services.
- Remoção de guards redundantes que se tornaram impossíveis após tipagem.
- Substituição de `fresh()` potencialmente nulo por `refresh()/load()` quando aplicável.

Não foram alteradas fórmulas de elegibilidade, pesos de pontuação, ordenação de ranking, regras de desempate, regras de concurso, sorteio, contratos, rendas, RGPD, auditoria, policies ou permissões.

## Artefactos gerados

- `storage/phpstan/phpstan-14-before.txt`
- `storage/phpstan/phpstan-14-after-models.txt`
- `storage/phpstan/phpstan-14-after-scoring.txt`
- `storage/phpstan/phpstan-14-after-eligibility.txt`
- `storage/phpstan/phpstan-14-after-simulation.txt`
- `storage/phpstan/phpstan-14-after-contests.txt`
- `storage/phpstan/phpstan-14-after-templates.txt`
- `storage/phpstan/phpstan-14-final.txt`
- `storage/phpstan/phpstan-14-summary.txt`
- `storage/phpstan/phpstan-14-phpunit.txt`
- `storage/phpstan/phpstan-14-directed-tests.txt`
- `storage/phpstan/phpstan-14-pint-final.txt`
- `storage/phpstan/phpstan-14-route-list.txt`

## Testes e comandos

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/pint --test` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK — 283 testes, 1775 asserções |
| `php artisan route:list --except-vendor` | OK — 1083 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` | Falha esperada — 544 erros legados remanescentes |

Filtros dirigidos executados:

| Filtro | Resultado |
| --- | --- |
| `Scoring` | OK |
| `Score` | OK |
| `Ranking` | OK |
| `TieBreaker` | Sem testes encontrados pelo filtro |
| `Eligibility` | OK |
| `Lottery` | OK |
| `Simulation` | OK |
| `Simulator` | OK |
| `Candidate` | OK |
| `Contest` | OK |
| `Public` | OK |
| `Application` | OK |
| `Procedure` | OK |
| `Template` | OK |
| `Document` | OK |

## Bugs reais encontrados

Não foi confirmado bug funcional em produção nesta sprint.

Foram adicionados guards de integridade para estados inválidos que já causariam erro em runtime, por exemplo:

- execução de scoring sem `ScoringRuleSet`;
- score sem candidatura associada;
- entrada de ranking sem candidatura associada;
- verificação de elegibilidade sem candidato associado.

## Falsos positivos tratados

- Casts enum/datetime vistos pelo PHPStan como `string`.
- Relações Eloquent vistas como `Model|null` ou `Model`.
- Coleções Eloquent sem generics.
- Arrays de elegibilidade e desempate sem shape.

## Correções adiadas

Permanecem erros em:

- `app/Services/CandidateExperience/ApplicationSimulationConsistencyService.php` — 13 erros.
- `app/Services/Contests/ContestService.php` — 10 erros.
- `app/Models/LotteryDrawResult.php` — 7 erros.
- `app/Services/Programs/ProgramService.php` — 8 erros.
- `app/Services/TenantPortal/TenantPortalAccessService.php` — 8 erros.

Estas áreas devem ser tratadas em sprint própria ou continuação da PHPSTAN-14, porque envolvem nullability e casts próximos de fluxos funcionais sensíveis.

## Riscos residuais

- `php artisan test` continua não recomendado neste ambiente por limite operacional de 128 MB; usar PHPUnit direto com `memory_limit=-1`.
- PHPStan ainda contém 544 erros globais.
- Security, RGPD, Audit e Policies permanecem para a PHPSTAN-15.
- Alguns seeders demo e services periféricos ainda têm generics/array shapes pendentes.
- CI/CD deve impedir novos erros PHPStan por assinatura exata.

## Recomendação

Avançar para PHPSTAN-15 apenas mantendo a mesma política:

1. `exact_new = 0`.
2. Sem baseline nem suppressions.
3. Foco em Security, RGPD, Audit e Policies.
4. Testes dirigidos antes de alterar fluxos críticos.
