<?php

namespace App\Services\Access;

use App\Models\Role;
use App\Services\Security\Program53RateLimitService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Schema;
use JsonException;
use ReflectionMethod;
use Throwable;

/**
 * @phpstan-type Program53AuditCheck array{
 *     code: string,
 *     status: 'pass'|'fail',
 *     message: string,
 *     context: array<string, bool|int|string|list<string>|null>
 * }
 * @phpstan-type Program53AuditResult array{
 *     schema_version: string,
 *     program: string,
 *     sprint: string,
 *     template_key: string,
 *     manifest: string,
 *     summary: array{total: int, passed: int, failed: int, drift: bool},
 *     checks: list<Program53AuditCheck>
 * }
 */
final class Program53AccessAuditService
{
    public const TEMPLATE_KEY = 'analista-candidaturas-exportacao';

    public const MANIFEST_PATH =
        'docs/access/manifests/sprint-53h-program-53-access-manifest.json';

    /** @var array<string, string> */
    private const PRESERVED_TEMPLATE_FINGERPRINTS = [
        'operador-recolha' => '3f601bc74585c6dc99249be19cb4db5fd596c768b0ac2a21cbe43d984abd6fb5',
        'analista-candidaturas' => 'fa8bdef37843b732c93b154939f93d578201f57b6d125116d021a9f90a3a59bf',
        'exportador-candidaturas' => '9df64e0b1fa119a33bc7da0a68496752520db1db690cdcef231fa6547494dc0b',
    ];

    /** @var array<string, string> */
    private const REQUIRED_LIMITERS = [
        Program53RateLimitService::EXPORT_PREVIEW => 'program53.export-preview',
        Program53RateLimitService::EXPORT_REQUEST => 'program53.export-request',
        Program53RateLimitService::EXPORT_DOWNLOAD => 'program53.export-download',
        Program53RateLimitService::BATCH_SEAL => 'program53.batch-seal',
        Program53RateLimitService::BATCH_PUBLISH => 'program53.batch-publish',
        Program53RateLimitService::REVALIDATION_SEAL => 'program53.revalidation-seal',
    ];

    /** @var list<Program53AuditCheck> */
    private array $checks = [];

    public function __construct(
        private readonly MunicipalRoleTemplateRegistry $templates,
        private readonly Program53RateLimitService $rateLimits,
        private readonly RateLimiter $laravelRateLimiter,
    ) {}

    /** @return Program53AuditResult */
    public function audit(): array
    {
        $this->checks = [];
        $manifest = $this->manifest();

        $this->auditManifest($manifest);
        $this->auditTemplates();
        $this->auditRateLimiters();
        $this->auditPersistedRoles();
        $this->auditSegregation();
        $this->auditDirectPermissions();

        usort(
            $this->checks,
            static fn (array $left, array $right): int => strcmp(
                $left['code'],
                $right['code'],
            ),
        );

        $failed = count(array_filter(
            $this->checks,
            static fn (array $check): bool => $check['status'] === 'fail',
        ));

        return [
            'schema_version' => '1.0',
            'program' => '53',
            'sprint' => '53H',
            'template_key' => self::TEMPLATE_KEY,
            'manifest' => self::MANIFEST_PATH,
            'summary' => [
                'total' => count($this->checks),
                'passed' => count($this->checks) - $failed,
                'failed' => $failed,
                'drift' => $failed > 0,
            ],
            'checks' => $this->checks,
        ];
    }

    /** @return array<string, mixed> */
    private function manifest(): array
    {
        $path = base_path(self::MANIFEST_PATH);

        if (! File::isFile($path)) {
            $this->add(
                'manifest.file',
                false,
                'O manifesto determinístico do Programa 53 não existe.',
                ['path' => self::MANIFEST_PATH],
            );

            return [];
        }

        try {
            $decoded = json_decode(
                File::get($path),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            $this->add(
                'manifest.file',
                false,
                'O manifesto do Programa 53 não contém JSON válido.',
                ['path' => self::MANIFEST_PATH],
            );

            return [];
        }

        if (! is_array($decoded)) {
            $this->add(
                'manifest.file',
                false,
                'O manifesto do Programa 53 não contém um objeto JSON.',
                ['path' => self::MANIFEST_PATH],
            );

            return [];
        }

        $this->add(
            'manifest.file',
            true,
            'Manifesto determinístico carregado.',
            ['path' => self::MANIFEST_PATH],
        );

        return $decoded;
    }

    /** @param array<string, mixed> $manifest */
    private function auditManifest(array $manifest): void
    {
        $rawRoutes = $manifest['routes'] ?? null;
        $routes = is_array($rawRoutes)
            ? array_values(array_filter(
                $rawRoutes,
                static fn (mixed $row): bool => is_array($row),
            ))
            : [];
        $expectedCount = $manifest['route_count'] ?? null;

        $this->add(
            'manifest.identity',
            ($manifest['program'] ?? null) === '53'
                && ($manifest['sprint'] ?? null) === '53H'
                && ($manifest['template_key'] ?? null) === self::TEMPLATE_KEY
                && ($manifest['deterministic'] ?? null) === true,
            'Identidade e modo determinístico do manifesto reconciliados.',
        );
        $this->add(
            'manifest.route_count',
            is_int($expectedCount)
                && $expectedCount === count($routes),
            'Contagem declarada de rotas reconciliada.',
            [
                'declared' => is_int($expectedCount) ? $expectedCount : -1,
                'actual' => count($routes),
            ],
        );

        $names = [];

        foreach ($routes as $index => $row) {
            /** @var array<string, mixed> $row */
            $name = $this->string($row['route_name'] ?? null);

            if ($name === null) {
                $this->add(
                    sprintf('route.%03d.name', $index),
                    false,
                    'Entrada do manifesto sem nome de rota válido.',
                );

                continue;
            }

            $names[] = $name;
            $this->auditRoute($name, $row);
        }

        $sortedNames = $names;
        sort($sortedNames, SORT_STRING);
        $this->add(
            'manifest.route_order',
            $names === $sortedNames,
            'Rotas do manifesto possuem ordem determinística.',
        );
        $this->add(
            'manifest.route_names_unique',
            count($names) === count(array_unique($names)),
            'Nomes de rota do manifesto são únicos.',
        );

        $actualRouteNames = collect(RouteFacade::getRoutes()->getRoutes())
            ->map(static fn (Route $route): ?string => $route->getName())
            ->filter(static fn (?string $name): bool => is_string($name))
            ->values();
        $this->add(
            'routes.application_names_unique',
            $actualRouteNames->count()
                === $actualRouteNames->unique()->count(),
            'A aplicação não possui nomes de rota duplicados.',
            ['route_count' => $actualRouteNames->count()],
        );
    }

    /** @param array<string, mixed> $row */
    private function auditRoute(string $name, array $row): void
    {
        $code = 'route.'.$name;
        $route = RouteFacade::getRoutes()->getByName($name);

        if (! $route instanceof Route) {
            $this->add(
                $code.'.exists',
                false,
                'A rota declarada não existe.',
                ['route' => $name],
            );

            return;
        }

        $middleware = array_values(app('router')->resolveMiddleware(
            $route->gatherMiddleware(),
            $route->excludedMiddleware(),
        ));
        $this->add(
            $code.'.guards',
            $this->containsAll($middleware, [
                'auth',
                'active.backoffice',
                'mfa.backoffice',
                'log.backoffice',
            ]),
            'Guards obrigatórios presentes.',
            ['route' => $name],
        );
        $this->add(
            $code.'.role_middleware',
            ! collect($middleware)->contains(
                static fn (string $item): bool => str_starts_with(
                    $item,
                    'role:',
                ),
            ),
            'A rota não depende do nome de uma role.',
            ['route' => $name],
        );

        $permissions = $this->csv($row['permission'] ?? null);
        $expectedPermissionMiddleware = array_map(
            static fn (string $permission): string => 'permission:'.$permission,
            $permissions,
        );
        $this->add(
            $code.'.permissions',
            $permissions !== []
                && $this->containsAll(
                    $middleware,
                    $expectedPermissionMiddleware,
                ),
            'Permissions do manifesto presentes na rota.',
            ['route' => $name, 'permissions' => $permissions],
        );

        $entitlement = $this->string($row['entitlement'] ?? null);
        $this->add(
            $code.'.entitlement',
            $entitlement === null
                || in_array(
                    'municipality.feature:'.$entitlement,
                    $middleware,
                    true,
                ),
            'Entitlement da operação reconciliado.',
            ['route' => $name, 'entitlement' => $entitlement],
        );

        $policy = $this->string($row['policy'] ?? null);
        $ability = $this->string($row['ability'] ?? null);
        $policyValid = $policy !== null
            && $ability !== null
            && class_exists($policy)
            && method_exists($policy, $ability)
            && (new ReflectionMethod($policy, $ability))->isPublic();
        $this->add(
            $code.'.policy',
            $policyValid,
            'Policy e ability públicas existem.',
            ['route' => $name, 'policy' => $policy, 'ability' => $ability],
        );

        $this->add(
            $code.'.mfa',
            ($row['mfa_required'] ?? null) === true
                && in_array('mfa.backoffice', $middleware, true),
            'MFA está declarada e aplicada à rota.',
            ['route' => $name],
        );
        $this->add(
            $code.'.logging',
            $this->string($row['audit_event'] ?? null) !== null
                && in_array('log.backoffice', $middleware, true),
            'Logging e evento de auditoria estão declarados.',
            ['route' => $name],
        );
        $this->add(
            $code.'.scope',
            in_array(
                $row['municipal_scope'] ?? null,
                [
                    'authenticated_user',
                    'authenticated_user_and_route_bound_record',
                ],
                true,
            ),
            'Scope municipal explícito e suportado.',
            ['route' => $name],
        );

        $limiter = $this->string($row['rate_limiter'] ?? null);
        $this->add(
            $code.'.rate_limiter',
            $limiter === null
                || in_array('throttle:'.$limiter, $middleware, true),
            'Rate limiter obrigatório reconciliado.',
            ['route' => $name, 'rate_limiter' => $limiter],
        );

        $this->auditAllowedTemplates(
            code: $code,
            routeName: $name,
            row: $row,
            permissions: $permissions,
        );
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $permissions
     */
    private function auditAllowedTemplates(
        string $code,
        string $routeName,
        array $row,
        array $permissions,
    ): void {
        $allowed = $this->stringList($row['allowed_templates'] ?? null);
        $valid = $allowed !== [];

        foreach ($allowed as $templateKey) {
            try {
                $template = $this->template($templateKey);
            } catch (Throwable) {
                $valid = false;

                continue;
            }

            if (! $this->containsAll($template['permissions'], $permissions)) {
                $valid = false;
            }
        }

        $this->add(
            $code.'.allowed_templates',
            $valid,
            'Templates autorizados satisfazem as permissions declaradas.',
            ['route' => $routeName, 'templates' => $allowed],
        );
    }

    private function auditTemplates(): void
    {
        $templates = collect($this->templates->all())
            ->keyBy('key');
        $new = $templates->get(self::TEMPLATE_KEY);
        $this->add(
            'template.new.exists',
            is_array($new),
            'Template municipal do Programa 53 existe.',
        );

        if (is_array($new)) {
            $this->add(
                'template.new.identity',
                $new['label'] === 'Analista de candidaturas e exportação'
                    && $new['version'] === '1.0.0'
                    && $new['fingerprint'] === '4208acb7b770b5095d63f377a2a974cf457ddcfe8bfda36fa74a089972df6c79',
                'Identidade, versão e fingerprint do template estão fechados.',
                [
                    'version' => $new['version'],
                    'fingerprint' => $new['fingerprint'],
                ],
            );
            $this->add(
                'template.new.permissions',
                count($new['permissions']) === 36
                    && ! collect($new['permissions'])->contains(
                        static fn (string $permission): bool => str_contains(
                            $permission,
                            '*',
                        ),
                    )
                    && ! in_array(
                        'reports.export_sensitive',
                        $new['permissions'],
                        true,
                    ),
                'Matriz do template contém 36 permissions explícitas e exclui exportação sensível.',
                ['permission_count' => count($new['permissions'])],
            );
            $this->add(
                'template.new.entitlements',
                $new['entitlement_dependencies'] === [
                    'applications.review',
                    'applications.export',
                ],
                'Dependências de entitlement são informativas e exatas.',
                ['entitlements' => $new['entitlement_dependencies']],
            );
        }

        foreach (self::PRESERVED_TEMPLATE_FINGERPRINTS as $key => $fingerprint) {
            $template = $templates->get($key);
            $this->add(
                'template.preserved.'.$key,
                is_array($template)
                    && $template['fingerprint'] === $fingerprint,
                'Semântica do template histórico preservada.',
                ['template' => $key],
            );
        }

        $analyst = $templates->get('analista-candidaturas');
        $exporter = $templates->get('exportador-candidaturas');
        $this->add(
            'template.preserved.boundaries',
            is_array($analyst)
                && is_array($exporter)
                && ! in_array('applications.export', $analyst['permissions'], true)
                && ! in_array('documents.approve', $exporter['permissions'], true)
                && ! in_array('documents.reject', $exporter['permissions'], true)
                && ! in_array('reports.export_sensitive', $analyst['permissions'], true)
                && ! in_array('reports.export_sensitive', $exporter['permissions'], true),
            'Fronteiras históricas entre análise, decisão e exportação permanecem intactas.',
        );

        $catalogResolved = true;

        foreach ($templates->keys() as $key) {
            try {
                $this->templates->resolve((string) $key);
            } catch (Throwable) {
                $catalogResolved = false;
            }
        }

        $this->add(
            'template.catalog',
            $catalogResolved,
            'Todas as permissions dos templates existem no catálogo persistido.',
        );
    }

    private function auditRateLimiters(): void
    {
        foreach (self::REQUIRED_LIMITERS as $operation => $name) {
            $registered = $this->laravelRateLimiter->limiter($name);
            $configurationValid = true;

            try {
                $normal = $this->rateLimits->configuration($operation);
                $configurationValid = $normal['user']['max_attempts'] > 0
                    && $normal['municipality']['max_attempts'] > 0;

                if (str_starts_with($operation, 'export_')) {
                    $sensitive = $this->rateLimits->configuration(
                        $operation,
                        'sensitive',
                    );
                    $configurationValid = $configurationValid
                        && $sensitive['user']['max_attempts']
                            <= $normal['user']['max_attempts']
                        && $sensitive['municipality']['max_attempts']
                            <= $normal['municipality']['max_attempts'];
                }
            } catch (Throwable) {
                $configurationValid = false;
            }

            $this->add(
                'rate_limiter.'.$name,
                $registered !== null && $configurationValid,
                'Named limiter registado com configuração válida e fail-closed.',
                ['operation' => $operation, 'rate_limiter' => $name],
            );
        }
    }

    private function auditPersistedRoles(): void
    {
        if (! Schema::hasTable('roles')
            || ! Schema::hasColumn('roles', 'template_key')) {
            $this->add(
                'roles.schema',
                false,
                'Metadata versionada de templates não está migrada.',
            );

            return;
        }

        $roles = Role::query()
            ->whereNotNull('template_key')
            ->with('permissions:id,name')
            ->orderBy('id')
            ->get();
        $valid = true;

        foreach ($roles as $role) {
            $key = $role->template_key;

            if (! is_string($key)) {
                $valid = false;

                continue;
            }

            try {
                $template = $this->template($key);
            } catch (Throwable) {
                $valid = false;

                continue;
            }

            $actual = $role->permissions
                ->pluck('name')
                ->map(static fn ($name): string => (string) $name)
                ->sort()
                ->values()
                ->all();
            $expected = $template['permissions'];
            sort($expected, SORT_STRING);

            if (
                $role->municipality_id === null
                || $role->scope !== 'municipal'
                || $role->is_system
                || ! $role->is_active
                || $role->template_version !== $template['version']
                || $role->template_fingerprint !== $template['fingerprint']
                || $actual !== $expected
            ) {
                $valid = false;
            }
        }

        $this->add(
            'roles.template_instances',
            $valid,
            'Instâncias ligadas a templates permanecem municipais, ativas e sem drift.',
            ['role_count' => $roles->count()],
        );
    }

    private function auditSegregation(): void
    {
        if (! $this->accessTablesAvailable()) {
            $this->add(
                'segregation.assignments',
                false,
                'Tabelas de atribuição de roles indisponíveis.',
            );

            return;
        }

        $candidateConflicts = DB::table('role_user as candidate_assignment')
            ->join(
                'roles as candidate_role',
                'candidate_role.id',
                '=',
                'candidate_assignment.role_id',
            )
            ->join(
                'role_user as municipal_assignment',
                'municipal_assignment.user_id',
                '=',
                'candidate_assignment.user_id',
            )
            ->join(
                'roles as municipal_role',
                'municipal_role.id',
                '=',
                'municipal_assignment.role_id',
            )
            ->where('candidate_role.name', 'candidate')
            ->where('candidate_role.is_active', true)
            ->where('municipal_role.scope', 'municipal')
            ->where('municipal_role.is_system', false)
            ->where('municipal_role.is_active', true)
            ->distinct()
            ->count('candidate_assignment.user_id');
        $mutableKeys = collect($this->templates->all())
            ->filter(
                static fn (array $template): bool => $template['segregation_class']
                    === 'program53_mutable',
            )
            ->pluck('key')
            ->all();
        $auditorConflicts = DB::table('role_user as auditor_assignment')
            ->join(
                'roles as auditor_role',
                'auditor_role.id',
                '=',
                'auditor_assignment.role_id',
            )
            ->join(
                'role_user as mutable_assignment',
                'mutable_assignment.user_id',
                '=',
                'auditor_assignment.user_id',
            )
            ->join(
                'roles as mutable_role',
                'mutable_role.id',
                '=',
                'mutable_assignment.role_id',
            )
            ->where('auditor_role.name', 'auditor')
            ->where('auditor_role.is_active', true)
            ->whereIn('mutable_role.template_key', $mutableKeys)
            ->where('mutable_role.is_active', true)
            ->distinct()
            ->count('auditor_assignment.user_id');

        $this->add(
            'segregation.assignments',
            $candidateConflicts === 0 && $auditorConflicts === 0,
            'Não existem conflitos ativos candidate/backoffice ou auditor/perfil mutável.',
            [
                'candidate_conflicts' => $candidateConflicts,
                'auditor_conflicts' => $auditorConflicts,
            ],
        );
    }

    private function auditDirectPermissions(): void
    {
        $tables = [
            'permission_user',
            'model_has_permissions',
            'user_permissions',
        ];
        $populated = [];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)
                && DB::table($table)->limit(1)->exists()) {
                $populated[] = $table;
            }
        }

        $this->add(
            'permissions.direct_assignments',
            $populated === [],
            'Não existem permissions atribuídas diretamente a utilizadores.',
            ['tables_with_assignments' => $populated],
        );
    }

    /**
     * @return array{
     *     key: string,
     *     version: string,
     *     label: string,
     *     description: string,
     *     permissions: list<string>,
     *     capabilities: list<string>,
     *     excluded_permissions: list<string>,
     *     entitlement_dependencies: list<string>,
     *     segregation_class: string,
     *     fingerprint: string
     * }
     */
    private function template(string $key): array
    {
        foreach ($this->templates->all() as $template) {
            if ($template['key'] === $key) {
                return $template;
            }
        }

        throw new \DomainException('Template municipal desconhecido.');
    }

    /**
     * @param  array<string, bool|int|string|list<string>|null>  $context
     */
    private function add(
        string $code,
        bool $passed,
        string $message,
        array $context = [],
    ): void {
        ksort($context, SORT_STRING);
        $this->checks[] = [
            'code' => $code,
            'status' => $passed ? 'pass' : 'fail',
            'message' => $message,
            'context' => $context,
        ];
    }

    /**
     * @param  list<string>  $haystack
     * @param  list<string>  $needles
     */
    private function containsAll(array $haystack, array $needles): bool
    {
        return array_diff($needles, $haystack) === [];
    }

    /** @return list<string> */
    private function csv(mixed $value): array
    {
        $value = $this->string($value);

        if ($value === null) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode(',', $value),
        )));
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_string($item)
                && trim($item) !== '',
        ));
    }

    private function string(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== ''
            ? trim($value)
            : null;
    }

    private function accessTablesAvailable(): bool
    {
        return Schema::hasTable('roles')
            && Schema::hasTable('role_user')
            && Schema::hasColumn('roles', 'template_key')
            && Schema::hasColumn('roles', 'is_active');
    }
}
