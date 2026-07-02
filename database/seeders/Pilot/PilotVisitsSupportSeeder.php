<?php

namespace Database\Seeders\Pilot;

use Database\Seeders\CandidateSupportDemoSeeder;
use Illuminate\Database\Seeder;

class PilotVisitsSupportSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CandidateSupportDemoSeeder::class);

        app(PilotScenarioBuilder::class)->ensureVisitsAndSupport();
    }
}
