<?php

namespace App\Services\Finance;

use App\Models\AnnualDocumentUpdateRequest;
use App\Models\DefaultNotice;
use App\Models\LeasePayment;
use App\Models\PaymentImportBatch;
use App\Models\PaymentReceipt;
use App\Models\RegularizationAgreement;
use App\Models\TenantFinancialAccount;

class FinanceNumberService
{
    public function accountNumber(): string
    {
        return $this->next('CF', TenantFinancialAccount::class, 'account_number');
    }

    public function paymentNumber(): string
    {
        return $this->next('PG', LeasePayment::class, 'payment_number');
    }

    public function receiptNumber(): string
    {
        return $this->next('RC', PaymentReceipt::class, 'receipt_number');
    }

    public function noticeNumber(): string
    {
        return $this->next('INC', DefaultNotice::class, 'notice_number');
    }

    public function agreementNumber(): string
    {
        return $this->next('ACR', RegularizationAgreement::class, 'agreement_number');
    }

    public function importBatchNumber(): string
    {
        return $this->next('IMP', PaymentImportBatch::class, 'batch_number');
    }

    public function annualUpdateNumber(): string
    {
        return $this->next('DOC', AnnualDocumentUpdateRequest::class, 'request_number');
    }

    public function rentInstallmentReference(int $contractId, int $year, int $month): string
    {
        return sprintf('REN-%06d-%04d%02d', $contractId, $year, $month);
    }

    private function next(string $prefix, string $model, string $column): string
    {
        $year = now()->format('Y');
        $count = $model::query()
            ->where($column, 'like', "{$prefix}-{$year}-%")
            ->withTrashed()
            ->count() + 1;

        return sprintf('%s-%s-%05d', $prefix, $year, $count);
    }
}
