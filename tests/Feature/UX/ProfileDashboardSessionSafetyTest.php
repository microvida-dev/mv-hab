<?php

namespace Tests\Feature\UX;

use App\Models\NavigationFavorite;
use App\Models\NavigationRecentItem;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDashboardSessionSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_deactivated_user_cannot_access_profile_dashboard(): void
    {
        $user = $this->userWithRole('municipal_technician');
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_invalid_favorites_and_recent_items_are_ignored_without_500(): void
    {
        $user = $this->userWithRole('administrator');

        NavigationFavorite::query()->create([
            'user_id' => $user->id,
            'label' => 'Rota removida de teste',
            'item_type' => 'page',
            'route_name' => 'missing.route',
        ]);

        NavigationRecentItem::query()->create([
            'user_id' => $user->id,
            'label' => 'Recente removido de teste',
            'item_type' => 'page',
            'route_name' => 'missing.route',
            'last_visited_at' => now(),
            'visits_count' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Rota removida de teste')
            ->assertDontSee('Recente removido de teste');
    }

    public function test_unauthenticated_or_stale_session_is_redirected_without_internal_error(): void
    {
        $this->withSession(['login_web_stale' => 999999])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
