<?php

namespace Tests\Feature\UX;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class CaseWorkspaceNextActionTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_next_action_is_suggestive_and_does_not_mutate_case(): void
    {
        $request = MaintenanceRequest::factory()->create();
        $status = $request->getRawOriginal('status');

        $this->actingAs($this->userWithRole())
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.maintenance.show', $request))
            ->assertOk()
            ->assertSee('Próxima ação');

        $this->assertSame($status, $request->fresh()?->getRawOriginal('status'));
    }
}
