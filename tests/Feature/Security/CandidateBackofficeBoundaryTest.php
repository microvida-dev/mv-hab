<?php

namespace Tests\Feature\Security;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateBackofficeBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_is_forbidden_from_backoffice_even_with_anomalous_custom_permissions(): void
    {
        $candidate = User::factory()->create(['status' => 'active']);
        $candidate->assignRole('candidate');
        $permissions = Permission::query()
            ->whereIn('name', ['documents.view', 'documents.approve', 'roles.view'])
            ->pluck('id')
            ->all();
        $customRole = Role::query()->create([
            'name' => 'candidate_escalation_probe',
            'label' => 'Teste de fronteira do candidato',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
        $customRole->permissions()->sync($permissions);
        $candidate->roles()->attach($customRole);

        $this->actingAs($candidate)
            ->get(route('admin.document-reviews.index'))
            ->assertForbidden();

        $this->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.roles.index'))
            ->assertForbidden();

        $this->get(route('candidate.dashboard'))->assertOk();
    }
}
