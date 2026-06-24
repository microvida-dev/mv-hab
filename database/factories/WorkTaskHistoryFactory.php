<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkTaskHistory> */
class WorkTaskHistoryFactory extends Factory
{
    protected $model = WorkTaskHistory::class;

    public function definition(): array
    {
        return [
            'work_task_id' => WorkTask::factory(),
            'event_code' => 'work_task_created',
            'actor_id' => User::factory(),
            'to_status' => WorkTask::STATUS_PENDING,
            'note' => fake()->sentence(),
            'metadata' => [],
            'occurred_at' => now(),
        ];
    }
}
