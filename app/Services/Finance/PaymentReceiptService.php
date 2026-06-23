<?php

namespace App\Services\Finance;

use App\Enums\PaymentReceiptStatus;
use App\Models\LeasePayment;
use App\Models\PaymentReceipt;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentReceiptService
{
    public function __construct(
        private readonly FinanceNumberService $numbers,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function issue(LeasePayment $payment, User $actor, ?string $notes = null): PaymentReceipt
    {
        if ((float) $payment->allocated_amount <= 0) {
            throw ValidationException::withMessages(['payment' => 'Só é possível emitir comprovativo após imputação do pagamento.']);
        }

        $existing = $payment->receipt()
            ->whereIn('status', [PaymentReceiptStatus::Issued->value, PaymentReceiptStatus::Reissued->value])
            ->first();

        if ($existing) {
            return $existing;
        }

        $receipt = new PaymentReceipt;
        $receipt->forceFill([
            'lease_payment_id' => $payment->id,
            'tenant_financial_account_id' => $payment->tenant_financial_account_id,
            'lease_contract_id' => $payment->lease_contract_id,
            'user_id' => $payment->user_id,
            'receipt_number' => $this->numbers->receiptNumber(),
            'status' => PaymentReceiptStatus::Issued,
            'issued_at' => now(),
            'total_amount' => $payment->allocated_amount,
            'currency' => $payment->currency,
            'mime_type' => 'text/html',
            'notes' => $notes,
            'issued_by' => $actor->id,
        ])->save();

        $html = view('backoffice.finance.receipts.document', ['receipt' => $receipt->load('leasePayment.allocations.rentInstallment', 'leaseContract', 'tenant')])->render();
        $path = 'finance/receipts/'.$receipt->id.'/'.$receipt->receipt_number.'.html';
        Storage::disk('local')->put($path, $html);

        $receipt->forceFill([
            'storage_disk' => 'local',
            'storage_path' => $path,
            'checksum' => hash('sha256', $html),
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $receipt, 'finance', 'payment_receipt_issue', 'Comprovativo interno de pagamento emitido.');
        $this->notifications->paymentReceiptIssued($receipt->refresh(), $actor);

        return $receipt->refresh();
    }

    public function cancel(PaymentReceipt $receipt, User $actor, string $reason): PaymentReceipt
    {
        $receipt->forceFill([
            'status' => PaymentReceiptStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'cancellation_reason' => $reason,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $receipt, 'finance', 'payment_receipt_cancel', 'Comprovativo interno cancelado.');

        return $receipt->refresh();
    }
}
