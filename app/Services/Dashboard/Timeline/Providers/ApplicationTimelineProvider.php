<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\Application;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;

class ApplicationTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory,
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('applications.view')) {
            return [];
        }

        return Application::query()
            ->whereNotNull('submitted_at')
            ->whereIn('status', ['submitted', 'under_analysis', 'documents_pending', 'eligible', 'ineligible'])
            ->orderBy('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (Application $application): TimelineEvent => $this->factory->make(
                id: 'application-submitted-'.$application->getKey(),
                type: TimelineType::ApplicationSubmitted,
                title: 'Candidatura submetida',
                description: trim(($application->application_number ?? 'Candidatura').' · para análise técnica'),
                route: route('backoffice.applications.index'),
                datetime: $application->submitted_at,
                priority: TimelinePriority::Medium,
                icon: 'document',
                tone: 'info',
                workspace: TimelineWorkspace::Applications,
                metadata: [
                    'application_id' => $application->getKey(),
                    'application_number' => $application->application_number,
                    'status' => $application->status?->value ?? $application->status,
                ],
            ))
            ->all();
    }
}
