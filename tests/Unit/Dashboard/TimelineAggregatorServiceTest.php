<?php

namespace Tests\Unit\Dashboard;

use App\Data\Dashboard\TimelineEvent;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TimelineAggregatorServiceTest extends TestCase
{
    public function test_it_orders_events_by_priority_and_datetime(): void
    {
        Carbon::setTestNow('2026-07-02 08:00:00');

        $provider = new class implements TimelineProviderInterface {
            public function forUser(User $user, array $dashboard = []): array
            {
                return [
                    new TimelineEvent(
                        id: 'low-later',
                        type: 'deadline',
                        title: 'Prazo baixo',
                        datetime: Carbon::parse('2026-07-02 10:00:00'),
                        priority: 'low',
                    ),
                    new TimelineEvent(
                        id: 'critical-later',
                        type: 'task',
                        title: 'Tarefa crítica',
                        datetime: Carbon::parse('2026-07-02 11:00:00'),
                        priority: 'critical',
                    ),
                    new TimelineEvent(
                        id: 'high-earlier',
                        type: 'visit',
                        title: 'Visita alta',
                        datetime: Carbon::parse('2026-07-02 09:00:00'),
                        priority: 'high',
                    ),
                ];
            }
        };

        $timeline = (new TimelineAggregatorService([$provider]))->forUser(new User());

        $this->assertSame('critical-later', $timeline['items'][0]['id']);
        $this->assertSame('high-earlier', $timeline['items'][1]['id']);
        $this->assertSame('low-later', $timeline['items'][2]['id']);
        $this->assertSame('high-earlier', $timeline['nextAction']['id']);
        $this->assertSame(3, $timeline['metrics']['total']);
        $this->assertSame(1, $timeline['metrics']['critical']);
    }

    public function test_it_groups_events_by_today_tomorrow_and_without_date(): void
    {
        Carbon::setTestNow('2026-07-02 08:00:00');

        $provider = new class implements TimelineProviderInterface {
            public function forUser(User $user, array $dashboard = []): array
            {
                return [
                    new TimelineEvent(
                        id: 'today',
                        type: 'task',
                        title: 'Hoje',
                        datetime: Carbon::parse('2026-07-02 09:00:00'),
                        priority: 'medium',
                    ),
                    new TimelineEvent(
                        id: 'tomorrow',
                        type: 'visit',
                        title: 'Amanhã',
                        datetime: Carbon::parse('2026-07-03 09:00:00'),
                        priority: 'medium',
                    ),
                    new TimelineEvent(
                        id: 'without-date',
                        type: 'deadline',
                        title: 'Sem data',
                        datetime: null,
                        priority: 'low',
                    ),
                ];
            }
        };

        $timeline = (new TimelineAggregatorService([$provider]))->forUser(new User());

        $this->assertSame(['Hoje', 'Amanhã', 'Sem data'], array_column($timeline['groups'], 'label'));
    }

    public function test_it_removes_duplicate_events_by_id(): void
    {
        $provider = new class implements TimelineProviderInterface {
            public function forUser(User $user, array $dashboard = []): array
            {
                return [
                    new TimelineEvent(id: 'same-id', type: 'task', title: 'Primeiro'),
                    new TimelineEvent(id: 'same-id', type: 'task', title: 'Duplicado'),
                ];
            }
        };

        $timeline = (new TimelineAggregatorService([$provider]))->forUser(new User());

        $this->assertCount(1, $timeline['items']);
        $this->assertSame('Primeiro', $timeline['items'][0]['title']);
    }
}
