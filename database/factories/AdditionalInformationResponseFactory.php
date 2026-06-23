<?php

namespace Database\Factories;

use App\Enums\AdditionalInformationResponseStatus;
use App\Models\AdditionalInformationRequest;
use App\Models\AdditionalInformationResponse;
use App\Models\Application;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdditionalInformationResponse> */
class AdditionalInformationResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'additional_information_request_id' => AdditionalInformationRequest::factory(),
            'complaint_id' => Complaint::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'response_text' => 'Resposta fictícia.',
            'submitted_at' => now(),
            'status' => AdditionalInformationResponseStatus::Submitted->value,
        ];
    }
}
