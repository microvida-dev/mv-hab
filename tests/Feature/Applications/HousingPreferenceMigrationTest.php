<?php

namespace Tests\Feature\Applications;

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
}
