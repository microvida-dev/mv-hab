<?php

namespace Tests\Feature\Municipalities;

use App\Enums\MunicipalAdministratorInvitationStatus;
use App\Models\MfaDevice;
use App\Models\MunicipalityOnboardingRun;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use App\Notifications\MunicipalAdministratorInvitationNotification;
use App\Services\Municipalities\MunicipalAdministratorInvitationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MunicipalAdministratorInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_invitation_uses_password_broker_and_is_consumed_after_reset(): void
    {
        Queue::fake();
        Notification::fake();
        $operator = $this->globalOperator();

        Artisan::call('mvhab:municipality:onboard', [
            '--actor-id' => $operator->id,
            '--name' => 'Município de Alcanena',
            '--code' => 'ALCANENA',
            '--tax-number' => '506000001',
            '--contact-email' => 'habitacao@alcanena.pt',
            '--admin-name' => 'Administrador Municipal',
            '--admin-email' => 'admin@alcanena.pt',
            '--justification' => 'Aprovação institucional para o primeiro onboarding municipal.',
            '--confirm' => true,
        ]);

        $run = MunicipalityOnboardingRun::query()->sole();
        $invitation = $run->invitation()->sole();
        $administrator = User::query()->findOrFail($run->admin_user_id);

        app(MunicipalAdministratorInvitationService::class)->send($invitation->id);

        Notification::assertSentTo(
            $administrator,
            MunicipalAdministratorInvitationNotification::class,
        );
        $this->assertSame(
            MunicipalAdministratorInvitationStatus::Sent,
            $invitation->refresh()->status,
        );
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $administrator->email,
        ]);

        event(new PasswordReset($administrator));

        $this->assertSame(
            MunicipalAdministratorInvitationStatus::Consumed,
            $invitation->refresh()->status,
        );
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
