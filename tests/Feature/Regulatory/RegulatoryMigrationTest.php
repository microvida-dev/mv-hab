<?php

namespace Tests\Feature\Regulatory;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegulatoryMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regulatory_migration_applies_reverts_and_reapplies(): void
    {
        $path = database_path(
            'migrations/2026_07_27_000041_create_affordable_rent_regulatory_layer.php',
        );
        $migration = require $path;

        $this->assertInstanceOf(Migration::class, $migration);
        $this->assertTrue(Schema::hasTable('affordable_rent_regulatory_profiles'));
        $this->assertTrue(Schema::hasTable('regulatory_snapshots'));
        $this->assertTrue(Schema::hasColumn('contracts', 'regulatory_snapshot_id'));

        $migration->down();

        $this->assertFalse(Schema::hasTable('regulatory_snapshots'));
        $this->assertFalse(Schema::hasTable('affordable_rent_regulatory_profiles'));
        $this->assertFalse(Schema::hasColumn('contracts', 'regulatory_snapshot_id'));
        $this->assertFalse(Schema::hasColumn('programs', 'regulatory_profile_id'));

        $migration->up();

        $this->assertTrue(Schema::hasTable('affordable_rent_regulatory_profiles'));
        $this->assertTrue(Schema::hasTable('regulatory_snapshots'));
        $this->assertTrue(Schema::hasColumn('contracts', 'regulatory_snapshot_id'));
        $this->assertTrue(Schema::hasColumn('programs', 'regulatory_profile_id'));
    }
}
