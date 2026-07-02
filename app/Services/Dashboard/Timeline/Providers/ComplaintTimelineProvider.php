<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\Complaint;
use App\Models\ComplaintDecision;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class ComplaintTimelineProvider implements TimelineProviderInterface
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory(),
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('complaints.view')) {
            return [];
        }

        return collect()
            ->merge($this->openComplaints())
            ->merge($this->additionalInformationDeadlines())
            ->merge($this->decisions())
            ->values()
            ->all();
    }

    private function openComplaints(): array
    {
        return Complaint::query()
            ->whereIn('status', ['submitted', 'registered', 'in_analysis'])
            ->orderByDesc('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (Complaint $complaint): TimelineEvent => $this->factory->make(
                id: 'complaint-'.$complaint->getKey(),
                type: TimelineType::Complaint,
                title: 'Reclamação em análise',
                description: trim(($complaint->complaint_number ?? 'Reclamação').' · '.$complaint->subject),
                route: route('backoffice.complaints.index'),
                datetime: $complaint->submitted_at ?? $complaint->received_at ?? $complaint->created_at,
                priority: TimelinePriority::High,
                icon: 'complaint',
                tone: 'warning',
                workspace: TimelineWorkspace::Applications,
                metadata: [
                    'complaint_id' => $complaint->getKey(),
                    'complaint_number' => $complaint->complaint_number,
                    'status' => $complaint->status,
                ],
            ))
            ->all();
    }

    private function additionalInformationDeadlines(): array
    {
        return Complaint::query()
            ->whereNotNull('additional_information_deadline_at')
            ->whereIn('status', ['additional_information_requested'])
            ->orderBy('additional_information_deadline_at')
            ->limit(20)
            ->get()
            ->map(fn (Complaint $complaint): TimelineEvent => $this->factory->make(
                id: 'complaint-additional-information-'.$complaint->getKey(),
                type: TimelineType::ComplaintAdditionalInformation,
                title: 'Informação adicional de reclamação pendente',
                description: trim(($complaint->complaint_number ?? 'Reclamação').' · prazo de resposta'),
                route: route('backoffice.complaints.index'),
                datetime: $complaint->additional_information_deadline_at,
                priority: $complaint->additional_information_deadline_at?->isPast()
                    ? TimelinePriority::Critical
                    : TimelinePriority::High,
                icon: 'document-warning',
                tone: $complaint->additional_information_deadline_at?->isPast() ? 'danger' : 'warning',
                workspace: TimelineWorkspace::Applications,
                metadata: [
                    'complaint_id' => $complaint->getKey(),
                    'complaint_number' => $complaint->complaint_number,
                    'status' => $complaint->status,
                ],
            ))
            ->all();
    }

    private function decisions(): array
    {
        return ComplaintDecision::query()
            ->whereNotNull('proposed_at')
            ->whereIn('status', ['proposed', 'pending_approval'])
            ->orderBy('proposed_at')
            ->limit(20)
            ->get()
            ->map(fn (ComplaintDecision $decision): TimelineEvent => $this->factory->make(
                id: 'complaint-decision-'.$decision->getKey(),
                type: TimelineType::ComplaintDecision,
                title: 'Decisão de reclamação pendente',
                description: trim(($decision->decision_number ?? 'Decisão').' · aguarda aprovação'),
                route: route('backoffice.complaints.index'),
                datetime: $decision->proposed_at ?? $decision->created_at,
                priority: TimelinePriority::Medium,
                icon: 'scale',
                tone: 'info',
                workspace: TimelineWorkspace::Applications,
                metadata: [
                    'complaint_decision_id' => $decision->getKey(),
                    'decision_number' => $decision->decision_number,
                    'status' => $decision->status,
                ],
            ))
            ->all();
    }
}
