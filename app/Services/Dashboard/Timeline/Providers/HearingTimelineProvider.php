<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\Hearing;
use App\Models\HearingSubmission;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;

class HearingTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory,
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('hearings.view')) {
            return [];
        }

        return collect()
            ->merge($this->openHearings())
            ->merge($this->submittedHearings())
            ->values()
            ->all();
    }

    private function openHearings(): array
    {
        return Hearing::query()
            ->whereNotNull('deadline_at')
            ->whereNotIn('status', ['closed', 'cancelled', 'expired'])
            ->orderBy('deadline_at')
            ->limit(20)
            ->get()
            ->map(fn (Hearing $hearing): TimelineEvent => $this->factory->make(
                id: 'hearing-'.$hearing->getKey(),
                type: TimelineType::Hearing,
                title: 'Audiência prévia em curso',
                description: trim(($hearing->hearing_number ?? 'Audiência').' · prazo de pronúncia'),
                route: route('backoffice.hearings.index'),
                datetime: $hearing->deadline_at,
                priority: $hearing->deadline_at?->isPast()
                    ? TimelinePriority::Critical
                    : TimelinePriority::High,
                icon: 'hearing',
                tone: $hearing->deadline_at?->isPast() ? 'danger' : 'warning',
                workspace: TimelineWorkspace::Applications,
                metadata: [
                    'hearing_id' => $hearing->getKey(),
                    'hearing_number' => $hearing->hearing_number,
                    'status' => $hearing->status,
                ],
            ))
            ->all();
    }

    private function submittedHearings(): array
    {
        return HearingSubmission::query()
            ->whereNotNull('submitted_at')
            ->whereIn('status', ['submitted', 'under_review'])
            ->orderBy('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (HearingSubmission $submission): TimelineEvent => $this->factory->make(
                id: 'hearing-submission-'.$submission->getKey(),
                type: TimelineType::HearingSubmission,
                title: 'Pronúncia recebida',
                description: trim(($submission->submission_number ?? 'Pronúncia').' · aguarda análise'),
                route: route('backoffice.hearings.index'),
                datetime: $submission->submitted_at,
                priority: TimelinePriority::Medium,
                icon: 'message',
                tone: 'info',
                workspace: TimelineWorkspace::Applications,
                metadata: [
                    'hearing_submission_id' => $submission->getKey(),
                    'submission_number' => $submission->submission_number,
                    'status' => $submission->status,
                ],
            ))
            ->all();
    }
}
