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
        $created = 0;

        $account->rentInstallments()
            ->where('amount_outstanding', '>', 0)
            ->whereDate('due_date', '<', today())
            ->whereNotIn('status', [RentInstallmentStatus::Paid->value, RentInstallmentStatus::Cancelled->value, RentInstallmentStatus::Waived->value])
            ->each(function (RentInstallment $installment) use ($actor, &$created) {
                $arrear = $this->createOrUpdate($installment, $actor);
                $created += $arrear->wasRecentlyCreated ? 1 : 0;
            });

        $this->transactions->recalculateAccount($account);

        return $created;
    }

    public function createOrUpdate(RentInstallment $installment, User $actor): Arrear
    {
        $days = max(0, today()->diffInDays($installment->due_date, true));
        $arrear = Arrear::query()->firstOrNew(['rent_installment_id' => $installment->id]);
        $arrear->forceFill([
            'tenant_financial_account_id' => $installment->tenant_financial_account_id,
            'lease_contract_id' => $installment->lease_contract_id,
            'user_id' => $installment->user_id,
            'status' => $arrear->exists ? $arrear->status : ArrearStatus::Open,
            'original_amount' => $installment->amount_due,
            'outstanding_amount' => $installment->amount_outstanding,
            'overdue_since' => $installment->due_date,
            'days_overdue' => $days,
            'detected_at' => $arrear->detected_at ?? now(),
            'created_by' => $arrear->exists ? $arrear->created_by : $actor->id,
            'updated_by' => $actor->id,
        ])->save();

        $installment->forceFill([
            'status' => RentInstallmentStatus::Overdue,
            'overdue_at' => $installment->overdue_at ?? now(),
            'updated_by' => $actor->id,
        ])->save();

        if ($arrear->wasRecentlyCreated) {
            $this->transactions->record($this->accountForInstallment($installment), FinancialTransactionType::ArrearDetected, (float) $installment->amount_outstanding, $arrear, $actor, 'Incumprimento detetado.');
            $this->auditLogger->record(AuditEvents::CREATE, $arrear, 'finance', 'arrear_detect', 'Incumprimento de renda detetado.');
            $this->notifications->arrearDetected($arrear->refresh(), $actor);
        }

        return $arrear->refresh();
    }

    public function close(Arrear $arrear, User $actor, ?string $notes = null): Arrear
    {
        $arrear->forceFill([
            'status' => ArrearStatus::Closed,
            'closed_at' => now(),
            'notes' => $notes,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $arrear, 'finance', 'arrear_close', 'Incumprimento fechado.');

        return $arrear->refresh();
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
}
