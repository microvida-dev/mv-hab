<?php

namespace App\Services\Eligibility;

use App\Models\Contest;
use App\Models\EligibilityRuleSet;
use App\Models\Program;
use Carbon\CarbonInterface;
use RuntimeException;

class EligibilityRuleSetResolver
{
    public function resolve(?Program $program = null, ?Contest $contest = null): ?EligibilityRuleSet
    {
        return $this->resolveAt(now(), $program, $contest);
    }

    public function resolveAt(
        CarbonInterface $referenceDate,
        ?Program $program = null,
        ?Contest $contest = null,
    ): ?EligibilityRuleSet {
        $regulatoryProfileId = null;

        if ($contest instanceof Contest && $contest->regulatory_profile_id !== null) {
            $regulatoryProfileId = $contest->regulatory_profile_id;
        } elseif ($program instanceof Program) {
            $regulatoryProfileId = $program->regulatory_profile_id;
        }

        if ($contest) {
            $contestRuleSet = EligibilityRuleSet::query()
                ->activeAt($referenceDate)
                ->when(
                    $regulatoryProfileId !== null,
                    fn ($query) => $query->where('regulatory_profile_id', $regulatoryProfileId),
                )
                ->where('contest_id', $contest->id)
                ->latest('starts_at')
                ->latest('id')
                ->first();

            if ($contestRuleSet) {
                return $contestRuleSet;
            }

            $program ??= $contest->program;
        }

        if (! $program) {
            return null;
        }

        return EligibilityRuleSet::query()
            ->activeAt($referenceDate)
            ->when(
                $regulatoryProfileId !== null,
                fn ($query) => $query->where('regulatory_profile_id', $regulatoryProfileId),
            )
            ->where('program_id', $program->id)
            ->whereNull('contest_id')
            ->orderByDesc('is_default')
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    public function resolveOrFail(?Program $program = null, ?Contest $contest = null): EligibilityRuleSet
    {
        return $this->resolve($program, $contest)
            ?? throw new RuntimeException('Não existe um conjunto de regras de elegibilidade ativo para o contexto selecionado.');
    }
}
