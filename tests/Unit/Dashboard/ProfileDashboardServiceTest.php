<?php

namespace Tests\Unit\Dashboard;

use App\Enums\FeatureKey;
use App\Models\User;
use App\Services\Dashboard\ProfileDashboardService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class ProfileDashboardServiceTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_profile_dashboard_payload_contains_authorized_sections(): void
    {
        $municipality = $this->municipalityWithFeatures(FeatureKey::ApplicationIntake, FeatureKey::ApplicationReview);
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
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
        $this->assertArrayHasKey('workspace_intelligence', $payload);
        $this->assertArrayHasKey('summary', $payload['workspace_intelligence']);
        $this->assertArrayHasKey('workspaces', $payload['workspace_intelligence']);
        $this->assertArrayHasKey('adaptive_dashboard', $payload);
        $this->assertSame('municipal_technician', $payload['adaptive_dashboard']['profile']);
        $this->assertSame('Foco técnico', $payload['adaptive_dashboard']['headline']);
        $this->assertArrayHasKey('focus_metrics', $payload['adaptive_dashboard']);
        $this->assertArrayHasKey('primary_action', $payload['adaptive_dashboard']);
        $this->assertArrayHasKey('priority_queue', $payload);
        $this->assertArrayHasKey('items', $payload['priority_queue']);
        $this->assertArrayHasKey('summary', $payload['priority_queue']);
    }
}
