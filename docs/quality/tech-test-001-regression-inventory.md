# TECH-TEST-001 — Inventário de regressões 45B–45D

Base auditada: `49507e7cee0519c010dfa5dd0967bff0652a62df`

HEAD herdado: `d00406d3b22fee5dabfd326495eac752d9276312`

## Resumo

- Ficheiros: **93** (45 adicionados; 48 modificados).
- Origem provável: 45B=50; 45C=16; 45D=27.
- Risco alto: 43.
- Cobertura multi-Município detetada: 2.
- Nenhum ficheiro ficou sem classificação.

## Decisões da auditoria

- As ativações `FeatureKey::cases()` usadas como fixture foram classificadas como **correta mas acoplada** e corrigidas na 46A para intake/review/export explícitos.
- As remoções assinaladas em `AuditAccessRoutesCommandTest` preservam `assertSame` e apenas atualizam contadores reais.
- A alteração de `assertForbidden()` para `assertOk()` em `RbacCharacterizationTest` valida a migração intencional para permission middleware; não é relaxamento arbitrário.
- As wildcard permissions permanecem apenas nos testes que verificam explicitamente a semântica wildcard.
- Não foram encontrados mocks de Gate, Policies, `User::hasPermission`, entitlement ou scope municipal em testes end-to-end.

## Inventário

| Ficheiro | Estado | Sprint | Tipo | Domínio | Classificação | Risco | Ação |
|---|---:|---:|---|---|---|---:|---|
| `tests/Concerns/InteractsWithMunicipalFeatures.php` | A | 45D | feature | segurança e autorização | correta mas acoplada | médio | Corrigida na 46A: API variádica, Municipality explícito no enable e sem ativação global/default. |
| `tests/Feature/Backoffice/CustomRoleManagementTest.php` | A | 45C | feature | RBAC e perfis municipais | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Backoffice/MunicipalRoleTemplateTest.php` | A | 45C | feature | RBAC e perfis municipais | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Backoffice/MunicipalityFeatureManagementTest.php` | A | 45D | feature | entitlements municipais | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Backoffice/PermissionMatrixTest.php` | A | 45C | feature | permissões e middleware | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/DocumentIntelligence/DocumentAiManualReviewExecutionTest.php` | M | 45B | feature | documentos e revisão documental | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Documents/DocumentSecurityFlowTest.php` | M | 45B | feature | documentos e revisão documental | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Entitlements/MunicipalityEntitlementServiceTest.php` | A | 45D | feature | entitlements municipais | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Entitlements/MunicipalityFeatureDependencyTest.php` | A | 45D | feature | entitlements municipais | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Entitlements/MunicipalityFeatureEntitlementPersistenceTest.php` | A | 45D | feature | entitlements municipais | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Entitlements/MunicipalityFeatureNavigationTest.php` | A | 45D | feature | entitlements municipais | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Integrated/FullHousingProgramFlowTest.php` | M | 45C | feature | segurança e autorização | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Performance/BasicLoadSmokeTest.php` | M | 45C | feature | segurança e autorização | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/PublicPortal/PublicHousingOfferSprint20Test.php` | M | 45B | feature | portal público de fogos | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/QA45DashboardsKpisMunicipalReportsTest.php` | M | 45C | feature | relatórios e exportações | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Reports/MunicipalExportsSecurityTest.php` | M | 45C | feature | segurança e autorização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Reports/MunicipalReportsAuthorizationTest.php` | M | 45C | feature | relatórios e exportações | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/AdditionalDocumentBackofficePermissionAccessTest.php` | A | 45B | feature | documentos e revisão documental | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/AdministrativeProcessBackofficePolicyTest.php` | A | 45B | feature | processo administrativo | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/AdministrativeProcessBackofficeRouteAccessTest.php` | A | 45B | feature | processo administrativo | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/AdministrativeProcessIndexPermissionAccessTest.php` | A | 45B | feature | processo administrativo | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/AdministrativeProcessMutationPermissionAccessTest.php` | A | 45B | feature | processo administrativo | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/AllocationLotteriesPermissionAccessTest.php` | A | 45B | feature | atribuição | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/AllocationOperationsPermissionAccessTest.php` | A | 45B | feature | atribuição | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/AllocationReportsPermissionAccessTest.php` | A | 45B | feature | atribuição | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/AllocationRuleSetsPermissionAccessTest.php` | A | 45B | feature | atribuição | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/AllocationRunsPermissionAccessTest.php` | A | 45B | feature | atribuição | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/ApplicationArtifactPolicyTest.php` | A | 45B | feature | artefactos de candidatura | correta mas acoplada | alto | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Security/ApplicationArtifactRouteAccessTest.php` | A | 45B | feature | artefactos de candidatura | correta mas acoplada | alto | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Security/ApplicationBackofficePolicyTest.php` | A | 45B | feature | candidaturas backoffice | correta mas acoplada | alto | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Security/ApplicationBackofficeRouteAccessTest.php` | A | 45B | feature | candidaturas backoffice | correta mas acoplada | alto | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Security/ApplicationFeatureEntitlementAccessTest.php` | A | 45D | feature | entitlements de candidaturas | correta mas acoplada | alto | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Security/ApplicationIntakePermissionAccessTest.php` | A | 45B | feature | receção de candidaturas | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/ApplicationProcessTrackingPermissionAccessTest.php` | A | 45B | feature | acompanhamento processual | correta mas acoplada | alto | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Security/ApplicationReviewPermissionAccessTest.php` | A | 45B | feature | análise de candidaturas | correta mas acoplada | alto | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Security/AuditAccessRoutesCommandTest.php` | M | 45B | feature | auditoria de rotas | correta | alto | Contadores estritos atualizados para os valores reais; assertions equivalentes preservadas. |
| `tests/Feature/Security/CandidateBackofficeBoundaryTest.php` | A | 45C | feature | boundary candidato/backoffice | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/CustomRoleAuditTest.php` | A | 45C | feature | RBAC e perfis municipais | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/CustomRoleEffectivePermissionTest.php` | A | 45C | feature | RBAC e perfis municipais | correta | alto | Wildcard mantido exclusivamente para testar a semântica wildcard do RBAC. |
| `tests/Feature/Security/DocumentConfigurationPermissionAccessTest.php` | A | 45B | feature | documentos e revisão documental | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/DocumentDossierRouteIntegrityTest.php` | A | 45B | feature | documentos e revisão documental | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/DocumentReviewPermissionAccessTest.php` | A | 45B | feature | documentos e revisão documental | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/HousingUnitPermissionAccessTest.php` | A | 45B | feature | fogos municipais | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/InitialMunicipalRoleProfilesTest.php` | A | 45C | feature | RBAC e perfis municipais | correta mas acoplada | alto | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Security/MunicipalityFeatureAuditTest.php` | A | 45D | feature | entitlements municipais | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/MunicipalityFeatureMiddlewareTest.php` | A | 45D | feature | entitlements municipais | correta mas acoplada | alto | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Security/PermissionSensitiveMfaTest.php` | A | 45C | feature | permissões e middleware | correta mas acoplada | alto | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Security/ProgramContestPermissionAccessTest.php` | A | 45B | feature | programas e concursos | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/PublicHousingUnitPermissionAccessTest.php` | A | 45B | feature | fogos municipais | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/PublicPortalSettingsPermissionAccessTest.php` | A | 45B | feature | configuração do portal público | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/RbacCharacterizationTest.php` | M | 45B | feature | RBAC | correta | alto | Expectativas atualizadas para permission middleware, MFA efetivo e guards adicionados. |
| `tests/Feature/Security/RequirePermissionMiddlewareTest.php` | A | 45B | feature | permissões e middleware | correta | alto | Wildcard mantido exclusivamente para testar a semântica wildcard do RBAC. |
| `tests/Feature/Security/SystemRoleProtectionTest.php` | A | 45C | feature | RBAC e perfis municipais | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Security/TypologyAdequacyRulesPermissionAccessTest.php` | A | 45B | feature | regras de tipologia | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Sprint12AllocationTest.php` | M | 45B | feature | atribuição | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Sprint17ReportingDashboardTest.php` | M | 45C | feature | relatórios | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Sprint23ProcessTrackingTest.php` | M | 45B | feature | acompanhamento processual | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Sprint24BackofficeOperationalTest.php` | M | 45B | feature | operação backoffice | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Sprint3PortalProgramsTest.php` | M | 45B | feature | portal público | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Sprint6DocumentManagementTest.php` | M | 45B | feature | documentos e revisão documental | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Sprint7EligibilityEngineTest.php` | M | 45C | feature | elegibilidade | correta | médio | Sem correção funcional; manter como regressão ativa. |
| `tests/Feature/Sprint8ApplicationSubmissionTest.php` | M | 45B | feature | candidaturas | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/Sprint9AdministrativeWorkflowTest.php` | M | 45B | feature | processo administrativo | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/AccessibilitySmokeTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/ApplicationCaseWorkspaceTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/CaseChecklistTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/CaseNextActionTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/CaseTimelineTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/CaseWorkspaceAuthorizationTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/CaseWorkspaceResponsiveSmokeTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/CaseWorkspaceRgpdTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/CaseWorkspaceTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/CaseWorkspaceVisualConsistencyTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/Concerns/CreatesEnterpriseCaseFixtures.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/DashboardVisualConsistencyTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Cobertura UX preservada; fixture municipal explícita revista na 46A. |
| `tests/Feature/UX/DesignSystemRgpdTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/MainDashboardVisualStructureTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Cobertura UX preservada; fixture municipal explícita revista na 46A. |
| `tests/Feature/UX/MunicipalUnifiedPlatformTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Cobertura UX preservada; fixture municipal explícita revista na 46A. |
| `tests/Feature/UX/PortugueseTerminologySmokeTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/PortugueseTerminologyTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/PriorityOperationsQueueTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/ProfileDashboardTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/ProfileDashboardWidgetsTest.php` | M | 45D | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/SmartActionCenterTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Cobertura UX preservada; fixture municipal explícita revista na 46A. |
| `tests/Feature/UX/UnifiedPlatformAccessibilityTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Cobertura UX preservada; fixture municipal explícita revista na 46A. |
| `tests/Feature/UX/UniversalSearchTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Feature/UX/WorkspaceDashboardTest.php` | M | 45B | feature | experiência de utilização | correta mas acoplada | médio | Cobertura UX preservada; fixture municipal explícita revista na 46A. |
| `tests/Unit/Cases/CaseWorkspaceServiceTest.php` | M | 45D | unitário | Case Workspace | correta mas acoplada | baixo | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Unit/Dashboard/DashboardWidgetRegistryTest.php` | M | 45D | unitário | dashboard | correta mas acoplada | baixo | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Unit/Dashboard/ProfileDashboardServiceTest.php` | M | 45D | unitário | dashboard | correta mas acoplada | baixo | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |
| `tests/Unit/DocumentIntelligence/DocumentDuplicateDetectorTest.php` | M | 45B | unitário | documentos e revisão documental | correta | baixo | Sem correção funcional; manter como regressão ativa. |
| `tests/Unit/Entitlements/FeatureKeyTest.php` | A | 45D | unitário | infraestrutura técnica | correta | alto | Sem correção funcional; manter como regressão ativa. |
| `tests/Unit/Search/UniversalSearchServiceTest.php` | M | 45D | unitário | pesquisa universal | correta mas acoplada | baixo | Corrigida na 46A: substituída a ativação global por features explícitas e mínimas. |

## Detalhe estruturado

O detalhe por ficheiro, incluindo features, permissions, Policies, assertions, mocks, helpers, alterações MFA/Município/entitlement e cobertura multi-Município, está em `tech-test-001-regression-inventory.json`.
