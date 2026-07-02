<?php

namespace Tests\Unit\Dashboard;

use App\Data\Dashboard\TimelineEvent;
use App\Services\Dashboard\Timeline\NextActionResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class NextActionResolverTest extends TestCase
{
    public function test_it_prioritizes_past_events_over_future_events(): void
    {
        Carbon::setTestNow('2026-07-02 10:00:00');

        $events = new Collection([
            new TimelineEvent(
                id: 'future-critical',
                type: 'task',
                title: 'Crítico futuro',
                datetime: Carbon::parse('2026-07-02 12:00:00'),
                priority: 'critical',
            ),
            new TimelineEvent(
                id: 'past-high',
                type: 'visit',
                title: 'Visita atrasada',
                datetime: Carbon::parse('2026-07-02 09:00:00'),
                priority: 'high',
            ),
        ]);

        $resolved = (new NextActionResolver())->resolve($events);

        $this->assertSame('past-high', $resolved?->id);
    }

    public function test_it_prioritizes_administrative_events_over_operational_events(): void
    {
        Carbon::setTestNow('2026-07-02 10:00:00');

        $events = new Collection([
            new TimelineEvent(
                id: 'task',
                type: 'task',
                title: 'Tarefa',
                datetime: Carbon::parse('2026-07-02 10:30:00'),
                priority: 'critical',
            ),
            new TimelineEvent(
                id: 'complaint',
                type: 'complaint',
                title: 'Reclamação',
                datetime: Carbon::parse('2026-07-02 11:30:00'),
                priority: 'high',
            ),
        ]);

        $resolved = (new NextActionResolver())->resolve($events);

        $this->assertSame('complaint', $resolved?->id);
    }

    public function test_it_returns_null_without_events(): void
    {
        $resolved = (new NextActionResolver())->resolve(new Collection());

        $this->assertNull($resolved);
    }
}
