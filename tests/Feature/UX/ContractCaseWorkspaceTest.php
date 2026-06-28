<?php

namespace Tests\Feature\UX;

use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class ContractCaseWorkspaceTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_authorized_user_opens_contract_workspace(): void
    {
        $contract = Contract::factory()->create();

        $this->assertEnterpriseWorkspace('backoffice.cases.contracts.show', $contract, 'Contrato');
    }
}
