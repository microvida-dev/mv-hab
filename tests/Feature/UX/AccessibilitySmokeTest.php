<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilitySmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_dashboard_has_labels_focus_and_headings(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('for="global-search"', false)
            ->assertSee('aria-describedby="global-search-help"', false)
            ->assertSee('focus-visible:ring-2', false)
            ->assertSee('<h1', false)
            ->assertSee('<h2', false);
    }

    public function test_case_workspace_tabs_have_minimum_accessible_semantics(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('role="tab"', false)
            ->assertSee('aria-selected=', false)
            ->assertSee('for="case-search"', false);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
