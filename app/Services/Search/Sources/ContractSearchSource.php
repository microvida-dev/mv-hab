<?php

namespace App\Services\Search\Sources;

use App\Models\Contract;
use App\Models\User;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;
use Illuminate\Database\Eloquent\Builder;

class ContractSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(private readonly SearchResultAuthorizationService $authorization) {}

    public function key(): string
    {
        return 'contract';
    }

    public function label(): string
    {
        return 'Contratos';
    }

    public function minimumCharacters(): int
    {
        return 2;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(User $user, string $term, int $limit): array
    {
        if (! $this->authorization->canAccess($user, 'backoffice.contracts.leases.show', 'contracts.view')) {
            return [];
        }

        return array_values(Contract::query()
            ->select(['id', 'contract_number', 'status', 'housing_unit_id', 'program_id', 'contest_id', 'created_at'])
            ->with(['housingUnit:id,code,public_title,typology,parish', 'program:id,name', 'contest:id,title'])
            ->where(function (Builder $query) use ($term): void {
                $query->where('contract_number', 'like', '%'.$term.'%')
                    ->orWhereHas('housingUnit', function (Builder $housing) use ($term): void {
                        $housing->where('code', 'like', '%'.$term.'%')
                            ->orWhere('public_title', 'like', '%'.$term.'%');
                    });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Contract $contract): array => [
                'type' => 'contract',
                'group_key' => 'contracts',
                'group_label' => $this->label(),
                'label' => 'Contrato '.($contract->contract_number ?: '#'.$contract->getKey()),
                'subtitle' => trim('Estado: '.$this->enumLabel($contract->status).' · '.($this->relatedDisplayTitle($contract, 'housingUnit') ?? $this->relatedAttribute($contract, 'program', 'name') ?? $this->relatedAttribute($contract, 'contest', 'title') ?? 'Sem referência pública')),
                'route_name' => 'backoffice.contracts.leases.show',
                'route_parameters' => [$contract->getKey()],
                'score' => 76,
            ])
            ->all());
    }
}
