<?php

namespace Database\Factories;

use App\Enums\DataSubjectRequestActionType;
use App\Models\DataSubjectRequest;
use App\Models\DataSubjectRequestAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DataSubjectRequestAction> */
class DataSubjectRequestActionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'data_subject_request_id' => DataSubjectRequest::factory(),
            'action_type' => DataSubjectRequestActionType::DataSearch->value,
            'status' => 'completed',
            'description' => 'Ação RGPD demo.',
            'performed_at' => now(),
        ];
    }
}
