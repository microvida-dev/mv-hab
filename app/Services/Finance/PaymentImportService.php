<?php

namespace App\Services\Finance;

use App\Enums\PaymentImportRowStatus;
use App\Enums\PaymentImportStatus;
use App\Models\PaymentImportBatch;
use App\Models\PaymentImportRow;
use App\Models\RentInstallment;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentImportService
{
    public function __construct(
        private readonly FinanceNumberService $numbers,
        private readonly LeasePaymentService $payments,
        private readonly PaymentAllocationService $allocations,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(UploadedFile $file, User $actor, ?string $notes = null): PaymentImportBatch
    {
        $batch = new PaymentImportBatch;
        $batch->forceFill([
            'batch_number' => $this->numbers->importBatchNumber(),
            'status' => PaymentImportStatus::Draft,
            'original_filename' => $file->getClientOriginalName(),
            'storage_disk' => 'local',
            'notes' => $notes,
            'created_by' => $actor->id,
        ])->save();

        $path = $file->storeAs('finance/imports/'.$batch->id, $batch->batch_number.'.csv', 'local');
        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => 'Não foi possível guardar o ficheiro de importação.',
            ]);
        }

        $batch->forceFill(['storage_path' => $path])->save();

        $contents = Storage::disk('local')->get($path) ?? '';
        $rows = preg_split('/\R/', trim($contents)) ?: [];
        $rowNumber = 0;

        foreach ($rows as $line) {
            $rowNumber++;
            $columns = str_getcsv($line);
            if ($rowNumber === 1 && in_array('reference', $columns, true)) {
                continue;
            }

            [$reference, $amount, $paymentDate, $payerName] = array_pad($columns, 4, null);
            PaymentImportRow::query()->create([
                'payment_import_batch_id' => $batch->id,
                'status' => PaymentImportRowStatus::Pending,
                'row_number' => $rowNumber,
                'reference' => $reference,
                'amount' => is_numeric($amount) ? $amount : null,
                'payment_date' => $paymentDate ?: null,
                'payer_name' => $payerName,
                'raw_payload' => $columns,
            ]);
        }

        $batch->forceFill(['row_count' => $batch->rows()->count()])->save();
        $this->auditLogger->record(AuditEvents::CREATE, $batch, 'finance', 'payment_import_store', 'Lote CSV de pagamentos registado.');

        return $batch->refresh();
    }

    public function process(PaymentImportBatch $batch, User $actor): PaymentImportBatch
    {
        return DB::transaction(function () use ($batch, $actor) {
            $batch->forceFill(['status' => PaymentImportStatus::Processing])->save();

            foreach ($batch->rows()->where('status', PaymentImportRowStatus::Pending)->get() as $row) {
                /** @var PaymentImportRow $row */
                $installment = RentInstallment::query()->where('reference', $row->reference)->first();

                if (! $installment || ! $row->amount || ! $row->payment_date) {
                    $row->forceFill(['status' => PaymentImportRowStatus::Unmatched, 'failure_reason' => 'Referência, valor ou data inválidos.'])->save();

                    continue;
                }

                $account = $installment->tenantFinancialAccount;

                if (! $account instanceof TenantFinancialAccount) {
                    $row->forceFill(['status' => PaymentImportRowStatus::Failed, 'failure_reason' => 'Prestação sem conta financeira associada.'])->save();

                    continue;
                }

                $payment = $this->payments->store($account, $actor, [
                    'amount' => (float) $row->amount,
                    'payment_date' => $this->dateString($row),
                    'method' => 'bank_import',
                    'source' => 'csv_import',
                    'external_reference' => $row->reference,
                    'payer_name' => $row->payer_name,
                    'confirm_now' => true,
                ]);
                $this->allocations->allocate($payment, $installment, $actor, min((float) $payment->unallocated_amount, (float) $installment->amount_outstanding));

                $row->forceFill([
                    'status' => PaymentImportRowStatus::Imported,
                    'lease_payment_id' => $payment->id,
                    'rent_installment_id' => $installment->id,
                    'tenant_financial_account_id' => $installment->tenant_financial_account_id,
                    'user_id' => $installment->user_id,
                ])->save();
            }

            $imported = $batch->rows()->where('status', PaymentImportRowStatus::Imported)->count();
            $failed = $batch->rows()->whereIn('status', [PaymentImportRowStatus::Unmatched, PaymentImportRowStatus::Failed])->count();
            $batch->forceFill([
                'status' => $failed > 0 ? PaymentImportStatus::PartiallyProcessed : PaymentImportStatus::Processed,
                'matched_count' => $imported,
                'imported_count' => $imported,
                'failed_count' => $failed,
                'processed_at' => now(),
                'processed_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $batch, 'finance', 'payment_import_process', 'Lote CSV de pagamentos processado.');

            return $batch->refresh();
        });
    }

    private function dateString(PaymentImportRow $row): string
    {
        $paymentDate = $row->getAttribute('payment_date');

        return $paymentDate instanceof CarbonInterface
            ? $paymentDate->toDateString()
            : (string) $paymentDate;
    }
}
