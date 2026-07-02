<?php

namespace Database\Seeders\Pilot;

use Illuminate\Database\Seeder;

class PilotHearingComplaintSeeder extends Seeder
{
    public function run(): void
    {
        app(PilotScenarioBuilder::class)->ensureHearingAndComplaints();
    }
}
