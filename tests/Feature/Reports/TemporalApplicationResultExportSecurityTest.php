<?php

namespace Tests\Feature\Reports;

use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ExportScope;
use App\Enums\FeatureKey;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Enums\ReportRunStatus;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Program;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\Role;
use App\Models\User;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class TemporalApplicationResultExportSecurityTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private Municipality $municipality;

    private Contest $contest;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Storage::fake('local');
        $this->seed([SystemAccessSeeder::class, ReportDefinitionSeeder::class]);
        $this->municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
            FeatureKey::ApplicationExport,
        );
        $program = Program::factory()->create([
            'municipality_id' => $this->municipality->getKey(),
        ]);
        $this->contest = Contest::factory()->create([
            'program_id' => $program->getKey(),
        ]);
    }

    public function test_guest_candidate_inactive_user_and_missing_mfa_are_blocked(): void
    {
        $this->get(route('backoffice.reports.temporal-exports.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.reports.temporal-exports.index'))
            ->assertForbidden();

        $administrator = $this->userWithRole('administrator');
        $this->actingAs($administrator)
            ->get(route('backoffice.reports.temporal-exports.index'))
            ->assertRedirect(route('backoffice.security.mfa.index'));

        $inactive = $this->userWithRole('administrator');
        $inactive->forceFill(['status' => 'inactive'])->save();
        $this->actingAs($inactive)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.temporal-exports.index'))
            ->assertForbidden();
    }

    public function test_permission_entitlement_and_municipal_scope_are_independent_guards(): void
    {
        $withoutPermission = $this->limitedExporter([]);
        $this->actingAs($withoutPermission)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $this->payload())
            ->assertForbidden();

        $municipalityWithoutFeature = Municipality::factory()->create();
        $withPermission = $this->limitedExporter([
            'reports.view',
            'reports.export',
            'applications.export',
        ], $municipalityWithoutFeature);
        $foreignProgram = Program::factory()->create([
            'municipality_id' => $municipalityWithoutFeature->getKey(),
        ]);
        $foreignContest = Contest::factory()->create([
            'program_id' => $foreignProgram->getKey(),
        ]);
        $this->actingAs($withPermission)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $this->payload([
                'contest_id' => $foreignContest->getKey(),
            ]))
            ->assertForbidden();

        $administrator = $this->userWithRole('administrator');
        $otherMunicipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationExport,
        );
        $otherProgram = Program::factory()->create([
            'municipality_id' => $otherMunicipality->getKey(),
        ]);
        $otherContest = Contest::factory()->create([
            'program_id' => $otherProgram->getKey(),
        ]);
        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $this->payload([
                'contest_id' => $otherContest->getKey(),
            ]))
            ->assertForbidden();

        $globalWithoutAssignment = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
        ]);
        $globalWithoutAssignment->assignRole('administrator');
        $this->actingAs($globalWithoutAssignment)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.temporal-exports.index'))
            ->assertForbidden();
    }

    public function test_sensitive_export_requires_confirmation_and_sensitive_permissions(): void
    {
        $administrator = $this->userWithRole('administrator');
        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $this->payload([
                'include_sensitive' => '1',
            ]))
            ->assertSessionHasErrors('sensitive_confirmed');

        $limited = $this->limitedExporter([
            'reports.view',
            'reports.export',
            'applications.export',
        ]);
        $this->actingAs($limited)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $this->payload([
                'include_sensitive' => '1',
                'sensitive_confirmed' => '1',
            ]))
            ->assertForbidden();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $this->payload([
                'include_document_files' => '1',
                'document_files_confirmed' => '1',
            ]))
            ->assertSessionHasErrors('include_document_files');
    }

    public function test_inactive_role_does_not_grant_export_access(): void
    {
        $administrator = $this->userWithRole('administrator');
        Role::query()
            ->where('name', 'administrator')
            ->update(['is_active' => false]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('report_exports', 0);
    }

    public function test_temporal_definition_cannot_use_the_legacy_synchronous_exporter(): void
    {
        $administrator = $this->userWithRole('administrator');
        $definition = ReportDefinition::query()
            ->where('code', TemporalApplicationResultExportService::REPORT_CODE)
            ->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.exports.store', $definition), [
                'format' => ReportFormat::Zip->value,
                'scope' => ExportScope::Pseudonymized->value,
            ])
            ->assertForbidden();
        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.runs.store', $definition), [
                'format' => ReportFormat::Zip->value,
                'scope' => ExportScope::Pseudonymized->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('report_exports', 0);
        $this->assertDatabaseCount('report_runs', 0);
    }

    public function test_auditor_can_view_scoped_metadata_but_cannot_generate_or_download(): void
    {
        $administrator = $this->userWithRole('administrator');
        $export = $this->completedExport($administrator);
        $auditor = $this->userWithRole('auditor');

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.temporal-exports.show', $export))
            ->assertOk()
            ->assertSee($export->public_id)
            ->assertDontSee((string) $export->file_path);

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $this->payload())
            ->assertForbidden();
        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.temporal-exports.download', $export))
            ->assertForbidden();
    }

    public function test_foreign_public_id_and_expired_file_are_not_downloadable(): void
    {
        $owner = $this->userWithRole('administrator');
        $export = $this->completedExport($owner);
        $otherMunicipality = $this->municipalityWithFeatures(FeatureKey::ApplicationExport);
        $foreignUser = User::factory()->create([
            'municipality_id' => $otherMunicipality->getKey(),
            'status' => 'active',
        ]);
        $foreignUser->assignRole('administrator');

        $this->actingAs($foreignUser)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.temporal-exports.show', $export))
            ->assertForbidden();

        $export->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->actingAs($owner)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.temporal-exports.download', $export))
            ->assertNotFound();
    }

    private function completedExport(User $owner): ReportExport
    {
        $definition = ReportDefinition::query()
            ->where('code', TemporalApplicationResultExportService::REPORT_CODE)
            ->firstOrFail();
        $run = ReportRun::factory()->create([
            'report_definition_id' => $definition->getKey(),
            'user_id' => $owner->getKey(),
            'status' => ReportRunStatus::Completed,
            'format' => ReportFormat::Zip,
            'scope' => ExportScope::Pseudonymized,
            'filters' => ['contest_id' => $this->contest->getKey()],
        ]);
        $path = 'reports/tests/'.Str::uuid().'/export.zip';
        Storage::disk('local')->put($path, 'zip-test');

        return ReportExport::factory()->create([
            'report_run_id' => $run->getKey(),
            'user_id' => $owner->getKey(),
            'municipality_id' => $this->municipality->getKey(),
            'contest_id' => $this->contest->getKey(),
            'export_profile' => TemporalApplicationResultExportService::PROFILE,
            'export_mode' => ApplicationResultExportMode::CurrentState,
            'status' => ReportExportStatus::Completed,
            'requested_format' => ReportFormat::Zip,
            'format' => ReportFormat::Zip,
            'scope' => ExportScope::Pseudonymized,
            'file_path' => $path,
            'file_name' => 'export.zip',
            'formats' => [ApplicationResultExportFormat::Csv->value],
            'datasets' => [ApplicationResultExportDataset::Applications->value],
        ]);
    }

    /** @param list<string> $permissions */
    private function limitedExporter(
        array $permissions,
        ?Municipality $municipality = null,
    ): User {
        $municipality ??= $this->municipality;
        $role = Role::query()->create([
            'municipality_id' => $municipality->getKey(),
            'name' => 'exportador-'.Str::lower(Str::random(8)),
            'label' => 'Exportador limitado',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissions)->pluck('id'),
        );
        $user = User::factory()->create([
            'municipality_id' => $municipality->getKey(),
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'contest_id' => $this->contest->getKey(),
            'mode' => ApplicationResultExportMode::CurrentState->value,
            'formats' => [ApplicationResultExportFormat::Csv->value],
            'datasets' => [
                ApplicationResultExportDataset::Applications->value,
                ApplicationResultExportDataset::Documents->value,
            ],
            'csv_delimiter' => 'semicolon',
            'csv_bom' => '1',
            'include_sensitive' => '0',
            'include_document_files' => '0',
            'changed_documents_only' => '0',
            'include_unchanged' => '0',
            'idempotency_token' => (string) Str::uuid(),
            ...$overrides,
        ];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create([
            'municipality_id' => $this->municipality->getKey(),
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
