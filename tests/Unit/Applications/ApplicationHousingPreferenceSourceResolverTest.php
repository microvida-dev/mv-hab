<?php

namespace Tests\Unit\Applications;

use App\Enums\ApplicationPreferenceSource;
use App\Models\ApplicationPreference;
use App\Services\Allocation\HousingPreferenceService;
use App\Services\Applications\ApplicationHousingPreferenceSourceResolver;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCompatibleHousingContext;
use Tests\TestCase;

class ApplicationHousingPreferenceSourceResolverTest extends TestCase
{
    use CreatesCompatibleHousingContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_legacy_source_remains_readable_until_official_flow_is_initialized(): void
    {
        $context = $this->compatibleHousingContext();
        $unit = $this->compatibleContestHousingUnit($context);
        $legacy = ApplicationPreference::factory()->create([
            'application_id' => $context['application']->id,
            'housing_unit_id' => $unit->housing_unit_id,
            'preference_order' => 1,
        ]);
        $context['application']->forceFill([
            'preference_source' => ApplicationPreferenceSource::Legacy,
        ])->save();
        $resolver = app(ApplicationHousingPreferenceSourceResolver::class);

        $preferences = $resolver->preferencesFor(
            $context['application']->fresh(),
        );

        $this->assertCount(1, $preferences);
        $resolved = $preferences->first();
        $this->assertInstanceOf(ApplicationPreference::class, $resolved);
        $this->assertTrue($resolved->is($legacy));
    }

    public function test_official_empty_selection_never_reactivates_legacy_preferences(): void
    {
        $context = $this->compatibleHousingContext();
        $unit = $this->compatibleContestHousingUnit($context);
        ApplicationPreference::factory()->create([
            'application_id' => $context['application']->id,
            'housing_unit_id' => $unit->housing_unit_id,
            'preference_order' => 1,
        ]);
        $context['application']->forceFill([
            'preference_source' => ApplicationPreferenceSource::Legacy,
        ])->save();
        $service = app(HousingPreferenceService::class);
        $service->replace(
            $context['application']->fresh(),
            [[
                'contest_housing_unit_id' => $unit->id,
                'preference_order' => 1,
            ]],
            $context['candidate'],
        );
        $service->replace(
            $context['application']->fresh(),
            [],
            $context['candidate'],
        );
        $application = $context['application']->fresh();
        $resolver = app(ApplicationHousingPreferenceSourceResolver::class);

        $this->assertSame(
            ApplicationPreferenceSource::Official,
            $application->preference_source,
        );
        $this->assertNotNull(
            $application->official_preferences_initialized_at,
        );
        $this->assertCount(0, $resolver->preferencesFor($application));
        $this->assertDatabaseCount('application_preferences', 1);
    }

    public function test_reconciled_source_uses_official_rows_and_conflict_is_fail_closed(): void
    {
        $context = $this->compatibleHousingContext();
        $unit = $this->compatibleContestHousingUnit($context);
        $service = app(HousingPreferenceService::class);
        $service->replace(
            $context['application'],
            [[
                'contest_housing_unit_id' => $unit->id,
                'preference_order' => 1,
            ]],
            $context['candidate'],
        );
        $resolver = app(ApplicationHousingPreferenceSourceResolver::class);
        $application = $context['application']->fresh();
        $resolver->markReconciled($application);

        $this->assertCount(
            1,
            $resolver->preferencesFor($application->fresh()),
        );

        $resolver->markRequiresManualReview($application);

        $this->assertSame(
            ApplicationPreferenceSource::RequiresManualReview,
            $application->fresh()->preference_source,
        );
        $this->assertCount(
            0,
            $resolver->preferencesFor($application->fresh()),
        );
    }
}
