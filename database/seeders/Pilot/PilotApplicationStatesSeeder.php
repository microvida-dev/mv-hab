<?php

namespace Database\Seeders\Pilot;

use Illuminate\Database\Seeder;

class PilotApplicationStatesSeeder extends Seeder
{
    public function run(): void
    {
        app(PilotScenarioBuilder::class)->ensureApplicationStates();
    }
}
