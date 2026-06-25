<?php

namespace Tests\Feature\Reports;

use App\Enums\ExportScope;
use App\Models\ReportAccessLog;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Reporting\Exporters\CsvReportExporter;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MunicipalExportsSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed([
            SystemAccessSeeder::class,
            ReportDefinitionSeeder::class,
        ]);
    }

    public function test_csv_exporter_neutralizes_formula_injection(): void
    {
        $csv = app(CsvReportExporter::class)->render([
            ['Campo' => '=cmd', 'Outro' => '+SUM(A1:A2)', 'Seguro' => 'texto'],
        ]);

        $this->assertStringContainsString("'=cmd", $csv);
        $this->assertStringContainsString("'+SUM", $csv);
        $this->assertStringContainsString('texto', $csv);
    }

    public function test_report_export_is_private_authorized_and_audited_on_creation(): void
    {
        $admin = $this->userWithRole('administrator');
        $report = ReportDefinition::query()->where('code', 'application_status_summary')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('backoffice.reports.exports.store', $report), [
                'format' => 'csv',
                'scope' => ExportScope::Aggregated->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $export = ReportExport::query()->firstOrFail();
        Storage::disk('local')->assertExists($export->file_path);
        $this->assertStringStartsWith('reports/', $export->file_path);
        $this->assertStringNotContainsString('storage_path', json_encode($export->only(['file_name', 'format', 'scope']), JSON_THROW_ON_ERROR));

        $this->assertTrue(ReportAccessLog::query()->where('access_type', 'export_report')->exists());
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
