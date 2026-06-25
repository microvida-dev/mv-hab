<?php

namespace Tests\Feature\Security;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitiveAccessAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_audit_formatter_masks_sensitive_values_and_paths(): void
    {
        $actor = User::factory()->create();
        $event = app(AuditTrailService::class)->record(
            'qa32_sensitive_audit',
            $actor,
            AuditEventCategory::Security,
            AuditEventSeverity::Warning,
            'Teste QA32 de minimização.',
            oldValues: [
                'nif' => '123456789',
                'storage_path' => 'documents/private/file.pdf',
                'password' => 'secret',
            ],
            newValues: [
                'email' => 'qa32@example.test',
                'token' => 'raw-token',
            ],
            actor: $actor,
        );

        $this->assertSame('[masked]', $event->old_values['nif']);
        $this->assertSame('[masked]', $event->old_values['storage_path']);
        $this->assertSame('[masked]', $event->old_values['password']);
        $this->assertSame('[masked]', $event->new_values['token']);
        $this->assertSame('qa32@example.test', $event->new_values['email']);
    }
}
