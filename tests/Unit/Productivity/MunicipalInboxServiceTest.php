<?php

namespace Tests\Unit\Productivity;

use App\Enums\OfficialNotificationType;
use App\Models\OfficialNotification;
use App\Services\Productivity\MunicipalInboxService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class MunicipalInboxServiceTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_groups_notifications_by_operational_category_with_minimized_payload(): void
    {
        $administrator = $this->backofficeUser();
        OfficialNotification::factory()->create([
            'user_id' => $administrator->id,
            'notification_type' => OfficialNotificationType::VisitScheduled->value,
            'event_code' => 'visit_scheduled',
            'body' => 'Texto privado não exposto',
        ]);

        $groups = app(MunicipalInboxService::class)->forUser($administrator);

        $this->assertSame('Operacional', $groups[0]['title']);
        $this->assertSame('Visita solicitada', $groups[0]['items'][0]['title']);
        $this->assertArrayNotHasKey('body', $groups[0]['items'][0]);
    }
}
