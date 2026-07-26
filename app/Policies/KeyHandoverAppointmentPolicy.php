<?php

namespace App\Policies;

use App\Models\KeyHandoverAppointment;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class KeyHandoverAppointmentPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $user->hasRole('candidate')
            ? $this->canAccess($user, 'allocations', 'view')
            : $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, KeyHandoverAppointment $appointment): bool
    {
        if ($user->hasRole('candidate')) {
            return $appointment->user_id === $user->id && $this->canAccess($user, 'allocations', 'view');
        }

        return $this->canAccess($user, 'allocations', 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function update(User $user, KeyHandoverAppointment $appointment): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'contracts', 'key_handovers.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, KeyHandoverAppointment $appointment): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsKeyHandoverAppointment($user, $appointment);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutate($user, 'schedule')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function scheduleBackoffice(User $user): bool
    {
        return $this->createBackoffice($user);
    }

    public function updateBackoffice(User $user, KeyHandoverAppointment $appointment): bool
    {
        return $this->canMutate($user, 'update')
            && $this->municipalScope->ownsKeyHandoverAppointment($user, $appointment);
    }

    public function completeBackoffice(User $user, KeyHandoverAppointment $appointment): bool
    {
        return $this->canMutate($user, 'complete')
            && $this->municipalScope->ownsKeyHandoverAppointment($user, $appointment);
    }

    public function cancelBackoffice(User $user, KeyHandoverAppointment $appointment): bool
    {
        return $this->canMutate($user, 'cancel')
            && $this->municipalScope->ownsKeyHandoverAppointment($user, $appointment);
    }

    private function canMutate(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'contracts', "key_handovers.{$action}");
    }
}
