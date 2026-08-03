<?php

namespace App\Services\Agenda;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Agenda\AgendaView;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Models\User;
use App\Services\Agenda\Builders\AgendaDayBuilder;
use App\Services\Agenda\Builders\AgendaMonthBuilder;
use App\Services\Agenda\Builders\AgendaWeekBuilder;
use App\Services\Agenda\Filters\AgendaFilters;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use Illuminate\Support\Collection;

final readonly class AgendaService
{
    public function __construct(
        private TimelineAggregatorService $timeline,
        private AgendaEventFilter $eventFilter,
        private AgendaDayBuilder $dayBuilder,
        private AgendaWeekBuilder $weekBuilder,
        private AgendaMonthBuilder $monthBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    public function build(User $user, AgendaFilters $filters, array $dashboard = []): array
    {
        $date = $filters->from ?? now();

        $timeline = $this->timeline->forUser($user, $dashboard);
        $events = $this->timeline->eventsForUser($user, $dashboard);
        $events = $this->eventFilter->apply($events, $filters);

        $agenda = match ($filters->view) {
            AgendaView::Day => $this->dayBuilder->build($date, $events),
            AgendaView::Week => $this->weekBuilder->build($date, $events),
            AgendaView::Month => $this->monthBuilder->build($date, $events),
        };

        return array_merge($agenda->toArray(), [
            'nextAction' => $timeline['nextAction'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    public function today(User $user, array $dashboard = []): array
    {
        $today = now();

        return $this->build(
            $user,
            new AgendaFilters(
                view: AgendaView::Day,
                from: $today->copy()->startOfDay(),
                to: $today->copy()->endOfDay(),
            ),
            $dashboard,
        );
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    public function week(User $user, array $dashboard = []): array
    {
        return $this->build(
            $user,
            new AgendaFilters(
                view: AgendaView::Week,
                from: now(),
            ),
            $dashboard,
        );
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    public function month(User $user, array $dashboard = []): array
    {
        return $this->build(
            $user,
            new AgendaFilters(
                view: AgendaView::Month,
                from: now(),
            ),
            $dashboard,
        );
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return Collection<int, TimelineEvent>
     */
    public function nextEvents(User $user, int $limit = 5, array $dashboard = []): Collection
    {
        return $this->timeline
            ->eventsForUser($user, $dashboard)
            ->sort(fn (TimelineEvent $left, TimelineEvent $right): int => $this->compareEvents($left, $right))
            ->take($limit)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return Collection<int, TimelineEvent>
     */
    public function nextCriticalEvents(User $user, int $limit = 5, array $dashboard = []): Collection
    {
        return $this->timeline
            ->eventsForUser($user, $dashboard)
            ->filter(fn (TimelineEvent $event): bool => $event->priority === TimelinePriority::Critical)
            ->sort(fn (TimelineEvent $left, TimelineEvent $right): int => $this->compareEvents($left, $right))
            ->take($limit)
            ->values();
    }

    private function compareEvents(TimelineEvent $left, TimelineEvent $right): int
    {
        $byDate = $this->eventTimestamp($left) <=> $this->eventTimestamp($right);

        if ($byDate !== 0) {
            return $byDate;
        }

        $byPriority = $left->priorityWeight() <=> $right->priorityWeight();

        return $byPriority !== 0
            ? $byPriority
            : strcasecmp($left->title, $right->title);
    }

    private function eventTimestamp(TimelineEvent $event): int
    {
        return $event->datetime === null
            ? PHP_INT_MAX
            : $event->datetime->getTimestamp();
    }
}
