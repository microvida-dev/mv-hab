<?php

namespace App\Services\Search\Sources;

use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;
use Illuminate\Database\Eloquent\Builder;

class ReportSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(private readonly SearchResultAuthorizationService $authorization) {}

    public function key(): string
    {
        return 'report';
    }

    public function label(): string
    {
        return 'Relatórios';
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
        if (! $this->authorization->canAccess($user, 'backoffice.reports.definitions.show', 'reports.view')) {
            return [];
        }

        return array_values(ReportDefinition::query()
            ->select(['id', 'code', 'name', 'report_type', 'sensitivity_level', 'required_permission', 'is_active', 'created_at'])
            ->where('is_active', true)
            ->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('code', 'like', '%'.$term.'%');
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->filter(fn (ReportDefinition $report): bool => $report->required_permission === null
                || $user->hasPermission($report->required_permission))
            ->map(fn (ReportDefinition $report): array => [
                'type' => 'report',
                'group_key' => 'reports',
                'group_label' => $this->label(),
                'label' => $report->name,
                'subtitle' => 'Tipo: '.$this->enumLabel($report->report_type).' · Sensibilidade: '.$this->enumLabel($report->sensitivity_level),
                'route_name' => 'backoffice.reports.definitions.show',
                'route_parameters' => [$report->getKey()],
                'score' => 68,
            ])
            ->values()
            ->all());
    }
}
