# Relatório PHPSTAN-08 — Models Core, Eloquent Relations Hardening

Data: 2026-06-23

## Objetivo

Executar hardening estático de relações Eloquent e scopes nos modelos core definidos para a sprint, sem alterar lógica funcional, migrations, seeders, configuração, workflows ou regras de domínio.

## Âmbito Executado

Modelos tratados pela ordem definida:

1. `app/Models/Application.php`
2. `app/Models/User.php`
3. `app/Models/Contract.php`
4. `app/Models/Contest.php`
5. `app/Models/Program.php`
6. `app/Models/HousingUnit.php`

## Resultado PHPStan

| Etapa | Total de erros PHPStan | Diferença face à baseline |
| --- | ---: | ---: |
| Baseline `phpstan-08-before.txt` | 2192 | 0 |
| Após `Application` | 2110 | -82 |
| Após `User` | 2055 | -137 |
| Após `Contract` | 1980 | -212 |
| Após `Contest` | 1941 | -251 |
| Após `Program` | 1908 | -284 |
| Após `HousingUnit` | 1876 | -316 |
| Final `phpstan-08-final.txt` | 1876 | -316 |

Meta mínima da sprint: reduzir pelo menos 150 erros.

Resultado: cumprido. Redução total de 316 erros.

## Resultado por Modelo

| Modelo | Erros diretos antes | Erros diretos depois | Resultado |
| --- | ---: | ---: | --- |
| `Application` | 67 | 0 | OK |
| `User` | 48 | 0 | OK |
| `Contract` | 52 | 0 | OK |
| `Contest` | 32 | 0 | OK |
| `Program` | 30 | 0 | OK |
| `HousingUnit` | 29 | 0 | OK |

Total de erros diretos removidos nos modelos alvo: 258.

## Alterações Técnicas

Foram aplicadas apenas alterações de tipagem estática:

- `@return BelongsTo<...>`
- `@return BelongsToMany<...>`
- `@return HasMany<...>`
- `@return HasOne<...>`
- `@use HasFactory<...Factory>`
- `@param Builder<self>`
- `@return Builder<self>`
- Tipagem de scopes Eloquent simples.
- PHPDoc de propriedades de enum em modelos onde o cast já existia.

Não foram alteradas regras de negócio, queries críticas, estados administrativos, permissões, policies, validações, migrations, seeders ou configurações.

## Ficheiros Alterados

- `app/Models/Application.php`
- `app/Models/User.php`
- `app/Models/Contract.php`
- `app/Models/Contest.php`
- `app/Models/Program.php`
- `app/Models/HousingUnit.php`

## Ficheiros Criados

- `docs/qa/phpstan-08-models-core-eloquent-relations-hardening-report.md`
- `storage/phpstan/phpstan-08-before.txt`
- `storage/phpstan/phpstan-08-after-application.txt`
- `storage/phpstan/phpstan-08-after-user.txt`
- `storage/phpstan/phpstan-08-after-contract.txt`
- `storage/phpstan/phpstan-08-after-contest.txt`
- `storage/phpstan/phpstan-08-after-program.txt`
- `storage/phpstan/phpstan-08-after-housing-unit.txt`
- `storage/phpstan/phpstan-08-final.txt`
- `storage/phpstan/phpstan-08-pint-test.txt`
- `storage/phpstan/phpstan-08-phpunit.txt`
- `storage/phpstan/phpstan-08-route-list.txt`
- `storage/phpstan/phpstan-08-optimize-clear.txt`

## Validações Executadas

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/pint --test` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK: 283 testes, 1775 asserções |
| `php artisan route:list --except-vendor` | OK: 1083 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` | Falha esperada por dívida legada: 1876 erros remanescentes |

## Observações Sobre Erros Novos Aparente

A tipagem mais precisa de relações Eloquent expôs 173 mensagens PHPStan que antes estavam mascaradas por tipos genéricos como `Model` ou relações sem generics. Isto não corresponde a alterações funcionais introduzidas nesta sprint.

Categorias principais dos avisos agora visíveis:

- `nullsafe.neverNull`
- `identical.alwaysFalse`
- `notIdentical.alwaysTrue`
- `property.nonObject`
- `instanceof.alwaysTrue`
- `property.notFound`
- `method.notFound`

Estas mensagens devem ser tratadas em sprints seguintes de lógica de domínio e nullability, não nesta sprint de relações Eloquent.

## Dívida Remanescente Principal

Top identificadores no PHPStan final:

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

Ficheiros com mais erros remanescentes:

| Ficheiro | Erros |
| --- | ---: |
| `app/Services/Scoring/ScoringDataProvider.php` | 88 |
| `app/Services/Administrative/AdministrativeTimelineService.php` | 28 |
| `app/Services/Contracts/RentSnapshotService.php` | 25 |
| `app/Services/Complaints/ComplaintService.php` | 23 |
| `app/Models/Allocation.php` | 22 |
| `app/Services/Allocation/LotteryService.php` | 21 |
| `app/Services/Scoring/ScoringCriterionEvaluator.php` | 21 |
| `app/Models/DefinitiveList.php` | 20 |
| `app/Models/ProvisionalList.php` | 20 |
| `app/Services/Contracts/LeaseContractService.php` | 20 |

## Riscos Identificados

- A tipagem de `Application`, `Program`, `Contest` e `Contract` torna mais visíveis comparações enum/string em services existentes.
- Alguns services assumem relações sempre presentes; quando isso é garantido por schema, a anotação é aceitável, mas a lógica consumidora deve ser revista em sprint própria.
- Existem relações e propriedades dinâmicas em modelos ainda não tratados, especialmente em scoring, listas, allocation, contratos e serviços administrativos.

## Fora de Âmbito Preservado

Não foram alterados:

- Elegibilidade
- Pontuação
- Classificação
- Concursos
- Candidaturas
- Documentos
- Auditoria
- RGPD
- Policies
- Contratos
- Rendas
- Manutenção
- Vistorias
- Notificações
- Workflows administrativos
- Migrations
- Seeders
- Configuração local ou produção

## Recomendação

Avançar para a próxima sprint PHPStan focada em modelos remanescentes de domínio e serviços consumidores, com prioridade para:

1. `app/Models/Allocation.php`
2. `app/Models/DefinitiveList.php`
3. `app/Models/ProvisionalList.php`
4. `app/Services/Scoring/ScoringDataProvider.php`
5. Comparações enum/string em services de contratos, concursos e scoring.

PHPStan continua a falhar por dívida legada, mas a sprint PHPSTAN-08 cumpriu o objetivo: os seis modelos core alvo ficaram sem erros diretos e a dívida total foi reduzida de forma relevante sem quebrar testes.
