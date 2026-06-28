<?php

namespace Tests\Unit\Cases;

use App\Models\Contest;
use App\Services\Cases\CaseTypeRegistry;
use Tests\TestCase;

class CaseTypeRegistryTest extends TestCase
{
    public function test_registry_contains_enterprise_case_types(): void
    {
        $types = app(CaseTypeRegistry::class)->types();

        $this->assertArrayHasKey('contest', $types);
        $this->assertArrayHasKey('contract', $types);
        $this->assertArrayHasKey('maintenance_request', $types);
        $this->assertArrayHasKey('support_ticket', $types);
        $this->assertSame(Contest::class, $types['contest']['model']);
        $this->assertSame('contests.view', $types['contest']['permission']);
    }
}
