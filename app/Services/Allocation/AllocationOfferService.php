<?php

namespace App\Services\Allocation;

use App\Enums\AllocationOfferStatus;
use App\Enums\AllocationStatus;
use App\Models\Allocation;
use App\Models\AllocationOffer;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AllocationOfferService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly AllocationNotificationService $notificationService,
    ) {}

    public function createAndIssue(Allocation $allocation, User $actor, ?string $message = null, ?string $instructions = null): AllocationOffer
    {
        return DB::transaction(function () use ($allocation, $actor, $message, $instructions) {
            $deadline = $allocation->acceptance_deadline_at;
            $offer = new AllocationOffer([
                'message' => $message ?? 'Foi-lhe proposta a atribuição de uma habitação.',
                'instructions' => $instructions ?? 'Responda dentro do prazo indicado na área reservada.',
            ]);
            $offer->forceFill([
                'allocation_id' => $allocation->id,
                'application_id' => $allocation->application_id,
                'user_id' => $allocation->user_id,
                'contest_housing_unit_id' => $allocation->contest_housing_unit_id,
                'housing_unit_id' => $allocation->housing_unit_id,
                'offer_number' => $this->generateOfferNumber(),
                'status' => AllocationOfferStatus::PendingResponse,
                'issued_by' => $actor->id,
                'issued_at' => now(),
                'response_deadline_at' => $deadline,
            ])->save();

            $allocation->forceFill([
                'status' => AllocationStatus::Offered,
                'offered_at' => now(),
            ])->save();

            $this->notificationService->offerIssued($offer, $actor);
            $this->auditLogger->record(AuditEvents::CREATE, $offer, 'allocations', 'allocation_offer_issue', 'Oferta de atribuição emitida.');

            return $offer->refresh();
        });
    }

    public function issue(AllocationOffer $offer, User $actor): AllocationOffer
    {
        if ($this->offerStatus($offer) !== AllocationOfferStatus::Draft) {
            throw ValidationException::withMessages(['allocation_offer' => 'Apenas ofertas em rascunho podem ser emitidas.']);
        }

        $offer->forceFill([
            'status' => AllocationOfferStatus::PendingResponse,
            'issued_by' => $actor->id,
            'issued_at' => now(),
        ])->save();
        $this->notificationService->offerIssued($offer, $actor);
        $this->auditLogger->record(AuditEvents::UPDATE, $offer, 'allocations', 'allocation_offer_issue', 'Oferta de atribuição emitida.');

        return $offer->refresh();
    }

    public function markExpired(AllocationOffer $offer, User $actor): AllocationOffer
    {
        if (! $this->offerStatusIsIn($offer, [AllocationOfferStatus::PendingResponse, AllocationOfferStatus::Issued])) {
            throw ValidationException::withMessages(['allocation_offer' => 'A oferta não está pendente de resposta.']);
        }

        $offer->forceFill(['status' => AllocationOfferStatus::Expired, 'expired_at' => now()])->save();
        $this->requiredAllocation($offer)->forceFill(['status' => AllocationStatus::Expired, 'expired_at' => now()])->save();
        $this->notificationService->offerExpired($offer, $actor);
        $this->auditLogger->record(AuditEvents::UPDATE, $offer, 'allocations', 'allocation_offer_expire', 'Oferta de atribuição expirada.');

        return $offer->refresh();
    }

    public function cancel(AllocationOffer $offer, User $actor, ?string $reason = null): AllocationOffer
    {
        $offer->forceFill(['status' => AllocationOfferStatus::Cancelled, 'cancelled_at' => now()])->save();
        $this->requiredAllocation($offer)->forceFill(['status' => AllocationStatus::Cancelled, 'cancelled_at' => now(), 'cancellation_reason' => $reason])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $offer, 'allocations', 'allocation_offer_cancel', 'Oferta de atribuição cancelada.');

        return $offer->refresh();
    }

    private function generateOfferNumber(): string
    {
        $next = AllocationOffer::withTrashed()->count() + 1;

        do {
            $number = 'OAF-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (AllocationOffer::withTrashed()->where('offer_number', $number)->exists());

        return $number;
    }

    private function requiredAllocation(AllocationOffer $offer): Allocation
    {
        $allocation = $offer->allocation;

        if (! $allocation instanceof Allocation) {
            throw ValidationException::withMessages(['allocation' => 'A oferta não tem atribuição associada.']);
        }

        return $allocation;
    }

    /**
     * @param  list<AllocationOfferStatus>  $statuses
     */
    private function offerStatusIsIn(AllocationOffer $offer, array $statuses): bool
    {
        $status = $this->offerStatus($offer);

        return $status !== null && in_array($status, $statuses, true);
    }

    private function offerStatus(AllocationOffer $offer): ?AllocationOfferStatus
    {
        $status = $offer->getAttribute('status');

        if ($status instanceof AllocationOfferStatus) {
            return $status;
        }

        return is_string($status) ? AllocationOfferStatus::tryFrom($status) : null;
    }
}
