<?php

namespace Database\Seeders\Pilot;

use Database\Seeders\MunicipalEndToEndWorkflowSeeder;
use Illuminate\Database\Seeder;

class PilotUsersAndTeamsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MunicipalEndToEndWorkflowSeeder::class);
    }
}
