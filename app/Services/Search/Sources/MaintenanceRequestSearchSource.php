<?php

namespace App\Services\Search\Sources;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceRequestSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(private readonly SearchResultAuthorizationService $authorization) {}

    public function key(): string
    {
        return 'maintenance_request';
    }

    public function label(): string
    {
        return 'Manutenção';
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
        if (! $this->authorization->canAccess($user, 'backoffice.maintenance.requests.show', 'maintenance_requests.view')) {
            return [];
        }

        return array_values(MaintenanceRequest::query()
            ->select(['id', 'request_number', 'status', 'urgency', 'priority', 'housing_unit_id', 'reported_at', 'created_at'])
            ->with(['housingUnit:id,code,public_title,typology'])
            ->where(function (Builder $query) use ($term): void {
                $query->where('request_number', 'like', '%'.$term.'%')
                    ->orWhere('title', 'like', '%'.$term.'%')
                    ->orWhereHas('housingUnit', function (Builder $housing) use ($term): void {
                        $housing->where('code', 'like', '%'.$term.'%')
                            ->orWhere('public_title', 'like', '%'.$term.'%');
                    });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (MaintenanceRequest $request): array => [
                'type' => 'maintenance_request',
                'group_key' => 'maintenance',
                'group_label' => $this->label(),
                'label' => 'Pedido '.$request->request_number,
                'subtitle' => 'Estado: '.$this->enumLabel($request->status).' · Urgência: '.$this->enumLabel($request->urgency),
                'route_name' => 'backoffice.maintenance.requests.show',
                'route_parameters' => [$request->getKey()],
                'score' => 72,
            ])
            ->all());
    }
}
