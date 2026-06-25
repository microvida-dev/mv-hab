<?php

namespace Tests\Unit\Operations;

use App\Services\Operations\OperationalHealthService;
use Tests\TestCase;

class OperationalHealthServiceTest extends TestCase
{
    public function test_operational_health_service_returns_sanitized_checks(): void
    {
        $checks = app(OperationalHealthService::class)->checks();

        $this->assertNotEmpty($checks);
        $this->assertContains('debug_state', collect($checks)->pluck('name')->all());
        $this->assertStringNotContainsString('APP_KEY', json_encode($checks, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('DB_PASSWORD', json_encode($checks, JSON_THROW_ON_ERROR));
    }
}
