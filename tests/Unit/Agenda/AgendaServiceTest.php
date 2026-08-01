<?php

namespace Tests\Unit\Agenda;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineStatus;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\User;
use App\Services\Agenda\AgendaEventFilter;
use App\Services\Agenda\AgendaService;
use App\Services\Agenda\Builders\AgendaDayBuilder;
use App\Services\Agenda\Builders\AgendaMonthBuilder;
use App\Services\Agenda\Builders\AgendaWeekBuilder;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AgendaServiceTest extends TestCase
{
    public function test_today_returns_day_structure(): void
    {
        $service = $this->serviceWithEvents($this->events());

        $agenda = $service->today(new User);

        $this->assertArrayHasKey('date', $agenda);
        $this->assertArrayHasKey('events', $agenda);
    }

    public function test_today_includes_events_created_earlier_on_the_same_day(): void
    {
        Carbon::setTestNow('2026-07-03 12:00:00');

        try {
            $service = $this->serviceWithEvents([
                new TimelineEvent(
                    id: 'earlier-today',
                    title: 'Submissão formal recebida',
                    workspace: TimelineWorkspace::Applications,
                    type: TimelineType::CorrectionResponse,
                    priority: TimelinePriority::High,
                    status: TimelineStatus::Completed,
                    datetime: Carbon::parse(
                        '2026-07-03 09:30:00',
                    ),
                ),
                new TimelineEvent(
                    id: 'tomorrow',
                    title: 'Evento de amanhã',
                    workspace: TimelineWorkspace::Applications,
                    type: TimelineType::CorrectionRequest,
                    priority: TimelinePriority::Medium,
                    status: TimelineStatus::Pending,
                    datetime: Carbon::parse(
                        '2026-07-04 09:30:00',
                    ),
                ),
            ]);

            $agenda = $service->today(new User);
            $events = $agenda['events'] ?? [];

            $this->assertIsArray($events);
            $this->assertSame(
                ['earlier-today'],
                array_column($events, 'id'),
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_week_returns_week_structure(): void
    {
        $service = $this->serviceWithEvents($this->events());

        $agenda = $service->week(new User);

        $this->assertArrayHasKey('start', $agenda);
        $this->assertArrayHasKey('end', $agenda);
        $this->assertArrayHasKey('days', $agenda);
    }

    public function test_month_returns_month_structure(): void
    {
        $service = $this->serviceWithEvents($this->events());

        $agenda = $service->month(new User);

        $this->assertArrayHasKey('month', $agenda);
        $this->assertArrayHasKey('weeks', $agenda);
    }

    public function test_next_events_are_sorted(): void
    {
        $service = $this->serviceWithEvents([
            ...$this->events(),
            new TimelineEvent(
                id: 'without-date',
                title: 'Evento sem data',
                type: TimelineType::Task,
            ),
        ]);

        $events = $service->nextEvents(new User);

        $this->assertSame(
            ['critical', 'normal', 'without-date'],
            $events->pluck('id')->all()
        );
    }

    public function test_next_critical_events_only_returns_critical(): void
    {
        $service = $this->serviceWithEvents($this->events());

        $events = $service->nextCriticalEvents(new User);

        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertInstanceOf(TimelineEvent::class, $event);
        $this->assertSame('critical', $event->id);
    }

    /**
     * @param  array<int,TimelineEvent>  $events
     */
    private function serviceWithEvents(array $events): AgendaService
    {
        $timeline = new class($events) extends TimelineAggregatorService
        {
            /**
             * @param  array<int, TimelineEvent>  $events
             */
            public function __construct(private readonly array $events) {}

            /**
             * @param  array<string, mixed>  $dashboard
             * @return Collection<int, TimelineEvent>
             */
            public function eventsForUser(User $user, array $dashboard = []): Collection
            {
                return collect($this->events);
            }

            /**
             * @param  array<string, mixed>  $dashboard
             * @return array<string, mixed>
             */
            public function forUser(User $user, array $dashboard = []): array
            {
                return ['nextAction' => null];
            }
        };

        return new AgendaService(
            $timeline,
            new AgendaEventFilter,
            new AgendaDayBuilder,
            new AgendaWeekBuilder,
            new AgendaMonthBuilder,
        );
    }

    /**
     * @return array<int,TimelineEvent>
     */
    private function events(): array
    {
        return [
            new TimelineEvent(
                id: 'normal',
                title: 'Evento Normal',
                workspace: TimelineWorkspace::Operations,
                type: TimelineType::Task,
                priority: TimelinePriority::Medium,
                status: TimelineStatus::Pending,
                datetime: Carbon::parse('2026-07-03 09:00'),
            ),

            new TimelineEvent(
                id: 'critical',
                title: 'Evento Crítico',
                workspace: TimelineWorkspace::Operations,
                type: TimelineType::Task,
                priority: TimelinePriority::Critical,
                status: TimelineStatus::Pending,
                datetime: Carbon::parse('2026-07-02 09:00'),
            ),
        ];
    }
}
