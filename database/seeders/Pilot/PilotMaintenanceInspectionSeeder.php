<?php

namespace Database\Seeders\Pilot;

use Illuminate\Database\Seeder;

class PilotMaintenanceInspectionSeeder extends Seeder
{
    public function run(): void
    {
        app(PilotScenarioBuilder::class)->ensureMaintenanceAndInspections();
    }
}
