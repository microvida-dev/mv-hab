<?php

namespace Tests\Feature\Applications;

use App\Enums\ApplicationPreferenceSource;
use App\Models\Application;
use App\Models\ApplicationPreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HousingPreferenceMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_compatibility_migration_applies_reverts_and_reapplies_without_dropping_legacy_data(): void
    {
        $path = database_path(
            'migrations/2026_07_27_000042_add_compatible_housing_preference_context.php',
        );
        $migration = require $path;

        $this->assertInstanceOf(Migration::class, $migration);
        $this->assertTrue(
            Schema::hasColumn(
                'allocation_rule_sets',
                'minimum_preferences',
            ),
        );
        $this->assertTrue(
            Schema::hasColumn(
                'housing_preferences',
                'compatibility_status',
            ),
        );
        $this->assertTrue(Schema::hasTable('application_preferences'));

        $migration->down();

        $this->assertFalse(
            Schema::hasColumn(
                'allocation_rule_sets',
                'minimum_preferences',
            ),
        );
        $this->assertFalse(
            Schema::hasColumn(
                'housing_preferences',
                'compatibility_status',
            ),
        );
        $this->assertTrue(Schema::hasTable('application_preferences'));

        $migration->up();

        $this->assertTrue(
            Schema::hasColumn(
                'allocation_rule_sets',
                'minimum_preferences',
            ),
        );
        $this->assertTrue(
            Schema::hasColumn(
                'housing_preferences',
                'compatibility_status',
            ),
        );
        $this->assertTrue(Schema::hasTable('application_preferences'));
    }

    public function test_preference_source_migration_is_reversible_and_backfills_legacy_without_deleting_data(): void
    {
        $application = Application::factory()->create();
        ApplicationPreference::factory()->create([
            'application_id' => $application->id,
        ]);
        $path = database_path(
            'migrations/2026_07_27_000044_add_application_preference_source_state.php',
        );
        $migration = require $path;

        $this->assertInstanceOf(Migration::class, $migration);
        $this->assertTrue(
            Schema::hasColumn('applications', 'preference_source'),
        );

        $migration->down();

        $this->assertFalse(
            Schema::hasColumn('applications', 'preference_source'),
        );
        $this->assertDatabaseCount('application_preferences', 1);

        $migration->up();

        $this->assertTrue(
            Schema::hasColumn('applications', 'preference_source'),
        );
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'preference_source' => ApplicationPreferenceSource::Legacy->value,
        ]);
        $this->assertDatabaseCount('application_preferences', 1);
    }

    public function test_final_snapshot_unique_constraint_is_structural(): void
    {
        $indexes = collect(Schema::getIndexes('application_snapshots'));
        $index = $indexes->firstWhere(
            'name',
            'application_snapshots_type_unique',
        );

        $this->assertIsArray($index);
        $this->assertTrue($index['unique']);
        $this->assertSame(
            ['application_id', 'snapshot_type'],
            $index['columns'],
        );
    }
}
