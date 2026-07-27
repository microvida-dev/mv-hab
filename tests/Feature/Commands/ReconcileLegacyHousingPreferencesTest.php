<?php

namespace Tests\Feature\Commands;

use App\Enums\HousingCompatibilityStatus;
use App\Models\ApplicationPreference;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCompatibleHousingContext;
use Tests\TestCase;

class ReconcileLegacyHousingPreferencesTest extends TestCase
{
    use CreatesCompatibleHousingContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_reconciliation_is_dry_run_by_default_and_idempotent_when_applied(): void
    {
        $context = $this->compatibleHousingContext();
        $unit = $this->compatibleContestHousingUnit($context);
        $legacy = ApplicationPreference::factory()->create([
            'application_id' => $context['application']->id,
            'housing_unit_id' => $unit->housing_unit_id,
            'preference_order' => 1,
        ]);

        $this->artisan('preferences:reconcile-legacy')
            ->expectsOutputToContain('Dry-run concluído')
            ->assertSuccessful();
        $this->assertDatabaseCount('housing_preferences', 0);

        $this->artisan('preferences:reconcile-legacy', ['--apply' => true])
            ->expectsOutputToContain(
                'Reconciliação aplicada apenas às correspondências inequívocas.',
            )
            ->assertSuccessful();
        $this->assertDatabaseHas('housing_preferences', [
            'application_id' => $context['application']->id,
            'contest_housing_unit_id' => $unit->id,
            'housing_unit_id' => $unit->housing_unit_id,
            'preference_order' => 1,
            'compatibility_status' => HousingCompatibilityStatus::RequiresRevalidation->value,
            'legacy_application_preference_id' => $legacy->id,
        ]);

        $this->artisan('preferences:reconcile-legacy', ['--apply' => true])
            ->assertSuccessful();
        $this->assertDatabaseCount('housing_preferences', 1);
        $this->assertDatabaseCount('application_preferences', 1);
    }
}
