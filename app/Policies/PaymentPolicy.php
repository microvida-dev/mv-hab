<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class PaymentPolicy
{
    use ChecksPermissions;

    private const MODULE = 'payments';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, Payment $payment): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsPayment($user, $payment);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutateBackoffice($user, 'create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(User $user, Payment $payment): bool
    {
        return $this->canMutateBackoffice($user, 'update')
            && $this->municipalScope->ownsPayment($user, $payment);
    }

    public function deleteBackoffice(User $user, Payment $payment): bool
    {
        return $this->canMutateBackoffice($user, 'delete')
            && $this->municipalScope->ownsPayment($user, $payment);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, $action);
    }
}
