<?php

namespace Database\Factories;

use App\Enums\AdministrativeDecisionResult;
use App\Enums\AdministrativeDecisionStatus;
use App\Enums\AdministrativeDecisionType;
use App\Models\AdministrativeDecision;
use App\Models\AdministrativeProcess;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdministrativeDecision>
 */
class AdministrativeDecisionFactory extends Factory
{
    public function definition(): array
    {
        $process = AdministrativeProcess::factory()->create();

        return [
            'administrative_process_id' => $process->id,
            'application_id' => $process->application_id,
            'decision_type' => AdministrativeDecisionType::AdmissionForScoring->value,
            'decision_result' => AdministrativeDecisionResult::AdmittedForScoring->value,
            'status' => AdministrativeDecisionStatus::Proposed->value,
            'summary' => 'Proposta fictícia de admissão.',
            'grounds' => 'Fundamentação administrativa fictícia.',
            'decided_by' => User::factory(),
            'decided_at' => now(),
            'candidate_visible' => false,
        ];
    }
}
