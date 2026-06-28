<?php

namespace Tests\Feature\UX;

use App\Enums\OfficialNotificationType;
use App\Models\OfficialNotification;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class OperationalNotificationTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_dashboard_notification_summary_uses_existing_notifications(): void
    {
        $administrator = $this->backofficeUser();
        OfficialNotification::factory()->create([
            'user_id' => $administrator->id,
            'notification_type' => OfficialNotificationType::MaintenanceRequestCreated->value,
            'event_code' => 'maintenance_request_created',
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Caixa de Entrada Municipal')
            ->assertSee('1 notificações operacionais agrupadas');
    }
}
