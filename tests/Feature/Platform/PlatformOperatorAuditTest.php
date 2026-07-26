<?php

namespace Tests\Feature\Platform;

use App\Models\AuditEvent;
use App\Services\Platform\PlatformOperatorManagementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformOperatorAuditTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_grant_and_revoke_write_minimized_immutable_audit_events(): void
    {
        $actor = $this->platformUser([
            'platform_operators.view',
            'platform_operators.manage',
            'platform_operators.audit',
        ]);
        $target = $this->platformUser(['platform_operators.view'], assigned: false);
        session(['mfa.verified_at' => now()]);
        $service = app(PlatformOperatorManagementService::class);
        $assignment = $service->grant(
            $actor,
            $target,
            'Concessão auditável aprovada para operação global.',
        );
        $service->revoke(
            $actor,
            $assignment,
            'Revogação auditável aprovada para operação global.',
        );

        $events = AuditEvent::query()
            ->whereIn('event_code', [
                'platform_operator_granted',
                'platform_operator_revoked',
            ])
            ->orderBy('id')
            ->get();
        $firstEvent = $events->first();

        $this->assertCount(2, $events);

        if (! $firstEvent instanceof AuditEvent) {
            throw new RuntimeException('O evento de auditoria esperado não foi persistido.');
        }

        $this->assertSame($target->id, $firstEvent->subject_user_id);
        $this->assertSame($actor->id, $firstEvent->user_id);
        $this->assertNotNull(data_get($firstEvent->metadata, 'operation_id'));
        $serialized = $events->toJson();
        $this->assertStringNotContainsString($target->email, $serialized);
        $this->assertStringNotContainsString('secret_encrypted', $serialized);
        $this->assertFalse((bool) $firstEvent->update(['description' => 'alterado']));
        $this->assertFalse((bool) $firstEvent->delete());
    }
}
