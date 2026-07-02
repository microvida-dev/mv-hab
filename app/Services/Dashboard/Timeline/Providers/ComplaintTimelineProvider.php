<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\ComplaintDecisionStatus;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintDecision;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class ComplaintTimelineProvider implements TimelineProviderInterface
{
    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('public_lists.view')) {
            return [];
        }

        return collect()
            ->merge($this->openComplaints())
            ->merge($this->additionalInformationDeadlines())
            ->merge($this->pendingDecisions())
            ->values()
            ->all();
    }

    private function openComplaints(): array
    {
        return Complaint::query()
            ->whereIn('status', [
                ComplaintStatus::Submitted->value,
                ComplaintStatus::Received->value,
                ComplaintStatus::UnderReview->value,
            ])
            ->orderByRaw('assigned_at IS NULL DESC, submitted_at ASC')
            ->limit(8)
            ->get()
            ->map(fn (Complaint $complaint): TimelineEvent => new TimelineEvent(
                id: 'complaint-'.$complaint->getKey(),
                type: TimelineType::Complaint,
                title: 'Reclamação por analisar',
                description: trim(($complaint->complaint_number ?? 'Reclamação').' · '.$complaint->subject),
                route: 'backoffice.complaints.show',
                datetime: $complaint->submitted_at ?? $complaint->received_at ?? $complaint->created_at,
                priority: $complaint->assigned_to ? 'high' : 'critical',
                icon: 'message-alert',
                tone: $complaint->assigned_to ? 'warning' : 'danger',
                workspace: TimelineWorkspace::Contests,
                metadata: [
                    'complaint_id' => $complaint->getKey(),
                    'complaint_number' => $complaint->complaint_number,
                    'status' => $complaint->status?->value,
                    'assigned_to' => $complaint->assigned_to,
                ],
            ))
            ->all();
    }

    private function additionalInformationDeadlines(): array
    {
        return Complaint::query()
            ->where('requires_additional_information', true)
            ->whereNotNull('additional_information_deadline_at')
            ->whereDate('additional_information_deadline_at', '<=', now()->addDays(2)->toDateString())
            ->orderBy('additional_information_deadline_at')
            ->limit(8)
            ->get()
            ->map(fn (Complaint $complaint): TimelineEvent => new TimelineEvent(
                id: 'complaint-additional-information-'.$complaint->getKey(),
                type: TimelineType::ComplaintAdditionalInformation,
                title: $complaint->additional_information_deadline_at?->isPast()
                    ? 'Informação adicional de reclamação expirada'
                    : 'Informação adicional de reclamação com prazo próximo',
                description: trim(($complaint->complaint_number ?? 'Reclamação').' · '.$complaint->subject),
                route: 'backoffice.complaints.show',
                datetime: $complaint->additional_information_deadline_at,
                priority: $complaint->additional_information_deadline_at?->isPast() ? 'critical' : 'high',
                icon: 'message-alert',
                tone: $complaint->additional_information_deadline_at?->isPast() ? 'danger' : 'warning',
                workspace: TimelineWorkspace::Contests,
                metadata: [
                    'complaint_id' => $complaint->getKey(),
                    'complaint_number' => $complaint->complaint_number,
                    'status' => $complaint->status?->value,
                ],
            ))
            ->all();
    }

    private function pendingDecisions(): array
    {
        return ComplaintDecision::query()
            ->whereIn('status', [
                ComplaintDecisionStatus::Draft->value,
                ComplaintDecisionStatus::Proposed->value,
            ])
            ->orderBy('proposed_at')
            ->limit(8)
            ->get()
            ->map(fn (ComplaintDecision $decision): TimelineEvent => new TimelineEvent(
                id: 'complaint-decision-'.$decision->getKey(),
                type: TimelineType::ComplaintDecision,
                title: 'Decisão de reclamação pendente',
                description: trim(($decision->decision_number ?? 'Decisão').' · '.$decision->summary),
                route: 'backoffice.complaint-decisions.show',
                datetime: $decision->proposed_at ?? $decision->created_at,
                priority: TimelinePriority::High,
                icon: 'document-check',
                tone: 'warning',
                workspace: TimelineWorkspace::Contests,
                metadata: [
                    'complaint_decision_id' => $decision->getKey(),
                    'decision_number' => $decision->decision_number,
                    'status' => $decision->status?->value,
                ],
            ))
            ->all();
    }
}
