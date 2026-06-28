<?php

namespace App\Services\Search\Sources;

use App\Models\Application;
use App\Models\User;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class ApplicationSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(private readonly SearchResultAuthorizationService $authorization) {}

    public function key(): string
    {
        return 'application';
    }

    public function label(): string
    {
        return 'Candidaturas';
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
        $routeName = Route::has('backoffice.cases.applications.show')
            ? 'backoffice.cases.applications.show'
            : 'backoffice.applications.show';

        if (! $this->authorization->canAccess($user, $routeName, 'applications.view')) {
            return [];
        }

        return array_values(Application::query()
            ->select(['id', 'public_id', 'application_number', 'status', 'contest_id', 'program_id', 'submitted_at', 'created_at'])
            ->with(['contest:id,title', 'program:id,name'])
            ->where(function (Builder $query) use ($term): void {
                $query->where('application_number', 'like', '%'.$term.'%')
                    ->orWhere('public_id', 'like', '%'.$term.'%');
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Application $application): array => [
                'type' => 'application',
                'group_key' => 'applications',
                'group_label' => $this->label(),
                'label' => 'Candidatura '.($application->application_number ?: $application->public_id),
                'subtitle' => trim('Estado: '.$this->enumLabel($application->status).' · '.($this->relatedAttribute($application, 'contest', 'title') ?? $this->relatedAttribute($application, 'program', 'name') ?? 'Sem concurso associado')),
                'route_name' => $routeName,
                'route_parameters' => [$application->getRouteKey()],
                'score' => 90,
            ])
            ->all());
    }
}
