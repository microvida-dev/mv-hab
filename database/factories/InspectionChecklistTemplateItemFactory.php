<?php

namespace Database\Factories;

use App\Models\InspectionChecklistTemplate;
use App\Models\InspectionChecklistTemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionChecklistTemplateItem>
 */
class InspectionChecklistTemplateItemFactory extends Factory
{
    protected $model = InspectionChecklistTemplateItem::class;

    public function definition(): array
    {
        return [
            'inspection_checklist_template_id' => InspectionChecklistTemplate::factory(),
            'code' => 'item-'.fake()->unique()->numberBetween(1, 999),
            'label' => fake()->randomElement(['Paredes', 'Pavimentos', 'Instalação elétrica', 'Canalização']),
            'area' => fake()->randomElement(['Sala', 'Cozinha', 'WC', 'Quartos']),
            'is_required' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
