<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\HearingStatus;
use App\Enums\HearingSubmissionStatus;
use App\Models\Hearing;
use App\Models\HearingSubmission;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class HearingTimelineProvider implements TimelineProviderInterface
{
    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('public_lists.view')) {
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
            ->whereIn('status', [
                HearingStatus::Issued->value,
                HearingStatus::Open->value,
            ])
            ->whereNotNull('deadline_at')
            ->whereDate('deadline_at', '<=', now()->addDays(2)->toDateString())
            ->orderBy('deadline_at')
            ->limit(8)
            ->get()
            ->map(fn (Hearing $hearing): TimelineEvent => new TimelineEvent(
                id: 'hearing-'.$hearing->getKey(),
                type: 'hearing',
                title: $hearing->deadline_at?->isPast()
                    ? 'Audiência prévia expirada'
                    : 'Audiência prévia com prazo próximo',
                description: trim(($hearing->hearing_number ?? 'Audiência').' · '.$hearing->subject),
                route: 'backoffice.hearings.show',
                datetime: $hearing->deadline_at,
                priority: $hearing->deadline_at?->isPast() ? 'critical' : 'high',
                icon: 'message',
                tone: $hearing->deadline_at?->isPast() ? 'danger' : 'warning',
                workspace: 'contests',
                metadata: [
                    'hearing_id' => $hearing->getKey(),
                    'hearing_number' => $hearing->hearing_number,
                    'status' => $hearing->status?->value,
                ],
            ))
            ->all();
    }

    private function submittedHearings(): array
    {
        return HearingSubmission::query()
            ->whereIn('status', [
                HearingSubmissionStatus::Submitted->value,
            ])
            ->orderBy('submitted_at')
            ->limit(8)
            ->get()
            ->map(fn (HearingSubmission $submission): TimelineEvent => new TimelineEvent(
                id: 'hearing-submission-'.$submission->getKey(),
                type: 'hearing-submission',
                title: 'Pronúncia em audiência por analisar',
                description: trim('Pronúncia submetida · '.$submission->submitted_at?->format('d/m/Y H:i')),
                route: 'backoffice.hearing-submissions.show',
                datetime: $submission->submitted_at,
                priority: 'high',
                icon: 'message',
                tone: 'warning',
                workspace: 'contests',
                metadata: [
                    'hearing_submission_id' => $submission->getKey(),
                    'hearing_id' => $submission->hearing_id,
                    'status' => $submission->status?->value,
                ],
            ))
            ->all();
    }
}
