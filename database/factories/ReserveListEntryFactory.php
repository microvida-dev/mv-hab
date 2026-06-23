<?php

namespace Database\Factories;

use App\Enums\ReserveListEntryStatus;
use App\Models\AllocationRun;
use App\Models\Application;
use App\Models\DefinitiveListEntry;
use App\Models\ReserveList;
use App\Models\ReserveListEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReserveListEntry> */
class ReserveListEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reserve_list_id' => ReserveList::factory(),
            'allocation_run_id' => AllocationRun::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'definitive_list_entry_id' => DefinitiveListEntry::factory(),
            'reserve_position' => 1,
            'status' => ReserveListEntryStatus::Waiting->value,
        ];
    }
}
