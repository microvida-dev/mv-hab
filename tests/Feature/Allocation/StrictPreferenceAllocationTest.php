<?php

namespace Tests\Feature\Allocation;

use App\Enums\AllocationMethod;
use App\Enums\ApplicationStatus;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\DefinitiveListStatus;
use App\Enums\HousingCompatibilityStatus;
use App\Enums\ListEntryStatus;
use App\Enums\ListEntryType;
use App\Models\AllocationRun;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\HousingPreference;
use App\Models\ProvisionalList;
use App\Models\User;
use App\Services\Allocation\PreferenceAllocationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCompatibleHousingContext;
use Tests\TestCase;

class StrictPreferenceAllocationTest extends TestCase
{
    use CreatesCompatibleHousingContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_allocation_uses_first_available_preference(): void
    {
        $scenario = $this->allocationScenario();
        $result = app(PreferenceAllocationService::class)->allocate(
            $scenario['run'],
            $scenario['actor'],
        );

        $allocation = $result->allocations->firstOrFail();
        $this->assertSame($scenario['units'][0]->id, $allocation->contest_housing_unit_id);
        $this->assertSame(1, $allocation->preference_order);
        $this->assertCount(0, $result->reserveEntries);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'allocations',
            'action' => 'allocation_by_locked_preference',
        ]);
    }

    public function test_allocation_uses_second_preference_when_first_is_unavailable(): void
    {
        $scenario = $this->allocationScenario([1]);
        $result = app(PreferenceAllocationService::class)->allocate(
            $scenario['run'],
            $scenario['actor'],
        );

        $allocation = $result->allocations->firstOrFail();
        $this->assertSame($scenario['units'][1]->id, $allocation->contest_housing_unit_id);
        $this->assertSame(2, $allocation->preference_order);
    }

    public function test_allocation_uses_third_preference_when_previous_choices_are_unavailable(): void
    {
        $scenario = $this->allocationScenario([1, 2]);
        $result = app(PreferenceAllocationService::class)->allocate(
            $scenario['run'],
            $scenario['actor'],
        );

        $allocation = $result->allocations->firstOrFail();
        $this->assertSame($scenario['units'][2]->id, $allocation->contest_housing_unit_id);
        $this->assertSame(3, $allocation->preference_order);
    }

    public function test_no_available_preference_enters_reserve_without_unselected_fallback(): void
    {
        $scenario = $this->allocationScenario([1, 2, 3]);
        $result = app(PreferenceAllocationService::class)->allocate(
            $scenario['run'],
            $scenario['actor'],
        );

        $this->assertCount(0, $result->allocations);
        $this->assertCount(1, $result->reserveEntries);
        $this->assertSame(
            $scenario['entry']->id,
            $result->reserveEntries->firstOrFail()->id,
        );
        $this->assertSame(
            ContestHousingUnitStatus::Available,
            $scenario['units'][3]->fresh()->status,
        );
        $this->assertDatabaseMissing('allocations', [
            'contest_housing_unit_id' => $scenario['units'][3]->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'allocations',
            'action' => 'reserve_by_preference_unavailability',
        ]);
    }

    /**
     * @param  list<int>  $unavailableOrders
     * @return array<string, mixed>
     */
    private function allocationScenario(array $unavailableOrders = []): array
    {
        $context = $this->compatibleHousingContext();
        $units = collect(range(1, 4))
            ->map(fn () => $this->compatibleContestHousingUnit($context));
        $submittedAt = now()->subDay();
        $application = $context['application'];
        $application->forceFill([
            'application_number' => 'CAND-50E-'.fake()->unique()->numerify('######'),
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => $submittedAt,
            'locked_at' => $submittedAt,
        ])->save();

        foreach ($units->take(3)->values() as $index => $unit) {
            $preference = new HousingPreference([
                'preference_order' => $index + 1,
                'compatibility_status' => HousingCompatibilityStatus::Compatible,
                'compatibility_snapshot' => [
                    'version' => 1,
                    'status' => HousingCompatibilityStatus::Compatible->value,
                ],
                'evaluated_at' => $submittedAt,
                'invalidated_at' => null,
                'invalidation_reason' => null,
            ]);
            $preference->forceFill([
                'application_id' => $application->id,
                'user_id' => $context['candidate']->id,
                'contest_id' => $context['contest']->id,
                'contest_housing_unit_id' => $unit->id,
                'housing_unit_id' => $unit->housing_unit_id,
                'regulatory_snapshot_id' => $application->regulatory_snapshot_id,
                'submitted_at' => $submittedAt,
                'locked_at' => $submittedAt,
            ])->save();
        }

        foreach ($unavailableOrders as $order) {
            $units[$order - 1]->forceFill([
                'status' => ContestHousingUnitStatus::Unavailable,
            ])->save();
        }

        $actor = User::factory()->create([
            'municipality_id' => $context['municipality']->id,
        ]);
        $actor->assignRole('administrator');
        $provisionalList = ProvisionalList::factory()->create([
            'program_id' => $context['program']->id,
            'contest_id' => $context['contest']->id,
            'generated_by' => $actor->id,
        ]);
        $definitiveList = DefinitiveList::factory()->create([
            'program_id' => $context['program']->id,
            'contest_id' => $context['contest']->id,
            'provisional_list_id' => $provisionalList->id,
            'status' => DefinitiveListStatus::Locked,
            'generated_by' => $actor->id,
            'approved_by' => $actor->id,
            'published_by' => $actor->id,
            'approved_at' => now(),
            'published_at' => now(),
        ]);
        $entry = DefinitiveListEntry::factory()->create([
            'definitive_list_id' => $definitiveList->id,
            'application_id' => $application->id,
            'user_id' => $context['candidate']->id,
            'entry_type' => ListEntryType::Ranked,
            'status' => ListEntryStatus::Ranked,
            'rank_position' => 1,
            'total_score' => '100.00',
        ]);
        $run = AllocationRun::factory()->create([
            'allocation_rule_set_id' => $context['rule_set']->id,
            'program_id' => $context['program']->id,
            'contest_id' => $context['contest']->id,
            'definitive_list_id' => $definitiveList->id,
            'allocation_method' => AllocationMethod::PreferenceBased,
            'started_by' => $actor->id,
        ]);

        return [
            ...$context,
            'application' => $application->fresh(),
            'units' => $units,
            'actor' => $actor,
            'entry' => $entry,
            'run' => $run,
        ];
    }
}
