# Relatorio PHPSTAN-17 - Final Residual Cleanup Enterprise CI Enforcement

Data de execucao: 2026-06-23

## Resumo executivo

A sprint PHPSTAN-17 foi executada como limpeza residual final orientada a tipagem estatica e sem introducao de alteracoes funcionais profundas.

Resultado principal:

- PHPStan antes: 209 erros wrapper / 198 assinaturas normalizadas.
- PHPStan final: 96 erros wrapper / 85 assinaturas normalizadas.
- Reducao liquida: 113 erros wrapper / 113 assinaturas normalizadas.
- Erros novos: 0.
- PHPUnit direto: OK.
- Pint test: OK.
- Route list: OK.

## Escopo executado

Foram corrigidos problemas de baixo risco em:

- Generics de `HasFactory`.
- Generics de relacoes `MorphTo`.
- PHPDoc de arrays e colecoes.
- Tipagem de `Collection`, `Builder` e `LengthAwarePaginator`.
- Retornos `fresh()` que o PHPStan interpretava como nullable, substituidos por `load()`/`refresh()` e retorno do mesmo modelo.
- Tipagem de IDs internos usados em consultas `find()`.
- Tipagem de DTOs informais em services.

Nao foram criadas migrations, seeders, controllers, rotas ou dependencias.

## Ficheiros aplicacionais alterados

### Middleware

- `app/Http/Middleware/EnsureUserHasRole.php`

### Models

- `app/Models/AdhesionRegistrationStatusHistory.php`
- `app/Models/ApplicationReviewItem.php`
- `app/Models/Arrear.php`
- `app/Models/AuditLog.php`
- `app/Models/ContestDeadline.php`
- `app/Models/ContestJuryMember.php`
- `app/Models/CorrectionRequestItem.php`
- `app/Models/FinancialTransaction.php`
- `app/Models/GeneratedOfficialDocument.php`
- `app/Models/GeneratedProcedureDocument.php`
- `app/Models/HousingUnitPublicDocument.php`
- `app/Models/InternalAlert.php`
- `app/Models/Municipality.php`
- `app/Models/Permission.php`
- `app/Models/ProgramRule.php`
- `app/Models/Role.php`
- `app/Models/TenantChargeRun.php`
- `app/Models/TenantPayment.php`

### Services

- `app/Services/Administrative/AdministrativeProcessNoteService.php`
- `app/Services/Administrative/AdministrativeTaskService.php`
- `app/Services/Administrative/ApplicationIntakeService.php`
- `app/Services/Administrative/ApplicationReviewService.php`
- `app/Services/Allocation/ContestHousingUnitService.php`
- `app/Services/Allocation/HousingPreferenceService.php`
- `app/Services/Allocation/LotteryAuditService.php`
- `app/Services/Allocation/ReserveListService.php`
- `app/Services/Allocation/TypologyAdequacyService.php`
- `app/Services/Applications/ApplicationDocumentService.php`
- `app/Services/Applications/ApplicationReceiptService.php`
- `app/Services/Applications/ApplicationService.php`
- `app/Services/Applications/ApplicationSubmissionService.php`
- `app/Services/Candidate/AdhesionRegistrationService.php`
- `app/Services/Candidate/HousingSituationService.php`
- `app/Services/Candidate/RegistrationProgressService.php`
- `app/Services/CandidateNotifications/CandidateNotificationCenterService.php`
- `app/Services/Complaints/ComplaintReviewService.php`
- `app/Services/Contests/ContestService.php`
- `app/Services/Contracts/ContractClauseService.php`
- `app/Services/Contracts/ContractPlaceholderService.php`
- `app/Services/Contracts/LeaseContractSignatureService.php`
- `app/Services/Contracts/LeaseContractValidationService.php`
- `app/Services/Contracts/RentCalculationService.php`
- `app/Services/Contracts/RentManualReviewService.php`
- `app/Services/Eligibility/EligibilityResultAggregator.php`
- `app/Services/Eligibility/EligibilitySnapshotService.php`
- `app/Services/Finance/AnnualDocumentUpdateService.php`
- `app/Services/Finance/DefaultNoticeService.php`
- `app/Services/Finance/IncomeChangeService.php`
- `app/Services/Finance/RegularizationAgreementService.php`
- `app/Services/Finance/RentScheduleService.php`
- `app/Services/Inspections/InspectionTemplateService.php`
- `app/Services/Inspections/PropertyInspectionItemService.php`
- `app/Services/Lists/ListAnonymizationService.php`
- `app/Services/Programs/ProgramService.php`
- `app/Services/Properties/PropertyTechnicalHistoryService.php`
- `app/Services/Scoring/ManualScoreService.php`
- `app/Services/Support/SupportTicketService.php`
- `app/Services/TenantBilling/TenantChargeRunService.php`
- `app/Services/TenantBilling/TenantPaymentService.php`
- `app/Services/TenantCommunications/TenantCommunicationService.php`

## Artefactos gerados

- `storage/phpstan/phpstan-17-before.txt`
- `storage/phpstan/phpstan-17-final.txt`
- `storage/phpstan/phpstan-17-count-final.txt`
- `storage/phpstan/phpstan-17-baseline-compare-final.txt`
- `storage/phpstan/phpstan-17-phpunit.txt`
- `storage/phpstan/phpstan-17-pint-test.txt`
- `storage/phpstan/phpstan-17-route-list.txt`
- `storage/phpstan/phpstan-17-summary.txt`

## Validacao final

### PHPUnit

Comando:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Resultado:

- Passou.
- 283 testes.
- 1775 assercoes.

### PHPStan

Comando:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json
```

Resultado:

- Exit code 1, esperado por ainda existirem erros residuais.
- Erros wrapper finais: 96.
- Assinaturas normalizadas finais: 85.
- Ficheiros afetados: 42.

### Comparacao com baseline da sprint

Comando:

```bash
php scripts/phpstan-baseline-compare.php storage/phpstan/phpstan-17-before.txt storage/phpstan/phpstan-17-final.txt
```

Resultado:

- Erros anteriores: 209.
- Erros finais: 96.
- Assinaturas normalizadas anteriores: 198.
- Assinaturas normalizadas finais: 85.
- Corrigidos: 113.
- Novos: 0.
- Estado: passed.

### Pint

Comando:

```bash
./vendor/bin/pint --test
```

Resultado:

- Passou.

### Rotas

Comando:

```bash
php artisan route:list --except-vendor
```

Resultado:

- Passou.
- 1083 rotas apresentadas.

## Erros residuais PHPStan

Distribuicao final por identificador:

| Identificador | Quantidade |
| --- | ---: |
| method.nonObject | 18 |
| nullsafe.neverNull | 18 |
| property.nonObject | 11 |
| argument.type | 9 |
| identical.alwaysFalse | 8 |
| notIdentical.alwaysTrue | 7 |
| deadCode.unreachable | 5 |
| nullCoalesce.expr | 3 |
| function.impossibleType | 3 |
| argument.templateType | 2 |
| instanceof.alwaysTrue | 2 |
| booleanAnd.alwaysFalse | 2 |
| property.notFound | 2 |
| arguments.count | 1 |
| property.onlyWritten | 1 |
| booleanOr.alwaysTrue | 1 |
| notIdentical.alwaysFalse | 1 |
| function.alreadyNarrowedType | 1 |
| nullCoalesce.offset | 1 |

## Riscos residuais

- Permanecem erros que exigem revisao funcional cuidadosa, especialmente `method.nonObject`, `property.nonObject`, condicoes sempre verdadeiras/falsas e dead code.
- Estes erros nao devem ser corrigidos automaticamente sem testes de dominio, porque podem tocar regras de elegibilidade, scoring, workflows ou estados administrativos.
- A sprint nao introduziu baseline, suppressions ou `ignoreErrors`.

## Recomendacao

Encerrar a PHPSTAN-17 como concluida.

A proxima etapa deve ser uma sprint especifica de hardening funcional sobre os 96 erros residuais, priorizando:

1. `method.nonObject` e `property.nonObject`.
2. Comparacoes impossiveis com enums/estados.
3. Dead code em services de workflow.
4. `argument.type` em pontos de dominio com testes dedicados.
