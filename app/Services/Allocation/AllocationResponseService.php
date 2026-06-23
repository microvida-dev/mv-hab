<?php

namespace App\Services\Allocation;

use App\Enums\AllocationOfferStatus;
use App\Enums\AllocationStatus;
use App\Models\Allocation;
use App\Models\AllocationOffer;
use App\Models\AllocationRuleSet;
use App\Models\ContestHousingUnit;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AllocationResponseService
{
    public function __construct(
        private readonly ContestHousingUnitService $contestHousingUnitService,
        private readonly AllocationNotificationService $notificationService,
        private readonly ReplacementService $replacementService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function accept(AllocationOffer $offer, User $candidate, ?string $response = null): AllocationOffer
    {
        $this->assertOwnPendingOffer($offer, $candidate);

        return DB::transaction(function () use ($offer, $candidate, $response) {
            $offer->forceFill([
                'status' => AllocationOfferStatus::Accepted,
                'accepted_at' => now(),
                'candidate_response' => $response,
            ])->save();

            $allocation = $this->requiredAllocation($offer);
            $unit = $this->requiredContestHousingUnit($offer);

            $allocation->forceFill([
                'status' => AllocationStatus::ReadyForContract,
                'accepted_at' => now(),
                'ready_for_contract_at' => now(),
            ])->save();

            $this->contestHousingUnitService->markAccepted($unit, $candidate);
            $this->notificationService->offerAccepted($offer, $candidate);
            $this->notificationService->readyForContract($allocation, $candidate);
            $this->auditLogger->record(AuditEvents::UPDATE, $offer, 'allocations', 'allocation_offer_accept', 'Oferta de atribuição aceite pelo candidato.');

            return $offer->refresh();
        });
    }

    public function refuse(AllocationOffer $offer, User $candidate, string $reason): AllocationOffer
    {
        $this->assertOwnPendingOffer($offer, $candidate);

        return DB::transaction(function () use ($offer, $candidate, $reason) {
            $offer->forceFill([
                'status' => AllocationOfferStatus::Refused,
                'refused_at' => now(),
                'refusal_reason' => $reason,
            ])->save();

            $allocation = $this->requiredAllocation($offer);
            $unit = $this->requiredContestHousingUnit($offer);

            $allocation->forceFill([
                'status' => AllocationStatus::Refused,
                'refused_at' => now(),
                'refusal_reason' => $reason,
            ])->save();

            $this->contestHousingUnitService->release($unit, $candidate);
            $this->notificationService->offerRefused($offer, $candidate);
            $this->auditLogger->record(AuditEvents::UPDATE, $offer, 'allocations', 'allocation_offer_refuse', 'Oferta de atribuição recusada pelo candidato.');

            if ($this->requiredAllocationRuleSet($allocation)->auto_call_next_on_refusal) {
                $this->replacementService->callNextFor($allocation->refresh(), $candidate);
            }

            return $offer->refresh();
        });
    }

    public function withdraw(Allocation $allocation, User $candidate, string $reason): Allocation
    {
        if ($allocation->user_id !== $candidate->id) {
            throw ValidationException::withMessages(['allocation' => 'Só pode desistir da sua própria atribuição.']);
        }

        return DB::transaction(function () use ($allocation, $candidate, $reason) {
            $allocation->forceFill([
                'status' => AllocationStatus::Withdrawn,
                'withdrawn_at' => now(),
                'withdrawal_reason' => $reason,
            ])->save();

            $allocation->activeOffer?->forceFill([
                'status' => AllocationOfferStatus::Withdrawn,
                'withdrawn_at' => now(),
            ])->save();

            $this->contestHousingUnitService->release($this->requiredAllocationContestHousingUnit($allocation), $candidate);
            $this->auditLogger->record(AuditEvents::UPDATE, $allocation, 'allocations', 'allocation_withdraw', 'Desistência de atribuição registada.');

            if ($this->requiredAllocationRuleSet($allocation)->auto_call_next_on_refusal) {
                $this->replacementService->callNextFor($allocation->refresh(), $candidate);
            }

            return $allocation->refresh();
        });
    }

    public function expire(AllocationOffer $offer, User $actor): AllocationOffer
    {
        if (! $this->offerStatusIsIn($offer, [AllocationOfferStatus::PendingResponse, AllocationOfferStatus::Issued])) {
            throw ValidationException::withMessages(['allocation_offer' => 'A oferta não está pendente.']);
        }

        $offer->forceFill(['status' => AllocationOfferStatus::Expired, 'expired_at' => now()])->save();
        $allocation = $this->requiredAllocation($offer);
        $allocation->forceFill(['status' => AllocationStatus::Expired, 'expired_at' => now()])->save();
        $this->contestHousingUnitService->release($this->requiredContestHousingUnit($offer), $actor);
        $this->notificationService->offerExpired($offer, $actor);
        $this->auditLogger->record(AuditEvents::UPDATE, $offer, 'allocations', 'allocation_offer_expire', 'Oferta de atribuição expirada.');

        if ($this->requiredAllocationRuleSet($allocation)->auto_call_next_on_expiry) {
            $this->replacementService->callNextFor($allocation->refresh(), $actor);
        }

        return $offer->refresh();
    }

    private function assertOwnPendingOffer(AllocationOffer $offer, User $candidate): void
    {
        if ($offer->user_id !== $candidate->id) {
            throw ValidationException::withMessages(['allocation_offer' => 'Não pode responder a uma oferta de outro candidato.']);
        }

        if ($this->offerStatus($offer) !== AllocationOfferStatus::PendingResponse) {
            throw ValidationException::withMessages(['allocation_offer' => 'A oferta não está pendente de resposta.']);
        }

        if ($offer->response_deadline_at && now()->gt($offer->response_deadline_at)) {
            throw ValidationException::withMessages(['allocation_offer' => 'O prazo de resposta terminou.']);
        }
    }

    private function requiredAllocation(AllocationOffer $offer): Allocation
    {
        $allocation = $offer->allocation;

        if (! $allocation instanceof Allocation) {
            throw ValidationException::withMessages(['allocation' => 'A oferta não tem atribuição associada.']);
        }

        return $allocation;
    }

    private function requiredContestHousingUnit(AllocationOffer $offer): ContestHousingUnit
    {
        $unit = $offer->contestHousingUnit;

        if (! $unit instanceof ContestHousingUnit) {
            throw ValidationException::withMessages(['contest_housing_unit' => 'A oferta não tem habitação associada.']);
        }

        return $unit;
    }

    private function requiredAllocationContestHousingUnit(Allocation $allocation): ContestHousingUnit
    {
        $unit = $allocation->contestHousingUnit;

        if (! $unit instanceof ContestHousingUnit) {
            throw ValidationException::withMessages(['contest_housing_unit' => 'A atribuição não tem habitação associada.']);
        }

        return $unit;
    }

    private function requiredAllocationRuleSet(Allocation $allocation): AllocationRuleSet
    {
        $ruleSet = $allocation->allocationRuleSet;

        if (! $ruleSet instanceof AllocationRuleSet) {
            throw ValidationException::withMessages(['allocation_rule_set' => 'A atribuição não tem regras de atribuição associadas.']);
        }

        return $ruleSet;
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
