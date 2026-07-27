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

    public function test_regulatory_source_hardening_migration_is_reversible(): void
    {
        $path = database_path(
            'migrations/2026_07_27_000043_harden_affordable_rent_regulatory_sources.php',
        );
        $migration = require $path;

        $this->assertInstanceOf(Migration::class, $migration);
        $this->assertTrue(Schema::hasColumn(
            'affordable_rent_regulatory_profiles',
            'sixth_irs_bracket_upper_limit',
        ));
        $this->assertTrue(Schema::hasTable('rent_limit_table_manifests'));
        $this->assertTrue(Schema::hasTable('rent_limit_table_rows'));

        $migration->down();

        $this->assertFalse(Schema::hasTable('rent_limit_table_rows'));
        $this->assertFalse(Schema::hasTable('rent_limit_table_manifests'));
        $this->assertFalse(Schema::hasColumn(
            'affordable_rent_regulatory_profiles',
            'sixth_irs_bracket_upper_limit',
        ));

        $migration->up();

        $this->assertTrue(Schema::hasTable('rent_limit_table_manifests'));
        $this->assertTrue(Schema::hasTable('rent_limit_table_rows'));
        $this->assertTrue(Schema::hasColumn(
            'affordable_rent_regulatory_profiles',
            'irs_source_reference',
        ));
    }
}
