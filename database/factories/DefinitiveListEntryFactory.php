<?php

namespace Database\Factories;

use App\Enums\ListEntryStatus;
use App\Enums\ListEntryType;
use App\Models\Application;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DefinitiveListEntry> */
class DefinitiveListEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'definitive_list_id' => DefinitiveList::factory(),
            'application_id' => Application::factory()->submitted(),
            'entry_type' => ListEntryType::Ranked->value,
            'status' => ListEntryStatus::Ranked->value,
            'rank_position' => 1,
            'total_score' => 10,
            'public_identifier' => 'CAND-'.now()->format('Y').'-'.fake()->unique()->bothify('??????????'),
            'changed_after_complaint' => false,
        ];
    }
}
