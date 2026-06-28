<?php

namespace App\Services\Search\Sources;

use App\Models\Program;
use App\Models\User;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;
use Illuminate\Database\Eloquent\Builder;

class ProgramSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(private readonly SearchResultAuthorizationService $authorization) {}

    public function key(): string
    {
        return 'program';
    }

    public function label(): string
    {
        return 'Programas';
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
        if (! $this->authorization->canAccess($user, 'admin.programs.show', 'programs.view')) {
            return [];
        }

        return array_values(Program::query()
            ->select(['id', 'name', 'slug', 'status', 'published_at', 'created_at'])
            ->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('slug', 'like', '%'.$term.'%');
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Program $program): array => [
                'type' => 'program',
                'group_key' => 'programs',
                'group_label' => $this->label(),
                'label' => $program->name,
                'subtitle' => 'Estado: '.$this->enumLabel($program->status),
                'route_name' => 'admin.programs.show',
                'route_parameters' => [$program->getKey()],
                'score' => 80,
            ])
            ->all());
    }
}
