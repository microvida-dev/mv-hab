<?php

namespace App\Services\Simulator;

use App\Enums\SimulationContestMatchStatus;
use App\Models\Contest;
use Illuminate\Database\Eloquent\Collection;

class ContestRecommendationService
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array{status: string, typology: string|null, bedrooms: int|null, options: list<string>, warnings: list<string>, payload: array<string, mixed>}  $typology
     * @param  array{status: string, rent_min: float|null, rent_max: float|null, effort_rate: float|null, warnings: list<string>, payload: array<string, mixed>}  $rent
     * @return list<array<string, mixed>>
     */
    public function recommend(array $input, array $typology, array $rent, ?Contest $selectedContest = null, int $limit = 5): array
    {
        $contests = $this->candidateContests($selectedContest, $limit);
        $recommendations = [];

        foreach ($contests as $contest) {
            $score = $contest->isOpenForApplications() ? 40.0 : 20.0;
            $reasons = [];
            $warnings = [];

            if ($typology['typology'] !== null && $this->contestHasTypology($contest, $typology['typology'])) {
                $score += 30.0;
                $reasons[] = 'Existem fogos compatíveis com a tipologia recomendada.';
            } elseif ($typology['typology'] !== null) {
                $warnings[] = 'Não foram encontrados fogos da tipologia recomendada no concurso.';
            }

            if ($rent['rent_max'] !== null && $this->contestHasRentWithin($contest, (float) $rent['rent_max'])) {
                $score += 20.0;
                $reasons[] = 'Existem rendas dentro da estimativa máxima calculada.';
            }

            $preferredTypologies = is_array($input['preferred_typologies'] ?? null) ? $input['preferred_typologies'] : [];
            if ($preferredTypologies !== [] && in_array($typology['typology'], $preferredTypologies, true)) {
                $score += 10.0;
                $reasons[] = 'A tipologia recomendada coincide com a preferência indicada.';
            }

            $recommendations[] = [
                'program_id' => $contest->program_id,
                'contest_id' => $contest->id,
                'match_status' => $this->matchStatus($score)->value,
                'match_score' => min($score, 100.0),
                'public_status' => $contest->publicPhase(),
                'opens_at' => $contest->opens_at,
                'closes_at' => $contest->closes_at,
                'recommended_typologies' => $typology['typology'] === null ? [] : [$typology['typology']],
                'rent_min' => $this->rentMin($contest),
                'rent_max' => $this->rentMax($contest),
                'reasons' => $reasons !== [] ? $reasons : ['Concurso público disponível para consulta.'],
                'warnings' => $warnings,
                'cta_url' => route('public.contests.show', ['slug' => $contest->slug]),
            ];
        }

        usort($recommendations, static fn (array $a, array $b): int => ($b['match_score'] <=> $a['match_score']));

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * @return Collection<int, Contest>
     */
    private function candidateContests(?Contest $selectedContest, int $limit): Collection
    {
        if ($selectedContest instanceof Contest) {
            return new Collection([$selectedContest->loadMissing(['program', 'contestHousingUnits'])]);
        }

        return Contest::query()
            ->publiclyVisible()
            ->with(['program', 'contestHousingUnits'])
            ->latest('published_at')
            ->limit($limit * 2)
            ->get();
    }

    private function contestHasTypology(Contest $contest, string $typology): bool
    {
        return $contest->contestHousingUnits()
            ->where('typology', $typology)
            ->exists();
    }

    private function contestHasRentWithin(Contest $contest, float $maxRent): bool
    {
        return $contest->contestHousingUnits()
            ->whereNotNull('monthly_rent')
            ->where('monthly_rent', '<=', $maxRent)
            ->exists();
    }

    private function rentMin(Contest $contest): ?float
    {
        $value = $contest->contestHousingUnits()
            ->whereNotNull('monthly_rent')
            ->min('monthly_rent');

        return $value !== null ? (float) $value : null;
    }

    private function rentMax(Contest $contest): ?float
    {
        $value = $contest->contestHousingUnits()
            ->whereNotNull('monthly_rent')
            ->max('monthly_rent');

        return $value !== null ? (float) $value : null;
    }

    private function matchStatus(float $score): SimulationContestMatchStatus
    {
        return match (true) {
            $score >= 80 => SimulationContestMatchStatus::Strong,
            $score >= 50 => SimulationContestMatchStatus::Possible,
            $score >= 30 => SimulationContestMatchStatus::RequiresReview,
            default => SimulationContestMatchStatus::NotRecommended,
        };
    }
}
