<?php

namespace Database\Factories;

use App\Enums\SecurityChecklistStatus;
use App\Models\SecurityChecklist;
use App\Models\SecurityChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SecurityChecklistItem> */
class SecurityChecklistItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'security_checklist_id' => SecurityChecklist::factory(),
            'category' => 'audit',
            'title' => 'Item demo',
            'description' => 'Validação demo.',
            'status' => SecurityChecklistStatus::Draft->value,
            'recommendation' => 'Validar antes de produção.',
        ];
    }
}
