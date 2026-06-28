<?php

namespace Tests\Unit\Productivity;

use App\Enums\OfficialNotificationType;
use App\Models\OfficialNotification;
use App\Services\Productivity\OperationalNotificationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class OperationalNotificationServiceTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_summarizes_existing_inbox_groups(): void
    {
        $administrator = $this->backofficeUser();
        OfficialNotification::factory()->create([
            'user_id' => $administrator->id,
            'notification_type' => OfficialNotificationType::SupportTicketCreated->value,
            'event_code' => 'support_ticket_created',
        ]);

        $summary = app(OperationalNotificationService::class)->summary($administrator);

        $this->assertSame('Caixa de Entrada Municipal', $summary['label']);
        $this->assertSame(1, $summary['total']);
    }
}
