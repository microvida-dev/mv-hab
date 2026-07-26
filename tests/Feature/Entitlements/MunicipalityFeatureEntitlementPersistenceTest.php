<?php

namespace Tests\Feature\Entitlements;

use App\Enums\FeatureKey;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MunicipalityFeatureEntitlementPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_has_only_the_required_domain_columns(): void
    {
        $this->assertSame([
            'id',
            'municipality_id',
            'feature_key',
            'enabled',
            'created_at',
            'updated_at',
        ], Schema::getColumnListing('municipality_feature_entitlements'));
    }

    public function test_model_casts_relations_and_scopes_are_typed(): void
    {
        $municipality = Municipality::factory()->create();
        MunicipalityFeatureEntitlement::factory()
            ->for($municipality)
            ->forFeature(FeatureKey::ApplicationReview)
            ->enabled()
            ->create();
        MunicipalityFeatureEntitlement::factory()
            ->for($municipality)
            ->forFeature(FeatureKey::ApplicationExport)
            ->disabled()
            ->create();

        $entitlement = MunicipalityFeatureEntitlement::query()
            ->forMunicipality($municipality)
            ->forFeature(FeatureKey::ApplicationReview)
            ->enabled()
            ->sole();

        $this->assertSame(FeatureKey::ApplicationReview, $entitlement->feature_key);
        $this->assertTrue($entitlement->enabled);
        $this->assertTrue($entitlement->municipality->is($municipality));
        $this->assertCount(2, $municipality->featureEntitlements);
    }

    public function test_compound_unique_constraint_rejects_duplicate_feature(): void
    {
        $municipality = Municipality::factory()->create();
        MunicipalityFeatureEntitlement::factory()
            ->for($municipality)
            ->forFeature(FeatureKey::ApplicationIntake)
            ->create();

        $this->expectException(QueryException::class);

        MunicipalityFeatureEntitlement::factory()
            ->for($municipality)
            ->forFeature(FeatureKey::ApplicationIntake)
            ->create();
    }

    public function test_migration_backfills_existing_municipalities_but_not_future_ones_and_can_be_reapplied(): void
    {
        $migration = require database_path('migrations/2026_07_23_000035_create_municipality_feature_entitlements_table.php');
        $migration->down();

        $existing = Municipality::factory()->create();
        $migration->up();

        $this->assertCount(3, MunicipalityFeatureEntitlement::query()
            ->forMunicipality($existing)
            ->enabled()
            ->get());

        $future = Municipality::factory()->create();
        $this->assertDatabaseMissing('municipality_feature_entitlements', [
            'municipality_id' => $future->id,
        ]);

        $migration->down();
        $this->assertFalse(Schema::hasTable('municipality_feature_entitlements'));

        $migration->up();
        $this->assertTrue(Schema::hasTable('municipality_feature_entitlements'));
        $this->assertDatabaseCount('municipality_feature_entitlements', 6);
    }
}
