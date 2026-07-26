<?php

namespace App\Services\Access;

use App\Enums\BackofficeRouteBoundedContext;
use App\Enums\RouteInventoryRisk;
use App\Models\Permission;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

/**
 * @phpstan-type InventoryRouteRow array{
 *     route_name: string|null,
 *     uri: string,
 *     http_methods: list<string>,
 *     controller_class: string|null,
 *     controller_method: string|null,
 *     middleware_resolved: list<string>,
 *     active_backoffice_present: bool,
 *     mfa_backoffice_present: bool,
 *     log_backoffice_present: bool,
 *     role_middleware_active: string|null,
 *     role_middleware_excluded: list<string>,
 *     permission_middleware: list<string>,
 *     permission_catalog_exists: bool,
 *     semantic_permission_available: bool,
 *     permission_recommendation: string|null,
 *     permission_semantically_adequate: bool,
 *     policy_class: string|null,
 *     policy_ability: string|null,
 *     policy_ability_source: string,
 *     form_request: string|null,
 *     form_request_authorize: string,
 *     feature_entitlement: list<string>,
 *     feature_required: bool|null,
 *     feature_key: string|null,
 *     municipality_source: string,
 *     municipal_record_scope: string,
 *     fail_closed_without_municipality: bool|null,
 *     platform_route: bool,
 *     municipal_route: bool,
 *     mixed_context_route: bool,
 *     record_model: string|null,
 *     operation_type: 'read'|'mutation',
 *     mfa_sensitive: bool,
 *     audit_requirement: 'required'|'recommended'|'not_required',
 *     audit_implementation: 'confirmed'|'missing'|'unknown',
 *     private_data: bool,
 *     bounded_context: string,
 *     bounded_context_label: string,
 *     target_sprint: string,
 *     risk: string,
 *     confidence: 'confirmed'|'high'|'medium'|'low'|'unknown',
 *     test_coverage: 'route_name_reference'|'not_detected',
 *     test_sources: list<string>,
 *     source: list<string>,
 *     migration_recommendation: string
 * }
 * @phpstan-type InventorySummary array{
 *     route_collection_total: int,
 *     inventoried_routes: int,
 *     fixed_role_routes: int,
 *     permission_middleware_routes: int,
 *     missing_permission_routes: int,
 *     missing_policy_routes: int,
 *     missing_scope_routes: int,
 *     mutations_without_audit: int,
 *     residual_routes: int,
 *     missing_active_backoffice_routes: int,
 *     missing_mfa_backoffice_routes: int,
 *     missing_log_backoffice_routes: int,
 *     routes_without_detected_tests: int,
 *     mixed_context_routes: int,
 *     platform_routes: int,
 *     feature_decision_pending_routes: int,
 *     by_bounded_context: array<string, int>,
 *     by_risk: array<string, int>,
 *     by_target_sprint: array<string, int>
 * }
 */
class BackofficeRouteInventoryService
{
    /** @var array<string, list<string>> */
    private const CONTEXT_NEEDLES = [
        'administration_security' => [
            'access-audit', 'access.audit', 'access-logs', 'access.logs',
            'security', 'security-alert', 'security.alert', 'permission-review',
            'permission.review', 'roles', 'role-', 'role.', 'sessions',
        ],
        'users_teams' => [
            'users', 'user-administration', 'user.administration', 'teams',
            'team-members', 'team.members', 'competenc',
        ],
        'rgpd' => [
            'rgpd', 'privacy', 'data-subject', 'data.subject', 'retention',
            'anonymization', 'consent', 'data-export', 'data.export',
        ],
        'decisions' => [
            'administrative-decision', 'administrative.decision',
            'complaint-decision', 'complaint.decision',
        ],
        'scoring' => [
            'scoring', 'score', 'ranking', 'tie-break', 'tie.break',
            'classification',
        ],
        'eligibility' => [
            'eligibility', 'eligible', 'eligibility-rule', 'eligibility.rule',
        ],
        'hearings' => [
            'hearing', 'audience', 'preliminary-hearing',
            'preliminary.hearing',
        ],
        'complaints' => [
            'complaint', 'additional-information', 'additional.information',
        ],
        'lists' => [
            'backoffice.lists.', 'backoffice/lists/',
            'provisional-list', 'provisional.list', 'definitive-list',
            'definitive.list', 'public-list', 'public.list', 'list-publication',
            'list.publication',
        ],
        'allocations' => [
            'allocation', 'lottery', 'draw-', 'draw.', 'withdrawal',
            'contest-closure', 'contest.closure', 'post-draw', 'post.draw',
        ],
        'documents' => [
            'document', 'generated-document', 'generated.document',
            'official-document', 'official.document',
        ],
        'administrative_processes' => [
            'administrative-process', 'administrative.process',
            'application-review', 'application.review', 'correction-request',
            'correction.request', 'correction-response', 'correction.response',
            'application-inconsistenc', 'process-confirmation',
            'process.confirmation', 'administrative-task', 'administrative.task',
            'administrative-note', 'administrative.note',
        ],
        'applications' => [
            'application', 'candidate', 'citizen', 'household', 'income-record',
            'income.record', 'adhesion', 'simulator',
        ],
        'payments' => [
            'payment', 'invoice', 'receipt', 'tenant-charge',
            'tenant.charge', 'rent-installment', 'rent.installment',
        ],
        'finance' => [
            'finance', 'rent-', 'rent.', 'arrear', 'deposit',
            'regularization', 'financial',
        ],
        'contracts' => [
            'contract', 'key-handover', 'key.handover', 'tenant-transition',
            'tenant.transition', 'tenant-operation', 'tenant.operation', 'lease',
        ],
        'inspections' => ['inspection', 'vistoria', 'checklist-template'],
        'visits' => [
            'housing-visit', 'housing.visit', 'visit-slot', 'visit.slot',
            'visit-availabilit', 'visit.availabilit', 'open-house',
        ],
        'agenda' => ['agenda', 'calendar', 'timeline'],
        'maintenance' => [
            'maintenance', 'intervention', 'supplier', 'property',
            'housing-unit', 'housing.unit',
        ],
        'reports' => [
            'report', 'analytics', 'dashboard', 'indicator', 'export',
            'productivity', 'workspace', 'search',
        ],
        'notifications' => [
            'notification', 'internal-alert', 'internal.alert',
            'work-task', 'work.task',
        ],
        'communications' => [
            'communication', 'message', 'support-ticket', 'support.ticket',
            'contextual-faq', 'contextual.faq', 'procedure-minute',
            'procedure.minute', 'procedure-template', 'procedure.template',
        ],
        'configuration' => [
            'settings', 'configuration', 'config', 'program', 'contest',
            'municipality', 'template', 'catalog',
        ],
    ];

    /** @var array<string, string> */
    private const CONTEXT_MODULES = [
        'administration_security' => 'security',
        'users_teams' => 'users',
        'applications' => 'applications',
        'documents' => 'documents',
        'administrative_processes' => 'administrative_processes',
        'eligibility' => 'eligibility',
        'scoring' => 'scoring',
        'decisions' => 'administrative_processes',
        'hearings' => 'complaints',
        'complaints' => 'complaints',
        'lists' => 'public_lists',
        'allocations' => 'allocations',
        'contracts' => 'contracts',
        'finance' => 'finance',
        'payments' => 'payments',
        'maintenance' => 'maintenance_requests',
        'inspections' => 'inspections',
        'visits' => 'visits',
        'agenda' => 'visits',
        'reports' => 'reports',
        'communications' => 'notifications',
        'notifications' => 'notifications',
        'rgpd' => 'privacy',
        'configuration' => 'settings',
        'residual' => 'settings',
    ];

    /** @var list<string> */
    private const SENSITIVE_ACTIONS = [
        'approve', 'reject', 'delete', 'destroy', 'publish', 'export',
        'download', 'audit', 'assign', 'remove', 'reassign', 'lock',
        'sign', 'activate', 'suspend', 'cancel', 'terminate', 'reverse',
        'import', 'execute', 'revoke', 'reset', 'disable', 'anonymize',
    ];

    /** @var list<string> */
    private const READ_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /** @var array<string, string> */
    private array $fileContents = [];

    /** @var list<string>|null */
    private ?array $permissionCatalog = null;

    /** @var array<string, list<string>>|null */
    private ?array $routeTestSources = null;

    public function __construct(private readonly Gate $gate) {}

    /**
     * @return Collection<int, InventoryRouteRow>
     */
    public function inventory(): Collection
    {
        /** @var Collection<int, InventoryRouteRow> $routes */
        $routes = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => $this->isBackofficeRoute($route))
            ->map(fn (Route $route): array => $this->inspect($route))
            ->sortBy([
                ['target_sprint', 'asc'],
                ['bounded_context', 'asc'],
                ['risk', 'asc'],
                ['route_name', 'asc'],
                ['uri', 'asc'],
            ])
            ->values();

        return $routes;
    }

    /**
     * @param  Collection<int, covariant InventoryRouteRow>  $rows
     * @return InventorySummary
     */
    public function summary(Collection $rows): array
    {
        return [
            'route_collection_total' => count(RouteFacade::getRoutes()->getRoutes()),
            'inventoried_routes' => $rows->count(),
            'fixed_role_routes' => $rows
                ->filter(fn (array $row): bool => is_string($row['role_middleware_active']))
                ->count(),
            'permission_middleware_routes' => $rows
                ->filter(fn (array $row): bool => $row['permission_middleware'] !== [])
                ->count(),
            'missing_permission_routes' => $rows
                ->where('permission_semantically_adequate', false)
                ->count(),
            'missing_policy_routes' => $rows
                ->filter(
                    fn (array $row): bool => $row['record_model'] !== null
                        && $row['policy_class'] === null
                )
                ->count(),
            'missing_scope_routes' => $rows
                ->where('municipal_record_scope', 'missing')
                ->count(),
            'mutations_without_audit' => $rows
                ->where('operation_type', 'mutation')
                ->where('audit_requirement', 'required')
                ->where('audit_implementation', 'missing')
                ->count(),
            'residual_routes' => $rows
                ->where('bounded_context', BackofficeRouteBoundedContext::Residual->value)
                ->count(),
            'missing_active_backoffice_routes' => $rows
                ->where('active_backoffice_present', false)
                ->count(),
            'missing_mfa_backoffice_routes' => $rows
                ->where('mfa_backoffice_present', false)
                ->count(),
            'missing_log_backoffice_routes' => $rows
                ->where('log_backoffice_present', false)
                ->count(),
            'routes_without_detected_tests' => $rows
                ->where('test_coverage', 'not_detected')
                ->count(),
            'mixed_context_routes' => $rows
                ->where('mixed_context_route', true)
                ->count(),
            'platform_routes' => $rows
                ->where('platform_route', true)
                ->count(),
            'feature_decision_pending_routes' => $rows
                ->whereNull('feature_required')
                ->count(),
            'by_bounded_context' => $this->countBy($rows, 'bounded_context'),
            'by_risk' => $this->countBy($rows, 'risk'),
            'by_target_sprint' => $this->countBy($rows, 'target_sprint'),
        ];
    }

    /**
     * @return InventoryRouteRow
     */
    private function inspect(Route $route): array
    {
        $declaredMiddleware = $this->stringList($route->gatherMiddleware());
        $excludedMiddleware = $this->stringList($route->excludedMiddleware());
        $middleware = $this->stringList(
            app('router')->resolveMiddleware($declaredMiddleware, $excludedMiddleware)
        );

        [$controllerClass, $controllerMethod, $method] = $this->controller($route);
        $controllerSource = $method instanceof ReflectionMethod
            ? $this->methodSource($method)
            : '';
        $classSource = is_string($controllerClass)
            ? $this->classSource($controllerClass)
            : '';

        $formRequest = $method instanceof ReflectionMethod
            ? $this->formRequest($method)
            : null;
        $formRequestSource = is_string($formRequest)
            ? $this->classSource($formRequest)
            : '';

        $recordModel = $method instanceof ReflectionMethod
            ? $this->recordModel($method, $route, $controllerClass)
            : $this->modelFromRouteParameters($route);
        $policyClass = $this->policyClass($recordModel);
        $policySource = is_string($policyClass)
            ? $this->classSource($policyClass)
            : '';
        $relatedServiceSource = $method instanceof ReflectionMethod
            ? $this->relatedServiceSource($method)
            : '';
        $combinedSource = implode("\n", [
            $controllerSource,
            $classSource,
            $formRequestSource,
            $policySource,
            $relatedServiceSource,
        ]);

        $routeIdentity = strtolower(implode(' ', array_filter([
            $route->getName(),
            $route->uri(),
            $controllerClass,
            $controllerMethod,
        ])));
        $context = $this->boundedContext($routeIdentity);
        $risk = $this->risk($context, $routeIdentity);
        $operationType = $this->operationType($route);
        $roleMiddleware = $this->firstMiddleware($middleware, 'role:');
        $permissionMiddleware = $this->middlewareValues($middleware, 'permission:');
        $permissionNames = $this->permissionNames($permissionMiddleware);
        $permissionRecommendation = $this->permissionRecommendation(
            context: $context,
            routeIdentity: $routeIdentity,
            controllerMethod: $controllerMethod,
        );
        $permissionCatalog = $this->permissionCatalog();
        $semanticPermissionAvailable = is_string($permissionRecommendation)
            && in_array($permissionRecommendation, $permissionCatalog, true);
        $middlewarePermissionsExist = $permissionNames !== []
            && collect($permissionNames)->every(
                fn (string $permission): bool => in_array($permission, $permissionCatalog, true)
            );
        $permissionCatalogExists = $middlewarePermissionsExist
            || $semanticPermissionAvailable;
        $permissionAdequate = is_string($permissionRecommendation)
            && in_array($permissionRecommendation, $permissionNames, true);
        $featureEntitlements = $this->middlewareValues(
            $middleware,
            'municipality.feature:',
        );
        [$featureRequired, $featureKey] = $this->featureDecision(
            context: $context,
            routeIdentity: $routeIdentity,
            featureEntitlements: $featureEntitlements,
        );
        $mixedContext = $this->isMixedContext($routeIdentity);
        $platformRoute = $this->isPlatformRoute($routeIdentity);
        $municipalRoute = ! $platformRoute && ! $mixedContext;
        $scope = $this->scopeFinding(
            combinedSource: $combinedSource,
            recordModel: $recordModel,
            municipalRoute: $municipalRoute,
            platformRoute: $platformRoute,
        );
        [$policyAbility, $policyAbilitySource] = $this->policyAbility(
            methodSource: $controllerSource."\n".$formRequestSource,
            policyClass: $policyClass,
            permissionRecommendation: $permissionRecommendation,
            controllerMethod: $controllerMethod,
        );
        $auditRequirement = $this->auditRequirement(
            operationType: $operationType,
            risk: $risk,
            privateData: $this->containsPrivateData($context),
        );
        $auditImplementation = $this->auditImplementation(
            combinedSource: $combinedSource,
            requirement: $auditRequirement,
        );
        $confidence = $this->confidence(
            context: $context,
            controllerClass: $controllerClass,
            permissionRecommendation: $permissionRecommendation,
            recordModel: $recordModel,
        );
        $testSources = $this->testSources($route->getName());

        return [
            'route_name' => $route->getName(),
            'uri' => $route->uri(),
            'http_methods' => $this->stringList(array_diff($route->methods(), ['HEAD'])),
            'controller_class' => $controllerClass,
            'controller_method' => $controllerMethod,
            'middleware_resolved' => $middleware,
            'active_backoffice_present' => in_array('active.backoffice', $middleware, true),
            'mfa_backoffice_present' => in_array('mfa.backoffice', $middleware, true),
            'log_backoffice_present' => in_array('log.backoffice', $middleware, true),
            'role_middleware_active' => $roleMiddleware,
            'role_middleware_excluded' => array_values(array_filter(
                $excludedMiddleware,
                fn (string $item): bool => str_starts_with($item, 'role:'),
            )),
            'permission_middleware' => $permissionMiddleware,
            'permission_catalog_exists' => $permissionCatalogExists,
            'semantic_permission_available' => $semanticPermissionAvailable,
            'permission_recommendation' => $permissionRecommendation,
            'permission_semantically_adequate' => $permissionAdequate,
            'policy_class' => $policyClass,
            'policy_ability' => $policyAbility,
            'policy_ability_source' => $policyAbilitySource,
            'form_request' => $formRequest,
            'form_request_authorize' => $this->formRequestAuthorization($formRequest),
            'feature_entitlement' => $featureEntitlements,
            'feature_required' => $featureRequired,
            'feature_key' => $featureKey,
            'municipality_source' => $this->municipalitySource(
                platformRoute: $platformRoute,
                mixedContext: $mixedContext,
                recordModel: $recordModel,
                featureEntitlements: $featureEntitlements,
            ),
            'municipal_record_scope' => $scope,
            'fail_closed_without_municipality' => match ($scope) {
                'confirmed' => true,
                'missing' => false,
                default => $platformRoute ? null : ($featureEntitlements !== [] ? true : null),
            },
            'platform_route' => $platformRoute,
            'municipal_route' => $municipalRoute,
            'mixed_context_route' => $mixedContext,
            'record_model' => $recordModel,
            'operation_type' => $operationType,
            'mfa_sensitive' => $this->mfaSensitive(
                routeIdentity: $routeIdentity,
                risk: $risk,
                operationType: $operationType,
            ),
            'audit_requirement' => $auditRequirement,
            'audit_implementation' => $auditImplementation,
            'private_data' => $this->containsPrivateData($context),
            'bounded_context' => $context->value,
            'bounded_context_label' => $context->label(),
            'target_sprint' => $context->targetSprint(),
            'risk' => $risk->value,
            'confidence' => $confidence,
            'test_coverage' => $testSources === []
                ? 'not_detected'
                : 'route_name_reference',
            'test_sources' => $testSources,
            'source' => $this->sources(
                controllerClass: $controllerClass,
                method: $method,
                formRequest: $formRequest,
                policyClass: $policyClass,
                permissionCatalogExists: $permissionCatalogExists,
                testsDetected: $testSources !== [],
            ),
            'migration_recommendation' => $this->migrationRecommendation(
                roleMiddleware: $roleMiddleware,
                permissionRecommendation: $permissionRecommendation,
                permissionCatalogExists: $permissionCatalogExists,
                policyClass: $policyClass,
                recordModel: $recordModel,
                scope: $scope,
                context: $context,
            ),
        ];
    }

    private function isBackofficeRoute(Route $route): bool
    {
        $middleware = $this->stringList(
            app('router')->resolveMiddleware(
                $this->stringList($route->gatherMiddleware()),
                $this->stringList($route->excludedMiddleware()),
            )
        );
        $roleMiddleware = $this->firstMiddleware($middleware, 'role:');

        if (is_string($roleMiddleware)) {
            $roles = array_values(array_filter(
                explode(',', substr($roleMiddleware, strlen('role:')))
            ));

            if ($roles !== [] && ! in_array('candidate', $roles, true)) {
                return true;
            }
        }

        $name = (string) $route->getName();
        $uri = $route->uri();

        return str_starts_with($name, 'backoffice.')
            || str_starts_with($name, 'admin.')
            || str_starts_with($uri, 'backoffice/')
            || str_starts_with($uri, 'admin/')
            || in_array('active.backoffice', $middleware, true)
            || in_array('mfa.backoffice', $middleware, true)
            || in_array('log.backoffice', $middleware, true);
    }

    /**
     * @return array{0: string|null, 1: string|null, 2: ReflectionMethod|null}
     */
    private function controller(Route $route): array
    {
        $action = $route->getActionName();

        if ($action === 'Closure') {
            return [null, null, null];
        }

        [$class, $method] = str_contains($action, '@')
            ? explode('@', $action, 2)
            : [$action, '__invoke'];

        if (! class_exists($class) || ! method_exists($class, $method)) {
            return [$class, $method, null];
        }

        try {
            return [$class, $method, new ReflectionMethod($class, $method)];
        } catch (Throwable) {
            return [$class, $method, null];
        }
    }

    private function boundedContext(string $routeIdentity): BackofficeRouteBoundedContext
    {
        foreach (self::CONTEXT_NEEDLES as $context => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($routeIdentity, $needle)) {
                    return BackofficeRouteBoundedContext::from($context);
                }
            }
        }

        return BackofficeRouteBoundedContext::Residual;
    }

    private function risk(
        BackofficeRouteBoundedContext $context,
        string $routeIdentity,
    ): RouteInventoryRisk {
        if ($this->containsAny($routeIdentity, [
            'export', 'download', 'private', 'signature', 'password', 'mfa',
        ])) {
            return RouteInventoryRisk::Critical;
        }

        return match ($context) {
            BackofficeRouteBoundedContext::AdministrationSecurity,
            BackofficeRouteBoundedContext::UsersTeams,
            BackofficeRouteBoundedContext::Documents,
            BackofficeRouteBoundedContext::Decisions,
            BackofficeRouteBoundedContext::Contracts,
            BackofficeRouteBoundedContext::Finance,
            BackofficeRouteBoundedContext::Payments,
            BackofficeRouteBoundedContext::Rgpd => RouteInventoryRisk::Critical,
            BackofficeRouteBoundedContext::Applications,
            BackofficeRouteBoundedContext::AdministrativeProcesses,
            BackofficeRouteBoundedContext::Eligibility,
            BackofficeRouteBoundedContext::Scoring,
            BackofficeRouteBoundedContext::Hearings,
            BackofficeRouteBoundedContext::Complaints,
            BackofficeRouteBoundedContext::Lists,
            BackofficeRouteBoundedContext::Allocations => RouteInventoryRisk::High,
            BackofficeRouteBoundedContext::Maintenance,
            BackofficeRouteBoundedContext::Inspections,
            BackofficeRouteBoundedContext::Visits,
            BackofficeRouteBoundedContext::Agenda,
            BackofficeRouteBoundedContext::Communications,
            BackofficeRouteBoundedContext::Notifications => RouteInventoryRisk::Medium,
            BackofficeRouteBoundedContext::Reports,
            BackofficeRouteBoundedContext::Configuration,
            BackofficeRouteBoundedContext::Residual => RouteInventoryRisk::Low,
        };
    }

    /**
     * @return 'read'|'mutation'
     */
    private function operationType(Route $route): string
    {
        return collect($route->methods())
            ->reject(fn (string $method): bool => $method === 'HEAD')
            ->every(fn (string $method): bool => in_array($method, self::READ_METHODS, true))
                ? 'read'
                : 'mutation';
    }

    private function permissionRecommendation(
        BackofficeRouteBoundedContext $context,
        string $routeIdentity,
        ?string $controllerMethod,
    ): ?string {
        $module = $this->permissionModule($context, $routeIdentity);
        $action = $this->permissionAction($routeIdentity, $controllerMethod);
        $candidate = "{$module}.{$action}";
        $catalog = $this->permissionCatalog();

        if (in_array($candidate, $catalog, true)) {
            return $candidate;
        }

        return null;
    }

    private function permissionModule(
        BackofficeRouteBoundedContext $context,
        string $routeIdentity,
    ): string {
        $specific = [
            'roles' => 'roles',
            'teams' => 'teams',
            'users' => 'users',
            'access-audit' => 'access_audit',
            'access.audit' => 'access_audit',
            'audit-log' => 'audit_logs',
            'privacy' => 'privacy',
            'rgpd' => 'rgpd',
            'citizen' => 'citizens',
            'household' => 'households',
            'income-record' => 'income_records',
            'adhesion' => 'adhesion_registrations',
            'work-task' => 'work_tasks',
            'support-ticket' => 'support',
            'contextual-faq' => 'contextual_faqs',
            'housing-unit' => 'housing_units',
            'program' => 'programs',
            'contest' => 'contests',
            'simulator' => 'simulator',
        ];

        foreach ($specific as $needle => $module) {
            if (str_contains($routeIdentity, $needle)) {
                return $module;
            }
        }

        return self::CONTEXT_MODULES[$context->value];
    }

    private function permissionAction(
        string $routeIdentity,
        ?string $controllerMethod,
    ): string {
        $action = strtolower((string) $controllerMethod);
        $identity = $action === '' || $action === '__invoke'
            ? $routeIdentity
            : $action;
        $specific = [
            'view_access_logs' => ['access-log', 'access.log'],
            'revoke_sessions' => ['revoke-session', 'revoke.session'],
            'reset_password' => ['reset-password', 'reset.password'],
            'force_mfa' => ['force-mfa', 'force.mfa'],
            'manage_members' => ['manage-member', 'member'],
            'view_team' => ['team'],
            'manage_sla' => ['sla'],
            'update_status' => ['status'],
            'reassign' => ['reassign'],
            'assign' => ['assign'],
            'claim' => ['claim'],
            'complete' => ['complete'],
            'reverse' => ['reverse'],
            'import' => ['import'],
            'sign' => ['sign'],
            'activate' => ['activate'],
            'suspend' => ['suspend'],
            'terminate' => ['terminate'],
            'validate' => ['validate'],
            'confirm' => ['confirm'],
            'lock' => ['lock'],
            'run' => ['run', 'execute', 'recalculate'],
            'reschedule' => ['reschedule'],
            'generate' => ['generate'],
            'cancel' => ['cancel'],
            'publish' => ['publish'],
            'approve' => ['approve'],
            'reject' => ['reject'],
            'audit' => ['audit'],
            'download' => ['download'],
            'export' => ['export'],
            'delete' => ['destroy', 'delete'],
            'create' => ['create', 'store', 'duplicate'],
            'update' => [
                'edit', 'update', 'review', 'close', 'archive', 'block', 'unblock',
            ],
        ];

        foreach ($specific as $permissionAction => $needles) {
            if ($this->containsAny($identity, $needles)) {
                return $permissionAction;
            }
        }

        return 'view';
    }

    /**
     * @param  list<string>  $featureEntitlements
     * @return array{0: bool|null, 1: string|null}
     */
    private function featureDecision(
        BackofficeRouteBoundedContext $context,
        string $routeIdentity,
        array $featureEntitlements,
    ): array {
        if ($featureEntitlements !== []) {
            return [true, $featureEntitlements[0]];
        }

        if ($context === BackofficeRouteBoundedContext::Applications) {
            return [
                true,
                $this->containsAny($routeIdentity, ['create', 'store', 'intake'])
                    ? 'applications.intake'
                    : 'applications.review',
            ];
        }

        if ($context === BackofficeRouteBoundedContext::AdministrativeProcesses) {
            return [
                true,
                str_contains($routeIdentity, 'intake')
                    ? 'applications.intake'
                    : 'applications.review',
            ];
        }

        if ($context === BackofficeRouteBoundedContext::Eligibility) {
            return [true, 'applications.review'];
        }

        if (
            $context === BackofficeRouteBoundedContext::Documents
            && ! $this->containsAny($routeIdentity, [
                'document-type', 'required-document', 'template', 'configuration',
            ])
        ) {
            return [true, 'applications.review'];
        }

        if (
            $context === BackofficeRouteBoundedContext::Reports
            && str_contains($routeIdentity, 'application')
            && $this->containsAny($routeIdentity, ['export', 'download', 'report'])
        ) {
            return [true, 'applications.export'];
        }

        if ($this->isMixedContext($routeIdentity)) {
            return [null, null];
        }

        return [false, null];
    }

    private function isMixedContext(string $routeIdentity): bool
    {
        return $this->containsAny($routeIdentity, [
            'dashboard', 'workspace', 'search', 'analytics', 'productivity',
            'report-run', 'report.run', 'report-export', 'report.export',
        ]);
    }

    private function isPlatformRoute(string $routeIdentity): bool
    {
        return $this->containsAny($routeIdentity, [
            'backoffice.platform.', 'municipality-feature',
            'municipality.feature', 'platform-operator', 'platform.operator',
        ]);
    }

    private function scopeFinding(
        string $combinedSource,
        ?string $recordModel,
        bool $municipalRoute,
        bool $platformRoute,
    ): string {
        if ($platformRoute) {
            return 'not_applicable';
        }

        if ($this->containsAny($combinedSource, [
            'MunicipalRecordScopeService',
            'sameMunicipality',
            'forMunicipality',
            'municipality_id',
            'municipalityId',
            'belongsToMunicipality',
        ])) {
            return 'confirmed';
        }

        if (! $municipalRoute) {
            return 'candidate';
        }

        return is_string($recordModel) ? 'missing' : 'candidate';
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function policyAbility(
        string $methodSource,
        ?string $policyClass,
        ?string $permissionRecommendation,
        ?string $controllerMethod,
    ): array {
        if (preg_match(
            "/(?:Gate::(?:authorize|allows|denies)|->(?:authorize|can))\\(\\s*['\"]([^'\"]+)['\"]/",
            $methodSource,
            $matches,
        ) === 1) {
            return [$matches[1], 'confirmed'];
        }

        if (! is_string($policyClass) || ! class_exists($policyClass)) {
            return [null, 'missing'];
        }

        $action = is_string($permissionRecommendation)
            ? Str::afterLast($permissionRecommendation, '.')
            : 'view';
        $candidates = match ($action) {
            'view' => strtolower((string) $controllerMethod) === 'index'
                ? ['viewAnyBackoffice', 'viewAny', 'viewBackoffice', 'view']
                : ['viewBackoffice', 'view', 'viewAnyBackoffice', 'viewAny'],
            default => [
                Str::camel($action).'Backoffice',
                Str::camel($action),
                'updateBackoffice',
                'update',
            ],
        };

        foreach ($candidates as $candidate) {
            if (method_exists($policyClass, $candidate)) {
                return [$candidate, 'inferred'];
            }
        }

        return [null, 'missing'];
    }

    private function formRequest(?ReflectionMethod $method): ?string
    {
        if (! $method instanceof ReflectionMethod) {
            return null;
        }

        foreach ($method->getParameters() as $parameter) {
            foreach ($this->parameterClasses($parameter) as $class) {
                if (is_subclass_of($class, FormRequest::class)) {
                    return $class;
                }
            }
        }

        return null;
    }

    private function formRequestAuthorization(?string $formRequest): string
    {
        if (! is_string($formRequest) || ! class_exists($formRequest)) {
            return 'none';
        }

        try {
            $reflection = new ReflectionClass($formRequest);

            if (! $reflection->hasMethod('authorize')) {
                return 'inherited';
            }

            $method = $reflection->getMethod('authorize');
            $source = $this->methodSource($method);

            if (preg_match('/return\\s+true\\s*;/', $source) === 1) {
                return 'always_true';
            }

            if (preg_match('/return\\s+false\\s*;/', $source) === 1) {
                return 'always_false';
            }

            if ($this->containsAny($source, ['Gate::', '->can(', '->cannot('])) {
                return 'gate_or_policy';
            }

            return $method->getDeclaringClass()->getName() === $formRequest
                ? 'custom'
                : 'inherited';
        } catch (Throwable) {
            return 'unknown';
        }
    }

    private function recordModel(
        ReflectionMethod $method,
        Route $route,
        ?string $controllerClass,
    ): ?string {
        foreach ($method->getParameters() as $parameter) {
            foreach ($this->parameterClasses($parameter) as $class) {
                if (is_subclass_of($class, Model::class)) {
                    return $class;
                }
            }
        }

        return $this->modelFromRouteParameters($route)
            ?? $this->modelFromController($controllerClass);
    }

    private function modelFromRouteParameters(Route $route): ?string
    {
        foreach ($route->parameterNames() as $parameter) {
            $candidate = 'App\\Models\\'.Str::studly(Str::singular($parameter));

            if (class_exists($candidate) && is_subclass_of($candidate, Model::class)) {
                return $candidate;
            }
        }

        return null;
    }

    private function modelFromController(?string $controllerClass): ?string
    {
        if (! is_string($controllerClass) || ! class_exists($controllerClass)) {
            return null;
        }

        try {
            $shortName = (new ReflectionClass($controllerClass))->getShortName();
            $candidate = 'App\\Models\\'.Str::beforeLast($shortName, 'Controller');

            return class_exists($candidate) && is_subclass_of($candidate, Model::class)
                ? $candidate
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function policyClass(?string $recordModel): ?string
    {
        if (! is_string($recordModel) || ! class_exists($recordModel)) {
            return null;
        }

        try {
            $policy = $this->gate->getPolicyFor($recordModel);

            if (is_object($policy)) {
                return $policy::class;
            }

            if (is_string($policy)) {
                return $policy;
            }
        } catch (Throwable) {
            // Fall through to the conventional policy name.
        }

        $candidate = 'App\\Policies\\'.class_basename($recordModel).'Policy';

        return class_exists($candidate) ? $candidate : null;
    }

    /**
     * @return list<string>
     */
    private function parameterClasses(ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();
        $types = $type instanceof ReflectionUnionType
            ? $type->getTypes()
            : ($type instanceof ReflectionNamedType ? [$type] : []);
        $classes = [];

        foreach ($types as $namedType) {
            if ($namedType instanceof ReflectionNamedType && ! $namedType->isBuiltin()) {
                $classes[] = $namedType->getName();
            }
        }

        return array_values(array_unique($classes));
    }

    /**
     * @param  list<string>  $featureEntitlements
     */
    private function municipalitySource(
        bool $platformRoute,
        bool $mixedContext,
        ?string $recordModel,
        array $featureEntitlements,
    ): string {
        if ($platformRoute) {
            return 'explicit_platform_scope';
        }

        if ($mixedContext) {
            return 'dynamic_domain_context';
        }

        if (is_string($recordModel)) {
            return 'authenticated_user_and_route_bound_record';
        }

        return $featureEntitlements !== []
            ? 'authenticated_user.municipality_id'
            : 'authenticated_user';
    }

    /**
     * @return 'required'|'recommended'|'not_required'
     */
    private function auditRequirement(
        string $operationType,
        RouteInventoryRisk $risk,
        bool $privateData,
    ): string {
        if ($operationType === 'mutation') {
            return 'required';
        }

        if ($privateData || $risk === RouteInventoryRisk::Critical) {
            return 'recommended';
        }

        return 'not_required';
    }

    /**
     * @return 'confirmed'|'missing'|'unknown'
     */
    private function auditImplementation(string $combinedSource, string $requirement): string
    {
        if ($this->containsAny($combinedSource, [
            'AuditLogger', 'AuditEvents', 'AuditTrail', 'AccessChangeLogger',
            'auditLogger', '->audit(', '->logAccess(', '->log(',
        ])) {
            return 'confirmed';
        }

        return $requirement === 'required' ? 'missing' : 'unknown';
    }

    private function containsPrivateData(BackofficeRouteBoundedContext $context): bool
    {
        return in_array($context, [
            BackofficeRouteBoundedContext::AdministrationSecurity,
            BackofficeRouteBoundedContext::UsersTeams,
            BackofficeRouteBoundedContext::Applications,
            BackofficeRouteBoundedContext::Documents,
            BackofficeRouteBoundedContext::AdministrativeProcesses,
            BackofficeRouteBoundedContext::Eligibility,
            BackofficeRouteBoundedContext::Decisions,
            BackofficeRouteBoundedContext::Hearings,
            BackofficeRouteBoundedContext::Complaints,
            BackofficeRouteBoundedContext::Contracts,
            BackofficeRouteBoundedContext::Finance,
            BackofficeRouteBoundedContext::Payments,
            BackofficeRouteBoundedContext::Communications,
            BackofficeRouteBoundedContext::Rgpd,
        ], true);
    }

    private function mfaSensitive(
        string $routeIdentity,
        RouteInventoryRisk $risk,
        string $operationType,
    ): bool {
        return $risk === RouteInventoryRisk::Critical
            || $operationType === 'mutation'
            || $this->containsAny($routeIdentity, self::SENSITIVE_ACTIONS);
    }

    /**
     * @return 'confirmed'|'high'|'medium'|'low'|'unknown'
     */
    private function confidence(
        BackofficeRouteBoundedContext $context,
        ?string $controllerClass,
        ?string $permissionRecommendation,
        ?string $recordModel,
    ): string {
        if ($context === BackofficeRouteBoundedContext::Residual) {
            return 'low';
        }

        if (
            is_string($controllerClass)
            && is_string($permissionRecommendation)
            && is_string($recordModel)
        ) {
            return 'high';
        }

        if (is_string($controllerClass) && is_string($permissionRecommendation)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @return list<string>
     */
    private function sources(
        ?string $controllerClass,
        ?ReflectionMethod $method,
        ?string $formRequest,
        ?string $policyClass,
        bool $permissionCatalogExists,
        bool $testsDetected,
    ): array {
        $sources = ['laravel_route_collection', 'resolved_middleware'];

        if (is_string($controllerClass)) {
            $sources[] = 'controller_reflection';
        }

        if ($method instanceof ReflectionMethod) {
            $sources[] = 'controller_source';
        }

        if (is_string($formRequest)) {
            $sources[] = 'form_request_reflection';
        }

        if (is_string($policyClass)) {
            $sources[] = 'policy_discovery';
        }

        if ($permissionCatalogExists) {
            $sources[] = 'permission_catalog';
        }

        if ($testsDetected) {
            $sources[] = 'existing_test_route_reference';
        }

        return $sources;
    }

    /**
     * @return list<string>
     */
    private function testSources(?string $routeName): array
    {
        if (! is_string($routeName) || $routeName === '') {
            return [];
        }

        return $this->routeTestSourceIndex()[$routeName] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    private function routeTestSourceIndex(): array
    {
        if (is_array($this->routeTestSources)) {
            return $this->routeTestSources;
        }

        $index = [];
        $files = File::allFiles(base_path('tests'));
        usort(
            $files,
            fn (SplFileInfo $left, SplFileInfo $right): int => strcmp(
                $left->getPathname(),
                $right->getPathname(),
            ),
        );

        foreach ($files as $file) {
            $contents = File::get($file->getPathname());
            $matches = [];

            if (preg_match_all(
                '/[\'"]((?:backoffice|admin)\.[A-Za-z0-9_.-]+)[\'"]/',
                $contents,
                $matches,
            ) === false) {
                continue;
            }

            $path = Str::after(
                $file->getPathname(),
                base_path().DIRECTORY_SEPARATOR,
            );

            foreach (array_unique($matches[1]) as $routeName) {
                $index[$routeName] ??= [];
                $index[$routeName][] = $path;
            }
        }

        ksort($index);

        foreach ($index as &$sources) {
            $sources = array_values(array_unique($sources));
            sort($sources);
        }
        unset($sources);

        return $this->routeTestSources = $index;
    }

    private function migrationRecommendation(
        ?string $roleMiddleware,
        ?string $permissionRecommendation,
        bool $permissionCatalogExists,
        ?string $policyClass,
        ?string $recordModel,
        string $scope,
        BackofficeRouteBoundedContext $context,
    ): string {
        $steps = [];

        if (is_string($roleMiddleware)) {
            $steps[] = 'remove_fixed_role';
        }

        if (! is_string($permissionRecommendation) || ! $permissionCatalogExists) {
            $steps[] = 'define_semantic_permission';
        } else {
            $steps[] = 'apply_permission:'.$permissionRecommendation;
        }

        if (is_string($recordModel) && ! is_string($policyClass)) {
            $steps[] = 'add_policy';
        }

        if ($scope === 'missing') {
            $steps[] = 'enforce_municipal_scope';
        }

        $steps[] = 'preserve_active_mfa_log';
        $steps[] = 'add_http_security_tests';

        if ($context === BackofficeRouteBoundedContext::Residual) {
            $steps[] = 'manual_context_review';
        }

        return implode(';', $steps);
    }

    private function methodSource(ReflectionMethod $method): string
    {
        $file = $method->getFileName();

        if (! is_string($file)) {
            return '';
        }

        $contents = $this->fileContents($file);

        if ($contents === '') {
            return '';
        }

        $lines = explode("\n", $contents);
        $start = max(0, $method->getStartLine() - 1);
        $length = max(1, $method->getEndLine() - $method->getStartLine() + 1);

        return implode("\n", array_slice($lines, $start, $length));
    }

    private function classSource(string $class): string
    {
        if (! class_exists($class)) {
            return '';
        }

        try {
            $file = (new ReflectionClass($class))->getFileName();

            return is_string($file) ? $this->fileContents($file) : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function relatedServiceSource(ReflectionMethod $method): string
    {
        $classes = [];
        $declaringClass = $method->getDeclaringClass();
        $constructor = $declaringClass->getConstructor();

        if ($constructor instanceof ReflectionMethod) {
            foreach ($constructor->getParameters() as $parameter) {
                $classes = [...$classes, ...$this->parameterClasses($parameter)];
            }
        }

        foreach ($method->getParameters() as $parameter) {
            $classes = [...$classes, ...$this->parameterClasses($parameter)];
        }

        return collect($classes)
            ->filter(fn (string $class): bool => str_starts_with($class, 'App\\Services\\'))
            ->unique()
            ->map(fn (string $class): string => $this->classSource($class))
            ->implode("\n");
    }

    private function fileContents(string $file): string
    {
        if (! array_key_exists($file, $this->fileContents)) {
            $contents = @file_get_contents($file);
            $this->fileContents[$file] = is_string($contents) ? $contents : '';
        }

        return $this->fileContents[$file];
    }

    /**
     * @return list<string>
     */
    private function permissionCatalog(): array
    {
        if (is_array($this->permissionCatalog)) {
            return $this->permissionCatalog;
        }

        if (! Schema::hasTable('permissions')) {
            return $this->permissionCatalog = [];
        }

        $permissions = Permission::query()
            ->where('name', '!=', '*')
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn (mixed $name): bool => is_string($name))
            ->map(fn (mixed $name): string => (string) $name)
            ->values()
            ->all();

        return $this->permissionCatalog = array_values($permissions);
    }

    /**
     * @param  list<string>  $middleware
     * @return list<string>
     */
    private function middlewareValues(array $middleware, string $prefix): array
    {
        return array_values(array_map(
            fn (string $item): string => substr($item, strlen($prefix)),
            array_filter(
                $middleware,
                fn (string $item): bool => str_starts_with($item, $prefix),
            ),
        ));
    }

    /**
     * @param  list<string>  $permissionMiddleware
     * @return list<string>
     */
    private function permissionNames(array $permissionMiddleware): array
    {
        $permissions = [];

        foreach ($permissionMiddleware as $item) {
            foreach (explode(',', $item) as $permission) {
                if ($permission !== '') {
                    $permissions[] = $permission;
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * @param  list<string>  $middleware
     */
    private function firstMiddleware(array $middleware, string $prefix): ?string
    {
        foreach ($middleware as $item) {
            if (str_starts_with($item, $prefix)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, covariant InventoryRouteRow>  $rows
     * @return array<string, int>
     */
    private function countBy(Collection $rows, string $key): array
    {
        /** @var array<string, int> $counts */
        $counts = $rows
            ->countBy(function (array $row) use ($key): string {
                $value = $row[$key] ?? null;

                return is_string($value) ? $value : 'unknown';
            })
            ->sortKeys()
            ->all();

        return $counts;
    }

    /**
     * @param  list<string>  $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<array-key, string>  $items
     * @return list<string>
     */
    private function stringList(array $items): array
    {
        return array_values($items);
    }
}
