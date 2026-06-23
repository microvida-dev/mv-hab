<?php

namespace Database\Seeders;

use App\Models\MaintenanceCategory;
use Illuminate\Database\Seeder;

class MaintenanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'plumbing', 'name' => 'Canalização', 'default_urgency' => 'urgent'],
            ['code' => 'electricity', 'name' => 'Eletricidade', 'default_urgency' => 'urgent'],
            ['code' => 'structure', 'name' => 'Estrutura e acabamentos', 'default_urgency' => 'normal'],
            ['code' => 'equipment', 'name' => 'Equipamentos', 'default_urgency' => 'normal'],
        ] as $category) {
            MaintenanceCategory::query()->firstOrCreate(['code' => $category['code']], $category);
        }
    }
}
