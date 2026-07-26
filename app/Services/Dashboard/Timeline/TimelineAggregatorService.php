<?php

namespace App\Services\Dashboard\Timeline;

use App\Data\Dashboard\TimelineEvent;
use App\Models\User;
use Illuminate\Support\Collection;

class TimelineAggregatorService
{
    /**
     * @param  array<int, TimelineProviderInterface>  $providers
     */
    public function __construct(
        private readonly array $providers = [],
        private readonly ?NextActionResolver $nextActionResolver = null,
        private readonly ?TimelineMetricsService $metricsService = null,
    ) {}

    /**
     * @param  array<string, mixed>  $dashboard
     * @return Collection<int, TimelineEvent>
     */
    public function eventsForUser(User $user, array $dashboard = []): Collection
    {
        return collect($this->providers)
            ->flatMap(fn (TimelineProviderInterface $provider): array => $provider->forUser($user, $dashboard))
            ->unique(fn (TimelineEvent $event): string => $event->id)
            ->sort(fn (TimelineEvent $left, TimelineEvent $right): int => $this->compareEvents($left, $right))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    public function forUser(User $user, array $dashboard = []): array
    {
        $events = $this->eventsForUser($user, $dashboard);

        return [
            'nextAction' => $this->nextAction($events),
            'metrics' => $this->metrics($events),
            'items' => $events->take(12)->map->toArray()->values()->all(),
            'groups' => $this->groups($events->take(24)),
        ];
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return array<string, mixed>
     */
    private function metrics(Collection $events): array
    {
        $metrics = $this->metricsService ?? new TimelineMetricsService;

        return $metrics->calculate($events);
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return array<string, mixed>|null
     */
    private function nextAction(Collection $events): ?array
    {
        $resolver = $this->nextActionResolver ?? new NextActionResolver;

        return $resolver->resolve($events)?->toArray();
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return array<int, array<string, mixed>>
     */
    private function groups(Collection $events): array
    {
        return $events
            ->sortBy(fn (TimelineEvent $event): string => $event->datetime?->toIso8601String() ?? '9999-12-31T23:59:59')
            ->groupBy(fn (TimelineEvent $event): string => $event->datetime?->isToday()
                ? 'Hoje'
                : ($event->datetime?->isTomorrow() ? 'Amanhã' : ($event->datetime?->format('d/m/Y') ?? 'Sem data')))
            ->map(fn (Collection $items, string $label): array => [
                'label' => $label,
                'items' => $items
                    ->sort(fn (TimelineEvent $left, TimelineEvent $right): int => $this->compareEvents($left, $right))
                    ->map->toArray()
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    private function compareEvents(TimelineEvent $left, TimelineEvent $right): int
    {
        $comparisons = [
            $left->priorityWeight() <=> $right->priorityWeight(),
            $this->eventDate($left) <=> $this->eventDate($right),
            $this->workspace($left) <=> $this->workspace($right),
            $left->type->value <=> $right->type->value,
            strcasecmp($left->title, $right->title),
        ];

        foreach ($comparisons as $comparison) {
            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    private function eventDate(TimelineEvent $event): string
    {
        return $event->datetime?->toIso8601String() ?? '9999-12-31T23:59:59';
    }

    private function workspace(TimelineEvent $event): string
    {
        if ($event->workspace === null) {
            return '';
        }

        return $event->workspace->value;
    }
}
