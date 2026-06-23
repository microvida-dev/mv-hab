<?php

namespace Database\Factories;

use App\Enums\ReserveListStatus;
use App\Models\AllocationRun;
use App\Models\Contest;
use App\Models\DefinitiveList;
use App\Models\Program;
use App\Models\ReserveList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReserveList> */
class ReserveListFactory extends Factory
{
    public function definition(): array
    {
        return [
            'allocation_run_id' => AllocationRun::factory(),
            'program_id' => Program::factory(),
            'contest_id' => Contest::factory(),
            'definitive_list_id' => DefinitiveList::factory(),
            'status' => ReserveListStatus::Active->value,
            'generated_by' => User::factory(),
            'generated_at' => now(),
        ];
    }
}
