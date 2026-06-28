<?php

namespace Tests\Feature\UX;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class MaintenanceCaseWorkspaceTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_authorized_user_opens_maintenance_workspace(): void
    {
        $request = MaintenanceRequest::factory()->create();

        $this->assertEnterpriseWorkspace('backoffice.cases.maintenance.show', $request, 'Pedido de manutenção');
    }
}
