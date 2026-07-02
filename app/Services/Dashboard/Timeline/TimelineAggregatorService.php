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
    ) {}

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    public function forUser(User $user, array $dashboard = []): array
    {
        $events = collect($this->providers)
            ->flatMap(fn (TimelineProviderInterface $provider): array => $provider->forUser($user, $dashboard))
            ->filter(fn (mixed $event): bool => $event instanceof TimelineEvent)
            ->unique(fn (TimelineEvent $event): string => $event->id)
            ->sortBy([
                fn (TimelineEvent $event): int => $event->priorityWeight(),
                fn (TimelineEvent $event): string => $event->datetime?->toIso8601String() ?? '9999-12-31T23:59:59',
                fn (TimelineEvent $event): string => $event->workspace ?? '',
                fn (TimelineEvent $event): string => $event->type,
            ])
            ->values();

        return [
            'nextAction' => $this->nextAction($events),
            'items' => $events->take(12)->map->toArray()->values()->all(),
            'groups' => $this->groups($events->take(24)),
        ];
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return array<string, mixed>|null
     */
    private function nextAction(Collection $events): ?array
    {
        $resolver = $this->nextActionResolver ?? new NextActionResolver();

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
                    ->sortBy([
                        fn (TimelineEvent $event): int => $event->priorityWeight(),
                        fn (TimelineEvent $event): string => $event->datetime?->toIso8601String() ?? '9999-12-31T23:59:59',
                    ])
                    ->map->toArray()
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
