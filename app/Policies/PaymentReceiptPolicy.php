<?php

namespace App\Policies;

use App\Models\LeasePayment;
use App\Models\PaymentReceipt;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class PaymentReceiptPolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, PaymentReceipt $paymentReceipt): bool
    {
        return $user->hasRole('candidate') ? $this->ownsFinanceRecord($user, $paymentReceipt) && $this->canViewFinance($user) : $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateFinance($user);
    }

    public function update(User $user, PaymentReceipt $paymentReceipt): bool
    {
        return $this->canManageFinance($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'payments', 'receipts.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, PaymentReceipt $receipt): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsPaymentReceipt($user, $receipt);
    }

    public function generateBackoffice(User $user, LeasePayment $payment): bool
    {
        return $this->canMutateBackoffice($user, 'receipts.generate')
            && $this->municipalScope->ownsLeasePayment($user, $payment);
    }

    public function downloadBackoffice(User $user, PaymentReceipt $receipt): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'payments', 'receipts.download')
            && $this->municipalScope->ownsPaymentReceipt($user, $receipt);
    }

    public function cancelBackoffice(User $user, PaymentReceipt $receipt): bool
    {
        return $this->canMutateBackoffice($user, 'receipts.cancel')
            && $this->municipalScope->ownsPaymentReceipt($user, $receipt);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'payments', $action);
    }
}
