<?php

namespace Database\Seeders\Pilot;

use Database\Seeders\Sprint24BackofficeOperationalSeeder;
use Illuminate\Database\Seeder;

class PilotOperationsAgendaSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(Sprint24BackofficeOperationalSeeder::class);

        app(PilotScenarioBuilder::class)->ensureOperationsAgenda();
    }
}
