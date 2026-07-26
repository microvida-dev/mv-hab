<?php

namespace App\Services\Finance;

use App\Enums\ArrearStatus;
use App\Enums\FinancialTransactionType;
use App\Enums\RentInstallmentStatus;
use App\Models\Arrear;
use App\Models\RentInstallment;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArrearDetectionService
{
    public function __construct(
        private readonly FinancialTransactionService $transactions,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function detectForAccount(TenantFinancialAccount $account, User $actor): int
    {
        return DB::transaction(function () use ($account, $actor): int {
            $lockedAccount = TenantFinancialAccount::query()
                ->whereKey($account->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $created = 0;

            $lockedAccount->rentInstallments()
                ->where('amount_outstanding', '>', 0)
                ->whereDate('due_date', '<', today())
                ->whereNotIn('status', [RentInstallmentStatus::Paid->value, RentInstallmentStatus::Cancelled->value, RentInstallmentStatus::Waived->value])
                ->lockForUpdate()
                ->each(function (RentInstallment $installment) use ($actor, &$created): void {
                    $arrear = $this->createOrUpdate($installment, $actor);
                    $created += $arrear->wasRecentlyCreated ? 1 : 0;
                });

            $this->transactions->recalculateAccount($lockedAccount);

            return $created;
        });
    }

    public function createOrUpdate(RentInstallment $installment, User $actor): Arrear
    {
        return DB::transaction(function () use ($installment, $actor): Arrear {
            $lockedInstallment = RentInstallment::query()
                ->whereKey($installment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $days = max(0, (int) today()->diffInDays($lockedInstallment->due_date, true));
            $arrear = Arrear::query()
                ->where('rent_installment_id', $lockedInstallment->id)
                ->lockForUpdate()
                ->first() ?? new Arrear(['rent_installment_id' => $lockedInstallment->id]);
            $arrear->forceFill([
                'tenant_financial_account_id' => $lockedInstallment->tenant_financial_account_id,
                'lease_contract_id' => $lockedInstallment->lease_contract_id,
                'user_id' => $lockedInstallment->user_id,
                'status' => $arrear->exists ? $arrear->status : ArrearStatus::Open,
                'original_amount' => $lockedInstallment->amount_due,
                'outstanding_amount' => $lockedInstallment->amount_outstanding,
                'overdue_since' => $lockedInstallment->due_date,
                'days_overdue' => $days,
                'detected_at' => $arrear->detected_at ?? now(),
                'created_by' => $arrear->exists ? $arrear->created_by : $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            $lockedInstallment->forceFill([
                'status' => RentInstallmentStatus::Overdue,
                'overdue_at' => $lockedInstallment->overdue_at ?? now(),
                'updated_by' => $actor->id,
            ])->save();

            if ($arrear->wasRecentlyCreated) {
                $this->transactions->record(
                    $this->accountForInstallment($lockedInstallment),
                    FinancialTransactionType::ArrearDetected,
                    DecimalMoney::normalize((string) $lockedInstallment->amount_outstanding),
                    $arrear,
                    $actor,
                    'Incumprimento detetado.',
                );
                $this->auditLogger->record(AuditEvents::CREATE, $arrear, 'finance', 'arrear_detect', 'Incumprimento de renda detetado.');
                $this->notifications->arrearDetected($arrear->refresh(), $actor);
            }

            return $arrear->refresh();
        });
    }

    public function close(Arrear $arrear, User $actor, ?string $notes = null): Arrear
    {
        return DB::transaction(function () use ($arrear, $actor, $notes): Arrear {
            $lockedArrear = Arrear::query()
                ->whereKey($arrear->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($this->arrearHasStatus($lockedArrear, ArrearStatus::Closed)) {
                return $lockedArrear;
            }

            $lockedArrear->forceFill([
                'status' => ArrearStatus::Closed,
                'closed_at' => now(),
                'notes' => $notes,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $lockedArrear, 'finance', 'arrear_close', 'Incumprimento fechado.');

            return $lockedArrear->refresh();
        });
    }

    private function accountForInstallment(RentInstallment $installment): TenantFinancialAccount
    {
        $account = $installment->tenantFinancialAccount;

        if (! $account instanceof TenantFinancialAccount) {
            throw ValidationException::withMessages([
                'tenant_financial_account' => 'A prestação não tem conta financeira associada.',
            ]);
        }

        return $account;
    }

    private function arrearHasStatus(Arrear $arrear, ArrearStatus $expected): bool
    {
        $status = $arrear->getAttribute('status');

        return $status === $expected || $status === $expected->value;
    }
}
