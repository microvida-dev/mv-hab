<?php

namespace App\Services\Allocation;

use App\Models\AllocationRuleSet;
use App\Models\DefinitiveList;
use Illuminate\Validation\ValidationException;

class AllocationRuleSetResolver
{
    public function resolveFor(DefinitiveList $list, ?int $ruleSetId = null): AllocationRuleSet
    {
        if ($ruleSetId) {
            return AllocationRuleSet::query()->active()->findOrFail($ruleSetId);
        }

        $contestRule = AllocationRuleSet::query()
            ->active()
            ->where('contest_id', $list->contest_id)
            ->latest()
            ->first();

        if ($contestRule) {
            return $contestRule;
        }

        $programRule = AllocationRuleSet::query()
            ->active()
            ->where('program_id', $list->program_id)
            ->whereNull('contest_id')
            ->latest()
            ->first();

        if (! $programRule) {
            throw ValidationException::withMessages(['allocation_rule_set_id' => 'Não existe regra de atribuição ativa para esta lista.']);
        }

        return $programRule;
    }
}
