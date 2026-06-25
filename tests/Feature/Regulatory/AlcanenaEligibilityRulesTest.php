<?php

namespace Tests\Feature\Regulatory;

use App\Models\Contest;
use App\Models\EligibilityRuleSet;
use Database\Seeders\DemoAlcanenaAffordableRentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlcanenaEligibilityRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_8_and_9_eligibility_rules_are_present_and_traceable(): void
    {
        $this->seed(DemoAlcanenaAffordableRentSeeder::class);

        $contest = Contest::query()->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)->firstOrFail();
        $criteria = EligibilityRuleSet::query()
            ->where('contest_id', $contest->id)
            ->firstOrFail()
            ->criteria()
            ->get();

        foreach ([
            'candidate_is_adult',
            'all_household_members_have_valid_residency',
            'annual_income_within_alcanena_limit',
            'all_non_dependent_adults_meet_rmmg',
            'typology_is_adequate',
            'no_declared_property_impediment',
            'tax_and_social_security_status_regular',
            'no_fraud_or_false_declarations_last_five_years',
        ] as $code) {
            $this->assertTrue($criteria->contains('code', $code), "Missing criterion {$code}");
        }

        $this->assertSame(7, $criteria->where('requires_manual_review', true)->count());
    }
}
