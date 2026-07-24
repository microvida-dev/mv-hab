<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InventoryBackofficeRoutesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_json_inventory_covers_the_route_collection_and_required_metadata(): void
    {
        $output = $this->outputPath('inventory.json');

        $this->artisan('access:inventory-backoffice-routes', [
            '--format' => 'json',
            '--output' => $output,
        ])->assertSuccessful();

        $payload = $this->jsonFile($output);

        $this->assertSame(1, $payload['schema_version']);
        $this->assertSame(
            Route::getRoutes()->count(),
            $payload['summary']['route_collection_total'],
        );
        $this->assertGreaterThan(0, $payload['summary']['inventoried_routes']);
        $this->assertGreaterThan(0, $payload['summary']['missing_active_backoffice_routes']);
        $this->assertGreaterThan(0, $payload['summary']['missing_mfa_backoffice_routes']);
        $this->assertGreaterThan(0, $payload['summary']['missing_log_backoffice_routes']);
        $this->assertGreaterThan(0, $payload['summary']['routes_without_detected_tests']);
        $this->assertSame(0, collect($payload['routes'])
            ->filter(fn (array $route): bool => trim((string) $route['bounded_context']) === '')
            ->count());

        $requiredKeys = [
            'route_name',
            'uri',
            'http_methods',
            'controller_class',
            'controller_method',
            'middleware_resolved',
            'active_backoffice_present',
            'mfa_backoffice_present',
            'log_backoffice_present',
            'role_middleware_active',
            'role_middleware_excluded',
            'permission_middleware',
            'permission_catalog_exists',
            'semantic_permission_available',
            'permission_recommendation',
            'permission_semantically_adequate',
            'policy_class',
            'policy_ability',
            'form_request',
            'form_request_authorize',
            'feature_entitlement',
            'feature_required',
            'feature_key',
            'municipality_source',
            'municipal_record_scope',
            'fail_closed_without_municipality',
            'platform_route',
            'municipal_route',
            'mixed_context_route',
            'record_model',
            'operation_type',
            'mfa_sensitive',
            'audit_requirement',
            'audit_implementation',
            'private_data',
            'bounded_context',
            'risk',
            'confidence',
            'test_coverage',
            'test_sources',
            'source',
            'migration_recommendation',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $payload['routes'][0]);
        }
    }

    public function test_inventory_detects_fixed_roles_permissions_policy_request_feature_and_scope(): void
    {
        $output = $this->outputPath('detections.json');

        $this->artisan('access:inventory-backoffice-routes', [
            '--format' => 'json',
            '--output' => $output,
        ])->assertSuccessful();

        $routes = collect($this->jsonFile($output)['routes'])->keyBy('route_name');
        $accessAudit = $routes->get('backoffice.access-audit.index');
        $migrated = $routes->get('backoffice.application-reviews.complete');

        $this->assertNotNull($accessAudit);
        $this->assertNull($accessAudit['role_middleware_active']);
        $this->assertContains(
            'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor',
            $accessAudit['role_middleware_excluded'],
        );
        $this->assertContains('access_audit.view', $accessAudit['permission_middleware']);
        $this->assertTrue($accessAudit['active_backoffice_present']);
        $this->assertTrue($accessAudit['mfa_backoffice_present']);
        $this->assertTrue($accessAudit['log_backoffice_present']);
        $this->assertSame('access_audit.view', $accessAudit['permission_recommendation']);
        $this->assertTrue($accessAudit['permission_catalog_exists']);
        $this->assertTrue($accessAudit['permission_semantically_adequate']);

        $this->assertNotNull($migrated);
        $this->assertContains(
            'administrative_processes.update',
            $migrated['permission_middleware'],
        );
        $this->assertSame(
            'App\\Policies\\ApplicationReviewPolicy',
            $migrated['policy_class'],
        );
        $this->assertSame(
            'App\\Http\\Requests\\CompleteApplicationReviewRequest',
            $migrated['form_request'],
        );
        $this->assertSame('always_true', $migrated['form_request_authorize']);
        $this->assertContains('applications.review', $migrated['feature_entitlement']);
        $this->assertSame('confirmed', $migrated['municipal_record_scope']);
        $this->assertContains(
            $migrated['confidence'],
            ['confirmed', 'high', 'medium', 'low', 'unknown'],
        );
        $this->assertSame('route_name_reference', $migrated['test_coverage']);
        $this->assertNotEmpty($migrated['test_sources']);
    }

    public function test_json_filters_return_only_the_requested_findings(): void
    {
        $output = $this->outputPath('filtered.json');

        $this->artisan('access:inventory-backoffice-routes', [
            '--format' => 'json',
            '--output' => $output,
            '--only-fixed-role' => true,
            '--bounded-context' => 'maintenance',
            '--risk' => 'critical',
            '--missing-permission' => true,
        ])->assertSuccessful();

        $routes = collect($this->jsonFile($output)['routes']);

        $this->assertNotEmpty($routes);
        $this->assertTrue($routes->every(
            fn (array $route): bool => is_string($route['role_middleware_active'])
                && $route['bounded_context'] === 'maintenance'
                && $route['risk'] === 'critical'
                && $route['permission_semantically_adequate'] === false
        ));
    }

    public function test_gap_filters_are_applied_to_policy_scope_and_audit_findings(): void
    {
        $filters = [
            'missing-policy' => [
                'predicate' => fn (array $route): bool => is_string($route['record_model'])
                    && $route['policy_class'] === null,
                'may_be_empty' => true,
            ],
            'missing-scope' => [
                'predicate' => fn (array $route): bool => $route['municipal_record_scope'] === 'missing',
                'may_be_empty' => false,
            ],
            'mutation-without-audit' => [
                'predicate' => fn (array $route): bool => $route['operation_type'] === 'mutation'
                    && $route['audit_requirement'] === 'required'
                    && $route['audit_implementation'] === 'missing',
                'may_be_empty' => false,
            ],
        ];

        foreach ($filters as $option => $expectation) {
            $output = $this->outputPath($option.'.json');

            $this->artisan('access:inventory-backoffice-routes', [
                '--format' => 'json',
                '--output' => $output,
                '--only-fixed-role' => true,
                '--'.$option => true,
            ])->assertSuccessful();

            $routes = collect($this->jsonFile($output)['routes']);

            if (! $expectation['may_be_empty']) {
                $this->assertNotEmpty($routes, "O filtro {$option} deve produzir achados.");
            }

            $this->assertTrue(
                $routes->every($expectation['predicate']),
                "O filtro {$option} devolveu uma rota fora do critério.",
            );
        }
    }

    public function test_csv_and_markdown_outputs_are_valid(): void
    {
        $csv = $this->outputPath('inventory.csv');
        $markdown = $this->outputPath('inventory.md');

        $this->artisan('access:inventory-backoffice-routes', [
            '--format' => 'csv',
            '--output' => $csv,
            '--only-fixed-role' => true,
        ])->assertSuccessful();

        $this->artisan('access:inventory-backoffice-routes', [
            '--format' => 'markdown',
            '--output' => $markdown,
            '--only-fixed-role' => true,
        ])->assertSuccessful();

        $csvHandle = fopen($csv, 'r');
        $this->assertNotFalse($csvHandle);
        $headers = fgetcsv($csvHandle);
        fclose($csvHandle);

        $this->assertIsArray($headers);
        $this->assertContains('route_name', $headers);
        $this->assertContains('bounded_context', $headers);
        $this->assertContains('migration_recommendation', $headers);
        $this->assertStringContainsString(
            '# Inventário permission-first de rotas backoffice',
            File::get($markdown),
        );
    }

    public function test_inventory_is_deterministic_and_does_not_change_database_state(): void
    {
        $first = $this->outputPath('first.json');
        $second = $this->outputPath('second.json');
        $before = [
            'permissions' => Permission::query()->count(),
            'users' => User::query()->count(),
            'audit_logs' => AuditLog::query()->count(),
        ];

        $this->artisan('access:inventory-backoffice-routes', [
            '--format' => 'json',
            '--output' => $first,
            '--only-fixed-role' => true,
        ])->assertSuccessful();

        $this->artisan('access:inventory-backoffice-routes', [
            '--format' => 'json',
            '--output' => $second,
            '--only-fixed-role' => true,
        ])->assertSuccessful();

        $this->assertSame(File::get($first), File::get($second));
        $this->assertSame($before, [
            'permissions' => Permission::query()->count(),
            'users' => User::query()->count(),
            'audit_logs' => AuditLog::query()->count(),
        ]);
    }

    private function outputPath(string $name): string
    {
        $path = storage_path('framework/testing/backoffice-route-inventory/'.$name);
        File::delete($path);

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonFile(string $path): array
    {
        $this->assertFileExists($path);

        return json_decode(
            File::get($path),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
