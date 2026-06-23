<?php

namespace Tests\Unit\Simulator;

use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\Program;
use App\Services\Simulator\ContestRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContestRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommends_public_open_contest_with_matching_typology_and_rent(): void
    {
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->open()->for($program)->create();
        $housingUnit = HousingUnit::factory()->create(['typology' => 'T1', 'monthly_rent' => 300]);
        ContestHousingUnit::factory()->for($program)->for($contest)->for($housingUnit)->create([
            'typology' => 'T1',
            'monthly_rent' => 300,
        ]);

        $recommendations = app(ContestRecommendationService::class)->recommend(
            ['preferred_typologies' => ['T1']],
            ['status' => 'recommended', 'typology' => 'T1', 'bedrooms' => 1, 'options' => ['T1'], 'warnings' => [], 'payload' => []],
            ['status' => 'estimated', 'rent_min' => null, 'rent_max' => 350.0, 'effort_rate' => 35.0, 'warnings' => [], 'payload' => []],
        );

        $this->assertNotEmpty($recommendations);
        $this->assertSame($contest->id, $recommendations[0]['contest_id']);
    }
}
