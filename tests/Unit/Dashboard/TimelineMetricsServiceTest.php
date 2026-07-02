<?php

namespace Tests\Unit\Dashboard;

use App\Data\Dashboard\TimelineEvent;
use App\Services\Dashboard\Timeline\TimelineMetricsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TimelineMetricsServiceTest extends TestCase
{
    public function test_it_calculates_timeline_metrics(): void
    {
        Carbon::setTestNow('2026-07-02 10:00:00');

        $metrics = (new TimelineMetricsService())->calculate(new Collection([
            new TimelineEvent(
                id: 'overdue',
                type: 'complaint',
                title: 'Reclamação',
                datetime: Carbon::parse('2026-07-02 09:00:00'),
                priority: 'critical',
                workspace: 'contests',
            ),
            new TimelineEvent(
                id: 'today',
                type: 'visit',
                title: 'Visita',
                datetime: Carbon::parse('2026-07-02 12:00:00'),
                priority: 'medium',
                workspace: 'patrimony',
            ),
            new TimelineEvent(
                id: 'future',
                type: 'task',
                title: 'Tarefa',
                datetime: Carbon::parse('2026-07-03 12:00:00'),
                priority: 'high',
                workspace: 'operations',
            ),
        ]));

        $this->assertSame(3, $metrics['total']);
        $this->assertSame(1, $metrics['critical']);
        $this->assertSame(1, $metrics['high']);
        $this->assertSame(1, $metrics['overdue']);
        $this->assertSame(2, $metrics['today']);
        $this->assertSame(1, $metrics['by_workspace']['contests']);
        $this->assertSame(1, $metrics['by_type']['complaint']);
    }
}
