<?php

namespace Tests\Feature\Platform;

use App\Enums\FeatureKey;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\PlatformOperatorAssignment;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformOperatorPolicyTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_scope_and_permission_are_both_required_for_platform_operations(): void
    {
        $municipality = Municipality::factory()->create();
        $scopedWithoutFeaturePermission = $this->platformUser(['platform_operators.view']);
        $permissionWithoutScope = $this->platformUser([
            'municipality_features.view',
        ], assigned: false);
        $authorized = $this->platformUser([
            'platform_operators.view',
            'municipality_features.view',
        ]);

        $this->assertFalse(Gate::forUser($scopedWithoutFeaturePermission)
            ->allows('view', [MunicipalityFeatureEntitlement::class, $municipality]));
        $this->assertFalse(Gate::forUser($permissionWithoutScope)
            ->allows('view', [MunicipalityFeatureEntitlement::class, $municipality]));
        $this->assertTrue(Gate::forUser($authorized)
            ->allows('view', [MunicipalityFeatureEntitlement::class, $municipality]));
        $this->assertTrue(Gate::forUser($authorized)
            ->allows('viewAny', PlatformOperatorAssignment::class));
        $this->assertSame('applications.intake', FeatureKey::ApplicationIntake->value);
    }

    public function test_auditor_with_explicit_scope_is_read_only(): void
    {
        $auditor = $this->platformUser([
            'platform_operators.view',
            'platform_operators.manage',
            'platform_operators.audit',
        ]);
        $auditor->assignRole('auditor');
        $assignment = PlatformOperatorAssignment::query()
            ->where('user_id', $auditor->id)
            ->sole();

        $this->assertTrue(Gate::forUser($auditor)->allows('viewAny', PlatformOperatorAssignment::class));
        $this->assertTrue(Gate::forUser($auditor)->allows('auditAny', PlatformOperatorAssignment::class));
        $this->assertFalse(Gate::forUser($auditor)->allows('create', PlatformOperatorAssignment::class));
        $this->assertFalse(Gate::forUser($auditor)->allows('revoke', $assignment));
    }
}
