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
use App\Support\DecimalMoney;
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
     * @param  array<string, bool|int|string|null>  $data
     */
    public function store(TenantFinancialAccount $account, User $actor, array $data): RentReview
    {
        return DB::transaction(function () use ($account, $actor, $data): RentReview {
            $lockedAccount = TenantFinancialAccount::query()
                ->whereKey($account->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $contract = $this->contractForAccount($lockedAccount);

            $review = new RentReview;
            $review->forceFill([
                'tenant_financial_account_id' => $lockedAccount->id,
                'lease_contract_id' => $lockedAccount->lease_contract_id,
                'user_id' => $lockedAccount->user_id,
                'household_id' => $lockedAccount->household_id,
                'requested_by' => $actor->id,
                'status' => RentReviewStatus::Requested,
                'review_type' => $this->reviewTypeFromData($data),
                'current_rent' => DecimalMoney::normalize((string) $contract->monthly_rent),
                'proposed_rent' => isset($data['proposed_rent']) ? DecimalMoney::normalize((string) $data['proposed_rent']) : null,
                'effective_from' => $data['effective_from'] ?? now()->addMonth()->startOfMonth(),
                'requested_at' => now(),
                'reason' => $data['reason'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ])->save();

            $this->auditLogger->record(AuditEvents::CREATE, $review, 'finance', 'rent_review_create', 'Revisão de renda criada.');
            $this->notifications->rentReviewRequested($review->refresh(), $actor);

            return $review->refresh();
        });
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

    public function calculate(RentReview $review, User $actor, int|string|null $proposedRent = null): RentReview
    {
        return DB::transaction(function () use ($review, $actor, $proposedRent): RentReview {
            $lockedReview = RentReview::query()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $account = $this->accountForReview($lockedReview);
            $household = $account->household;
            $household = $household instanceof Household ? $household : null;
            $monthlyIncome = DecimalMoney::normalize(
                (string) ($household?->incomeRecords()->sum('monthly_amount') ?? '0'),
            );
            $rentCandidate = $proposedRent
                ?? ($lockedReview->proposed_rent !== null
                    ? (string) $lockedReview->proposed_rent
                    : (string) $lockedReview->current_rent);
            $resolvedRent = DecimalMoney::normalize(
                $rentCandidate,
            );

            $lockedReview->forceFill([
                'status' => RentReviewStatus::Calculated,
                'proposed_rent' => $resolvedRent,
                'calculated_at' => now(),
                'reviewed_by' => $actor->id,
                'income_snapshot' => [
                    'monthly_household_income' => $monthlyIncome,
                    'household_id' => $household?->id,
                ],
                'calculation_snapshot' => [
                    'method' => 'manual_review_without_external_integrations',
                    'current_rent' => DecimalMoney::normalize((string) $lockedReview->current_rent),
                    'proposed_rent' => $resolvedRent,
                ],
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $lockedReview, 'finance', 'rent_review_calculate', 'Revisão de renda calculada manualmente.');

            return $lockedReview->refresh();
        });
    }

    public function approve(RentReview $review, User $actor, int|string $approvedRent): RentReview
    {
        $approved = DecimalMoney::normalize($approvedRent);
        if (! DecimalMoney::isPositive($approved)) {
            throw ValidationException::withMessages(['approved_rent' => 'A renda aprovada tem de ser superior a zero.']);
        }

        return DB::transaction(function () use ($review, $actor, $approved): RentReview {
            $lockedReview = RentReview::query()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($this->reviewHasStatus($lockedReview, RentReviewStatus::Approved)) {
                return $lockedReview;
            }

            $lockedReview->forceFill([
                'status' => RentReviewStatus::Approved,
                'approved_rent' => $approved,
                'approved_at' => now(),
                'approved_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::APPROVE, $lockedReview, 'finance', 'rent_review_approve', 'Revisão de renda aprovada.');

            return $lockedReview->refresh();
        });
    }

    public function reject(RentReview $review, User $actor, string $reason): RentReview
    {
        return DB::transaction(function () use ($review, $actor, $reason): RentReview {
            $lockedReview = RentReview::query()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($this->reviewHasStatus($lockedReview, RentReviewStatus::Rejected)) {
                return $lockedReview;
            }

            $lockedReview->forceFill([
                'status' => RentReviewStatus::Rejected,
                'rejected_at' => now(),
                'reviewed_by' => $actor->id,
                'rejection_reason' => $reason,
            ])->save();

            $this->auditLogger->record(AuditEvents::REJECT, $lockedReview, 'finance', 'rent_review_reject', 'Revisão de renda rejeitada.');

            return $lockedReview->refresh();
        });
    }

    public function apply(RentReview $review, User $actor): RentReview
    {
        return DB::transaction(function () use ($review, $actor) {
            $lockedReview = RentReview::query()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($this->reviewHasStatus($lockedReview, RentReviewStatus::Applied)) {
                return $lockedReview;
            }

            if (
                ! $this->isApprovedStatus($lockedReview)
                || ! DecimalMoney::isPositive(
                    $lockedReview->approved_rent === null ? null : (string) $lockedReview->approved_rent,
                )
            ) {
                throw ValidationException::withMessages(['review' => 'A revisão tem de estar aprovada antes de ser aplicada.']);
            }

            $contract = $this->contractForReview($lockedReview);
            $schedule = $this->schedules->generateForContract($contract, $actor, [
                'starts_on' => $lockedReview->effective_from ?? now()->addMonth()->startOfMonth(),
                'ends_on' => $contract->end_date,
                'monthly_rent' => $lockedReview->approved_rent,
                'payment_day' => $contract->payment_day ?? 8,
                'schedule_type' => 'rent_review',
                'source_rent_review_id' => $lockedReview->id,
            ]);

            $contract->forceFill([
                'monthly_rent' => $lockedReview->approved_rent,
                'updated_by' => $actor->id,
            ])->save();

            $lockedReview->forceFill([
                'status' => RentReviewStatus::Applied,
                'new_rent_schedule_id' => $schedule->id,
                'applied_at' => now(),
                'applied_by' => $actor->id,
            ])->save();

            $this->transactions->record($this->accountForReview($lockedReview), FinancialTransactionType::RentReviewApplied, 0, $lockedReview, $actor, 'Revisão de renda aplicada.');
            $this->auditLogger->record(AuditEvents::UPDATE, $lockedReview, 'finance', 'rent_review_apply', 'Revisão de renda aplicada ao contrato.');
            $this->notifications->rentReviewApplied($lockedReview->refresh(), $actor);

            return $lockedReview->refresh();
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
        return $this->reviewHasStatus($review, RentReviewStatus::Approved);
    }

    private function reviewHasStatus(RentReview $review, RentReviewStatus $expected): bool
    {
        $status = $review->getAttribute('status');

        return $status === $expected || $status === $expected->value;
    }

    /**
     * @param  array<string, bool|int|string|null>  $data
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
