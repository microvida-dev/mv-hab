# Sprint 45B — Autorização baseada em permissões no contexto de atribuições

## Resumo

A Sprint 45B concluiu a migração incremental do bounded context de atribuições habitacionais para autorização baseada em permissões, preservando rotas, URLs, route model binding, payloads, serviços de domínio, regras de negócio e fluxos administrativos existentes.

A intervenção ficou limitada ao objetivo da Sprint 45B:

- consolidar `userHasPermission`;
- consolidar `policyAllowsRecordScope`;
- manter `municipalityHasFeature` fora do âmbito, reservado para a Sprint 45C;
- não implementar billing, pacotes comerciais, trials, limites, entitlements ou feature flags comerciais.

Branch: `sprint-45b-permission-based-authorization`

## Commits funcionais

- `e0ae3155 feat(access): authorize typology adequacy rules by permission`
- `382e8f9c feat(access): authorize allocation rule sets by permission`
- `30d24b46 feat(access): authorize allocation runs by permission`
- `f07e3d38 feat(access): authorize allocation lotteries by permission`
- `aa363cf0 feat(access): authorize allocation reports by permission`

## Blocos migrados

Foram migradas 35 rotas do contexto de atribuições:

- Regras de adequação tipológica: 7 rotas
- Conjuntos de regras de atribuição: 9 rotas
- Execuções de atribuição: 7 rotas
- Lotarias de atribuição: 7 rotas
- Relatórios de atribuição: 5 rotas

Todas as rotas migradas passaram a usar:

- `auth`
- `active.backoffice`
- `mfa.backoffice`
- `log.backoffice`
- `permission:<permissão>`

E deixam de depender do middleware fixo:

- `role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor`

## Matriz de permissões

### Regras de adequação tipológica

- `index`: `allocations.view`
- `create`, `store`: `allocations.create`
- `edit`, `update`, `activate`, `deactivate`: `allocations.update`

### Conjuntos de regras de atribuição

- `index`, `show`: `allocations.view`
- `create`, `store`, `duplicate`: `allocations.create`
- `edit`, `update`, `archive`: `allocations.update`
- `activate`: `allocations.approve`

### Execuções de atribuição

- `index`, `show`: `allocations.view`
- `create`, `store`: `allocations.create`
- `run`: `allocations.update`
- `lock`: `allocations.approve`
- `cancel`: `allocations.reject`

### Lotarias de atribuição

- `index`, `show`: `allocations.view`
- `create`, `store`: `allocations.create`
- `run`: `allocations.update`
- `lock`: `allocations.approve`
- `audit`: `allocations.audit`

### Relatórios de atribuição

- `index`, `show`: `allocations.view`
- `store`: `allocations.create`
- `approve`: `allocations.approve`
- `download`: `allocations.export`

## Ficheiros funcionais alterados nos commits da sprint

- `routes/web.php`
- `app/Http/Controllers/Backoffice/TypologyAdequacyRuleController.php`
- `app/Http/Controllers/Backoffice/AllocationRuleSetController.php`
- `app/Http/Controllers/Backoffice/AllocationRunController.php`
- `app/Http/Controllers/Backoffice/LotteryRunController.php`
- `app/Http/Controllers/Backoffice/AllocationReportController.php`
- `app/Http/Requests/StoreTypologyAdequacyRuleRequest.php`
- `app/Http/Requests/UpdateTypologyAdequacyRuleRequest.php`
- `app/Http/Requests/StoreAllocationRuleSetRequest.php`
- `app/Http/Requests/UpdateAllocationRuleSetRequest.php`
- `app/Http/Requests/RunAllocationRequest.php`
- `app/Http/Requests/CancelAllocationRequest.php`
- `app/Http/Requests/RunLotteryRequest.php`
- `app/Http/Requests/LockLotteryRunRequest.php`
- `app/Http/Requests/GenerateAllocationReportRequest.php`
- `app/Http/Requests/ApproveAllocationReportRequest.php`
- `app/Policies/TypologyAdequacyRulePolicy.php`
- `app/Policies/AllocationRuleSetPolicy.php`
- `app/Policies/AllocationRunPolicy.php`
- `app/Policies/LotteryRunPolicy.php`
- `app/Policies/AllocationReportPolicy.php`

## Testes criados/reforçados

- `tests/Feature/Security/TypologyAdequacyRulesPermissionAccessTest.php`
- `tests/Feature/Security/AllocationRuleSetsPermissionAccessTest.php`
- `tests/Feature/Security/AllocationRunsPermissionAccessTest.php`
- `tests/Feature/Security/AllocationLotteriesPermissionAccessTest.php`
- `tests/Feature/Security/AllocationReportsPermissionAccessTest.php`
- `tests/Feature/Security/AuditAccessRoutesCommandTest.php`

No fecho da sprint foram também alinhados testes de regressão afetados por MFA/backoffice guards, dashboard UX atual e duplicação documental:

- `tests/Feature/DocumentIntelligence/DocumentAiManualReviewExecutionTest.php`
- `tests/Feature/Security/RbacCharacterizationTest.php`
- `tests/Feature/Sprint12AllocationTest.php`
- `tests/Feature/Sprint23ProcessTrackingTest.php`
- `tests/Feature/Sprint24BackofficeOperationalTest.php`
- `tests/Feature/Sprint8ApplicationSubmissionTest.php`
- `tests/Feature/Sprint9AdministrativeWorkflowTest.php`
- `tests/Feature/UX/AccessibilitySmokeTest.php`
- `tests/Feature/UX/CaseWorkspaceTest.php`
- `tests/Feature/UX/DashboardVisualConsistencyTest.php`
- `tests/Feature/UX/MainDashboardVisualStructureTest.php`
- `tests/Feature/UX/MunicipalUnifiedPlatformTest.php`
- `tests/Feature/UX/PortugueseTerminologySmokeTest.php`
- `tests/Feature/UX/ProfileDashboardTest.php`
- `tests/Feature/UX/SmartActionCenterTest.php`
- `tests/Feature/UX/UnifiedPlatformAccessibilityTest.php`
- `tests/Feature/UX/UniversalSearchTest.php`
- `tests/Feature/UX/WorkspaceDashboardTest.php`
- `tests/Unit/DocumentIntelligence/DocumentDuplicateDetectorTest.php`

## Decisões de segurança

- A autorização passou a existir em três camadas quando aplicável: middleware de rota, Form Request e Policy/Gate.
- Foram adicionadas abilities específicas de backoffice, preservando abilities antigas.
- Utilizadores `candidate` continuam bloqueados em rotas backoffice.
- Utilizadores `auditor` mantêm leitura/auditoria quando aplicável, sem mutações.
- Ações recusadas em testes preservam estado e não executam transições.
- MFA, utilizador ativo e log backoffice foram mantidos nas rotas migradas.

## RGPD e auditoria

- Não foram expostos novos dados pessoais.
- Não houve alteração a documentos privados.
- Não houve alteração a logs de auditoria existentes.
- Não houve alteração a retenção, exportações ou payloads com PII.
- Não houve alteração a policies fora do bounded context migrado.

## Base de dados

Não foram criadas migrations.

Não houve alteração de schema.

Não houve alteração de seeders no âmbito desta sprint.

## Auditoria de rotas

Auditoria inicial esperada antes dos 35 endpoints finais:

- `total_routes`: 1146
- `fixed_role_routes`: 986
- `backoffice_fixed_role_routes`: 765
- `candidate_fixed_role_routes`: 220
- `permission_middleware_routes`: 116
- `backoffice_fixed_role_without_active_backoffice`: 643
- `backoffice_fixed_role_without_mfa_backoffice`: 643
- `backoffice_fixed_role_without_log_backoffice`: 643

Auditoria final executada:

- `total_routes`: 1146
- `fixed_role_routes`: 951
- `backoffice_fixed_role_routes`: 730
- `candidate_fixed_role_routes`: 220
- `permission_middleware_routes`: 151
- `backoffice_fixed_role_without_active_backoffice`: 608
- `backoffice_fixed_role_without_mfa_backoffice`: 608
- `backoffice_fixed_role_without_log_backoffice`: 608

Diferença confirmada:

- `fixed_role_routes`: -35
- `backoffice_fixed_role_routes`: -35
- `permission_middleware_routes`: +35
- rotas backoffice sem `active/mfa/log`: -35

## Validações executadas

Passaram:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Resultado: 957 testes, 6642 assertions.

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UX --stop-on-failure
```

Resultado: 129 testes, 642 assertions.

```bash
php artisan test \
  tests/Feature/Security/TypologyAdequacyRulesPermissionAccessTest.php \
  tests/Feature/Security/AllocationRuleSetsPermissionAccessTest.php \
  tests/Feature/Security/AllocationRunsPermissionAccessTest.php \
  tests/Feature/Security/AllocationLotteriesPermissionAccessTest.php \
  tests/Feature/Security/AllocationReportsPermissionAccessTest.php \
  tests/Feature/Security/AllocationOperationsPermissionAccessTest.php \
  tests/Feature/Security/PublicPortalSettingsPermissionAccessTest.php \
  tests/Feature/Security/PublicHousingUnitPermissionAccessTest.php \
  tests/Feature/Security/AuditAccessRoutesCommandTest.php
```

Resultado: 80 testes, 1314 assertions.

```bash
php artisan test \
  tests/Unit/Contracts/RentCalculationDeterministicTest.php \
  tests/Unit/Dashboard/LotteryTimelineProviderTest.php \
  tests/Unit/Dashboard/AllocationTimelineProviderTest.php \
  tests/Unit/Dashboard/ContractTimelineProviderTest.php \
  tests/Unit/Simulator/TypologyRecommendationServiceTest.php \
  tests/Unit/Lottery/AuditableLotteryEngineTest.php \
  tests/Feature/QA26ContractsRentTenantPortalTest.php \
  tests/Feature/Backoffice/LotteryClosureFlowTest.php \
  tests/Feature/UX/ContractCaseWorkspaceTest.php \
  tests/Feature/Sprint13ContractsRentDepositTest.php \
  tests/Feature/Sprint12AllocationTest.php
```

Resultado: 36 testes, 244 assertions.

```bash
composer validate --strict
php artisan optimize:clear
npm run build
git diff --check
php artisan route:list --except-vendor
php artisan access:audit-routes --format=json
php artisan access:audit-routes --only-fixed-role --format=json
```

Resultados: comandos concluídos com sucesso.

```bash
./vendor/bin/pint --test \
  tests/Feature/DocumentIntelligence/DocumentAiManualReviewExecutionTest.php \
  tests/Feature/Security/RbacCharacterizationTest.php \
  tests/Feature/Sprint12AllocationTest.php \
  tests/Feature/Sprint23ProcessTrackingTest.php \
  tests/Feature/Sprint24BackofficeOperationalTest.php \
  tests/Feature/Sprint8ApplicationSubmissionTest.php \
  tests/Feature/Sprint9AdministrativeWorkflowTest.php \
  tests/Feature/UX/AccessibilitySmokeTest.php \
  tests/Feature/UX/CaseWorkspaceTest.php \
  tests/Feature/UX/DashboardVisualConsistencyTest.php \
  tests/Feature/UX/MainDashboardVisualStructureTest.php \
  tests/Feature/UX/MunicipalUnifiedPlatformTest.php \
  tests/Feature/UX/PortugueseTerminologySmokeTest.php \
  tests/Feature/UX/ProfileDashboardTest.php \
  tests/Feature/UX/SmartActionCenterTest.php \
  tests/Feature/UX/UnifiedPlatformAccessibilityTest.php \
  tests/Feature/UX/UniversalSearchTest.php \
  tests/Feature/UX/WorkspaceDashboardTest.php \
  tests/Unit/DocumentIntelligence/DocumentDuplicateDetectorTest.php
```

Resultado: passou nos ficheiros alterados no fecho.

## Gates globais com risco aceite

O comando global seguinte falhou por dívida de formatação em ficheiros fora do diff de fecho e fora do bounded context desta sprint:

```bash
./vendor/bin/pint --test
```

O comando global seguinte falhou com 157 erros PHPStan em áreas amplas fora do escopo imediato da Sprint 45B, incluindo Agenda, Dashboard/Timeline, Document Review, favoritos de navegação, Procedure Minutes e seeders:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v
```

Estes problemas não foram corrigidos nesta sprint para respeitar o limite funcional do pedido: autorização baseada em permissões no contexto de atribuições. Abrir essa correção implicaria uma intervenção transversal fora do bounded context.

## Riscos residuais

- Restam 951 rotas com middleware fixo no total da plataforma.
- Restam 730 rotas backoffice com middleware fixo.
- Restam 220 rotas candidate com middleware fixo.
- Restam 608 rotas backoffice fixas sem os três guards backoffice obrigatórios.
- O wrapper local `php artisan test` pode esgotar o limite de 128 MB; a suite completa foi validada com `php -d memory_limit=-1 ./vendor/bin/phpunit`.
- Pint global e PHPStan global continuam a refletir dívida técnica herdada fora do escopo desta sprint.

## Fora do âmbito preservado

Não foi implementado:

- Sprint 45C
- planos comerciais
- pacotes
- trials
- suspensão
- expiração
- limites comerciais
- ativação comercial de módulos
- billing
- feature flags comerciais

## Decisão final

`PASS_WITH_ACCEPTED_RISKS`

A migração da Sprint 45B no bounded context de atribuições foi concluída e validada por testes de segurança, testes funcionais relacionados, suite completa PHPUnit, build, route list e auditoria de rotas. O PASS puro fica condicionado por Pint/PHPStan globais, que revelam dívida transversal fora do âmbito autorizado desta sprint.
