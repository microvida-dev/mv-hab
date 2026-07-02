<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class DeadlineTimelineProvider implements TimelineProviderInterface
{
    public function forUser(User $user, array $dashboard = []): array
    {
        return collect($dashboard['deadlines'] ?? [])
            ->map(fn (array $deadline, int $index): TimelineEvent => new TimelineEvent(
                id: 'deadline-'.$index.'-'.md5((string) ($deadline['label'] ?? $deadline['title'] ?? 'Prazo')),
                type: TimelineType::Deadline,
                title: (string) ($deadline['label'] ?? $deadline['title'] ?? 'Prazo'),
                description: (string) ($deadline['description'] ?? ''),
                route: (string) ($deadline['route'] ?? 'dashboard'),
                datetime: null,
                priority: TimelinePriority::Low,
                icon: 'calendar',
                tone: (string) ($deadline['tone'] ?? 'neutral'),
                workspace: TimelineWorkspace::Operations,
                metadata: $deadline,
            ))
            ->all();
    }
}
