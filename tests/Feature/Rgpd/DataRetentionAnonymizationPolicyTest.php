<?php

namespace Tests\Feature\Rgpd;

use App\Enums\AnonymizationStatus;
use App\Enums\RetentionExecutionStatus;
use App\Models\RetentionPolicy;
use App\Models\User;
use App\Services\Rgpd\AnonymizationService;
use App\Services\Rgpd\RetentionExecutionService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataRetentionAnonymizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_retention_and_anonymization_are_approved_and_audited(): void
    {
        $actor = $this->userWithRole('administrator');
        $policy = RetentionPolicy::factory()->create();
        $retention = app(RetentionExecutionService::class);

        $execution = $retention->simulate($policy, $actor);
        $approvedExecution = $retention->approve($execution, $actor);
        $completedExecution = $retention->run($approvedExecution, $actor);
        $this->assertSame(RetentionExecutionStatus::Completed, $completedExecution->status);

        $subject = User::factory()->create(['email' => 'titular-anon@example.test']);
        $anon = app(AnonymizationService::class)->create([
            'user_id' => $subject->id,
            'anonymization_type' => 'user_profile',
            'reason' => 'Pedido sintético QA47 com base legal a validar.',
            'scope' => ['user.profile'],
        ], $actor);
        $approved = app(AnonymizationService::class)->approve($anon, $actor);
        $completed = app(AnonymizationService::class)->run($approved, $actor);

        $this->assertSame(AnonymizationStatus::Completed, $completed->status);
        $this->assertStringStartsWith('anon-', $subject->refresh()->email);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'rgpd_retention_executed']);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'rgpd_anonymization_executed']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
