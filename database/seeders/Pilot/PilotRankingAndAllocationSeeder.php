<?php

namespace Database\Seeders\Pilot;

use Illuminate\Database\Seeder;

class PilotRankingAndAllocationSeeder extends Seeder
{
    public function run(): void
    {
        app(PilotScenarioBuilder::class)->ensureRankingAndAllocation();
    }
}
