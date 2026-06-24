<?php

namespace Database\Factories;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WorkTask> */
class WorkTaskFactory extends Factory
{
    protected $model = WorkTask::class;

    public function definition(): array
    {
        return [
            'task_number' => 'WTK-'.now()->format('YmdHis').'-'.Str::upper(fake()->bothify('??##')),
            'type' => WorkTask::TYPE_DOCUMENT_REVIEW,
            'source' => 'factory',
            'priority' => WorkTask::PRIORITY_NORMAL,
            'status' => WorkTask::STATUS_PENDING,
            'municipal_team_id' => MunicipalTeam::factory(),
            'assigned_user_id' => null,
            'due_at' => now()->addWeek(),
            'metadata' => [],
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function assigned(User $user): static
    {
        return $this->state(fn () => [
            'assigned_user_id' => $user->getKey(),
            'status' => WorkTask::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);
    }
}
