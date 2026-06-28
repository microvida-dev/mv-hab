<?php

namespace Tests\Feature\UX;

use App\Models\HousingUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class HousingUnitCaseWorkspaceTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_authorized_user_opens_housing_unit_workspace(): void
    {
        $housingUnit = HousingUnit::factory()->create(['parish' => 'Alcanena', 'locality' => 'Centro']);

        $this->assertEnterpriseWorkspace('backoffice.cases.housing-units.show', $housingUnit, 'Fogo');
    }
}
