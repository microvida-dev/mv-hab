<?php

namespace Tests\Unit\Simulator;

use App\Services\Simulator\RentEstimateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentEstimateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimates_rent_from_default_effort_rate(): void
    {
        $result = app(RentEstimateService::class)->estimate([
            'monthly_income' => 1000,
        ]);

        $this->assertSame(350.0, $result['rent_max']);
        $this->assertSame(35.0, $result['effort_rate']);
    }
}
