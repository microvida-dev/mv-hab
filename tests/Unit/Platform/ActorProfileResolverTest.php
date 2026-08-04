<?php

declare(strict_types=1);

namespace Tests\Unit\Platform;

use App\Enums\ActorProfile;
use App\Enums\PlatformOperatorStatus;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use App\Services\Platform\ActorProfileResolver;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActorProfileResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_active_global_assignment_has_priority_over_administrator_role(): void
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');
        PlatformOperatorAssignment::factory()->for($user)->create();

        $resolver = app(ActorProfileResolver::class);

        $this->assertSame(
            ActorProfile::PlatformAdministrator,
            $resolver->primary($user),
        );
        $this->assertSame(
            [ActorProfile::PlatformAdministrator],
            $resolver->profiles($user),
        );
    }

    public function test_administrator_role_without_assignment_and_without_municipality_is_unclassified(): void
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');

        $this->assertSame(
            ActorProfile::Unclassified,
            app(ActorProfileResolver::class)->primary($user),
        );
    }

    public function test_administrator_with_municipality_remains_municipal_administrator(): void
    {
        $municipality = Municipality::factory()->create();
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');

        $this->assertSame(
            ActorProfile::MunicipalAdministrator,
            app(ActorProfileResolver::class)->primary($user),
        );
    }

    public function test_revoked_assignment_removes_platform_profile_immediately(): void
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');
        $assignment = PlatformOperatorAssignment::factory()
            ->for($user)
            ->create();

        $resolver = app(ActorProfileResolver::class);

        $this->assertSame(
            ActorProfile::PlatformAdministrator,
            $resolver->primary($user),
        );

        $assignment->forceFill([
            'status' => PlatformOperatorStatus::Revoked,
            'revoked_at' => now(),
            'revoke_justification' => 'Revogação explícita para teste de fronteira.',
        ])->save();

        $this->assertSame(
            ActorProfile::Unclassified,
            $resolver->primary($user),
        );
    }

    public function test_candidate_is_never_classified_as_platform_administrator(): void
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $user->assignRole('candidate');

        $this->assertSame(
            ActorProfile::Candidate,
            app(ActorProfileResolver::class)->primary($user),
        );
    }
}
