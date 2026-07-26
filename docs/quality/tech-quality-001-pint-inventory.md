# TECH-QUALITY-001 — Inventário Pint

## Baseline

- Branch-base: `610138a8ce35a29f5f4d5bf95543fbbdc58591c9`
- Ficheiros com diferenças Pint: **65**
- Migrations históricas: **0**
- Models/Policies: **0**
- Factories/Seeders: **0**
- Scripts/Commands: **0**

## Grupo 1 — Controllers e Form Requests (8)

- `app/Http/Requests/StoreHousingUnitRequest.php`
- `app/Http/Requests/Backoffice/PublicPortal/StoreHousingUnitPublicDocumentRequest.php`
- `app/Http/Requests/Backoffice/PublicPortal/StoreHousingUnitImageRequest.php`
- `app/Http/Requests/Backoffice/PublicPortal/StorePublicPortalLinkRequest.php`
- `app/Http/Requests/Backoffice/PublicPortal/UpdatePublicPortalSettingsRequest.php`
- `app/Http/Controllers/Candidate/SimulationController.php`
- `app/Http/Controllers/Navigation/FavoriteController.php`
- `app/Http/Controllers/Navigation/WorkspaceController.php`

## Grupo 2 — Services documentais e processuais (7)

- `app/Services/DocumentIntelligence/DocumentAiAssistantPersister.php`
- `app/Services/DocumentIntelligence/RegexFieldExtractor.php`
- `app/Services/DocumentIntelligence/DocumentAiScoreCalculator.php`
- `app/Services/DocumentIntelligence/DocumentExtractionPersister.php`
- `app/Services/ProcedureMinutes/Renderers/AlcanenaAta01Renderer.php`
- `app/Services/ProcedureMinutes/ProcedureMinuteService.php`
- `app/Services/Documents/DocumentAccessService.php`

## Grupo 3 — Dashboard e Agenda (27)

- `app/Services/Dashboard/Operations/TodayProvider.php`
- `app/Services/Dashboard/Timeline/Providers/TenantOperationsTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/LotteryTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/ApplicationTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/DocumentTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/InternalAlertTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/CorrectionRequestTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/InspectionTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/KeyHandoverTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/AllocationTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/DeadlineTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/WorkTaskTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/HearingTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/RgpdTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/RentTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/VisitTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/MaintenanceTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/ComplaintTimelineProvider.php`
- `app/Services/Dashboard/Timeline/Providers/ContractTimelineProvider.php`
- `app/Services/Dashboard/Timeline/TimelineProviderInterface.php`
- `app/Services/Dashboard/Timeline/TimelineProviderRegistry.php`
- `app/Services/Dashboard/Timeline/TimelineAggregatorService.php`
- `app/Services/Dashboard/ProfileDashboardService.php`
- `app/Services/Agenda/AgendaService.php`
- `app/Services/Agenda/Builders/AgendaMonthBuilder.php`
- `app/Services/Agenda/Builders/AgendaDayBuilder.php`
- `app/Services/Agenda/Builders/AgendaWeekBuilder.php`

## Grupo 4 — Testes (23)

- `tests/Unit/Dashboard/LotteryTimelineProviderTest.php`
- `tests/Unit/Dashboard/TimelineAggregatorServiceTest.php`
- `tests/Unit/Dashboard/AllocationTimelineProviderTest.php`
- `tests/Unit/Dashboard/RentTimelineProviderTest.php`
- `tests/Unit/Dashboard/TimelineEventFactoryTest.php`
- `tests/Unit/Dashboard/BaseTimelineProviderArchitectureTest.php`
- `tests/Unit/Dashboard/NextActionResolverTest.php`
- `tests/Unit/Dashboard/TenantOperationsTimelineProviderTest.php`
- `tests/Unit/Dashboard/DocumentTimelineProviderTest.php`
- `tests/Unit/Dashboard/TimelineProviderRegistryTest.php`
- `tests/Unit/Dashboard/TimelineMetricsServiceTest.php`
- `tests/Unit/Dashboard/ContractTimelineProviderTest.php`
- `tests/Unit/Agenda/AgendaServiceTest.php`
- `tests/Unit/Agenda/AgendaTimelineRepositoryTest.php`
- `tests/Unit/Agenda/AgendaBuildersTest.php`
- `tests/Unit/Agenda/AgendaEventFilterTest.php`
- `tests/Feature/Sprint3PortalProgramsTest.php`
- `tests/Feature/Security/RequirePermissionMiddlewareTest.php`
- `tests/Feature/Security/AdditionalDocumentBackofficePermissionAccessTest.php`
- `tests/Feature/Security/AdministrativeProcessIndexPermissionAccessTest.php`
- `tests/Feature/Security/AdministrativeProcessMutationPermissionAccessTest.php`
- `tests/Feature/Security/AdministrativeProcessBackofficePolicyTest.php`
- `tests/Feature/Security/DocumentConfigurationPermissionAccessTest.php`

## Regras de execução

Cada grupo é formatado e revisto isoladamente. São admitidas apenas alterações produzidas pelo Pint: imports, braces, trailing commas, alinhamento, espaços, quebras de linha e estilo equivalente. Não são permitidas renomeações, extrações de métodos ou alterações de comportamento.
