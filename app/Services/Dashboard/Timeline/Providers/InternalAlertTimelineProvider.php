<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\InternalAlertStatus;
use App\Models\InternalAlert;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;

class InternalAlertTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory,
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('internal_alerts.view')) {
            return [];
        }

        return InternalAlert::query()
            ->whereNotNull('due_at')
            ->whereNotIn('status', [
                InternalAlertStatus::Resolved->value,
                InternalAlertStatus::Dismissed->value,
            ])
            ->orderBy('due_at')
            ->limit(20)
            ->get()
            ->map(fn (InternalAlert $alert): TimelineEvent => $this->factory->make(
                id: 'internal-alert-'.$alert->getKey(),
                type: TimelineType::InternalAlert,
                title: 'Alerta interno com prazo',
                description: trim(($alert->title ?? 'Alerta interno').' · '.($alert->message ?? '')),
                route: route('backoffice.internal-alerts.index'),
                datetime: $alert->due_at,
                priority: $alert->due_at?->isPast()
                    ? TimelinePriority::Critical
                    : TimelinePriority::High,
                icon: 'notification',
                tone: $alert->due_at?->isPast() ? 'danger' : 'warning',
                workspace: TimelineWorkspace::Administration,
                metadata: [
                    'internal_alert_id' => $alert->getKey(),
                    'status' => $alert->status->value,
                ],
            ))
            ->all();
    }
}
