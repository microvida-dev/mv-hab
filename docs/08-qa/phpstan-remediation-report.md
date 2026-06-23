# Relatorio de Remediacao PHPStan Sprint 20A

Data: 16/06/2026.
Reclassificacao: 17/06/2026.

Estado final: `READY_FOR_LOCAL_TESTING`.

## Resumo executivo

A Sprint 20A executou remediacao local de PHPStan, null-safety e hardening sem CI/CD remoto, sem GitHub Actions, sem Psalm e sem integracoes CMD/Autenticacao.gov.

Foram corrigidos os erros estruturais repetitivos do trait `HasOptions`, endurecido o acesso a utilizadores autenticados em controllers/middlewares, corrigidas comparacoes Enum/String e ajustados alguns pontos de `findOrFail`, route binding e preview documental.

Os testes Laravel, build, Pint, rotas e cache de views passaram. O PHPStan ainda falha, mas os erros residuais estao maioritariamente concentrados em tipagem estatica, generics, relacoes Eloquent e modelacao Larastan. Assim, a plataforma fica apta para testes locais continuados, mantendo a entrada em staging condicionada a nova reducao da divida PHPStan.

## Contagem PHPStan

- Erros iniciais: 3681.
- Erros finais: 2827.
- Reducao absoluta: 854 erros.
- Reducao aproximada: 23,2%.

## Correcoes aplicadas por categoria

### Enums e iterable types

- `HasOptions::options()` documentado como `array<string, string>`.
- `HasOptions::values()` documentado como `list<string>`.
- Removida a maior fonte repetitiva de `missingType.iterableValue` em enums.

### Null-safety de utilizador autenticado

- Criado `Controller::authenticatedUser()` para devolver sempre `App\Models\User` ou abortar com 403.
- Criado `Controller::currentUser()` para rotas autenticadas que usam o helper global `request()`.
- Substituidos usos inseguros de `$request->user()` e `request()->user()` em controllers por helpers tipados.
- Corrigidos middlewares `EnsureUserHasRole`, `RequireSensitivePermission`, `LogBackofficeAccess`, `LogSensitiveResourceAccess`, `EnsureBackofficeMfaVerified` e `BlockInactiveBackofficeUsers`.
- Reposta a semantica original de `BlockInactiveBackofficeUsers`: utilizadores sem `status` explicito sao tratados como `active`.

### Enums versus strings

- Corrigida comparacao de `ListPublicationType::DefinitiveList`.
- Corrigida comparacao de `ApplicationStatus::Draft`.
- Corrigidos alguns assignments de enums para propriedades Eloquent string usando `->value`.

### Route bindings, findOrFail e relacoes Eloquent

- Convertidos alguns IDs validados para inteiros antes de `findOrFail`.
- Estreitados alguns resultados relacionais com `instanceof` antes de passar para services.
- Corrigido preview de modelo documental para exigir uma `DocumentTemplateVersion` valida.

### Contrato de verificacao de email

- `App\Models\User` passou a implementar `Illuminate\Contracts\Auth\MustVerifyEmail`.

## Ficheiros criados

- `docs/qa/phpstan-remediation-report.md`

## Ficheiros alterados

- `app/Enums/Concerns/HasOptions.php`
- `app/Events/CriticalNotificationEvent.php`
- `app/Models/User.php`
- `app/Http/Controllers/Controller.php`
- `app/Http/Controllers/Admin/ContestController.php`
- `app/Http/Controllers/Admin/ProgramController.php`
- `app/Http/Controllers/Admin/DocumentTypeController.php`
- `app/Http/Controllers/Admin/RequiredDocumentController.php`
- `app/Http/Controllers/Backoffice/AdministrativeWorkflowConfigController.php`
- `app/Http/Controllers/Backoffice/AllocationController.php`
- `app/Http/Controllers/Backoffice/AllocationRuleSetController.php`
- `app/Http/Controllers/Backoffice/AllocationReportController.php`
- `app/Http/Controllers/Backoffice/CommunicationLogController.php`
- `app/Http/Controllers/Backoffice/ComplaintController.php`
- `app/Http/Controllers/Backoffice/ContestHousingUnitController.php`
- `app/Http/Controllers/Backoffice/ContractClauseController.php`
- `app/Http/Controllers/Backoffice/ContractTemplateController.php`
- `app/Http/Controllers/Backoffice/DefinitiveListController.php`
- `app/Http/Controllers/Backoffice/DocumentTemplateController.php`
- `app/Http/Controllers/Backoffice/EligibilityCheckController.php`
- `app/Http/Controllers/Backoffice/Finance/AnnualDocumentUpdateRequestController.php`
- `app/Http/Controllers/Backoffice/Finance/DefaultNoticeController.php`
- `app/Http/Controllers/Backoffice/Finance/LeasePaymentController.php`
- `app/Http/Controllers/Backoffice/Finance/RegularizationAgreementController.php`
- `app/Http/Middleware/BlockInactiveBackofficeUsers.php`
- `app/Http/Middleware/EnsureBackofficeMfaVerified.php`
- `app/Http/Middleware/EnsureUserHasRole.php`
- `app/Http/Middleware/LogBackofficeAccess.php`
- `app/Http/Middleware/LogSensitiveResourceAccess.php`
- `app/Http/Middleware/RequireSensitivePermission.php`

## Validacao local executada

| Comando | Resultado |
| --- | --- |
| `composer validate --no-check-publish` | Passou. |
| `./vendor/bin/phpstan analyse --memory-limit=1G` | Falhou com 2827 erros. |
| `php artisan test` | Passou com 174 testes e 1164 assercoes. |
| `npm run build` | Passou. |
| `./vendor/bin/pint --test` | Falhou inicialmente por estilo; passou apos `./vendor/bin/pint`. |
| `php artisan route:list` | Passou; 830 rotas listadas. |
| `php artisan view:cache` | Passou. |
| `php artisan view:clear` | Passou apos validacao. |

## Erros residuais por categoria

- Null-safety e `argument.type` em controllers que passam resultados de `findOrFail`, relacoes Eloquent ou route bindings para services tipados.
- `missingType.iterableValue` em helpers `formData`, `normalized`, arrays de configuracao e payloads.
- `property.notFound` em relacoes Eloquent que PHPStan ainda infere como `Model`.
- `assign.propertyType` em atribuicoes diretas a propriedades Eloquent com enums e IDs.
- `method.notFound` em pelo menos um fluxo de `LotteryService::lock`.
- Generics e relacoes Eloquent ainda sem PHPDoc/casts suficientes.

## Riscos residuais

- PHPStan continua a bloquear a classificacao `READY_FOR_STAGING`, mas nao bloqueia `READY_FOR_LOCAL_TESTING`.
- A reducao atual removeu a maior fonte repetitiva dos enums, mas a base ainda tem divida estatica ampla.
- Existem erros residuais que podem indicar bugs reais, especialmente `method.notFound`, `property.notFound` e null-safety em relacoes.
- A configuracao PHPStan esta em `level: 8`, acima do exemplo sugerido na prompt. O nivel nao foi reduzido para esconder erros.

## Proximos passos recomendados

1. Executar Sprint 20B dedicada a PHPStan, sem alteracoes funcionais.
2. Prioridade 1: reduzir `argument.type`, incluindo assinaturas, DTOs, `int|string`, `BackedEnum`, arrays e collections.
3. Prioridade 2: tipar relacoes Eloquent com generics e PHPDoc onde Larastan nao infere corretamente.
4. Prioridade 3: resolver `property.notFound` validando casts, fillable, appends, relacoes e propriedades dinamicas.
5. Prioridade 4: corrigir `assign.propertyType`, garantindo que propriedades recebem valores escalares/enums compatíveis.
6. Prioridade 5: completar `missingType.iterableValue` em services, seeders, factories e helpers.
7. Prioridade 6: corrigir ou implementar `LotteryService::lock`.
8. Considerar estabilizacao temporaria em `level: 6` para Sprint 20B, evoluindo depois para `level: 7` e `level: 8`, sem baselines, `ignoreErrors` ou `@phpstan-ignore`.
9. Meta recomendada para Sprint 20B: reduzir PHPStan de 2827 para menos de 500 erros mantendo `php artisan test`, `npm run build`, `./vendor/bin/pint --test` e `php artisan route:list` verdes.
