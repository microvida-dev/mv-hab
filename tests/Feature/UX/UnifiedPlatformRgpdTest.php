<?php

namespace Tests\Feature\UX;

use App\Enums\OfficialNotificationType;
use App\Models\OfficialNotification;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedPlatformRgpdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_unified_dashboard_does_not_render_sensitive_notification_payloads(): void
    {
        $administrator = $this->userWithRole('administrator');

        OfficialNotification::factory()->create([
            'user_id' => $administrator->id,
            'notification_type' => OfficialNotificationType::MaintenanceRequestCreated->value,
            'event_code' => 'maintenance_request_created',
            'subject' => 'Morada privada e NIF interno',
            'body' => 'NIF 123456789 em storage/app/private/documento.pdf',
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Caixa de Entrada Municipal')
            ->assertDontSee('123456789')
            ->assertDontSee('storage/app/private')
            ->assertDontSee('Morada privada');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
