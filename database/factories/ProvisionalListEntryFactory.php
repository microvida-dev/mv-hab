<?php

namespace Database\Factories;

use App\Enums\ListEntryStatus;
use App\Enums\ListEntryType;
use App\Models\Application;
use App\Models\ProvisionalList;
use App\Models\ProvisionalListEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProvisionalListEntry> */
class ProvisionalListEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provisional_list_id' => ProvisionalList::factory(),
            'application_id' => Application::factory()->submitted(),
            'entry_type' => ListEntryType::Ranked->value,
            'status' => ListEntryStatus::Ranked->value,
            'rank_position' => 1,
            'total_score' => 10,
            'public_identifier' => 'CAND-'.now()->format('Y').'-'.fake()->unique()->bothify('??????????'),
            'candidate_name_masked' => null,
            'application_number_masked' => 'CAND-****-0001',
        ];
    }
}
