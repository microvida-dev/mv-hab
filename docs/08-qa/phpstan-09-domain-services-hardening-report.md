# Relatório PHPSTAN-09 — Domain Services Hardening

Data: 2026-06-23

## Resumo executivo

A sprint PHPSTAN-09 foi executada sobre os serviços de domínio definidos no backlog, sem alterações a migrations, seeders, configuração, baseline PHPStan ou dependências.

O objetivo principal foi reduzir erros PHPStan em serviços críticos de pontuação, elegibilidade, candidatura, contratos, rendas, atribuição, sorteios e timeline administrativa através de tipagem, PHPDoc, normalização segura de enums, guards de nulabilidade e generics de coleções.

Resultado global:

| Métrica | Valor |
| --- | ---: |
| Erros PHPStan antes | 1876 |
| Erros PHPStan finais | 1625 |
| Redução total | 251 |
| Ficheiros com erros antes | 384 |
| Ficheiros com erros finais | 374 |
| Erros diretos nos 10 serviços alvo | 0 |
| Erros exatos novos | 0 |
| Erros exatos removidos | 244 |

O PHPStan continua a falhar por dívida técnica legada fora dos ficheiros alvo. Esta sprint não fecha a análise estática global, mas removeu os erros diretos nos serviços de domínio previstos.

## Ficheiros alterados

| Ficheiro | Domínio | Resultado PHPStan direto |
| --- | --- | ---: |
| `app/Services/Scoring/ScoringDataProvider.php` | Pontuação | 0 |
| `app/Services/Scoring/ScoringCriterionEvaluator.php` | Pontuação | 0 |
| `app/Services/Eligibility/EligibilityDataProvider.php` | Elegibilidade | 0 |
| `app/Services/Applications/ApplicationValidationService.php` | Candidaturas | 0 |
| `app/Services/Applications/ApplicationSnapshotService.php` | Candidaturas | 0 |
| `app/Services/Contracts/LeaseContractService.php` | Contratos | 0 |
| `app/Services/Contracts/RentSnapshotService.php` | Rendas | 0 |
| `app/Services/Allocation/AllocationEngine.php` | Atribuição | 0 |
| `app/Services/Allocation/LotteryService.php` | Sorteios | 0 |
| `app/Services/Administrative/AdministrativeTimelineService.php` | Workflow administrativo | 0 |

## Progressão por etapa

| Etapa | Erros PHPStan | Variação acumulada |
| --- | ---: | ---: |
| Baseline antes da sprint | 1876 | 0 |
| Após scoring | 1767 | -109 |
| Após eligibility | 1755 | -121 |
| Após applications | 1728 | -148 |
| Após contracts | 1683 | -193 |
| Após allocation/lottery | 1653 | -223 |
| Após administrative timeline | 1625 | -251 |
| Validação final | 1625 | -251 |

## Correções aplicadas por domínio

### Pontuação

- Adicionada tipagem de coleções e arrays de contexto.
- Normalizados valores de enum através de helpers locais.
- Mantida a fórmula de cálculo e a ordem dos critérios.
- Preservada a estrutura de snapshots.

### Elegibilidade

- Tipadas coleções de agregado, rendimentos, documentos e preferências.
- Adicionados helpers para datas e enums.
- Mantida a leitura dos dados de candidatura sem alterar regras de elegibilidade.
- Evitada inferência ambígua de relações Eloquent.

### Candidaturas

- Substituída agregação dinâmica ambígua por helpers tipados para checks e mensagens falhadas.
- Tipadas estruturas de snapshot de agregado, rendimentos e documentação.
- Preservadas validações funcionais e bloqueios existentes.

### Contratos e rendas

- Tipadas relações de atribuição, contratos e cálculos de renda.
- Normalizados estados por helpers sem alterar transições.
- Preservada a lógica de geração contratual e snapshots financeiros.

### Atribuição e sorteios

- Tipadas coleções de listas definitivas, entradas elegíveis e resultados.
- Ajustada leitura de atributos dinâmicos para APIs Eloquent compatíveis com PHPStan.
- Preservada ordenação, aleatoriedade, locks e regras de atribuição.

### Workflow administrativo

- Tipadas coleções carregadas na timeline.
- Normalizada apresentação de labels de enum/string.
- Preservada a ordem e composição dos eventos processuais.

## Distribuição final por identificador PHPStan

| Identificador | Quantidade final |
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
| `instanceof.alwaysTrue` | 23 |
| `booleanAnd.alwaysFalse` | 17 |
| `booleanOr.alwaysTrue` | 14 |
| `property.onlyWritten` | 14 |
| `function.alreadyNarrowedType` | 11 |
| `argument.templateType` | 9 |
| `missingType.return` | 7 |

## Artefactos gerados

| Artefacto | Conteúdo |
| --- | --- |
| `storage/phpstan/phpstan-09-before.txt` | PHPStan antes da sprint |
| `storage/phpstan/phpstan-09-after-scoring.txt` | PHPStan após serviços de pontuação |
| `storage/phpstan/phpstan-09-after-eligibility.txt` | PHPStan após elegibilidade |
| `storage/phpstan/phpstan-09-after-applications.txt` | PHPStan após candidaturas |
| `storage/phpstan/phpstan-09-after-contracts.txt` | PHPStan após contratos/rendas |
| `storage/phpstan/phpstan-09-after-allocation.txt` | PHPStan após atribuição/sorteios |
| `storage/phpstan/phpstan-09-after-administrative.txt` | PHPStan após timeline administrativa |
| `storage/phpstan/phpstan-09-final.txt` | PHPStan final |
| `storage/phpstan/phpstan-09-pint-test.txt` | Resultado final do Pint |
| `storage/phpstan/phpstan-09-phpunit.txt` | Resultado final da suite PHPUnit |
| `storage/phpstan/phpstan-09-route-list.txt` | Resultado final da listagem de rotas |

## Validação executada

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `php -l` nos 10 ficheiros alterados | OK |
| `./vendor/bin/pint --test` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK, 283 testes, 1775 asserções |
| `php artisan route:list --except-vendor` | OK, 1083 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` | Falhou como esperado por 1625 erros legados |

Validações por domínio também foram executadas com filtros PHPUnit:

| Filtro | Resultado |
| --- | --- |
| `Scoring` | OK |
| `Score` | OK |
| `Eligibility` | OK |
| `Simulator` | OK |
| `Application` | OK |
| `Candidate` | OK |
| `Document` | OK em reexecução sequencial |
| `Contract` | OK |
| `Rent` | OK |
| `Finance` | OK |
| `Allocation` | OK |
| `Lottery` | OK |
| `Administrative` | OK |
| `Timeline` | OK |
| `Process` | OK |
| `Lease` | Sem testes encontrados |
| `DefinitiveList` | Sem testes encontrados |

## Problemas encontrados

- A execução inicial do filtro `Document` falhou quando correu em paralelo com outros filtros por conflito temporário de storage em ficheiro de contrato. A reexecução sequencial passou.
- Os filtros `Lease` e `DefinitiveList` não encontraram testes correspondentes.
- PHPStan global permanece vermelho por 1625 erros legados fora dos serviços alvo.

## Riscos residuais

- A dívida principal remanescente continua concentrada em generics Eloquent, tipos iteráveis, argumentos incompatíveis e acessos dinâmicos a propriedades/relações.
- Alguns erros finais indicam possíveis bugs reais fora do âmbito desta sprint, nomeadamente `method.nonObject`, `property.nonObject`, `method.notFound`, `identical.alwaysFalse`, `deadCode.unreachable` e `function.impossibleType`.
- Existem áreas sem cobertura direta por filtro nominal, especialmente `Lease` e `DefinitiveList`.

## Recomendação

Avançar para a sprint PHPSTAN seguinte com foco em:

1. Generics Eloquent e relações ainda pendentes em Models e Controllers.
2. `argument.type` e `return.type` em services fora dos 10 ficheiros desta sprint.
3. Erros potencialmente funcionais: `method.nonObject`, `property.nonObject`, `method.notFound`, `identical.alwaysFalse` e `deadCode.unreachable`.
4. Reforço de testes para fluxos com filtros sem cobertura nominal, em especial contratos/leases e listas definitivas.

