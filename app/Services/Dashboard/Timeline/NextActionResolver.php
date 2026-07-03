<?php

namespace App\Services\Dashboard\Timeline;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use Illuminate\Support\Collection;

class NextActionResolver
{
    /**
     * @param  Collection<int, TimelineEvent>  $events
     */
    public function resolve(Collection $events): ?TimelineEvent
    {
        return $events
            ->sortBy([
                fn (TimelineEvent $event): int => $this->businessWeight($event),
                fn (TimelineEvent $event): int => $event->priorityWeight(),
                fn (TimelineEvent $event): string => $event->datetime?->toIso8601String() ?? '9999-12-31T23:59:59',
            ])
            ->first();
    }

    private function businessWeight(TimelineEvent $event): int
    {
        if ($event->datetime?->isPast()) {
            return 1;
        }

        if ($event->priority === TimelinePriority::Critical) {
            return 10;
        }

        return match ($event->type) {
            TimelineType::RgpdRequest,
            TimelineType::InternalAlert => 15,

            TimelineType::CorrectionRequest,
            TimelineType::CorrectionResponse,
            TimelineType::Hearing,
            TimelineType::HearingSubmission,
            TimelineType::Complaint,
            TimelineType::ComplaintAdditionalInformation,
            TimelineType::ComplaintDecision,
            TimelineType::ApplicationSubmitted,
            TimelineType::DocumentSubmitted,
            TimelineType::DocumentUnderReview,
            TimelineType::DocumentDossierIncomplete,
            TimelineType::AdditionalDocumentRequested,
            TimelineType::AdditionalDocumentSubmitted,
            TimelineType::AllocationOffer,
            TimelineType::AllocationAccepted,
            TimelineType::AllocationReadyForContract => 20,

            TimelineType::MaintenanceRequest,
            TimelineType::MaintenanceIntervention,
            TimelineType::ContractIssued,
            TimelineType::ContractSigned,
            TimelineType::ContractSuspended,
            TimelineType::ContractTerminated,
            TimelineType::TenantTransitionPending,
            TimelineType::TenantInvoiceOverdue,
            TimelineType::TenantCommunicationAwaitingMunicipality => 25,

            TimelineType::Task,
            TimelineType::RentOverdue,
            TimelineType::TenantInvoiceDue,
            TimelineType::TenantPaymentRegistered => 30,

            TimelineType::Inspection,
            TimelineType::Visit,
            TimelineType::KeyHandover,
            TimelineType::LotteryScheduled,
            TimelineType::LotteryReady,
            TimelineType::LotteryCompleted,
            TimelineType::LotteryValidated => 40,

            TimelineType::Deadline,
            TimelineType::RentDue,
            TimelineType::LeasePaymentReceived,
            TimelineType::ContractActive,
            TimelineType::TenantTransitionCompleted,
            TimelineType::TenantPaymentConfirmed,
            TimelineType::TenantCommunicationOpen => 50,
        };
    }
}
