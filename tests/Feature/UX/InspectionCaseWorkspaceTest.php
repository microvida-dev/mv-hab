<?php

namespace Tests\Feature\UX;

use App\Models\PropertyInspection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class InspectionCaseWorkspaceTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_authorized_user_opens_inspection_workspace(): void
    {
        $inspection = PropertyInspection::factory()->create();

        $this->assertEnterpriseWorkspace('backoffice.cases.inspections.show', $inspection, 'Vistoria');
    }
}
