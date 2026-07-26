<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route as RouteFacade;
use InvalidArgumentException;
use JsonException;
use LogicException;

/**
 * @phpstan-type AccessRouteRow array{
 *     name: string|null,
 *     uri: string,
 *     methods: list<string>,
 *     action: string,
 *     middleware: list<string>,
 *     declared_middleware: list<string>,
 *     excluded_middleware: list<string>,
 *     role_middleware: string|null,
 *     roles: list<string>,
 *     permission_middleware: list<string>,
 *     uses_fixed_role_middleware: bool,
 *     is_backoffice_role_route: bool,
 *     has_auth: bool,
 *     has_active_backoffice: bool,
 *     has_mfa_backoffice: bool,
 *     has_log_backoffice: bool,
 *     missing_backoffice_guards: list<string>
 * }
 * @phpstan-type AccessRouteSummary array{
 *     total_routes: int,
 *     fixed_role_routes: int,
 *     backoffice_fixed_role_routes: int,
 *     candidate_fixed_role_routes: int,
 *     permission_middleware_routes: int,
 *     backoffice_fixed_role_without_active_backoffice: int,
 *     backoffice_fixed_role_without_mfa_backoffice: int,
 *     backoffice_fixed_role_without_log_backoffice: int
 * }
 */
class AuditAccessRoutes extends Command
{
    protected $signature = 'access:audit-routes
        {--format=table : Output format: table, json or csv}
        {--output= : Optional output file path}
        {--only-fixed-role : Include only routes protected by role:* middleware}';

    protected $description = 'Inventory route authorization middleware without changing application behavior.';

    /**
     * @throws JsonException
     */
    public function handle(): int
    {
        $format = strtolower((string) $this->option('format'));

        if (! in_array($format, ['table', 'json', 'csv'], true)) {
            throw new InvalidArgumentException('The --format option must be table, json or csv.');
        }

        $rows = $this->routeRows();

        if ((bool) $this->option('only-fixed-role')) {
            $rows = $rows
                ->filter(fn (array $row): bool => $row['uses_fixed_role_middleware'])
                ->values();
        }

        $summary = $this->summary($rows);

        $content = match ($format) {
            'json' => $this->asJson($rows, $summary),
            'csv' => $this->asCsv($rows),
            default => null,
        };

        if ($output = $this->normalizedOutputPath()) {
            $outputContent = $format === 'table'
                ? $this->asJson($rows, $summary)
                : $content;

            if (! is_string($outputContent)) {
                throw new LogicException('O conteúdo da auditoria de acessos não foi serializado.');
            }

            File::ensureDirectoryExists(dirname($output));
            File::put($output, $outputContent);

            $this->info("Access route audit written to: {$output}");

            return self::SUCCESS;
        }

        if ($format === 'json' || $format === 'csv') {
            if (! is_string($content)) {
                throw new LogicException('O conteúdo da auditoria de acessos não foi serializado.');
            }

            $this->line($content);

            return self::SUCCESS;
        }

        $this->renderSummary($summary);
        $this->renderTable($rows);

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, AccessRouteRow>
     */
    private function routeRows(): Collection
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->map(fn (Route $route): array => $this->routeRow($route))
            ->sortBy([
                ['uses_fixed_role_middleware', 'desc'],
                ['name', 'asc'],
                ['uri', 'asc'],
            ])
            ->values();
    }

    /**
     * @return AccessRouteRow
     */
    private function routeRow(Route $route): array
    {
        $declaredMiddleware = $this->stringList($route->gatherMiddleware());
        $excludedMiddleware = $this->stringList($route->excludedMiddleware());

        $middleware = $this->stringList(
            app('router')->resolveMiddleware(
                $declaredMiddleware,
                $excludedMiddleware,
            )
        );

        $roleMiddleware = null;

        foreach ($middleware as $item) {
            if (str_starts_with($item, 'role:')) {
                $roleMiddleware = $item;

                break;
            }
        }

        $permissionMiddleware = array_values(array_filter(
            $middleware,
            fn (string $item): bool => str_starts_with($item, 'permission:'),
        ));

        $roles = is_string($roleMiddleware)
            ? array_values(array_filter(explode(',', substr($roleMiddleware, strlen('role:')))))
            : [];

        return [
            'name' => $route->getName(),
            'uri' => $route->uri(),
            'methods' => $this->stringList(array_diff($route->methods(), ['HEAD'])),
            'action' => $route->getActionName(),
            'middleware' => $middleware,
            'declared_middleware' => $declaredMiddleware,
            'excluded_middleware' => $excludedMiddleware,
            'role_middleware' => $roleMiddleware,
            'roles' => $roles,
            'permission_middleware' => $permissionMiddleware,
            'uses_fixed_role_middleware' => is_string($roleMiddleware),
            'is_backoffice_role_route' => $this->isBackofficeRoleRoute($roles),
            'has_auth' => in_array('auth', $middleware, true),
            'has_active_backoffice' => in_array('active.backoffice', $middleware, true),
            'has_mfa_backoffice' => in_array('mfa.backoffice', $middleware, true),
            'has_log_backoffice' => in_array('log.backoffice', $middleware, true),
            'missing_backoffice_guards' => $this->missingBackofficeGuards(
                middleware: $middleware,
                isBackofficeRoleRoute: $this->isBackofficeRoleRoute($roles),
            ),
        ];
    }

    /**
     * @param  array<array-key, string>  $items
     * @return list<string>
     */
    private function stringList(array $items): array
    {
        return array_values($items);
    }

    /**
     * @param  list<string>  $roles
     */
    private function isBackofficeRoleRoute(array $roles): bool
    {
        return $roles !== []
            && ! in_array('candidate', $roles, true);
    }

    /**
     * @param  list<string>  $middleware
     * @return list<string>
     */
    private function missingBackofficeGuards(
        array $middleware,
        bool $isBackofficeRoleRoute,
    ): array {
        if (! $isBackofficeRoleRoute) {
            return [];
        }

        return array_values(array_filter(
            [
                'active.backoffice',
                'mfa.backoffice',
                'log.backoffice',
            ],
            fn (string $guard): bool => ! in_array($guard, $middleware, true),
        ));
    }

    /**
     * @param  Collection<int, covariant AccessRouteRow>  $rows
     * @return AccessRouteSummary
     */
    private function summary(Collection $rows): array
    {
        return [
            'total_routes' => $rows->count(),
            'fixed_role_routes' => $rows->where('uses_fixed_role_middleware', true)->count(),
            'backoffice_fixed_role_routes' => $rows->where('is_backoffice_role_route', true)->count(),
            'candidate_fixed_role_routes' => $rows
                ->filter(
                    fn (array $row): bool => $row['roles'] === ['candidate']
                )
                ->count(),
            'permission_middleware_routes' => $rows
                ->filter(fn (array $row): bool => $row['permission_middleware'] !== [])
                ->count(),
            'backoffice_fixed_role_without_active_backoffice' => $rows
                ->where('is_backoffice_role_route', true)
                ->where('has_active_backoffice', false)
                ->count(),
            'backoffice_fixed_role_without_mfa_backoffice' => $rows
                ->where('is_backoffice_role_route', true)
                ->where('has_mfa_backoffice', false)
                ->count(),
            'backoffice_fixed_role_without_log_backoffice' => $rows
                ->where('is_backoffice_role_route', true)
                ->where('has_log_backoffice', false)
                ->count(),
        ];
    }

    /**
     * @param  Collection<int, covariant AccessRouteRow>  $rows
     * @param  AccessRouteSummary  $summary
     *
     * @throws JsonException
     */
    private function asJson(Collection $rows, array $summary): string
    {
        return json_encode(
            [
                'generated_at' => now()->toIso8601String(),
                'summary' => $summary,
                'routes' => $rows->all(),
            ],
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ).PHP_EOL;
    }

    /**
     * @param  Collection<int, covariant AccessRouteRow>  $rows
     */
    private function asCsv(Collection $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new InvalidArgumentException('Unable to create temporary CSV stream.');
        }

        fputcsv($stream, [
            'name',
            'uri',
            'methods',
            'action',
            'role_middleware',
            'roles',
            'permission_middleware',
            'has_auth',
            'has_active_backoffice',
            'has_mfa_backoffice',
            'has_log_backoffice',
            'missing_backoffice_guards',
        ]);

        foreach ($rows as $row) {
            fputcsv($stream, [
                $row['name'],
                $row['uri'],
                implode('|', $row['methods']),
                $row['action'],
                $row['role_middleware'],
                implode('|', $row['roles']),
                implode('|', $row['permission_middleware']),
                $row['is_backoffice_role_route'] ? '1' : '0',
                $row['has_auth'] ? '1' : '0',
                $row['has_active_backoffice'] ? '1' : '0',
                $row['has_mfa_backoffice'] ? '1' : '0',
                $row['has_log_backoffice'] ? '1' : '0',
                implode('|', $row['missing_backoffice_guards']),
            ]);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            throw new InvalidArgumentException('Unable to read generated CSV.');
        }

        return $csv;
    }

    /**
     * @param  AccessRouteSummary  $summary
     */
    private function renderSummary(array $summary): void
    {
        $this->components->twoColumnDetail('Total routes', (string) $summary['total_routes']);
        $this->components->twoColumnDetail('Fixed role routes', (string) $summary['fixed_role_routes']);
        $this->components->twoColumnDetail(
            'Backoffice fixed role routes',
            (string) $summary['backoffice_fixed_role_routes'],
        );
        $this->components->twoColumnDetail(
            'Candidate fixed role routes',
            (string) $summary['candidate_fixed_role_routes'],
        );
        $this->components->twoColumnDetail(
            'Permission middleware routes',
            (string) $summary['permission_middleware_routes'],
        );
        $this->components->twoColumnDetail(
            'Backoffice fixed role routes missing active.backoffice',
            (string) $summary['backoffice_fixed_role_without_active_backoffice'],
        );
        $this->components->twoColumnDetail(
            'Backoffice fixed role routes missing mfa.backoffice',
            (string) $summary['backoffice_fixed_role_without_mfa_backoffice'],
        );
        $this->components->twoColumnDetail(
            'Backoffice fixed role routes missing log.backoffice',
            (string) $summary['backoffice_fixed_role_without_log_backoffice'],
        );
    }

    /**
     * @param  Collection<int, covariant AccessRouteRow>  $rows
     */
    private function renderTable(Collection $rows): void
    {
        $fixedRoleRows = $rows
            ->where('uses_fixed_role_middleware', true)
            ->map(fn (array $row): array => [
                $row['name'] ?? '—',
                implode(',', $row['methods']),
                $row['uri'],
                implode(',', $row['roles']),
                implode(',', $row['missing_backoffice_guards']) ?: '—',
            ])
            ->values()
            ->all();

        $this->newLine();
        $this->table(
            ['Route', 'Methods', 'URI', 'Fixed roles', 'Missing guards'],
            $fixedRoleRows,
        );
    }

    private function normalizedOutputPath(): ?string
    {
        $output = $this->option('output');

        if (! is_string($output) || trim($output) === '') {
            return null;
        }

        return str_starts_with($output, DIRECTORY_SEPARATOR)
            ? $output
            : base_path($output);
    }
}
