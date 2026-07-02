<?php

namespace App\Services\Agenda;

use App\Enums\Agenda\AgendaView;
use App\Models\User;
use App\Services\Agenda\Builders\AgendaDayBuilder;
use App\Services\Agenda\Builders\AgendaMonthBuilder;
use App\Services\Agenda\Builders\AgendaWeekBuilder;
use App\Services\Agenda\DTO\AgendaDay;
use App\Services\Agenda\DTO\AgendaMonth;
use App\Services\Agenda\DTO\AgendaWeek;
use App\Services\Agenda\Filters\AgendaFilters;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class AgendaService
{
    public function __construct(
        private TimelineAggregatorService $timeline,
        private AgendaDayBuilder $dayBuilder,
        private AgendaWeekBuilder $weekBuilder,
        private AgendaMonthBuilder $monthBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $dashboard
     */
    public function build(User $user, AgendaFilters $filters, array $dashboard = []): AgendaDay|AgendaWeek|AgendaMonth
    {
        $date = $filters->from ?? now();

        $events = $this->timeline->eventsForUser($user, $dashboard);
        $events = $this->applyFilters($events, $filters);

        return match ($filters->view) {
            AgendaView::Day => $this->dayBuilder->build($date, $events),
            AgendaView::Week => $this->weekBuilder->build($date, $events),
            AgendaView::Month => $this->monthBuilder->build($date, $events),
        };
    }

    /**
     * @param  Collection<int, \App\Data\Dashboard\TimelineEvent>  $events
     * @return Collection<int, \App\Data\Dashboard\TimelineEvent>
     */
    private function applyFilters(Collection $events, AgendaFilters $filters): Collection
    {
        return $events
            ->when($filters->workspace, fn (Collection $items) => $items->filter(fn ($event) => $event->workspace === $filters->workspace))
            ->when($filters->priority, fn (Collection $items) => $items->filter(fn ($event) => $event->priority === $filters->priority))
            ->when($filters->status, fn (Collection $items) => $items->filter(fn ($event) => $event->status === $filters->status))
            ->when($filters->type, fn (Collection $items) => $items->filter(fn ($event) => $event->type === $filters->type))
            ->values();
    }
}
