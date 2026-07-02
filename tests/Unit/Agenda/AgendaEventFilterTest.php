<?php

namespace Tests\Unit\Agenda;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineStatus;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Services\Agenda\AgendaEventFilter;
use App\Services\Agenda\Filters\AgendaFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AgendaEventFilterTest extends TestCase
{
    public function test_it_filters_events_by_workspace_priority_status_type_and_dates(): void
    {
        $events = new Collection([
            new TimelineEvent(
                id: 'match',
                type: TimelineType::Visit,
                title: 'Visita',
                datetime: Carbon::parse('2026-07-02 10:00:00'),
                priority: TimelinePriority::High,
                status: TimelineStatus::Scheduled,
                workspace: TimelineWorkspace::Patrimony,
            ),
            new TimelineEvent(
                id: 'wrong-workspace',
                type: TimelineType::Visit,
                title: 'Outro',
                datetime: Carbon::parse('2026-07-02 10:00:00'),
                priority: TimelinePriority::High,
                status: TimelineStatus::Scheduled,
                workspace: TimelineWorkspace::Applications,
            ),
            new TimelineEvent(
                id: 'wrong-date',
                type: TimelineType::Visit,
                title: 'Fora do período',
                datetime: Carbon::parse('2026-07-05 10:00:00'),
                priority: TimelinePriority::High,
                status: TimelineStatus::Scheduled,
                workspace: TimelineWorkspace::Patrimony,
            ),
        ]);

        $filtered = (new AgendaEventFilter())->apply($events, new AgendaFilters(
            workspace: TimelineWorkspace::Patrimony,
            priority: TimelinePriority::High,
            status: TimelineStatus::Scheduled,
            type: TimelineType::Visit,
            from: Carbon::parse('2026-07-02 00:00:00'),
            to: Carbon::parse('2026-07-02 23:59:59'),
        ));

        $this->assertCount(1, $filtered);
        $this->assertSame('match', $filtered->first()?->id);
    }
}
