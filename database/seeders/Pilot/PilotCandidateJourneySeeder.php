<?php

namespace Database\Seeders\Pilot;

use Illuminate\Database\Seeder;

class PilotCandidateJourneySeeder extends Seeder
{
    public function run(): void
    {
        app(PilotScenarioBuilder::class)->ensureCandidateJourney();
    }
}
