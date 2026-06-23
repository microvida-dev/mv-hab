<?php

namespace App\Policies;

use App\Models\PaymentReceipt;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class PaymentReceiptPolicy
{
    use ChecksFinanceAccess;

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
}
