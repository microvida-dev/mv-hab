<?php

namespace Database\Seeders;

use App\Enums\HousingUnitStatus;
use App\Models\HousingUnit;
use Illuminate\Database\Seeder;

class HousingUnitSeeder extends Seeder
{
    public function run(): void
    {
        $housingUnits = [
            [
                'code' => 'HAB-001',
                'address' => 'Rua das Flores, 12',
                'typology' => 'T2',
                'bedrooms' => 2,
                'monthly_rent' => 185.00,
                'status' => HousingUnitStatus::Available->value,
            ],
            [
                'code' => 'HAB-002',
                'address' => 'Rua do Sol, 28',
                'typology' => 'T3',
                'bedrooms' => 3,
                'monthly_rent' => 240.00,
                'status' => HousingUnitStatus::Available->value,
            ],
            [
                'code' => 'HAB-003',
                'address' => 'Avenida Central, 105',
                'typology' => 'T1',
                'bedrooms' => 1,
                'monthly_rent' => 150.00,
                'status' => HousingUnitStatus::Maintenance->value,
            ],
            [
                'code' => 'HAB-004',
                'address' => 'Travessa da Escola, 4',
                'typology' => 'T4',
                'bedrooms' => 4,
                'monthly_rent' => 285.00,
                'status' => HousingUnitStatus::Inactive->value,
            ],
        ];

        foreach ($housingUnits as $housingUnit) {
            HousingUnit::updateOrCreate(
                ['code' => $housingUnit['code']],
                $housingUnit,
            );
        }
    }
}
