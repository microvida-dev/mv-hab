<?php

namespace App\Services\Finance;

use App\Enums\FinancialTransactionType;
use App\Enums\RentReviewStatus;
use App\Enums\RentReviewType;
use App\Models\Contract;
use App\Models\Household;
use App\Models\IncomeChangeDeclaration;
use App\Models\RentReview;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentReviewService
{
    public function __construct(
        private readonly RentScheduleService $schedules,
        private readonly FinancialTransactionService $transactions,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function store(TenantFinancialAccount $account, User $actor, array $data): RentReview
    {
        $contract = $this->contractForAccount($account);

        $review = new RentReview;
        $review->forceFill([
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $account->lease_contract_id,
            'user_id' => $account->user_id,
            'household_id' => $account->household_id,
            'requested_by' => $actor->id,
            'status' => RentReviewStatus::Requested,
            'review_type' => $this->reviewTypeFromData($data),
            'current_rent' => $contract->monthly_rent,
            'proposed_rent' => $data['proposed_rent'] ?? null,
            'effective_from' => $data['effective_from'] ?? now()->addMonth()->startOfMonth(),
            'requested_at' => now(),
            'reason' => $data['reason'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $review, 'finance', 'rent_review_create', 'Revisão de renda criada.');
        $this->notifications->rentReviewRequested($review->refresh(), $actor);

        return $review->refresh();
    }

    public function createFromIncomeChange(IncomeChangeDeclaration $declaration, User $actor): RentReview
    {
        $review = $this->store($this->accountForDeclaration($declaration), $actor, [
            'review_type' => RentReviewType::IncomeChange->value,
            'reason' => 'Revisão aberta a partir de declaração de alteração de rendimentos.',
            'proposed_rent' => null,
        ]);

        $declaration->forceFill(['rent_review_id' => $review->id])->save();

        return $review;
    }

    public function calculate(RentReview $review, User $actor, ?float $proposedRent = null): RentReview
    {
        $account = $this->accountForReview($review);
        $household = $account->household;
        $household = $household instanceof Household ? $household : null;
        $monthlyIncome = $household ? (float) $household->incomeRecords()->sum('monthly_amount') : 0.0;
        $proposedRent = $proposedRent ?? (float) ($review->proposed_rent ?: $review->current_rent);

        $review->forceFill([
            'status' => RentReviewStatus::Calculated,
            'proposed_rent' => $proposedRent,
            'calculated_at' => now(),
            'reviewed_by' => $actor->id,
            'income_snapshot' => [
                'monthly_household_income' => $monthlyIncome,
                'household_id' => $household?->id,
            ],
            'calculation_snapshot' => [
                'method' => 'manual_review_without_external_integrations',
                'current_rent' => (float) $review->current_rent,
                'proposed_rent' => $proposedRent,
            ],
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $review, 'finance', 'rent_review_calculate', 'Revisão de renda calculada manualmente.');

        return $review->refresh();
    }

    public function approve(RentReview $review, User $actor, float $approvedRent): RentReview
    {
        if ($approvedRent <= 0) {
            throw ValidationException::withMessages(['approved_rent' => 'A renda aprovada tem de ser superior a zero.']);
        }

        $review->forceFill([
            'status' => RentReviewStatus::Approved,
            'approved_rent' => $approvedRent,
            'approved_at' => now(),
            'approved_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $review, 'finance', 'rent_review_approve', 'Revisão de renda aprovada.');

        return $review->refresh();
    }

    public function reject(RentReview $review, User $actor, string $reason): RentReview
    {
        $review->forceFill([
            'status' => RentReviewStatus::Rejected,
            'rejected_at' => now(),
            'reviewed_by' => $actor->id,
            'rejection_reason' => $reason,
        ])->save();

        $this->auditLogger->record(AuditEvents::REJECT, $review, 'finance', 'rent_review_reject', 'Revisão de renda rejeitada.');

        return $review->refresh();
    }

    public function apply(RentReview $review, User $actor): RentReview
    {
        return DB::transaction(function () use ($review, $actor) {
            if (! $this->isApprovedStatus($review) || ! $review->approved_rent) {
                throw ValidationException::withMessages(['review' => 'A revisão tem de estar aprovada antes de ser aplicada.']);
            }

            $contract = $this->contractForReview($review);
            $schedule = $this->schedules->generateForContract($contract, $actor, [
                'starts_on' => $review->effective_from ?? now()->addMonth()->startOfMonth(),
                'ends_on' => $contract->end_date,
                'monthly_rent' => $review->approved_rent,
                'payment_day' => $contract->payment_day ?? 8,
                'schedule_type' => 'rent_review',
                'source_rent_review_id' => $review->id,
            ]);

            $contract->forceFill([
                'monthly_rent' => $review->approved_rent,
                'updated_by' => $actor->id,
            ])->save();

            $review->forceFill([
                'status' => RentReviewStatus::Applied,
                'new_rent_schedule_id' => $schedule->id,
                'applied_at' => now(),
                'applied_by' => $actor->id,
            ])->save();

            $this->transactions->record($this->accountForReview($review), FinancialTransactionType::RentReviewApplied, 0, $review, $actor, 'Revisão de renda aplicada.');
            $this->auditLogger->record(AuditEvents::UPDATE, $review, 'finance', 'rent_review_apply', 'Revisão de renda aplicada ao contrato.');
            $this->notifications->rentReviewApplied($review->refresh(), $actor);

            return $review->refresh();
        });
    }

    private function contractForAccount(TenantFinancialAccount $account): Contract
    {
        $contract = $account->leaseContract;

        if (! $contract instanceof Contract) {
            throw ValidationException::withMessages([
                'contract' => 'A conta financeira não tem contrato associado.',
            ]);
        }

        return $contract;
    }

    private function accountForDeclaration(IncomeChangeDeclaration $declaration): TenantFinancialAccount
    {
        $account = $declaration->tenantFinancialAccount;

        if (! $account instanceof TenantFinancialAccount) {
            throw ValidationException::withMessages([
                'tenant_financial_account' => 'A declaração não tem conta financeira associada.',
            ]);
        }

        return $account;
    }

    private function accountForReview(RentReview $review): TenantFinancialAccount
    {
        $account = $review->tenantFinancialAccount;

        if (! $account instanceof TenantFinancialAccount) {
            throw ValidationException::withMessages([
                'tenant_financial_account' => 'A revisão não tem conta financeira associada.',
            ]);
        }

        return $account;
    }

    private function contractForReview(RentReview $review): Contract
    {
        $contract = $review->leaseContract;

        if (! $contract instanceof Contract) {
            throw ValidationException::withMessages([
                'contract' => 'A revisão não tem contrato associado.',
            ]);
        }

        return $contract;
    }

    private function isApprovedStatus(RentReview $review): bool
    {
        $status = $review->getAttribute('status');

        return $status === RentReviewStatus::Approved
            || $status === RentReviewStatus::Approved->value;
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    private function reviewTypeFromData(array $data): RentReviewType
    {
        $value = $data['review_type'] ?? RentReviewType::Annual->value;

        if (! is_int($value) && ! is_string($value)) {
            throw ValidationException::withMessages([
                'review_type' => 'Tipo de revisão inválido.',
            ]);
        }

        return RentReviewType::from($value);
    }
}
