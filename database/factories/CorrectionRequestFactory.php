<?php

namespace Database\Factories;

use App\Enums\CorrectionRequestStatus;
use App\Models\AdministrativeProcess;
use App\Models\CorrectionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorrectionRequest>
 */
class CorrectionRequestFactory extends Factory
{
    public function definition(): array
    {
        $process = AdministrativeProcess::factory()->create();

        return [
            'administrative_process_id' => $process->id,
            'application_id' => $process->application_id,
            'user_id' => $process->user_id,
            'request_number' => 'APR-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'status' => CorrectionRequestStatus::Draft->value,
            'subject' => 'Pedido de aperfeiçoamento fictício',
            'message' => 'Mensagem fictícia para teste.',
            'instructions' => 'Responda através da área reservada.',
            'response_deadline_at' => now()->addDays(10),
            'candidate_visible' => false,
        ];
    }
}
