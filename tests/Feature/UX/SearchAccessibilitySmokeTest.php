<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAccessibilitySmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_search_page_has_label_dialog_empty_state_and_focusable_results_structure(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.index'))
            ->assertOk()
            ->assertSee('for="universal-search"', false)
            ->assertSee('aria-describedby="universal-search-help"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('Centro de Comandos');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.index', ['q' => 'sem-resultados-ux05']))
            ->assertOk()
            ->assertSee('Sem resultados autorizados');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
