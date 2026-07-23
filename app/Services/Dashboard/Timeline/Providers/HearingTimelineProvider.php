<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\HearingStatus;
use App\Enums\HearingSubmissionStatus;
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

    /**
     * @return array<int, TimelineEvent>
     */
    private function openHearings(): array
    {
        return Hearing::query()
            ->whereNotNull('deadline_at')
            ->whereIn('status', [
                HearingStatus::Issued->value,
                HearingStatus::Open->value,
                HearingStatus::Submitted->value,
                HearingStatus::UnderReview->value,
            ])
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
                    'status' => $hearing->status->value,
                ],
            ))
            ->all();
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function submittedHearings(): array
    {
        return HearingSubmission::query()
            ->with('hearing')
            ->whereNotNull('submitted_at')
            ->whereIn('status', [
                HearingSubmissionStatus::Submitted->value,
                HearingSubmissionStatus::UnderReview->value,
            ])
            ->orderBy('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (HearingSubmission $submission): TimelineEvent => $this->submittedHearingEvent($submission))
            ->all();
    }

    private function submittedHearingEvent(HearingSubmission $submission): TimelineEvent
    {
        $hearing = $submission->hearing;
        $hearingNumber = $hearing instanceof Hearing ? $hearing->hearing_number : 'Pronúncia';

        return $this->factory->make(
            id: 'hearing-submission-'.$submission->getKey(),
            type: TimelineType::HearingSubmission,
            title: 'Pronúncia recebida',
            description: trim($hearingNumber.' · aguarda análise'),
            route: route('backoffice.hearings.index'),
            datetime: $submission->submitted_at,
            priority: TimelinePriority::Medium,
            icon: 'message',
            tone: 'info',
            workspace: TimelineWorkspace::Applications,
            metadata: [
                'hearing_submission_id' => $submission->getKey(),
                'hearing_number' => $hearingNumber,
                'status' => $submission->status->value,
            ],
        );
    }
}
