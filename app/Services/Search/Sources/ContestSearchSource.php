<?php

namespace App\Services\Search\Sources;

use App\Models\Contest;
use App\Models\User;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class ContestSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(private readonly SearchResultAuthorizationService $authorization) {}

    public function key(): string
    {
        return 'contest';
    }

    public function label(): string
    {
        return 'Concursos';
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
        $routeName = Route::has('backoffice.cases.contests.show')
            ? 'backoffice.cases.contests.show'
            : 'admin.contests.show';

        if (! $this->authorization->canAccess($user, $routeName, 'contests.view')) {
            return [];
        }

        return array_values(Contest::query()
            ->select(['id', 'code', 'title', 'status', 'program_id', 'published_at', 'created_at'])
            ->with(['program:id,name'])
            ->where(function (Builder $query) use ($term): void {
                $query->where('title', 'like', '%'.$term.'%')
                    ->orWhere('code', 'like', '%'.$term.'%')
                    ->orWhere('slug', 'like', '%'.$term.'%');
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Contest $contest): array => [
                'type' => 'contest',
                'group_key' => 'contests',
                'group_label' => $this->label(),
                'label' => $contest->title,
                'subtitle' => trim(($contest->code ?: 'Sem código').' · Estado: '.$this->enumLabel($contest->status).' · '.($this->relatedAttribute($contest, 'program', 'name') ?? 'Sem programa')),
                'route_name' => $routeName,
                'route_parameters' => [$contest->getKey()],
                'score' => 82,
            ])
            ->all());
    }
}
