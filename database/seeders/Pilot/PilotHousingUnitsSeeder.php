<?php

namespace Database\Seeders\Pilot;

use Database\Seeders\HousingUnitSeeder;
use Illuminate\Database\Seeder;

class PilotHousingUnitsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(HousingUnitSeeder::class);
    }
}
