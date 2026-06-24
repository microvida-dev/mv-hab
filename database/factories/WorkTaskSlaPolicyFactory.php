<?php

namespace Database\Factories;

use App\Models\WorkTask;
use App\Models\WorkTaskSlaPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkTaskSlaPolicy> */
class WorkTaskSlaPolicyFactory extends Factory
{
    protected $model = WorkTaskSlaPolicy::class;

    public function definition(): array
    {
        return [
            'type' => WorkTask::TYPE_DOCUMENT_REVIEW,
            'label' => WorkTask::typeLabel(WorkTask::TYPE_DOCUMENT_REVIEW),
            'business_days' => 5,
            'warning_business_days' => 1,
            'is_active' => true,
        ];
    }
}
