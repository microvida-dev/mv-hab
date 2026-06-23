<?php

namespace App\Services\Eligibility;

use App\Models\Contest;
use App\Models\EligibilityRuleSet;
use App\Models\Program;
use RuntimeException;

class EligibilityRuleSetResolver
{
    public function resolve(?Program $program = null, ?Contest $contest = null): ?EligibilityRuleSet
    {
        if ($contest) {
            $contestRuleSet = EligibilityRuleSet::query()
                ->active()
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
            ->active()
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
