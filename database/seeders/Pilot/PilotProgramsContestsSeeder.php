<?php

namespace Database\Seeders\Pilot;

use Database\Seeders\DemoAlcanenaAffordableRentSeeder;
use Database\Seeders\EligibilityDemoRuleSetSeeder;
use Database\Seeders\ScoringDemoRuleSetSeeder;
use Illuminate\Database\Seeder;

class PilotProgramsContestsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoAlcanenaAffordableRentSeeder::class,
            EligibilityDemoRuleSetSeeder::class,
            ScoringDemoRuleSetSeeder::class,
        ]);
    }
}
