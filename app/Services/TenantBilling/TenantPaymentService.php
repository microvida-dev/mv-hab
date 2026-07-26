<?php

namespace App\Services\TenantBilling;

use App\Enums\TenantPaymentStatus;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantPaymentService
{
    public function __construct(
        private readonly TenantInvoiceService $invoices,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerForInvoice(TenantInvoice $invoice, User $actor, array $data): TenantPayment
    {
        return DB::transaction(function () use ($invoice, $actor, $data) {
            $lockedInvoice = TenantInvoice::query()
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $amount = DecimalMoney::normalize((string) $data['amount']);

            if (! DecimalMoney::isPositive($amount)) {
                throw ValidationException::withMessages(['amount' => 'O valor do pagamento tem de ser superior a zero.']);
            }

            $payment = new TenantPayment;
            $payment->forceFill([
                'tenant_invoice_id' => $lockedInvoice->id,
                'tenant_financial_account_id' => $lockedInvoice->tenant_financial_account_id,
                'lease_contract_id' => $lockedInvoice->lease_contract_id,
                'user_id' => $lockedInvoice->user_id,
                'payment_number' => $this->paymentNumber(),
                'status' => ($data['confirm_now'] ?? false) ? TenantPaymentStatus::Confirmed : TenantPaymentStatus::Registered,
                'amount' => $amount,
                'allocated_amount' => DecimalMoney::min($amount, (string) $lockedInvoice->amount_outstanding),
                'unallocated_amount' => DecimalMoney::max(
                    0,
                    DecimalMoney::subtract($amount, (string) $lockedInvoice->amount_outstanding),
                ),
                'currency' => $data['currency'] ?? 'EUR',
                'payment_date' => $data['payment_date'],
                'value_date' => $data['value_date'] ?? $data['payment_date'],
                'registered_at' => now(),
                'confirmed_at' => ($data['confirm_now'] ?? false) ? now() : null,
                'method' => $data['method'] ?? 'manual',
                'source' => $data['source'] ?? 'backoffice',
                'external_reference' => $data['external_reference'] ?? null,
                'payer_name' => $data['payer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'registered_by' => $actor->id,
                'confirmed_by' => ($data['confirm_now'] ?? false) ? $actor->id : null,
            ])->save();

            $this->invoices->markPaymentImpact($lockedInvoice);
            $this->auditLogger->record(AuditEvents::CREATE, $payment, 'payments', 'tenant_payment_registered', 'Pagamento operacional de inquilino registado.');

            return $payment->refresh();
        });
    }

    public function confirm(TenantPayment $payment, User $actor): TenantPayment
    {
        return DB::transaction(function () use ($payment, $actor): TenantPayment {
            $lockedPayment = TenantPayment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status === TenantPaymentStatus::Confirmed) {
                return $lockedPayment;
            }

            if (! in_array($lockedPayment->status, [TenantPaymentStatus::Registered, TenantPaymentStatus::Pending], true)) {
                throw ValidationException::withMessages(['payment' => 'Só pagamentos registados ou pendentes podem ser confirmados.']);
            }

            $lockedPayment->forceFill([
                'status' => TenantPaymentStatus::Confirmed,
                'confirmed_at' => now(),
                'confirmed_by' => $actor->id,
            ])->save();

            if ($lockedPayment->invoice) {
                $this->invoices->markPaymentImpact($lockedPayment->invoice);
            }

            $this->auditLogger->record(AuditEvents::APPROVE, $lockedPayment, 'payments', 'tenant_payment_confirmed', 'Pagamento operacional confirmado.');

            return $lockedPayment->refresh();
        });
    }

    private function paymentNumber(): string
    {
        return 'TPAY-'.now()->format('Ym').'-'.str_pad((string) (TenantPayment::query()->withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
