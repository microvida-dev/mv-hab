<?php

namespace App\Services\Contracts;

use App\Enums\RentCalculationStatus;
use App\Enums\RentManualReviewStatus;
use App\Models\RentCalculation;
use App\Models\RentManualReview;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentManualReviewService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function request(RentCalculation $calculation, array $data, User $actor): RentManualReview
    {
        return DB::transaction(function () use ($calculation, $data, $actor): RentManualReview {
            $lockedCalculation = RentCalculation::query()
                ->whereKey($calculation->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedCalculation->loadMissing('rentRuleSet');

            if (! $lockedCalculation->rentRuleSet?->allow_manual_override) {
                throw ValidationException::withMessages(['rent_calculation_id' => 'A regra de renda não permite revisão manual.']);
            }

            $existing = RentManualReview::query()
                ->where('rent_calculation_id', $lockedCalculation->id)
                ->where('status', RentManualReviewStatus::Pending->value)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof RentManualReview) {
                return $existing;
            }

            $review = RentManualReview::query()->create([
                'rent_calculation_id' => $lockedCalculation->id,
                'requested_by' => $actor->id,
                'status' => RentManualReviewStatus::Pending,
                'original_rent' => $lockedCalculation->applicable_rent ?? DecimalMoney::normalize(0),
                'proposed_rent' => DecimalMoney::normalize((string) $data['proposed_rent']),
                'reason' => $data['reason'],
                'legal_basis' => $data['legal_basis'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'requested_at' => now(),
            ]);

            $lockedCalculation->forceFill(['status' => RentCalculationStatus::RequiresManualReview])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $review, 'contracts', 'rent_manual_review_request', 'Revisão manual de renda solicitada.');

            return $review->refresh();
        });
    }

    public function approve(RentManualReview $review, User $actor, int|string|null $approvedRent = null, ?string $internalNotes = null): RentManualReview
    {
        return DB::transaction(function () use ($review, $actor, $approvedRent, $internalNotes): RentManualReview {
            $lockedReview = RentManualReview::query()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->reviewHasStatus($lockedReview, RentManualReviewStatus::Approved)) {
                return $lockedReview;
            }

            $calculation = $lockedReview->rentCalculation;

            if (! $calculation instanceof RentCalculation) {
                throw ValidationException::withMessages(['rent_calculation_id' => 'A revisão manual não tem cálculo de renda associado.']);
            }

            $lockedCalculation = RentCalculation::query()
                ->whereKey($calculation->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $approved = DecimalMoney::normalize(
                $approvedRent ?? (string) $lockedReview->proposed_rent,
            );

            if (! DecimalMoney::isPositive($approved)) {
                throw ValidationException::withMessages(['approved_rent' => 'A renda aprovada tem de ser superior a zero.']);
            }

            $lockedReview->forceFill([
                'status' => RentManualReviewStatus::Approved,
                'reviewed_by' => $actor->id,
                'approved_rent' => $approved,
                'internal_notes' => $internalNotes ?: $lockedReview->internal_notes,
                'reviewed_at' => now(),
            ])->save();

            $lockedCalculation->forceFill([
                'status' => RentCalculationStatus::Approved,
                'manual_rent' => $approved,
                'applicable_rent' => $approved,
                'approved_at' => now(),
                'approved_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::APPROVE, $lockedReview, 'contracts', 'rent_manual_review_approve', 'Revisão manual de renda aprovada.');

            return $lockedReview->refresh();
        });
    }

    public function reject(RentManualReview $review, User $actor, string $reason): RentManualReview
    {
        return DB::transaction(function () use ($review, $actor, $reason): RentManualReview {
            $lockedReview = RentManualReview::query()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($this->reviewHasStatus($lockedReview, RentManualReviewStatus::Rejected)) {
                return $lockedReview;
            }

            $lockedReview->forceFill([
                'status' => RentManualReviewStatus::Rejected,
                'reviewed_by' => $actor->id,
                'internal_notes' => trim(($lockedReview->internal_notes ?? '')."\nRejeição: ".$reason),
                'reviewed_at' => now(),
            ])->save();

            $this->auditLogger->record(AuditEvents::REJECT, $lockedReview, 'contracts', 'rent_manual_review_reject', 'Revisão manual de renda rejeitada.');

            return $lockedReview->refresh();
        });
    }

    private function reviewHasStatus(RentManualReview $review, RentManualReviewStatus $expected): bool
    {
        $status = $review->getAttribute('status');

        return $status === $expected || $status === $expected->value;
    }
}
