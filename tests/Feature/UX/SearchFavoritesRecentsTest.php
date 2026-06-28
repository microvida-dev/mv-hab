<?php

namespace Tests\Feature\UX;

use App\Models\NavigationFavorite;
use App\Models\NavigationRecentItem;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchFavoritesRecentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_invalid_favorites_and_recent_items_do_not_break_dashboard_or_search(): void
    {
        $administrator = $this->userWithRole('administrator');

        NavigationFavorite::query()->create([
            'user_id' => $administrator->id,
            'item_type' => 'page',
            'label' => 'Rota inválida',
            'route_name' => 'backoffice.missing.route',
            'route_parameters' => [],
        ]);

        NavigationRecentItem::query()->create([
            'user_id' => $administrator->id,
            'item_type' => 'page',
            'label' => 'Recente inválido',
            'route_name' => 'backoffice.other.missing.route',
            'route_parameters' => [],
            'last_visited_at' => now(),
            'visits_count' => 1,
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Rota inválida')
            ->assertDontSee('Recente inválido');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.index', ['q' => 'Concursos']))
            ->assertOk()
            ->assertSee('Pesquisa Universal');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
