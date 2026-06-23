<?php

namespace App\Policies;

use App\Models\KeyHandoverAppointment;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class KeyHandoverAppointmentPolicy
{
    use ChecksPermissions;

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
}
