<?php

namespace App\Services\Contracts;

use App\Enums\ContractTemplateStatus;
use App\Models\Allocation;
use App\Models\ContractTemplate;
use Illuminate\Validation\ValidationException;

class ContractTemplateResolver
{
    public function resolve(Allocation $allocation, ?ContractTemplate $explicit = null): ContractTemplate
    {
        if ($explicit !== null) {
            if ($explicit->status !== ContractTemplateStatus::Active) {
                throw ValidationException::withMessages(['contract_template_id' => 'A minuta indicada não está ativa.']);
            }

            return $explicit;
        }

        $template = ContractTemplate::query()
            ->active()
            ->where(function ($query) use ($allocation) {
                $query->where('contest_id', $allocation->contest_id)
                    ->orWhere(function ($builder) use ($allocation) {
                        $builder->whereNull('contest_id')
                            ->where('program_id', $allocation->program_id);
                    });
            })
            ->orderByRaw('case when contest_id is null then 1 else 0 end')
            ->latest('version_number')
            ->latest('id')
            ->first();

        if (! $template) {
            throw ValidationException::withMessages(['contract_template_id' => 'Não existe minuta contratual ativa para o programa ou concurso.']);
        }

        return $template;
    }
}
