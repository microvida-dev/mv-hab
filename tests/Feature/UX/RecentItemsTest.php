<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecentItemsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_workspace_visit_is_persisted_as_recent_item(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('workspaces.show', 'patrimonio'))
            ->assertOk();

        $this->assertDatabaseHas('navigation_recent_items', [
            'user_id' => $administrator->id,
            'item_type' => 'workspace',
            'workspace_key' => 'patrimonio',
            'label' => 'Património',
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recentes')
            ->assertSee('Património');
    }

    public function test_repeated_workspace_visit_updates_existing_recent_item(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('workspaces.show', 'patrimonio'))
            ->assertOk();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('workspaces.show', 'patrimonio'))
            ->assertOk();

        $this->assertDatabaseCount('navigation_recent_items', 1);
        $this->assertDatabaseHas('navigation_recent_items', [
            'user_id' => $administrator->id,
            'workspace_key' => 'patrimonio',
            'visits_count' => 2,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
