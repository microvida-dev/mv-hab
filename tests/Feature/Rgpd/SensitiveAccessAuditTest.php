<?php

namespace Tests\Feature\Rgpd;

use App\Models\SensitiveDataAccessLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitiveAccessAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_access_logs_are_append_only_for_rgpd_acceptance(): void
    {
        $log = SensitiveDataAccessLog::factory()->create(['action' => 'view']);

        $this->assertFalse($log->update(['action' => 'download']));
        $this->assertSame('view', $log->fresh()->action);
    }
}
