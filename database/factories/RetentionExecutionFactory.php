<?php

namespace Database\Factories;

use App\Enums\RetentionExecutionStatus;
use App\Models\RetentionExecution;
use App\Models\RetentionPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<RetentionExecution> */
class RetentionExecutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'execution_number' => 'RET-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'retention_policy_id' => RetentionPolicy::factory(),
            'status' => RetentionExecutionStatus::Simulation->value,
            'mode' => 'simulation',
            'matched_records_count' => 0,
            'affected_records_count' => 0,
        ];
    }
}
