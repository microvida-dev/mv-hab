<?php

namespace App\Services\Contracts;

use App\Enums\RentCalculationStatus;
use App\Enums\RentManualReviewStatus;
use App\Models\RentCalculation;
use App\Models\RentManualReview;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class RentManualReviewService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function request(RentCalculation $calculation, array $data, User $actor): RentManualReview
    {
        if (! $calculation->rentRuleSet?->allow_manual_override) {
            throw ValidationException::withMessages(['rent_calculation_id' => 'A regra de renda não permite revisão manual.']);
        }

        $review = RentManualReview::query()->create([
            'rent_calculation_id' => $calculation->id,
            'requested_by' => $actor->id,
            'status' => RentManualReviewStatus::Pending,
            'original_rent' => $calculation->applicable_rent ?? 0,
            'proposed_rent' => $data['proposed_rent'],
            'reason' => $data['reason'],
            'legal_basis' => $data['legal_basis'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'requested_at' => now(),
        ]);

        $calculation->forceFill(['status' => RentCalculationStatus::RequiresManualReview])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $review, 'contracts', 'rent_manual_review_request', 'Revisão manual de renda solicitada.');

        return $review->refresh();
    }

    public function approve(RentManualReview $review, User $actor, ?float $approvedRent = null, ?string $internalNotes = null): RentManualReview
    {
        $calculation = $review->rentCalculation;

        if (! $calculation instanceof RentCalculation) {
            throw ValidationException::withMessages(['rent_calculation_id' => 'A revisão manual não tem cálculo de renda associado.']);
        }

        $approved = $approvedRent ?? (float) $review->proposed_rent;
        $review->forceFill([
            'status' => RentManualReviewStatus::Approved,
            'reviewed_by' => $actor->id,
            'approved_rent' => $approved,
            'internal_notes' => $internalNotes ?: $review->internal_notes,
            'reviewed_at' => now(),
        ])->save();

        $calculation->forceFill([
            'status' => RentCalculationStatus::Approved,
            'manual_rent' => $approved,
            'applicable_rent' => $approved,
            'approved_at' => now(),
            'approved_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $review, 'contracts', 'rent_manual_review_approve', 'Revisão manual de renda aprovada.');

        return $review->refresh();
    }

    public function reject(RentManualReview $review, User $actor, string $reason): RentManualReview
    {
        $review->forceFill([
            'status' => RentManualReviewStatus::Rejected,
            'reviewed_by' => $actor->id,
            'internal_notes' => trim(($review->internal_notes ?? '')."\nRejeição: ".$reason),
            'reviewed_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::REJECT, $review, 'contracts', 'rent_manual_review_reject', 'Revisão manual de renda rejeitada.');

        return $review->refresh();
    }
}
