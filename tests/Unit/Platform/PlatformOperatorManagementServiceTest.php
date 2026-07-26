<?php

namespace Tests\Unit\Platform;

use App\Enums\PlatformOperatorGrantSource;
use App\Services\Platform\PlatformOperatorManagementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformOperatorManagementServiceTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_grant_creates_only_the_assignment_and_preserves_roles(): void
    {
        $actor = $this->platformUser([
            'platform_operators.view',
            'platform_operators.manage',
            'platform_operators.audit',
        ]);
        $target = $this->platformUser(['platform_operators.view'], assigned: false);
        $targetRoleIds = $target->roles()->pluck('roles.id')->all();
        session(['mfa.verified_at' => now()]);

        $assignment = app(PlatformOperatorManagementService::class)->grant(
            $actor,
            $target,
            'Concessão aprovada para operação global dedicada.',
        );

        $this->assertSame(PlatformOperatorGrantSource::PlatformOperator, $assignment->grant_source);
        $this->assertSame($actor->id, $assignment->granted_by);
        $this->assertSame($targetRoleIds, $target->roles()->pluck('roles.id')->all());
        $this->assertDatabaseCount('role_user', 2);
        $this->assertDatabaseCount('platform_operator_assignments', 2);
    }

    public function test_self_grant_is_blocked_without_creating_an_assignment(): void
    {
        $user = $this->platformUser([
            'platform_operators.view',
            'platform_operators.manage',
        ], assigned: false);
        session(['mfa.verified_at' => now()]);

        try {
            app(PlatformOperatorManagementService::class)->grant(
                $user,
                $user,
                'Tentativa de concessão à própria conta dedicada.',
            );
            $this->fail('A concessão à própria conta devia ser recusada.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('platform_operator_assignments', 0);
        }
    }

    public function test_management_requires_a_verified_mfa_session(): void
    {
        $actor = $this->platformUser([
            'platform_operators.view',
            'platform_operators.manage',
        ]);
        $target = $this->platformUser(['platform_operators.view'], assigned: false);

        $this->expectException(AuthorizationException::class);

        app(PlatformOperatorManagementService::class)->grant(
            $actor,
            $target,
            'Concessão sem sessão multifator verificada.',
        );
    }
}
