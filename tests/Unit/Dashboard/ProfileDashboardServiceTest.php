<?php

namespace Tests\Unit\Dashboard;

use App\Models\User;
use App\Services\Dashboard\ProfileDashboardService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_profile_dashboard_payload_contains_authorized_sections(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Técnica',
            'status' => 'active',
        ]);
        $user->assignRole('municipal_technician');

        $payload = app(ProfileDashboardService::class)->forUser($user);

        $this->assertSame('Bom trabalho, Maria', $payload['greeting']);
        $this->assertSame('Técnico municipal', $payload['profile_label']);
        $this->assertNotEmpty($payload['workspaces']);
        $this->assertNotEmpty($payload['metrics']);
        $this->assertNotEmpty($payload['quick_actions']);
        $this->assertNotEmpty($payload['widgets']);
    }
}
