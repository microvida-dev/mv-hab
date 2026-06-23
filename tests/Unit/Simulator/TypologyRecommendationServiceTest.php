<?php

namespace Tests\Unit\Simulator;

use App\Services\Simulator\TypologyRecommendationService;
use Tests\TestCase;

class TypologyRecommendationServiceTest extends TestCase
{
    public function test_recommends_typology_from_household_size(): void
    {
        $result = app(TypologyRecommendationService::class)->recommend([
            'household_members_count' => 3,
        ]);

        $this->assertSame('T2', $result['typology']);
        $this->assertSame(2, $result['bedrooms']);
    }
}
