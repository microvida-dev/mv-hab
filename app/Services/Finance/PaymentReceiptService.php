<?php

namespace App\Services\Finance;

use App\Enums\PaymentReceiptStatus;
use App\Models\LeasePayment;
use App\Models\PaymentReceipt;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptService
{
    public function __construct(
        private readonly FinanceNumberService $numbers,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function issue(LeasePayment $payment, User $actor, ?string $notes = null): PaymentReceipt
    {
        $storedPath = null;

        try {
            return DB::transaction(function () use ($payment, $actor, $notes, &$storedPath): PaymentReceipt {
                $lockedPayment = LeasePayment::query()
                    ->whereKey($payment->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! DecimalMoney::isPositive((string) $lockedPayment->allocated_amount)) {
                    throw ValidationException::withMessages(['payment' => 'Só é possível emitir comprovativo após imputação do pagamento.']);
                }

                $existing = $lockedPayment->receipt()
                    ->whereIn('status', [PaymentReceiptStatus::Issued->value, PaymentReceiptStatus::Reissued->value])
                    ->first();

                if ($existing instanceof PaymentReceipt) {
                    return $existing;
                }

                $receipt = new PaymentReceipt;
                $receipt->forceFill([
                    'lease_payment_id' => $lockedPayment->id,
                    'tenant_financial_account_id' => $lockedPayment->tenant_financial_account_id,
                    'lease_contract_id' => $lockedPayment->lease_contract_id,
                    'user_id' => $lockedPayment->user_id,
                    'receipt_number' => $this->numbers->receiptNumber(),
                    'status' => PaymentReceiptStatus::Issued,
                    'issued_at' => now(),
                    'total_amount' => $lockedPayment->allocated_amount,
                    'currency' => $lockedPayment->currency,
                    'mime_type' => 'text/html',
                    'notes' => $notes,
                    'issued_by' => $actor->id,
                ])->save();

                $html = view('backoffice.finance.receipts.document', [
                    'receipt' => $receipt->load('leasePayment.allocations.rentInstallment', 'leaseContract', 'tenant'),
                ])->render();
                $storedPath = 'finance/receipts/'.$receipt->id.'/'.$receipt->receipt_number.'.html';
                Storage::disk('local')->put($storedPath, $html);

                $receipt->forceFill([
                    'storage_disk' => 'local',
                    'storage_path' => $storedPath,
                    'checksum' => hash('sha256', $html),
                ])->save();

                $this->auditLogger->record(AuditEvents::CREATE, $receipt, 'finance', 'payment_receipt_issue', 'Comprovativo interno de pagamento emitido.');
                $this->notifications->paymentReceiptIssued($receipt->refresh(), $actor);

                return $receipt->refresh();
            });
        } catch (\Throwable $exception) {
            if (is_string($storedPath) && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function cancel(PaymentReceipt $receipt, User $actor, string $reason): PaymentReceipt
    {
        return DB::transaction(function () use ($receipt, $actor, $reason): PaymentReceipt {
            $lockedReceipt = PaymentReceipt::query()
                ->whereKey($receipt->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->receiptHasStatus($lockedReceipt, PaymentReceiptStatus::Cancelled)) {
                return $lockedReceipt;
            }

            $lockedReceipt->forceFill([
                'status' => PaymentReceiptStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancellation_reason' => $reason,
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $lockedReceipt, 'finance', 'payment_receipt_cancel', 'Comprovativo interno cancelado.');

            return $lockedReceipt->refresh();
        });
    }

    public function download(PaymentReceipt $receipt, User $actor): StreamedResponse
    {
        $disk = $receipt->storage_disk;
        $path = $receipt->storage_path;

        abort_if($disk === null || $path === null || ! Storage::disk($disk)->exists($path), 404);

        $this->auditLogger->record(
            AuditEvents::ACCESS,
            $receipt,
            'finance',
            'payment_receipt_download',
            'Comprovativo interno de pagamento descarregado.',
        );

        return Storage::disk($disk)->download($path, $receipt->receipt_number.'.html');
    }

    private function receiptHasStatus(PaymentReceipt $receipt, PaymentReceiptStatus $expected): bool
    {
        $status = $receipt->getAttribute('status');

        return $status === $expected || $status === $expected->value;
    }
}
