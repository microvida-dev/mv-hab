<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class DeadlineTimelineProvider implements TimelineProviderInterface
{
    public function forUser(User $user, array $dashboard = []): array
    {
        return collect($dashboard['deadlines'] ?? [])
            ->map(fn (array $deadline, int $index): TimelineEvent => new TimelineEvent(
                id: 'deadline-'.$index.'-'.md5((string) ($deadline['label'] ?? $deadline['title'] ?? 'Prazo')),
                type: 'deadline',
                title: (string) ($deadline['label'] ?? $deadline['title'] ?? 'Prazo'),
                description: (string) ($deadline['description'] ?? ''),
                route: (string) ($deadline['route'] ?? 'dashboard'),
                datetime: null,
                priority: 'low',
                icon: 'calendar',
                tone: (string) ($deadline['tone'] ?? 'neutral'),
                workspace: 'operations',
                metadata: $deadline,
            ))
            ->all();
    }
}
