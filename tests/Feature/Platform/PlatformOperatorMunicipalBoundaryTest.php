<?php

namespace Tests\Feature\Platform;

use App\Data\Platform\PlatformOperatorBootstrapManifest;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\PlatformOperatorAssignment;
use App\Services\Platform\PlatformOperatorManagementService;
use App\Services\Platform\PlatformOperatorScopeService;
use Database\Seeders\SystemAccessSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformOperatorMunicipalBoundaryTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_municipal_account_cannot_be_bootstrapped_as_global_operator(): void
    {
        $municipality = Municipality::factory()->create();
        $municipalUser = $this->platformUser(
            ['platform_operators.view'],
            assigned: false,
            municipalityId: $municipality->id,
        );
        $manifest = PlatformOperatorBootstrapManifest::fromArray([
            'environment' => 'testing',
            'approved_user_ids' => [$municipalUser->id],
            'approval_references' => ['SEC-APPROVAL-001', 'MANAGEMENT-APPROVAL-001'],
            'bootstrap_operator_reference' => 'OPS-RUNBOOK-001',
            'approved_at' => '2026-07-23',
        ]);

        $this->expectException(DomainException::class);

        app(PlatformOperatorManagementService::class)->planBootstrap($manifest);
    }

    public function test_null_municipality_no_longer_implies_platform_scope(): void
    {
        $municipality = Municipality::factory()->create();
        $unassigned = $this->platformUser([
            'municipality_features.view',
        ], assigned: false);

        $this->assertNull($unassigned->municipality_id);
        $this->assertFalse(app(PlatformOperatorScopeService::class)->hasGlobalScope($unassigned));
        $this->assertFalse(Gate::forUser($unassigned)
            ->allows('view', [MunicipalityFeatureEntitlement::class, $municipality]));
    }

    public function test_assignment_on_municipal_account_does_not_cross_the_boundary(): void
    {
        $municipality = Municipality::factory()->create();
        $municipalUser = $this->platformUser(
            ['platform_operators.view', 'municipality_features.view'],
            municipalityId: $municipality->id,
        );

        $this->assertDatabaseHas('platform_operator_assignments', [
            'user_id' => $municipalUser->id,
        ]);
        $this->assertFalse(Gate::forUser($municipalUser)
            ->allows('viewAny', PlatformOperatorAssignment::class));
        $this->assertFalse(Gate::forUser($municipalUser)
            ->allows('view', [MunicipalityFeatureEntitlement::class, $municipality]));
    }
}
