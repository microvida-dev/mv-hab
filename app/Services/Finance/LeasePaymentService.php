<?php

namespace App\Services\Finance;

use App\Enums\FinancialTransactionType;
use App\Enums\LeasePaymentStatus;
use App\Enums\PaymentAllocationStatus;
use App\Models\LeasePayment;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeasePaymentService
{
    public function __construct(
        private readonly FinanceNumberService $numbers,
        private readonly FinancialTransactionService $transactions,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, bool|int|string|null>  $data
     */
    public function store(TenantFinancialAccount $account, User $actor, array $data): LeasePayment
    {
        return DB::transaction(function () use ($account, $actor, $data) {
            $lockedAccount = TenantFinancialAccount::query()
                ->whereKey($account->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $amount = DecimalMoney::normalize((string) $data['amount']);

            if (! DecimalMoney::isPositive($amount)) {
                throw ValidationException::withMessages(['amount' => 'O valor do pagamento tem de ser superior a zero.']);
            }

            $payment = new LeasePayment;
            $payment->forceFill([
                'tenant_financial_account_id' => $lockedAccount->id,
                'lease_contract_id' => $lockedAccount->lease_contract_id,
                'user_id' => $lockedAccount->user_id,
                'status' => LeasePaymentStatus::Pending,
                'payment_number' => $this->numbers->paymentNumber(),
                'amount' => $amount,
                'allocated_amount' => DecimalMoney::normalize(0),
                'unallocated_amount' => $amount,
                'payment_date' => $data['payment_date'],
                'value_date' => $data['value_date'] ?? $data['payment_date'],
                'received_at' => now(),
                'method' => $data['method'] ?? 'manual',
                'source' => $data['source'] ?? 'backoffice',
                'external_reference' => $data['external_reference'] ?? null,
                'payer_name' => $data['payer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::CREATE, $payment, 'finance', 'lease_payment_store', 'Pagamento de renda registado.');

            if (($data['confirm_now'] ?? false) === true) {
                return $this->confirm($payment, $actor);
            }

            return $payment->refresh();
        });
    }

    public function confirm(LeasePayment $payment, User $actor): LeasePayment
    {
        return DB::transaction(function () use ($payment, $actor) {
            $lockedPayment = LeasePayment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->paymentHasStatus($lockedPayment, [
                LeasePaymentStatus::Confirmed,
                LeasePaymentStatus::PartiallyAllocated,
                LeasePaymentStatus::Allocated,
            ])) {
                return $lockedPayment;
            }

            if (! $this->paymentHasStatus($lockedPayment, [LeasePaymentStatus::Pending, LeasePaymentStatus::Draft])) {
                throw ValidationException::withMessages(['payment' => 'Só pagamentos pendentes podem ser confirmados.']);
            }

            $lockedPayment->forceFill([
                'status' => LeasePaymentStatus::Confirmed,
                'confirmed_at' => now(),
                'confirmed_by' => $actor->id,
            ])->save();

            $account = $this->accountForPayment($lockedPayment);
            $this->transactions->record(
                $account,
                FinancialTransactionType::PaymentReceived,
                DecimalMoney::negate((string) $lockedPayment->amount),
                $lockedPayment,
                $actor,
                'Pagamento confirmado.',
            );
            $this->auditLogger->record(AuditEvents::APPROVE, $lockedPayment, 'finance', 'lease_payment_confirm', 'Pagamento de renda confirmado manualmente.');
            $this->notifications->leasePaymentRegistered($lockedPayment->refresh(), $actor);

            return $lockedPayment->refresh();
        });
    }

    public function reverse(LeasePayment $payment, User $actor, string $reason): LeasePayment
    {
        return DB::transaction(function () use ($payment, $actor, $reason) {
            $lockedPayment = LeasePayment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->paymentHasStatus($lockedPayment, [LeasePaymentStatus::Reversed])) {
                throw ValidationException::withMessages(['payment' => 'O pagamento já está estornado.']);
            }

            if (! $this->paymentHasStatus($lockedPayment, [
                LeasePaymentStatus::Confirmed,
                LeasePaymentStatus::PartiallyAllocated,
                LeasePaymentStatus::Allocated,
            ])) {
                throw ValidationException::withMessages(['payment' => 'Só pagamentos confirmados podem ser estornados.']);
            }

            foreach ($lockedPayment->allocations()->where('status', PaymentAllocationStatus::Active)->lockForUpdate()->get() as $allocation) {
                app(PaymentAllocationService::class)->reverse($allocation, $actor, $reason);
            }

            $lockedPayment->forceFill([
                'status' => LeasePaymentStatus::Reversed,
                'reversed_at' => now(),
                'reversed_by' => $actor->id,
                'reversal_reason' => $reason,
            ])->save();

            $this->transactions->record(
                $this->accountForPayment($lockedPayment),
                FinancialTransactionType::PaymentReversed,
                DecimalMoney::normalize((string) $lockedPayment->amount),
                $lockedPayment,
                $actor,
                'Pagamento estornado.',
            );
            $this->auditLogger->record(AuditEvents::UPDATE, $lockedPayment, 'finance', 'lease_payment_reverse', 'Pagamento de renda estornado.');

            return $lockedPayment->refresh();
        });
    }

    private function accountForPayment(LeasePayment $payment): TenantFinancialAccount
    {
        $account = $payment->tenantFinancialAccount;

        if (! $account instanceof TenantFinancialAccount) {
            throw ValidationException::withMessages([
                'tenant_financial_account' => 'O pagamento não tem conta financeira associada.',
            ]);
        }

        return $account;
    }

    /**
     * @param  array<int, LeasePaymentStatus>  $statuses
     */
    private function paymentHasStatus(LeasePayment $payment, array $statuses): bool
    {
        $status = $payment->getAttribute('status');

        foreach ($statuses as $expected) {
            if ($status === $expected || $status === $expected->value) {
                return true;
            }
        }

        return false;
    }
}
