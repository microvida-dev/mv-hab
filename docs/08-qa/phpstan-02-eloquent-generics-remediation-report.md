# Relatório PHPSTAN-02 — Remediação Segura de Generics Eloquent

Data: 2026-06-23

## Resumo executivo

A Sprint PHPSTAN-02 executou uma remediação limitada a PHPDoc/generics de baixo risco, sem alterar lógica, queries, permissões, migrations, seeders ou regras de negócio.

Foram tratados três lotes seguros:

- relações simples em models auxiliares/logs e administrativos de baixo risco;
- factories com `@extends Factory<Model>`;
- relações e scopes simples de portal público/oferta habitacional.

Foi tentado um lote adicional em `AdministrativeProcess`, mas foi revertido porque fez emergir mais erros fora de `missingType.generics`, contrariando o critério de bloqueio da sprint.

## Métricas

| Métrica | Antes | Depois | Diferença |
| --- | ---: | ---: | ---: |
| Total PHPStan | 2897 | 2828 | -69 |
| `missingType.generics` | 1160 | 1093 | -67 |
| Ficheiros afetados | 561 | 529 | -32 |

Alterações por identificador:

| Identificador | Antes | Depois | Diferença |
| --- | ---: | ---: | ---: |
| `missingType.generics` | 1160 | 1093 | -67 |
| `method.notFound` | 35 | 34 | -1 |
| `property.notFound` | 285 | 284 | -1 |

## Ficheiros alterados

Models:

- `app/Models/AccessLog.php`
- `app/Models/AdditionalInformationRequest.php`
- `app/Models/AdditionalInformationResponse.php`
- `app/Models/AdministrativeDecision.php`
- `app/Models/AdministrativeProcessNote.php`
- `app/Models/AdministrativeProcessStatusHistory.php`
- `app/Models/AdministrativeTask.php`
- `app/Models/AdministrativeWorkflowConfig.php`
- `app/Models/ContestHousingUnit.php`
- `app/Models/HousingUnitPublicDocument.php`

Factories:

- `database/factories/DashboardDefinitionFactory.php`
- `database/factories/DashboardWidgetFactory.php`
- `database/factories/IndicatorDefinitionFactory.php`
- `database/factories/IndicatorSnapshotFactory.php`
- `database/factories/InspectionChecklistTemplateFactory.php`
- `database/factories/InspectionChecklistTemplateItemFactory.php`
- `database/factories/MaintenanceAssignmentFactory.php`
- `database/factories/MaintenanceAttachmentFactory.php`
- `database/factories/MaintenanceCategoryFactory.php`
- `database/factories/MaintenanceCostFactory.php`
- `database/factories/MaintenanceInterventionFactory.php`
- `database/factories/MaintenanceRequestStatusHistoryFactory.php`
- `database/factories/MaintenanceSupplierFactory.php`
- `database/factories/PropertyHistoryEventFactory.php`
- `database/factories/PropertyInspectionAttachmentFactory.php`
- `database/factories/PropertyInspectionFactory.php`
- `database/factories/PropertyInspectionItemFactory.php`
- `database/factories/PropertyInspectionReportFactory.php`
- `database/factories/ReportAccessLogFactory.php`
- `database/factories/ReportDefinitionFactory.php`
- `database/factories/ReportDownloadLogFactory.php`
- `database/factories/ReportExportFactory.php`
- `database/factories/ReportFilterPresetFactory.php`
- `database/factories/ReportRunFactory.php`

Artefactos QA/PHPStan:

- `storage/phpstan/phpstan-02-before.txt`
- `storage/phpstan/phpstan-02-after-lote1-fix.txt`
- `storage/phpstan/phpstan-02-after-lote2.txt`
- `storage/phpstan/phpstan-02-after-lote3.txt`
- `storage/phpstan/phpstan-02-final.txt`
- `docs/qa/phpstan-02-eloquent-generics-remediation-report.md`

## Relações corrigidas

Foram tipadas 39 relações Eloquent simples:

- `BelongsTo<User, $this>` em logs, tarefas, decisões e uploads;
- `BelongsTo<Application, $this>` em pedidos/respostas/processos administrativos auxiliares;
- `BelongsTo<Complaint, $this>`;
- `BelongsTo<DocumentSubmission, $this>`;
- `BelongsTo<Program, $this>`;
- `BelongsTo<Contest, $this>`;
- `BelongsTo<HousingUnit, $this>`;
- `HasMany<AdditionalInformationResponse, $this>`;
- `HasMany<HousingPreference, $this>`;
- `HasMany<Allocation, $this>`;
- `HasMany<AllocationOffer, $this>`.

Decisão técnica: foi usado `$this` como declaring model no PHPDoc das relações. O padrão `self` foi testado e revertido porque o Larastan reportou `return.type` em massa.

## Factories tipadas

Foram tipadas 24 factories com:

```php
/**
 * @extends Factory<Model>
 */
```

Não foram criadas factories novas e não foi alterado o conteúdo de `definition()`.

## Scopes tipados

Foram tipados 2 scopes simples:

- `HousingUnitPublicDocument::scopePubliclyVisible()`
- `ContestHousingUnit::scopeAvailable()`

Não foram alteradas queries, condições ou estados.

## Relações adiadas

Foram adiadas por risco, ambiguidade ou fora de âmbito:

- `AccessLog::resource()` por ser `MorphTo`;
- `DocumentAccessLog` por tocar em documentos privados e acessos;
- `SensitiveDataAccessLog` por segurança/RGPD;
- `ReportAccessLog` por registo de acesso a relatórios potencialmente sensíveis;
- `AdministrativeProcess` porque a tipagem das relações fez emergir erros `method.nonObject` fora do alvo da sprint;
- `User`, `Application`, `Contract`, `Contest`, `Program`, `HousingUnit`, `Allocation`, `Eligibility`, `Scoring`, `Finance/Rents`, `Security`, `RGPD`, `Audit` e `Policies`, conforme exclusões do sprint.

## Riscos e decisões

- O workspace não é um repositório Git: `git status --short` falhou com `fatal: not a git repository`.
- A ausência de Git impede confirmação formal de worktree limpo; foi usada validação por comandos e listagem de ficheiros alterados.
- `AdministrativeProcess` foi revertido por critério de bloqueio: surgiram mais erros não alvo após tipagem.
- O PHPStan continua a falhar, mas o total foi reduzido e não foram introduzidos novos identificadores.
- Não foi executado `npm run build`, porque não houve alteração frontend.

## Comandos executados

| Comando | Resultado |
| --- | --- |
| `git status --short` | Falhou: diretório não é repositório Git. |
| `php artisan optimize:clear` | Passou. |
| `vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-02-before.txt` | Falhou com erros PHPStan esperados; baseline 2897 erros. |
| `vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-02-after-lote1-fix.txt` | Falhou com erros PHPStan esperados; 2870 erros. |
| `vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-02-after-lote2.txt` | Falhou com erros PHPStan esperados; 2846 erros. |
| `vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-02-after-lote3.txt` | Falhou com erros PHPStan esperados; 2828 erros. |
| `vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-02-after-lote4.txt` | Falhou com erros PHPStan esperados; lote revertido por expor erros não alvo. |
| `vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-02-final.txt` | Falhou com erros PHPStan esperados; 2828 erros finais. |
| `php artisan test` | Falhou por `Allowed memory size of 134217728 bytes exhausted`; antes da falha reportou 163 testes e 983 asserções passadas. |
| `php -d memory_limit=-1 artisan test` | Voltou a falhar pelo mesmo limite efetivo de 128 MB. |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | Passou: 280 testes, 1758 asserções. |
| `vendor/bin/pint --test` | Falhou inicialmente por alinhamento PHPDoc em 2 ficheiros; passou após correção manual. |

## Resultado dos testes

Resultado válido: `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml`

- 280 testes
- 1758 asserções
- Resultado: OK

O comando `php artisan test` padrão não é fiável neste ambiente por limite de memória de 128 MB.

## Resultado do Pint

`vendor/bin/pint --test`: OK após correção manual de alinhamento PHPDoc.

## Resultado do PHPStan

`vendor/bin/phpstan analyse --memory-limit=1G -v`: continua a falhar, como esperado, mas reduziu:

- total: 2897 → 2828;
- `missingType.generics`: 1160 → 1093;
- ficheiros afetados: 561 → 529.

## Erros remanescentes por domínio

Principais grupos remanescentes:

- relações Eloquent em models core;
- generics de `User`, `Application`, `Contract`, `Contest`, `Program`, `HousingUnit` e `Allocation`;
- `MorphTo`/polimórficas;
- logs sensíveis e RGPD;
- relações em documentos privados;
- scopes que expõem erros de lógica fora do alvo;
- factories já não apresentam `missingType.generics`.

## Recomendação para PHPSTAN-03

Avançar para `PHPSTAN-03` com foco em:

1. arrays, collections e return types de baixo risco;
2. `missingType.iterableValue` em Form Requests e helpers simples;
3. `return.type` em `authorize()` que deve devolver `bool` explícito;
4. manter fora de âmbito erros de estados impossíveis, `method.notFound`, RGPD, segurança e workflows críticos.

Não avançar ainda para correções de `AdministrativeProcess`, `Application`, `Contract`, `Eligibility`, `Scoring`, `Finance/Rents`, `Security` ou `RGPD` sem testes dirigidos por domínio.
