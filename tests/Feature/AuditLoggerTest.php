<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logger_records_context_for_an_auditable_model(): void
    {
        $user = User::factory()->create();
        $citizen = Citizen::factory()->create(['name' => 'Foundation Test']);

        $this->actingAs($user);

        $log = app(AuditLogger::class)->record(
            event: AuditEvents::UPDATE,
            auditable: $citizen,
            module: 'citizens',
            action: 'update',
            description: 'Foundation audit record.',
            oldValues: ['name' => 'Old value'],
            newValues: ['name' => 'Foundation Test'],
            metadata: ['reason' => 'sprint_1_foundation'],
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'event' => AuditEvents::UPDATE,
            'auditable_type' => $citizen->getMorphClass(),
            'auditable_id' => $citizen->id,
            'module' => 'citizens',
            'action' => 'update',
        ]);

        $this->assertSame('Foundation Test', $log->new_values['name']);
        $this->assertSame('sprint_1_foundation', $log->metadata['reason']);
    }
}
