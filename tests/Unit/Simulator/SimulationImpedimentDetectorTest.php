<?php

namespace Tests\Unit\Simulator;

use App\Services\Simulator\SimulationImpedimentDetector;
use Tests\TestCase;

class SimulationImpedimentDetectorTest extends TestCase
{
    public function test_property_flag_creates_blocking_impediment(): void
    {
        $impediments = app(SimulationImpedimentDetector::class)->detect(
            ['has_property' => true],
            ['score' => 100, 'missing_fields' => [], 'complete' => true],
        );

        $this->assertTrue((bool) $impediments[0]['is_blocking']);
    }
}
