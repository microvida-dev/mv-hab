<?php

namespace Tests\Feature\Security;

use App\Enums\DocumentStatus;
use App\Enums\ExportScope;
use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\EligibilityCheck;
use App\Models\ReportAccessLog;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class InitialMunicipalRoleProfilesTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private const FULL_BACKOFFICE_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,legal_manager,financial_manager,housing_manager,maintenance_manager,inspection_manager,support_agent,auditor';

    private const LEGACY_BACKOFFICE_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed([
            SystemAccessSeeder::class,
            ReportDefinitionSeeder::class,
        ]);
    }

    public function test_initial_templates_use_exact_permissions_consumed_by_real_routes(): void
    {
        $registry = app(MunicipalRoleTemplateRegistry::class);
        $operator = $registry->resolve('operador-recolha');
        $analyst = $registry->resolve('analista-candidaturas');
        $exporter = $registry->resolve('exportador-candidaturas');

        $this->assertContains('administrative_processes.create', $operator['permissions']);
        $this->assertNotContains('documents.approve', $operator['permissions']);
        $this->assertNotContains('eligibility.view', $operator['permissions']);

        $this->assertContains('documents.approve', $analyst['permissions']);
        $this->assertContains('documents.reject', $analyst['permissions']);
        $this->assertContains('eligibility.view', $analyst['permissions']);
        $this->assertNotContains('applications.export', $analyst['permissions']);

        $this->assertContains('applications.export', $exporter['permissions']);
        $this->assertContains('reports.view', $exporter['permissions']);
        $this->assertContains('reports.export', $exporter['permissions']);
        $this->assertContains('reports.audit', $exporter['permissions']);
        $this->assertSame([], array_values(array_filter(
            $exporter['permissions'],
            fn (string $permission): bool => str_starts_with($permission, 'exports.'),
        )));
    }

    public function test_operator_reaches_collection_workflow_but_not_decisions_or_prohibited_domains(): void
    {
        [$operator] = $this->userFromTemplate('operador-recolha');
        $application = Application::factory()->submitted()->create([
            'application_number' => 'CAND-45C-OPERADOR',
        ]);
        $submission = DocumentSubmission::factory()->create([
            'status' => DocumentStatus::UnderReview->value,
        ]);
        $application->program()->update(['municipality_id' => $operator->municipality_id]);
        $submission->user()->update(['municipality_id' => $operator->municipality_id]);

        $this->asVerified($operator)
            ->get(route('dashboard'))
            ->assertOk();

        $this->asVerified($operator)
            ->get(route('workspaces.show', 'atendimento'))
            ->assertOk()
            ->assertSee('Candidaturas')
            ->assertSee('Revisão documental')
            ->assertDontSee('Rendas e contas');

        $this->asVerified($operator)
            ->get(route('backoffice.search.index', ['q' => 'CAND-45C-OPERADOR']))
            ->assertOk()
            ->assertSee('CAND-45C-OPERADOR')
            ->assertSee(route('backoffice.cases.applications.show', $application), false);

        $this->asVerified($operator)
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk();

        $this->asVerified($operator)
            ->get(route('backoffice.application-intake.index'))
            ->assertOk();

        $this->asVerified($operator)
            ->get(route('admin.document-reviews.index'))
            ->assertOk();

        $this->asVerified($operator)
            ->post(route('admin.document-reviews.validate', $submission), [
                'internal_notes' => 'Tentativa sem poderes de decisão.',
            ])
            ->assertForbidden();

        $this->asVerified($operator)
            ->get(route('backoffice.eligibility.checks.index'))
            ->assertForbidden();

        $this->assertProhibitedDomains($operator);
    }

    public function test_analyst_can_decide_documents_and_consult_eligibility_without_export_access(): void
    {
        [$analyst] = $this->userFromTemplate('analista-candidaturas');
        $validatedSubmission = DocumentSubmission::factory()->create([
            'status' => DocumentStatus::UnderReview->value,
        ]);
        $rejectedSubmission = DocumentSubmission::factory()->create([
            'status' => DocumentStatus::Submitted->value,
        ]);
        $eligibilityCheck = EligibilityCheck::factory()->create();
        $validatedSubmission->user()->update(['municipality_id' => $analyst->municipality_id]);
        $rejectedSubmission->user()->update(['municipality_id' => $analyst->municipality_id]);
        $eligibilityCheck->program()->update(['municipality_id' => $analyst->municipality_id]);

        $this->asVerified($analyst)
            ->get(route('workspaces.show', 'concursos'))
            ->assertOk()
            ->assertSee('Elegibilidade')
            ->assertDontSee('Rendas e contas');

        $this->asVerified($analyst)
            ->post(route('admin.document-reviews.validate', $validatedSubmission), [
                'internal_notes' => 'Documento validado no teste do perfil.',
            ])
            ->assertRedirect(route('admin.document-reviews.show', $validatedSubmission));

        $this->assertDatabaseHas('document_submissions', [
            'id' => $validatedSubmission->id,
            'status' => DocumentStatus::Validated->value,
        ]);

        $this->asVerified($analyst)
            ->post(route('admin.document-reviews.reject', $rejectedSubmission), [
                'rejection_reason' => 'Documento ilegível e incompleto.',
                'internal_notes' => 'Solicitar nova submissão.',
            ])
            ->assertRedirect(route('admin.document-reviews.show', $rejectedSubmission));

        $this->assertDatabaseHas('document_submissions', [
            'id' => $rejectedSubmission->id,
            'status' => DocumentStatus::Rejected->value,
        ]);

        $this->asVerified($analyst)
            ->get(route('backoffice.eligibility.checks.index'))
            ->assertOk();

        $this->asVerified($analyst)
            ->get(route('backoffice.eligibility.checks.show', $eligibilityCheck))
            ->assertOk();

        $this->asVerified($analyst)
            ->get(route('backoffice.reports.exports.index'))
            ->assertForbidden();

        $this->assertProhibitedDomains($analyst);
    }

    public function test_exporter_can_export_only_application_reports_and_consult_export_audit(): void
    {
        [$exporter] = $this->userFromTemplate('exportador-candidaturas');
        $applicationReport = ReportDefinition::query()
            ->where('code', 'application_status_summary')
            ->firstOrFail();
        $complaintReport = ReportDefinition::query()
            ->where('code', 'complaints_summary')
            ->firstOrFail();

        $this->asVerified($exporter)
            ->get(route('workspaces.show', 'gestao'))
            ->assertOk()
            ->assertSee('Relatórios')
            ->assertDontSee('Rendas e contas');

        $this->asVerified($exporter)
            ->get(route('backoffice.reports.index'))
            ->assertOk()
            ->assertSee('Resumo de estados das candidaturas')
            ->assertDontSee('Resumo de reclamações')
            ->assertDontSee('Ocupação do parque habitacional');

        $this->asVerified($exporter)
            ->get(route('backoffice.reports.definitions.show', $applicationReport))
            ->assertOk()
            ->assertSee('Criar exportação');

        $this->asVerified($exporter)
            ->get(route('backoffice.reports.definitions.show', $complaintReport))
            ->assertForbidden();

        $this->asVerified($exporter)
            ->post(route('backoffice.reports.exports.store', $applicationReport), [
                'format' => 'csv',
                'scope' => ExportScope::Aggregated->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $export = ReportExport::query()->firstOrFail();
        Storage::disk('local')->assertExists($export->file_path);
        $this->assertTrue(ReportAccessLog::query()
            ->where('user_id', $exporter->id)
            ->where('report_definition_id', $applicationReport->id)
            ->exists());

        $this->asVerified($exporter)
            ->get(route('backoffice.reports.exports.index'))
            ->assertOk();

        $this->asVerified($exporter)
            ->get(route('backoffice.reports.exports.show', $export))
            ->assertOk();

        $this->asVerified($exporter)
            ->get(route('backoffice.reports.exports.download', $export))
            ->assertOk();

        $this->asVerified($exporter)
            ->get(route('backoffice.reports.access-logs.index'))
            ->assertOk();

        $this->asVerified($exporter)
            ->post(route('backoffice.reports.exports.store', $complaintReport), [
                'format' => 'csv',
                'scope' => ExportScope::Aggregated->value,
            ])
            ->assertForbidden();

        $this->asVerified($exporter)
            ->get(route('admin.document-reviews.index'))
            ->assertForbidden();

        $this->asVerified($exporter)
            ->get(route('backoffice.eligibility.checks.index'))
            ->assertForbidden();

        $this->assertProhibitedDomains($exporter);
    }

    public function test_custom_profile_navigation_and_workflow_routes_have_no_fixed_role_middleware(): void
    {
        $expectedPermissions = [
            'dashboard' => 'permission:dashboard.view',
            'workspaces.show' => 'permission:dashboard.view',
            'backoffice.search.index' => 'permission:dashboard.view',
            'backoffice.cases.applications.show' => 'permission:applications.view',
            'backoffice.eligibility.checks.index' => 'permission:eligibility.view',
            'backoffice.eligibility.checks.show' => 'permission:eligibility.view',
            'backoffice.reports.index' => 'permission:reports.view',
            'backoffice.reports.definitions.show' => 'permission:reports.view',
            'backoffice.reports.exports.index' => 'permission:reports.view',
            'backoffice.reports.exports.store' => 'permission:reports.export',
            'backoffice.reports.exports.download' => 'permission:reports.export',
            'backoffice.reports.access-logs.index' => 'permission:reports.audit',
        ];

        foreach ($expectedPermissions as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertContains($permission, $middleware);
            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:'),
                ),
                "Route [{$routeName}] still uses fixed role middleware.",
            );

            if ($routeName !== 'dashboard') {
                $this->assertContains('active.backoffice', $middleware);
                $this->assertContains('mfa.backoffice', $middleware);
                $this->assertContains('log.backoffice', $middleware);
            }
        }

        $this->assertContains(
            self::FULL_BACKOFFICE_ROLE_MIDDLEWARE,
            Route::getRoutes()->getByName('backoffice.search.index')?->excludedMiddleware() ?? [],
        );
        $this->assertContains(
            self::LEGACY_BACKOFFICE_ROLE_MIDDLEWARE,
            Route::getRoutes()->getByName('backoffice.reports.index')?->excludedMiddleware() ?? [],
        );
    }

    public function test_candidate_remains_outside_backoffice_even_with_a_custom_profile(): void
    {
        [$exporter, $role] = $this->userFromTemplate('exportador-candidaturas');
        $candidateRole = Role::query()->where('name', 'candidate')->firstOrFail();
        $exporter->roles()->attach($candidateRole);

        $this->actingAs($exporter)
            ->get(route('dashboard'))
            ->assertRedirect(route('candidate.dashboard'));

        $this->asVerified($exporter)
            ->get(route('backoffice.reports.index'))
            ->assertForbidden();

        $this->assertTrue($role->isMunicipalCustom());
    }

    /**
     * @return array{User, Role}
     */
    private function userFromTemplate(string $templateKey): array
    {
        $template = app(MunicipalRoleTemplateRegistry::class)->resolve($templateKey);
        $role = Role::query()->create([
            'name' => $templateKey.'_'.Str::lower(Str::random(8)),
            'label' => $template['label'],
            'description' => $template['description'],
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
        $role->permissions()->sync($template['permission_ids']);

        $municipality = $this->municipalityWithFeatures(FeatureKey::cases());
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
            'mfa_required' => false,
        ]);
        $user->roles()->attach($role);

        $this->assertFalse($user->roles()->where('roles.id', '!=', $role->id)->exists());
        $this->assertFalse(Schema::hasTable('permission_user'));

        return [$user, $role];
    }

    private function asVerified(User $user): static
    {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()]);
    }

    private function assertProhibitedDomains(User $user): void
    {
        foreach ([
            'backoffice.contracts.leases.index',
            'backoffice.finance.installments.index',
            'backoffice.maintenance.requests.index',
            'backoffice.roles.index',
        ] as $routeName) {
            $this->asVerified($user)
                ->get(route($routeName))
                ->assertForbidden();
        }
    }
}
