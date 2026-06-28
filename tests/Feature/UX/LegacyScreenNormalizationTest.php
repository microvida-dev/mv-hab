<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyScreenNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_work_task_and_report_legacy_screens_use_portuguese_labels(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.work-tasks.dashboard'))
            ->assertOk()
            ->assertSee('Painel de tarefas')
            ->assertDontSee('Dashboard de tarefas');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.index'))
            ->assertOk()
            ->assertSee('Painel operacional')
            ->assertSee('Painel executivo')
            ->assertDontSee('Dashboard operacional')
            ->assertDontSee('Dashboard executivo');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
