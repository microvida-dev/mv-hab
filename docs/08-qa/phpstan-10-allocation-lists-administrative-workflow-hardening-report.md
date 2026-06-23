# Relatório PHPSTAN-10 — Allocation, Lists & Administrative Workflow Hardening

Data: 2026-06-23

## Resumo executivo

A sprint PHPSTAN-10 foi executada sobre allocation, listas, audiência/reclamações e workflow administrativo.

Foram feitas apenas correções de tipagem, generics Eloquent, guards relacionais, normalização explícita de enums já definidos em casts, array shapes e substituição de retornos nullable (`fresh`) por `refresh/load` quando o modelo já existe.

Não foram alteradas migrations, seeders, policies, regras de negócio, regras de ranking, ordenação, transições permitidas, prazos legais, auditoria ou configuração PHPStan.

Resultado final:

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros PHPStan globais | 1625 | 1317 | -308 |
| Ficheiros com erros | 374 | 344 | -30 |
| `missingType.generics` | 727 | 628 | -99 |
| `missingType.iterableValue` | 153 | 125 | -28 |
| `argument.type` | 151 | 121 | -30 |
| `property.notFound` | 71 | 64 | -7 |
| `property.nonObject` | 63 | 43 | -20 |
| `method.nonObject` | 61 | 43 | -18 |
| `method.notFound` | 25 | 19 | -6 |
| `deadCode.unreachable` | 40 | 14 | -26 |

Comparação exata:

| Métrica | Valor |
| --- | ---: |
| Erros exatos novos | 0 |
| Erros exatos removidos | 305 |
| Erros diretos nos ficheiros prioritários finais | 0 |

## Progressão por bloco

| Bloco | Erros PHPStan | Variação acumulada |
| --- | ---: | ---: |
| Baseline PHPSTAN-10 | 1625 | 0 |
| Models allocation/listas | 1519 | -106 |
| Allocation services | 1496 | -129 |
| List services | 1453 | -172 |
| Hearings/complaints | 1398 | -227 |
| Administrative workflow | 1339 | -286 |
| Correções adjacentes para `exact_new=0` | 1317 | -308 |
| Final | 1317 | -308 |

## Ficheiros alterados

### Ficheiros prioritários

- `app/Models/Allocation.php`
- `app/Models/AllocationRun.php`
- `app/Models/AllocationOffer.php`
- `app/Models/DefinitiveList.php`
- `app/Models/DefinitiveListEntry.php`
- `app/Models/ProvisionalList.php`
- `app/Models/ProvisionalListEntry.php`
- `app/Services/Allocation/AllocationOfferService.php`
- `app/Services/Allocation/AllocationResponseService.php`
- `app/Services/Allocation/AllocationReportService.php`
- `app/Services/Allocation/ContractReadinessService.php`
- `app/Services/Lists/DefinitiveListService.php`
- `app/Services/Lists/ProvisionalListService.php`
- `app/Services/Lists/ListPublicationService.php`
- `app/Services/Hearings/HearingService.php`
- `app/Services/Hearings/HearingSubmissionService.php`
- `app/Services/Complaints/ComplaintService.php`
- `app/Services/Complaints/ComplaintDecisionService.php`
- `app/Services/Complaints/AdditionalInformationService.php`
- `app/Services/Administrative/AdministrativeDeadlineService.php`
- `app/Services/Administrative/AdministrativeDecisionService.php`
- `app/Services/Administrative/AdministrativeProcessService.php`
- `app/Services/Administrative/AdministrativeWorkflowTransitionService.php`
- `app/Services/Administrative/CorrectionRequestService.php`
- `app/Services/Administrative/CorrectionResponseService.php`
- `app/Services/AdministrativeDecision/FinalDecisionReadinessService.php`

### Ficheiros adjacentes corrigidos

Estes ficheiros foram ajustados porque os generics dos models tornaram visíveis erros exatos novos em serviços adjacentes. A correção foi necessária para cumprir o critério `0 erros novos exatos`.

- `app/Http/Controllers/PublicArea/PublishedResultListController.php`
- `app/Services/Allocation/AllocationNotificationService.php`
- `app/Services/Allocation/PreferenceAllocationService.php`
- `app/Services/Allocation/RankingAllocationService.php`
- `app/Services/Allocation/ReplacementService.php`

## Correções por domínio

### Allocation

- Tipadas relações Eloquent em `Allocation`, `AllocationRun` e `AllocationOffer`.
- Corrigidos scopes `active`, `readyForContract` e `pendingResponse` com generics de `Builder`.
- Adicionados guards para relações obrigatórias em ofertas, respostas, notificações, ranking e substituição.
- Normalizados estados de `AllocationOfferStatus`, `AllocationRunStatus` e `ContestHousingUnitStatus` via `getAttribute()` e casts existentes.

Classificação: TS/DT, com alguns RF convertidos em validações controladas.

### Lottery

- Sem alteração direta ao motor de sorteio.
- As correções de allocation não alteraram locks, ordenação ou seleção.

Classificação: TS.

### Definitive lists

- Tipadas relações de `DefinitiveList` e `DefinitiveListEntry`.
- Corrigido `eligibleForAllocation()` sem alterar filtros.
- Corrigidos guards de decisão de reclamação e logs de alteração.
- Corrigida readiness final com query tipada sobre `DefinitiveListEntry`.

Classificação: TS/DT.

### Provisional lists

- Tipadas relações de `ProvisionalList` e `ProvisionalListEntry`.
- Corrigido `isComplaintPeriodOpen()` com leitura explícita de enum/data já castados.
- Corrigidos services de geração, aprovação, publicação, abertura/fecho de prazo e cancelamento.

Classificação: TS/FP.

### Hearings

- Normalização de `HearingStatus`.
- Guard de candidato e aplicação antes de notificar.
- Guard de `HearingSubmission->hearing`.
- Deadline lido como `CarbonInterface` quando aplicável.

Classificação: TS/RF.

### Complaints

- Normalização de `ComplaintStatus`, `ComplaintDecisionStatus` e `ComplaintDecisionResult`.
- Guard de aplicação/lista/candidato em reclamações e decisões.
- Retornos `fresh()` substituídos por `refresh/load` para evitar `Model|null`.
- Dados de anexos e payloads tipados com `array<string, mixed>`.

Classificação: TS/RF.

### Administrative workflow

- Normalização de `AdministrativeProcessStatus`, `AdministrativeDecisionStatus`, `AdministrativeDecisionResult` e `CorrectionRequestStatus`.
- Guard de processo, candidatura, pedido, item e resposta antes de transições.
- Tipagem de `Collection<int, CorrectionRequest>`.
- Preservada a matriz `ALLOWED` de transições.

Classificação: TS/RF.

## Bugs reais encontrados

| Código | Ficheiro | Evidência | Tratamento |
| --- | --- | --- | --- |
| RF | `AllocationResponseService.php` | Relações obrigatórias podiam ser `null` antes de `forceFill()` ou chamada a service. | Guard local com `ValidationException`. |
| RF | `ListPublicationService.php` | Notificação podia receber `User|null`. | Guard de candidato na entrada da lista. |
| RF | `ComplaintDecisionService.php` | Decisão dependia de relações dinâmicas sem estreitamento. | Guards de reclamação, candidatura, lista e candidato. |
| RF | `CorrectionResponseService.php` | Resposta podia operar sobre request/item/processo `null`. | Guards antes de revisão/transição. |
| FP/DT | Vários models | PHPStan não tinha generics de relações Eloquent. | PHPDoc generics. |

## Correções adiadas

| Ficheiro/domínio | Erro | Motivo | Risco | Sprint recomendada |
| --- | --- | --- | --- | --- |
| Controllers de backoffice/candidato | `argument.type`, `property.notFound` | Fora do foco principal desta sprint. | Médio | PHPSTAN-11 |
| Policies | `missingType.generics`, `argument.type` | Área de segurança dedicada. | Alto | PHPSTAN-11 |
| Remaining models | `missingType.generics` | Ainda há 628 ocorrências globais. | Médio | PHPSTAN-11/12 |
| Reports/exports/dashboards | `return.type`, `missingType.iterableValue` | Não são workflow administrativo direto. | Médio | Sprint dedicada a reports |
| Public portal residual | `property.notFound`/tipagem de queries | Parcialmente corrigido apenas onde criou erro novo. | Médio | PHPSTAN-11 |

## Testes executados

### Validação final

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/pint --test` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK, 283 testes, 1775 asserções |
| `php artisan route:list --except-vendor` | OK, 1083 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` | Falhou por 1317 erros legados |

### Testes dirigidos

| Filtro | Resultado |
| --- | --- |
| `Allocation` | OK, 7 testes, 40 asserções |
| `Lottery` | OK, 4 testes, 40 asserções |
| `DefinitiveList` | Sem testes encontrados |
| `ProvisionalList` | Sem testes encontrados |
| `List` | OK, 14 testes, 96 asserções |
| `Offer` | OK, 8 testes, 40 asserções |
| `ContractReadiness` | Sem testes encontrados |
| `Publication` | OK, 1 teste, 3 asserções |
| `Definitive` | OK, 2 testes, 17 asserções |
| `Provisional` | OK, 2 testes, 16 asserções |
| `Hearing` | OK, 6 testes, 42 asserções |
| `Complaint` | OK, 6 testes, 42 asserções |
| `AdditionalInformation` | Sem testes encontrados |
| `Administrative` | OK, 6 testes, 32 asserções |
| `Decision` | OK, 2 testes, 11 asserções |
| `Correction` | OK, 1 teste, 11 asserções |
| `Process` | OK, 12 testes, 63 asserções |
| `Deadline` | OK, 1 teste, 9 asserções |

## Artefactos

- `storage/phpstan/phpstan-10-before.txt`
- `storage/phpstan/phpstan-10-after-models-allocation-lists.txt`
- `storage/phpstan/phpstan-10-after-allocation-services.txt`
- `storage/phpstan/phpstan-10-after-list-services.txt`
- `storage/phpstan/phpstan-10-after-hearings-complaints.txt`
- `storage/phpstan/phpstan-10-after-administrative-workflow.txt`
- `storage/phpstan/phpstan-10-after-adjacent-fixes.txt`
- `storage/phpstan/phpstan-10-final.txt`
- `storage/phpstan/phpstan-10-pint-test.txt`
- `storage/phpstan/phpstan-10-phpunit.txt`
- `storage/phpstan/phpstan-10-route-list.txt`
- `storage/phpstan/phpstan-10-directed-tests.txt`

## Riscos residuais

- PHPStan global ainda falha com 1317 erros legados.
- Ainda existem 628 erros de `missingType.generics`, sobretudo em models, policies e relações Eloquent fora do bloco trabalhado.
- Restam erros com possível risco funcional em controllers, policies, reports e serviços fora de allocation/listas/workflow administrativo.
- Há filtros de teste sem testes nominais (`DefinitiveList`, `ProvisionalList`, `ContractReadiness`, `AdditionalInformation`), embora haja cobertura parcial por filtros equivalentes.

## Recomendação

Avançar para PHPSTAN-11 com foco em:

1. Policies e autorização.
2. Controllers de candidato e backoffice.
3. Ownership de dados privados e documentos.
4. Generics Eloquent restantes em models relacionados.
5. Correção de `argument.type`, `property.notFound` e `method.nonObject` nos fluxos de candidato.

