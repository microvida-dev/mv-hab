<?php

namespace Tests\Unit\Agenda;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Services\Agenda\Builders\AgendaDayBuilder;
use App\Services\Agenda\Builders\AgendaMonthBuilder;
use App\Services\Agenda\Builders\AgendaWeekBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AgendaBuildersTest extends TestCase
{
    public function test_day_builder_builds_day_with_events_and_statistics(): void
    {
        Carbon::setTestNow('2026-07-02 08:00:00');

        $day = (new AgendaDayBuilder())->build(Carbon::parse('2026-07-02'), new Collection([
            new TimelineEvent(
                id: 'today',
                type: TimelineType::Task,
                title: 'Hoje',
                datetime: Carbon::parse('2026-07-02 09:00:00'),
                priority: TimelinePriority::Critical,
            ),
            new TimelineEvent(
                id: 'tomorrow',
                type: TimelineType::Visit,
                title: 'Amanhã',
                datetime: Carbon::parse('2026-07-03 09:00:00'),
            ),
        ]));

        $this->assertSame('2026-07-02', $day->date->toDateString());
        $this->assertCount(1, $day->events);
        $this->assertSame('today', $day->events[0]->id);
        $this->assertSame(1, $day->statistics['critical']);
    }

    public function test_week_builder_builds_seven_days(): void
    {
        $week = (new AgendaWeekBuilder())->build(Carbon::parse('2026-07-02'), new Collection([
            new TimelineEvent(
                id: 'event',
                type: TimelineType::Task,
                title: 'Evento',
                datetime: Carbon::parse('2026-07-02 09:00:00'),
            ),
        ]));

        $this->assertCount(7, $week->days);
        $this->assertSame(1, $week->summary['total']);
    }

    public function test_month_builder_builds_weeks_and_summary(): void
    {
        $month = (new AgendaMonthBuilder())->build(Carbon::parse('2026-07-02'), new Collection([
            new TimelineEvent(
                id: 'event',
                type: TimelineType::Task,
                title: 'Evento',
                datetime: Carbon::parse('2026-07-02 09:00:00'),
            ),
        ]));

        $this->assertSame('2026-07', $month->month->format('Y-m'));
        $this->assertNotEmpty($month->weeks);
        $this->assertSame(1, $month->summary['total']);
    }
}
