<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class PaymentPolicy
{
    use ChecksPermissions;

    private const MODULE = 'payments';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }

    public function approve(User $user, Payment $payment): bool
    {
        return $this->canAccess($user, self::MODULE, 'approve');
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $this->canAccess($user, self::MODULE, 'reject');
    }
}
