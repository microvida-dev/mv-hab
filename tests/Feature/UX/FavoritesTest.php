<?php

namespace Tests\Feature\UX;

use App\Models\NavigationFavorite;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoritesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_user_can_persist_authorized_workspace_favorite(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('navigation.favorites.store'), [
                'workspace_key' => 'concursos',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('navigation_favorites', [
            'user_id' => $administrator->id,
            'item_type' => 'workspace',
            'workspace_key' => 'concursos',
            'label' => 'Concursos',
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Favoritos')
            ->assertSee('Concursos');
    }

    public function test_user_cannot_favorite_unauthorized_workspace(): void
    {
        $supportAgent = $this->userWithRole('support_agent');

        $this->actingAs($supportAgent)
            ->post(route('navigation.favorites.store'), [
                'workspace_key' => 'administracao',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('navigation_favorites', [
            'user_id' => $supportAgent->id,
            'workspace_key' => 'administracao',
        ]);
    }

    public function test_user_can_remove_own_favorite(): void
    {
        $administrator = $this->userWithRole('administrator');
        $favorite = NavigationFavorite::query()->create([
            'user_id' => $administrator->id,
            'item_type' => 'workspace',
            'workspace_key' => 'concursos',
            'label' => 'Concursos',
            'route_name' => 'workspaces.show',
            'route_parameters' => ['workspace' => 'concursos'],
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('navigation.favorites.destroy', $favorite))
            ->assertRedirect();

        $this->assertDatabaseMissing('navigation_favorites', [
            'id' => $favorite->id,
        ]);
    }

    public function test_user_can_reorder_own_favorites(): void
    {
        $administrator = $this->userWithRole('administrator');
        $first = NavigationFavorite::query()->create([
            'user_id' => $administrator->id,
            'item_type' => 'workspace',
            'workspace_key' => 'concursos',
            'label' => 'Concursos',
            'route_name' => 'workspaces.show',
            'route_parameters' => ['workspace' => 'concursos'],
            'sort_order' => 1,
        ]);
        $second = NavigationFavorite::query()->create([
            'user_id' => $administrator->id,
            'item_type' => 'workspace',
            'workspace_key' => 'gestao',
            'label' => 'Gestão',
            'route_name' => 'workspaces.show',
            'route_parameters' => ['workspace' => 'gestao'],
            'sort_order' => 2,
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->put(route('navigation.favorites.reorder'), [
                'favorites' => [$second->id, $first->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('navigation_favorites', [
            'id' => $second->id,
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('navigation_favorites', [
            'id' => $first->id,
            'sort_order' => 2,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
