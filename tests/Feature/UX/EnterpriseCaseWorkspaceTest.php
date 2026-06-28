<?php

namespace Tests\Feature\UX;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class EnterpriseCaseWorkspaceTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_enterprise_case_workspace_supports_main_case_types(): void
    {
        $cases = $this->enterpriseCases();

        $this->assertEnterpriseWorkspace('backoffice.cases.contests.show', $cases['contest'], 'Concurso');
        $this->assertEnterpriseWorkspace('backoffice.cases.contracts.show', $cases['contract'], 'Contrato');
        $this->assertEnterpriseWorkspace('backoffice.cases.maintenance.show', $cases['maintenance_request'], 'Pedido de manutenção');
        $this->assertEnterpriseWorkspace('backoffice.cases.inspections.show', $cases['inspection'], 'Vistoria');
    }
}
