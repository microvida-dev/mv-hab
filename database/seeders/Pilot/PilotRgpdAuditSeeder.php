<?php

namespace Database\Seeders\Pilot;

use Illuminate\Database\Seeder;

class PilotRgpdAuditSeeder extends Seeder
{
    public function run(): void
    {
        app(PilotScenarioBuilder::class)->ensureRgpdAndAudit();
    }
}
