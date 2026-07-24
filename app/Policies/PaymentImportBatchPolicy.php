<?php

namespace App\Policies;

use App\Models\PaymentImportBatch;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class PaymentImportBatchPolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canViewFinance($user);
    }

    public function view(User $user, PaymentImportBatch $paymentImportBatch): bool
    {
        return ! $user->hasRole('candidate') && $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateFinance($user);
    }

    public function update(User $user, PaymentImportBatch $paymentImportBatch): bool
    {
        return $this->canManageFinance($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'payments', 'imports.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, PaymentImportBatch $batch): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsPaymentImportBatch($user, $batch);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutateBackoffice($user, 'imports.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function processBackoffice(User $user, PaymentImportBatch $batch): bool
    {
        return $this->canMutateBackoffice($user, 'imports.process')
            && $this->municipalScope->ownsPaymentImportBatch($user, $batch);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'payments', $action);
    }
}
