<?php

namespace App\Services\Simulator;

use App\Enums\SimulationResultStatus;
use App\Enums\SimulationSessionStatus;
use App\Models\SimulationResult;
use App\Models\SimulationSession;
use Illuminate\Support\Facades\DB;

class SimulationResultService
{
    /**
     * @param  array{score: float, missing_fields: list<string>, complete: bool}  $completeness
     * @param  array{status: string, typology: string|null, bedrooms: int|null, options: list<string>, warnings: list<string>, payload: array<string, mixed>}  $typology
     * @param  array{status: string, rent_min: float|null, rent_max: float|null, effort_rate: float|null, warnings: list<string>, payload: array<string, mixed>}  $rent
     * @param  list<array<string, mixed>>  $impediments
     * @param  list<array<string, mixed>>  $recommendations
     */
    public function persist(
        SimulationSession $session,
        SimulationResultStatus $status,
        array $completeness,
        array $typology,
        array $rent,
        array $impediments,
        array $recommendations,
    ): SimulationResult {
        return DB::transaction(function () use ($session, $status, $completeness, $typology, $rent, $impediments, $recommendations): SimulationResult {
            $session->result()->delete();
            $session->impediments()->delete();
            $session->recommendedContests()->delete();

            $blockingCount = count(array_filter($impediments, static fn (array $item): bool => (bool) ($item['is_blocking'] ?? false)));

            $result = $session->result()->create([
                'result_status' => $status,
                'eligibility_summary' => $this->summary($status, $blockingCount, $completeness['score']),
                'eligibility_score' => max($completeness['score'] - ($blockingCount * 25), 0),
                'eligibility_payload' => [
                    'completeness' => $completeness,
                    'indicative' => true,
                ],
                'typology_status' => $typology['status'],
                'recommended_typology' => $typology['typology'],
                'recommended_bedrooms' => $typology['bedrooms'],
                'typology_payload' => $typology,
                'rent_status' => $rent['status'],
                'estimated_rent_min' => $rent['rent_min'],
                'estimated_rent_max' => $rent['rent_max'],
                'estimated_effort_rate' => $rent['effort_rate'],
                'rent_payload' => $rent,
                'recommendations_payload' => ['contests' => $recommendations],
                'impediments_count' => count($impediments),
                'blocking_impediments_count' => $blockingCount,
                'recommended_contests_count' => count($recommendations),
            ]);

            foreach ($impediments as $impediment) {
                $session->impediments()->create($impediment + [
                    'simulation_result_id' => $result->id,
                ]);
            }

            foreach ($recommendations as $recommendation) {
                $session->recommendedContests()->create($recommendation + [
                    'simulation_result_id' => $result->id,
                ]);
            }

            $session->forceFill([
                'status' => SimulationSessionStatus::Completed,
                'result_status' => $status,
                'completed_at' => now(),
            ])->save();

            return $result->fresh(['impediments', 'recommendedContests.contest.program']) ?? $result;
        });
    }

    private function summary(SimulationResultStatus $status, int $blockingCount, float $score): string
    {
        return match ($status) {
            SimulationResultStatus::LikelyEligible => 'A simulação não encontrou impedimentos bloqueantes com os dados indicados.',
            SimulationResultStatus::LikelyIneligible => 'A simulação identificou '.$blockingCount.' impedimento(s) que podem impedir a candidatura.',
            SimulationResultStatus::RequiresReview => 'A simulação identificou dados que exigem validação pelos serviços municipais.',
            SimulationResultStatus::InsufficientData => 'A simulação tem apenas '.round($score).'% dos dados mínimos necessários.',
            SimulationResultStatus::NoMatchingContest => 'Não foram encontrados concursos públicos compatíveis com os dados indicados.',
        };
    }
}
