<?php

namespace App\Services\Contracts;

use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractTemplate;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class ContractClauseService
{
    /**
     * @return Collection<int, ContractClause>
     */
    public function resolve(ContractTemplate $template): Collection
    {
        $template->loadMissing('clauses');
        $clauses = $template->clauses
            ->filter(function (ContractClause $clause): bool {
                $pivot = $this->pivot($clause);

                return (bool) data_get($pivot, 'is_active') && $clause->status->value === 'active';
            });

        if ($clauses->isNotEmpty()) {
            return $clauses->values();
        }

        return ContractClause::query()
            ->active()
            ->where(function ($query) use ($template) {
                $query->where('contest_id', $template->contest_id)
                    ->orWhere(function ($builder) use ($template) {
                        $builder->whereNull('contest_id')->where('program_id', $template->program_id);
                    });
            })
            ->orderBy('sort_order')
            ->get();
    }

    public function snapshotForContract(Contract $contract, ContractTemplate $template): void
    {
        foreach ($this->resolve($template) as $index => $clause) {
            $pivot = $this->pivot($clause);

            $contract->clauses()->create([
                'contract_clause_id' => $clause->id,
                'code' => $clause->code,
                'title' => $clause->title,
                'body' => $clause->body,
                'category' => $clause->category,
                'sort_order' => data_get($pivot, 'sort_order') ?? $clause->sort_order ?? $index,
            ]);
        }
    }

    public function renderClausesHtml(Contract $contract): string
    {
        return $contract->clauses()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($clause) => '<section><h3>'.e($clause->title).'</h3><div>'.nl2br(e($clause->body)).'</div></section>')
            ->implode("\n");
    }

    private function pivot(ContractClause $clause): ?Pivot
    {
        $pivot = $clause->getRelationValue('pivot');

        return $pivot instanceof Pivot ? $pivot : null;
    }
}
