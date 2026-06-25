<?php

namespace Tests\Feature\Operations;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OperationalHealthCommandTest extends TestCase
{
    public function test_health_command_reports_core_operational_checks_as_sanitized_json(): void
    {
        Artisan::call('mvhab:operations:health', ['--json' => true]);
        $checks = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $names = collect($checks)->pluck('name')->all();

        foreach ([
            'app_environment',
            'debug_state',
            'database_connection',
            'cache_store',
            'queue_connection',
            'failed_jobs',
            'private_storage',
            'log_channel',
            'schedule_list',
            'route_health',
        ] as $expectedCheck) {
            $this->assertContains($expectedCheck, $names);
        }

        $this->assertStringNotContainsString('APP_KEY', Artisan::output());
        $this->assertStringNotContainsString('password', strtolower(Artisan::output()));
    }
}
