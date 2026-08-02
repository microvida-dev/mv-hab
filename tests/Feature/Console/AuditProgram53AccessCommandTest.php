<?php

namespace Tests\Feature\Console;

use App\Models\Municipality;
use App\Models\Role;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use JsonException;
use Tests\TestCase;

class AuditProgram53AccessCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    /** @throws JsonException */
    public function test_json_audit_is_deterministic_and_read_only(): void
    {
        $before = $this->databaseState();

        $firstExit = Artisan::call('access:audit-program-53', [
            '--format' => 'json',
            '--fail-on-drift' => true,
        ]);
        $first = Artisan::output();
        $secondExit = Artisan::call('access:audit-program-53', [
            '--format' => 'json',
            '--fail-on-drift' => true,
        ]);
        $second = Artisan::output();

        $this->assertSame(0, $firstExit);
        $this->assertSame(0, $secondExit);
        $this->assertSame($first, $second);
        $this->assertSame($before, $this->databaseState());

        $payload = json_decode(
            $first,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertIsArray($payload);
        $this->assertSame('53', data_get($payload, 'program'));
        $this->assertSame(
            'analista-candidaturas-exportacao',
            data_get($payload, 'template_key'),
        );
        $this->assertFalse((bool) data_get(
            $payload,
            'summary.drift',
        ));
        $this->assertSame(0, data_get($payload, 'summary.failed'));
    }

    public function test_fail_on_drift_rejects_a_divergent_template_instance(): void
    {
        $municipality = Municipality::factory()->create();
        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('analista-candidaturas-exportacao');
        Role::query()->create([
            'municipality_id' => $municipality->id,
            'template_key' => $template['key'],
            'template_version' => $template['version'],
            'template_fingerprint' => $template['fingerprint'],
            'name' => 'program53_drift_test',
            'label' => $template['label'],
            'description' => $template['description'],
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->artisan('access:audit-program-53', [
            '--format' => 'table',
            '--fail-on-drift' => true,
        ])
            ->expectsOutputToContain('Divergências')
            ->assertFailed();

        $this->assertDatabaseCount('access_change_events', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_markdown_output_can_be_written_without_database_side_effects(): void
    {
        $path = storage_path(
            'framework/testing/program53-access-audit.md',
        );
        @unlink($path);

        try {
            $this->artisan('access:audit-program-53', [
                '--format' => 'markdown',
                '--output' => $path,
                '--fail-on-drift' => true,
            ])->assertSuccessful();

            $this->assertFileExists($path);
            $content = (string) file_get_contents($path);
            $this->assertStringContainsString(
                '# Auditoria de acesso do Programa 53',
                $content,
            );
            $this->assertStringContainsString(
                '`template.new.permissions`',
                $content,
            );
            $this->assertDatabaseCount('access_change_events', 0);
            $this->assertDatabaseCount('audit_logs', 0);
            $this->assertDatabaseCount('audit_events', 0);
        } finally {
            @unlink($path);
        }
    }

    /** @return array<string, int> */
    private function databaseState(): array
    {
        return [
            'roles' => Role::query()->count(),
            'role_assignments' => (int) DB::table('role_user')->count(),
            'permission_assignments' => (int) DB::table('permission_role')->count(),
            'access_events' => (int) DB::table('access_change_events')->count(),
            'audit_logs' => (int) DB::table('audit_logs')->count(),
            'audit_events' => (int) DB::table('audit_events')->count(),
        ];
    }
}
