<?php

namespace Tests\Feature\Access;

use App\Enums\FeatureKey;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\PlatformOperatorAssignment;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Access\RoleAssignmentService;
use App\Services\Access\RoleManagementService;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Program53AnalystAccessMatrixTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private Municipality $municipality;

    private Role $role;

    private User $analyst;

    private AdministrativeProcess $process;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            SystemAccessSeeder::class,
            ReportDefinitionSeeder::class,
        ]);
        $this->municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
            FeatureKey::ApplicationExport,
        );
        $administrator = $this->userWithRole('administrator');
        $this->role = app(RoleManagementService::class)->applyTemplate(
            $administrator,
            'analista-candidaturas-exportacao',
            'Aplicar o perfil municipal do Programa 53 para a matriz de acesso.',
        );
        $this->analyst = User::factory()->create([
            'municipality_id' => $this->municipality->getKey(),
            'status' => 'active',
            'mfa_required' => false,
        ]);
        app(RoleAssignmentService::class)->assign(
            $administrator,
            $this->analyst,
            $this->role,
            'Atribuir o perfil municipal para validar a matriz do Programa 53.',
        );
        $program = Program::factory()->create([
            'municipality_id' => $this->municipality->getKey(),
        ]);
        $contest = Contest::factory()->create([
            'program_id' => $program->getKey(),
        ]);
        $application = Application::factory()->submitted()->create([
            'program_id' => $program->getKey(),
            'contest_id' => $contest->getKey(),
        ]);
        $this->process = AdministrativeProcess::factory()->create([
            'application_id' => $application->getKey(),
            'program_id' => $program->getKey(),
            'contest_id' => $contest->getKey(),
            'user_id' => $application->user_id,
        ]);
    }

    public function test_profile_has_exact_template_without_sensitive_or_external_capabilities(): void
    {
        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('analista-candidaturas-exportacao');
        $permissions = $this->role->permissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
        $expected = $template['permissions'];
        sort($expected, SORT_STRING);

        $this->assertSame($expected, $permissions);
        $this->assertSame($template['version'], $this->role->template_version);
        $this->assertSame(
            $template['fingerprint'],
            $this->role->template_fingerprint,
        );
        $this->assertSame('municipal', $this->role->scope);
        $this->assertFalse($this->role->is_system);
        $this->assertFalse($this->analyst->hasPermission('*'));

        foreach ([
            'reports.export_sensitive',
            'roles.view',
            'users.view',
            'platform_operators.view',
            'finance.view',
            'contracts.view',
            'privacy.view',
            'rgpd.retention.view',
        ] as $permission) {
            $this->assertFalse(
                $this->analyst->hasPermission($permission),
                "O perfil não pode receber {$permission}.",
            );
        }

        $this->assertDatabaseCount('platform_operator_assignments', 0);
        $this->assertSame(3, MunicipalityFeatureEntitlement::query()
            ->where('municipality_id', $this->municipality->getKey())
            ->where('enabled', true)
            ->count());
    }

    public function test_profile_can_open_authorized_program_53_surfaces(): void
    {
        $this->actingAs($this->analyst)
            ->withSession(['mfa.verified_at' => now()]);

        foreach ([
            'dashboard',
            'backoffice.applications.index',
            'admin.document-reviews.index',
            'backoffice.application-review-batches.index',
            'backoffice.correction-revalidations.index',
            'backoffice.reports.temporal-exports.index',
            'backoffice.reports.access-logs.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }

        $this->get(route(
            'backoffice.correction-requests.index',
            $this->process,
        ))->assertOk();
    }

    public function test_profile_is_denied_from_access_administration_and_external_domains(): void
    {
        $this->actingAs($this->analyst)
            ->withSession(['mfa.verified_at' => now()]);

        foreach ([
            'backoffice.roles.index',
            'backoffice.users.index',
            'backoffice.platform.operators.index',
            'backoffice.finance.installments.index',
            'backoffice.contracts.leases.index',
            'backoffice.security.privacy.retention.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }

        $this->assertDatabaseCount('platform_operator_assignments', 0);
    }

    public function test_mfa_entitlement_account_role_and_municipal_scope_fail_closed(): void
    {
        $this->actingAs($this->analyst)
            ->get(route('backoffice.application-review-batches.index'))
            ->assertRedirect(route('backoffice.security.mfa.index'));

        $this->actingAs($this->analyst)
            ->withSession(['mfa.verified_at' => now()]);
        MunicipalityFeatureEntitlement::query()
            ->where('municipality_id', $this->municipality->getKey())
            ->where('feature_key', FeatureKey::ApplicationExport->value)
            ->delete();
        $this->get(route('backoffice.reports.temporal-exports.index'))
            ->assertForbidden();

        $foreignMunicipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $foreignProgram = Program::factory()->create([
            'municipality_id' => $foreignMunicipality->getKey(),
        ]);
        $foreignContest = Contest::factory()->create([
            'program_id' => $foreignProgram->getKey(),
        ]);
        $this->get(route(
            'backoffice.application-review-batches.contest',
            $foreignContest,
        ))->assertForbidden();

        $this->analyst->forceFill(['status' => 'inactive'])->save();
        $this->get(route('backoffice.application-review-batches.index'))
            ->assertForbidden();

        $this->analyst->forceFill(['status' => 'active'])->save();
        $this->role->forceFill(['is_active' => false])->save();
        $this->get(route('backoffice.application-review-batches.index'))
            ->assertForbidden();

        $this->assertDatabaseCount('report_exports', 0);
        $this->assertDatabaseCount('application_review_batches', 0);
        $this->assertSame(0, PlatformOperatorAssignment::query()->count());
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
