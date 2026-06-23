<?php

namespace App\Services\Simulator;

use App\Enums\TypologyRecommendationStatus;
use App\Models\Contest;
use App\Models\TypologyAdequacyRule;

class TypologyRecommendationService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{status: string, typology: string|null, bedrooms: int|null, options: list<string>, warnings: list<string>, payload: array<string, mixed>}
     */
    public function recommend(array $input, ?Contest $contest = null): array
    {
        $members = $this->integer($input['household_members_count'] ?? null);

        if ($members === null || $members <= 0) {
            return [
                'status' => TypologyRecommendationStatus::InsufficientData->value,
                'typology' => null,
                'bedrooms' => null,
                'options' => [],
                'warnings' => ['Indique a dimensão do agregado para obter uma recomendação de tipologia.'],
                'payload' => ['source' => 'fallback'],
            ];
        }

        $bedrooms = match (true) {
            $members <= 2 => 1,
            $members === 3 => 2,
            $members === 4 => 3,
            default => 4,
        };
        $typology = 'T'.$bedrooms;
        $warnings = [];
        $source = 'fallback';

        $rule = $this->matchingRule($members, $contest);
        if ($rule instanceof TypologyAdequacyRule) {
            $typology = $rule->typology ?: $typology;
            $bedrooms = $rule->min_bedrooms ?: $bedrooms;
            $source = 'typology_adequacy_rules';
        }

        if (($input['has_accessibility_needs'] ?? false) === true) {
            $warnings[] = 'O agregado indica necessidades de acessibilidade; a adequação final depende das características do fogo.';
        }

        return [
            'status' => $warnings === []
                ? TypologyRecommendationStatus::Recommended->value
                : TypologyRecommendationStatus::RequiresReview->value,
            'typology' => $typology,
            'bedrooms' => $bedrooms,
            'options' => array_values(array_unique([$typology, 'T'.max($bedrooms - 1, 1), 'T'.($bedrooms + 1)])),
            'warnings' => $warnings,
            'payload' => [
                'source' => $source,
                'members' => $members,
                'contest_id' => $contest?->id,
            ],
        ];
    }

    private function matchingRule(int $members, ?Contest $contest): ?TypologyAdequacyRule
    {
        if (! $contest instanceof Contest) {
            return null;
        }

        return TypologyAdequacyRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($contest): void {
                $query->where('contest_id', $contest->id)
                    ->orWhere('program_id', $contest->program_id);
            })
            ->where(function ($query) use ($members): void {
                $query->whereNull('min_household_members')
                    ->orWhere('min_household_members', '<=', $members);
            })
            ->where(function ($query) use ($members): void {
                $query->whereNull('max_household_members')
                    ->orWhere('max_household_members', '>=', $members);
            })
            ->orderBy('priority_order')
            ->first();
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
