<?php

namespace Tests\Feature\UX;

use App\Models\Contract;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniversalSearchAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_cannot_use_backoffice_universal_search(): void
    {
        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.index', ['q' => 'contrato']))
            ->assertForbidden();
    }

    public function test_user_without_financial_or_contract_permission_does_not_see_contract_results(): void
    {
        $supportAgent = $this->userWithRole('support_agent');
        Contract::factory()->create([
            'contract_number' => 'CTR-UX05-SECRET',
        ]);

        $this->actingAs($supportAgent)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.index', ['q' => 'CTR-UX05-SECRET']))
            ->assertOk()
            ->assertDontSee('Contrato CTR-UX05-SECRET')
            ->assertSee('Sem resultados autorizados');
    }

    public function test_administrator_sees_authorized_contract_result(): void
    {
        $administrator = $this->userWithRole('administrator');
        Contract::factory()->create([
            'contract_number' => 'CTR-UX05-VISIBLE',
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.index', ['q' => 'CTR-UX05-VISIBLE']))
            ->assertOk()
            ->assertSee('Contrato CTR-UX05-VISIBLE');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
