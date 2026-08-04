<?php

namespace Tests\Feature\Municipalities;

use App\Enums\MunicipalAdministratorInvitationStatus;
use App\Enums\MunicipalityOnboardingStatus;
use App\Jobs\SendMunicipalAdministratorInvitation;
use App\Models\AuditEvent;
use App\Models\MfaDevice;
use App\Models\Municipality;
use App\Models\MunicipalityOnboardingRun;
use App\Models\PlatformOperatorAssignment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MunicipalityOnboardingCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_preview_is_read_only(): void
    {
        $operator = $this->globalOperator();

        $exit = Artisan::call('mvhab:municipality:onboard', $this->arguments($operator));

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('MUNICIPALITY_ONBOARDING=PREVIEW', Artisan::output());
        $this->assertDatabaseCount('municipalities', 0);
        $this->assertDatabaseCount('municipality_onboarding_runs', 0);
        $this->assertDatabaseCount('municipal_administrator_invitations', 0);
    }

    public function test_dry_run_overrides_confirm_and_remains_read_only(): void
    {
        $operator = $this->globalOperator();

        $exit = Artisan::call('mvhab:municipality:onboard', [
            ...$this->arguments($operator),
            '--confirm' => true,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('MUNICIPALITY_ONBOARDING=PREVIEW', Artisan::output());
        $this->assertDatabaseCount('municipalities', 0);
        $this->assertDatabaseCount('municipality_onboarding_runs', 0);
        $this->assertDatabaseCount('municipal_administrator_invitations', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_domain_writes_are_rolled_back_when_role_provisioning_fails(): void
    {
        Queue::fake();
        $operator = $this->globalOperator();

        $probeMunicipality = Municipality::factory()->create();
        $probeMunicipalityId = (int) $probeMunicipality->getKey();
        $probeMunicipality->delete();

        foreach ([$probeMunicipalityId, $probeMunicipalityId + 1] as $municipalityId) {
            Role::query()->create([
                'name' => "municipal_{$municipalityId}_municipal_administrator",
                'label' => 'Identificador reservado ocupado',
                'scope' => 'municipal',
                'is_system' => false,
                'is_active' => true,
            ]);
        }

        $exit = Artisan::call('mvhab:municipality:onboard', [
            ...$this->arguments($operator),
            '--confirm' => true,
        ]);

        $this->assertSame(20, $exit);
        $this->assertDatabaseCount('municipalities', 0);
        $this->assertDatabaseCount('municipal_administrator_invitations', 0);
        $this->assertDatabaseCount('role_user', 1);
        $this->assertDatabaseHas('municipality_onboarding_runs', [
            'municipality_code' => 'ALCANENA',
            'status' => MunicipalityOnboardingStatus::Failed->value,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_command_output_and_audit_metadata_do_not_expose_personal_identity_data(): void
    {
        Queue::fake();
        $operator = $this->globalOperator();
        $arguments = [
            ...$this->arguments($operator),
            '--confirm' => true,
        ];

        $this->assertSame(0, Artisan::call('mvhab:municipality:onboard', $arguments));

        $serialized = Artisan::output().' '.AuditEvent::query()
            ->get(['description', 'old_values', 'new_values', 'metadata'])
            ->toJson();

        foreach ([
            '506000001',
            'habitacao@alcanena.pt',
            'admin@alcanena.pt',
            'Administrador Municipal',
        ] as $sensitiveValue) {
            $this->assertStringNotContainsString($sensitiveValue, $serialized);
        }
    }

    public function test_confirmed_onboarding_creates_exact_domain_and_queues_invitation(): void
    {
        Queue::fake();
        $operator = $this->globalOperator();

        $exit = Artisan::call('mvhab:municipality:onboard', [
            ...$this->arguments($operator),
            '--confirm' => true,
        ]);

        $this->assertSame(0, $exit);
        $municipality = Municipality::query()->sole();
        $administrator = User::query()
            ->where('municipality_id', $municipality->id)
            ->sole();
        $role = Role::query()
            ->where('municipality_id', $municipality->id)
            ->where('template_key', 'municipal-administrator')
            ->sole();
        $run = MunicipalityOnboardingRun::query()->sole();
        $invitation = $run->invitation()->sole();

        $this->assertSame('ALCANENA', $municipality->code);
        $this->assertTrue($administrator->mfa_required);
        $this->assertNotNull($administrator->email_verified_at);
        $this->assertTrue($administrator->hasRole($role->name));
        $this->assertFalse($role->permissions()->where('name', 'like', '%*%')->exists());
        $this->assertSame(MunicipalAdministratorInvitationStatus::Queued, $invitation->status);
        $this->assertDatabaseCount('municipality_feature_entitlements', 0);
        $this->assertDatabaseCount('platform_operator_assignments', 1);
        $this->assertNull($operator->fresh()?->municipality_id);
        Queue::assertPushed(
            SendMunicipalAdministratorInvitation::class,
            fn (SendMunicipalAdministratorInvitation $job): bool => $job->invitationId === $invitation->id,
        );
    }

    public function test_second_identical_execution_is_idempotent(): void
    {
        Queue::fake();
        $operator = $this->globalOperator();
        $arguments = [
            ...$this->arguments($operator),
            '--confirm' => true,
        ];

        $this->assertSame(0, Artisan::call('mvhab:municipality:onboard', $arguments));
        $this->assertSame(0, Artisan::call('mvhab:municipality:onboard', $arguments));

        $this->assertDatabaseCount('municipalities', 1);
        $this->assertDatabaseCount('municipality_onboarding_runs', 1);
        $this->assertDatabaseCount('municipal_administrator_invitations', 1);
        $this->assertDatabaseCount('roles', 12);
        $this->assertStringContainsString('IDEMPOTENT_REPLAY=true', Artisan::output());
    }

    public function test_actor_without_global_assignment_is_rejected(): void
    {
        $actor = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
            'mfa_required' => true,
        ]);
        $actor->assignRole('administrator');
        MfaDevice::factory()->confirmed()->for($actor)->create();

        $exit = Artisan::call('mvhab:municipality:onboard', [
            ...$this->arguments($actor),
            '--confirm' => true,
        ]);

        $this->assertSame(11, $exit);
        $this->assertDatabaseCount('municipalities', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function arguments(User $actor): array
    {
        return [
            '--actor-id' => $actor->id,
            '--name' => 'Município de Alcanena',
            '--code' => 'ALCANENA',
            '--tax-number' => '506000001',
            '--contact-email' => 'habitacao@alcanena.pt',
            '--admin-name' => 'Administrador Municipal',
            '--admin-email' => 'admin@alcanena.pt',
            '--justification' => 'Aprovação institucional para o primeiro onboarding municipal.',
        ];
    }

    private function globalOperator(): User
    {
        $operator = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
            'mfa_required' => true,
        ]);
        $operator->assignRole('administrator');
        PlatformOperatorAssignment::factory()->create(['user_id' => $operator->id]);
        MfaDevice::factory()->confirmed()->for($operator)->create();

        return $operator;
    }
}
