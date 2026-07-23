<?php

namespace Tests\Feature\Platform;

use App\Enums\PlatformOperatorGrantSource;
use App\Enums\PlatformOperatorStatus;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformOperatorAssignmentPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_persists_typed_evidence_and_cannot_be_physically_deleted(): void
    {
        $assignment = PlatformOperatorAssignment::factory()->create();

        $this->assertSame(PlatformOperatorStatus::Active, $assignment->status);
        $this->assertSame(PlatformOperatorGrantSource::Bootstrap, $assignment->grant_source);
        $this->assertNotSame('', $assignment->granted_at->toDateTimeString());
        $this->assertTrue($assignment->isActive());
        $this->assertFalse($assignment->delete());
        $this->assertDatabaseHas('platform_operator_assignments', ['id' => $assignment->id]);
    }

    public function test_user_can_have_only_one_assignment(): void
    {
        $user = User::factory()->create();
        PlatformOperatorAssignment::factory()->for($user)->create();

        $this->expectException(QueryException::class);

        PlatformOperatorAssignment::factory()->for($user)->create();
    }

    public function test_contradictory_active_evidence_is_rejected(): void
    {
        $this->expectException(DomainException::class);

        PlatformOperatorAssignment::factory()->create([
            'revoked_at' => now(),
            'revoke_justification' => 'Evidência incompatível com o estado ativo.',
        ]);
    }

    public function test_migration_is_reversible_without_touching_users(): void
    {
        $user = User::factory()->create();
        $migration = require database_path(
            'migrations/2026_07_23_000036_create_platform_operator_assignments_table.php',
        );

        $migration->down();

        $this->assertFalse(Schema::hasTable('platform_operator_assignments'));
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $migration->up();

        $this->assertTrue(Schema::hasTable('platform_operator_assignments'));
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
