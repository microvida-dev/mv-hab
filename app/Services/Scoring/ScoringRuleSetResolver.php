<?php

namespace App\Services\Scoring;

use App\Models\Contest;
use App\Models\Program;
use App\Models\ScoringRuleSet;
use RuntimeException;

class ScoringRuleSetResolver
{
    public function resolve(?Program $program = null, ?Contest $contest = null): ?ScoringRuleSet
    {
        if ($contest) {
            $contestRuleSet = ScoringRuleSet::query()
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

        return ScoringRuleSet::query()
            ->active()
            ->where('program_id', $program->id)
            ->whereNull('contest_id')
            ->orderByDesc('is_default')
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    public function resolveOrFail(?Program $program = null, ?Contest $contest = null): ScoringRuleSet
    {
        return $this->resolve($program, $contest)
            ?? throw new RuntimeException('Não existe uma matriz de classificação ativa para o contexto selecionado.');
    }
}
