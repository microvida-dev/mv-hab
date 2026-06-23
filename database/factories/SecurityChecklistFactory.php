<?php

namespace Database\Factories;

use App\Enums\SecurityChecklistStatus;
use App\Models\SecurityChecklist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SecurityChecklist> */
class SecurityChecklistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'checklist_number' => 'CHK-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'name' => 'Checklist demo',
            'status' => SecurityChecklistStatus::InProgress->value,
            'environment' => 'test',
            'started_at' => now(),
        ];
    }
}
