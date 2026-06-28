<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortugueseTerminologyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_critical_english_terms_do_not_appear_on_primary_ux_pages(): void
    {
        $administrator = $this->userWithRole('administrator');
        $application = Application::factory()->submitted()->create();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Workspaces')
            ->assertDontSee('Inbox Municipal')
            ->assertDontSee('My Work');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Cronologia')
            ->assertDontSee('Timeline')
            ->assertDontSee('Work Task');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
