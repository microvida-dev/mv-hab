<?php

namespace Tests\Unit\Platform;

use App\Enums\PlatformOperatorScope;
use App\Enums\PlatformOperatorStatus;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Services\Platform\PlatformOperatorScopeService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformOperatorScopeServiceTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_active_explicit_assignment_grants_global_scope_and_revocation_removes_it(): void
    {
        $user = $this->platformUser(['platform_operators.view']);
        $scope = app(PlatformOperatorScopeService::class);

        $this->assertSame(PlatformOperatorScope::Global, $scope->scopeFor($user));
        $this->assertTrue($scope->hasGlobalScope($user));
        $this->assertSame(1, $scope->activeCount());

        PlatformOperatorAssignment::query()
            ->where('user_id', $user->id)
            ->update([
                'status' => PlatformOperatorStatus::Revoked->value,
                'revoked_by' => $user->id,
                'revoked_at' => now(),
                'revoke_justification' => 'Revogação explícita de teste.',
            ]);

        $this->assertNull($scope->scopeFor($user->refresh()));
    }

    public function test_null_municipality_without_assignment_fails_closed(): void
    {
        $user = $this->platformUser(['platform_operators.view'], assigned: false);

        $this->assertFalse(app(PlatformOperatorScopeService::class)->hasGlobalScope($user));
    }

    public function test_municipal_and_candidate_accounts_fail_closed_even_with_assignment(): void
    {
        $municipality = Municipality::factory()->create();
        $municipalUser = $this->platformUser(
            ['platform_operators.view'],
            municipalityId: $municipality->id,
        );
        $candidate = $this->platformUser(['platform_operators.view']);
        $candidate->assignRole('candidate');
        $scope = app(PlatformOperatorScopeService::class);

        $this->assertFalse($scope->hasGlobalScope($municipalUser));
        $this->assertFalse($scope->hasGlobalScope($candidate->refresh()));
    }
}
