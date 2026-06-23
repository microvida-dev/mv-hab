<?php

namespace App\Services\Contracts;

use App\Enums\RentRuleSetStatus;
use App\Models\Allocation;
use App\Models\RentRuleSet;
use Illuminate\Validation\ValidationException;

class RentRuleSetResolver
{
    public function resolve(Allocation $allocation, ?RentRuleSet $explicit = null): RentRuleSet
    {
        if ($explicit !== null) {
            if ($explicit->status !== RentRuleSetStatus::Active) {
                throw ValidationException::withMessages(['rent_rule_set_id' => 'O conjunto de regras indicado não está ativo.']);
            }

            return $explicit;
        }

        $ruleSet = RentRuleSet::query()
            ->active()
            ->where(function ($query) use ($allocation) {
                $query->where('contest_id', $allocation->contest_id)
                    ->orWhere(function ($builder) use ($allocation) {
                        $builder->whereNull('contest_id')
                            ->where('program_id', $allocation->program_id);
                    });
            })
            ->orderByRaw('case when contest_id is null then 1 else 0 end')
            ->latest('id')
            ->first();

        if (! $ruleSet) {
            throw ValidationException::withMessages(['rent_rule_set_id' => 'Não existe regra de renda ativa para o programa ou concurso.']);
        }

        return $ruleSet;
    }
}
