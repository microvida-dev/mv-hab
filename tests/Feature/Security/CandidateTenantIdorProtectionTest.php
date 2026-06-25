<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateTenantIdorProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_and_tenant_functional_access_do_not_grant_backoffice_permissions(): void
    {
        $this->seed(SystemAccessSeeder::class);

        $candidate = User::factory()->create(['status' => 'active']);
        $candidate->assignRole('candidate');

        $this->assertTrue($candidate->hasPermission('applications.create'));
        $this->assertTrue($candidate->hasPermission('contracts.view'));
        $this->assertTrue($candidate->hasPermission('maintenance_requests.create'));
        $this->assertFalse($candidate->hasPermission('users.view'));
        $this->assertFalse($candidate->hasPermission('roles.assign'));
        $this->assertFalse($candidate->hasPermission('work_tasks.view'));

        $this->actingAs($candidate)
            ->get(route('backoffice.work-tasks.index'))
            ->assertForbidden();
    }
}
