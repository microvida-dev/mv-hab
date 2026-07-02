<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use Tests\TestCase;

final class TimelineEventFactoryTest extends TestCase
{
    public function test_it_builds_timeline_event_from_carbon_datetime(): void
    {
        $datetime = now()->startOfMinute();

        $event = (new TimelineEventFactory())->make(
            id: 'event-1',
            type: TimelineType::Task,
            title: 'Evento',
            description: 'Descrição',
            route: route('dashboard'),
            datetime: $datetime,
            priority: TimelinePriority::Medium,
            icon: 'calendar',
            tone: 'info',
            workspace: TimelineWorkspace::Operations,
            metadata: ['source' => 'test'],
        );

        $this->assertSame('event-1', $event->id);
        $this->assertSame(TimelineType::Task, $event->type);
        $this->assertSame($datetime->toIso8601String(), $event->datetime?->toIso8601String());
        $this->assertSame(['source' => 'test'], $event->metadata);
    }

    public function test_it_builds_timeline_event_from_string_datetime(): void
    {
        $event = (new TimelineEventFactory())->make(
            id: 'event-2',
            type: TimelineType::Deadline,
            title: 'Prazo',
            description: 'Descrição',
            route: route('dashboard'),
            datetime: '2030-01-01 10:00:00',
            priority: TimelinePriority::High,
            icon: 'clock',
            tone: 'warning',
            workspace: TimelineWorkspace::Administration,
        );

        $this->assertSame('2030-01-01T10:00:00+00:00', $event->datetime?->toIso8601String());
    }

    public function test_it_builds_timeline_event_without_datetime(): void
    {
        $event = (new TimelineEventFactory())->make(
            id: 'event-3',
            type: TimelineType::InternalAlert,
            title: 'Alerta',
            description: 'Descrição',
            route: route('dashboard'),
            datetime: null,
            priority: TimelinePriority::Low,
            icon: 'bell',
            tone: 'neutral',
            workspace: TimelineWorkspace::Administration,
        );

        $this->assertNull($event->datetime);
    }
}
