<?php

namespace App\Services\Agenda;

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
        return $this->build(
            $user,
            new AgendaFilters(
                view: AgendaView::Day,
                from: now(),
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
     * @return Collection<int, \App\Data\Dashboard\TimelineEvent>
     */
    public function nextEvents(User $user, int $limit = 5, array $dashboard = []): Collection
    {
        return $this->timeline
            ->eventsForUser($user, $dashboard)
            ->sortBy(fn ($event): int => $event->datetime?->timestamp ?? PHP_INT_MAX)
            ->take($limit)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return Collection<int, \App\Data\Dashboard\TimelineEvent>
     */
    public function nextCriticalEvents(User $user, int $limit = 5, array $dashboard = []): Collection
    {
        return $this->timeline
            ->eventsForUser($user, $dashboard)
            ->filter(fn ($event): bool => $event->priority === TimelinePriority::Critical)
            ->sortBy(fn ($event): int => $event->datetime?->timestamp ?? PHP_INT_MAX)
            ->take($limit)
            ->values();
    }
}
