<?php

namespace App\Services\Finance;

use App\Enums\FinancialTransactionType;
use App\Enums\PaymentAllocationStatus;
use App\Enums\RentInstallmentStatus;
use App\Models\FinancialTransaction;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Support\DecimalMoney;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinancialTransactionService
{
    public function record(
        TenantFinancialAccount $account,
        FinancialTransactionType $type,
        int|string $amount,
        ?Model $transactionable,
        ?User $actor,
        ?string $description = null,
    ): FinancialTransaction {
        $account = $this->recalculateAccount($account);

        return FinancialTransaction::query()->create([
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $account->lease_contract_id,
            'user_id' => $account->user_id,
            'transaction_type' => $type,
            'amount' => DecimalMoney::normalize($amount),
            'balance_after' => $account->current_balance,
            'currency' => $account->currency,
            'description' => $description,
            'transactionable_type' => $transactionable?->getMorphClass(),
            'transactionable_id' => $transactionable?->getKey(),
            'occurred_at' => now(),
            'created_by' => $actor?->id,
        ]);
    }

    public function recalculateAccount(TenantFinancialAccount $account): TenantFinancialAccount
    {
        return DB::transaction(function () use ($account): TenantFinancialAccount {
            $lockedAccount = TenantFinancialAccount::query()
                ->whereKey($account->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $installments = $lockedAccount->rentInstallments()
                ->whereNotIn('status', [RentInstallmentStatus::Cancelled->value])
                ->get(['status', 'amount_due', 'amount_outstanding', 'amount_waived']);

            $totalIssued = DecimalMoney::sum($installments->pluck('amount_due'));
            $totalPaid = DecimalMoney::normalize(
                (string) $lockedAccount->leasePayments()
                    ->whereIn('status', ['confirmed', 'partially_allocated', 'allocated'])
                    ->sum('amount'),
            );
            $totalWaived = DecimalMoney::sum($installments->pluck('amount_waived'));
            $totalOverdue = DecimalMoney::sum(
                $installments
                    ->filter(fn ($installment): bool => $installment->status === RentInstallmentStatus::Overdue)
                    ->pluck('amount_outstanding'),
            );
            $allocated = DecimalMoney::normalize(
                (string) $lockedAccount->leasePayments()
                    ->join('payment_allocations', 'lease_payments.id', '=', 'payment_allocations.lease_payment_id')
                    ->where('payment_allocations.status', PaymentAllocationStatus::Active->value)
                    ->sum('payment_allocations.amount'),
            );
            $balance = DecimalMoney::max(
                0,
                DecimalMoney::subtract(
                    DecimalMoney::subtract($totalIssued, $allocated),
                    $totalWaived,
                ),
            );

            $lockedAccount->forceFill([
                'total_issued' => $totalIssued,
                'total_paid' => $totalPaid,
                'total_overdue' => $totalOverdue,
                'total_waived' => $totalWaived,
                'current_balance' => $balance,
                'next_due_date' => $lockedAccount->rentInstallments()
                    ->where('amount_outstanding', '>', 0)
                    ->whereNotIn('status', [RentInstallmentStatus::Cancelled->value, RentInstallmentStatus::Waived->value])
                    ->orderBy('due_date')
                    ->value('due_date'),
            ])->save();

            return $lockedAccount->refresh();
        });
    }
}
