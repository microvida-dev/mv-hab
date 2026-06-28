<?php

namespace Tests\Unit\Cases;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Cases\CaseNextActionResolver;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseNextActionResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_next_action_is_suggestive_for_maintenance_case(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');
        $request = MaintenanceRequest::factory()->create();

        $action = app(CaseNextActionResolver::class)->forCase($user, 'maintenance_request', $request);

        $this->assertNotSame('', $action->label);
        $this->assertSame($request->getRawOriginal('status'), $request->fresh()?->getRawOriginal('status'));
    }
}
