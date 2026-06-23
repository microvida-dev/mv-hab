<?php

namespace Database\Factories;

use App\Enums\AdditionalInformationRequestStatus;
use App\Models\AdditionalInformationRequest;
use App\Models\Application;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdditionalInformationRequest> */
class AdditionalInformationRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'request_number' => 'INF-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'status' => AdditionalInformationRequestStatus::Open->value,
            'subject' => 'Pedido complementar fictício',
            'message' => 'Mensagem fictícia.',
            'deadline_at' => now()->addDays(10),
            'issued_by' => User::factory(),
            'issued_at' => now(),
        ];
    }
}
