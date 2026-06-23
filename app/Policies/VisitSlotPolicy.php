<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitSlot;
use App\Policies\Concerns\ChecksPermissions;

class VisitSlotPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'visits', 'view');
    }

    public function view(User $user, VisitSlot $slot): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, VisitSlot $slot): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'visits', 'update');
    }
}
