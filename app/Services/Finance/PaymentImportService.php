<?php

namespace App\Services\Finance;

use App\Enums\LeasePaymentStatus;
use App\Enums\PaymentImportRowStatus;
use App\Enums\PaymentImportStatus;
use App\Models\LeasePayment;
use App\Models\PaymentImportBatch;
use App\Models\PaymentImportRow;
use App\Models\RentInstallment;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
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
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function store(UploadedFile $file, User $actor, ?string $notes = null): PaymentImportBatch
    {
        if ($actor->municipality_id === null) {
            throw ValidationException::withMessages([
                'file' => 'É necessário um Município explícito para importar pagamentos.',
            ]);
        }

        $this->assertNotDuplicate($file, $actor);
        $storedPath = null;

        try {
            return DB::transaction(function () use ($file, $actor, $notes, &$storedPath): PaymentImportBatch {
                $batch = new PaymentImportBatch;
                $batch->forceFill([
                    'municipality_id' => $actor->municipality_id,
                    'batch_number' => $this->numbers->importBatchNumber(),
                    'status' => PaymentImportStatus::Draft,
                    'original_filename' => $file->getClientOriginalName(),
                    'storage_disk' => 'local',
                    'notes' => $notes,
                    'created_by' => $actor->id,
                ])->save();

                $storedPath = $file->storeAs('finance/imports/'.$batch->id, $batch->batch_number.'.csv', 'local');
                if ($storedPath === false) {
                    throw ValidationException::withMessages([
                        'file' => 'Não foi possível guardar o ficheiro de importação.',
                    ]);
                }

                $batch->forceFill(['storage_path' => $storedPath])->save();

                $contents = Storage::disk('local')->get($storedPath) ?? '';
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
            });
        } catch (\Throwable $exception) {
            if (is_string($storedPath) && Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function process(PaymentImportBatch $batch, User $actor): PaymentImportBatch
    {
        return DB::transaction(function () use ($batch, $actor) {
            $lockedBatch = PaymentImportBatch::query()
                ->whereKey($batch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->batchHasStatus($lockedBatch, [
                PaymentImportStatus::Processed,
                PaymentImportStatus::PartiallyProcessed,
            ])) {
                return $lockedBatch;
            }

            $lockedBatch->forceFill(['status' => PaymentImportStatus::Processing])->save();

            foreach ($lockedBatch->rows()->where('status', PaymentImportRowStatus::Pending)->lockForUpdate()->get() as $row) {
                /** @var PaymentImportRow $row */
                $installment = $this->municipalScope
                    ->rentInstallments(RentInstallment::query(), $actor)
                    ->where('reference', $row->reference)
                    ->first();

                if (! $installment || ! $row->amount || ! $row->payment_date) {
                    $row->forceFill(['status' => PaymentImportRowStatus::Unmatched, 'failure_reason' => 'Referência, valor ou data inválidos.'])->save();

                    continue;
                }

                $account = $installment->tenantFinancialAccount;

                if (! $account instanceof TenantFinancialAccount) {
                    $row->forceFill(['status' => PaymentImportRowStatus::Failed, 'failure_reason' => 'Prestação sem conta financeira associada.'])->save();

                    continue;
                }

                $payment = LeasePayment::query()
                    ->where('tenant_financial_account_id', $account->id)
                    ->where('external_reference', $row->reference)
                    ->whereDate('payment_date', $this->dateString($row))
                    ->where('amount', DecimalMoney::normalize((string) $row->amount))
                    ->where('source', 'csv_import')
                    ->where('status', '!=', LeasePaymentStatus::Reversed->value)
                    ->lockForUpdate()
                    ->first();

                if (! $payment instanceof LeasePayment) {
                    $payment = $this->payments->store($account, $actor, [
                        'amount' => DecimalMoney::normalize((string) $row->amount),
                        'payment_date' => $this->dateString($row),
                        'method' => 'bank_import',
                        'source' => 'csv_import',
                        'external_reference' => $row->reference,
                        'payer_name' => $row->payer_name,
                        'confirm_now' => true,
                    ]);
                }

                $allocatable = DecimalMoney::min(
                    (string) $payment->unallocated_amount,
                    (string) $installment->amount_outstanding,
                );
                if (DecimalMoney::isPositive($allocatable)) {
                    $this->allocations->allocate($payment, $installment, $actor, $allocatable);
                }

                $row->forceFill([
                    'status' => PaymentImportRowStatus::Imported,
                    'lease_payment_id' => $payment->id,
                    'rent_installment_id' => $installment->id,
                    'tenant_financial_account_id' => $installment->tenant_financial_account_id,
                    'user_id' => $installment->user_id,
                ])->save();
            }

            $imported = $lockedBatch->rows()->where('status', PaymentImportRowStatus::Imported)->count();
            $failed = $lockedBatch->rows()->whereIn('status', [PaymentImportRowStatus::Unmatched, PaymentImportRowStatus::Failed])->count();
            $lockedBatch->forceFill([
                'status' => $failed > 0 ? PaymentImportStatus::PartiallyProcessed : PaymentImportStatus::Processed,
                'matched_count' => $imported,
                'imported_count' => $imported,
                'failed_count' => $failed,
                'processed_at' => now(),
                'processed_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $lockedBatch, 'finance', 'payment_import_process', 'Lote CSV de pagamentos processado.');

            return $lockedBatch->refresh();
        });
    }

    private function assertNotDuplicate(UploadedFile $file, User $actor): void
    {
        $incomingChecksum = hash_file('sha256', $file->getRealPath());
        $possibleDuplicates = PaymentImportBatch::query()
            ->where('municipality_id', $actor->municipality_id)
            ->where('original_filename', $file->getClientOriginalName())
            ->whereNotNull('storage_path')
            ->latest()
            ->limit(100)
            ->get(['storage_disk', 'storage_path']);

        foreach ($possibleDuplicates as $possibleDuplicate) {
            $disk = $possibleDuplicate->storage_disk;
            $path = $possibleDuplicate->storage_path;

            if (! is_string($disk) || ! is_string($path) || ! Storage::disk($disk)->exists($path)) {
                continue;
            }

            $storedContents = Storage::disk($disk)->get($path);
            if (is_string($storedContents) && hash('sha256', $storedContents) === $incomingChecksum) {
                throw ValidationException::withMessages([
                    'file' => 'Este ficheiro de pagamentos já foi importado.',
                ]);
            }
        }
    }

    /**
     * @param  list<PaymentImportStatus>  $statuses
     */
    private function batchHasStatus(PaymentImportBatch $batch, array $statuses): bool
    {
        $actual = $batch->getAttribute('status');

        foreach ($statuses as $expected) {
            if ($actual === $expected || $actual === $expected->value) {
                return true;
            }
        }

        return false;
    }

    private function dateString(PaymentImportRow $row): string
    {
        $paymentDate = $row->getAttribute('payment_date');

        return $paymentDate instanceof CarbonInterface
            ? $paymentDate->toDateString()
            : (string) $paymentDate;
    }
}
