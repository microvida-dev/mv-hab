<?php

namespace Database\Seeders;

use App\Models\InspectionChecklistTemplate;
use Illuminate\Database\Seeder;

class InspectionChecklistTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = InspectionChecklistTemplate::query()->firstOrNew([
            'code' => 'housing-standard-demo',
        ]);

        $template->fill([
            'name' => 'Checklist habitacional base demo',
            'inspection_type' => 'periodic',
            'is_active' => true,
        ]);

        $template->forceFill([
            'municipality_id' => null,
            'is_system' => true,
            'created_by' => null,
        ])->save();

        foreach ([
            'Paredes e tetos',
            'Pavimentos',
            'Instalação elétrica',
            'Canalização',
            'Caixilharias',
        ] as $index => $label) {
            $template->items()->updateOrCreate(
                [
                    'code' => 'item-'.($index + 1),
                ],
                [
                    'label' => $label,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
