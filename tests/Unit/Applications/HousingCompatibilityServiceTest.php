<?php

namespace Tests\Unit\Applications;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\HousingCompatibilityStatus;
use App\Enums\RegulatoryConfigurationStatus;
use App\Models\Contest;
use App\Models\Municipality;
use App\Services\Applications\HousingCompatibilityService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesCompatibleHousingContext;
use Tests\TestCase;

class HousingCompatibilityServiceTest extends TestCase
{
    use CreatesCompatibleHousingContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_versioned_typology_and_capacity_rules_cover_one_two_three_and_seven_people(): void
    {
        $scenarios = [
            [1, 'T2', 1, 2],
            [2, 'T2', 1, 2],
            [3, 'T3', 2, 3],
        ];

        foreach ($scenarios as [$members, $typology, $minimumBedrooms, $maximumBedrooms]) {
            $context = $this->compatibleHousingContext(
                $members,
                $typology,
                $minimumBedrooms,
                $maximumBedrooms,
            );
            $compatible = $this->compatibleContestHousingUnit(
                $context,
                $typology,
                $maximumBedrooms,
                1,
                $members,
            );
            $this->compatibleContestHousingUnit(
                $context,
                'T'.($maximumBedrooms + 1),
                $maximumBedrooms + 1,
                1,
                $members,
            );

            $options = app(HousingCompatibilityService::class)
                ->optionsFor($context['application']);

            $this->assertSame([$compatible->id], $options->pluck('unit.id')->all());
        }

        $sevenPeople = $this->compatibleHousingContext(
            7,
            null,
            4,
            null,
        );
        $adequate = $this->compatibleContestHousingUnit(
            $sevenPeople,
            'T4',
            4,
            7,
            8,
        );
        $this->compatibleContestHousingUnit(
            $sevenPeople,
            'T4',
            4,
            1,
            6,
        );
        $this->compatibleContestHousingUnit(
            $sevenPeople,
            'T3',
            3,
            1,
            8,
        );

        $options = app(HousingCompatibilityService::class)
            ->optionsFor($sevenPeople['application']);

        $this->assertSame([$adequate->id], $options->pluck('unit.id')->all());
    }

    public function test_financial_checks_fail_closed_for_effort_limit_annual_limit_and_incomplete_income(): void
    {
        $context = $this->compatibleHousingContext();
        $affordable = $this->compatibleContestHousingUnit($context);
        $expensive = $this->compatibleContestHousingUnit(
            $context,
            monthlyRent: '900.00',
        );
        $service = app(HousingCompatibilityService::class);

        $this->assertTrue(
            $service->evaluate($context['application'], $affordable)->compatible,
        );
        $effortResult = $service->evaluate(
            $context['application'],
            $expensive,
        );
        $this->assertSame(
            HousingCompatibilityStatus::Incompatible,
            $effortResult->status,
        );
        $this->assertFalse(
            collect($effortResult->checks)->firstWhere('key', 'effort_rate')['passed'],
        );

        $context['income']->forceFill([
            'monthly_amount' => '4166.67',
            'annual_amount' => '50000.00',
        ])->save();
        $annualResult = $service->evaluate(
            $context['application']->fresh(),
            $affordable,
        );
        $this->assertSame(
            HousingCompatibilityStatus::Incompatible,
            $annualResult->status,
        );
        $this->assertFalse(
            collect($annualResult->checks)->firstWhere('key', 'income_limit')['passed'],
        );

        $context['income']->delete();
        $incompleteResult = $service->evaluate(
            $context['application']->fresh(),
            $affordable,
        );
        $this->assertSame(
            HousingCompatibilityStatus::RequiresData,
            $incompleteResult->status,
        );
        $this->assertNull($incompleteResult->snapshot['annual_income']);
        $this->assertNull($incompleteResult->snapshot['monthly_income']);
    }

    public function test_compatibility_uses_the_exact_paa_or_rsaa_profile_and_rejects_incomplete_rsaa(): void
    {
        $paa = $this->compatibleHousingContext();
        $paaUnit = $this->compatibleContestHousingUnit($paa);
        $paaResult = app(HousingCompatibilityService::class)
            ->evaluate($paa['application'], $paaUnit);

        $this->assertTrue($paaResult->compatible);
        $this->assertSame(
            AffordableRentLegalRegime::PaaLegacy2019->value,
            $paaResult->snapshot['legal_regime'],
        );
        $this->assertSame($paa['profile']->id, $paaResult->snapshot['regulatory_profile_id']);

        $rsaa = $this->compatibleHousingContext(
            regime: AffordableRentLegalRegime::Rsaa2026,
        );
        $rsaaUnit = $this->compatibleContestHousingUnit($rsaa);
        $rsaaResult = app(HousingCompatibilityService::class)
            ->evaluate($rsaa['application'], $rsaaUnit);

        $this->assertTrue($rsaaResult->compatible);
        $this->assertSame(
            AffordableRentLegalRegime::Rsaa2026->value,
            $rsaaResult->snapshot['legal_regime'],
        );

        $incomplete = $this->compatibleHousingContext(
            regime: AffordableRentLegalRegime::Rsaa2026,
            configurationStatus: RegulatoryConfigurationStatus::Incomplete,
        );
        $incompleteUnit = $this->compatibleContestHousingUnit($incomplete);
        $incompleteResult = app(HousingCompatibilityService::class)
            ->evaluate($incomplete['application'], $incompleteUnit);

        $this->assertFalse($incompleteResult->compatible);
        $this->assertSame(
            HousingCompatibilityStatus::ConfigurationIncomplete,
            $incompleteResult->status,
        );
    }

    public function test_options_exclude_other_contests_municipalities_and_unavailable_units(): void
    {
        $context = $this->compatibleHousingContext();
        $valid = $this->compatibleContestHousingUnit($context);
        $otherMunicipality = Municipality::factory()->create();
        $foreign = $this->compatibleContestHousingUnit(
            $context,
            municipality: $otherMunicipality,
        );
        $otherContest = Contest::factory()
            ->for($context['program'])
            ->open()
            ->create();
        $otherContestUnit = $this->compatibleContestHousingUnit(
            $context,
            contest: $otherContest,
        );
        $unavailable = $this->compatibleContestHousingUnit(
            $context,
            status: ContestHousingUnitStatus::Unavailable,
        );
        $service = app(HousingCompatibilityService::class);
        $options = $service->optionsFor($context['application']);

        $this->assertSame([$valid->id], $options->pluck('unit.id')->all());
        $this->assertFalse(
            $service->evaluate($context['application'], $foreign)->compatible,
        );
        $this->assertFalse(
            $service->evaluate(
                $context['application'],
                $otherContestUnit,
            )->compatible,
        );
        $this->assertFalse(
            $service->evaluate(
                $context['application'],
                $unavailable,
            )->compatible,
        );
    }

    public function test_minimum_adult_income_accessibility_and_selection_window_are_cumulative(): void
    {
        $incomeContext = $this->compatibleHousingContext(
            parameters: ['minimum_adult_monthly_income' => '2100.00'],
        );
        $incomeUnit = $this->compatibleContestHousingUnit($incomeContext);
        $service = app(HousingCompatibilityService::class);
        $incomeResult = $service->evaluate(
            $incomeContext['application'],
            $incomeUnit,
        );

        $this->assertSame(
            HousingCompatibilityStatus::Incompatible,
            $incomeResult->status,
        );
        $this->assertFalse(
            collect($incomeResult->checks)
                ->firstWhere('key', 'minimum_income')['passed'],
        );

        $accessibilityContext = $this->compatibleHousingContext();
        $accessibilityContext['applicant']->forceFill([
            'has_reduced_mobility' => true,
        ])->save();
        $inaccessibleUnit = $this->compatibleContestHousingUnit(
            $accessibilityContext,
        );
        $accessibleUnit = $this->compatibleContestHousingUnit(
            $accessibilityContext,
        );
        $accessibleUnit->forceFill(['accessible' => true])->save();

        $this->assertFalse(
            $service->evaluate(
                $accessibilityContext['application']->fresh(),
                $inaccessibleUnit,
            )->compatible,
        );
        $this->assertTrue(
            $service->evaluate(
                $accessibilityContext['application']->fresh(),
                $accessibleUnit->fresh(),
            )->compatible,
        );

        $windowContext = $this->compatibleHousingContext();
        $windowUnit = $this->compatibleContestHousingUnit($windowContext);
        $windowContext['rule_set']->forceFill([
            'preference_selection_starts_at' => now()->subWeek(),
            'preference_selection_ends_at' => now()->subMinute(),
        ])->save();
        $windowResult = $service->evaluate(
            $windowContext['application']->fresh(),
            $windowUnit,
        );

        $this->assertSame(
            HousingCompatibilityStatus::Incompatible,
            $windowResult->status,
        );
        $this->assertFalse(
            collect($windowResult->checks)
                ->firstWhere('key', 'selection_window')['passed'],
        );
    }

    public function test_options_query_count_is_bounded_and_independent_of_cards(): void
    {
        $singleContext = $this->compatibleHousingContext();
        $this->compatibleContestHousingUnit($singleContext);
        $singleQueryCount = $this->queryCountForOptions($singleContext);

        $context = $this->compatibleHousingContext();
        for ($index = 0; $index < 20; $index++) {
            $this->compatibleContestHousingUnit($context);
        }

        $options = app(HousingCompatibilityService::class)
            ->optionsFor($context['application']->fresh());
        $multipleQueryCount = $this->queryCountForOptions($context);

        $this->assertCount(20, $options);
        $this->assertLessThanOrEqual(
            $singleQueryCount + 2,
            $multipleQueryCount,
            "A consulta cresceu de {$singleQueryCount} para {$multipleQueryCount} queries.",
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function queryCountForOptions(array $context): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(HousingCompatibilityService::class)
            ->optionsFor($context['application']->fresh());
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
