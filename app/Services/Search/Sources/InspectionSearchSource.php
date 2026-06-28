<?php

namespace App\Services\Search\Sources;

use App\Models\PropertyInspection;
use App\Models\User;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class InspectionSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(private readonly SearchResultAuthorizationService $authorization) {}

    public function key(): string
    {
        return 'inspection';
    }

    public function label(): string
    {
        return 'Vistorias';
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
        $routeName = Route::has('backoffice.cases.inspections.show')
            ? 'backoffice.cases.inspections.show'
            : 'backoffice.inspections.show';

        if (! $this->authorization->canAccess($user, $routeName, 'inspections.view')) {
            return [];
        }

        return array_values(PropertyInspection::query()
            ->select(['id', 'inspection_number', 'inspection_type', 'status', 'housing_unit_id', 'scheduled_for', 'created_at'])
            ->with(['housingUnit:id,code,public_title,typology'])
            ->where(function (Builder $query) use ($term): void {
                $query->where('inspection_number', 'like', '%'.$term.'%')
                    ->orWhereHas('housingUnit', function (Builder $housing) use ($term): void {
                        $housing->where('code', 'like', '%'.$term.'%')
                            ->orWhere('public_title', 'like', '%'.$term.'%');
                    });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (PropertyInspection $inspection): array => [
                'type' => 'inspection',
                'group_key' => 'inspections',
                'group_label' => $this->label(),
                'label' => 'Vistoria '.$inspection->inspection_number,
                'subtitle' => 'Estado: '.$this->enumLabel($inspection->status).' · '.($this->relatedDisplayTitle($inspection, 'housingUnit') ?? 'Imóvel associado'),
                'route_name' => $routeName,
                'route_parameters' => [$inspection->getKey()],
                'score' => 70,
            ])
            ->all());
    }
}
