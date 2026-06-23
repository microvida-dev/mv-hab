<?php

namespace App\Policies;

use App\Models\PaymentImportBatch;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class PaymentImportBatchPolicy
{
    use ChecksFinanceAccess;

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
}
