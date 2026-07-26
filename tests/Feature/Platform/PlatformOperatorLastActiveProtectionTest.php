<?php

namespace Tests\Feature\Platform;

use App\Models\PlatformOperatorAssignment;
use App\Services\Platform\PlatformOperatorManagementService;
use Database\Seeders\SystemAccessSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformOperatorLastActiveProtectionTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_last_active_operator_cannot_be_revoked(): void
    {
        $operator = $this->platformUser([
            'platform_operators.view',
            'platform_operators.manage',
        ]);
        $assignment = PlatformOperatorAssignment::query()->sole();
        session(['mfa.verified_at' => now()]);

        try {
            app(PlatformOperatorManagementService::class)->revoke(
                $operator,
                $assignment,
                'Tentativa de revogação do último operador ativo.',
            );
            $this->fail('A revogação do último operador devia ser recusada.');
        } catch (DomainException) {
            $this->assertTrue($assignment->refresh()->isActive());
        }
    }

    public function test_self_revoke_is_allowed_only_when_another_operator_remains(): void
    {
        $operator = $this->platformUser([
            'platform_operators.view',
            'platform_operators.manage',
        ]);
        $other = $this->platformUser(['platform_operators.view']);
        $assignment = PlatformOperatorAssignment::query()
            ->where('user_id', $operator->id)
            ->sole();
        session(['mfa.verified_at' => now()]);

        app(PlatformOperatorManagementService::class)->revoke(
            $operator,
            $assignment,
            'Revogação da própria conta com operador alternativo ativo.',
        );

        $this->assertFalse($assignment->refresh()->isActive());
        $this->assertTrue(PlatformOperatorAssignment::query()
            ->where('user_id', $other->id)
            ->firstOrFail()
            ->isActive());
    }
}
