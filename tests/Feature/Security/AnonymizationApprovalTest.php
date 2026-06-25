<?php

namespace Tests\Feature\Security;

use App\Enums\AnonymizationStatus;
use App\Models\User;
use App\Services\Rgpd\AnonymizationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnonymizationApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_anonymization_requires_approval_masks_profile_and_is_audited(): void
    {
        $actor = $this->userWithRole('administrator');
        $subject = User::factory()->create([
            'name' => 'Titular QA32',
            'email' => 'titular-qa32@example.test',
        ]);
        $service = app(AnonymizationService::class);

        $request = $service->create([
            'data_subject_request_id' => null,
            'user_id' => $subject->id,
            'anonymization_type' => 'user_profile',
            'reason' => 'Pedido QA32 com base legal validada.',
            'scope' => ['user.profile'],
        ], $actor);

        $this->assertSame(AnonymizationStatus::Draft, $request->status);
        $approved = $service->approve($request, $actor);
        $this->assertSame(AnonymizationStatus::Approved, $approved->status);
        $completed = $service->run($approved, $actor);

        $this->assertSame(AnonymizationStatus::Completed, $completed->status);
        $this->assertStringStartsWith('anon-', $subject->refresh()->email);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'rgpd_anonymization_requested']);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'rgpd_anonymization_executed']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
