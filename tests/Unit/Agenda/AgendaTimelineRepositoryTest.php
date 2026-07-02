<?php

namespace Tests\Unit\Agenda;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Services\Agenda\AgendaTimelineRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AgendaTimelineRepositoryTest extends TestCase
{
    public function test_it_returns_events_for_day_week_month_and_workspace(): void
    {
        $events = new Collection([
            new TimelineEvent(
                id: 'today',
                type: TimelineType::Task,
                title: 'Hoje',
                datetime: Carbon::parse('2026-07-02 09:00:00'),
                priority: TimelinePriority::High,
                workspace: TimelineWorkspace::Operations,
            ),
            new TimelineEvent(
                id: 'same-week',
                type: TimelineType::Visit,
                title: 'Semana',
                datetime: Carbon::parse('2026-07-03 09:00:00'),
                priority: TimelinePriority::Medium,
                workspace: TimelineWorkspace::Patrimony,
            ),
            new TimelineEvent(
                id: 'other-month',
                type: TimelineType::Deadline,
                title: 'Outro mês',
                datetime: Carbon::parse('2026-08-01 09:00:00'),
                priority: TimelinePriority::Low,
                workspace: TimelineWorkspace::Contests,
            ),
        ]);

        $repository = new AgendaTimelineRepository();

        $this->assertSame(['today'], $repository->eventsOfDay($events, Carbon::parse('2026-07-02'))->pluck('id')->all());
        $this->assertSame(['same-week', 'today'], $repository->eventsOfWeek($events, Carbon::parse('2026-07-02'))->pluck('id')->all());
        $this->assertSame(['same-week', 'today'], $repository->eventsOfMonth($events, Carbon::parse('2026-07-02'))->pluck('id')->all());
        $this->assertSame(['same-week'], $repository->eventsByWorkspace($events, TimelineWorkspace::Patrimony)->pluck('id')->all());
    }

    public function test_it_filters_events_by_technician_metadata(): void
    {
        $events = new Collection([
            new TimelineEvent(
                id: 'assigned',
                type: TimelineType::Task,
                title: 'Atribuída',
                datetime: Carbon::parse('2026-07-02 09:00:00'),
                metadata: ['assigned_to' => 10],
            ),
            new TimelineEvent(
                id: 'other',
                type: TimelineType::Task,
                title: 'Outra',
                datetime: Carbon::parse('2026-07-02 10:00:00'),
                metadata: ['assigned_to' => 20],
            ),
        ]);

        $this->assertSame(
            ['assigned'],
            (new AgendaTimelineRepository())->eventsByTechnician($events, 10)->pluck('id')->all()
        );
    }
}
