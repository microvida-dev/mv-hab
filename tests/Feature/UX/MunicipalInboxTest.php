<?php

namespace Tests\Feature\UX;

use App\Enums\NotificationPriority;
use App\Enums\OfficialNotificationStatus;
use App\Enums\OfficialNotificationType;
use App\Models\OfficialNotification;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class MunicipalInboxTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_inbox_aggregates_existing_notifications_without_rendering_body_or_subject(): void
    {
        $administrator = $this->backofficeUser();
        OfficialNotification::factory()->create([
            'user_id' => $administrator->id,
            'notification_type' => OfficialNotificationType::SupportTicketCreated->value,
            'priority' => NotificationPriority::Urgent->value,
            'status' => OfficialNotificationStatus::Queued->value,
            'event_code' => 'support_ticket_created',
            'subject' => 'Assunto interno sensível',
            'body' => 'Conteúdo interno com NIF 123456789.',
        ]);

        $this->actingAs($administrator)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertOk()
            ->assertSee('Caixa de Entrada Municipal')
            ->assertSee('Operacional')
            ->assertSee('Pedido de apoio criado')
            ->assertDontSee('Assunto interno sensível')
            ->assertDontSee('123456789');
    }
}
