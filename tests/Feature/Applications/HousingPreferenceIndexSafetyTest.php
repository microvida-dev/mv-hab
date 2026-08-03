<?php

namespace Tests\Feature\Applications;

use App\Models\HousingPreference;
use App\Services\Allocation\HousingPreferenceService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCompatibleHousingContext;
use Tests\TestCase;

class HousingPreferenceIndexSafetyTest extends TestCase
{
    use CreatesCompatibleHousingContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_soft_deleted_positions_and_units_can_be_reused_and_reordered_repeatedly(): void
    {
        $context = $this->compatibleHousingContext();
        $units = collect(range(1, 3))
            ->map(fn () => $this->compatibleContestHousingUnit($context));
        $service = app(HousingPreferenceService::class);
        $original = $units->values()
            ->map(fn ($unit, int $index): array => [
                'contest_housing_unit_id' => $unit->id,
                'preference_order' => $index + 1,
            ])
            ->all();

        $service->replace(
            $context['application'],
            $original,
            $context['candidate'],
        );

        $context['application']->housingPreferences()
            ->where('preference_order', 2)
            ->firstOrFail()
            ->delete();
        $service->replace(
            $context['application']->fresh(),
            $original,
            $context['candidate'],
        );

        $context['application']->housingPreferences()
            ->where('contest_housing_unit_id', $units[0]->id)
            ->firstOrFail()
            ->delete();
        $service->replace(
            $context['application']->fresh(),
            $original,
            $context['candidate'],
        );

        $reordered = [
            [
                'contest_housing_unit_id' => $units[2]->id,
                'preference_order' => 1,
            ],
            [
                'contest_housing_unit_id' => $units[0]->id,
                'preference_order' => 2,
            ],
            [
                'contest_housing_unit_id' => $units[1]->id,
                'preference_order' => 3,
            ],
        ];

        for ($cycle = 0; $cycle < 3; $cycle++) {
            $service->replace(
                $context['application']->fresh(),
                $reordered,
                $context['candidate'],
            );
            $service->replace(
                $context['application']->fresh(),
                $original,
                $context['candidate'],
            );
        }

        $this->assertSame(
            [1, 2, 3],
            $context['application']->housingPreferences()
                ->orderBy('preference_order')
                ->pluck('preference_order')
                ->map(fn (mixed $order): int => (int) $order)
                ->all(),
        );
        $this->assertSame(
            3,
            HousingPreference::withTrashed()
                ->where('application_id', $context['application']->id)
                ->count(),
        );
        $this->assertDatabaseCount('application_snapshots', 0);
    }
}
