<?php

namespace Database\Seeders;

use App\Models\InspectionChecklistTemplate;
use Illuminate\Database\Seeder;

class InspectionChecklistTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = InspectionChecklistTemplate::query()->firstOrCreate(
            ['code' => 'housing-standard-demo'],
            ['name' => 'Checklist habitacional base demo', 'inspection_type' => 'periodic', 'is_active' => true],
        );

        foreach (['Paredes e tetos', 'Pavimentos', 'Instalação elétrica', 'Canalização', 'Caixilharias'] as $index => $label) {
            $template->items()->firstOrCreate(['code' => 'item-'.($index + 1)], ['label' => $label, 'sort_order' => $index + 1]);
        }
    }
}
